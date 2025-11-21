<?php
// Sistema de gerenciamento de sessões
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para verificar se o usuário está logado
function verificarLogin() {
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_tipo'])) {
        header("Location: /sistema-integrado-votacao/public/pages/guest/login.php");
        exit;
    }
}

// Função para verificar se é administrador
function verificarAdmin() {
    verificarLogin();
    if ($_SESSION['usuario_tipo'] !== 'admin') {
        header("Location: /sistema-integrado-votacao/public/pages/guest/index.php");
        exit;
    }
}

// Função para verificar se é aluno
function verificarAluno() {
    verificarLogin();
    if ($_SESSION['usuario_tipo'] !== 'aluno') {
        header("Location: /sistema-integrado-votacao/public/pages/guest/index.php");
        exit;
    }
}

// Função para fazer login de aluno
function loginAluno($id_aluno, $nome, $email, $ra, $curso, $semestre) {
    $_SESSION['usuario_id'] = $id_aluno;
    $_SESSION['usuario_tipo'] = 'aluno';
    $_SESSION['usuario_nome'] = $nome;
    $_SESSION['usuario_email'] = $email;
    $_SESSION['usuario_ra'] = $ra;
    $_SESSION['usuario_curso'] = $curso;
    $_SESSION['usuario_semestre'] = $semestre;

    // Atualizar último acesso
    require_once __DIR__ . '/conexao.php';
    global $conn;
    $stmt = $conn->prepare("UPDATE ALUNO SET ultimo_acesso = NOW() WHERE id_aluno = ?");
    $stmt->execute([$id_aluno]);
}

// Função para fazer login de administrador
function loginAdmin($id_admin, $nome, $email) {
    $_SESSION['usuario_id'] = $id_admin;
    $_SESSION['usuario_tipo'] = 'admin';
    $_SESSION['usuario_nome'] = $nome;
    $_SESSION['usuario_email'] = $email;

    // Atualizar último acesso
    require_once __DIR__ . '/conexao.php';
    global $conn;
    $stmt = $conn->prepare("UPDATE ADMINISTRADOR SET ultimo_acesso = NOW() WHERE id_admin = ?");
    $stmt->execute([$id_admin]);
}

// Função para fazer logout
function logout() {
    session_unset();
    session_destroy();
    header("Location: /sistema-integrado-votacao/public/pages/guest/index.php");
    exit;
}

// Função para obter dados do usuário logado
function obterUsuarioLogado() {
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }

    return [
        'id' => $_SESSION['usuario_id'],
        'tipo' => $_SESSION['usuario_tipo'],
        'nome' => $_SESSION['usuario_nome'],
        'email' => $_SESSION['usuario_email'],
        'ra' => $_SESSION['usuario_ra'] ?? null,
        'curso' => $_SESSION['usuario_curso'] ?? null,
        'semestre' => $_SESSION['usuario_semestre'] ?? null
    ];
}

// Função para verificar se está logado (retorna boolean)
function estaLogado() {
    return isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_tipo']);
}
?>
