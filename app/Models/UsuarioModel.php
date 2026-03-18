<?php

/**
 * Model de Usuários
 *
 * Responsável EXCLUSIVAMENTE pelo acesso aos dados das tabelas
 * 'usuarios' e 'refresh_tokens'.
 *
 * No padrão MVC, o Model:
 *   ✓ Executa queries SQL
 *   ✓ Retorna dados brutos do banco
 *   ✗ NÃO valida regras de negócio (isso é responsabilidade do Service)
 *   ✗ NÃO formata dados para resposta HTTP (isso é responsabilidade do Controller)
 *
 * Segurança: TODAS as queries usam Prepared Statements (parâmetros '?')
 * para prevenir SQL Injection.
 */
class UsuarioModel
{
    /** @var PDO Conexão com o banco de dados */
    private $db;

    public function __construct()
    {
        // Obtém a instância única da conexão (padrão Singleton)
        $this->db = Connection::getInstance();
    }

    /**
     * Busca um usuário pelo nome de login.
     *
     * @param  string     $usuario Nome de usuário
     * @return array|null Dados do usuário (id, usuario, senha) ou null
     */
    public function buscarPorUsuario(string $usuario): ?array
    {
        // Prepared Statement: o '?' é substituído de forma segura pelo PDO
        $stmt = $this->db->prepare(
            'SELECT id, usuario, senha FROM usuarios WHERE usuario = ? LIMIT 1'
        );
        $stmt->execute([$usuario]);

        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Busca um usuário pelo ID numérico.
     *
     * @param  int        $id ID do usuário
     * @return array|null Dados do usuário (id, usuario) ou null
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, usuario FROM usuarios WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);

        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Persiste um refresh token no banco de dados.
     *
     * Um usuário pode ter vários refresh tokens simultaneamente
     * (exemplo: logado em diferentes dispositivos).
     *
     * @param int    $usuarioId  ID do usuário dono do token
     * @param string $token      String aleatória do refresh token
     * @param string $expiraEm   Data/hora de expiração (formato: YYYY-MM-DD HH:MM:SS)
     */
    public function salvarRefreshToken(int $usuarioId, string $token, string $expiraEm): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO refresh_tokens (usuario_id, token, expires_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$usuarioId, $token, $expiraEm]);
    }

    /**
     * Busca um refresh token válido (que ainda não expirou).
     *
     * O JOIN com 'usuarios' retorna também os dados do usuário
     * para evitar uma segunda query.
     *
     * @param  string     $token O refresh token a buscar
     * @return array|null Dados do token + usuário, ou null se inválido/expirado
     */
    public function buscarRefreshToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT rt.id, rt.usuario_id, rt.expires_at, u.usuario
             FROM refresh_tokens rt
             INNER JOIN usuarios u ON u.id = rt.usuario_id
             WHERE rt.token = ? AND rt.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$token]);

        $resultado = $stmt->fetch();
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Remove um refresh token específico do banco.
     *
     * Chamado após o uso do refresh token para invalidá-lo
     * (técnica conhecida como "Refresh Token Rotation").
     *
     * @param string $token Token a remover
     */
    public function removerRefreshToken(string $token): void
    {
        $stmt = $this->db->prepare('DELETE FROM refresh_tokens WHERE token = ?');
        $stmt->execute([$token]);
    }

    /**
     * Remove todos os refresh tokens de um usuário.
     *
     * Útil para implementar "logout de todos os dispositivos".
     *
     * @param int $usuarioId ID do usuário
     */
    public function removerTodosRefreshTokens(int $usuarioId): void
    {
        $stmt = $this->db->prepare('DELETE FROM refresh_tokens WHERE usuario_id = ?');
        $stmt->execute([$usuarioId]);
    }
}
