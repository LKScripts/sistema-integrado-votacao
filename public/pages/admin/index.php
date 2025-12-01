<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';

// Verificar se é administrador
verificarAdmin();

// Aplicar filtros
$filtro_curso = $_GET['curso'] ?? '';
$filtro_semestre = $_GET['semestre'] ?? '';
$filtro_ano = $_GET['ano'] ?? '';

// Base da query com filtros
$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($filtro_curso)) {
    $where_conditions[] = "curso = ?";
    $params[] = $filtro_curso;
    $types .= "s";
}

if (!empty($filtro_semestre)) {
    $where_conditions[] = "semestre = ?";
    $params[] = intval($filtro_semestre);
    $types .= "i";
}

if (!empty($filtro_ano)) {
    $where_conditions[] = "YEAR(data_apuracao) = ?";
    $params[] = intval($filtro_ano);
    $types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

// Estatísticas Gerais
$sql = "SELECT
            COUNT(DISTINCT id_eleicao) as total_eleicoes,
            SUM(total_votantes) as total_votos,
            AVG(percentual_participacao) as participacao_media,
            (SELECT COUNT(*) FROM VOTO WHERE id_candidatura IS NULL) as total_votos_branco,
            (SELECT COUNT(*) FROM VOTO) as total_votos_geral
        FROM v_resultados_completos
        WHERE $where_clause";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$estatisticas = $stmt->fetch();

$percentual_branco = $estatisticas['total_votos_geral'] > 0
    ? ($estatisticas['total_votos_branco'] / $estatisticas['total_votos_geral']) * 100
    : 0;

// Participação por Curso
$sql = "SELECT curso,
               AVG(percentual_participacao) as media_participacao,
               COUNT(*) as num_eleicoes
        FROM v_resultados_completos
        WHERE $where_clause
        GROUP BY curso
        ORDER BY media_participacao DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$participacao_curso = $stmt->fetchAll();

// Evolução Temporal (últimos 6 meses)
$sql = "SELECT
            DATE_FORMAT(data_apuracao, '%Y-%m') as mes_ano,
            DATE_FORMAT(data_apuracao, '%m/%Y') as mes_ano_fmt,
            AVG(percentual_participacao) as media_participacao
        FROM v_resultados_completos
        WHERE data_apuracao >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(data_apuracao, '%Y-%m')
        ORDER BY mes_ano ASC";

$evolucao = $conn->query($sql)->fetchAll();

// Top 5 Eleições com Maior Participação
$sql = "SELECT
            CONCAT(curso, '-', semestre) as turma,
            DATE_FORMAT(data_apuracao, '%m/%Y') as periodo,
            percentual_participacao,
            total_votantes,
            total_aptos
        FROM v_resultados_completos
        WHERE $where_clause
        ORDER BY percentual_participacao DESC
        LIMIT 5";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$top_participacao = $stmt->fetchAll();

// Histórico de Eleições
$sql = "SELECT
            id_resultado,
            curso,
            semestre,
            DATE_FORMAT(data_apuracao, '%d/%m/%Y') as data_apuracao_fmt,
            representante,
            ra_representante,
            votos_representante,
            suplente,
            votos_suplente,
            total_votantes,
            total_aptos,
            percentual_participacao
        FROM v_resultados_completos
        WHERE $where_clause
        ORDER BY data_apuracao DESC
        LIMIT 10";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$historico = $stmt->fetchAll();

// Buscar anos disponíveis para o filtro
$anos_disponiveis = $conn->query("SELECT DISTINCT YEAR(data_apuracao) as ano
                                   FROM RESULTADO
                                   ORDER BY ano DESC")->fetchAll();

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
    <title>SIV - Sistema Integrado de Votações</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="/assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard {
            padding: 40px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2em;
            color: #333;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #666;
            font-size: 1.1em;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filters-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95em;
        }

        .filter-btn {
            padding: 10px 25px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95em;
        }

        .filter-btn:hover {
            background: #004654;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--primary);
        }

        .stat-detail {
            color: #999;
            font-size: 0.85em;
            margin-top: 5px;
        }

        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .chart-title {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .top-list {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .top-item {
            padding: 15px;
            border-left: 4px solid var(--primary);
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .top-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .top-item-title {
            font-weight: 600;
            color: #333;
        }

        .top-item-percentage {
            font-size: 1.3em;
            font-weight: bold;
            color: var(--primary);
        }

        .top-item-bar {
            background: #e9ecef;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }

        .top-item-bar-fill {
            background: linear-gradient(90deg, var(--primary), #004654);
            height: 100%;
            transition: width 0.3s ease;
        }

        .history-table {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .history-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .history-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .history-table tr:hover {
            background: #f8f9fa;
        }

        @media (max-width: 768px) {
            .charts-row {
                grid-template-columns: 1fr;
            }

            .filters-row {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php require_once 'components/header.php'; ?>

    <main class="dashboard">
        <div class="page-header">
            <h1 class="page-title">Dashboard de Eleições</h1>
            <p class="page-subtitle">Análise histórica e estatísticas de eleições encerradas</p>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-row">
                    <div class="filter-group">
                        <label for="curso">Curso</label>
                        <select name="curso" id="curso">
                            <option value="">Todos os Cursos</option>
                            <option value="DSM" <?= $filtro_curso === 'DSM' ? 'selected' : '' ?>>DSM - Desenvolvimento de Software Multiplataforma</option>
                            <option value="GE" <?= $filtro_curso === 'GE' ? 'selected' : '' ?>>GE - Gestão Empresarial</option>
                            <option value="GPI" <?= $filtro_curso === 'GPI' ? 'selected' : '' ?>>GPI - Gestão da Produção Industrial</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="semestre">Semestre</label>
                        <select name="semestre" id="semestre">
                            <option value="">Todos os Semestres</option>
                            <?php for($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>" <?= $filtro_semestre == $i ? 'selected' : '' ?>><?= $i ?>º Semestre</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="ano">Ano</label>
                        <select name="ano" id="ano">
                            <option value="">Todos os Anos</option>
                            <?php foreach($anos_disponiveis as $ano_item): ?>
                                <option value="<?= $ano_item['ano'] ?>" <?= $filtro_ano == $ano_item['ano'] ? 'selected' : '' ?>>
                                    <?= $ano_item['ano'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="filter-btn">Aplicar Filtros</button>
                </div>
            </form>
        </div>

        <!-- Estatísticas Gerais -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Eleições Realizadas</div>
                <div class="stat-value"><?= $estatisticas['total_eleicoes'] ?? 0 ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total de Votos</div>
                <div class="stat-value"><?= number_format($estatisticas['total_votos'] ?? 0) ?></div>
                <div class="stat-detail"><?= $estatisticas['total_votos_branco'] ?? 0 ?> votos em branco</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Participação Média</div>
                <div class="stat-value"><?= number_format($estatisticas['participacao_media'] ?? 0, 1) ?>%</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Votos em Branco</div>
                <div class="stat-value"><?= number_format($percentual_branco, 1) ?>%</div>
                <div class="stat-detail">do total de votos</div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts-row">
            <div class="chart-card">
                <h3 class="chart-title">Taxa de Participação por Curso</h3>
                <div class="chart-container">
                    <canvas id="chartParticipacaoCurso"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">Distribuição de Votos</h3>
                <div class="chart-container">
                    <canvas id="chartVotosBranco"></canvas>
                </div>
            </div>
        </div>

        <?php if (count($evolucao) > 0): ?>
        <div class="chart-card" style="margin-bottom: 30px;">
            <h3 class="chart-title">Evolução da Participação (Últimos 6 Meses)</h3>
            <div class="chart-container">
                <canvas id="chartEvolucao"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top 5 Eleições -->
        <?php if (count($top_participacao) > 0): ?>
        <div class="top-list">
            <h3 class="chart-title">Top 5 Eleições com Maior Participação</h3>
            <?php foreach($top_participacao as $index => $item): ?>
                <div class="top-item">
                    <div class="top-item-header">
                        <span class="top-item-title">
                            <?= $index + 1 ?>. <?= htmlspecialchars($item['turma']) ?> (<?= htmlspecialchars($item['periodo']) ?>)
                        </span>
                        <span class="top-item-percentage"><?= number_format($item['percentual_participacao'], 1) ?>%</span>
                    </div>
                    <div class="top-item-bar">
                        <div class="top-item-bar-fill" style="width: <?= $item['percentual_participacao'] ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Histórico de Eleições -->
        <div class="history-table">
            <h3 class="chart-title">Histórico de Eleições</h3>
            <?php if (count($historico) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Semestre</th>
                            <th>Data</th>
                            <th>Representante</th>
                            <th>Votos</th>
                            <th>Suplente</th>
                            <th>Votos</th>
                            <th>Participação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($historico as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['curso']) ?></td>
                                <td><?= $item['semestre'] ?>º</td>
                                <td><?= $item['data_apuracao_fmt'] ?></td>
                                <td>
                                    <?= htmlspecialchars($item['representante']) ?>
                                    <br><small style="color: #999;">RA: <?= htmlspecialchars($item['ra_representante']) ?></small>
                                </td>
                                <td><?= $item['votos_representante'] ?></td>
                                <td>
                                    <?= $item['suplente'] ? htmlspecialchars($item['suplente']) : '-' ?>
                                </td>
                                <td><?= $item['votos_suplente'] ?? '-' ?></td>
                                <td>
                                    <strong><?= number_format($item['percentual_participacao'], 1) ?>%</strong>
                                    <br><small style="color: #999;">(<?= $item['total_votantes'] ?>/<?= $item['total_aptos'] ?>)</small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px 0;">
                    Nenhuma eleição encerrada encontrada com os filtros selecionados.
                </p>
            <?php endif; ?>
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

    <script>
        // Gráfico de Participação por Curso
        <?php if (count($participacao_curso) > 0): ?>
        const ctxCurso = document.getElementById('chartParticipacaoCurso');
        new Chart(ctxCurso, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($participacao_curso, 'curso')) ?>,
                datasets: [{
                    label: 'Participação Média (%)',
                    data: <?= json_encode(array_map(fn($item) => round($item['media_participacao'], 1), $participacao_curso)) ?>,
                    backgroundColor: [
                        'rgba(0, 95, 115, 0.8)',
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ],
                    borderColor: [
                        'rgba(0, 95, 115, 1)',
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        <?php endif; ?>

        // Gráfico de Votos em Branco
        const ctxBranco = document.getElementById('chartVotosBranco');
        new Chart(ctxBranco, {
            type: 'doughnut',
            data: {
                labels: ['Votos Válidos', 'Votos em Branco'],
                datasets: [{
                    data: [
                        <?= ($estatisticas['total_votos_geral'] ?? 0) - ($estatisticas['total_votos_branco'] ?? 0) ?>,
                        <?= $estatisticas['total_votos_branco'] ?? 0 ?>
                    ],
                    backgroundColor: [
                        'rgba(0, 95, 115, 0.8)',
                        'rgba(108, 117, 125, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Evolução
        <?php if (count($evolucao) > 0): ?>
        const ctxEvolucao = document.getElementById('chartEvolucao');
        new Chart(ctxEvolucao, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($evolucao, 'mes_ano_fmt')) ?>,
                datasets: [{
                    label: 'Participação Média (%)',
                    data: <?= json_encode(array_map(fn($item) => round($item['media_participacao'], 1), $evolucao)) ?>,
                    borderColor: 'rgba(0, 95, 115, 1)',
                    backgroundColor: 'rgba(0, 95, 115, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(0, 95, 115, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>

</html>
