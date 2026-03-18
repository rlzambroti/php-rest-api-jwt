<?php

/**
 * Carregador de variáveis de ambiente (.env)
 *
 * Lê o arquivo .env da raiz do projeto e registra cada variável
 * usando putenv() e $_ENV[], tornando-as acessíveis em todo o sistema.
 *
 * Formato aceito no .env:
 *   CHAVE=valor
 *   # linhas que começam com # são comentários
 *   CHAVE_COM_ASPAS="valor entre aspas"
 */
class Env
{
    /**
     * Carrega o arquivo .env e popula $_ENV e putenv().
     *
     * @param string $caminho Caminho absoluto até o arquivo .env
     */
    public static function carregar(string $caminho): void
    {
        if (!file_exists($caminho)) {
            return; // Se não existir, ignora silenciosamente
        }

        // Lê o arquivo linha por linha, ignorando linhas em branco
        $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            // Ignora comentários (linhas que começam com #)
            if ($linha === '' || $linha[0] === '#') {
                continue;
            }

            // Divide em chave e valor no primeiro '=' encontrado
            $posicaoIgual = strpos($linha, '=');
            if ($posicaoIgual === false) {
                continue; // Linha sem '=' é ignorada
            }

            $chave = trim(substr($linha, 0, $posicaoIgual));
            $valor = trim(substr($linha, $posicaoIgual + 1));

            // Remove aspas simples ou duplas ao redor do valor
            if (strlen($valor) >= 2) {
                $primeiroChar = $valor[0];
                $ultimoChar   = $valor[strlen($valor) - 1];

                if (($primeiroChar === '"' && $ultimoChar === '"') ||
                    ($primeiroChar === "'" && $ultimoChar === "'")) {
                    $valor = substr($valor, 1, -1);
                }
            }

            // Registra a variável de ambiente (se ainda não estiver definida)
            if (!isset($_ENV[$chave])) {
                $_ENV[$chave] = $valor;
                putenv("{$chave}={$valor}");
            }
        }
    }

    /**
     * Retorna o valor de uma variável de ambiente.
     * Se não existir, retorna o valor padrão informado.
     *
     * @param string $chave   Nome da variável
     * @param mixed  $padrao  Valor padrão (opcional)
     * @return mixed
     */
    public static function get(string $chave, $padrao = null)
    {
        return $_ENV[$chave] ?? $padrao;
    }
}
