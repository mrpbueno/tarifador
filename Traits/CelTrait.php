<?php


namespace FreePBX\modules\Tarifador\Traits;

use PDO;

/**
 * Trait CelTrait
 * @package FreePBX\modules\Tarifador\Traits
 * @author Mauro <https://github.com/mrpbueno>
 */
trait CelTrait
{
    public function getCel($post)
    {
        $sql = "SELECT * FROM asteriskcdrdb.cel WHERE linkedid = :linkedid ORDER BY id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':linkedid', $post['linkedid'], PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($data) ? $data : null;
    }
}