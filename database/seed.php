<?php

/**
 * Seed — Popula o banco de dados com dados iniciais para testes.
 *
 * Execute na linha de comando a partir da raiz do projeto:
 *   php database/seed.php
 *
 * O que este script faz:
 *   1. Cria o usuário de teste: admin / 123456
 *   2. Insere 4 clientes de exemplo
 */

// Carrega o Env manualmente (sem autoloader aqui)
require_once __DIR__ . '/../app/Core/Env.php';
Env::carregar(__DIR__ . '/../.env');

$config = require __DIR__ . '/../config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $config['host'],
    $config['port'],
    $config['dbname']
);

try {
    $pdo = new PDO($dsn, $config['usuario'], $config['senha'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✓ Conectado ao banco '{$config['dbname']}'\n\n";

    // ── Usuário de teste ──────────────────────────────────────────────────
    // password_hash() gera um hash bcrypt seguro da senha
    $senhaHash = password_hash('123456', PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO usuarios (usuario, senha) VALUES (?, ?)'
    );
    $stmt->execute(['admin', $senhaHash]);

    echo "Usuário criado (ou já existia):\n";
    echo "  Login:  admin\n";
    echo "  Senha:  123456\n\n";

    // ── Clientes de exemplo ───────────────────────────────────────────────
    // CPFs válidos gerados para teste (algoritmo verificado)
    $clientes = [
        ['529.982.247-25', 'Maria da Silva',  '1985-03-10', '(11) 98765-4321', 'maria@email.com'],
        ['275.084.900-02', 'João Pereira',    '1992-07-22', '(41) 99111-2233', 'joao@email.com'],
        ['060.860.200-09', 'Ana Souza',       '2000-01-15', null,              'ana@email.com'],
        ['321.666.520-85', 'Carlos Oliveira', '1978-11-30', '(21) 97654-3210', null],
    ];

    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO clientes (cpf, nome, data_nascimento, whatsapp, email)
         VALUES (?, ?, ?, ?, ?)'
    );

    echo "Clientes inseridos:\n";
    foreach ($clientes as $cliente) {
        $stmt->execute($cliente);
        echo "  ✓ {$cliente[1]} ({$cliente[0]})\n";
    }

    echo "\n✓ Seed executado com sucesso!\n";
    echo "\nAgora você pode testar:\n";
    echo "  POST /auth/login  →  {\"usuario\": \"admin\", \"senha\": \"123456\"}\n";

} catch (PDOException $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    echo "\nVerifique se:\n";
    echo "  1. O MySQL está rodando (XAMPP Control Panel)\n";
    echo "  2. Você executou o arquivo database/migration.sql\n";
    echo "  3. As configurações no .env estão corretas\n";
    exit(1);
}
