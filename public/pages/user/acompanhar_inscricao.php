<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/automacao_eleicoes.php';

verificarAluno();

$usuario = obterUsuarioLogado();
$id_aluno = $usuario['id'];
$curso = $usuario['curso'];
$semestre = $usuario['semestre'];

// Buscar candidatura mais recente do aluno
$stmt = $conn->prepare("
    SELECT
        c.*,
        e.status as status_eleicao,
        e.curso as eleicao_curso,
        e.semestre as eleicao_semestre,
        e.data_inicio_candidatura,
        e.data_fim_candidatura,
        e.data_inicio_votacao,
        e.data_fim_votacao,
        a.nome_completo,
        a.ra,
        a.foto_perfil,
        admin.nome_completo as nome_validador
    FROM CANDIDATURA c
    JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
    JOIN ALUNO a ON c.id_aluno = a.id_aluno
    LEFT JOIN ADMINISTRADOR admin ON c.validado_por = admin.id_admin
    WHERE c.id_aluno = ?
    AND e.curso = ?
    AND e.semestre = ?
    ORDER BY c.data_inscricao DESC
    LIMIT 1
");
$stmt->execute([$id_aluno, $curso, $semestre]);
$candidatura = $stmt->fetch();

// Gerar nome da eleição baseado nos dados
if ($candidatura) {
    $candidatura['nome_eleicao'] = "Eleição {$candidatura['eleicao_curso']} - {$candidatura['eleicao_semestre']}º Semestre";
}

if (!$candidatura) {
    header("Location: index.php");
    exit;
}

// Determinar cor do status
$status_colors = [
    'pendente' => ['bg' => '#fff3cd', 'border' => '#ffc107', 'text' => '#856404', 'icon' => 'clock'],
    'deferido' => ['bg' => '#d4edda', 'border' => '#28a745', 'text' => '#155724', 'icon' => 'check-circle'],
    'indeferido' => ['bg' => '#f8d7da', 'border' => '#dc3545', 'text' => '#721c24', 'icon' => 'times-circle']
];

$status_atual = $candidatura['status_validacao'];
$cor_status = $status_colors[$status_atual] ?? $status_colors['pendente'];

// Determinar foto para preview
$foto_preview = null;
if (!empty($candidatura['foto_candidato'])) {
    if (filter_var($candidatura['foto_candidato'], FILTER_VALIDATE_URL)) {
        $foto_preview = $candidatura['foto_candidato'];
    } else {
        $foto_preview = '../../../storage/uploads/candidatos/' . $candidatura['foto_candidato'];
    }
} elseif (!empty($candidatura['foto_perfil'])) {
    $foto_preview = $candidatura['foto_perfil'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhar Inscrição - SIV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include 'components/header.php'; ?>

    <main class="user-application">
        <div class="card-wrapper">
            <div class="card">
                <h1 class="title">
                    <i class="fas fa-clipboard-check"></i>
                    Acompanhamento de Inscrição
                </h1>

                <!-- Status da Candidatura -->
                <div style="background: <?= $cor_status['bg'] ?>; border: 2px solid <?= $cor_status['border'] ?>; border-radius: 12px; padding: 20px; margin-bottom: 30px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-<?= $cor_status['icon'] ?>" style="font-size: 40px; color: <?= $cor_status['text'] ?>;"></i>
                        <div>
                            <h2 style="margin: 0; color: <?= $cor_status['text'] ?>; font-size: 24px; text-transform: uppercase;">
                                Status: <?= htmlspecialchars($status_atual) ?>
                            </h2>
                            <p style="margin: 5px 0 0 0; color: <?= $cor_status['text'] ?>; font-size: 14px;">
                                <?php if ($status_atual === 'pendente'): ?>
                                    Sua candidatura está aguardando análise do administrador.
                                <?php elseif ($status_atual === 'deferido'): ?>
                                    Parabéns! Sua candidatura foi aprovada e você aparecerá na votação.
                                <?php else: ?>
                                    Infelizmente sua candidatura foi indeferida. Veja a justificativa abaixo.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($status_atual === 'indeferido' && !empty($candidatura['justificativa_indeferimento'])): ?>
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid <?= $cor_status['border'] ?>;">
                            <h3 style="margin: 0 0 10px 0; color: <?= $cor_status['text'] ?>; font-size: 16px;">
                                <i class="fas fa-exclamation-triangle"></i> Motivo do Indeferimento:
                            </h3>
                            <p style="margin: 0; color: <?= $cor_status['text'] ?>; font-size: 14px; line-height: 1.6;">
                                <?= nl2br(htmlspecialchars($candidatura['justificativa_indeferimento'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($candidatura['validado_por'] && $candidatura['data_validacao']): ?>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid <?= $cor_status['border'] ?>; font-size: 13px; color: <?= $cor_status['text'] ?>;">
                            <i class="fas fa-user-shield"></i>
                            Validado por: <strong><?= htmlspecialchars($candidatura['nome_validador']) ?></strong> em
                            <?= date('d/m/Y H:i', strtotime($candidatura['data_validacao'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Preview da Card (como apareceria na votação) -->
                <h2 style="margin-bottom: 20px; color: #005f73;">
                    <i class="fas fa-eye"></i> Preview - Como aparecerá na votação:
                </h2>

                <div class="candidate-card" style="max-width: 400px; margin: 0 auto 30px;">
                    <div class="media">
                        <?php if ($foto_preview): ?>
                            <img src="<?= htmlspecialchars($foto_preview) ?>" alt="Sua foto" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="placeholder" style="display:none;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php else: ?>
                            <div class="placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="content">
                        <h2><?= htmlspecialchars($candidatura['nome_completo']) ?></h2>
                        <div class="info-row">
                            <i class="fas fa-id-card"></i>
                            <span>RA: <?= htmlspecialchars($candidatura['ra']) ?></span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-graduation-cap"></i>
                            <span><?= htmlspecialchars($curso) ?></span>
                        </div>
                        <?php if (!empty($candidatura['proposta'])): ?>
                            <div class="info-row proposta-preview">
                                <p style="margin-top: 10px;"><strong>Proposta:</strong> <?= htmlspecialchars(substr($candidatura['proposta'], 0, 100)) ?>...</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 15px; background: #f8f9fa; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 14px;">
                        <i class="fas fa-info-circle"></i> Apenas preview - não é possível votar aqui
                    </div>
                </div>

                <!-- Informações Detalhadas -->
                <div style="background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 30px;">
                    <h3 style="margin-top: 0; color: #005f73;">
                        <i class="fas fa-info-circle"></i> Informações da Candidatura
                    </h3>

                    <div style="display: grid; gap: 15px;">
                        <div>
                            <strong style="color: #666;">Eleição:</strong><br>
                            <?= htmlspecialchars($candidatura['nome_eleicao']) ?>
                        </div>

                        <div>
                            <strong style="color: #666;">Curso/Semestre:</strong><br>
                            <?= htmlspecialchars($curso) ?> - <?= $semestre ?>º Semestre
                        </div>

                        <div>
                            <strong style="color: #666;">Data da Inscrição:</strong><br>
                            <?= date('d/m/Y H:i', strtotime($candidatura['data_inscricao'])) ?>
                        </div>

                        <?php if (!empty($candidatura['proposta'])): ?>
                            <div>
                                <strong style="color: #666;">Proposta Completa:</strong><br>
                                <div style="margin-top: 10px; padding: 15px; background: white; border-radius: 8px; white-space: pre-wrap; line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($candidatura['proposta'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div>
                            <strong style="color: #666;">Período de Candidaturas:</strong><br>
                            <?= date('d/m/Y H:i', strtotime($candidatura['data_inicio_candidatura'])) ?> até
                            <?= date('d/m/Y H:i', strtotime($candidatura['data_fim_candidatura'])) ?>
                        </div>

                        <div>
                            <strong style="color: #666;">Período de Votação:</strong><br>
                            <?= date('d/m/Y H:i', strtotime($candidatura['data_inicio_votacao'])) ?> até
                            <?= date('d/m/Y H:i', strtotime($candidatura['data_fim_votacao'])) ?>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="form-buttons">
                    <a href="index.php" class="button secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                    <?php if ($status_atual === 'deferido'): ?>
                        <a href="votacao.php" class="button primary">
                            <i class="fas fa-vote-yea"></i> Ver Votação
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="site">
        <div class="content">
            <img src="../../assets/images/logo-governo-do-estado-sp.png" alt="Logo Governo SP" class="logo-governo">
            <a href="../../pages/guest/sobre.php" class="btn-about">SOBRE O SISTEMA</a>
            <p>Sistema Integrado de Votação - FATEC/CPS</p>
            <p>Versão 0.1 (11/06/2025)</p>
        </div>
    </footer>
</body>
</html>
