<?php

namespace FreePBX\modules\Tarifador\Traits;

use FreePBX\modules\Tarifador\Utils\Sanitize;
use PDO;
use PDOException;
use Exception;

/**
 * Trait PinUserTrait
 * @package FreePBX\modules\Tarifador\Traits
 * @author Mauro <https://github.com/mrpbueno>
 */
trait PinUserTrait
{
    /**
     * @param int $id
     * @return bool|void
     */
    private function deletePinUser(mixed $id): void
    {
        $validated_id = Sanitize::int($id);
        if ($validated_id === false || $validated_id <= 0) {
            $_SESSION['toast_message'] = ['message' => 'ID inválido ou não fornecido para exclusão.', 'title' => 'Erro de Validação', 'level' => 'error'];
            return;
        }

        $sql = "DELETE FROM tarifador_pinuser WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute(['id' => $validated_id]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['toast_message'] = ['message' => 'Usuário excluído com sucesso!', 'title' => 'Sucesso', 'level' => 'success'];
            } else {
                $_SESSION['toast_message'] = ['message' => 'O usuário não foi encontrado ou já havia sido excluído.', 'title' => 'Aviso', 'level' => 'warning'];
            }
        } catch (PDOException $e) {
            $_SESSION['toast_message'] = ['message' => 'Ocorreu um erro no banco de dados. O usuário não pôde ser excluído.', 'title' => 'Erro', 'level' => 'error'];
        }

        redirect('config.php?display=tarifador&page=pinuser');
    }

    /**
     * Atualiza os dados de um usuário/PIN existente.
     * 
     * @param array $post O array $_POST com os dados do formulário.
     * @return bool|void Retorna `false` em caso de falha de validação ou redireciona.
     */
        private function updatePinUser(array $post): void
        {
            $id = Sanitize::int($post['id'] ?? null);
            $user = Sanitize::string($post['user'] ?? '');
            $department = Sanitize::string($post['department'] ?? '');
    
            if ($id === false || $id <= 0 || empty($user)) {
                $_SESSION['toast_message'] = ['message' => 'ID ou nome de usuário inválido ou ausente.', 'title' => 'Erro de Validação', 'level' => 'error'];
                return;
            }
    
            try {
                $sql = "UPDATE tarifador_pinuser SET user = :user, department = :department WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':id' => $id,
                    ':user' => $user,
                    ':department' => $department
                ]);
                $_SESSION['toast_message'] = ['message' => 'Usuário atualizado com sucesso!', 'title' => 'Sucesso', 'level' => 'success'];
    
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $_SESSION['toast_message'] = ['message' => 'O nome de usuário informado já está em uso por outro PIN.', 'title' => 'Erro', 'level' => 'error'];
                } else {
                    $_SESSION['toast_message'] = ['message' => 'Ocorreu um erro inesperado no banco de dados ao salvar as alterações.', 'title' => 'Erro', 'level' => 'error'];
                }
                return;
            }
    
            redirect('config.php?display=tarifador&page=pinuser');
        }
    /**
     * Adiciona um usuário/PIN
     * 
     * @param array $post O array $_POST com os dados do formulário.
     * @return void
     */
    private function addPinUser(array $data): void
    {
        $sql = "INSERT INTO tarifador_pinuser (pin, user, department, enabled) VALUES (:pin, :user, :department, :enabled)";
        $params = [
            ':pin' => Sanitize::string($data['pin'] ?? ''),
            ':user' => Sanitize::string($data['user'] ?? ''),
            ':department' => Sanitize::string($data['department'] ?? ''),
            ':enabled' => Sanitize::int($data['enabled'] ?? 1),
        ];

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate key
                $updateSql = "UPDATE tarifador_pinuser SET enabled = 1 WHERE pin = :pin";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute(['pin' => $params[':pin']]);
            }
        }
    }

    /**
     * Sincroniza os PINs do FreePBX com o módulo Tarifador/Usuário
     * Após sincronização é possível associar o PIN com o nome do usuário via formulário ou CSV
     * 
     * @return void
     */
        private function syncPinUser(): void
        {
            try {
                // 1. GET ALL VALID PINS FROM FreePBX
                $sqlFetchPins = "SELECT passwords FROM pinsets WHERE passwords != ''";
                $stmtFetchPins = $this->db->query($sqlFetchPins);
    
                $all_freepbx_pins = [];
                foreach ($stmtFetchPins->fetchAll(PDO::FETCH_COLUMN) as $passwords_blob) {
                    $pins_from_row = array_filter(array_map('trim', explode("\n", $passwords_blob)));
                    if (!empty($pins_from_row)) {
                        $all_freepbx_pins = array_merge($all_freepbx_pins, $pins_from_row);
                    }
                }
                $all_freepbx_pins = array_unique($all_freepbx_pins);
    
                if (empty($all_freepbx_pins)) {
                    // Se não há PINs no FreePBX, desabilita todos e encerra.
                    $this->db->exec("UPDATE tarifador_pinuser SET enabled = 0");
                    $_SESSION['toast_message'] = ['message' => 'Nenhum PIN encontrado no FreePBX. Todos os usuários foram desabilitados.', 'title' => 'Aviso', 'level' => 'warning'];
                    redirect('config.php?display=tarifador&page=pinuser');
                    return;
                }
    
                // 2. INICIAR A TRANSAÇÃO (GARANTIR INTEGRIDADE)
                $this->db->beginTransaction();
    
                // 3. DESABILITAR USUÁRIOS QUE NÃO EXISTEM MAIS
                $placeholders_not_in = implode(',', array_fill(0, count($all_freepbx_pins), '?'));
                $sqlDisable = "UPDATE tarifador_pinuser SET enabled = 0 WHERE pin NOT IN ($placeholders_not_in)";
                $stmtDisable = $this->db->prepare($sqlDisable);
                $stmtDisable->execute(array_values($all_freepbx_pins));
    
                // 4. INSERIR NOVOS PINS E ATIVAR/ATUALIZAR EXISTENTES EM UMA ÚNICA QUERY
                $sqlUpsert_parts = ["INSERT INTO tarifador_pinuser (pin, user, department, enabled) VALUES"];
                $insert_rows = [];
                $params = [];
                foreach ($all_freepbx_pins as $pin) {
                    $insert_rows[] = '(?, ?, ?, ?)';
                    array_push($params, $pin, '---', '---', 1);
                }
                $sqlUpsert_parts[] = implode(', ', $insert_rows);
                $sqlUpsert_parts[] = "ON DUPLICATE KEY UPDATE enabled = 1, user = IF(user = '---' OR user IS NULL, VALUES(user), user), department = IF(department = '---' OR department IS NULL, VALUES(department), department)";
    
                $stmtUpsert = $this->db->prepare(implode(' ', $sqlUpsert_parts));
                $stmtUpsert->execute($params);
    
                // 5. SE TUDO DEU CERTO, CONFIRMA A TRANSAÇÃO
                $this->db->commit();
                $_SESSION['toast_message'] = ['message' => 'Sincronização de PINs concluída com sucesso!', 'title' => 'Sucesso', 'level' => 'success'];
    
            } catch (PDOException $e) {
                // 6. SE QUALQUER ERRO OCORREU, DESFAZ TUDO
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $_SESSION['toast_message'] = ['message' => 'Ocorreu um erro crítico durante a sincronização. Nenhuma alteração foi salva.', 'title' => 'Erro', 'level' => 'error'];
            }
    
            redirect('config.php?display=tarifador&page=pinuser');
        }
    /**
     * @return array|null
     */
    private function getListPinUser(): ?array
    {
        $sql = "SELECT * FROM tarifador_pinuser";
        $data = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return $data === false ? null : $data;
    }

    /**
     * @param int $id
     * @return array
     */
        private function getOnePinUser(mixed $id): array|false
        {
            $validated_id = Sanitize::int($id);
            if ($validated_id === false || $validated_id <= 0) {
                $_SESSION['toast_message'] = ['message' => 'ID inválido ou não fornecido.', 'title' => 'Erro de Validação', 'level' => 'error'];
                return false;
            }
            $sql = "SELECT * FROM tarifador_pinuser WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $validated_id]);
            $pinuser = $stmt->fetchObject();
    
            if ($pinuser === false) {
                return false; // User not found
            }
    
            $sql = "SELECT pinsets_id, description FROM pinsets WHERE passwords LIKE :passwords";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['passwords' => "%{$pinuser->pin}%"]);
            $pinsets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            return [
                'id' => $pinuser->id,
                'pin' => $pinuser->pin,
                'user' => $pinuser->user,
                'department' => $pinuser->department,
                'enabled' => $pinuser->enabled,
                'pinsets' => $pinsets,
            ];
        }

    /**
     * Importa um arquivo CSV com uma lista de pin, user e department.
     *
     * @param array $post O array $_POST contendo os dados do formulário.
     * @return void Redireciona o usuário após a operação.
     */
    private function importPinUser(array $post): void
    {
        // 1. VALIDAÇÃO ROBUSTA DO UPLOAD
        if (!isset($_FILES['user_file']) || $_FILES['user_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['toast_message'] = ['message' => 'Erro no upload do arquivo ou nenhum arquivo enviado.', 'title' => 'Erro', 'level' => 'error'];
            redirect('config.php?display=tarifador&page=pinuser');
            return;
        }

        $file_path = $_FILES['user_file']['tmp_name'];

        // Valida o tipo MIME para garantir que é um arquivo de texto/csv
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);

        if (!in_array($mime_type, ['text/csv', 'text/plain'], true)) {
            $_SESSION['toast_message'] = ['message' => 'Formato de arquivo inválido. Por favor, envie um arquivo .csv.', 'title' => 'Erro', 'level' => 'error'];
            redirect('config.php?display=tarifador&page=pinuser');
            return;
        }

        $file = fopen($file_path, 'r');
        if ($file === false) {
            $_SESSION['toast_message'] = ['message' => 'Não foi possível abrir o arquivo enviado.', 'title' => 'Erro', 'level' => 'error'];
            redirect('config.php?display=tarifador&page=pinuser');
            return;
        }

        // 2. PREPARAÇÃO FORA DO LOOP E INÍCIO DA TRANSAÇÃO
        try {
            $header = fgetcsv($file, 5000, ",");
            $expected_header = ['pin', 'user', 'department'];
            if (empty($header) || count(array_diff($expected_header, array_map('strtolower', $header))) > 0) {
                $_SESSION['toast_message'] = ['message' => 'Cabeçalho do CSV inválido. As colunas esperadas são: pin, user, department.', 'title' => 'Erro de Formato', 'level' => 'error'];
                redirect('config.php?display=tarifador&page=pinuser');
                return;
            }

            $this->db->beginTransaction();
            $sqlUpdate = "UPDATE tarifador_pinuser SET user = :user, department = :department WHERE pin = :pin";
            $stmtUpdate = $this->db->prepare($sqlUpdate);

            $sqlInsert = "INSERT INTO tarifador_pinuser (pin, user, department, enabled) VALUES (:pin, :user, :department, 1)";
            $stmtInsert = $this->db->prepare($sqlInsert);

            $line_number = 1;
            // 3. PROCESSAMENTO LINHA A LINHA (BAIXO USO DE MEMÓRIA)
            while (($row_data = fgetcsv($file, 5000, ",")) !== false) {
                $line_number++;
                if (count($header) !== count($row_data)) {
                    continue; // Pula linhas malformadas
                }
                $row = array_combine($header, $row_data);

                // Validação e sanitização da linha
                $pin = Sanitize::stringInput($row['pin'] ?? null);
                $user = Sanitize::stringInput($row['user'] ?? null);
                $department = Sanitize::stringInput($row['department'] ?? null);

                if (empty($pin) || empty($user)) {
                    continue; // Pula linhas sem pin ou user
                }

                // Tenta o UPDATE
                $stmtUpdate->execute([':user' => $user, ':department' => $department, ':pin' => $pin]);

                // Se nenhuma linha foi afetada, faz o INSERT
                if ($stmtUpdate->rowCount() === 0) {
                    $stmtInsert->execute([':pin' => $pin, ':user' => $user, ':department' => $department]);
                }
            }

            // Se tudo correu bem, confirma as alterações
            $this->db->commit();
            $_SESSION['toast_message'] = ['message' => 'Importação concluída com sucesso!', 'title' => 'Sucesso', 'level' => 'success'];

        } catch (Exception $e) {
            // 4. Se qualquer erro ocorrer, desfaz TODAS as alterações
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['toast_message'] = ['message' => "Ocorreu um erro na importação na linha {$line_number}. Nenhuma alteração foi salva.", 'title' => 'Erro Crítico', 'level' => 'error'];
        } finally {
            fclose($file);
        }

        redirect('config.php?display=tarifador&page=pinuser');
    }

    /**
     * @param array $request
     * @return mixed
     */
    private function getDepartment(array $request): array
    {
        $sql = "SELECT DISTINCT department AS name FROM tarifador_pinuser WHERE department LIKE :department LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $term = Sanitize::string($request['term'] ?? '');
        $stmt->execute(['department' => "%{$term}%"]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data ?: [];
    }

    /**
     * Busca usuários para popular um select.
     * 
     * @param array $request
     * @return mixed
     */
    private function getUser(array $request): array
    {
        $sql = "SELECT pin AS id, user AS text FROM tarifador_pinuser WHERE user LIKE :user LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $term = Sanitize::string($request['term'] ?? '');
        $stmt->execute(['user' => "%{$term}%"]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data ?: [];
    }

    /**
     * @param string $pin
     * @return string
     */
    private function getPinUser(string $pin): string
    {
        if (empty($pin)) {
            return '';
        }
        $pin = Sanitize::string($pin);
        $sql = "SELECT user FROM tarifador_pinuser WHERE pin = :pin LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pin' => $pin]);
        $pinuser = $stmt->fetchObject();

        return $pinuser->user ?? _("Sem Cadastro");
    }
}