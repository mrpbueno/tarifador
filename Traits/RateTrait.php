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
    private function getListRate(): array
    {
        $sql = 'SELECT * FROM tarifador_rate ORDER BY seq ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getOneRate(int $id): ?array
    {
        if ($id <= 0) {
            $_SESSION['toast_message'] = ['message' => _('ID inválido ou não fornecido.'), 'title' => _('Erro de Validação'), 'level' => 'error'];
            return null;
        }

        $sql = "SELECT * FROM tarifador_rate WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
    }

    private function addRate(array $post): void
    {
        $name = Sanitize::string($post['name'] ?? '');
        $telco = Sanitize::string($post['telco'] ?? '');
        $dial_pattern = Sanitize::string($post['dial_pattern'] ?? '');
        $rate = Sanitize::float($post['rate'] ?? null);
        $start = Sanitize::string($post['start'] ?? '');
        $end = Sanitize::string($post['end'] ?? '');

        if (empty($name) || empty($telco) || empty($dial_pattern) || $rate === false || empty($start) || empty($end)) {
            $_SESSION['toast_message'] = ['message' => _('Todos os campos são obrigatórios e a tarifa deve ser um número válido.'), 'title' => _('Erro de Validação'), 'level' => 'error'];
            redirect('config.php?display=tarifador&page=rate&view=form');
            return;
        }

        if ($conflict_data = $this->testDate($dial_pattern, $start, $end)) {
            $error_message = sprintf(
                _('Já existe um padrão de discagem na data de vigência escolhida: %s - %s - %s'),
                htmlspecialchars($conflict_data['dial_pattern'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($conflict_data['start'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($conflict_data['end'], ENT_QUOTES, 'UTF-8')
            );

            $_SESSION['toast_message'] = ['message' => $error_message, 'title' => _('Conflito de Tarifas'), 'level' => 'error'];
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
            $_SESSION['toast_message'] = ['message' => _('Ocorreu um erro no banco de dados ao salvar a tarifa.'), 'title' => _('Erro'), 'level' => 'error'];
            redirect('config.php?display=tarifador&page=rate&view=form');
            return;
        }

        $_SESSION['toast_message'] = ['message' => _('Tarifa adicionada com sucesso!'), 'title' => _('Sucesso'), 'level' => 'success'];
        redirect('config.php?display=tarifador&page=rate');
    }

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
            $_SESSION['toast_message'] = ['message' => _('Todos os campos são obrigatórios, o ID deve ser válido e a tarifa um número.'), 'title' => _('Erro de Validação'), 'level' => 'error'];
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
            $_SESSION['toast_message'] = ['message' => _('Ocorreu um erro no banco de dados ao salvar a tarifa.'), 'title' => _('Erro'), 'level' => 'error'];
            redirect('config.php?display=tarifador&page=rate&view=form&id=' . $id);
            return;
        }

        $_SESSION['toast_message'] = ['message' => _('Tarifa atualizada com sucesso!'), 'title' => _('Sucesso'), 'level' => 'success'];
        redirect('config.php?display=tarifador&page=rate');
    }

    private function updateOrderRate(array $post): bool
    {
        if (!isset($post['data']) || !is_array($post['data'])) {
            $_SESSION['toast_message'] = ['message' => _('Dados de ordenação inválidos ou ausentes.'), 'title' => _('Erro'), 'level' => 'error'];
            return false;
        }

        $validated_order = [];
        foreach ($post['data'] as $item) {
            $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
            $seq = filter_var($item['seq'] ?? null, FILTER_VALIDATE_INT);

            if ($id === false || $seq === false) {
                $_SESSION['toast_message'] = ['message' => _('Um dos itens na ordenação continha dados inválidos.'), 'title' => _('Erro'), 'level' => 'error'];
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
            $_SESSION['toast_message'] = ['message' => _('Ocorreu um erro no banco de dados e a ordem não pôde ser salva. Nenhuma alteração foi feita.'), 'title' => _('Erro'), 'level' => 'error'];
            return false;
        }

        return true;
    }

    private function deleteRate(int $id): void
    {
        if ($id <= 0) {
            $_SESSION['toast_message'] = ['message' => _('ID inválido ou não fornecido para exclusão.'), 'title' => _('Erro de Validação'), 'level' => 'error'];
            redirect('config.php?display=tarifador&page=rate');
            return;
        }

        $sql = "DELETE FROM tarifador_rate WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $_SESSION['toast_message'] = ['message' => _('Tarifa excluída com sucesso!'), 'title' => _('Sucesso'), 'level' => 'success'];
            } else {
                $_SESSION['toast_message'] = ['message' => _('A tarifa não foi encontrada ou já havia sido excluída.'), 'title' => _('Aviso'), 'level' => 'warning'];
            }
        } catch (PDOException $e) {
            freepbx_log(FPBX_LOG_ERROR, "Tarifador => " . $e->getMessage());
            $_SESSION['toast_message'] = ['message' => _('Ocorreu um erro no banco de dados. A tarifa não pôde ser excluída, possivelmente por estar em uso.'), 'title' => _('Erro'), 'level' => 'error'];
        }

        redirect('config.php?display=tarifador&page=rate');
    }

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