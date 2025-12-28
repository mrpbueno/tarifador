<?php

namespace FreePBX\modules\Tarifador\Traits;

use FreePBX\modules\Tarifador\Utils\Sanitize;
use PDO;
use PDOException;
use Exception;

/**
 * Trait PinUserTrait
 * @package FreePBX\modules\Tarifador\Traits
 * @author Mauro <https://github.com/mrpbueno>
 */
trait PinUserTrait
{
    /**
     * Deletes a PIN user from the database.
     *
     * @param mixed $id The ID of the user to be deleted.
     * @return void
     */
    private function deletePinUser(mixed $id): void
    {
        $validated_id = Sanitize::int($id);
        if ($validated_id === false || $validated_id <= 0) {
            $_SESSION['toast_message'] = ['message' => 'Invalid or missing ID for deletion.', 'title' => 'Validation Error', 'level' => 'error'];
            return;
        }

        $sql = "DELETE FROM tarifador_pinuser WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute(['id' => $validated_id]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['toast_message'] = ['message' => 'User deleted successfully!', 'title' => 'Success', 'level' => 'success'];
            } else {
                $_SESSION['toast_message'] = ['message' => 'User not found or already deleted.', 'title' => 'Warning', 'level' => 'warning'];
            }
        } catch (PDOException $e) {
            $_SESSION['toast_message'] = ['message' => 'A database error occurred. The user could not be deleted.', 'title' => 'Error', 'level' => 'error'];
        }

        redirect('config.php?display=tarifador&page=pinuser');
    }

    /**
     * Updates an existing user/PIN data.
     *
     * @param array $post The $_POST array with the form data.
     * @return void
     */
    private function updatePinUser(array $post): void
    {
        $id = Sanitize::int($post['id'] ?? null);
        $user = Sanitize::string($post['user'] ?? '');
        $department = Sanitize::string($post['department'] ?? '');

        if ($id === false || $id <= 0 || empty($user)) {
            $_SESSION['toast_message'] = ['message' => 'Invalid or missing ID or username.', 'title' => 'Validation Error', 'level' => 'error'];
            return;
        }

        try {
            $sql = "UPDATE tarifador_pinuser SET user = :user, department = :department WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':user' => $user,
                ':department' => $department
            ]);
            $_SESSION['toast_message'] = ['message' => 'User updated successfully!', 'title' => 'Success', 'level' => 'success'];

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $_SESSION['toast_message'] = ['message' => 'The provided username is already in use by another PIN.', 'title' => 'Error', 'level' => 'error'];
            } else {
                $_SESSION['toast_message'] = ['message' => 'An unexpected database error occurred while saving the changes.', 'title' => 'Error', 'level' => 'error'];
            }
            return;
        }

        redirect('config.php?display=tarifador&page=pinuser');
    }
    /**
     * Adds a user/PIN.
     *
     * @param array $data The data for the new user/PIN.
     * @return void
     */
    private function addPinUser(array $data): void
    {
        $sql = "INSERT INTO tarifador_pinuser (pin, user, department, enabled) VALUES (:pin, :user, :department, :enabled)";
        $params = [
            ':pin' => Sanitize::string($data['pin'] ?? ''),
            ':user' => Sanitize::string($data['user'] ?? ''),
            ':department' => Sanitize::string($data['department'] ?? ''),
            ':enabled' => Sanitize::int($data['enabled'] ?? 1),
        ];

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate key
                $updateSql = "UPDATE tarifador_pinuser SET enabled = 1 WHERE pin = :pin";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute(['pin' => $params[':pin']]);
            }
        }
    }

    /**
     * Synchronizes PINs from FreePBX with the Tarifador/User module.
     * After synchronization, it is possible to associate the PIN with the user name via form or CSV.
     *
     * @return void
     */
    private function syncPinUser(): void
    {
        try {
            // 1. GET ALL VALID PINS FROM FreePBX
            $sqlFetchPins = "SELECT passwords FROM pinsets WHERE passwords != ''";
            $stmtFetchPins = $this->db->query($sqlFetchPins);

            $all_freepbx_pins = [];
            foreach ($stmtFetchPins->fetchAll(PDO::FETCH_COLUMN) as $passwords_blob) {
                $pins_from_row = array_filter(array_map('trim', explode("\n", $passwords_blob)));
                if (!empty($pins_from_row)) {
                    $all_freepbx_pins = array_merge($all_freepbx_pins, $pins_from_row);
                }
            }
            $all_freepbx_pins = array_unique($all_freepbx_pins);

            if (empty($all_freepbx_pins)) {
                // If there are no PINs in FreePBX, disable all and exit.
                $this->db->exec("UPDATE tarifador_pinuser SET enabled = 0");
                $_SESSION['toast_message'] = ['message' => 'No PINs found in FreePBX. All users have been disabled.', 'title' => 'Warning', 'level' => 'warning'];
                redirect('config.php?display=tarifador&page=pinuser');
                return;
            }

            // 2. START TRANSACTION (ENSURE INTEGRITY)
            $this->db->beginTransaction();

            // 3. DISABLE USERS THAT NO LONGER EXIST
            $placeholders_not_in = implode(',', array_fill(0, count($all_freepbx_pins), '?'));
            $sqlDisable = "UPDATE tarifador_pinuser SET enabled = 0 WHERE pin NOT IN ($placeholders_not_in)";
            $stmtDisable = $this->db->prepare($sqlDisable);
            $stmtDisable->execute(array_values($all_freepbx_pins));

            // 4. INSERT NEW PINS AND ACTIVATE/UPDATE EXISTING ONES IN A SINGLE QUERY
            $sqlUpsert_parts = ["INSERT INTO tarifador_pinuser (pin, user, department, enabled) VALUES"];
            $insert_rows = [];
            $params = [];
            foreach ($all_freepbx_pins as $pin) {
                $insert_rows[] = '(?, ?, ?, ?)';
                array_push($params, $pin, '---', '---', 1);
            }
            $sqlUpsert_parts[] = implode(', ', $insert_rows);
            $sqlUpsert_parts[] = "ON DUPLICATE KEY UPDATE enabled = 1, user = IF(user = '---' OR user IS NULL, VALUES(user), user), department = IF(department = '---' OR department IS NULL, VALUES(department), department)";

            $stmtUpsert = $this->db->prepare(implode(' ', $sqlUpsert_parts));
            $stmtUpsert->execute($params);

            // 5. IF EVERYTHING WENT WELL, COMMIT THE TRANSACTION
            $this->db->commit();
            $_SESSION['toast_message'] = ['message' => 'PIN synchronization completed successfully!', 'title' => 'Success', 'level' => 'success'];

        } catch (PDOException $e) {
            // 6. IF ANY ERROR OCCURRED, ROLLBACK EVERYTHING
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['toast_message'] = ['message' => 'A critical error occurred during synchronization. No changes were saved.', 'title' => 'Error', 'level' => 'error'];
        }

        redirect('config.php?display=tarifador&page=pinuser');
    }
    /**
     * Gets the list of all PIN users.
     *
     * @return array|null A list of all PIN users or null on failure.
     */
    private function getListPinUser(): ?array
    {
        $sql = "SELECT * FROM tarifador_pinuser";
        $data = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return $data === false ? null : $data;
    }

    /**
     * Gets a single PIN user by their ID.
     *
     * @param mixed $id The ID of the user.
     * @return array|false An array with the user data or false if not found.
     */
    private function getOnePinUser(mixed $id): array|false
    {
        $validated_id = Sanitize::int($id);
        if ($validated_id === false || $validated_id <= 0) {
            $_SESSION['toast_message'] = ['message' => 'Invalid or missing ID.', 'title' => 'Validation Error', 'level' => 'error'];
            return false;
        }
        $sql = "SELECT * FROM tarifador_pinuser WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $validated_id]);
        $pinuser = $stmt->fetchObject();

        if ($pinuser === false) {
            return false; // User not found
        }

        $sql = "SELECT pinsets_id, description FROM pinsets WHERE passwords LIKE :passwords";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['passwords' => "%{$pinuser->pin}%"]);
        $pinsets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'id' => $pinuser->id,
            'pin' => $pinuser->pin,
            'user' => $pinuser->user,
            'department' => $pinuser->department,
            'enabled' => $pinuser->enabled,
            'pinsets' => $pinsets,
        ];
    }

    /**
     * Imports a CSV file with a list of pin, user, and department.
     *
     * @param array $post The $_POST array containing the uploaded file data.
     * @return void Redirects the user after the operation.
     */
    private function importPinUser(array $post): void
    {
        // 1. ROBUST UPLOAD VALIDATION
        if (!isset($_FILES['user_file']) || $_FILES['user_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['toast_message'] = ['message' => 'Error uploading the file or no file sent.', 'title' => 'Error', 'level' => 'error'];
            redirect('config.php?display=tarifador&page=pinuser');
            return;
        }

        $file_path = $_FILES['user_file']['tmp_name'];

        // Validate MIME type to ensure it is a text/csv file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);

        if (!in_array($mime_type, ['text/csv', 'text/plain'], true)) {
            $_SESSION['toast_message'] = ['message' => 'Invalid file format. Please upload a .csv file.', 'title' => 'Error', 'level' => 'error'];
            redirect('config.php?display=tarifador&page=pinuser');
            return;
        }

        $file = fopen($file_path, 'r');
        if ($file === false) {
            $_SESSION['toast_message'] = ['message' => 'Could not open the uploaded file.', 'title' => 'Error', 'level' => 'error'];
            redirect('config.php?display=tarifador&page=pinuser');
            return;
        }

        // 2. PREPARATION OUTSIDE THE LOOP AND START OF THE TRANSACTION
        try {
            $header = fgetcsv($file, 5000, ",");
            $expected_header = ['pin', 'user', 'department'];
            if (empty($header) || count(array_diff($expected_header, array_map('strtolower', $header))) > 0) {
                $_SESSION['toast_message'] = ['message' => 'Invalid CSV header. The expected columns are: pin, user, department.', 'title' => 'Format Error', 'level' => 'error'];
                redirect('config.php?display=tarifador&page=pinuser');
                return;
            }

            $this->db->beginTransaction();
            $sqlUpdate = "UPDATE tarifador_pinuser SET user = :user, department = :department WHERE pin = :pin";
            $stmtUpdate = $this->db->prepare($sqlUpdate);

            $sqlInsert = "INSERT INTO tarifador_pinuser (pin, user, department, enabled) VALUES (:pin, :user, :department, 1)";
            $stmtInsert = $this->db->prepare($sqlInsert);

            $line_number = 1;
            // 3. LINE-BY-LINE PROCESSING (LOW MEMORY USAGE)
            while (($row_data = fgetcsv($file, 5000, ",")) !== false) {
                $line_number++;
                if (count($header) !== count($row_data)) {
                    continue; // Skip malformed lines
                }
                $row = array_combine($header, $row_data);

                // Line validation and sanitization
                $pin = Sanitize::stringInput($row['pin'] ?? null);
                $user = Sanitize::stringInput($row['user'] ?? null);
                $department = Sanitize::stringInput($row['department'] ?? null);

                if (empty($pin) || empty($user)) {
                    continue; // Skip lines without pin or user
                }

                // Try UPDATE
                $stmtUpdate->execute([':user' => $user, ':department' => $department, ':pin' => $pin]);

                // If no rows were affected, do INSERT
                if ($stmtUpdate->rowCount() === 0) {
                    $stmtInsert->execute([':pin' => $pin, ':user' => $user, ':department' => $department]);
                }
            }

            // If everything went well, commit the changes
            $this->db->commit();
            $_SESSION['toast_message'] = ['message' => 'Import completed successfully!', 'title' => 'Success', 'level' => 'success'];

        } catch (Exception $e) {
            // 4. If any error occurs, undo ALL changes
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['toast_message'] = ['message' => "An error occurred on line {$line_number} during import. No changes were saved.", 'title' => 'Critical Error', 'level' => 'error'];
        } finally {
            fclose($file);
        }

        redirect('config.php?display=tarifador&page=pinuser');
    }

    /**
     * Searches for departments to populate a select input.
     *
     * @param array $request The request data, usually containing a 'term' for searching.
     * @return array A list of matching departments.
     */
    private function getDepartment(array $request): array
    {
        $sql = "SELECT DISTINCT department AS name FROM tarifador_pinuser WHERE department LIKE :department LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $term = Sanitize::string($request['term'] ?? '');
        $stmt->execute(['department' => "%{$term}%"]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data ?: [];
    }

    /**
     * Searches for users to populate a select input.
     *
     * @param array $request The request data, usually containing a 'term' for searching.
     * @return array A list of matching users, formatted for a select input.
     */
    private function getUser(array $request): array
    {
        $sql = "SELECT pin AS id, user AS text FROM tarifador_pinuser WHERE user LIKE :user LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $term = Sanitize::string($request['term'] ?? '');
        $stmt->execute(['user' => "%{$term}%"]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data ?: [];
    }

    /**
     * Gets the user's name associated with a given PIN.
     *
     * @param string $pin The PIN to search for.
     * @return string The user's name or a default string "Not Registered" if not found.
     */
    private function getPinUser(string $pin): string
    {
        if (empty($pin)) {
            return '';
        }
        $pin = Sanitize::string($pin);
        $sql = "SELECT user FROM tarifador_pinuser WHERE pin = :pin LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pin' => $pin]);
        $pinuser = $stmt->fetchObject();

        return $pinuser->user ?? _("Not Registered");
    }
}