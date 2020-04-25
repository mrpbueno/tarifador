<?php


namespace FreePBX\modules\Tarifador\Traits;

use PDO;
use PDOException;

trait RateTrait
{
    /**
     * @return array|null
     */
    private function getListRate()
    {
        $sql = 'SELECT * FROM tarifador_rate ORDER BY seq ASC';
        $data = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return is_array($data) ? $data : null;
    }

    /**
     * @param $id
     * @return array
     */
    private function getOneRate($id)
    {
        $sql = "SELECT * FROM tarifador_rate WHERE id = :id LIMIT 1";
        $stmt = $this->Database->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetchObject();

        return [
            'id' => $row->id,
            'name' => $row->name,
            'telco' => $row->telco,
            'dial_pattern' => $row->dial_pattern,
            'rate' => $row->rate,
            'start' => $row->start,
            'end' => $row->end,
        ];
    }

    /**
     * @param $post
     * @return bool|void
     */
    private function addRate($post)
    {
        $data = $this->testDate($post['dial_pattern'], $post['start'], $post['end']);
        if (is_array($data)) {
            $error = "Já existe um padrão de discagem na data de vigência escolhida: ";
            $error .= $data['dial_pattern']." - ".$data['start']." - ".$data['end'];
            echo "<script>javascript:alert('"._($error)."')</script>";
            return false;
        }
        $sql = "INSERT INTO tarifador_rate (name, telco, dial_pattern, rate, start, end) ";
        $sql .= "VALUES (:name, :telco, :dial_pattern, :rate, :start, :end)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $post['name'], PDO::PARAM_STR);
        $stmt->bindParam(':telco', $post['telco'], PDO::PARAM_STR);
        $stmt->bindParam(':dial_pattern', $post['dial_pattern'], PDO::PARAM_STR);
        $stmt->bindParam(':rate', $post['rate'], PDO::PARAM_STR);
        $stmt->bindParam(':start', $post['start'], PDO::PARAM_STR);
        $stmt->bindParam(':end', $post['end'], PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            echo "<script>javascript:alert('"._($stmt->getMessage()."<br><br>".$sql)."')</script>";
            return false;
        }

        return redirect('config.php?display=tarifador&page=rate');
    }

    /**
     * @param $post
     * @return bool|void
     */
    private function updateRate($post)
    {
        $sql = 'UPDATE tarifador_rate SET name = :name, telco = :telco, dial_pattern = :dial_pattern, ';
        $sql .= 'rate = :rate, start = :start, end = :end WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $post['id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $post['name'], PDO::PARAM_STR);
        $stmt->bindParam(':telco', $post['telco'], PDO::PARAM_STR);
        $stmt->bindParam(':dial_pattern', $post['dial_pattern'], PDO::PARAM_STR);
        $stmt->bindParam(':rate', $post['rate'], PDO::PARAM_STR);
        $stmt->bindParam(':start', $post['start'], PDO::PARAM_STR);
        $stmt->bindParam(':end', $post['end'], PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            echo "<script>javascript:alert('"._($stmt->getMessage()."<br><br>".$sql)."')</script>";
            return false;
        }

        return redirect('config.php?display=tarifador&page=rate');
    }

    /**
     * @param $post
     * @return bool
     */
    private function updateOrderRate($post)
    {
        $order = $post['data'];
        $sql = "UPDATE tarifador_rate SET seq = :seq WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        foreach($order as $value){
            $stmt->bindParam(':id', $value['id'], PDO::PARAM_INT);
            $stmt->bindParam(':seq', $value['seq'], PDO::PARAM_INT);
            try {
                $stmt->execute();
            } catch (PDOException $e) {
                echo "<script>javascript:alert('"._($stmt->getMessage()."<br><br>".$sql)."')</script>";
                return false;
            }
        }
    }

    /**
     * @param $id
     * @return bool|void
     */
    private function deleteRate($id)
    {
        $sql = "DELETE FROM tarifador_rate WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            echo "<script>javascript:alert('"._($stmt->getMessage()."<br><br>".$sql)."')</script>";
            return false;
        }
        return redirect('config.php?display=tarifador&page=rate');
    }

    /**
     * @param $dial_pattern
     * @param $start
     * @param $end
     * @return mixed
     */
    private function testDate($dial_pattern, $start, $end)
    {
        $sql = "SELECT dial_pattern, start, end FROM tarifador_rate WHERE dial_pattern = :dial_pattern ";
        $sql .= "AND ((start BETWEEN :start AND :end) OR (end BETWEEN :start AND :end) ";
        $sql .= "OR (start < :start AND end > :end)) LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':dial_pattern', $dial_pattern, PDO::PARAM_STR);
        $stmt->bindParam(':start', $start, PDO::PARAM_STR);
        $stmt->bindParam(':end', $end, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    /**
     * @param $number
     * @param $calldate
     * @return mixed
     */
    private function getRate($calldate)
    {
        $sql = 'SELECT name, dial_pattern, rate FROM tarifador_rate WHERE start <= :calldate AND end >= :calldate ORDER BY seq ASC';
        $stmt = $this->db->prepare($sql);
        $date = date('Y-m-d',strtotime($calldate));
        $stmt->bindParam(':calldate', $date, PDO::PARAM_STR);
        $stmt->execute();
        $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rates = is_array($rates) ? $rates : null;

        return $rates;
    }
}