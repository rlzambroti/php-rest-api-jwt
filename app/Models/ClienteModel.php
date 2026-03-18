<?php

/**
 * Model de Clientes
 *
 * Implementa as operações CRUD para a tabela 'clientes':
 *   C → Create  (criar)
 *   R → Read    (listarTodos, buscarPorId, buscarPorCpf)
 *   U → Update  (atualizar)
 *   D → Delete  (excluir)
 *
 * Todas as queries usam Prepared Statements para prevenir SQL Injection.
 */
class ClienteModel
{
    /** @var PDO Conexão com o banco de dados */
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Retorna todos os clientes, ordenados por nome.
     *
     * @return array Lista de clientes (pode ser vazia)
     */
    public function listarTodos(): array
    {
        $stmt = $this->db->query(
            'SELECT id, cpf, nome, data_nascimento, whatsapp, email, created_at, updated_at
             FROM clientes
             ORDER BY nome ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Busca um cliente pelo ID.
     *
     * @param  int        $id ID do cliente
     * @return array|null Dados do cliente ou null se não existir
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, cpf, nome, data_nascimento, whatsapp, email, created_at, updated_at
             FROM clientes WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);

        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Busca um cliente pelo CPF.
     * Usado para verificar duplicidade antes de criar/atualizar.
     *
     * @param  string     $cpf CPF do cliente
     * @return array|null Dados do cliente ou null se não existir
     */
    public function buscarPorCpf(string $cpf): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, cpf FROM clientes WHERE cpf = ? LIMIT 1'
        );
        $stmt->execute([$cpf]);

        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Insere um novo cliente no banco de dados.
     *
     * Usa parâmetros nomeados (:campo) para maior legibilidade.
     *
     * @param  array $dados Campos: cpf, nome, data_nascimento, whatsapp, email
     * @return int   ID do cliente recém-criado
     */
    public function criar(array $dados): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO clientes (cpf, nome, data_nascimento, whatsapp, email)
             VALUES (:cpf, :nome, :data_nascimento, :whatsapp, :email)'
        );

        $stmt->execute([
            ':cpf'             => $dados['cpf'],
            ':nome'            => $dados['nome'],
            ':data_nascimento' => $dados['data_nascimento'],
            ':whatsapp'        => isset($dados['whatsapp']) && $dados['whatsapp'] !== '' ? $dados['whatsapp'] : null,
            ':email'           => isset($dados['email'])    && $dados['email']    !== '' ? $dados['email']    : null,
        ]);

        // lastInsertId() retorna o ID gerado pelo AUTO_INCREMENT
        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza todos os campos de um cliente existente.
     *
     * @param  int   $id    ID do cliente a atualizar
     * @param  array $dados Novos valores dos campos
     * @return bool  true se pelo menos uma linha foi alterada
     */
    public function atualizar(int $id, array $dados): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE clientes
             SET cpf = :cpf,
                 nome = :nome,
                 data_nascimento = :data_nascimento,
                 whatsapp = :whatsapp,
                 email = :email
             WHERE id = :id'
        );

        $stmt->execute([
            ':cpf'             => $dados['cpf'],
            ':nome'            => $dados['nome'],
            ':data_nascimento' => $dados['data_nascimento'],
            ':whatsapp'        => isset($dados['whatsapp']) && $dados['whatsapp'] !== '' ? $dados['whatsapp'] : null,
            ':email'           => isset($dados['email'])    && $dados['email']    !== '' ? $dados['email']    : null,
            ':id'              => $id,
        ]);

        // rowCount() retorna o número de linhas afetadas pela query
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove um cliente do banco de dados.
     *
     * @param  int  $id ID do cliente a remover
     * @return bool true se o cliente foi removido
     */
    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM clientes WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}
