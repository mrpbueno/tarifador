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
    /**
     * @param array $post
     * @return array|null
     */
    public function getCel($post)
    {
        $sql = "SELECT id, eventtime, eventtype, cid_num, cid_name, exten, cid_dnid, context, channame, uniqueid, linkedid ";
        $sql .= "FROM asteriskcdrdb.cel WHERE linkedid = :uniqueid OR uniqueid = :uniqueid ORDER BY id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':uniqueid', $post['uniqueid'], PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($data) ? $data : null;
    }
}