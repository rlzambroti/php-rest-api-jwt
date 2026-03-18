<?php

/**
 * ╔══════════════════════════════════════════════════════════════════════════╗
 * ║              PONTO DE ENTRADA DA APLICAÇÃO (Front Controller)           ║
 * ╚══════════════════════════════════════════════════════════════════════════╝
 *
 * TODA requisição HTTP passa por este arquivo.
 * O Apache (via .htaccess) redireciona qualquer URL para cá.
 *
 * O que acontece aqui:
 *   1. Configura o PHP para exibir/ocultar erros conforme o ambiente
 *   2. Registra o autoloader (carrega classes automaticamente)
 *   3. Carrega as variáveis de ambiente (.env)
 *   4. Cria o Router e registra as rotas
 *   5. Cria os objetos Request e Response
 *   6. Despacha a requisição para o Controller correto
 */

// ── 1. Configuração de erros ───────────────────────────────────────────────
// Em desenvolvimento, exibe todos os erros na tela
// Em produção, troque para: error_reporting(0); ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ── 2. Autoloader PSR-4 ───────────────────────────────────────────────────
// Carrega automaticamente as classes sem precisar de require/include manuais.
//
// Convenção PSR-4:
//   Nome da classe:  AuthController
//   Arquivo:         app/Controllers/AuthController.php
//
// Como funciona:
//   spl_autoload_register() registra uma função que é chamada
//   automaticamente quando uma classe é usada mas ainda não foi carregada.
spl_autoload_register(function (string $classe): void {
    // Mapa de diretórios por "namespace" (prefixo do nome da classe)
    $mapa = [
        'Controllers' => __DIR__ . '/../app/Controllers/',
        'Services'    => __DIR__ . '/../app/Services/',
        'Models'      => __DIR__ . '/../app/Models/',
        'Middleware'  => __DIR__ . '/../app/Middleware/',
        'Database'    => __DIR__ . '/../app/Database/',
        'Core'        => __DIR__ . '/../app/Core/',
    ];

    // Tenta localizar o arquivo da classe em cada diretório
    foreach ($mapa as $diretorio) {
        $arquivo = $diretorio . $classe . '.php';
        if (file_exists($arquivo)) {
            require_once $arquivo;
            return;
        }
    }
});

// Carrega as classes do Core explicitamente (são utilitários sem namespace)
require_once __DIR__ . '/../app/Core/Env.php';
require_once __DIR__ . '/../app/Core/JWT.php';
require_once __DIR__ . '/../app/Core/Request.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/Router.php';

// ── 3. Variáveis de ambiente ───────────────────────────────────────────────
// Lê o arquivo .env da raiz do projeto
Env::carregar(__DIR__ . '/../.env');

// ── 4. Cabeçalhos CORS (Cross-Origin Resource Sharing) ────────────────────
// Permite que frontends em outros domínios consumam esta API.
// Em produção, substitua '*' pelo domínio específico do seu frontend.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight: navegadores enviam OPTIONS antes de requisições com credenciais
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── 5. Handler global de exceções não capturadas ──────────────────────────
// Garante que qualquer erro inesperado retorne JSON limpo (nunca HTML).
set_exception_handler(function (Throwable $e): void {
    // Limpa qualquer saída parcial que possa ter sido gerada
    if (ob_get_level() > 0) {
        ob_clean();
    }

    $ambiente = Env::get('APP_ENV', 'production');

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    $corpo = [
        'status'  => 'error',
        'message' => 'Erro interno no servidor.',
    ];

    // Em desenvolvimento, expõe detalhes para facilitar o debug
    if ($ambiente === 'development') {
        $corpo['debug'] = [
            'erro'    => get_class($e) . ': ' . $e->getMessage(),
            'arquivo' => $e->getFile() . ':' . $e->getLine(),
        ];
    }

    echo json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
});

// ── 6. Roteador ───────────────────────────────────────────────────────────
$router = new Router();

// Carrega as definições de rotas (Controllers + Middlewares)
require __DIR__ . '/../routes/api.php';

// ── 7. Requisição e Resposta ──────────────────────────────────────────────
$request  = new Request();
$response = new Response();

// ── 8. Despacha! ──────────────────────────────────────────────────────────
// O Router encontra a rota, executa middlewares e chama o Controller
$router->despachar($request, $response);
