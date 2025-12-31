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
     * @var array|null Static cache for the list of trunk prefixes.
     */
    private static ?array $trunkListCache = null;

    /**
     * Fetches the list of trunks and creates channel prefixes for comparison.
     *
     * @return array Array of strings with the trunk prefixes (in lowercase).
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
     * Classifies the call based on the use of trunk channels.
     * @param array $cdr The CDR data row.
     * @param array $trunkList List of trunk prefixes.
     * @return string 'INBOUND', 'OUTBOUND' or 'INTERNAL'.
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
     * List of calls grouped by LinkedID with correct pagination.
     * @param array $post
     * @return array
     */
    private function getListCdr(array $post): array
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $limit = (int)($post['limit'] ?? 100);
        $offset = (int)($post['offset'] ?? 0);
        $order = strtolower($post['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $orderBy = $post['sort'] ?? 'calldate';
        $sortMap = [
            'calldate' => 'MIN(calldate)',
            'uniqueid' => 'MIN(uniqueid)',
            'cnum'     => 'MIN(cnum)',
            'src'      => 'MIN(src)',
            'dst'      => 'MAX(dst)'
        ];
        $sortCol = $sortMap[$orderBy] ?? 'MIN(calldate)';

        $whereParts = ["calldate BETWEEN :startDateTime AND :endDateTime"];
        $params = [
            ':startDateTime' => $post['startDate'] . ' ' . $post['startTime'],
            ':endDateTime' => $post['endDate'] . ' ' . $post['endTime'],
        ];

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $whereParts[] = $filter['sql'];
                $params[$filter['placeholder']] = $filter['value'];
            }
        }
        $whereSql = implode(' AND ', $whereParts);

        $joinSql = "LEFT JOIN asterisk.tarifador_pinuser t ON accountcode = t.pin";

        $sqlCount = "SELECT COUNT(DISTINCT linkedid) FROM asteriskcdrdb.cdr $joinSql WHERE $whereSql";
        $stmtCount = $this->db->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();

        if ($total == 0) {
            return ["total" => 0, "rows" => []];
        }

        $sqlIds = "SELECT linkedid FROM asteriskcdrdb.cdr $joinSql 
                   WHERE $whereSql 
                   GROUP BY linkedid 
                   ORDER BY $sortCol $order 
                   LIMIT $offset, $limit";
        
        $stmtIds = $this->db->prepare($sqlIds);
        $stmtIds->execute($params);
        $linkedIds = $stmtIds->fetchAll(PDO::FETCH_COLUMN);

        if (empty($linkedIds)) {
            return ["total" => $total, "rows" => []];
        }

        $inQuery = implode(',', array_fill(0, count($linkedIds), '?'));
        
        $sqlDetails = "SELECT calldate, uniqueid, linkedid, t.user, src, cnum, cnam, did, dst, dcontext, channel, dstchannel, lastapp, disposition, billsec, duration, accountcode
                       FROM asteriskcdrdb.cdr 
                       $joinSql
                       WHERE linkedid IN ($inQuery) 
                       ORDER BY calldate ASC, sequence ASC";

        $stmtDetails = $this->db->prepare($sqlDetails);
        $stmtDetails->execute($linkedIds);
        $rows = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

        $groupedCalls = [];
        foreach ($rows as $row) {
            $groupedCalls[$row['linkedid']][] = $row;
        }

        $active_rates = $this->getRate($post['startDate'], $post['endDate']);
        $trunkList = $this->getTrunkList();
        $finalRows = [];
        foreach ($linkedIds as $lid) {
            if (isset($groupedCalls[$lid])) {
                $finalRows[] = $this->collapseCdrGroup($groupedCalls[$lid], $active_rates, $trunkList);
            }
        }

        return ["total" => $total, "rows" => $finalRows];
    }

    /**
     * Receives all "legs" of a call and returns a consolidated row.
     * @param array $legs All legs of a call.
     * @param array $rates Active rates.
     * @param array $trunkList List of trunk prefixes.
     * @return array Consolidated call data.
     */
    private function collapseCdrGroup(array $legs, array $rates, array $trunkList): array
    {
        $firstLeg = $legs[0];
        $lastLeg = end($legs);
        $startTime = strtotime($firstLeg['calldate']);
        $endTime = strtotime($lastLeg['calldate']) + (int)$lastLeg['duration'];
        $totalDuration = max(0, $endTime - $startTime);
        $finalDisposition = 'NO ANSWER'; 
        $totalBillsec = 0;
        $totalCost = 0.00;        
        $chargeableRateName = _('ND');
        $hasOutboundCost = false;
        $ignoreBillsecApps = ['Queue', 'Busy', 'Congestion', 'Playback', 'VMail'];
        $isAnswered = false;
        foreach ($legs as $leg) {
            if ($leg['disposition'] === 'ANSWERED') {
                if (!in_array($leg['lastapp'], ['Busy', 'Congestion'])) {
                    $isAnswered = true;
                }
            }
        }

        if ($isAnswered) {
            $finalDisposition = 'ANSWERED';
            
            foreach ($legs as $leg) {
                if ($leg['disposition'] === 'ANSWERED') {
                    if (!in_array($leg['lastapp'], $ignoreBillsecApps)) {
                        $totalBillsec += (int)$leg['billsec'];
                    }
                }

                $legType = $this->getCallType($leg, $trunkList);
                if ($legType === 'OUTBOUND' && (int)$leg['billsec'] > 0) {
                    $costDetails = $this->cost($leg['dst'], (int)$leg['billsec'], $leg['calldate'], $rates);
                    if ($costDetails) {
                        $totalCost += (float)$costDetails['cost'];
                        $chargeableRateName = $costDetails['rate'];
                        $hasOutboundCost = true;
                    }
                }
            }

            if ($totalBillsec === 0 && $finalDisposition === 'ANSWERED') {
                $finalDisposition = 'NO ANSWER';
            }

        } else {
            $finalDisposition = $lastLeg['disposition'];
            if ($finalDisposition === 'ANSWERED') {
                $finalDisposition = 'BUSY';
            }
            $totalBillsec = 0;
        }

        $waitSec = max(0, $totalDuration - $totalBillsec);
        $consolidated = $firstLeg;        
        $consolidated['dst'] = $lastLeg['dst'];
        $consolidated['disposition'] = $finalDisposition;
        $consolidated['billsec'] = $totalBillsec;
        $consolidated['duration'] = $totalDuration;
        $consolidated['wait'] = $waitSec;
        $consolidated['call_type'] = $this->getCallType($firstLeg, $trunkList);
        $consolidated['cost'] = number_format($totalCost, 2, '.', '');
        $consolidated['rate'] = $hasOutboundCost ? $chargeableRateName : _('ND');

        if (count($legs) > 1) {
            $consolidated['lastapp'] .= ' <i class="fa fa-code-fork" title="Transferida/Múltiplos Eventos"></i>';
        }

        return $consolidated;
    }

    /**
     * Quantity by call status (Now using COUNT DISTINCT to match the grid)
     * @param array $post
     * @return array
     */
    private function getDisposition(array $post): array
    {
        $post = $this->filterDateTime($post);
        $filters = $this->filterSelect($post);
        $sql_parts = ['SELECT disposition, COUNT(DISTINCT linkedid) AS value FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime'];
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
     * Filter for SELECT
     * @param array $post
     * @return array
     */
    private function filterSelect(array $post): array
    {
        $filters = [];
        
        if (!empty($post['src'])) {
            $value = Sanitize::string($post['src']);
            $sqlOperator = '=';
            if (str_starts_with($value, '_')) {
                $sqlOperator = 'RLIKE';
                $value = $this->asteriskRegExp($value);
            }
            $filters[] = [
                'placeholder' => ':src',
                'sql' => "(src $sqlOperator :src OR cnum $sqlOperator :src)",
                'value' => $value,
                'param_type' => PDO::PARAM_STR,
            ];
        }

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

        if (!empty($post['search'])) {
            $search = Sanitize::string($post['search']);
            if (is_numeric($search)) {
                $filters[] = [
                    'sql' => '(src LIKE :search OR cnum LIKE :search OR dst LIKE :search OR did LIKE :search)',
                    'placeholder' => ':search',
                    'value' => "%{$search}%",
                    'param_type' => PDO::PARAM_STR
                ];
            } else {
                $filters[] = [
                    'sql' => '(cnam LIKE :search OR t.user LIKE :search OR uniqueid LIKE :search)',
                    'placeholder' => ':search',
                    'value' => "%{$search}%",
                    'param_type' => PDO::PARAM_STR
                ];
            }
        }

        return $filters;
    }

    /**
     * Adds a text filter to the filters array.
     * @param array $filters The filters array.
     * @param array $post The post data.
     * @param string $field The field name.
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

    /**
    * Calculates cost based on the call date.
    * @param string|null $number The number dialed.
    * @param int $billSec The billable seconds.
    * @param string $callDate The call date (Y-m-d H:i:s).
    * @param array $rates Array of active rates.
    * @return array|null The cost details or null.
    */
    private function cost(?string $number, int $billSec, string $callDate, array $rates): ?array
    {
        if ($number === null || $billSec <= 3) return null;
        $chargeableSeconds = ($billSec > 30) ? (int)(ceil($billSec / 6) * 6) : 30;
        $chargeableMinutes = $chargeableSeconds / 60;
        $callDateYMD = substr($callDate, 0, 10); 
    
        foreach ($rates as $rate) {
            if ($callDateYMD >= $rate['start'] && $callDateYMD <= $rate['end']) {
                if ($this->match($rate['dial_pattern'], $number)) {
                    return [
                        'rate' => $rate['name'] ?? '---', 
                        'cost' => number_format($chargeableMinutes * (float)$rate['rate'], 2, '.', '')
                    ];
                }
            }
        }
        return null;
    }

    /**
     * Matches a number against a dial pattern.
     * @param string $dialPattern The dial pattern.
     * @param string $number The number to match.
     * @return bool True if it matches, false otherwise.
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
            $pattern = preg_quote($pattern, '/');
            $pattern = str_replace(['\[', '\]', '\-'], ['[', ']', '-'], $pattern);
            $asteriskToRegexMap = [
                'X' => '[0-9]', 'Z' => '[1-9]', 'N' => '[2-9]', '.' => '.+', '!' => '.*'
            ];
            foreach ($asteriskToRegexMap as $asteriskChar => $regexEquiv) {
                $pattern = str_replace($asteriskChar, $regexEquiv, $pattern);
            }
            $finalRegex = "/^" . $pattern . "$/";
            $regexCache[$dialPattern] = $finalRegex;
        }
        return preg_match($finalRegex, $number) === 1;
    }

    /**
     * Gets one call by ID.
     * @param int $id The call ID.
     * @return int The call ID.
     */
    private function getOneCall(int $id): int
    {
        return $id;
    }

    /**
     * Filters date and time from post data.
     * @param array $post The post data.
     * @return array The filtered post data.
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
     * Runs a filtered query.
     * @param array $post The post data.
     * @param string $baseSql The base SQL query.
     * @param string $suffix The SQL suffix.
     * @return array The query result.
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
     * Gets the top source count.
     * @param array $post The post data.
     * @return array The query result.
     */
    private function getTopSrcCount(array $post): array
    {
        $sql = "SELECT cnum, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";
        $groupBy = "GROUP BY cnum ORDER BY total DESC LIMIT 50";
        return $this->runFilteredQuery($post, $sql, $groupBy);
    }

    /**
     * Gets the top destination count.
     * @param array $post The post data.
     * @return array The query result.
     */
    private function getTopDstCount(array $post): array
    {
        $sql = "SELECT dst, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";
        $groupBy = "GROUP BY dst ORDER BY total DESC LIMIT 50";
        return $this->runFilteredQuery($post, $sql, $groupBy);
    }

    /**
     * Gets calls per hour.
     * @param array $post The post data.
     * @return array The query result.
     */
    public function getCallsHour(array $post): array
    {
        $sql = "SELECT HOUR(calldate) AS hour, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";
        $groupBy = "GROUP BY hour ORDER BY hour";
        return $this->runFilteredQuery($post, $sql, $groupBy);
    }

    /**
     * Number of calls, minutes, average, cost and count by status (OVERALL TOTAL).
     * @param array $post
     * @return array
     */
    public function getTotalCalls(array $post): array
    {

        $sql = "SELECT dst, billsec, channel, dstchannel, src, cnum, disposition, calldate, linkedid FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";
        $rows = $this->runFilteredQuery($post, $sql); 
        
        $uniqueCalls = [];
        $totalCost = 0.0;
        $totalBillsec = 0;
        $linkedIdStatus = [];

        $active_rates = $this->getRate($post['startDate'], $post['endDate']);
        $trunkList = $this->getTrunkList();

        foreach ($rows as $row) {
            $lid = $row['linkedid'];
            $uniqueCalls[$lid] = true;
            $totalBillsec += $row['billsec'];
            
            // Grouped Disposition Logic (Simple)
            if (!isset($linkedIdStatus[$lid])) {
                $linkedIdStatus[$lid] = 'NO ANSWER';
            }
            if ($row['disposition'] === 'ANSWERED') {
                $linkedIdStatus[$lid] = 'ANSWERED';
            } elseif ($linkedIdStatus[$lid] !== 'ANSWERED' && $row['disposition'] !== 'NO ANSWER') {
                $linkedIdStatus[$lid] = $row['disposition'];
            }

            // Cost (Sum of all billable legs)
            $type = $this->getCallType($row, $trunkList);
            if ($type === 'OUTBOUND' && $row['billsec'] > 0) {
                $costDetails = $this->cost($row['dst'], (int)$row['billsec'], $row['calldate'], $active_rates);
                if ($costDetails) {
                    $totalCost += (float)$costDetails['cost'];
                }
            }
        }

        $totalCalls = count($uniqueCalls);
        $stats = ['ANSWERED' => 0, 'NO ANSWER' => 0, 'BUSY' => 0, 'FAILED' => 0];
        foreach ($linkedIdStatus as $status) {
            $s = strtoupper($status);
            if (isset($stats[$s])) $stats[$s]++;
            else $stats['FAILED']++;
        }

        $totalTime = $this->formatSecondsToHMS($totalBillsec);
        $avgSeconds = $totalCalls > 0 ? floor($totalBillsec / $totalCalls) : 0;
        $avg = $this->formatSecondsToHMS((int) $avgSeconds);

        return [
            'total_calls' => $totalCalls,
            'total_time'  => $totalTime,
            'avg'         => $avg,
            'total_cost'  => number_format($totalCost, 2, '.', ''),
            'answered'    => $stats['ANSWERED'],
            'no_answer'   => $stats['NO ANSWER'],
            'busy'        => $stats['BUSY'],
            'failed'      => $stats['FAILED']
        ];
    }

    /**
     * Converts an Asterisk dialplan pattern to a regex.
     * @param string $number The number to convert.
     * @return string The regex pattern.
     */
    private function asteriskRegExp(string $number): string
    {
        $number = urldecode($number);
        if (str_starts_with($number, '__')) {
            $number = substr($number, 1);
        } elseif (str_starts_with($number, '_')) {
            $pattern = substr($number, 1);
            $map = ['X' => '[0-9]', 'Z' => '[1-9]', 'N' => '[2-9]', '.' => '.+',];
            $number = strtr($pattern, $map);
        }
        return "^" . $number . "$";
    }

    /**
     * Formats seconds to H:M:S format.
     * @param int $seconds The seconds to format.
     * @return string The formatted time.
     */
    private function formatSecondsToHMS(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }
}