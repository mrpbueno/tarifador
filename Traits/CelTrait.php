<?php


namespace FreePBX\modules\Tarifador\Traits;

use FreePBX\modules\Tarifador\Utils\Sanitize;
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
    public function getCel(array $post): ?array
    {
        if (!isset($post['uniqueid'])) {
            return null;
        }
        $uniqueid = Sanitize::string($post['uniqueid']);
        $sql = "SELECT id, eventtime, eventtype, cid_num, cid_name, exten, cid_dnid, context, channame, uniqueid, linkedid
                FROM asteriskcdrdb.cel
                WHERE linkedid = :uniqueid OR uniqueid = :uniqueid
                ORDER BY id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uniqueid' => $uniqueid]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data === false ? null : $data;
    }
}