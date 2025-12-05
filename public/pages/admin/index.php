<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';

// Verificar se é administrador
verificarAdmin();

// Buscar solicitações de mudança pendentes
$stmt_solicitacoes = $conn->query("
    SELECT
        s.id_solicitacao,
        s.tipo_mudanca,
        s.curso_atual,
        s.semestre_atual,
        s.curso_novo,
        s.semestre_novo,
        s.justificativa,
        s.data_solicitacao,
        a.id_aluno,
        a.nome_completo,
        a.ra,
        a.email_institucional
    FROM solicitacao_mudanca s
    INNER JOIN aluno a ON s.id_aluno = a.id_aluno
    WHERE s.status = 'pendente'
    ORDER BY s.data_solicitacao ASC
");
$solicitacoes_pendentes = $stmt_solicitacoes->fetchAll();
$total_solicitacoes = count($solicitacoes_pendentes);

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
            r.id_resultado,
            r.curso,
            r.semestre,
            DATE_FORMAT(r.data_apuracao, '%d/%m/%Y') as data_apuracao_fmt,
            DATE_FORMAT(e.data_inicio_votacao, '%d/%m/%Y') as data_inicio_fmt,
            DATE_FORMAT(e.data_fim_votacao, '%d/%m/%Y') as data_fim_fmt,
            r.representante,
            r.ra_representante,
            r.votos_representante,
            r.suplente,
            r.votos_suplente,
            r.total_votantes,
            r.total_aptos,
            r.percentual_participacao
        FROM v_resultados_completos r
        INNER JOIN eleicao e ON r.id_eleicao = e.id_eleicao
        WHERE $where_clause
        ORDER BY r.data_apuracao DESC
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
// ======== RECEBENDO FILTROS =========
$busca = $_GET['busca'] ?? '';
$filtro_tabela = $_GET['tabela'] ?? '';
$filtro_admin = $_GET['admin'] ?? '';
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';

// ======== PAGINAÇÃO =========
$limite = 15;  // Aumentado de 10 para 15
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina - 1) * $limite;

// Buscar valores únicos para dropdowns
$tabelas_disponiveis = $conn->query("SELECT DISTINCT tabela FROM AUDITORIA ORDER BY tabela")->fetchAll(PDO::FETCH_COLUMN);
$admins_disponiveis = $conn->query("
    SELECT DISTINCT a.id_admin, ad.nome_completo
    FROM AUDITORIA a
    LEFT JOIN ADMINISTRADOR ad ON a.id_admin = ad.id_admin
    WHERE a.id_admin IS NOT NULL
    ORDER BY ad.nome_completo
")->fetchAll(PDO::FETCH_ASSOC);

// ======== MONTANDO WHERE =========
$where = "WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $where .= " AND (descricao LIKE ? OR dados_anteriores LIKE ? OR dados_novos LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

if (!empty($filtro_tabela)) {
    $where .= " AND tabela = ?";
    $params[] = $filtro_tabela;
}

if (!empty($filtro_admin)) {
    $where .= " AND id_admin = ?";
    $params[] = intval($filtro_admin);
}

if (!empty($filtro_data_inicio)) {
    $where .= " AND DATE(data_hora) >= ?";
    $params[] = $filtro_data_inicio;
}

if (!empty($filtro_data_fim)) {
    $where .= " AND DATE(data_hora) <= ?";
    $params[] = $filtro_data_fim;
}

// ======== TOTAL DE REGISTROS =========
$stmtTotal = $conn->prepare("SELECT COUNT(*) FROM auditoria $where");
$stmtTotal->execute($params);
$total_registros = $stmtTotal->fetchColumn();
$total_paginas = ceil($total_registros / $limite);

// ======== BUSCA PAGINADA =========
$sql = "SELECT * FROM auditoria $where ORDER BY data_hora DESC LIMIT $limite OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Sistema Integrado de Votações</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <link rel="stylesheet" href="../../assets/styles/inscricoes.css">
    <link rel="stylesheet" href="../../assets/styles/modal.css">
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
        <?php if (isset($_SESSION['sucesso'])): ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>✓</strong> <?= $_SESSION['sucesso'] ?>
            </div>
            <?php unset($_SESSION['sucesso']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['erro'])): ?>
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>✗</strong> <?= $_SESSION['erro'] ?>
            </div>
            <?php unset($_SESSION['erro']); ?>
        <?php endif; ?>

        <div class="page-header">
            <h1 class="page-title">Dashboard de Eleições</h1>
            <p class="page-subtitle">Análise histórica e estatísticas de eleições encerradas</p>
        </div>

        <!-- Painel de Solicitações de Mudança -->
        <?php if ($total_solicitacoes > 0): ?>
        <div class="solicitacoes-painel" style="background: #fffbea; border: 2px solid #ffc107; border-radius: 8px; padding: 15px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleSolicitacoes()">
                <div>
                    <h3 style="margin: 0; color: #856404; font-size: 16px;">
                        <strong><?= $total_solicitacoes ?></strong> Solicitação<?= $total_solicitacoes > 1 ? 'ões' : '' ?> de Mudança Pendente<?= $total_solicitacoes > 1 ? 's' : '' ?>
                    </h3>
                    <p style="margin: 5px 0 0 0; color: #856404; font-size: 13px;">
                        Clique para expandir e visualizar as solicitações de alunos
                    </p>
                </div>
                <i id="iconToggle" class="fas fa-chevron-down" style="color: #856404; font-size: 20px; transition: transform 0.3s;"></i>
            </div>

            <div id="listaSolicitacoes" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ffc107;">
                <?php foreach ($solicitacoes_pendentes as $sol): ?>
                    <div class="solicitacao-item" style="background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 15px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <h4 style="margin: 0 0 10px 0; color: #333; font-size: 15px;">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($sol['nome_completo']) ?>
                                </h4>
                                <p style="margin: 3px 0; font-size: 13px; color: #666;">
                                    <strong>RA:</strong> <?= htmlspecialchars($sol['ra']) ?>
                                </p>
                                <p style="margin: 3px 0; font-size: 13px; color: #666;">
                                    <strong>Email:</strong> <?= htmlspecialchars($sol['email_institucional']) ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <p style="margin: 3px 0; font-size: 13px; color: #666;">
                                    <strong>Solicitado em:</strong> <?= date('d/m/Y H:i', strtotime($sol['data_solicitacao'])) ?>
                                </p>
                                <p style="margin: 3px 0; font-size: 13px;">
                                    <span style="background: #007bff; color: white; padding: 3px 10px; border-radius: 12px; font-size: 11px;">
                                        <?= strtoupper(str_replace('_', ' ', $sol['tipo_mudanca'])) ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
                            <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 20px; align-items: center;">
                                <div>
                                    <p style="margin: 0 0 8px 0; font-size: 12px; color: #999; text-transform: uppercase; font-weight: 600;">Dados Atuais</p>
                                    <p style="margin: 5px 0; font-size: 14px; color: #495057;">
                                        <strong>Curso:</strong> <?= htmlspecialchars($sol['curso_atual']) ?><br>
                                        <strong>Semestre:</strong> <?= htmlspecialchars($sol['semestre_atual']) ?>º
                                    </p>
                                </div>
                                <div style="text-align: center; align-self: center;">
                                    <i class="fas fa-arrow-right" style="color: #6c757d; font-size: 28px;"></i>
                                </div>
                                <div>
                                    <p style="margin: 0 0 8px 0; font-size: 12px; color: #007bff; text-transform: uppercase; font-weight: 600;">Dados Solicitados</p>
                                    <p style="margin: 5px 0; font-size: 14px; color: #007bff; font-weight: 600;">
                                        <strong>Curso:</strong> <?= $sol['curso_novo'] ? htmlspecialchars($sol['curso_novo']) : '<em style="color: #999; font-weight: 400;">Sem alteração</em>' ?><br>
                                        <strong>Semestre:</strong> <?= $sol['semestre_novo'] ? htmlspecialchars($sol['semestre_novo']) . 'º' : '<em style="color: #999; font-weight: 400;">Sem alteração</em>' ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <?php if ($sol['justificativa']): ?>
                            <div style="margin-bottom: 15px;">
                                <p style="margin: 0 0 5px 0; font-size: 12px; color: #666; font-weight: 700;">JUSTIFICATIVA:</p>
                                <p style="margin: 0; font-size: 14px; color: #555; font-style: italic; line-height: 1.6;">
                                    "<?= htmlspecialchars($sol['justificativa']) ?>"
                                </p>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button onclick="abrirModalAnalise(<?= $sol['id_solicitacao'] ?>, 'recusar')"
                                    class="button secondary"
                                    style="background: #dc3545; color: #fff; border: 2px solid #dc3545; padding: 8px 20px; font-size: 13px; font-weight: 600;">
                                <i class="fas fa-times"></i> Recusar
                            </button>
                            <button onclick="abrirModalAnalise(<?= $sol['id_solicitacao'] ?>, 'aprovar')"
                                    class="button primary"
                                    style="background: #28a745; border-color: #28a745; color: #fff; padding: 8px 20px; font-size: 13px;">
                                <i class="fas fa-check"></i> Aprovar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

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
                            <th>Data Início</th>
                            <th>Data Fim</th>
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
                                <td><?= $item['data_inicio_fmt'] ?></td>
                                <td><?= $item['data_fim_fmt'] ?></td>
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
<!-- ==================== AUDITORIA (ESTILO INSCRICOES.PHP) ==================== -->
<section class="list-applicants" style="margin-top: 40px;">
    
    <h2 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; color: #333;">
        Registro de Auditoria
    </h2>
    <p style="color: #666; margin-bottom: 25px; font-size: 14px;">
        Acompanhe todas as modificações feitas no sistema
    </p>

    <!-- FILTROS -->
    <form method="GET" class="form-filters">
        <div class="filters-grid">

            <div class="filter-group">
                <label for="busca">Buscar</label>
                <input id="busca" name="busca" type="text"
                       placeholder="Buscar descrição ou dados..."
                       value="<?= htmlspecialchars($busca) ?>">
            </div>

            <div class="filter-group">
                <label for="tabela">Tabela</label>
                <select id="tabela" name="tabela">
                    <option value="">Todas</option>
                    <?php foreach ($tabelas_disponiveis as $tabela): ?>
                        <option value="<?= htmlspecialchars($tabela) ?>"
                                <?= $filtro_tabela === $tabela ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tabela) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="admin">Administrador</label>
                <select id="admin" name="admin">
                    <option value="">Todos</option>
                    <?php foreach ($admins_disponiveis as $admin): ?>
                        <option value="<?= $admin['id_admin'] ?>"
                                <?= $filtro_admin == $admin['id_admin'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($admin['nome_completo'] ?? "Admin #{$admin['id_admin']}") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="data_inicio">Data Início</label>
                <input id="data_inicio" name="data_inicio" type="date"
                       value="<?= htmlspecialchars($filtro_data_inicio) ?>">
            </div>

            <div class="filter-group">
                <label for="data_fim">Data Fim</label>
                <input id="data_fim" name="data_fim" type="date"
                       value="<?= htmlspecialchars($filtro_data_fim) ?>">
            </div>
        </div>

        <div class="filter-actions">
            <button type="submit" class="button primary">
                <i class="fas fa-search"></i> Filtrar
            </button>
            <a href="index.php" class="button secondary">
                <i class="fas fa-times"></i> Limpar
            </a>
        </div>
    </form>

    <!-- TABELA -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Admin</th>
                <th>Eleição</th>
                <th>Tabela</th>
                <th>Operação</th>
                <th>Descrição</th>
                <th>Dados Anteriores</th>
                <th>Dados Novos</th>
                <th>IP</th>
                <th>Data/Hora</th>
            </tr>
        </thead>

        <tbody>
        <?php if (count($resultados) > 0): ?>
            <?php foreach ($resultados as $audit): ?>
                <tr>
                    <td><?= $audit['id_auditoria'] ?></td>
                    <td><?= $audit['id_admin'] ?></td>
                    <td><?= $audit['id_eleicao'] ?></td>
                    <td><?= $audit['tabela'] ?></td>
                    <td><?= $audit['operacao'] ?></td>
                    <td><?= htmlspecialchars($audit['descricao']) ?></td>

                    <td style="max-width:200px; word-break:break-word;">
                        <?= $audit['dados_anteriores'] ?: '<i>N/A</i>' ?>
                    </td>

                    <td style="max-width:200px; word-break:break-word;">
                        <?= $audit['dados_novos'] ?: '<i>N/A</i>' ?>
                    </td>

                    <td><?= $audit['ip_origem'] ?></td>
                    <td><?= $audit['data_hora'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" style="text-align:center; padding:25px;">
                    <div style="background:#fff3cd; padding:20px; border-radius:8px; border:1px solid #ffc107;">
                        <p style="color:#856404; margin:0;">Nenhum registro encontrado.</p>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>

        <!-- PAGINAÇÃO -->
        <tfoot>
        <tr>
            <td colspan="10">
                <?php
                // Calcular números para exibição
                $primeiro_registro = $total_registros > 0 ? $offset + 1 : 0;
                $ultimo_registro = min($offset + $limite, $total_registros);

                // Construir query string com filtros
                $query_params = [];
                if (!empty($busca)) $query_params[] = 'busca=' . urlencode($busca);
                if (!empty($filtro_tabela)) $query_params[] = 'tabela=' . urlencode($filtro_tabela);
                if (!empty($filtro_admin)) $query_params[] = 'admin=' . urlencode($filtro_admin);
                if (!empty($filtro_data_inicio)) $query_params[] = 'data_inicio=' . urlencode($filtro_data_inicio);
                if (!empty($filtro_data_fim)) $query_params[] = 'data_fim=' . urlencode($filtro_data_fim);
                $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
                ?>

                <div class="pagination" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0;">
                    <div class="results" style="color: #666; font-size: 14px;">
                        Mostrando <?= $primeiro_registro ?> a <?= $ultimo_registro ?> de <?= $total_registros ?> registro<?= $total_registros != 1 ? 's' : '' ?>
                    </div>

                    <?php if ($total_paginas > 1): ?>
                    <ul style="display: flex; gap: 5px; list-style: none; margin: 0; padding: 0;">
                        <!-- Botão Anterior -->
                        <li>
                            <?php if ($pagina > 1): ?>
                                <a href="?pagina=<?= $pagina - 1 ?><?= $query_string ?>"
                                   style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333;">
                                    ‹ Anterior
                                </a>
                            <?php else: ?>
                                <span style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; color: #ccc; opacity: 0.5; cursor: not-allowed;">
                                    ‹ Anterior
                                </span>
                            <?php endif; ?>
                        </li>

                        <?php
                        // Mostrar até 5 páginas
                        $inicio = max(1, $pagina - 2);
                        $fim = min($total_paginas, $pagina + 2);

                        for ($i = $inicio; $i <= $fim; $i++):
                        ?>
                            <li>
                                <a href="?pagina=<?= $i ?><?= $query_string ?>"
                                   style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; <?= $i == $pagina ? 'background:#005f73; color:white; font-weight:bold;' : 'color: #333;' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Botão Próximo -->
                        <li>
                            <?php if ($pagina < $total_paginas): ?>
                                <a href="?pagina=<?= $pagina + 1 ?><?= $query_string ?>"
                                   style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333;">
                                    Próximo ›
                                </a>
                            <?php else: ?>
                                <span style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; color: #ccc; opacity: 0.5; cursor: not-allowed;">
                                    Próximo ›
                                </span>
                            <?php endif; ?>
                        </li>
                    </ul>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        </tfoot>

    </table>
</section>
<!-- ==================== FIM AUDITORIA ==================== -->

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

        // ===== FUNÇÕES DE SOLICITAÇÃO DE MUDANÇA =====
        function toggleSolicitacoes() {
            const lista = document.getElementById('listaSolicitacoes');
            const icon = document.getElementById('iconToggle');

            if (lista.style.display === 'none') {
                lista.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                lista.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }

        function abrirModalAnalise(idSolicitacao, acao) {
            const titulo = acao === 'aprovar' ? 'Aprovar Solicitação' : 'Recusar Solicitação';
            const corFundo = acao === 'aprovar' ? '#28a745' : '#dc3545';
            const textoConfirmacao = acao === 'aprovar'
                ? 'Tem certeza que deseja APROVAR esta solicitação? Os dados do aluno serão alterados.'
                : 'Tem certeza que deseja RECUSAR esta solicitação?';

            const observacoesLabel = acao === 'aprovar'
                ? 'Observações (opcional)'
                : 'Motivo da recusa (opcional)';

            const html = `
                <div class="modal show" id="modalAnalise">
                    <div class="modal-content" style="max-width: 500px;">
                        <div class="modal-header" style="background: ${corFundo};">
                            <h2 style="margin: 0; color: #fff;">
                                <i class="fas fa-${acao === 'aprovar' ? 'check-circle' : 'times-circle'}"></i>
                                ${titulo}
                            </h2>
                            <button class="btn-close" type="button" onclick="fecharModalAnalise()">&times;</button>
                        </div>

                        <div class="modal-body">
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <p style="margin: 0; font-size: 14px; color: #555;">
                                    ${textoConfirmacao}
                                </p>
                            </div>

                            <form method="POST" action="processar_solicitacao_admin.php">
                                <input type="hidden" name="id_solicitacao" value="${idSolicitacao}">
                                <input type="hidden" name="acao" value="${acao}">

                                <div class="form-group">
                                    <label for="observacoes">${observacoesLabel}</label>
                                    <textarea id="observacoes" name="observacoes" rows="3"
                                              placeholder="${acao === 'recusar' ? 'Ex: Dados inconsistentes com registros acadêmicos...' : 'Ex: Aprovado conforme documentação apresentada...'}"></textarea>
                                </div>

                                <div class="form-buttons">
                                    <button type="button" class="button secondary" onclick="fecharModalAnalise()">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="button primary" style="background: ${corFundo}; border-color: ${corFundo};">
                                        <i class="fas fa-${acao === 'aprovar' ? 'check' : 'times'}"></i>
                                        Confirmar ${acao === 'aprovar' ? 'Aprovação' : 'Recusa'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', html);
        }

        function fecharModalAnalise() {
            const modal = document.getElementById('modalAnalise');
            if (modal) {
                modal.remove();
            }
        }

        // Fechar modal ao clicar fora
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('modalAnalise');
            if (modal && e.target === modal) {
                fecharModalAnalise();
            }
        });
    </script>
</body>

</html>
