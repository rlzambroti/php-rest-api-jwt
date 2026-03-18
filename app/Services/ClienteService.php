<?php

/**
 * Service de Clientes
 *
 * Contém as regras de negócio para gerenciamento de clientes:
 *   - Validação dos campos obrigatórios
 *   - Validação do formato e dígitos do CPF
 *   - Verificação de CPF duplicado
 *
 * Regra de ouro: o Service não acessa o banco diretamente (usa o Model)
 * e não manipula HTTP (sem Request/Response).
 */
class ClienteService
{
    /** @var ClienteModel Responsável pelo acesso ao banco */
    private $clienteModel;

    public function __construct(ClienteModel $clienteModel)
    {
        $this->clienteModel = $clienteModel;
    }

    /**
     * Retorna a lista de todos os clientes.
     *
     * @return array Lista de clientes (pode ser vazia)
     */
    public function listar(): array
    {
        return $this->clienteModel->listarTodos();
    }

    /**
     * Busca um cliente pelo ID.
     *
     * @param  int   $id ID do cliente
     * @return array Dados do cliente
     *
     * @throws RuntimeException Se o cliente não existir (código 404)
     */
    public function buscar(int $id): array
    {
        $cliente = $this->clienteModel->buscarPorId($id);

        if ($cliente === null) {
            throw new RuntimeException("Cliente com ID {$id} não encontrado.", 404);
        }

        return $cliente;
    }

    /**
     * Cria um novo cliente após validar os dados.
     *
     * @param  array $dados Campos: cpf, nome, data_nascimento, whatsapp, email
     * @return array Cliente criado (com id e timestamps)
     *
     * @throws InvalidArgumentException Se os dados forem inválidos
     * @throws RuntimeException         Se o CPF já estiver cadastrado (código 409)
     */
    public function criar(array $dados): array
    {
        // 1. Valida os campos obrigatórios e formatos
        $this->validarDados($dados);

        // 2. Verifica se o CPF já existe no banco
        if ($this->clienteModel->buscarPorCpf($dados['cpf']) !== null) {
            throw new RuntimeException("CPF {$dados['cpf']} já está cadastrado.", 409);
        }

        // 3. Persiste no banco e retorna o registro completo
        $id = $this->clienteModel->criar($dados);

        return $this->clienteModel->buscarPorId($id);
    }

    /**
     * Atualiza os dados de um cliente existente.
     *
     * @param  int   $id    ID do cliente a atualizar
     * @param  array $dados Novos dados
     * @return array Cliente com dados atualizados
     *
     * @throws RuntimeException         Se o cliente não existir (404) ou CPF em uso (409)
     * @throws InvalidArgumentException Se os dados forem inválidos
     */
    public function atualizar(int $id, array $dados): array
    {
        // 1. Confirma que o cliente existe
        if ($this->clienteModel->buscarPorId($id) === null) {
            throw new RuntimeException("Cliente com ID {$id} não encontrado.", 404);
        }

        // 2. Valida os dados
        $this->validarDados($dados);

        // 3. Verifica CPF duplicado, mas ignora o próprio cliente
        $clienteComCpf = $this->clienteModel->buscarPorCpf($dados['cpf']);
        if ($clienteComCpf !== null && (int) $clienteComCpf['id'] !== $id) {
            throw new RuntimeException("CPF {$dados['cpf']} já está cadastrado por outro cliente.", 409);
        }

        // 4. Atualiza e retorna os dados atualizados
        $this->clienteModel->atualizar($id, $dados);

        return $this->clienteModel->buscarPorId($id);
    }

    /**
     * Remove um cliente pelo ID.
     *
     * @param  int  $id ID do cliente
     * @throws RuntimeException Se o cliente não existir (código 404)
     */
    public function excluir(int $id): void
    {
        if ($this->clienteModel->buscarPorId($id) === null) {
            throw new RuntimeException("Cliente com ID {$id} não encontrado.", 404);
        }

        $this->clienteModel->excluir($id);
    }

    // ── Métodos privados de validação ────────────────────────────────────────

    /**
     * Valida os dados de entrada do cliente.
     * Acumula todos os erros antes de lançar a exceção.
     *
     * @throws InvalidArgumentException Se houver campos inválidos
     */
    private function validarDados(array $dados): void
    {
        $erros = [];

        // CPF: obrigatório e válido (formato + dígitos verificadores)
        if (empty($dados['cpf'])) {
            $erros[] = 'O campo cpf é obrigatório.';
        } elseif (!$this->validarCpf($dados['cpf'])) {
            $erros[] = 'CPF inválido. Informe no formato 123.456.789-09 ou somente os 11 dígitos.';
        }

        // Nome: obrigatório, mínimo 3 caracteres
        if (empty($dados['nome'])) {
            $erros[] = 'O campo nome é obrigatório.';
        } elseif (strlen(trim($dados['nome'])) < 3) {
            $erros[] = 'O nome deve ter pelo menos 3 caracteres.';
        }

        // Data de nascimento: obrigatória, formato YYYY-MM-DD
        if (empty($dados['data_nascimento'])) {
            $erros[] = 'O campo data_nascimento é obrigatório. Use o formato YYYY-MM-DD.';
        } elseif (!$this->validarData($dados['data_nascimento'])) {
            $erros[] = 'Data de nascimento inválida. Use o formato YYYY-MM-DD (ex.: 1990-12-31).';
        }

        // E-mail: opcional, mas se informado deve ser válido
        if (!empty($dados['email']) && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'E-mail inválido.';
        }

        if (!empty($erros)) {
            throw new InvalidArgumentException(implode(' | ', $erros));
        }
    }

    /**
     * Valida o CPF brasileiro verificando formato e dígitos verificadores.
     *
     * Aceita: "123.456.789-09"  ou  "12345678909"
     *
     * Como funciona o algoritmo do CPF:
     *   1. Remove formatação (pontos e traço)
     *   2. Rejeita sequências repetidas (111.111.111-11 etc.)
     *   3. Calcula o 1º dígito verificador (posição 10)
     *   4. Calcula o 2º dígito verificador (posição 11)
     *
     * @param  string $cpf CPF a validar
     * @return bool   true se válido
     */
    private function validarCpf(string $cpf): bool
    {
        // Remove tudo que não for dígito
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Deve ter exatamente 11 dígitos
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Rejeita CPFs inválidos com todos os dígitos iguais
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Verifica os dois dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * ($t + 1 - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;

            if ((int) $cpf[$t] !== $digito) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida se a data está no formato correto YYYY-MM-DD.
     *
     * @param  string $data Data a validar
     * @return bool   true se válida
     */
    private function validarData(string $data): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $data);
        return $d !== false && $d->format('Y-m-d') === $data;
    }
}
