<?php

namespace FreePBX\modules\Tarifador\Traits;

use FreePBX\modules\Tarifador\Utils\Sanitize;
use PDO;
use PDOException;

/**
 * Trait PinUserTrait
 * @package FreePBX\modules\Tarifador\Traits
 * @author Mauro <https://github.com/mrpbueno>
 */
trait PinUserTrait
{
    /**
     * @param int $id
     * @return bool|void
     */
    private function deletePinUser($id)
    {
        $sql = "DELETE FROM tarifador_pinuser WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', Sanitize::int($id), PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            echo "<script>javascript:alert('"._($stmt->getMessage()."<br><br>".$sql)."')</script>";
            return false;
        }

        return redirect('config.php?display=tarifador&page=pinuser');
    }

    /**
     * @param array $post
     * @return bool|void
     */
    private function updatePinUser($post)
    {
        $sql = "UPDATE tarifador_pinuser SET user = :user, department = :department WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', Sanitize::int($post['id']), PDO::PARAM_INT);
        $stmt->bindParam(':user',  Sanitize::string($post['user']), PDO::PARAM_STR);
        $stmt->bindParam(':department', Sanitize::string($post['department']), PDO::PARAM_STR);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                echo "<script>alert('" ._("Erro! PIN duplicado.")."')</script>";
                return false;
            } else {
                echo "<script>alert('" ._($stmt->getMessage()."<br><br>".$sql)."')</script>";
                return false;
            }
        }

        return redirect('config.php?display=tarifador&page=pinuser');
    }

    /**
     * @param array $data
     * @return void
     */
    private function addPinUser($data)
    {
        $sql = "INSERT INTO tarifador_pinuser (pin, user, department, enabled) VALUES (:pin, :user, :department, :enabled)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':pin', Sanitize::string($data['pin']), PDO::PARAM_STR);
        $stmt->bindParam(':user', Sanitize::string($data['user']), PDO::PARAM_STR);
        $stmt->bindParam(':department', Sanitize::string($data['department']), PDO::PARAM_STR);
        $stmt->bindParam(':enabled', Sanitize::int($data['enabled']), PDO::PARAM_INT);
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
     * @return void
     */
    private function syncPinUser()
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
                        $this->addPinUser([
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
    private function getListPinUser()
    {
        $sql = "SELECT * FROM tarifador_pinuser";
        $data = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return is_array($data) ? $data : null;
    }

    /**
     * @param int $id
     * @return array
     */
    private function getOnePinUser($id)
    {
        $sql = "SELECT * FROM tarifador_pinuser WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', Sanitize::int($id), PDO::PARAM_INT);
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
     * @param array $post
     * @return void
     */
    private function importPinUser($post)
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
                        $pin = isset($row['pin']) ? Sanitize::string(trim($row['pin'])) : '';
                        $user = isset($row['user']) ? Sanitize::string(trim($row['user'])) : '';
                        $department = isset($row['department']) ? Sanitize::string(trim($row['department'])) : '';
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
                                $this->addPinUser($data);
                            }
                        }
                    }
                }
            }
        }

        return redirect('config.php?display=tarifador&page=pinuser');
    }

    /**
     * @param array $request
     * @return mixed
     */
    private function getDepartment($request)
    {
        $sql = "SELECT DISTINCT department AS name FROM tarifador_pinuser WHERE department LIKE :department LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $department = '%'.Sanitize::string($request['term']).'%';
        $stmt->bindParam(':department', $department, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data;
    }

    /**
     * @param array $request
     * @return mixed
     */
    private function getUser($request)
    {
        $sql = "SELECT pin AS id, user AS text FROM tarifador_pinuser WHERE user LIKE :user LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $q = isset($request['q']) ? $request['q'] : '';
        $user = '%'.Sanitize::string($q).'%';
        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data;
    }

    /**
     * @param string $pin
     * @return string
     */
    private function getPinUser($pin)
    {
        if (empty($pin)) return '';
        $sql = "SELECT user FROM tarifador_pinuser WHERE pin = :pin LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':pin', Sanitize::string($pin), PDO::PARAM_STR);
        $stmt->execute();
        $pinuser = $stmt->fetchObject();

        return isset($pinuser->user) ? $pinuser->user : _("Sem Cadastro");
    }
}