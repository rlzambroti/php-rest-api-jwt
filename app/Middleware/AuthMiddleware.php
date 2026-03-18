<?php

/**
 * Middleware de Autenticação JWT
 *
 * Um Middleware é um "interceptador" — ele fica entre a requisição e o Controller,
 * podendo bloquear ou modificar a requisição antes de chegar ao destino.
 *
 * Este middleware:
 *   1. Lê o header "Authorization: Bearer <token>"
 *   2. Valida o access token JWT
 *   3. Se válido → injeta os dados do usuário na requisição e retorna true
 *   4. Se inválido → prepara resposta 401 e retorna false (bloqueia o controller)
 *
 * O Router verifica o retorno e só chama o Controller se for true.
 */
class AuthMiddleware
{
    /**
     * Processa a autenticação da requisição.
     *
     * @param  Request  $request  Objeto da requisição HTTP
     * @param  Response $response Objeto da resposta HTTP
     * @return bool     true para continuar, false para bloquear
     */
    public function processar(Request $request, Response $response): bool
    {
        // ── 1. Lê o header Authorization ─────────────────────────────────
        $authHeader = $request->getHeader('Authorization');

        if ($authHeader === null || strpos($authHeader, 'Bearer ') !== 0) {
            $response->json([
                'status'  => 'error',
                'message' => 'Token de acesso não informado. Use o header: Authorization: Bearer {token}',
            ], 401);
            return false;
        }

        // Remove o prefixo "Bearer " (7 caracteres) para obter só o token
        $token = substr($authHeader, 7);

        if ($token === '') {
            $response->json([
                'status'  => 'error',
                'message' => 'Token de acesso vazio.',
            ], 401);
            return false;
        }

        // ── 2. Valida o token JWT ─────────────────────────────────────────
        $config = require __DIR__ . '/../../config/jwt.php';

        try {
            $payload = JWT::validar($token, $config['segredo']);

            // ── 3. Injeta os dados do usuário na requisição ───────────────
            // O Controller pode recuperar com: $request->getAtributo('usuario')
            $request->setAtributo('usuario', $payload);

            return true; // Libera para o Controller

        } catch (RuntimeException $e) {
            // Token expirado
            $response->json([
                'status'  => 'error',
                'message' => 'Access token expirado. Chame POST /auth/refresh para renovar.',
            ], 401);
            return false;

        } catch (InvalidArgumentException $e) {
            // Token inválido ou adulterado
            $response->json([
                'status'  => 'error',
                'message' => 'Token inválido: ' . $e->getMessage(),
            ], 401);
            return false;
        }
    }
}
