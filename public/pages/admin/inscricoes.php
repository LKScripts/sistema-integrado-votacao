<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';

// Verifica se é administrador logado
verificarAdmin();

$usuario = obterUsuarioLogado();
$id_admin = $usuario['id'];

$mensagem = "";
$erro = "";

// Processar validação de candidatura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_candidatura'])) {
    // VALIDAR CSRF PRIMEIRO
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente validar novamente.");

    $id_candidatura = intval($_POST['id_candidatura']);
    $acao = $_POST['acao'] ?? '';
    $justificativa = trim($_POST['justificativa'] ?? '');

    if ($acao === 'deferir') {
        $stmt = $conn->prepare("
            UPDATE CANDIDATURA
            SET status_validacao = 'deferido',
                validado_por = ?,
                data_validacao = NOW()
            WHERE id_candidatura = ?
        ");
        if ($stmt->execute([$id_admin, $id_candidatura])) {
            $mensagem = "Candidatura deferida com sucesso!";
        }

    } elseif ($acao === 'indeferir' && !empty($justificativa)) {
        $stmt = $conn->prepare("
            UPDATE CANDIDATURA
            SET status_validacao = 'indeferido',
                validado_por = ?,
                data_validacao = NOW(),
                justificativa_indeferimento = ?
            WHERE id_candidatura = ?
        ");
        if ($stmt->execute([$id_admin, $justificativa, $id_candidatura])) {
            $mensagem = "Candidatura indeferida com sucesso!";
        }
    } else {
        $erro = "Para indeferir é necessário informar a justificativa.";
    }
}

// Filtros avançados
$filtro_curso = $_GET['curso'] ?? '';
$filtro_semestre = $_GET['semestre'] ?? '';
$filtro_nome = $_GET['nome'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Ordenação
$sort_column = $_GET['sort'] ?? 'data_inscricao';
$sort_order = $_GET['order'] ?? 'DESC';

// Validar coluna de ordenação permitida
$allowed_columns = ['data_inscricao', 'nome_completo', 'curso', 'semestre', 'status_validacao'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'data_inscricao';
}

// Validar ordem
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

// Paginação
$registros_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Query para contar total de registros
$sql_count = "
    SELECT COUNT(*) as total
    FROM CANDIDATURA c
    JOIN ALUNO a ON c.id_aluno = a.id_aluno
    JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
    WHERE 1=1
";

$sql = "
    SELECT
        c.id_candidatura,
        c.data_inscricao,
        c.status_validacao,
        c.proposta,
        c.foto_candidato,
        c.justificativa_indeferimento,
        a.nome_completo,
        a.ra,
        a.email_institucional,
        e.curso,
        e.semestre,
        e.id_eleicao
    FROM CANDIDATURA c
    JOIN ALUNO a ON c.id_aluno = a.id_aluno
    JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
    WHERE 1=1
";

$params = [];

// Aplicar filtros
if (!empty($filtro_curso)) {
    $sql .= " AND e.curso = ?";
    $sql_count .= " AND e.curso = ?";
    $params[] = $filtro_curso;
}

if (!empty($filtro_semestre)) {
    $sql .= " AND e.semestre = ?";
    $sql_count .= " AND e.semestre = ?";
    $params[] = intval($filtro_semestre);
}

if (!empty($filtro_nome)) {
    $sql .= " AND a.nome_completo LIKE ?";
    $sql_count .= " AND a.nome_completo LIKE ?";
    $params[] = "%$filtro_nome%";
}

if (!empty($filtro_status)) {
    $sql .= " AND c.status_validacao = ?";
    $sql_count .= " AND c.status_validacao = ?";
    $params[] = $filtro_status;
}

// Contar total de registros
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Mapear colunas para query SQL
$column_map = [
    'data_inscricao' => 'c.data_inscricao',
    'nome_completo' => 'a.nome_completo',
    'curso' => 'e.curso',
    'semestre' => 'e.semestre',
    'status_validacao' => 'c.status_validacao'
];

// Buscar registros da página atual com ordenação
$sql .= " ORDER BY {$column_map[$sort_column]} $sort_order LIMIT ? OFFSET ?";
$params[] = $registros_por_pagina;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$candidaturas = $stmt->fetchAll();

// Calcular range de registros exibidos
$primeiro_registro = $total_registros > 0 ? $offset + 1 : 0;
$ultimo_registro = min($offset + $registros_por_pagina, $total_registros);

// Função para gerar link de ordenação
function getSortLink($column, $current_sort, $current_order) {
    global $filtro_curso, $filtro_semestre, $filtro_nome, $filtro_status;

    $query_params = [];
    if (!empty($filtro_nome)) $query_params[] = 'nome=' . urlencode($filtro_nome);
    if (!empty($filtro_curso)) $query_params[] = 'curso=' . urlencode($filtro_curso);
    if (!empty($filtro_semestre)) $query_params[] = 'semestre=' . urlencode($filtro_semestre);
    if (!empty($filtro_status)) $query_params[] = 'status=' . urlencode($filtro_status);

    // Se já está ordenando por esta coluna, inverte a ordem
    if ($column === $current_sort) {
        $new_order = $current_order === 'ASC' ? 'DESC' : 'ASC';
    } else {
        // Nova coluna: ordem padrão
        $new_order = 'DESC';
    }

    $query_params[] = 'sort=' . $column;
    $query_params[] = 'order=' . $new_order;

    return 'inscricoes.php?' . implode('&', $query_params);
}

// Função para obter ícone de ordenação
function getSortIcon($column, $current_sort, $current_order) {
    if ($column !== $current_sort) {
        return '<span class="sort-icon">⇅</span>';
    }
    return $current_order === 'ASC'
        ? '<span class="sort-icon active">↑</span>'
        : '<span class="sort-icon active">↓</span>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Gerenciar Inscrições</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <style>
        /* Filtros aprimorados */
        .form-filters {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
            font-size: 0.9em;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 0.95em;
            transition: border-color 0.2s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .button {
            padding: 6px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.2s;
            font-size: 0.9em;
            width: auto;
            white-space: nowrap;
            line-height: 1.4;
        }

        .button.primary {
            background: var(--primary);
            color: white;
        }

        .button.primary:hover {
            background: #004654;
        }

        .button.secondary {
            background: #6c757d;
            color: white;
        }

        .button.secondary:hover {
            background: #5a6268;
        }

        /* Tabela com ordenação */
        .list-applicants table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .list-applicants thead th {
            background: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }

        .list-applicants thead th.sortable {
            cursor: pointer;
            user-select: none;
            transition: background 0.2s;
        }

        .list-applicants thead th.sortable:hover {
            background: #e9ecef;
            color: #333;
        }

        .list-applicants thead th.sortable.active {
            background: #e8f4f8;
            color: var(--primary);
        }

        .sort-icon {
            font-size: 1.1em;
            margin-left: 6px;
            opacity: 0.4;
            transition: opacity 0.2s;
        }

        .sort-icon.active {
            opacity: 1;
            color: var(--primary);
        }

        .list-applicants tbody td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .list-applicants tbody tr:hover {
            background: #f8f9fa;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .status-pendente {
            background: #fff3cd;
            color: #856404;
        }

        .status-deferido {
            background: #d4edda;
            color: #155724;
        }

        .status-indeferido {
            background: #f8d7da;
            color: #721c24;
        }

        /* Ações da tabela */
        .action-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .action-link:hover {
            background: #e8f4f8;
            color: #004654;
        }

        /* Info box */
        .info-box {
            background: #e8f4f8;
            border: 1px solid var(--primary);
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-box-icon {
            font-size: 1.5em;
            color: var(--primary);
        }

        .info-box-text {
            flex: 1;
            color: #004654;
            font-size: 0.95em;
        }

        .info-box-text strong {
            font-weight: 600;
        }

        /* Modal styles override */
        .modal .content {
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 0.95em;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* Paginação melhorada */
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            flex-wrap: wrap;
            gap: 15px;
        }

        .pagination .results {
            color: #666;
            font-size: 0.9em;
        }

        .pagination ul {
            display: flex;
            gap: 5px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .pagination ul li a,
        .pagination ul li span {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }

        .pagination ul li a:hover {
            background: #e9ecef;
        }

        .pagination ul li a[style*="background: #005f73"] {
            background: var(--primary) !important;
            color: white !important;
            border-color: var(--primary) !important;
        }
    </style>
</head>

<body>
    <?php require_once 'components/header.php'; ?>

    <?php if (!empty($mensagem)): ?>
        <div class="modal feedback" style="display:block;">
            <div class="content">
                <a href="inscricoes.php" class="close">&times;</a>
                <h3 class="title">Sucesso!</h3>
                <div class="text">
                    <p><?= htmlspecialchars($mensagem) ?></p>
                </div>
                <div class="modal-buttons">
                    <a href="inscricoes.php" class="button primary">OK</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($erro)): ?>
        <div class="modal feedback" style="display:block;">
            <div class="content">
                <a href="inscricoes.php" class="close">&times;</a>
                <h3 class="title">Erro</h3>
                <div class="text">
                    <p><?= htmlspecialchars($erro) ?></p>
                </div>
                <div class="modal-buttons">
                    <a href="inscricoes.php" class="button primary">OK</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($candidaturas as $cand): ?>
    <div class="modal" id="edit-user-modal-<?= $cand['id_candidatura'] ?>">
        <div class="content">
            <a href="#" class="close">&times;</a>

            <h3 class="title">Validar Candidatura</h3>

            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" value="<?= htmlspecialchars($cand['nome_completo']) ?>" readonly />
            </div>

            <div class="form-group">
                <label>RA</label>
                <input type="text" value="<?= htmlspecialchars($cand['ra']) ?>" readonly />
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="text" value="<?= htmlspecialchars($cand['email_institucional']) ?>" readonly />
            </div>

            <div class="form-group">
                <label>Curso</label>
                <input type="text" value="<?= htmlspecialchars($cand['curso']) ?>" readonly />
            </div>

            <div class="form-group">
                <label>Semestre</label>
                <input type="text" value="<?= $cand['semestre'] ?>º Semestre" readonly />
            </div>

            <div class="form-group">
                <label>Proposta do Candidato</label>
                <textarea readonly><?= htmlspecialchars($cand['proposta']) ?></textarea>
            </div>

            <?php if (!empty($cand['foto_candidato'])): ?>
            <div class="form-group">
                <label>Foto do Candidato</label>
                <img src="../../../storage/uploads/candidatos/<?= htmlspecialchars($cand['foto_candidato']) ?>"
                     alt="Foto do candidato"
                     style="max-width: 300px; max-height: 300px; border-radius: 8px; display: block; margin-top: 10px;" />
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Status Atual</label>
                <input type="text" value="<?= ucfirst($cand['status_validacao']) ?>" readonly />
            </div>

            <?php if ($cand['status_validacao'] === 'pendente'): ?>
            <form method="POST" id="form-deferir-<?= $cand['id_candidatura'] ?>">
                <?= campoCSRF() ?>
                <input type="hidden" name="id_candidatura" value="<?= $cand['id_candidatura'] ?>" />
                <input type="hidden" name="acao" value="deferir" />
            </form>

            <form method="POST" id="form-indeferir-<?= $cand['id_candidatura'] ?>">
                <?= campoCSRF() ?>
                <input type="hidden" name="id_candidatura" value="<?= $cand['id_candidatura'] ?>" />
                <input type="hidden" name="acao" value="indeferir" />

                <div class="form-group">
                    <label for="justificativa-<?= $cand['id_candidatura'] ?>">Justificativa para Indeferimento</label>
                    <textarea
                        id="justificativa-<?= $cand['id_candidatura'] ?>"
                        name="justificativa"
                        placeholder="Digite o motivo do indeferimento..."></textarea>
                </div>
            </form>

            <div class="modal-buttons">
                <a href="#" class="button secondary">Cancelar</a>
                <button type="button" onclick="document.getElementById('form-deferir-<?= $cand['id_candidatura'] ?>').submit();" class="button primary" style="background-color: #28a745;">Deferir</button>
                <button type="button" onclick="if(document.getElementById('justificativa-<?= $cand['id_candidatura'] ?>').value.trim() === '') { alert('Por favor, informe a justificativa para indeferir.'); return false; } document.getElementById('form-indeferir-<?= $cand['id_candidatura'] ?>').submit();" class="button primary" style="background-color: #dc3545;">Indeferir</button>
            </div>
            <?php else: ?>
            <?php if ($cand['status_validacao'] === 'indeferido' && !empty($cand['justificativa_indeferimento'])): ?>
            <div class="form-group">
                <label>Justificativa do Indeferimento</label>
                <textarea readonly><?= htmlspecialchars($cand['justificativa_indeferimento']) ?></textarea>
            </div>
            <?php endif; ?>
            <div class="modal-buttons">
                <a href="#" class="button secondary">Fechar</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>


    <main class="manage-applicants">
        <div class="container">
            <h1 style="font-size: 2em; color: #333; margin-bottom: 10px;">Gerenciar Inscrições</h1>
            <p style="color: #666; margin-bottom: 25px;">Visualize e gerencie todas as candidaturas submetidas</p>

            <form class="form-filters" method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="nome">Nome do Aluno</label>
                        <input id="nome" name="nome" type="text" value="<?= htmlspecialchars($filtro_nome) ?>" placeholder="Digite o nome..." />
                    </div>

                    <div class="filter-group">
                        <label for="curso">Curso</label>
                        <select id="curso" name="curso">
                            <option value="">Todos os Cursos</option>
                            <option value="Desenvolvimento de Software Multiplataforma" <?= $filtro_curso === 'Desenvolvimento de Software Multiplataforma' ? 'selected' : '' ?>>DSM</option>
                            <option value="Gestão Empresarial" <?= $filtro_curso === 'Gestão Empresarial' ? 'selected' : '' ?>>Gestão Empresarial</option>
                            <option value="Gestão da Produção Industrial" <?= $filtro_curso === 'Gestão da Produção Industrial' ? 'selected' : '' ?>>Gestão Produção</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="semestre">Semestre</label>
                        <select id="semestre" name="semestre">
                            <option value="">Todos os Semestres</option>
                            <option value="1" <?= $filtro_semestre === '1' ? 'selected' : '' ?>>1º Semestre</option>
                            <option value="2" <?= $filtro_semestre === '2' ? 'selected' : '' ?>>2º Semestre</option>
                            <option value="3" <?= $filtro_semestre === '3' ? 'selected' : '' ?>>3º Semestre</option>
                            <option value="4" <?= $filtro_semestre === '4' ? 'selected' : '' ?>>4º Semestre</option>
                            <option value="5" <?= $filtro_semestre === '5' ? 'selected' : '' ?>>5º Semestre</option>
                            <option value="6" <?= $filtro_semestre === '6' ? 'selected' : '' ?>>6º Semestre</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">Todos os Status</option>
                            <option value="pendente" <?= $filtro_status === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="deferido" <?= $filtro_status === 'deferido' ? 'selected' : '' ?>>Deferido</option>
                            <option value="indeferido" <?= $filtro_status === 'indeferido' ? 'selected' : '' ?>>Indeferido</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="button primary">Aplicar Filtros</button>
                    <a href="inscricoes.php" class="button secondary">Limpar Filtros</a>
                </div>
            </form>

            <section class="list-applicants">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" class="sortable <?= $sort_column === 'data_inscricao' ? 'active' : '' ?>">
                                <a href="<?= getSortLink('data_inscricao', $sort_column, $sort_order) ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center;">
                                    Data Inscrição
                                    <?= getSortIcon('data_inscricao', $sort_column, $sort_order) ?>
                                </a>
                            </th>
                            <th scope="col" class="sortable <?= $sort_column === 'nome_completo' ? 'active' : '' ?>">
                                <a href="<?= getSortLink('nome_completo', $sort_column, $sort_order) ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center;">
                                    Nome
                                    <?= getSortIcon('nome_completo', $sort_column, $sort_order) ?>
                                </a>
                            </th>
                            <th scope="col" class="sortable <?= $sort_column === 'curso' ? 'active' : '' ?>">
                                <a href="<?= getSortLink('curso', $sort_column, $sort_order) ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center;">
                                    Curso
                                    <?= getSortIcon('curso', $sort_column, $sort_order) ?>
                                </a>
                            </th>
                            <th scope="col" class="sortable <?= $sort_column === 'semestre' ? 'active' : '' ?>">
                                <a href="<?= getSortLink('semestre', $sort_column, $sort_order) ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center;">
                                    Semestre
                                    <?= getSortIcon('semestre', $sort_column, $sort_order) ?>
                                </a>
                            </th>
                            <th scope="col" class="sortable <?= $sort_column === 'status_validacao' ? 'active' : '' ?>">
                                <a href="<?= getSortLink('status_validacao', $sort_column, $sort_order) ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center;">
                                    Status
                                    <?= getSortIcon('status_validacao', $sort_column, $sort_order) ?>
                                </a>
                            </th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($candidaturas) > 0): ?>
                            <?php foreach ($candidaturas as $cand): ?>
                                <?php
                                $status_class = 'status-pendente';
                                $status_texto = 'Pendente';
                                if ($cand['status_validacao'] === 'deferido') {
                                    $status_class = 'status-deferido';
                                    $status_texto = 'Deferido';
                                } elseif ($cand['status_validacao'] === 'indeferido') {
                                    $status_class = 'status-indeferido';
                                    $status_texto = 'Indeferido';
                                }
                                $data_formatada = date('d/m/Y H:i', strtotime($cand['data_inscricao']));
                                ?>
                                <tr>
                                    <td><?= $data_formatada ?></td>
                                    <th scope="row"><?= htmlspecialchars($cand['nome_completo']) ?></th>
                                    <td><?= htmlspecialchars($cand['curso']) ?></td>
                                    <td><?= $cand['semestre'] ?>º Semestre</td>
                                    <td>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= $status_texto ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="#edit-user-modal-<?= $cand['id_candidatura'] ?>" class="action-link">Visualizar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px 20px;">
                                    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 20px; display: inline-block;">
                                        <p style="color: #856404; margin: 10px 0 0 0; font-weight: 500;">
                                            Nenhuma candidatura encontrada.
                                        </p>
                                        <?php if (!empty($filtro_curso) || !empty($filtro_semestre) || !empty($filtro_nome) || !empty($filtro_status)): ?>
                                        <p style="color: #856404; margin: 15px 0 0 0; font-size: 0.9rem;">
                                            Tente ajustar os filtros ou <a href="inscricoes.php" class="button primary" style="display: inline-block; padding: 8px 16px; margin-top: 10px;">limpar a busca</a>.
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                    <?php if ($total_registros > 0): ?>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <div class="pagination">
                                    <div class="results">
                                        Mostrando <?= $primeiro_registro ?> a <?= $ultimo_registro ?> de <?= $total_registros ?> resultado<?= $total_registros != 1 ? 's' : '' ?>
                                    </div>
                                    <?php if ($total_paginas > 1): ?>
                                    <ul>
                                        <?php
                                        // Construir query string com filtros e ordenação
                                        $query_params = [];
                                        if (!empty($filtro_nome)) $query_params[] = 'nome=' . urlencode($filtro_nome);
                                        if (!empty($filtro_curso)) $query_params[] = 'curso=' . urlencode($filtro_curso);
                                        if (!empty($filtro_semestre)) $query_params[] = 'semestre=' . urlencode($filtro_semestre);
                                        if (!empty($filtro_status)) $query_params[] = 'status=' . urlencode($filtro_status);
                                        if (!empty($sort_column)) $query_params[] = 'sort=' . urlencode($sort_column);
                                        if (!empty($sort_order)) $query_params[] = 'order=' . urlencode($sort_order);
                                        $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
                                        ?>

                                        <!-- Botão Anterior -->
                                        <li>
                                            <?php if ($pagina_atual > 1): ?>
                                                <a href="?pagina=<?= $pagina_atual - 1 ?><?= $query_string ?>">‹</a>
                                            <?php else: ?>
                                                <span style="opacity: 0.3; cursor: not-allowed;">‹</span>
                                            <?php endif; ?>
                                        </li>

                                        <?php
                                        // Mostrar até 5 páginas
                                        $inicio = max(1, $pagina_atual - 2);
                                        $fim = min($total_paginas, $pagina_atual + 2);

                                        for ($i = $inicio; $i <= $fim; $i++):
                                        ?>
                                            <li>
                                                <a href="?pagina=<?= $i ?><?= $query_string ?>"
                                                   <?= $i == $pagina_atual ? 'style="background: #005f73; color: white; font-weight: bold;"' : '' ?>>
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <!-- Botão Próximo -->
                                        <li>
                                            <?php if ($pagina_atual < $total_paginas): ?>
                                                <a href="?pagina=<?= $pagina_atual + 1 ?><?= $query_string ?>">›</a>
                                            <?php else: ?>
                                                <span style="opacity: 0.3; cursor: not-allowed;">›</span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </section>
        </div>
    </main>

    <footer class="site">
        <div class="content">
            <img src="../../assets/images/logo-governo-do-estado-sp.png" alt="Logo Governo SP" class="logo-governo">
            <a href="../../pages/guest/sobre.html" class="btn-about">SOBRE O SISTEMA</a>
            <p>Sistema Integrado de Votação - FATEC/CPS</p>
            <p>Versão 0.1 (11/06/2025)</p>
        </div>
    </footer>

    <script>
    // Fechar modal ao clicar fora do conteúdo
    document.addEventListener('DOMContentLoaded', function() {
        const modals = document.querySelectorAll('.modal');

        modals.forEach(function(modal) {
            modal.addEventListener('click', function(e) {
                // Se clicou no backdrop (fora do .content), fecha o modal
                if (e.target === modal) {
                    window.location.hash = '';
                }
            });
        });

        // Fechar modal com tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && window.location.hash) {
                window.location.hash = '';
            }
        });
    });
    </script>
</body>

</html>
