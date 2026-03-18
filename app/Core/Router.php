<?php

/**
 * Roteador HTTP
 *
 * O Router é o "controlador de tráfego" da API.
 * Ele mapeia cada URL + Método HTTP para um Controller específico.
 *
 * Funcionamento:
 *   1. Registramos rotas com get(), post(), put(), delete()
 *   2. Quando uma requisição chega, dispatch() percorre as rotas
 *   3. Compara método e caminho com cada rota registrada
 *   4. Se encontrar, executa os middlewares e depois o controller
 *   5. Se não encontrar, retorna 404
 *
 * Suporte a parâmetros de rota:
 *   Rota: /clientes/{id}
 *   URL:  /clientes/42
 *   Resultado: $request->getParam('id') === '42'
 */
class Router
{
    /**
     * Lista de rotas registradas.
     * Cada rota é um array com: metodo, caminho, handler, middlewares, padrao.
     *
     * @var array
     */
    private array $rotas = [];

    // ── Métodos de registro de rotas ──────────────────────────────────────

    /**
     * Registra uma rota para o método GET.
     *
     * @param string         $caminho     Caminho da rota (ex.: /clientes/{id})
     * @param callable|array $handler     Controller: ['NomeClasse', 'nomeMetodo'] ou Closure
     * @param array          $middlewares Lista de classes de middleware a executar
     */
    public function get(string $caminho, $handler, array $middlewares = []): void
    {
        $this->registrar('GET', $caminho, $handler, $middlewares);
    }

    /**
     * Registra uma rota para o método POST.
     */
    public function post(string $caminho, $handler, array $middlewares = []): void
    {
        $this->registrar('POST', $caminho, $handler, $middlewares);
    }

    /**
     * Registra uma rota para o método PUT.
     */
    public function put(string $caminho, $handler, array $middlewares = []): void
    {
        $this->registrar('PUT', $caminho, $handler, $middlewares);
    }

    /**
     * Registra uma rota para o método DELETE.
     */
    public function delete(string $caminho, $handler, array $middlewares = []): void
    {
        $this->registrar('DELETE', $caminho, $handler, $middlewares);
    }

    // ── Despacho da requisição ────────────────────────────────────────────

    /**
     * Encontra a rota correspondente à requisição e a executa.
     *
     * Fluxo:
     *   Requisição → Middleware(s) → Controller → send()
     *
     * @param Request  $request  Objeto com dados da requisição HTTP
     * @param Response $response Objeto para construir a resposta HTTP
     */
    public function despachar(Request $request, Response $response): void
    {
        $metodoRequisicao = $request->getMetodo();
        $caminhoRequisicao = $request->getCaminho();

        foreach ($this->rotas as $rota) {
            // Verifica se o método HTTP bate
            if ($rota['metodo'] !== $metodoRequisicao) {
                continue;
            }

            // Verifica se o caminho bate com o padrão regex da rota
            $parametros = [];
            if (!preg_match($rota['padrao'], $caminhoRequisicao, $parametros)) {
                continue;
            }

            // Extrai apenas os parâmetros nomeados (ex.: 'id' → '42')
            // preg_match() retorna índices numéricos E nomeados, filtramos os numéricos
            $paramsRota = array_filter(
                $parametros,
                function ($chave) {
                    return !is_numeric($chave);
                },
                ARRAY_FILTER_USE_KEY
            );

            $request->setParametrosRota($paramsRota);

            // ── Executa os middlewares em ordem ───────────────────────────
            // Se algum middleware retornar false, a execução para
            foreach ($rota['middlewares'] as $classeMiddleware) {
                $middleware = new $classeMiddleware();

                if (!$middleware->processar($request, $response)) {
                    // Middleware bloqueou — envia a resposta de erro e para
                    $response->send();
                    return;
                }
            }

            // ── Executa o handler (Controller ou Closure) ─────────────────
            $handler = $rota['handler'];

            if (is_array($handler)) {
                // Formato: ['NomeDoController', 'nomeDoMetodo']
                [$classe, $metodo] = $handler;
                $controller = new $classe();
                $controller->$metodo($request, $response);
            } elseif (is_callable($handler)) {
                // Formato: function(Request $req, Response $res) { ... }
                $handler($request, $response);
            }

            $response->send();
            return;
        }

        // Nenhuma rota encontrada → 404 Not Found
        $response->json([
            'status'  => 'error',
            'message' => 'Rota não encontrada.',
        ], 404);
        $response->send();
    }

    // ── Método privado de registro ────────────────────────────────────────

    /**
     * Registra internamente uma rota, convertendo o caminho em regex.
     *
     * Conversão de parâmetros:
     *   {id}    → (?P<id>[^/]+)   (captura qualquer coisa exceto '/')
     *   {nome}  → (?P<nome>[^/]+)
     */
    private function registrar(string $metodo, string $caminho, $handler, array $middlewares): void
    {
        // Converte {parametro} em grupos de captura nomeados do regex
        $padrao = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $caminho);

        // Adiciona delimitadores e ancora início/fim da string
        $padrao = '#^' . $padrao . '$#';

        $this->rotas[] = [
            'metodo'      => strtoupper($metodo),
            'caminho'     => $caminho,
            'handler'     => $handler,
            'middlewares' => $middlewares,
            'padrao'      => $padrao,
        ];
    }
}
