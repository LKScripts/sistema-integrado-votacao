<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';

// Verificar se é administrador
verificarAdmin();

$id_eleicao = $_GET['id'] ?? null;

if (!$id_eleicao) {
    header('Location: apuracao.php');
    exit;
}

// Buscar dados da eleição
$sql = "SELECT e.*, COUNT(DISTINCT v.id_voto) as total_votos
        FROM ELEICAO e
        LEFT JOIN VOTO v ON e.id_eleicao = v.id_eleicao
        WHERE e.id_eleicao = ?
        GROUP BY e.id_eleicao";

$stmt = $conn->prepare($sql);
$stmt->execute([$id_eleicao]);
$eleicao = $stmt->fetch();

if (!$eleicao) {
    header('Location: apuracao.php');
    exit;
}

// Buscar contagem de votos (incluindo votos em branco)
$sql = "SELECT * FROM v_contagem_votos_completa WHERE id_eleicao = ? ORDER BY total_votos DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$id_eleicao]);
$contagem = $stmt->fetchAll();

// Contar total de alunos aptos
$sql = "SELECT COUNT(*) as total_aptos
        FROM ALUNO
        WHERE curso = ? AND semestre = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$eleicao['curso'], $eleicao['semestre']]);
$aptos = $stmt->fetch();
$total_aptos = $aptos['total_aptos'];

// Calcular percentual de participação
$percentual_participacao = $total_aptos > 0 ? ($eleicao['total_votos'] / $total_aptos) * 100 : 0;

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
    <title>SIV - Contagem de Votos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="/assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .votes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .votes-table th,
        .votes-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .votes-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .votes-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .votes-table tr:hover {
            background-color: #f0f0f0;
        }

        .vote-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            position: relative;
            overflow: hidden;
        }

        .vote-bar-fill {
            background: linear-gradient(90deg, #007bff, #0056b3);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .candidate-name {
            font-weight: 500;
        }

        .blank-vote {
            color: #dc3545;
            font-style: italic;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <?php require_once 'components/header.php'; ?>

    <main class="manage-reports">
        <div class="card-wrapper">
            <div class="card">
                <h1 class="title">Contagem de Votos</h1>
                <h2 style="color: #666; margin-bottom: 20px;">
                    <?= htmlspecialchars(obterNomeCurso($eleicao['curso'])) ?> -
                    <?= $eleicao['semestre'] ?>º Semestre
                </h2>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= $eleicao['total_votos'] ?></div>
                        <div class="stat-label">Total de Votos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $total_aptos ?></div>
                        <div class="stat-label">Alunos Aptos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= number_format($percentual_participacao, 2) ?>%</div>
                        <div class="stat-label">Participação</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= count(array_filter($contagem, fn($c) => $c['id_candidatura'] != 0)) ?></div>
                        <div class="stat-label">Candidatos</div>
                    </div>
                </div>

                <?php if (count($contagem) > 0): ?>
                    <table class="votes-table">
                        <thead>
                            <tr>
                                <th>Posição</th>
                                <th>Candidato</th>
                                <th>RA</th>
                                <th>Votos</th>
                                <th>Porcentagem</th>
                                <th>Visualização</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $posicao = 1;
                            $total_votos = $eleicao['total_votos'];
                            foreach($contagem as $c):
                                $percentual = $total_votos > 0 ? ($c['total_votos'] / $total_votos) * 100 : 0;
                                $is_blank = $c['id_candidatura'] == 0;
                            ?>
                                <tr>
                                    <td><?= $posicao++ ?>º</td>
                                    <td class="<?= $is_blank ? 'blank-vote' : 'candidate-name' ?>">
                                        <?= htmlspecialchars($c['nome_candidato']) ?>
                                    </td>
                                    <td><?= $is_blank ? '-' : htmlspecialchars($c['ra']) ?></td>
                                    <td><strong><?= $c['total_votos'] ?></strong></td>
                                    <td><?= number_format($percentual, 2) ?>%</td>
                                    <td>
                                        <div class="vote-bar">
                                            <div class="vote-bar-fill" style="width: <?= $percentual ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; margin-top: 20px;">
                        Nenhum voto registrado para esta eleição ainda.
                    </p>
                <?php endif; ?>

                <div style="margin-top: 30px; text-align: center;">
                    <a href="apuracao.php" class="btn btn-secondary">Voltar</a>
                    <?php if ($eleicao['status'] !== 'encerrada'): ?>
                        <button onclick="window.print()" class="btn btn-primary">Imprimir Contagem</button>
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
