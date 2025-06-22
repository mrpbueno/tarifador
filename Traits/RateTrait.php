<?php


namespace FreePBX\modules\Tarifador\Traits;

use FreePBX\modules\Tarifador\Utils\Sanitize;
use PDO;
use PDOException;

/**
 * Trait RateTrait
 * @package FreePBX\modules\Tarifador\Traits
 * @author Mauro <https://github.com/mrpbueno>
 */
trait RateTrait
{
    /**
     * @return array|null
     */
    private function getListRate()
    {
        $sql = 'SELECT * FROM tarifador_rate ORDER BY seq ASC';
        $data = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return is_array($data) ? $data : null;
    }

    /**
     * @param int $id
     * @return array
     */
    private function getOneRate($id)
    {
        $sql = "SELECT * FROM tarifador_rate WHERE id = :id LIMIT 1";
        $stmt = $this->Database->prepare($sql);
        $stmt->bindParam(':id', Sanitize::int($id), PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetchObject();

        return [
            'id' => $row->id,
            'name' => $row->name,
            'telco' => $row->telco,
            'dial_pattern' => $row->dial_pattern,
            'rate' => $row->rate,
            'start' => $row->start,
            'end' => $row->end,
        ];
    }

    /**
     * @param array $post
     * @return bool|void
     */
    private function addRate($post)
    {
        // 1. Validar e sanitizar todas as entradas primeiro, usando o filtro apropriado.
        $args = [
            'dial_pattern' => FILTER_SANITIZE_STRING,
            'start'        => FILTER_SANITIZE_STRING,
            'end'          => FILTER_SANITIZE_STRING,
            'name'         => FILTER_SANITIZE_STRING,
            'telco'        => FILTER_SANITIZE_STRING,
            'rate'         => FILTER_VALIDATE_FLOAT,
        ];
        $safe_post = filter_var_array($post, $args);    
        if (in_array(null, $safe_post, true) || $safe_post['rate'] === false) {
            $_SESSION['toast_message'] = ['message' => 'Todos os campos são obrigatórios e a tarifa deve ser um número válido.', 'title' => 'Erro de Validação', 'level' => 'error'];
         return false;
        }
        // 2. Verificar se já existe uma tarifa conflitante
        $conflict_data = $this->testDate($safe_post['dial_pattern'], $safe_post['start'], $safe_post['end']);
        if (is_array($conflict_data)) {
            // 3. Sanitizar a saída para a mensagem de erro (prevenção de XSS)
            $error_message = "Já existe um padrão de discagem na data de vigência escolhida: ";
            $error_message .= htmlspecialchars($conflict_data['dial_pattern'], ENT_QUOTES, 'UTF-8') . " - ";
            $error_message .= htmlspecialchars($conflict_data['start'], ENT_QUOTES, 'UTF-8') . " - ";
            $error_message .= htmlspecialchars($conflict_data['end'], ENT_QUOTES, 'UTF-8');
        
            $_SESSION['toast_message'] = ['message' => $error_message, 'title' => 'Conflito de Tarifas', 'level' => 'error'];
            return redirect('config.php?display=tarifador&page=rate&view=form');
        }

        // 4. Inserir no banco de dados usando Prepared Statements
        $sql = "INSERT INTO tarifador_rate (name, telco, dial_pattern, rate, start, end) 
                VALUES (:name, :telco, :dial_pattern, :rate, :start, :end)";
        $stmt = $this->db->prepare($sql);

        // 5. Usar bindValue para vincular os valores sanitizados de forma segura
        $stmt->bindValue(':name', $safe_post['name'], PDO::PARAM_STR);
        $stmt->bindValue(':telco', $safe_post['telco'], PDO::PARAM_STR);
        $stmt->bindValue(':dial_pattern', $safe_post['dial_pattern'], PDO::PARAM_STR);
        $stmt->bindValue(':rate', $safe_post['rate']);
        $stmt->bindValue(':start', $safe_post['start'], PDO::PARAM_STR);
        $stmt->bindValue(':end', $safe_post['end'], PDO::PARAM_STR);

        try {
            $stmt->execute();
        } catch (PDOException $e) {        
            $_SESSION['toast_message'] = ['message' => 'Ocorreu um erro no banco de dados ao salvar a tarifa.', 'title' => 'Erro', 'level' => 'error'];
        return false;
        }    
        $_SESSION['toast_message'] = ['message' => 'Tarifa adicionada com sucesso!', 'title' => 'Sucesso', 'level' => 'success'];
        return redirect('config.php?display=tarifador&page=rate');
    }

    /**
     * @param array $post
     * @return bool|void
     */
    private function updateRate($post)
    {
        // 1. DEFINIR AS REGRAS DE VALIDAÇÃO E SANITIZAÇÃO PARA CADA CAMPO
        $args = [
            'id'           => FILTER_VALIDATE_INT,    // O ID DEVE ser um inteiro.
            'name'         => FILTER_SANITIZE_STRING, // Remove tags, etc.
            'telco'        => FILTER_SANITIZE_STRING,
            'dial_pattern' => FILTER_SANITIZE_STRING,
            'rate'         => FILTER_VALIDATE_FLOAT,  // A tarifa DEVE ser um número.
            'start'        => FILTER_SANITIZE_STRING, // Validar como data seria o próximo passo.
            'end'          => FILTER_SANITIZE_STRING,
        ];

        // 2. APLICAR OS FILTROS AO ARRAY DE ENTRADA
        $safe_post = filter_var_array($post, $args);

        // 3. VERIFICAR SE OS DADOS ESSENCIAIS SÃO VÁLIDOS        
        if (empty($safe_post['id']) || $safe_post['rate'] === false) {
            $_SESSION['toast_message'] = ['message' => 'ID inválido ou tarifa não é um número. A operação foi cancelada.', 'title' => 'Erro de Validação', 'level' => 'error'];
            return false;
        }    

        // 4. PREPARAR E EXECUTAR A CONSULTA COM SEGURANÇA
        $sql = 'UPDATE tarifador_rate SET name = :name, telco = :telco, dial_pattern = :dial_pattern, 
                rate = :rate, start = :start, end = :end WHERE id = :id';
    
        $stmt = $this->db->prepare($sql);

        // 5. USAR bindValue PARA VINCULAR OS VALORES JÁ VALIDADOS E SANITIZADOS
        $stmt->bindValue(':id', $safe_post['id'], PDO::PARAM_INT);
        $stmt->bindValue(':name', $safe_post['name'], PDO::PARAM_STR);
        $stmt->bindValue(':telco', $safe_post['telco'], PDO::PARAM_STR);
        $stmt->bindValue(':dial_pattern', $safe_post['dial_pattern'], PDO::PARAM_STR);
        $stmt->bindValue(':rate', $safe_post['rate']); // Deixe o PDO lidar com o tipo float
        $stmt->bindValue(':start', $safe_post['start'], PDO::PARAM_STR);
        $stmt->bindValue(':end', $safe_post['end'], PDO::PARAM_STR);

        try {
            $stmt->execute();
        } catch (PDOException $e) {            
            $_SESSION['toast_message'] = ['message' => 'Ocorreu um erro no banco de dados ao salvar a tarifa.', 'title' => 'Erro', 'level' => 'error'];
            return false;
        }

        $_SESSION['toast_message'] = ['message' => 'Tarifa atualizada com sucesso!', 'title' => 'Sucesso', 'level' => 'success'];
        return redirect('config.php?display=tarifador&page=rate');
    }

    /**
     * @param array $post
     * @return bool
     */
    private function updateOrderRate($post)
    {
        // 1. VERIFICAÇÃO INICIAL DA ENTRADA    
        if (!isset($post['data']) || !is_array($post['data'])) {
            $_SESSION['toast_message'] = ['message' => 'Dados de ordenação inválidos ou ausentes.', 'title' => 'Erro', 'level' => 'error'];
            return false;
        }

        // 2. VALIDAÇÃO E LIMPEZA DOS DADOS    
        $validated_order = [];
        foreach ($post['data'] as $item) {            
            $id = filter_var(isset($item['id']) ? $item['id'] : null, FILTER_VALIDATE_INT);
            $seq = filter_var(isset($item['seq']) ? $item['seq'] : null, FILTER_VALIDATE_INT);
            // Se a validação falhar para qualquer item, aborta a operação inteira.
            if ($id === false || $seq === false) {
                $_SESSION['toast_message'] = ['message' => 'Um dos itens na ordenação continha dados inválidos.', 'title' => 'Erro', 'level' => 'error'];
                return false;
            }
            $validated_order[] = ['id' => $id, 'seq' => $seq];
        }
    
        // 3. USO DE TRANSAÇÃO PARA GARANTIR INTEGRIDADE (TUDO OU NADA)
        try {
            // Inicia a transação
            $this->db->beginTransaction();

            $sql = "UPDATE tarifador_rate SET seq = :seq WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            // Itera sobre o array JÁ VALIDADO.
            foreach ($validated_order as $value) {                
                $stmt->bindValue(':id', $value['id'], PDO::PARAM_INT);
                $stmt->bindValue(':seq', $value['seq'], PDO::PARAM_INT);
                $stmt->execute();
            }            
            $this->db->commit();

        } catch (PDOException $e) {
            // Se QUALQUER 'execute' falhar, desfaz TODAS as alterações.
            $this->db->rollBack();        
            $_SESSION['toast_message'] = ['message' => 'Ocorreu um erro no banco de dados e a ordem não pôde ser salva. Nenhuma alteração foi feita.', 'title' => 'Erro', 'level' => 'error'];
            return false;
        }        
        
        return true; 
    }

    /**
     * Exclui uma tarifa do banco de dados
     * 
     * @param int $id
     * @return bool|void
     */
    private function deleteRate($id)
    {        
        $validated_id = filter_var($id, FILTER_VALIDATE_INT);
        if ($validated_id === false || $validated_id <= 0) {
            $_SESSION['toast_message'] = ['message' => 'ID inválido ou não fornecido para exclusão.', 'title' => 'Erro de Validação', 'level' => 'error'];
        return false;
        }        
        $sql = "DELETE FROM tarifador_rate WHERE id = :id";
        $stmt = $this->db->prepare($sql);    
        $stmt->bindValue(':id', $validated_id, PDO::PARAM_INT);        
        try {
            $stmt->execute();            
            if ($stmt->rowCount() > 0) {            
                $_SESSION['toast_message'] = ['message' => 'Tarifa excluída com sucesso!', 'title' => 'Sucesso', 'level' => 'success'];
            } else {            
                $_SESSION['toast_message'] = ['message' => 'A tarifa não foi encontrada ou já havia sido excluída.', 'title' => 'Aviso', 'level' => 'warning'];
            }
        } catch (PDOException $e) {        
            $_SESSION['toast_message'] = ['message' => 'Ocorreu um erro no banco de dados. A tarifa não pôde ser excluída, possivelmente por estar em uso.', 'title' => 'Erro', 'level' => 'error'];          
        }

        return redirect('config.php?display=tarifador&page=rate');
    }

    /**
     * Encontra uma tarifa conflitante com base em um padrão de discagem e um intervalo de datas.
     * 
     * @param string $dialPattern O padrão de discagem a ser verificado.
     * @param string $start A data de início do novo intervalo no formato 'YYYY-MM-DD'.
     * @param string $end A data de fim do novo intervalo no formato 'YYYY-MM-DD'.
     * @return array|false Retorna o array da tarifa conflitante se encontrada, caso contrário, retorna false.
     */
    private function testDate($dialPattern, $start, $end)
    {
        $dialPattern = Sanitize::string($dialPattern);
        $start = Sanitize::string($start);
        $end = Sanitize::string($end);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {        
            return false;
        }
        $sql = "SELECT dial_pattern, start, end FROM tarifador_rate WHERE dial_pattern = :dial_pattern ";
        $sql .= "AND ((start BETWEEN :start AND :end) OR (end BETWEEN :start AND :end) ";
        $sql .= "OR (start < :start AND end > :end)) LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':dial_pattern', $dialPattern, PDO::PARAM_STR);
        $stmt->bindParam(':start', $start, PDO::PARAM_STR);
        $stmt->bindParam(':end', $end, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    /**
     * Busca todas as tarifas ativas para uma data específica.
     *
     * @param string $callDate A data para a qual buscar as tarifas.
     * @return array Retorna um array de tarifas ou um array vazio se não houver correspondência ou a data for inválida.
     */
    private function getRate($callDate)
    {
        $callDate = Sanitize::string($callDate);
        $timestamp = strtotime($callDate);
        if ($timestamp === false) { return []; }
        $date = date('Y-m-d', $timestamp);
        $sql = 'SELECT name, dial_pattern, rate FROM tarifador_rate WHERE start <= :calldate AND end >= :calldate ORDER BY seq ASC';
        $stmt = $this->db->prepare($sql);        
        $stmt->bindParam(':calldate', $date, PDO::PARAM_STR);
        $stmt->execute();
        $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rates = is_array($rates) ? $rates : null;

        return $rates;
    }
}