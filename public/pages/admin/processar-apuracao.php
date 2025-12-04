<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/helpers.php';

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

    // Capturar dados ANTES da finalização
    $dados_antes = [
        'id_eleicao' => $eleicao['id_eleicao'],
        'curso' => $eleicao['curso'],
        'semestre' => $eleicao['semestre'],
        'status' => $eleicao['status'],
        'data_inicio_votacao' => $eleicao['data_inicio_votacao'],
        'data_fim_votacao' => $eleicao['data_fim_votacao']
    ];

    // Buscar total de votos para incluir na auditoria
    $stmtVotos = $conn->prepare("SELECT COUNT(*) as total_votos FROM VOTO WHERE id_eleicao = ?");
    $stmtVotos->execute([$id_eleicao]);
    $total_votos = $stmtVotos->fetch()['total_votos'];

    // Chamar stored procedure para finalizar eleição
    $sql = "CALL sp_finalizar_eleicao(?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_eleicao, $id_admin]);

    // Registrar auditoria da finalização
    registrarAuditoria(
        $conn,
        $id_admin,
        'ELEICAO',
        'UPDATE',
        "Finalizou eleição #{$id_eleicao} - {$eleicao['curso']} {$eleicao['semestre']}º sem - {$total_votos} votos",
        null,  // IP detectado automaticamente
        $id_eleicao,
        json_encode($dados_antes),
        json_encode([
            'id_eleicao' => $eleicao['id_eleicao'],
            'curso' => $eleicao['curso'],
            'semestre' => $eleicao['semestre'],
            'status' => 'encerrada',
            'finalizado_por' => $id_admin,
            'data_finalizacao' => date('Y-m-d H:i:s'),
            'total_votos' => $total_votos
        ])
    );

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
