<?php
/**
 * PROTEÇÃO CSRF (Cross-Site Request Forgery)
 *
 * Funções para gerar e validar tokens CSRF em formulários
 * Protege contra ataques de requisições forjadas
 */

/**
 * Gera um token CSRF único para a sessão atual
 * Deve ser chamado em formulários HTML
 *
 * @return string Token CSRF de 64 caracteres
 */
function gerarTokenCSRF() {
    // Inicia sessão se ainda não foi iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Gerar novo token se não existir
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 64 caracteres hex
    }

    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF enviado pelo formulário
 * Deve ser chamado no processamento de POST/PUT/DELETE
 *
 * @param string $token Token recebido do formulário
 * @return bool TRUE se válido, FALSE se inválido
 */
function validarTokenCSRF($token) {
    // Inicia sessão se ainda não foi iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar se token existe na sessão
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    // Comparação segura contra timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Gera campo hidden HTML com token CSRF
 * Uso: echo campoCSRF(); dentro de <form>
 *
 * @return string HTML do campo hidden
 */
function campoCSRF() {
    $token = gerarTokenCSRF();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Valida token CSRF e encerra execução se inválido
 * Uso: validarCSRFOuMorrer(); no início do processamento POST
 *
 * @param string $mensagemErro Mensagem customizada de erro
 * @return void
 */
function validarCSRFOuMorrer($mensagemErro = null) {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

    if (!validarTokenCSRF($token)) {
        // Log de tentativa de ataque
        error_log("CSRF Attack Detected - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        // Mensagem padrão se não fornecida
        if ($mensagemErro === null) {
            $mensagemErro = "Requisição inválida. Por favor, recarregue a página e tente novamente.";
        }

        // Redirecionar com mensagem de erro
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['erro_csrf'] = $mensagemErro;

        // Redirecionar para página anterior ou index
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: $referer");
        exit;
    }
}

/**
 * Obtém e limpa mensagem de erro CSRF da sessão
 *
 * @return string|null Mensagem de erro ou null
 */
function obterErroCSRF() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $erro = $_SESSION['erro_csrf'] ?? null;
    unset($_SESSION['erro_csrf']);

    return $erro;
}

/**
 * Regenera o token CSRF (útil após login/logout)
 *
 * @return string Novo token gerado
 */
function regenerarTokenCSRF() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
?>
