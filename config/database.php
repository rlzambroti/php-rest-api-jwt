<?php

/**
 * Configuração do Banco de Dados
 *
 * Os valores são lidos do arquivo .env para manter as credenciais
 * fora do código-fonte (boa prática de segurança).
 *
 * Valores padrão (fallback) são usados quando a variável não está definida.
 * São adequados para desenvolvimento local com XAMPP.
 */
return [
    'host'    => Env::get('DB_HOST', 'localhost'),
    'port'    => Env::get('DB_PORT', '3306'),
    'dbname'  => Env::get('DB_NAME', 'api_academica'),
    'usuario' => Env::get('DB_USER', 'root'),
    'senha'   => Env::get('DB_PASS', 'root'),
];
