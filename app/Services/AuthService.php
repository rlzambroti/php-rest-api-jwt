<?php

/**
 * Service de Autenticação
 *
 * Contém a lógica de negócio relacionada à autenticação:
 *   - Verificar credenciais do usuário
 *   - Gerar access token (JWT) e refresh token
 *   - Renovar tokens (Refresh Token Rotation)
 *
 * Posição no MVC:
 *   Controller → Service → Model → Banco de Dados
 *
 * O Service NÃO sabe nada sobre HTTP (sem Request/Response).
 * Ele recebe dados processados e retorna resultados de negócio.
 */
class AuthService
{
    /** @var UsuarioModel Responsável pelo acesso ao banco */
    private $usuarioModel;

    public function __construct(UsuarioModel $usuarioModel)
    {
        $this->usuarioModel = $usuarioModel;
    }

    /**
     * Realiza o login: verifica credenciais e gera tokens.
     *
     * @param  string $usuario Nome de usuário
     * @param  string $senha   Senha em texto plano
     * @return array  Par de tokens: access_token + refresh_token
     *
     * @throws InvalidArgumentException Se usuário não existir ou senha for errada
     */
    public function login(string $usuario, string $senha): array
    {
        // 1. Busca o usuário no banco de dados
        $dadosUsuario = $this->usuarioModel->buscarPorUsuario($usuario);

        // 2. Verifica se existe e se a senha bate com o hash bcrypt armazenado
        //    password_verify() é seguro: usa comparação em tempo constante
        if ($dadosUsuario === null || !password_verify($senha, $dadosUsuario['senha'])) {
            // Mensagem genérica — não revele se o usuário existe ou não
            throw new InvalidArgumentException('Usuário ou senha incorretos.');
        }

        // 3. Credenciais corretas → gera e retorna os tokens
        return $this->gerarTokens($dadosUsuario);
    }

    /**
     * Renova os tokens usando um refresh token válido.
     *
     * Implementa "Refresh Token Rotation":
     *   - O refresh token antigo é INVALIDADO após o uso
     *   - Um novo par de tokens é gerado e retornado
     *
     * Vantagem: se um refresh token for roubado e usado,
     * o token original fica inválido e o sistema detecta a anomalia.
     *
     * @param  string $refreshToken Token de renovação
     * @return array  Novo par de tokens
     *
     * @throws InvalidArgumentException Se o refresh token for inválido ou expirado
     */
    public function renovarTokens(string $refreshToken): array
    {
        // 1. Verifica se o refresh token existe no banco e não está expirado
        $dadosToken = $this->usuarioModel->buscarRefreshToken($refreshToken);

        if ($dadosToken === null) {
            throw new InvalidArgumentException('Refresh token inválido ou expirado.');
        }

        // 2. Invalida o refresh token usado (ele não pode ser reutilizado)
        $this->usuarioModel->removerRefreshToken($refreshToken);

        // 3. Busca os dados atuais do usuário
        $dadosUsuario = $this->usuarioModel->buscarPorId((int) $dadosToken['usuario_id']);

        if ($dadosUsuario === null) {
            throw new RuntimeException('Usuário não encontrado.');
        }

        // 4. Gera e retorna um novo par de tokens
        return $this->gerarTokens($dadosUsuario);
    }

    /**
     * Gera um par access_token + refresh_token para um usuário.
     *
     * Access Token (JWT):
     *   - Curta duração (padrão: 15 minutos)
     *   - Assinado digitalmente — não precisa consultar banco para validar
     *   - Contém: id do usuário, nome de usuário, emissor, data de expiração
     *
     * Refresh Token (string aleatória):
     *   - Longa duração (padrão: 7 dias)
     *   - Armazenado no banco (pode ser revogado a qualquer momento)
     *   - Usado APENAS para obter novos access tokens
     *
     * @param  array $usuario Dados do usuário (id, usuario)
     * @return array ['access_token', 'refresh_token', 'token_type', 'expires_in']
     */
    private function gerarTokens(array $usuario): array
    {
        $config = require __DIR__ . '/../../config/jwt.php';
        $agora  = time();

        // ── Access Token (JWT) ────────────────────────────────────────────
        $payloadAcesso = [
            'iss'     => 'api-academica',                       // Issuer: emissor do token
            'iat'     => $agora,                                // Issued At: quando foi criado
            'exp'     => $agora + $config['expiracao_acesso'],  // Expiration: quando expira
            'sub'     => $usuario['id'],                        // Subject: ID do usuário
            'usuario' => $usuario['usuario'],                   // Dado extra útil
        ];

        $accessToken = JWT::gerar($payloadAcesso, $config['segredo']);

        // ── Refresh Token (string aleatória criptograficamente segura) ────
        // bin2hex(random_bytes(64)) gera 128 caracteres hexadecimais
        $refreshToken = bin2hex(random_bytes(64));
        $dataExpiracao = date('Y-m-d H:i:s', $agora + $config['expiracao_refresh']);

        // Persiste o refresh token no banco para controle
        $this->usuarioModel->salvarRefreshToken(
            (int) $usuario['id'],
            $refreshToken,
            $dataExpiracao
        );

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => $config['expiracao_acesso'], // Segundos até o access token expirar
        ];
    }
}
