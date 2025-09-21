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
     * Lista de chamadas com o custo da ligação
     * 
     * @param array $post
     * @return array|null
     */
    private function getListCdr($post)
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $limit = isset($post["limit"]) ? (int)$post["limit"] : 100;
        $offset = isset($post["offset"]) ? (int)$post["offset"] : 0;
        $order = (isset($post["order"]) && strtolower($post["order"]) === "desc") ? "desc" : "asc";
        $allowed_sort_columns = ["calldate", "uniqueid", "src", "dst"];
        $orderBy = isset($post["sort"]) ? $post["sort"] : "calldate";
        if (!in_array($orderBy, $allowed_sort_columns)) {
            $orderBy = "calldate";
        }
            
        $sql = "SELECT SQL_CALC_FOUND_ROWS calldate, uniqueid, t.user, src, cnam, did, dst, 
                lastapp, disposition, billsec, (duration - billsec) AS wait 
                FROM asteriskcdrdb.cdr 
                LEFT JOIN asterisk.tarifador_pinuser t ON accountcode = t.pin 
                WHERE calldate BETWEEN :startDateTime AND :endDateTime ";

        $params = [];
        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $filter) {
                $sql .= " AND " . $filter['sql'];
                $params[$filter['placeholder']] = $filter['value'];
            }
        }
        
        $sql .= " ORDER BY $orderBy $order LIMIT $offset, $limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':startDateTime', $post['startDate'].' '.$post['startTime']);
        $stmt->bindValue(':endDateTime', $post['endDate'].' '.$post['endTime']);
        foreach ($params as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value);
        }
        $stmt->execute();
        $cdrs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total = $this->db->query("SELECT FOUND_ROWS() as count")->fetch(PDO::FETCH_ASSOC);
        $active_rates = $this->getRate($post['startDate']);
    
        foreach ($cdrs as $key => $value) {
            $cdrs[$key]['cost'] = '0.00';
            $cdrs[$key]['rate'] = 'Não Tarifado';
            if ($value['billsec'] > 0 && strlen($value['src']) == 4 && strlen($value['dst']) != 4) {
                $cost_details = $this->cost($value['dst'], $value['billsec'], $active_rates);
                if ($cost_details !== null) {
                    $cdrs[$key]['cost'] = $cost_details['cost'];
                    $cdrs[$key]['rate'] = $cost_details['rate'];
                }
            }
        }

        return ["total" => $total['count'], "rows" => $cdrs];
    }

    /**
     * Quantidade por estado das chamadas 
     * 
     * @param array $post
     * @return array
     */
    private function getDisposition($post)
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);

        $sql = 'SELECT disposition, COUNT(*) AS value FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime';
    
        if (is_array($filters)) {
            foreach ($filters as $filter) {
                $sql .= " AND " . $filter['sql'];
            }
        }

        $sql .= " GROUP BY disposition";
        $stmt = $this->db->prepare($sql);
        $startDateTime = $post['startDate'].' '.$post['startTime'];
        $endDateTime = $post['endDate'].' '.$post['endTime'];
        $stmt->bindParam(':startDateTime', $startDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':endDateTime', $endDateTime, PDO::PARAM_STR);

        if (is_array($filters)) {
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = is_array($data) ? $data : [];

        $disposition = [_("ANSWERED"), _("NO ANSWER"), _("BUSY"), _("FAILED")];
        $value = [0, 0, 0, 0];
        $map = [
            'ANSWERED'   => 0,
            'NO ANSWER'  => 1,
            'BUSY'       => 2,
            'FAILED'     => 3
        ];

        foreach ($data as $row) {
            if (isset($map[$row['disposition']])) {
                $value[$map[$row['disposition']]] = (int) $row['value'];
            }
        }

        return ['disposition' => $disposition, 'value' => $value];
    }

    /**
     * Filtro para SELECT
     * 
     * @param array $post
     * @return array|string
     */
    private function filterSelect($post)
    {
        $filters = [];

        if (!empty($post['src'])) {
            $sql = 'src = :src';
            $value = Sanitize::string($post['src']);
            if ('_' == substr($post['src'],0,1 )) {
                $sql = 'src RLIKE :src';
                $value = $this->asteriskRegExp($value);
            }
            $filters[] = [
                'placeholder' => ':src',
                'sql' => $sql,
                'value' => $value,
                'param_type' => PDO::PARAM_STR,
            ];
        }

        if (!empty($post['dst'])) {
            $sql = 'dst = :dst';
            $value = Sanitize::string($post['dst']);
            if ('_' == substr($post['dst'],0,1 )) {
                $sql = 'dst RLIKE :dst';
                $value = $this->asteriskRegExp($value);
            }
            $filters[] = [
                'placeholder' => ':dst',
                'sql' => $sql,
                'value' => $value,
                'param_type' => PDO::PARAM_STR,
            ];
        }

        if (!empty($post['accountcode']) && ctype_digit($post['accountcode'])) {
            $filters[] = [
                'placeholder' => ':accountcode',
                'sql' => 'accountcode = :accountcode',
                'value' => Sanitize::string($post['accountcode']),
                'param_type' => PDO::PARAM_STR,
            ];
        }

        if (!empty($post['disposition'])) {
            $allowed_dispositions = ['ANSWERED', 'NO ANSWER', 'BUSY', 'FAILED'];
            $disposition_value = urldecode($post['disposition']);
            if (in_array($disposition_value, $allowed_dispositions)) {
                $filters[] = [
                    'placeholder' => ':disposition',
                    'sql' => 'disposition = :disposition',
                    'value' => $disposition_value,
                    'param_type' => PDO::PARAM_STR,
                ];
            }
        }

        return $filters;
    }

    /** 
    *
    * @param string $number
    * @param int $billSec
    * @param array $rates Array de tarifas ativas para o período.
    * @return array|null Retorna um array com 'cost' e 'rate' ou null se nenhuma tarifa corresponder.
    */
    private function cost($number, $billSec, array $rates)
    {
        $chargeableSeconds = 0;
        if ($billSec > 3) {
            if ( $billSec > 30 ) {
                $aux = ( $billSec / 6 );
                $chargeableSeconds = ( floor($aux) + 1 ) * 6;
            } else {
                $chargeableSeconds = 30;
            }
        }
    
        $chargeableMinutes = $chargeableSeconds / 60;
    
        foreach ($rates as $rate) {
            if ($this->match($rate['dial_pattern'], $number)) {
                return [
                    'rate' => is_null($rate['name']) ? '---' : $rate['name'], 
                    'cost' => number_format($chargeableMinutes * $rate['rate'], 2)
                ];
            }
        }
    
        return null;
    }

    /**
     * Verifica se o número é compatível com dial pattern do asterisk
     * 
     * @param string $dialPattern
     * @param string $number
     * @return boolean
     */
    private function match($dialPattern, $number)
    {
        if (substr($dialPattern, 0, 1) === '_') {
            $dialPattern = substr($dialPattern, 1);
        }
        
        $asteriskToRegexMap = [
            'X' => '[0-9]',   // Qualquer dígito de 0 a 9
            'Z' => '[1-9]',   // Qualquer dígito de 1 a 9
            'N' => '[2-9]',   // Qualquer dígito de 2 a 9
            '.' => '.+',      // Um ou mais caracteres quaisquer.
            '!' => '.*'       // Zero ou mais caracteres quaisquer.
        ];

        $pattern = preg_quote($dialPattern, '/');
        $search = array_map(function($key) { return preg_quote($key, '/'); }, array_keys($asteriskToRegexMap));
        $replace = array_values($asteriskToRegexMap);    
        $pattern = str_replace($search, $replace, $pattern);    
        $finalRegex = "/^" . $pattern . "$/";
    
        return (bool) @preg_match($finalRegex, $number);
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
     * Filtro de data e horário
     * 
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

    /**
     * Origem das chamadas (top 50) 
     * 
     * @param $post
     * @return array
     */
    private function getTopSrcCount($post)
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $sql = "SELECT src, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";

        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $filter) {            
                $sql .= " AND " . $filter['sql'];
            }
        }

        $sql .= " GROUP BY src ORDER BY total DESC LIMIT 50";

        $stmt = $this->db->prepare($sql);

        $startDateTime = $post['startDate'] . ' ' . $post['startTime'];
        $endDateTime = $post['endDate'] . ' ' . $post['endTime'];
        $stmt->bindParam(':startDateTime', $startDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':endDateTime', $endDateTime, PDO::PARAM_STR);

        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($data) ? $data : [];
    }

    /**
     * Destino das chamadas (top 50) 
     * 
     * @param $post
     * @return array
     */
    private function getTopDstCount($post)
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $sql = "SELECT dst, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";

        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $filter) {            
                $sql .= " AND " . $filter['sql'];
            }
        }

        $sql .= " GROUP BY dst ORDER BY total DESC LIMIT 50";

        $stmt = $this->db->prepare($sql);

        $startDateTime = $post['startDate'] . ' ' . $post['startTime'];
        $endDateTime   = $post['endDate'] . ' ' . $post['endTime'];

        $stmt->bindParam(':startDateTime', $startDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':endDateTime', $endDateTime, PDO::PARAM_STR);

        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($data) ? $data : [];
    }

    /**
     * Distribuição das chamadas por hora
     * 
     * @param $post
     * @return array|null
     */
    public function getCallsHour($post)
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $sql = "SELECT HOUR(calldate) AS hour, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";

        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $filter) {            
                $sql .= " AND " . $filter['sql'];
            }
    }

        $sql .= " GROUP BY hour ORDER BY hour";

        $stmt = $this->db->prepare($sql);

        $startDateTime = $post['startDate'] . ' ' . $post['startTime'];
        $endDateTime   = $post['endDate'] . ' ' . $post['endTime'];

        $stmt->bindParam(':startDateTime', $startDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':endDateTime', $endDateTime, PDO::PARAM_STR);

        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($data) ? $data : [];
    }

    /**
     * Quantidade de chamadas, minutos e média
     * 
     * @param $post
     * @return array|null
     */
    public function getTotalCalls($post)
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $sql = "
            SELECT 
                COUNT(billsec) AS total, 
                ROUND(SUM(billsec) / 60, 1) AS minutes, 
                ROUND(SUM(billsec) / COUNT(billsec) / 60, 1) AS avg 
            FROM asteriskcdrdb.cdr 
            WHERE calldate BETWEEN :startDateTime AND :endDateTime
        ";

        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $filter) {
                $sql .= " AND " . $filter['sql'];
            }
        }

        $stmt = $this->db->prepare($sql);

        $startDateTime = $post['startDate'] . ' ' . $post['startTime'];
        $endDateTime   = $post['endDate'] . ' ' . $post['endTime'];

        $stmt->bindParam(':startDateTime', $startDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':endDateTime', $endDateTime, PDO::PARAM_STR);

        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $filter) {
                $stmt->bindParam($filter['placeholder'], $filter['value'], $filter['param_type']);
            }
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($data) ? $data : [];
    }

    /**
     * @param $number
     * @return string
     */
    private function asteriskRegExp($number)
    {
        $number = urldecode($number);
        if ( '__' == substr($number,0,2) ) {
            $number = substr($number,1);
        } elseif ( '_' == substr($number,0,1 ) ) {
            $chars = preg_split('//', substr($number,1), -1, PREG_SPLIT_NO_EMPTY);
            $number = '';
            foreach ($chars as $chr) {
                if ( $chr == 'X' ) {
                    $number .= '[0-9]';
                } elseif ( $chr == 'Z' ) {
                    $number .= '[1-9]';
                } elseif ( $chr == 'N' ) {
                    $number .= '[2-9]';
                } elseif ( $chr == '.' ) {
                    $number .= '.+';
                } else {
                    $number .= $chr;
                }
            }
        }

        return "^".$number."\$";
    }
}