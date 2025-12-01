<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';

// Verificar se é administrador
verificarAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: apuracao.php');
    exit;
}

$id_eleicao = $_POST['id_eleicao'] ?? null;
$id_admin = $_SESSION['usuario_id'];

if (!$id_eleicao) {
    $_SESSION['erro'] = 'ID da eleição não informado.';
    header('Location: apuracao.php');
    exit;
}

try {
    // Verificar se a eleição existe e está aguardando finalização
    $sql = "SELECT * FROM ELEICAO WHERE id_eleicao = ? AND status = 'aguardando_finalizacao'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_eleicao]);
    $eleicao = $stmt->fetch();

    if (!$eleicao) {
        $_SESSION['erro'] = 'Eleição não encontrada ou não está aguardando finalização.';
        header('Location: apuracao.php');
        exit;
    }

    // Chamar stored procedure para finalizar eleição
    $sql = "CALL sp_finalizar_eleicao(?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_eleicao, $id_admin]);

    $_SESSION['sucesso'] = 'Eleição apurada com sucesso!';
    header('Location: apuracao.php');
    exit;

} catch (PDOException $e) {
    error_log("Erro ao apurar eleição: " . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao apurar eleição: ' . $e->getMessage();
    header('Location: apuracao.php');
    exit;
}
?>
