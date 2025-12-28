<?php

declare(strict_types=1);

namespace FreePBX\modules\Tarifador\Traits;

use DateTime;
use Exception;
use FreePBX\modules\Tarifador\Utils\Sanitize;
use PDO;
use PDOException;

/**
 * Trait RateTrait
 *
 * @package FreePBX\modules\Tarifador\Traits
 * @author  Mauro <https://github.com/mrpbueno>
 */
trait RateTrait
{
    /**
     * Gets the list of rates.
     * @return array The list of rates.
     */
    private function getListRate(): array
    {
        $sql = 'SELECT * FROM tarifador_rate ORDER BY seq ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets a single rate by ID.
     * @param int $id The rate ID.
     * @return array|null The rate data or null if not found.
     */
    private function getOneRate(int $id): ?array
    {
        if ($id <= 0) {
            $_SESSION['toast_message'] = ['message' => _('Invalid or missing ID.'), 'title' => _('Validation Error'), 'level' => 'error'];
            return null;
        }

        $sql = "SELECT * FROM tarifador_rate WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
    }

    /**
     * Adds a new rate.
     * @param array $post The post data.
     */
    private function addRate(array $post): void
    {
        $name = Sanitize::string($post['name'] ?? '');
        $telco = Sanitize::string($post['telco'] ?? '');
        $dial_pattern = Sanitize::string($post['dial_pattern'] ?? '');
        $rate = Sanitize::float($post['rate'] ?? null);
        $start = Sanitize::string($post['start'] ?? '');
        $end = Sanitize::string($post['end'] ?? '');

        if (empty($name) || empty($telco) || empty($dial_pattern) || $rate === false || empty($start) || empty($end)) {
            $_SESSION['toast_message'] = ['message' => _('All fields are required and the rate must be a valid number.'), 'title' => _('Validation Error'), 'level' => 'error'];
            redirect('config.php?display=tarifador&page=rate&view=form');
            return;
        }

        if ($conflict_data = $this->testDate($dial_pattern, $start, $end)) {
            $error_message = sprintf(
                _('A dial pattern already exists in the chosen effective date: %s - %s - %s'),
                htmlspecialchars($conflict_data['dial_pattern'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($conflict_data['start'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($conflict_data['end'], ENT_QUOTES, 'UTF-8')
            );

            $_SESSION['toast_message'] = ['message' => $error_message, 'title' => _('Rate Conflict'), 'level' => 'error'];
            redirect('config.php?display=tarifador&page=rate&view=form');
            return;
        }

        $sql = "INSERT INTO tarifador_rate (name, telco, dial_pattern, rate, start, end) 
                VALUES (:name, :telco, :dial_pattern, :rate, :start, :end)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':telco', $telco, PDO::PARAM_STR);
            $stmt->bindValue(':dial_pattern', $dial_pattern, PDO::PARAM_STR);
            $stmt->bindValue(':rate', $rate);
            $stmt->bindValue(':start', $start, PDO::PARAM_STR);
            $stmt->bindValue(':end', $end, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            freepbx_log(FPBX_LOG_ERROR, "Tarifador => " . $e->getMessage());
            $_SESSION['toast_message'] = ['message' => _('A database error occurred while saving the rate.'), 'title' => _('Error'), 'level' => 'error'];
            redirect('config.php?display=tarifador&page=rate&view=form');
            return;
        }

        $_SESSION['toast_message'] = ['message' => _('Rate added successfully!'), 'title' => _('Success'), 'level' => 'success'];
        redirect('config.php?display=tarifador&page=rate');
    }

    /**
     * Updates a rate.
     * @param array $post The post data.
     */
    private function updateRate(array $post): void
    {
        $id = Sanitize::int($post['id'] ?? 0);
        $name = Sanitize::string($post['name'] ?? '');
        $telco = Sanitize::string($post['telco'] ?? '');
        $dial_pattern = Sanitize::string($post['dial_pattern'] ?? '');
        $rate = Sanitize::float($post['rate'] ?? null);
        $start = Sanitize::string($post['start'] ?? '');
        $end = Sanitize::string($post['end'] ?? '');

        if ($id <= 0 || $rate === false || empty($name) || empty($telco) || empty($dial_pattern) || empty($start) || empty($end)) {
            $_SESSION['toast_message'] = ['message' => _('All fields are required, the ID must be valid and the rate a number.'), 'title' => _('Validation Error'), 'level' => 'error'];
            redirect('config.php?display=tarifador&page=rate&view=form&id=' . $id);
            return;
        }

        $sql = 'UPDATE tarifador_rate SET name = :name, telco = :telco, dial_pattern = :dial_pattern, 
                rate = :rate, start = :start, end = :end WHERE id = :id';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':telco', $telco, PDO::PARAM_STR);
            $stmt->bindValue(':dial_pattern', $dial_pattern, PDO::PARAM_STR);
            $stmt->bindValue(':rate', $rate);
            $stmt->bindValue(':start', $start, PDO::PARAM_STR);
            $stmt->bindValue(':end', $end, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            freepbx_log(FPBX_LOG_ERROR, "Tarifador => " . $e->getMessage());
            $_SESSION['toast_message'] = ['message' => _('A database error occurred while saving the rate.'), 'title' => _('Error'), 'level' => 'error'];
            redirect('config.php?display=tarifador&page=rate&view=form&id=' . $id);
            return;
        }

        $_SESSION['toast_message'] = ['message' => _('Rate updated successfully!'), 'title' => _('Success'), 'level' => 'success'];
        redirect('config.php?display=tarifador&page=rate');
    }

    /**
     * Updates the order of the rates.
     * @param array $post The post data.
     * @return bool True on success, false on failure.
     */
    private function updateOrderRate(array $post): bool
    {
        if (!isset($post['data']) || !is_array($post['data'])) {
            $_SESSION['toast_message'] = ['message' => _('Invalid or missing sort data.'), 'title' => _('Error'), 'level' => 'error'];
            return false;
        }

        $validated_order = [];
        foreach ($post['data'] as $item) {
            $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
            $seq = filter_var($item['seq'] ?? null, FILTER_VALIDATE_INT);

            if ($id === false || $seq === false) {
                $_SESSION['toast_message'] = ['message' => _('One of the items in the sort contained invalid data.'), 'title' => _('Error'), 'level' => 'error'];
                return false;
            }
            $validated_order[] = ['id' => $id, 'seq' => $seq];
        }

        try {
            $this->db->beginTransaction();

            $sql = "UPDATE tarifador_rate SET seq = :seq WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            foreach ($validated_order as $value) {
                $stmt->bindValue(':id', $value['id'], PDO::PARAM_INT);
                $stmt->bindValue(':seq', $value['seq'], PDO::PARAM_INT);
                $stmt->execute();
            }

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            freepbx_log(FPBX_LOG_ERROR, "Tarifador => " . $e->getMessage());
            $_SESSION['toast_message'] = ['message' => _('A database error occurred and the order could not be saved. No changes were made.'), 'title' => _('Error'), 'level' => 'error'];
            return false;
        }

        return true;
    }

    /**
     * Deletes a rate.
     * @param int $id The rate ID.
     */
    private function deleteRate(int $id): void
    {
        if ($id <= 0) {
            $_SESSION['toast_message'] = ['message' => _('Invalid or missing ID for deletion.'), 'title' => _('Validation Error'), 'level' => 'error'];
            redirect('config.php?display=tarifador&page=rate');
            return;
        }

        $sql = "DELETE FROM tarifador_rate WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $_SESSION['toast_message'] = ['message' => _('Rate deleted successfully!'), 'title' => _('Success'), 'level' => 'success'];
            } else {
                $_SESSION['toast_message'] = ['message' => _('The rate was not found or has already been deleted.'), 'title' => _('Warning'), 'level' => 'warning'];
            }
        } catch (PDOException $e) {
            freepbx_log(FPBX_LOG_ERROR, "Tarifador => " . $e->getMessage());
            $_SESSION['toast_message'] = ['message' => _('A database error occurred. The rate could not be deleted, possibly because it is in use.'), 'title' => _('Error'), 'level' => 'error'];
        }

        redirect('config.php?display=tarifador&page=rate');
    }

    /**
     * Tests for date conflicts with a dial pattern.
     * @param string $dialPattern The dial pattern.
     * @param string $start The start date.
     * @param string $end The end date.
     * @return array|null The conflicting data or null.
     */
    private function testDate(string $dialPattern, string $start, string $end): ?array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            return null;
        }

        $sql = "SELECT dial_pattern, start, end FROM tarifador_rate 
                WHERE dial_pattern = :dial_pattern 
                AND ((start BETWEEN :start AND :end) OR (end BETWEEN :start AND :end) OR (start < :start AND end > :end)) 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':dial_pattern', $dialPattern, PDO::PARAM_STR);
        $stmt->bindValue(':start', $start, PDO::PARAM_STR);
        $stmt->bindValue(':end', $end, PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
    }

    /**
     * Gets the active rates for a given date range.
     * @param string $startDate The start date.
     * @param string $endDate The end date.
     * @return array The list of active rates.
     */
    private function getRate(string $startDate, string $endDate): array
    {
        try {
            $dtStart = new \DateTime($startDate);
            $dtEnd = new \DateTime($endDate);
        } catch (\Exception $e) {
            return [];
        }
        $sql = 'SELECT name, dial_pattern, rate, start, end FROM tarifador_rate 
                WHERE start <= :endDate AND end >= :startDate 
                ORDER BY seq ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':startDate', $dtStart->format('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':endDate', $dtEnd->format('Y-m-d'), PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}