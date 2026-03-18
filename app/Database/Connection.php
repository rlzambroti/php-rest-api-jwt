<?php

/**
 * Conexão com o Banco de Dados (Padrão Singleton)
 *
 * O padrão Singleton garante que exista apenas UMA instância da conexão
 * PDO durante toda a execução da aplicação.
 *
 * Por que Singleton aqui?
 *   - Abrir uma conexão com o banco é custoso (tempo + memória)
 *   - Se cada Model criasse sua própria conexão, teríamos dezenas delas
 *   - Com Singleton, todos os Models compartilham a mesma conexão
 *
 * Por que PDO?
 *   - Abstrai o banco de dados (funciona com MySQL, PostgreSQL, SQLite...)
 *   - Suporta Prepared Statements nativamente (proteção contra SQL Injection)
 *   - Interface orientada a objetos
 */
class Connection
{
    /**
     * Instância única do PDO.
     * 'static' = pertence à classe, não a um objeto específico.
     * 'null'   = ainda não criada.
     *
     * @var PDO|null
     */
    private static $instancia = null;

    /**
     * Construtor privado — impede a criação de instâncias com 'new Connection()'.
     * Este é o ponto-chave do padrão Singleton.
     */
    private function __construct()
    {
    }

    /**
     * Retorna a instância única do PDO.
     *
     * Na primeira chamada: cria a conexão e armazena em $instancia.
     * Nas chamadas seguintes: retorna a conexão já criada.
     *
     * @throws RuntimeException Se não conseguir conectar
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instancia === null) {
            $config = require __DIR__ . '/../../config/database.php';

            // DSN = Data Source Name (string de conexão)
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['dbname']
            );

            try {
                self::$instancia = new PDO($dsn, $config['usuario'], $config['senha'], [
                    // Lança exceções em vez de retornar 'false' silenciosamente
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

                    // Retorna resultados como arrays associativos por padrão
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                    // Usa prepared statements reais (mais seguro contra SQL Injection)
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Registra o erro real no log do servidor (Apache error.log)
                error_log('Erro de conexão PDO: ' . $e->getMessage());

                // Em desenvolvimento, inclui a causa para facilitar o debug
                $detalhe = '';
                if (($_ENV['APP_ENV'] ?? '') === 'development') {
                    $detalhe = ' Causa: ' . $e->getMessage();
                }

                throw new RuntimeException(
                    'Não foi possível conectar ao banco de dados.' . $detalhe
                );
            }
        }

        return self::$instancia;
    }
}
