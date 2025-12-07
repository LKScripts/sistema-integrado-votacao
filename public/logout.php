<?php
/**
 * Sistema de Logout Seguro
 * Destrói a sessão e redireciona para página inicial
 */

// Inicia sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Registra logout na auditoria (se for admin)
if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin') {
    try {
        require_once __DIR__ . '/../config/conexao.php';
        require_once __DIR__ . '/../config/helpers.php';

        // Capturar dados antes de destruir a sessão
        $id_admin = $_SESSION['usuario_id'];
        $nome_admin = $_SESSION['usuario_nome'] ?? 'Desconhecido';
        $email_admin = $_SESSION['usuario_email'] ?? 'desconhecido@email.com';

        // Registrar logout com dados completos
        registrarAuditoria(
            $conn,
            $id_admin,
            'ADMINISTRADOR',
            'LOGOUT',
            "Logout realizado - $nome_admin ($email_admin)",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            null,  // Não relacionado a eleição
            null,  // Sem dados anteriores
            json_encode([
                'id_admin' => $id_admin,
                'nome' => $nome_admin,
                'email' => $email_admin,
                'timestamp' => date('Y-m-d H:i:s'),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ])
        );
    } catch (Exception $e) {
        // Continua o logout mesmo se falhar auditoria
        error_log("Erro ao registrar logout na auditoria: " . $e->getMessage());
    }
}

// Limpa todas as variáveis de sessão
$_SESSION = [];

// Destrói o cookie de sessão se existir
if (isset($_COOKIE[session_name()])) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destrói a sessão
session_destroy();

// Regenera ID de sessão para segurança extra
session_start();
session_regenerate_id(true);
session_destroy();

// Redireciona para página inicial
header("Location: /sistema-integrado-votacao/public/index.php");
exit;
?>
