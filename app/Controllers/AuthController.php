<?php

/**
 * Controller de Autenticação
 *
 * Responsável por receber as requisições HTTP de autenticação,
 * delegar ao AuthService e montar a resposta JSON.
 *
 * Responsabilidades do Controller:
 *   ✓ Ler os dados da requisição (corpo, parâmetros)
 *   ✓ Validar campos obrigatórios (validação de formato/negócio fica no Service)
 *   ✓ Chamar o Service correspondente
 *   ✓ Retornar a resposta HTTP com o código correto
 *   ✗ NÃO contém lógica de negócio (quem verifica a senha é o Service)
 *   ✗ NÃO acessa o banco diretamente (isso é responsabilidade do Model)
 */
class AuthController
{
    /** @var AuthService Lógica de autenticação */
    private $authService;

    public function __construct()
    {
        // Instancia o Service com sua dependência (Model)
        // Em projetos maiores, isso seria feito por um Container de Injeção de Dependência
        $this->authService = new AuthService(new UsuarioModel());
    }

    /**
     * POST /auth/login
     *
     * Recebe usuário e senha, valida credenciais e retorna os tokens.
     *
     * Corpo esperado (JSON):
     *   { "usuario": "admin", "senha": "123456" }
     *
     * Respostas:
     *   200 → login OK, retorna access_token e refresh_token
     *   400 → campos obrigatórios não informados
     *   401 → credenciais incorretas
     */
    public function login(Request $request, Response $response): void
    {
        $corpo = $request->getCorpo();

        // Valida presença dos campos obrigatórios
        if (empty($corpo['usuario']) || empty($corpo['senha'])) {
            $response->json([
                'status'  => 'error',
                'message' => 'Os campos usuario e senha são obrigatórios.',
            ], 400);
            return;
        }

        try {
            $tokens = $this->authService->login($corpo['usuario'], $corpo['senha']);

            $response->json([
                'status' => 'success',
                'data'   => $tokens,
            ]);

        } catch (InvalidArgumentException $e) {
            $response->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * POST /auth/refresh
     *
     * Recebe o refresh token e retorna um novo par de tokens.
     * O refresh token antigo é invalidado (Refresh Token Rotation).
     *
     * Corpo esperado (JSON):
     *   { "refresh_token": "abc123..." }
     *
     * Respostas:
     *   200 → novos tokens gerados
     *   400 → refresh_token não informado
     *   401 → refresh token inválido ou expirado
     */
    public function refresh(Request $request, Response $response): void
    {
        $corpo = $request->getCorpo();

        if (empty($corpo['refresh_token'])) {
            $response->json([
                'status'  => 'error',
                'message' => 'O campo refresh_token é obrigatório.',
            ], 400);
            return;
        }

        try {
            $tokens = $this->authService->renovarTokens($corpo['refresh_token']);

            $response->json([
                'status' => 'success',
                'data'   => $tokens,
            ]);

        } catch (InvalidArgumentException $e) {
            $response->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 401);
        } catch (RuntimeException $e) {
            $response->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 401);
        }
    }
}
