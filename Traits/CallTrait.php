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
     * Lists calls with their associated cost.
     * 
     * @param array $post The post data containing filter parameters.
     * @return array An array of call detail records, including cost and rate for each.
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

        $sql_parts = ["SELECT SQL_CALC_FOUND_ROWS calldate, uniqueid, t.user, src, cnam, did, dst, lastapp, disposition, billsec, (duration - billsec) AS wait",
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

        $cdrs = array_map(function ($cdr) use ($active_rates) {
            $cdr['cost'] = '0.00';
            $cdr['rate'] = 'Não Tarifado';
            if ((int)$cdr['billsec'] > 0 && strlen($cdr['src']) === 4 && strlen($cdr['dst']) !== 4) {
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
     * Counts calls by disposition status.
     * 
     * @param array $post The post data containing filter parameters.
     * @return array An array containing disposition labels and their corresponding counts.
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
     * Generates SQL filters for SELECT queries.
     * 
     * @param array $post The post data containing filter parameters.
     * @return array An array of filter conditions, each containing SQL snippet, placeholder, and value.
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
     * Adds a text-based filter (src or dst) to the filters array.
     *
     * @param array $filters The filters array to modify by reference.
     * @param array $post The post data containing filter parameters.
     * @param string $field The field name to filter (e.g., 'src', 'dst').
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

    /**
     * Calculates the cost of a call based on number and billing seconds.
     *
     * @param string|null $number The destination number of the call.
     * @param int $billSec The billed seconds for the call.
     * @param array $rates An array of active rates for the period.
     * @return array|null An array with 'cost' and 'rate' or null if no rate matches.
     */
    private function cost(?string $number, int $billSec, array $rates): ?array
    {
        if ($number === null || $billSec <= 3) {
            return null;
        }

        $chargeableSeconds = 0;
        if ($billSec > 30) {
            // Bill in 6-second increments, rounding up
            $chargeableSeconds = (int)(ceil($billSec / 6) * 6);
        } else {
            // Bill a minimum of 30 seconds
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
     * Checks if a number matches an Asterisk dial pattern.
     * 
     * @param string $dialPattern The Asterisk dial pattern to match against.
     * @param string $number The number to check.
     * @return bool True if the number matches the pattern, false otherwise.
     */
    private function match(string $dialPattern, string $number): bool
    {
        if (str_starts_with($dialPattern, '_')) {
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

        foreach ($asteriskToRegexMap as $asteriskChar => $regexEquiv) {
            $pattern = str_replace(preg_quote($asteriskChar, '/'), $regexEquiv, $pattern);
        }

        $finalRegex = "/^" . $pattern . "$/";

        return preg_match($finalRegex, $number) === 1;
    }

    /**
     * Retrieves a single call by its ID.
     *
     * @param int $id The ID of the call to retrieve.
     * @return int The ID of the call (placeholder, as it currently just returns the ID).
     */
    private function getOneCall(int $id): int
    {
        return $id;
    }

    /**
     * Filters and sanitizes date and time parameters from the post data.
     * 
     * @param array $post The post data.
     * @return array The post data with sanitized date and time parameters.
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
     * Retrieves the top 50 call sources by count.
     * 
     * @param array $post The post data containing filter parameters.
     * @return array An array of top source numbers and their call counts.
     */
    private function getTopSrcCount(array $post): array
    {
        $sql = "SELECT src, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";
        $groupBy = "GROUP BY src ORDER BY total DESC LIMIT 50";
        return $this->runFilteredQuery($post, $sql, $groupBy);
    }

    /**
     * Retrieves the top 50 call destinations by count.
     * 
     * @param array $post The post data containing filter parameters.
     * @return array An array of top destination numbers and their call counts.
     */
    private function getTopDstCount(array $post): array
    {
        $sql = "SELECT dst, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";
        $groupBy = "GROUP BY dst ORDER BY total DESC LIMIT 50";
        return $this->runFilteredQuery($post, $sql, $groupBy);
    }

    /**
     * Retrieves call distribution by hour.
     * 
     * @param array $post The post data containing filter parameters.
     * @return array An array of hourly call counts.
     */
    public function getCallsHour(array $post): array
    {
        $sql = "SELECT HOUR(calldate) AS hour, COUNT(*) AS total FROM asteriskcdrdb.cdr WHERE calldate BETWEEN :startDateTime AND :endDateTime";
        $groupBy = "GROUP BY hour ORDER BY hour";
        return $this->runFilteredQuery($post, $sql, $groupBy);
    }

    /**
     * Retrieves total call count, minutes, and average duration.
     * 
     * @param array $post The post data containing filter parameters.
     * @return array An array containing total calls, total minutes, and average duration.
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
     * Executes a filtered SQL query and returns the results.
     *
     * @param array $post The post data containing filter parameters.
     * @param string $baseSql The base SQL query string.
     * @param string $suffix Optional SQL suffix (e.g., GROUP BY, ORDER BY, LIMIT).
     * @return array The fetched results as an associative array, or an empty array if no results.
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
     * Converts an Asterisk dial pattern to a regular expression string for RLIKE queries.
     *
     * @param string $number The Asterisk dial pattern.
     * @return string The converted regular expression string.
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