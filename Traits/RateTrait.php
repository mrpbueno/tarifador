<?php


namespace FreePBX\modules\Tarifador\Traits;

use FreePBX\modules\Tarifador\Utils\Sanitize;
use PDO;
use PDOException;

/**
 * Trait RateTrait
 * @package FreePBX\modules\Tarifador\Traits
 * @author Mauro <https://github.com/mrpbueno>
 */
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
     * @param int $id
     * @return array
     */
    private function getOneRate($id)
    {
        $sql = "SELECT * FROM tarifador_rate WHERE id = :id LIMIT 1";
        $stmt = $this->Database->prepare($sql);
        $stmt->bindParam(':id', Sanitize::int($id), PDO::PARAM_INT);
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
     * @param array $post
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
        $stmt->bindParam(':name', Sanitize::string($post['name']), PDO::PARAM_STR);
        $stmt->bindParam(':telco', Sanitize::string($post['telco']), PDO::PARAM_STR);
        $stmt->bindParam(':dial_pattern', Sanitize::string($post['dial_pattern']), PDO::PARAM_STR);
        $stmt->bindParam(':rate', Sanitize::string($post['rate']), PDO::PARAM_STR);
        $stmt->bindParam(':start', Sanitize::string($post['start']), PDO::PARAM_STR);
        $stmt->bindParam(':end', Sanitize::string($post['end']), PDO::PARAM_STR);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            echo "<script>javascript:alert('"._($stmt->getMessage()."<br><br>".$sql)."')</script>";
            return false;
        }

        return redirect('config.php?display=tarifador&page=rate');
    }

    /**
     * @param array $post
     * @return bool|void
     */
    private function updateRate($post)
    {
        $sql = 'UPDATE tarifador_rate SET name = :name, telco = :telco, dial_pattern = :dial_pattern, ';
        $sql .= 'rate = :rate, start = :start, end = :end WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', Sanitize::int($post['id']), PDO::PARAM_INT);
        $stmt->bindParam(':name', Sanitize::string($post['name']), PDO::PARAM_STR);
        $stmt->bindParam(':telco', Sanitize::string($post['telco']), PDO::PARAM_STR);
        $stmt->bindParam(':dial_pattern', Sanitize::string($post['dial_pattern']), PDO::PARAM_STR);
        $stmt->bindParam(':rate', Sanitize::string($post['rate']), PDO::PARAM_STR);
        $stmt->bindParam(':start', Sanitize::string($post['start']), PDO::PARAM_STR);
        $stmt->bindParam(':end', Sanitize::string($post['end']), PDO::PARAM_STR);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            echo "<script>javascript:alert('"._($stmt->getMessage()."<br><br>".$sql)."')</script>";
            return false;
        }

        return redirect('config.php?display=tarifador&page=rate');
    }

    /**
     * @param array $post
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
     * @param int $id
     * @return bool|void
     */
    private function deleteRate($id)
    {
        $id = Sanitize::int($id);
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
     * @param string $dialPattern
     * @param string $start
     * @param string $end
     * @return mixed
     */
    private function testDate($dialPattern, $start, $end)
    {
        $dialPattern = Sanitize::string($dialPattern);
        $start = Sanitize::string($start);
        $end = Sanitize::string($end);
        $sql = "SELECT dial_pattern, start, end FROM tarifador_rate WHERE dial_pattern = :dial_pattern ";
        $sql .= "AND ((start BETWEEN :start AND :end) OR (end BETWEEN :start AND :end) ";
        $sql .= "OR (start < :start AND end > :end)) LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':dial_pattern', $dialPattern, PDO::PARAM_STR);
        $stmt->bindParam(':start', $start, PDO::PARAM_STR);
        $stmt->bindParam(':end', $end, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    /**
     * @param string $callDate
     * @return mixed
     */
    private function getRate($callDate)
    {
        $callDate = Sanitize::string($callDate);
        $sql = 'SELECT name, dial_pattern, rate FROM tarifador_rate WHERE start <= :calldate AND end >= :calldate ORDER BY seq ASC';
        $stmt = $this->db->prepare($sql);
        $date = date('Y-m-d',strtotime($callDate));
        $stmt->bindParam(':calldate', $date, PDO::PARAM_STR);
        $stmt->execute();
        $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rates = is_array($rates) ? $rates : null;

        return $rates;
    }
}