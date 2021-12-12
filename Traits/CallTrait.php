<?php

namespace FreePBX\modules\Tarifador\Traits;

use FreePBX\modules\Tarifador\Utils\Sanitize;
use PDO;

/**
 * Trait CallTrait
 * @package FreePBX\modules\Tarifador\Traits
 * @author Mauro <https://github.com/mrpbueno>
 */
trait CallTrait
{
    /**
     * @param array $post
     * @return array|null
     */
    private function getListCdr($post)
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $limit = isset($post["limit"]) ? Sanitize::int($post["limit"]) : 100;
        $start = isset($post["offset"]) ? Sanitize::int($post["offset"]) : 0;
        $order = isset($post["order"]) ? $post["order"] : "asc";
        $order = ($order == "asc") ? "asc" : "desc";
        $orderBy = isset($post["sort"]) ? Sanitize::string($post["sort"]) : "calldate";
        switch ($orderBy) {
            case "calldate":
            case "uniqueid":
            case "src":
            case "dst":
                break;
            default:
                $orderBy = "calldate";
        }
        $sql = "SELECT SQL_CALC_FOUND_ROWS calldate, uniqueid, t.user, src, cnam, did, dst, ";
        $sql .= "lastapp, disposition, duration, billsec, (duration -  billsec) AS wait ";
        $sql .= "FROM asteriskcdrdb.cdr ";
        $sql .= "LEFT JOIN asterisk.tarifador_pinuser t ON accountcode = t.pin ";
        $sql .= "WHERE calldate BETWEEN :startDateTime AND :endDateTime ";
        if (is_array($filters))
            foreach ($filters as $filter) {
                $sql .= " AND " . $filter['sql'];
            }
        $sql .= " ORDER BY $orderBy $order LIMIT $start, $limit";

        $stmt = $this->db->prepare($sql);
        $startDateTime = $post['startDate'].' '.$post['startTime'];
        $endDateTime = $post['endDate'].' '.$post['endTime'];
        $stmt->bindParam(':startDateTime', $startDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':endDateTime', $endDateTime, PDO::PARAM_STR);

        if (is_array($filters))
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        $stmt->execute();
        $cdrs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT FOUND_ROWS() as count";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC);

        $cdrs = is_array($cdrs) ? $cdrs : null;

        foreach ($cdrs as $key => $value) {
            if (strlen($cdrs[$key]['src']) == 4 && strlen($cdrs[$key]['dst']) != 4) {
                $cost = $this->cost($cdrs[$key]['dst'],$cdrs[$key]['calldate'],$cdrs[$key]['billsec']);
                $cdrs[$key]['cost'] = $cost['cost'];
                $cdrs[$key]['rate'] = $cost['rate'];
            } else {
                $cdrs[$key]['cost'] = 0;
                $cdrs[$key]['rate'] = "";
            }
        }

        return ["total" => $total['count'], "rows" => $cdrs];
    }

    /**
     * @param array $post
     * @return array
     */
    private function getDisposition($post)
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $sql = 'SELECT disposition, COUNT(disposition) AS value ';
        $sql .= 'FROM asteriskcdrdb.cdr ';
        $sql .= 'WHERE calldate BETWEEN :startDateTime AND :endDateTime ';
        if (is_array($filters))
            foreach ($filters as $filter) {
                $sql .= " AND " . $filter['sql'];
            }
        $sql .= " GROUP BY disposition";

        $stmt = $this->db->prepare($sql);
        $startDateTime = $post['startDate'].' '.$post['startTime'];
        $endDateTime = $post['endDate'].' '.$post['endTime'];
        $stmt->bindParam(':startDateTime', $startDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':endDateTime', $endDateTime, PDO::PARAM_STR);

        if (is_array($filters))
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $disposition = [_("ANSWERED"),_("NO ANSWER"),_("BUSY"),_("FAILED")];
        $value = [0,0,0,0];
        $data = is_array($data) ? $data : null;
        foreach ($data as $key => $v) {
            switch ($data[$key]['disposition']) {
                case "ANSWERED":
                    $value[0] = $data[$key]['value'];
                    break;
                case "NO ANSWER":
                    $value[1] = $data[$key]['value'];
                    break;
                case "BUSY":
                    $value[2] = $data[$key]['value'];
                    break;
                case "FAILED":
                    $value[3] = $data[$key]['value'];
                    break;
            }
        }

        return ["disposition"=>$disposition,"value"=>$value];

    }

    /**
     * @param array $post
     * @return array|string
     */
    private function filterSelect($post)
    {
        $filters = '';

        if (!empty($post['src']) && isset($post['src'])) {
            $filters[] = [
                'placeholder' => ':src',
                'sql' => 'src = :src',
                'value' => Sanitize::string($post['src']),
                'param_type' => PDO::PARAM_STR,
            ];
        }

        if (!empty($post['dst']) && isset($post['dst'])) {
            $filters[] = [
                'placeholder' => ':dst',
                'sql' => 'dst = :dst',
                'value' => Sanitize::string($post['dst']),
                'param_type' => PDO::PARAM_STR,
            ];
        }

        if (!empty($post['accountcode']) && isset($post['accountcode'])) {
            $filters[] = [
                'placeholder' => ':accountcode',
                'sql' => 'accountcode = :accountcode',
                'value' => Sanitize::string($post['accountcode']),
                'param_type' => PDO::PARAM_STR,
            ];
        }

        if (!empty($post['disposition']) && isset($post['disposition'])) {
            $filters[] = [
                'placeholder' => ':disposition',
                'sql' => 'disposition = :disposition',
                'value' => Sanitize::string($post['disposition']),
                'param_type' => PDO::PARAM_STR,
            ];
        }

        return $filters;
    }

    /**
     * @param string $number
     * @param string $callDate
     * @param int $billSec
     * @return mixed
     */
    private function cost($number, $callDate, $billSec)
    {
        if ($billSec > 3) {
            if ( $billSec > 30 ) {
                $aux = ( $billSec / 6 );
                if ( $aux > floor($aux) )
                    $billSec = ( floor($aux) + 1 ) * 6;
                $billSec = $billSec / 60;
            } else {
                $billSec = 0.5;
            }
        } else {
            $billSec = 0;
        }
        $rates = $this->getRate($callDate);
        foreach ($rates as $rate) {
            if ($this->match($rate['dial_pattern'], $number)) {
                $cost['rate'] = is_null($rate['name']) ? '---' : $rate['name'];
                $cost['cost'] = number_format($billSec * $rate['rate'], 2);

                return $cost;
            }
        }
    }

    /**
     * @param string $dialPattern
     * @param string $number
     * @return boolean
     */
    private function match($dialPattern, $number)
    {
        if (preg_match_all("#\[[^]]*\]#",$dialPattern, $out)) {
            $out = $out[0];
            foreach($out as $key => $value) {
                $temp = "";
                for ($i = 1; $i < strlen($value)-1; $i++) {
                    if (ctype_digit($value[$i]) && $i != strlen($value)-2 && $value[$i+1] != '-' ) {
                        $temp .= $value[$i]."|";
                    } else {
                        $temp .= $value[$i];
                    }
                }
                $result[$key] = "[".$temp."]";
            }
            $dialPattern = str_replace($out, $result, $dialPattern);
        }

        $search  = ["X","Z","N", ".", "!"];
        $replace  = ["[0-9]","[1-9]","[2-9]", "[[0-9]|.*]", ".*"];
        $pattern = "/^".str_replace($search, $replace, $dialPattern)."$/i";

        return preg_match($pattern, $number);
    }

    /**
     * @param int $id
     * @return mixed
     */
    private function getOneCall($id)
    {
        return $id;
    }

    /**
     * @param $post
     * @return mixed
     */
    private function filterDateTime($post)
    {
        $post['startDate'] = empty($_REQUEST['startDate']) ? date('Y-m-d') : Sanitize::string($_REQUEST['startDate']);
        $post['startTime'] = empty($_REQUEST['startTime']) ? '00:00' : Sanitize::string($_REQUEST['startTime']);
        $post['endDate'] = empty($_REQUEST['endDate']) ? date('Y-m-d') : Sanitize::string($_REQUEST['endDate']);
        $post['endTime'] = empty($_REQUEST['endTime']) ? '23:59' : Sanitize::string($_REQUEST['endTime']);

        return $post;
    }

    private function getTopSrcCount($post)
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $sql = "SELECT src, COUNT(src) AS total ";
        $sql .= "FROM asteriskcdrdb.cdr ";
        $sql .= "WHERE calldate BETWEEN :startDateTime AND :endDateTime ";
        if (is_array($filters))
            foreach ($filters as $filter) {
                $sql .= " AND " . $filter['sql'];
            }
        $sql .= " GROUP BY src ORDER BY total DESC LIMIT 50";

        $stmt = $this->db->prepare($sql);
        $startDateTime = $post['startDate'].' '.$post['startTime'];
        $endDateTime = $post['endDate'].' '.$post['endTime'];
        $stmt->bindParam(':startDateTime', $startDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':endDateTime', $endDateTime, PDO::PARAM_STR);

        if (is_array($filters))
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($data) ? $data : null;
    }

    private function getTopDstCount($post)
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $sql = "SELECT dst, COUNT(dst) AS total ";
        $sql .= "FROM asteriskcdrdb.cdr ";
        $sql .= "WHERE calldate BETWEEN :startDateTime AND :endDateTime ";
        if (is_array($filters))
            foreach ($filters as $filter) {
                $sql .= " AND " . $filter['sql'];
            }
        $sql .= " GROUP BY dst ORDER BY total DESC LIMIT 40";

        $stmt = $this->db->prepare($sql);
        $startDateTime = $post['startDate'].' '.$post['startTime'];
        $endDateTime = $post['endDate'].' '.$post['endTime'];
        $stmt->bindParam(':startDateTime', $startDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':endDateTime', $endDateTime, PDO::PARAM_STR);

        if (is_array($filters))
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($data) ? $data : null;
    }

    public function getCallsHour($post)
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $sql = "SELECT HOUR(calldate) AS hour, COUNT(calldate) AS total ";
        $sql .= "FROM asteriskcdrdb.cdr ";
        $sql .= "WHERE calldate BETWEEN :startDateTime AND :endDateTime ";
        if (is_array($filters))
            foreach ($filters as $filter) {
                $sql .= " AND " . $filter['sql'];
            }
        $sql .= " GROUP BY hour ORDER BY hour";

        $stmt = $this->db->prepare($sql);
        $startDateTime = $post['startDate'].' '.$post['startTime'];
        $endDateTime = $post['endDate'].' '.$post['endTime'];
        $stmt->bindParam(':startDateTime', $startDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':endDateTime', $endDateTime, PDO::PARAM_STR);

        if (is_array($filters))
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($data) ? $data : null;
    }
}