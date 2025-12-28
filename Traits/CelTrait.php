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
     * Retrieves CEL (Channel Event Logging) records based on a unique ID.
     * Searches for records where either `linkedid` or `uniqueid` matches the provided unique ID.
     *
     * @param array $post An associative array that must contain a 'uniqueid' key.
     *                    Example: `['uniqueid' => '1234567890.1']`
     * @return array|null An array of associative arrays, each representing a CEL record,
     *                    or null if the 'uniqueid' is not provided in $post or no records are found.
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