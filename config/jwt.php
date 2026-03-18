<?php

/**
 * Configuração dos Tokens JWT
 *
 * expiracao_acesso  = vida útil do access token em segundos
 *                     Padrão: 900 = 15 minutos
 *                     Curto prazo: se roubado, expira rápido
 *
 * expiracao_refresh = vida útil do refresh token em segundos
 *                     Padrão: 604800 = 7 dias
 *                     Longo prazo: armazenado no banco, pode ser revogado
 *
 * segredo           = chave secreta para assinar os JWTs
 *                     IMPORTANTE: troque por uma string longa e aleatória em produção!
 *                     Sugestão: use um gerador de senha com 64+ caracteres
 */
return [
    'segredo'           => Env::get('JWT_SECRET', 'troque_esta_chave_em_producao_use_algo_longo_e_aleatorio'),
    'expiracao_acesso'  => (int) Env::get('JWT_ACCESS_EXPIRY',  '900'),
    'expiracao_refresh' => (int) Env::get('JWT_REFRESH_EXPIRY', '604800'),
];
