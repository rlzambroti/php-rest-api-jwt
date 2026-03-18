<?php

/**
 * Implementação manual de JWT (JSON Web Token)
 *
 * JWT é um padrão aberto (RFC 7519) para transmitir informações de forma
 * segura entre partes como um objeto JSON assinado digitalmente.
 *
 * Estrutura de um JWT:
 *   HEADER.PAYLOAD.SIGNATURE
 *
 * Cada parte é codificada em Base64Url e separada por ponto.
 *
 *   Header   → Tipo do token e algoritmo usado
 *   Payload  → Dados (claims) — quem é o usuário, quando expira etc.
 *   Signature → Garante que o token não foi adulterado
 *
 * Esta implementação usa o algoritmo HS256 (HMAC com SHA-256).
 * A segurança depende do segredo (secret) — nunca o exponha!
 */
class JWT
{
    /**
     * Gera um token JWT assinado.
     *
     * @param array  $payload Dados a incluir no token (ex.: id, usuario, exp)
     * @param string $segredo Chave secreta para assinar o token
     * @return string Token JWT no formato HEADER.PAYLOAD.SIGNATURE
     */
    public static function gerar(array $payload, string $segredo): string
    {
        // ── Parte 1: Header ──────────────────────────────────────────────
        // Define o tipo do token e o algoritmo de assinatura
        $header = self::base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ]));

        // ── Parte 2: Payload ─────────────────────────────────────────────
        // Contém os dados (claims) — não armazene senhas aqui!
        $payload = self::base64UrlEncode(json_encode($payload));

        // ── Parte 3: Signature ───────────────────────────────────────────
        // HMAC-SHA256 garante que ninguém alterou o conteúdo sem o segredo
        $dadosParaAssinar = "{$header}.{$payload}";
        $assinatura = self::base64UrlEncode(
            hash_hmac('sha256', $dadosParaAssinar, $segredo, true)
        );

        return "{$header}.{$payload}.{$assinatura}";
    }

    /**
     * Decodifica e valida um token JWT.
     *
     * Verifica:
     *   1. Estrutura do token (3 partes separadas por ponto)
     *   2. Assinatura (se foi gerado com o mesmo segredo)
     *   3. Expiração (claim 'exp')
     *
     * @param string $token   Token JWT recebido do cliente
     * @param string $segredo Chave secreta usada para validar
     * @return array Payload decodificado
     *
     * @throws InvalidArgumentException Se o token for inválido ou adulterado
     * @throws RuntimeException         Se o token estiver expirado
     */
    public static function validar(string $token, string $segredo): array
    {
        // Divide o token nas 3 partes
        $partes = explode('.', $token);

        if (count($partes) !== 3) {
            throw new InvalidArgumentException('Token JWT com formato inválido.');
        }

        [$header, $payload, $assinaturaRecebida] = $partes;

        // Recalcula a assinatura esperada
        $dadosParaAssinar      = "{$header}.{$payload}";
        $assinaturaEsperada    = self::base64UrlEncode(
            hash_hmac('sha256', $dadosParaAssinar, $segredo, true)
        );

        // hash_equals() evita timing attacks (comparação em tempo constante)
        if (!hash_equals($assinaturaEsperada, $assinaturaRecebida)) {
            throw new InvalidArgumentException('Assinatura do token JWT inválida.');
        }

        // Decodifica o payload
        $dados = json_decode(self::base64UrlDecode($payload), true);

        if (!is_array($dados)) {
            throw new InvalidArgumentException('Payload do JWT inválido.');
        }

        // Verifica se o token está expirado
        if (isset($dados['exp']) && $dados['exp'] < time()) {
            throw new RuntimeException('Token JWT expirado.');
        }

        return $dados;
    }

    /**
     * Codifica dados em Base64Url (variante do Base64 segura para URLs).
     *
     * Diferenças do Base64 padrão:
     *   + → -
     *   / → _
     *   Remove o padding '='
     */
    private static function base64UrlEncode(string $dados): string
    {
        return rtrim(strtr(base64_encode($dados), '+/', '-_'), '=');
    }

    /**
     * Decodifica dados em Base64Url de volta para string.
     */
    private static function base64UrlDecode(string $dados): string
    {
        // Restaura o padding removido (Base64 precisa de múltiplos de 4)
        $resto = strlen($dados) % 4;
        if ($resto !== 0) {
            $dados .= str_repeat('=', 4 - $resto);
        }

        return base64_decode(strtr($dados, '-_', '+/'));
    }
}
