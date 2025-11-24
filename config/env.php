<?php
/**
 * Helper para carregar variáveis de ambiente do arquivo .env
 */

function carregarEnv($caminhoArquivo = null) {
    if ($caminhoArquivo === null) {
        $caminhoArquivo = __DIR__ . '/../.env';
    }

    if (!file_exists($caminhoArquivo)) {
        return false;
    }

    $linhas = file($caminhoArquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($linhas as $linha) {
        // Ignorar comentários
        if (strpos(trim($linha), '#') === 0) {
            continue;
        }

        // Separar chave=valor
        if (strpos($linha, '=') !== false) {
            list($chave, $valor) = explode('=', $linha, 2);

            $chave = trim($chave);
            $valor = trim($valor);

            // Remover aspas se existirem
            $valor = trim($valor, '"\'');

            // Definir no $_ENV e putenv
            $_ENV[$chave] = $valor;
            putenv("$chave=$valor");
        }
    }

    return true;
}

function env($chave, $padrao = null) {
    return $_ENV[$chave] ?? getenv($chave) ?: $padrao;
}

// Carregar automaticamente ao incluir este arquivo
carregarEnv();
?>
