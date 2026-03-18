<?php

/**
 * Encapsulamento da Resposta HTTP
 *
 * Esta classe centraliza a construção e envio de respostas HTTP.
 * Ao invés de chamar header() e echo espalhados pelo código,
 * usamos esta classe para ter um ponto único de controle.
 *
 * Padrão de uso nos Controllers:
 *   $response->json(['status' => 'success', 'data' => $dados]);
 *   $response->json(['status' => 'error', 'message' => 'Não encontrado.'], 404);
 */
class Response
{
    /** @var int Código de status HTTP (200, 201, 400, 401, 404, 500...) */
    private int $status = 200;

    /** @var array Headers HTTP a enviar */
    private array $headers = [];

    /** @var string Corpo da resposta */
    private string $corpo = '';

    /** @var bool Evita enviar a resposta mais de uma vez */
    private bool $enviada = false;

    /**
     * Prepara uma resposta JSON.
     *
     * Converte o array para JSON e define o Content-Type adequado.
     * A resposta só é enviada ao cliente quando send() for chamado.
     *
     * @param array $dados  Dados a serializar como JSON
     * @param int   $status Código HTTP (padrão: 200 OK)
     * @return self Retorna $this para encadeamento (fluent interface)
     */
    public function json(array $dados, int $status = 200): self
    {
        $this->status = $status;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        $this->corpo = json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $this;
    }

    /**
     * Prepara uma resposta HTML (usado para servir o Swagger UI).
     *
     * @param string $conteudo HTML a enviar
     * @param int    $status   Código HTTP (padrão: 200 OK)
     * @return self
     */
    public function html(string $conteudo, int $status = 200): self
    {
        $this->status = $status;
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->corpo = $conteudo;

        return $this;
    }

    /**
     * Envia a resposta para o cliente (headers + corpo).
     *
     * Este método é chamado pelo Router após o Controller e
     * os Middlewares terem preparado a resposta.
     */
    public function send(): void
    {
        if ($this->enviada) {
            return; // Garante que a resposta é enviada apenas uma vez
        }

        $this->enviada = true;

        // Define o código de status HTTP
        http_response_code($this->status);

        // Envia os headers
        foreach ($this->headers as $nome => $valor) {
            header("{$nome}: {$valor}");
        }

        // Envia o corpo da resposta
        echo $this->corpo;
    }

    /**
     * Verifica se a resposta já foi preparada (usado pelo Router
     * para saber se o Middleware já definiu uma resposta de erro).
     */
    public function foiPreparada(): bool
    {
        return $this->corpo !== '';
    }
}
