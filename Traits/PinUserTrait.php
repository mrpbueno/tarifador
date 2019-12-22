<?php

namespace FreePBX\modules\Tarifador\Traits;


use PDO;
use PDOException;

trait PinUserTrait
{
    /**
     * @param $id
     * @return bool|void
     */
    private function deletePinuser($id)
    {
        $sql = "DELETE FROM tarifador_pinuser WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            echo "<script>javascript:alert('"._($stmt->getMessage()."<br><br>".$sql)."')</script>";
            return false;
        }

        return redirect('config.php?display=tarifador&page=pinuser');
    }

    /**
     * @param $post
     * @return bool|void
     */
    private function updatePinuser($post)
    {
        $sql = "UPDATE tarifador_pinuser SET user = :user, department = :department WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $post['id'], PDO::PARAM_INT);
        $stmt->bindParam(':user', $post['user'], PDO::PARAM_STR);
        $stmt->bindParam(':department', $post['department'], PDO::PARAM_STR);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                echo "<script>javascript:alert('"._("Error! Duplicate pin.")."')</script>";
                return false;
            } else {
                echo "<script>javascript:alert('"._($stmt->getMessage()."<br><br>".$sql)."')</script>";
                return false;
            }
        }

        return redirect('config.php?display=tarifador&page=pinuser');
    }

    /**
     * @param $data
     */
    private function addPinuser($data)
    {
        $sql = "INSERT INTO tarifador_pinuser (pin, user, department, enabled) VALUES (:pin, :user, :department, :enabled)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':pin', $data['pin'], PDO::PARAM_STR);
        $stmt->bindParam(':user', $data['user'], PDO::PARAM_STR);
        $stmt->bindParam(':department', $data['department'], PDO::PARAM_STR);
        $stmt->bindParam(':enabled', $data['enabled'], PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $sql = "UPDATE tarifador_pinuser SET enabled = 1 WHERE pin = :pin";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':pin', $data['pin'], PDO::PARAM_STR);
                $stmt->execute();
            }
        }
    }

    /**
     *
     */
    private function syncPinuser()
    {
        $sql = "UPDATE tarifador_pinuser SET enabled = 0 ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $sql = "SELECT * FROM pinsets";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (is_array($data)) {
            foreach ($data as $d) {
                if ($d['passwords'] != "") {
                    $passwords = explode("\n", $d['passwords']);
                    foreach ($passwords as $key => $value) {
                        $this->addPinuser([
                            'pin' => $value,
                            'user' => '---',
                            'department' => '---',
                            'enabled' => 1,
                            'pinsets_id' => $d['pinsets_id'],
                        ]);
                    }
                }
            }
        }

        return redirect('config.php?display=tarifador&page=pinuser');
    }

    /**
     * @return array|null
     */
    private function getListPinuser()
    {
        $sql = 'SELECT * FROM tarifador_pinuser';
        $data = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return is_array($data) ? $data : null;
    }

    /**
     * @param $id
     * @return array
     */
    private function getOnePinuser($id)
    {
        $sql = "SELECT * FROM tarifador_pinuser WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $pinuser = $stmt->fetchObject();

        $sql = "SELECT pinsets_id, description FROM pinsets WHERE passwords LIKE :passwords";
        $stmt = $this->db->prepare($sql);
        $password = "%".$pinuser->pin."%";
        $stmt->bindParam(':passwords', $password, PDO::PARAM_STR);
        $stmt->execute();
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
     * @param $post
     */
    private function importPinuser($post)
    {
        if (isset($_FILES['user_file']) && $_FILES['user_file']['tmp_name'] != '') {
            $extension = pathinfo($_FILES["user_file"]["name"], PATHINFO_EXTENSION);
            $extension = strtolower($extension);
            if ($extension == 'csv') {
                $file = fopen($_FILES['user_file']['tmp_name'], 'r');
                if ($file !== false) {
                    $rows = [];
                    $header = fgetcsv($file, 5000, ",");
                    while (($row = fgetcsv($file, 5000, ",", "\"")) !== false) {
                        $rows[] = array_combine($header, $row);
                    }
                    foreach ($rows as $row) {
                        $pin = isset($row['pin']) ? htmlspecialchars(trim($row['pin'])) : '';
                        $user = isset($row['user']) ? htmlspecialchars(trim($row['user'])) : '';
                        $department = isset($row['department']) ? htmlspecialchars(trim($row['department'])) : '';
                        if ($pin != '' && $user != '' && $department != '') {
                            $sql = "UPDATE tarifador_pinuser SET user = :user, department = :department WHERE pin = :pin";
                            $stmt = $this->db->prepare($sql);
                            $stmt->bindParam(':pin', $pin, PDO::PARAM_STR);
                            $stmt->bindParam(':user', $user, PDO::PARAM_STR);
                            $stmt->bindParam(':department', $department, PDO::PARAM_STR);
                            $stmt->execute();

                            if ($stmt->rowCount() == 0) {
                                $data = [
                                    'pin' => $pin,
                                    'user' => $user,
                                    'department' => $department,
                                    'enabled' => 1
                                ];
                                $this->addPinuser($data);
                            }
                        }
                    }
                }
            }
        }

        return redirect('config.php?display=tarifador&page=pinuser');
    }

    /**
     * @param $request
     * @return mixed
     */
    private function getDepartment($request)
    {
        $sql = "SELECT DISTINCT department AS name FROM tarifador_pinuser WHERE department LIKE :department LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $department = '%'.$request['term'].'%';
        $stmt->bindParam(':department', $department, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data;
    }

    /**
     * @param $request
     * @return mixed
     */
    private function getUser($request)
    {
        $sql = "SELECT pin AS id, user AS text FROM tarifador_pinuser WHERE user LIKE :user LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $q = isset($request['q']) ? $request['q'] : '';
        $user = '%'.$q.'%';
        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data;
    }
}