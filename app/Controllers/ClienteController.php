<?php

/**
 * Controller de Clientes
 *
 * Gerencia os endpoints CRUD para a entidade Cliente.
 * Todas as rotas deste controller passam pelo AuthMiddleware (exigem JWT).
 *
 * Endpoints:
 *   GET    /clientes        → listar()
 *   POST   /clientes        → criar()
 *   GET    /clientes/{id}   → buscar()
 *   PUT    /clientes/{id}   → atualizar()
 *   DELETE /clientes/{id}   → excluir()
 */
class ClienteController
{
    /** @var ClienteService Lógica de negócio dos clientes */
    private $clienteService;

    public function __construct()
    {
        $this->clienteService = new ClienteService(new ClienteModel());
    }

    /**
     * GET /clientes
     *
     * Retorna todos os clientes cadastrados.
     *
     * Respostas:
     *   200 → lista de clientes (pode ser vazia)
     *   401 → não autenticado
     */
    public function listar(Request $request, Response $response): void
    {
        $clientes = $this->clienteService->listar();

        $response->json([
            'status' => 'success',
            'total'  => count($clientes),
            'data'   => $clientes,
        ]);
    }

    /**
     * GET /clientes/{id}
     *
     * Retorna um cliente específico pelo ID.
     *
     * Parâmetros de rota:
     *   {id} → ID numérico do cliente
     *
     * Respostas:
     *   200 → dados do cliente
     *   401 → não autenticado
     *   404 → cliente não encontrado
     */
    public function buscar(Request $request, Response $response): void
    {
        $id = (int) $request->getParam('id');

        try {
            $cliente = $this->clienteService->buscar($id);

            $response->json([
                'status' => 'success',
                'data'   => $cliente,
            ]);

        } catch (RuntimeException $e) {
            $codigo = $e->getCode() ?: 404;
            $response->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], (int) $codigo);
        }
    }

    /**
     * POST /clientes
     *
     * Cria um novo cliente.
     *
     * Corpo esperado (JSON):
     *   {
     *     "cpf":             "123.456.789-09",   (obrigatório)
     *     "nome":            "João da Silva",     (obrigatório)
     *     "data_nascimento": "1990-05-15",        (obrigatório, YYYY-MM-DD)
     *     "whatsapp":        "(11) 99999-9999",   (opcional)
     *     "email":           "joao@email.com"     (opcional)
     *   }
     *
     * Respostas:
     *   201 → cliente criado
     *   400 → dados inválidos
     *   401 → não autenticado
     *   409 → CPF já cadastrado
     */
    public function criar(Request $request, Response $response): void
    {
        $dados = $request->getCorpo();

        try {
            $cliente = $this->clienteService->criar($dados);

            $response->json([
                'status'  => 'success',
                'message' => 'Cliente criado com sucesso.',
                'data'    => $cliente,
            ], 201);

        } catch (InvalidArgumentException $e) {
            $response->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400);

        } catch (RuntimeException $e) {
            $codigo = $e->getCode() ?: 422;
            $response->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], (int) $codigo);
        }
    }

    /**
     * PUT /clientes/{id}
     *
     * Atualiza todos os dados de um cliente.
     *
     * Parâmetros de rota:
     *   {id} → ID do cliente a atualizar
     *
     * Corpo esperado: mesmo formato do POST /clientes
     *
     * Respostas:
     *   200 → cliente atualizado
     *   400 → dados inválidos
     *   401 → não autenticado
     *   404 → cliente não encontrado
     *   409 → CPF já pertence a outro cliente
     */
    public function atualizar(Request $request, Response $response): void
    {
        $id    = (int) $request->getParam('id');
        $dados = $request->getCorpo();

        try {
            $cliente = $this->clienteService->atualizar($id, $dados);

            $response->json([
                'status'  => 'success',
                'message' => 'Cliente atualizado com sucesso.',
                'data'    => $cliente,
            ]);

        } catch (InvalidArgumentException $e) {
            $response->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400);

        } catch (RuntimeException $e) {
            $codigo = $e->getCode() ?: 422;
            $response->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], (int) $codigo);
        }
    }

    /**
     * DELETE /clientes/{id}
     *
     * Remove um cliente pelo ID.
     *
     * Parâmetros de rota:
     *   {id} → ID do cliente a remover
     *
     * Respostas:
     *   200 → cliente removido
     *   401 → não autenticado
     *   404 → cliente não encontrado
     */
    public function excluir(Request $request, Response $response): void
    {
        $id = (int) $request->getParam('id');

        try {
            $this->clienteService->excluir($id);

            $response->json([
                'status'  => 'success',
                'message' => 'Cliente excluído com sucesso.',
            ]);

        } catch (RuntimeException $e) {
            $codigo = $e->getCode() ?: 404;
            $response->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], (int) $codigo);
        }
    }
}
