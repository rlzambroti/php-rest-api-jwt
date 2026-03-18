<?php

/**
 * Definição de Rotas da API
 *
 * Aqui registramos TODAS as rotas da aplicação no Router.
 * Este arquivo segue o princípio da Responsabilidade Única:
 * sua única função é mapear URLs para Controllers.
 *
 * Formato: $router->METODO('/caminho', ['Controller', 'metodo'], [middlewares]);
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │  Método  │  Endpoint            │  Controller          │  Auth?         │
 * ├─────────────────────────────────────────────────────────────────────────┤
 * │  POST    │  /auth/login         │  AuthController      │  Não           │
 * │  POST    │  /auth/refresh       │  AuthController      │  Não           │
 * │  GET     │  /clientes           │  ClienteController   │  Sim (JWT)     │
 * │  POST    │  /clientes           │  ClienteController   │  Sim (JWT)     │
 * │  GET     │  /clientes/{id}      │  ClienteController   │  Sim (JWT)     │
 * │  PUT     │  /clientes/{id}      │  ClienteController   │  Sim (JWT)     │
 * │  DELETE  │  /clientes/{id}      │  ClienteController   │  Sim (JWT)     │
 * │  GET     │  /docs               │  (closure)           │  Não           │
 * │  GET     │  /docs/openapi.json  │  (closure)           │  Não           │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * @var Router $router Instância do roteador (criada no index.php)
 */

// ── Autenticação (rotas públicas) ─────────────────────────────────────────
$router->post('/auth/login',   ['AuthController', 'login']);
$router->post('/auth/refresh', ['AuthController', 'refresh']);

// ── Clientes (rotas protegidas — JWT obrigatório) ─────────────────────────
$router->get('/clientes',         ['ClienteController', 'listar'],    ['AuthMiddleware']);
$router->post('/clientes',        ['ClienteController', 'criar'],     ['AuthMiddleware']);
$router->get('/clientes/{id}',    ['ClienteController', 'buscar'],    ['AuthMiddleware']);
$router->put('/clientes/{id}',    ['ClienteController', 'atualizar'], ['AuthMiddleware']);
$router->delete('/clientes/{id}', ['ClienteController', 'excluir'],  ['AuthMiddleware']);

// ── Documentação (rotas públicas) ─────────────────────────────────────────

// Swagger UI — interface visual para testar os endpoints
$router->get('/docs', function (Request $request, Response $response) {
    $arquivo = __DIR__ . '/../public/swagger-ui.html';
    $response->html(file_get_contents($arquivo));
});

// Especificação OpenAPI em JSON — consumida pelo Swagger UI
$router->get('/docs/openapi.json', function (Request $request, Response $response) {
    $arquivo = __DIR__ . '/../docs/openapi.json';

    if (!file_exists($arquivo)) {
        $response->json([
            'status'  => 'error',
            'message' => 'Arquivo docs/openapi.json não encontrado.',
        ], 404);
        return;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo file_get_contents($arquivo);
    exit;
});
