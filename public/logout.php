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

        $stmt = $conn->prepare("
            INSERT INTO AUDITORIA (id_admin, tipo_acao, descricao, ip_origem, data_acao)
            VALUES (?, 'logout', 'Logout realizado', ?, NOW())
        ");
        $stmt->execute([
            $_SESSION['usuario_id'],
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
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
