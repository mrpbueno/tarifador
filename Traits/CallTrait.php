<?php

namespace FreePBX\modules\Tarifador\Traits;

use PDO;

trait CallTrait
{
    /**
     * @param $post
     * @return array|null
     */
    private function getListCdr($post)
    {
        $filters = '';

        if (!empty($post['src']) && isset($post['src'])) {
            $filters[] = [
                'placeholder' => ':src',
                'sql' => 'src = :src',
                'value' => $post['src'],
                'param_type' => PDO::PARAM_STR,
            ];
        }

        if (!empty($post['dst']) && isset($post['dst'])) {
            $filters[] = [
                'placeholder' => ':dst',
                'sql' => 'dst = :dst',
                'value' => $post['dst'],
                'param_type' => PDO::PARAM_STR,
            ];
        }

        if (!empty($post['accountcode']) && isset($post['accountcode'])) {
            $filters[] = [
                'placeholder' => ':accountcode',
                'sql' => 'accountcode = :accountcode',
                'value' => $post['accountcode'],
                'param_type' => PDO::PARAM_STR,
            ];
        }

        if (!empty($post['disposition']) && isset($post['disposition'])) {
            $filters[] = [
                'placeholder' => ':disposition',
                'sql' => 'disposition = :disposition',
                'value' => $post['disposition'],
                'param_type' => PDO::PARAM_STR,
            ];
        }

        $sql = 'SELECT calldate, accountcode, src, cnam, did, dst, lastapp, disposition, duration, billsec';
        $sql .= ' FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDate AND :endDate ';
        if (is_array($filters))
            foreach ($filters as $filter) {
                $sql .= " AND " . $filter['sql'];
            }
        $sql .= ' ORDER BY calldate ASC';
        $stmt = $this->db->prepare($sql);
        $sartDate = $post['startDate'].' '.$post['startTime'];
        $endDate = $post['endDate'].' '.$post['endTime'];
        $stmt->bindParam(':startDate',$sartDate,PDO::PARAM_STR);
        $stmt->bindParam(':endDate',$endDate,PDO::PARAM_STR);

        if (is_array($filters))
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        $stmt->execute();
        $cdrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($cdrs)) {
            $cdrs = null;
        }

        foreach ($cdrs as $key => $value) {
            $cdrs[$key]['accountcode'] = $this->getPinuser($value['accountcode']);
            $cdrs[$key]['wait'] = $cdrs[$key]['duration'] - $cdrs[$key]['billsec'];
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
     * @param $pin
     * @return string
     */
    private function getPinuser($pin)
    {
        if (empty($pin)) {
            return '';
        }
        $sql = "SELECT user FROM tarifador_pinuser WHERE pin = :pin LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':pin', $pin, PDO::PARAM_STR);
        $stmt->execute();
        $pinuser = $stmt->fetchObject();

        return isset($pinuser->user) ? $pinuser->user : _("Sem Cadastro");
    }

    /**
     * @param $number
     * @param $calldate
     * @return mixed
     */
    private function getRate($number, $calldate)
    {
        $sql = 'SELECT * FROM tarifador_rate WHERE start <= :calldate AND end >= :calldate ORDER BY seq ASC';
        $stmt = $this->db->prepare($sql);
        $date = date('Y-m-d',strtotime($calldate));
        $stmt->bindParam(':calldate', $date, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rates = is_array($data) ? $data : null;

        foreach ($rates as $rate) {
            if ($this->match($rate['dial_pattern'], $number)) {
                return $rate;
            }
        }
    }

    /**
     * @param $number
     * @param $calldate
     * @param $billsec
     * @return mixed
     */
    private function cost($number, $calldate, $billsec)
    {
        $rate = $this->getRate($number, $calldate);

        if ($billsec > 3) {
            if ( $billsec > 30 ) {
                $aux = ( $billsec / 6 );
                if ( $aux > floor($aux) )
                    $billsec = ( floor($aux) + 1 ) * 6;
                $billsec = $billsec / 60;
            } else {
                $billsec = 0.5;
            }
        } else {
            $billsec = 0;
        }

        $cost['rate'] = is_null($rate['name']) ? '---' : $rate['name'];
        $cost['cost'] = number_format($billsec * $rate['rate'], 2);

        return $cost;
    }

    /**
     * @param $id
     * @return mixed
     */
    private function getOneCall($id)
    {
        return $id;
    }

    /**
     * @param $dial_pattern
     * @param $number
     * @return false|int
     */
    private function match($dial_pattern, $number)
    {
        if (preg_match_all("#\[[^]]*\]#",$dial_pattern, $out)) {
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
            $dial_pattern = str_replace($out, $result, $dial_pattern);
        }

        $search  = ["X","Z","N", ".", "!"];
        $replace  = ["[0-9]","[1-9]","[2-9]", "[[0-9]|.*]", ".*"];
        $pattern = "/^".str_replace($search, $replace, $dial_pattern)."$/i";

        return preg_match($pattern, $number);
    }

    /**
     * @param $src
     * @param $dst
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