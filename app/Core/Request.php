<?php

/**
 * Encapsulamento da Requisição HTTP
 *
 * Esta classe abstrai o acesso aos dados da requisição HTTP,
 * centralizando a leitura de $_SERVER, $_GET, $_POST e php://input.
 *
 * Por que usar esta classe em vez de acessar $_SERVER diretamente?
 *   - Código mais legível: $request->getMethod() vs $_SERVER['REQUEST_METHOD']
 *   - Fácil de testar: podemos criar um Request "falso" nos testes
 *   - Encapsula a lógica de parsing do corpo JSON
 */
class Request
{
    /** @var string Método HTTP: GET, POST, PUT, DELETE */
    private string $metodo;

    /** @var string Caminho da URL sem a base (ex.: /clientes/5) */
    private string $caminho;

    /** @var array Parâmetros de rota extraídos da URL (ex.: {id} → ['id' => '5']) */
    private array $parametrosRota = [];

    /** @var array Atributos extras definidos pelos middlewares */
    private array $atributos = [];

    /** @var array|null Corpo da requisição (JSON decodificado) */
    private ?array $corpo = null;

    /** @var bool Controla se o corpo já foi lido (lazy loading) */
    private bool $corpoParsed = false;

    public function __construct()
    {
        $this->metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->caminho = $this->extrairCaminho();
    }

    /**
     * Retorna o método HTTP da requisição (GET, POST, PUT, DELETE...).
     */
    public function getMetodo(): string
    {
        return $this->metodo;
    }

    /**
     * Retorna o caminho da URL (sem query string e sem base path).
     * Exemplo: "/clientes/5"
     */
    public function getCaminho(): string
    {
        return $this->caminho;
    }

    /**
     * Retorna o valor de um header HTTP.
     * Exemplo: $request->getHeader('Authorization')
     *
     * @param string $nome Nome do header (case-insensitive)
     * @return string|null Valor do header ou null se não existir
     */
    public function getHeader(string $nome): ?string
    {
        // getallheaders() retorna os headers com capitalização original
        $headers = getallheaders();

        // Busca ignorando maiúsculas/minúsculas
        foreach ($headers as $chave => $valor) {
            if (strcasecmp($chave, $nome) === 0) {
                return $valor;
            }
        }

        return null;
    }

    /**
     * Retorna o corpo da requisição decodificado como array.
     *
     * Funciona com:
     *   - application/json → decodifica o JSON de php://input
     *   - application/x-www-form-urlencoded → usa $_POST
     *
     * @return array Dados do corpo (array vazio se não houver corpo)
     */
    public function getCorpo(): array
    {
        if ($this->corpoParsed) {
            return $this->corpo ?? [];
        }

        $this->corpoParsed = true;
        $contentType = $this->getHeader('Content-Type') ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            // Lê o corpo bruto e decodifica como JSON
            $bruto = file_get_contents('php://input');
            $dados = json_decode($bruto, true);
            $this->corpo = is_array($dados) ? $dados : [];
        } else {
            // Formulário tradicional (form-data ou urlencoded)
            $this->corpo = $_POST ?: [];
        }

        return $this->corpo;
    }

    /**
     * Retorna um parâmetro de rota pelo nome.
     * Exemplo: para a rota /clientes/{id}, $request->getParam('id') retorna o ID.
     *
     * @param string $nome  Nome do parâmetro
     * @param mixed  $padrao Valor padrão se não existir
     * @return mixed
     */
    public function getParam(string $nome, $padrao = null)
    {
        return $this->parametrosRota[$nome] ?? $padrao;
    }

    /**
     * Define os parâmetros de rota (chamado pelo Router após encontrar a rota).
     *
     * @param array $params Ex.: ['id' => '5']
     */
    public function setParametrosRota(array $params): void
    {
        $this->parametrosRota = $params;
    }

    /**
     * Retorna um atributo definido por um middleware.
     * Usado para passar dados entre middlewares e controllers.
     *
     * Exemplo: AuthMiddleware define 'usuario_autenticado', controller lê.
     *
     * @param string $nome  Nome do atributo
     * @param mixed  $padrao Valor padrão se não existir
     * @return mixed
     */
    public function getAtributo(string $nome, $padrao = null)
    {
        return $this->atributos[$nome] ?? $padrao;
    }

    /**
     * Define um atributo na requisição (geralmente usado por middlewares).
     *
     * @param string $nome  Nome do atributo
     * @param mixed  $valor Valor a armazenar
     */
    public function setAtributo(string $nome, $valor): void
    {
        $this->atributos[$nome] = $valor;
    }

    /**
     * Extrai o caminho limpo da URL atual, removendo query string e base path.
     *
     * Problema: REQUEST_URI retorna o caminho completo, ex.: /a/v2/public/clientes
     * As rotas estão registradas sem o prefixo, ex.: /clientes
     *
     * Solução: detectar o diretório do script (base path) e removê-lo da URI.
     *   SCRIPT_NAME = /a/v2/public/index.php
     *   base path   = /a/v2/public
     *   REQUEST_URI = /a/v2/public/clientes
     *   caminho     = /clientes  ← o que o Router precisa
     */
    private function extrairCaminho(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove a query string (tudo após o '?')
        $posicao = strpos($uri, '?');
        if ($posicao !== false) {
            $uri = substr($uri, 0, $posicao);
        }

        // Decodifica caracteres URL-encoded (ex.: %20 → espaço)
        $uri = urldecode($uri);

        // Detecta o base path: diretório onde o index.php está instalado
        // Ex.: /a/v2/public/index.php → base path = /a/v2/public
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');

        // Remove o base path do início da URI (se presente)
        if ($scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }

        // Garante que começa com '/'
        if ($uri === '' || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        // Remove trailing slash, exceto para a raiz "/"
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        return $uri;
    }
}
