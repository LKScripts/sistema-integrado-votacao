<?php
// Sistema de gerenciamento de sessões
if (session_status() === PHP_SESSION_NONE) {
    // Configurar opções de segurança da sessão
    ini_set('session.cookie_httponly', 1); // Previne acesso via JavaScript (XSS)
    ini_set('session.cookie_samesite', 'Strict'); // Previne CSRF

    // Descomentar para HTTPS (Quando colocar o site no ar):
    // ini_set('session.cookie_secure', 1); // Apenas HTTPS

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
function loginAluno($id_aluno, $nome, $email, $ra, $curso, $semestre, $foto_perfil = null) {
    // Regenerar session ID para prevenir session fixation attack
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $id_aluno;
    $_SESSION['usuario_tipo'] = 'aluno';
    $_SESSION['usuario_nome'] = $nome;
    $_SESSION['usuario_email'] = $email;
    $_SESSION['usuario_ra'] = $ra;
    $_SESSION['usuario_curso'] = $curso;
    $_SESSION['usuario_semestre'] = $semestre;
    $_SESSION['usuario_foto'] = $foto_perfil;

    // Atualizar último acesso
    require_once __DIR__ . '/conexao.php';
    global $conn;
    $stmt = $conn->prepare("UPDATE ALUNO SET ultimo_acesso = NOW() WHERE id_aluno = ?");
    $stmt->execute([$id_aluno]);
}

// Função para fazer login de administrador
function loginAdmin($id_admin, $nome, $email) {
    // Regenerar session ID para prevenir session fixation attack
    session_regenerate_id(true);

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
        'semestre' => $_SESSION['usuario_semestre'] ?? null,
        'foto' => $_SESSION['usuario_foto'] ?? null
    ];
}

// Função para obter URL da foto do usuário (com fallback para imagem padrão)
function obterFotoUsuario() {
    // Primeiro tentar da sessão
    $foto = $_SESSION['usuario_foto'] ?? null;

    // Se não tiver na sessão e for aluno, buscar do banco
    if (empty($foto) && isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'aluno' && isset($_SESSION['usuario_id'])) {
        try {
            // Criar conexão local para evitar problemas de escopo global
            $host = "localhost";
            $usuario = "root";
            $senha = "";
            $banco = "siv_db";
            $porta = 3306;

            $dsn = "mysql:host=$host;dbname=$banco;port=$porta;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $connLocal = new PDO($dsn, $usuario, $senha, $options);

            $stmt = $connLocal->prepare("SELECT foto_perfil FROM ALUNO WHERE id_aluno = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $result = $stmt->fetch();

            if ($result && !empty($result['foto_perfil'])) {
                $foto = $result['foto_perfil'];
                // Atualizar sessão para não precisar buscar novamente
                $_SESSION['usuario_foto'] = $foto;
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar foto do usuário: " . $e->getMessage());
        }
    }

    // Se tiver foto, retornar
    if (!empty($foto)) {
        return $foto;
    }

    // Fallback para imagem padrão
    return '../../assets/images/user-icon.png';
}

// Função para verificar se está logado (retorna boolean)
function estaLogado() {
    return isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_tipo']);
}
?>
