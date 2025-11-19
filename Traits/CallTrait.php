<?php
declare(strict_types=1);

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
     * @var array|null Cache estático para a lista de prefixos de troncos.
     */
    private static ?array $trunkListCache = null;

    /**
     * Busca a lista de troncos e cria os prefixos de canal para comparação.
     *
     * @return array Array de strings com os prefixos dos troncos (em minúsculo).
     */
    private function getTrunkList(): array
    {
        if (self::$trunkListCache === null) {
            $sql = "SELECT tech, channelid FROM trunks";
            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $prefixes = [];
            foreach ($rows as $row) {
                $tech = strtolower($row['tech'] ?? '');
                $channelid = strtolower($row['channelid'] ?? '');

                if (empty($channelid)) {
                    continue;
                }

                if (str_contains($channelid, '/')) {
                    $parts = explode('/', $channelid);
                    if (!empty($parts[0])) {
                        $prefixes[] = $parts[0] . '/';
                    }
                } else {
                    if (!empty($tech)) {
                        $prefixes[] = $tech . '/' . $channelid;
                    }
                }
            }
            self::$trunkListCache = $prefixes;
        }
        return self::$trunkListCache;
    }

    /**
     * Classifica a chamada baseada no uso de canais de tronco.
     * * @param array $cdr A linha de dados do CDR.
     * @param array $trunkList Lista de prefixos de troncos.
     * @return string 'INBOUND', 'OUTBOUND' ou 'INTERNAL'.
     */
    private function getCallType(array $cdr, array $trunkList): string
    {
        $channel = strtolower($cdr['channel'] ?? '');
        $dstChannel = strtolower($cdr['dstchannel'] ?? '');

        foreach ($trunkList as $trunkPrefix) {
            if (str_starts_with($channel, $trunkPrefix)) {
                return 'INBOUND';
            }
        }

        foreach ($trunkList as $trunkPrefix) {
            if (str_starts_with($dstChannel, $trunkPrefix)) {
                return 'OUTBOUND';
            }
        }

        return 'INTERNAL';
    }

    /**
     * Lista de chamadas com o custo da ligação
     * * @param array $post
     * @return array|null
     */
    private function getListCdr(array $post): array
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $limit = (int)($post['limit'] ?? 100);
        $offset = (int)($post['offset'] ?? 0);
        $order = strtolower($post['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $allowed_sort_columns = ['calldate', 'uniqueid', 'src', 'dst'];
        $orderBy = $post['sort'] ?? 'calldate';
        if (!in_array($orderBy, $allowed_sort_columns, true)) {
            $orderBy = 'calldate';
        }

        $sql_parts = ["SELECT SQL_CALC_FOUND_ROWS calldate, uniqueid, t.user, src, cnam, did, dst, channel, dstchannel, lastapp, disposition, billsec, (duration - billsec) AS wait",
            "FROM asteriskcdrdb.cdr",
            "LEFT JOIN asterisk.tarifador_pinuser t ON accountcode = t.pin",
            "WHERE calldate BETWEEN :startDateTime AND :endDateTime"];

        $params = [
            ':startDateTime' => $post['startDate'] . ' ' . $post['startTime'],
            ':endDateTime' => $post['endDate'] . ' ' . $post['endTime'],
        ];

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $sql_parts[] = "AND " . $filter['sql'];
                $params[$filter['placeholder']] = $filter['value'];
            }
        }
        
        $sql_parts[] = "ORDER BY $orderBy $order LIMIT $offset, $limit";
        $sql = implode(' ', $sql_parts);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $cdrs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        $total = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        $active_rates = $this->getRate($post['startDate']);

        $trunkList = $this->getTrunkList();
    
        $cdrs = array_map(function ($cdr) use ($active_rates, $trunkList) {

            $cdr['call_type'] = $this->getCallType($cdr, $trunkList);
            $cdr['cost'] = '0.00';
            $cdr['rate'] = 'Não Tarifado';

            if ($cdr['call_type'] === 'OUTBOUND' && (int)$cdr['billsec'] > 0) {
                $cost_details = $this->cost($cdr['dst'], (int)$cdr['billsec'], $active_rates);
                if ($cost_details !== null) {
                    $cdr['cost'] = $cost_details['cost'];
                    $cdr['rate'] = $cost_details['rate'];
                }
            }

            return $cdr;
        }, $cdrs);

        return ["total" => $total, "rows" => $cdrs];
    }

    /**
     * Quantidade por estado das chamadas 
     * * @param array $post
     * @return array
     */
    private function getDisposition(array $post): array
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);

        $sql_parts = ['SELECT disposition, COUNT(*) AS value FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime'];
        $params = [
            ':startDateTime' => $post['startDate'] . ' ' . $post['startTime'],
            ':endDateTime' => $post['endDate'] . ' ' . $post['endTime'],
        ];

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $sql_parts[] = "AND " . $filter['sql'];
                $params[$filter['placeholder']] = $filter['value'];
            }
        }

        $sql_parts[] = "GROUP BY disposition";
        $sql = implode(' ', $sql_parts);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $dispositions = [_("ANSWERED"), _("NO ANSWER"), _("BUSY"), _("FAILED")];
        $values = array_fill(0, 4, 0);

        foreach ($data as $row) {
            $index = match ($row['disposition']) {
                'ANSWERED' => 0,
                'NO ANSWER' => 1,
                'BUSY' => 2,
                'FAILED' => 3,
                default => null,
            };
            if ($index !== null) {
                $values[$index] = (int)$row['value'];
            }
        }

        return ['disposition' => $dispositions, 'value' => $values];
    }

    /**
     * Filtro para SELECT
     * * @param array $post
     * @return array|string
     */
    private function filterSelect(array $post): array
    {
        $filters = [];
        $this->addTextFilter($filters, $post, 'src');
        $this->addTextFilter($filters, $post, 'dst');

        if (!empty($post['accountcode']) && ctype_digit((string)$post['accountcode'])) {
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
            if (in_array($disposition_value, $allowed_dispositions, true)) {
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
     * Adiciona um filtro de texto (src ou dst) ao array de filtros.
     *
     * @param array $filters O array de filtros (passado por referência).
     * @param array $post Os dados do POST.
     * @param string $field O campo ('src' ou 'dst').
     * @return void
     */
    private function addTextFilter(array &$filters, array $post, string $field): void
    {
        if (empty($post[$field])) {
            return;
        }

        $value = Sanitize::string($post[$field]);
        $sql = "$field = :$field";

        if (str_starts_with($value, '_')) {
            $sql = "$field RLIKE :$field";
            $value = $this->asteriskRegExp($value);
        }

        $filters[] = [
            'placeholder' => ":$field",
            'sql' => $sql,
            'value' => $value,
            'param_type' => PDO::PARAM_STR,
        ];
    }

    /** *
    * @param string $number
    * @param int $billSec
    * @param array $rates Array de tarifas ativas para o período.
    * @return array|null Retorna um array com 'cost' e 'rate' ou null se nenhuma tarifa corresponder.
    */
    private function cost(?string $number, int $billSec, array $rates): ?array
    {
        if ($number === null || $billSec <= 3) {
            return null;
        }
    
        $chargeableSeconds = 0;
        if ( $billSec > 30 ) {
            $chargeableSeconds = (int)(ceil($billSec / 6) * 6);
        } else {
            $chargeableSeconds = 30;
        }

        if ($chargeableSeconds === 0) {
            return null;
        }
    
        $chargeableMinutes = $chargeableSeconds / 60;
    
        foreach ($rates as $rate) {
            if ($this->match($rate['dial_pattern'], $number)) {
                return [
                    'rate' => $rate['name'] ?? '---', 
                    'cost' => number_format($chargeableMinutes * (float)$rate['rate'], 2, '.', '')
                ];
            }
        }
    
        return null;
    }

    /**
     * Verifica se o número é compatível com dial pattern do asterisk
     * * @param string $dialPattern
     * @param string $number
     * @return boolean
     */
    private function match(string $dialPattern, string $number): bool
    {
        static $regexCache = [];

        if (isset($regexCache[$dialPattern])) {
            $finalRegex = $regexCache[$dialPattern];
        } else {
            $pattern = $dialPattern;
            if (str_starts_with($pattern, '_')) {
                $pattern = substr($pattern, 1);
            }

            $asteriskToRegexMap = [
                'X' => '[0-9]',
                'Z' => '[1-9]',
                'N' => '[2-9]',
                '.' => '.+',
                '!' => '.*'
            ];
            
            $pattern = preg_quote($pattern, '/');
            foreach ($asteriskToRegexMap as $asteriskChar => $regexEquiv) {
                $pattern = str_replace(preg_quote($asteriskChar, '/'), $regexEquiv, $pattern);
            }
            
            $finalRegex = "/^" . $pattern . "$/";
            $regexCache[$dialPattern] = $finalRegex;
        }
    
        return preg_match($finalRegex, $number) === 1;
    }

    /**
     * @param int $id
     * @return mixed
     */
    private function getOneCall(int $id): int
    {
        return $id;
    }

    /**
     * Filtro de data e horário
     * * @param $post
     * @return mixed
     */
    private function filterDateTime(array $post): array
    {
        $post['startDate'] = Sanitize::string($post['startDate'] ?? date('Y-m-d'));
        $post['startTime'] = Sanitize::string($post['startTime'] ?? '00:00');
        $post['endDate'] = Sanitize::string($post['endDate'] ?? date('Y-m-d'));
        $post['endTime'] = Sanitize::string($post['endTime'] ?? '23:59');

        return $post;
    }

    /**
     * Executa uma query SQL filtrada e retorna os resultados.
     *
     * @param array $post Dados do POST com filtros.
     * @param string $baseSql A query SQL base (sem filtros 'AND').
     * @param string $suffix O sufixo da query (GROUP BY, ORDER BY, LIMIT).
     * @return array
     */
    private function runFilteredQuery(array $post, string $baseSql, string $suffix = ''): array
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);

        $params = [
            ':startDateTime' => $post['startDate'] . ' ' . $post['startTime'],
            ':endDateTime'   => $post['endDate'] . ' ' . $post['endTime'],
        ];

        $sqlParts = [$baseSql];
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $sqlParts[] = "AND " . $filter['sql'];
                $params[$filter['placeholder']] = $filter['value'];
            }
        }
        if ($suffix) {
            $sqlParts[] = $suffix;
        }

        $sql = implode(' ', $sqlParts);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    /**
     * Origem das chamadas (top 50) 
     * * @param $post
     * @return array
     */
    private function getTopSrcCount(array $post): array
    {
        $sql = "SELECT src, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";
        $groupBy = "GROUP BY src ORDER BY total DESC LIMIT 50";
        return $this->runFilteredQuery($post, $sql, $groupBy);
    }

    /**
     * Destino das chamadas (top 50) 
     * * @param $post
     * @return array
     */
    private function getTopDstCount(array $post): array
    {
        $sql = "SELECT dst, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";
        $groupBy = "GROUP BY dst ORDER BY total DESC LIMIT 50";
        return $this->runFilteredQuery($post, $sql, $groupBy);
    }

    /**
     * Distribuição das chamadas por hora
     * * @param $post
     * @return array|null
     */
    public function getCallsHour(array $post): array
    {
        $sql = "SELECT HOUR(calldate) AS hour, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";
        $groupBy = "GROUP BY hour ORDER BY hour";
        return $this->runFilteredQuery($post, $sql, $groupBy);
    }

    /**
     * Quantidade de chamadas, minutos e média
     * * @param $post
     * @return array|null
     */
    public function getTotalCalls(array $post): array
    {
        $sql = "
            SELECT 
                COUNT(billsec) AS total, 
                ROUND(SUM(billsec) / 60, 1) AS minutes, 
                ROUND(SUM(billsec) / COUNT(billsec) / 60, 1) AS avg 
            FROM asteriskcdrdb.cdr 
            WHERE calldate BETWEEN :startDateTime AND :endDateTime
        ";
        return $this->runFilteredQuery($post, $sql);
    }

    /**
     * @param $number
     * @return string
     */
    private function asteriskRegExp(string $number): string
    {
        $number = urldecode($number);
        if (str_starts_with($number, '__')) {
            $number = substr($number, 1);
        } elseif (str_starts_with($number, '_')) {
            $pattern = substr($number, 1);
            $map = [
                'X' => '[0-9]',
                'Z' => '[1-9]',
                'N' => '[2-9]',
                '.' => '.+',
            ];
            $number = strtr($pattern, $map);
        }

        return "^" . $number . "$";
    }
}