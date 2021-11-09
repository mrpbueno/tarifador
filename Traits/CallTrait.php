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

        $sql = 'SELECT calldate, uniqueid, t.user, src, cnam, did, dst, lastapp, disposition, duration, billsec, (duration -  billsec) AS wait ';
        $sql .= 'FROM asteriskcdrdb.cdr ';
        $sql .= 'LEFT JOIN asterisk.tarifador_pinuser t ON accountcode = t.pin ';
        $sql .= 'WHERE calldate BETWEEN :startDate AND :endDate ';
        if (is_array($filters))
            foreach ($filters as $filter) {
                $sql .= " AND " . $filter['sql'];
            }
        $sql .= ' ORDER BY calldate ASC';
        $stmt = $this->db->prepare($sql);
        $startDate = Sanitize::string($post['startDate'].' '.$post['startTime']);
        $endDate = Sanitize::string($post['endDate'].' '.$post['endTime']);
        $stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);

        if (is_array($filters))
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        $stmt->execute();
        $cdrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cdrs = is_array($cdrs) ? $cdrs : null;

        foreach ($cdrs as $key => $value) {
            if (strlen($cdrs[$key]['src']) == 4 && strlen($cdrs[$key]['dst']) != 4) {
                $cost = $this->cost($cdrs[$key]['dst'],$cdrs[$key]['calldate'],$cdrs[$key]['billsec']);
                $cdrs[$key]['cost'] = $cost['cost'];
                $cdrs[$key]['rate'] = $cost['rate'];
            } else {
                $cdrs[$key]['cost'] = 0;
                $cdrs[$key]['rate'] = "---";
            }
            $cdrs[$key]['calltype'] = $this->callType($cdrs[$key]['src'],$cdrs[$key]['dst']);
            $cdrs[$key]['disposition'] = _($cdrs[$key]['disposition']);
        }

        return $cdrs;
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
     * @param string $src
     * @param string $dst
     * @return string
     */
    private function callType($src, $dst) {
        $src = strlen($src);
        $dst = strlen($dst);
        if ($src == 4 && $dst == 4) {
            return _("Interna");
        }
        if ($src == 4 && $dst >= 8) {
            return _("Saída");
        }
        if ($src != 4 && $dst == 4) {
            return _("Entrada");
        }
    }
}