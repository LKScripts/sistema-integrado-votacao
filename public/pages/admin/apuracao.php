<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';

// Verificar se é administrador
verificarAdmin();

// Buscar eleições aguardando finalização ou já encerradas
$sql = "SELECT
            e.id_eleicao,
            e.curso,
            e.semestre,
            e.status,
            e.data_fim_votacao,
            COUNT(DISTINCT v.id_voto) as total_votos,
            COUNT(DISTINCT c.id_candidatura) as total_candidatos,
            r.id_resultado,
            r.data_apuracao
        FROM ELEICAO e
        LEFT JOIN VOTO v ON e.id_eleicao = v.id_eleicao
        LEFT JOIN CANDIDATURA c ON e.id_eleicao = c.id_eleicao AND c.status_validacao = 'deferido'
        LEFT JOIN RESULTADO r ON e.id_eleicao = r.id_eleicao
        WHERE e.status IN ('aguardando_finalizacao', 'encerrada')
        GROUP BY e.id_eleicao
        ORDER BY e.data_fim_votacao DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$eleicoes = $stmt->fetchAll();

// Mapear siglas para nomes completos
function obterNomeCurso($sigla) {
    $cursos = [
        'DSM' => 'Desenvolvimento de Software Multiplataforma',
        'GE' => 'Gestão Empresarial',
        'GPI' => 'Gestão da Produção Industrial'
    ];
    return $cursos[$sigla] ?? $sigla;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Apuração de Votos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="/assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <style>
        .election-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .election-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .election-info {
            flex: 1;
        }

        .election-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .election-details {
            color: #666;
            font-size: 0.9em;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .status-aguardando {
            background: #fff3cd;
            color: #856404;
        }

        .status-encerrada {
            background: #d4edda;
            color: #155724;
        }

        .election-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <?php require_once 'components/header.php'; ?>

    <main class="manage-reports">
        <div class="card-wrapper">
            <div class="card">
                <h1 class="title">Apuração de Votos</h1>

                <?php if (isset($_SESSION['sucesso'])): ?>
                    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0;">
                        <?= htmlspecialchars($_SESSION['sucesso']) ?>
                    </div>
                    <?php unset($_SESSION['sucesso']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['erro'])): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0;">
                        <?= htmlspecialchars($_SESSION['erro']) ?>
                    </div>
                    <?php unset($_SESSION['erro']); ?>
                <?php endif; ?>

                <?php if (count($eleicoes) > 0): ?>
                    <?php foreach($eleicoes as $eleicao): ?>
                        <div class="election-card">
                            <div class="election-header">
                                <div class="election-info">
                                    <div class="election-title">
                                        <?= htmlspecialchars(obterNomeCurso($eleicao['curso'])) ?> -
                                        <?= $eleicao['semestre'] ?>º Semestre
                                    </div>
                                    <div class="election-details">
                                        <?= $eleicao['total_votos'] ?> votos registrados |
                                        <?= $eleicao['total_candidatos'] ?> candidatos |
                                        Votação encerrada em: <?= date('d/m/Y', strtotime($eleicao['data_fim_votacao'])) ?>
                                        <?php if ($eleicao['data_apuracao']): ?>
                                            | Apurada em: <?= date('d/m/Y H:i', strtotime($eleicao['data_apuracao'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="status-badge status-<?= $eleicao['status'] === 'encerrada' ? 'encerrada' : 'aguardando' ?>">
                                    <?= $eleicao['status'] === 'encerrada' ? 'Apurada' : 'Aguardando Apuração' ?>
                                </span>
                            </div>

                            <div class="election-actions">
                                <a href="visualizar-votos.php?id=<?= $eleicao['id_eleicao'] ?>"
                                   class="btn btn-primary">
                                    Ver Contagem de Votos
                                </a>

                                <?php if ($eleicao['status'] === 'aguardando_finalizacao'): ?>
                                    <form method="POST" action="processar-apuracao.php" style="display: inline;">
                                        <input type="hidden" name="id_eleicao" value="<?= $eleicao['id_eleicao'] ?>">
                                        <button type="submit"
                                                class="btn btn-success"
                                                onclick="return confirm('Tem certeza que deseja apurar esta eleição? Esta ação não pode ser desfeita.')">
                                            Apurar Oficialmente
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($eleicao['status'] === 'encerrada' && $eleicao['id_resultado']): ?>
                                    <a href="gerar-ata.php?id=<?= $eleicao['id_eleicao'] ?>"
                                       class="btn btn-success"
                                       target="_blank">
                                        Gerar Ata de Votação
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; margin-top: 20px;">
                        Nenhuma eleição aguardando apuração ou apurada ainda.
                    </p>
                <?php endif; ?>
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
