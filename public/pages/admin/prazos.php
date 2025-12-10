<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';
require_once '../../../config/helpers.php';

// Verificar se é administrador
verificarAdmin();

$usuario = obterUsuarioLogado();
$id_admin = $usuario['id'];

$mensagem = "";
$tipo_mensagem = ""; // success | error

// Verificar se houve sucesso via GET (após redirect)
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $count = intval($_GET['count'] ?? 1);
    if ($count > 1) {
        $mensagem = "$count eleições cadastradas com sucesso!";
    } else {
        $mensagem = "Eleição cadastrada com sucesso!";
    }
    $tipo_mensagem = "success";
}

// Buscar eleições existentes (ativas e futuras)
$sql_eleicoes = "SELECT
    id_eleicao,
    curso,
    semestre,
    data_inicio_candidatura,
    data_fim_candidatura,
    data_inicio_votacao,
    data_fim_votacao,
    status
FROM ELEICAO
WHERE status IN ('candidatura_aberta', 'votacao_aberta', 'aguardando_finalizacao')
   OR data_inicio_candidatura >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
ORDER BY data_inicio_candidatura DESC
LIMIT 50";

$stmt_eleicoes = $conn->prepare($sql_eleicoes);
$stmt_eleicoes->execute();
$eleicoes_existentes = $stmt_eleicoes->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // VALIDAR CSRF PRIMEIRO
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente criar a eleição novamente.");

    $curso            = $_POST['curso'] ?? '';
    $semestre         = $_POST['semestre'] ?? '';
    $inscricao_inicio = $_POST['inscricao_inicio'] ?? '';
    $inscricao_fim    = $_POST['inscricao_fim'] ?? '';
    $votacao_inicio   = $_POST['votacao_inicio'] ?? '';
    $votacao_fim      = $_POST['votacao_fim'] ?? '';

    $hoje = date("Y-m-d");

    // ============================
    // VALIDAÇÕES
    // ============================

    if ($inscricao_inicio < $hoje) {
        $mensagem = "A data de <b>início das inscrições</b> não pode ser anterior a hoje.";
        $tipo_mensagem = "error";
    } elseif ($inscricao_fim < $inscricao_inicio) {
        $mensagem = "A data de <b>término das inscrições</b> deve ser maior ou igual ao início.";
        $tipo_mensagem = "error";
    } elseif ($votacao_inicio <= $inscricao_fim) {
        $mensagem = "A data de <b>início da votação</b> deve ser posterior ao fim das inscrições.";
        $tipo_mensagem = "error";
    } elseif ($votacao_fim < $votacao_inicio) {
        $mensagem = "A data de <b>término da votação</b> deve ser maior ou igual que o início da votação.";
        $tipo_mensagem = "error";
    }

    //  SEM ERRO → salva
    if ($tipo_mensagem !== "error") {

        // ============================
        // ✔ SALVAR NO BANCO
        // ============================

        // Determinar lista de cursos
        if ($curso === "Todos os Cursos") {
            $lista_cursos = ["DSM", "GE", "GPI"];
        } else {
            $lista_cursos = [$curso];
        }

        // Determinar lista de semestres
        if ($semestre === "todos") {
            $lista_semestres = [1, 2, 3, 4, 5, 6];
        } else {
            $lista_semestres = [intval($semestre)];
        }

        $status = "candidatura_aberta";

        // Verificar se a coluna lote_criacao existe no banco
        $coluna_lote_existe = false;
        try {
            $check_col = $conn->query("SHOW COLUMNS FROM ELEICAO LIKE 'lote_criacao'");
            $coluna_lote_existe = $check_col->rowCount() > 0;
        } catch (Exception $e) {
            // Se houver erro ao verificar, assume que não existe
            $coluna_lote_existe = false;
        }

        // Gerar identificador único de lote se for criação múltipla
        $lote_criacao = null;
        if ($coluna_lote_existe && (count($lista_cursos) > 1 || count($lista_semestres) > 1)) {
            $lote_criacao = md5(uniqid($id_admin . time(), true));
        }

        // Montar SQL dinamicamente baseado na existência da coluna
        if ($coluna_lote_existe) {
            $sql_insert = "
                INSERT INTO ELEICAO
                (curso, semestre, data_inicio_candidatura, data_fim_candidatura,
                 data_inicio_votacao, data_fim_votacao, status, criado_por, lote_criacao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
        } else {
            $sql_insert = "
                INSERT INTO ELEICAO
                (curso, semestre, data_inicio_candidatura, data_fim_candidatura,
                 data_inicio_votacao, data_fim_votacao, status, criado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";
        }

        $stmt = $conn->prepare($sql_insert);

        try {
            $eleicoes_criadas = [];

            // Loop duplo: cursos x semestres
            foreach ($lista_cursos as $c) {
                foreach ($lista_semestres as $s) {
                    // Preparar parâmetros baseado na existência da coluna
                    if ($coluna_lote_existe) {
                        $params = [
                            $c,
                            $s,
                            $inscricao_inicio,
                            $inscricao_fim,
                            $votacao_inicio,
                            $votacao_fim,
                            $status,
                            $id_admin,
                            $lote_criacao
                        ];
                    } else {
                        $params = [
                            $c,
                            $s,
                            $inscricao_inicio,
                            $inscricao_fim,
                            $votacao_inicio,
                            $votacao_fim,
                            $status,
                            $id_admin
                        ];
                    }

                    $stmt->execute($params);

                    $id_eleicao_criada = $conn->lastInsertId();
                    $eleicoes_criadas[] = [
                        'id_eleicao' => $id_eleicao_criada,
                        'curso' => $c,
                        'semestre' => $s
                    ];
                }
            }

            // Registrar auditoria com dados completos
            foreach ($eleicoes_criadas as $eleicao) {
                registrarAuditoria(
                    $conn,
                    $id_admin,
                    'ELEICAO',
                    'INSERT',
                    "Criou eleição #{$eleicao['id_eleicao']} para {$eleicao['curso']} {$eleicao['semestre']}º semestre",
                    null,  // IP detectado automaticamente
                    $eleicao['id_eleicao'],  // ID da eleição criada
                    null,  // Sem dados anteriores (é INSERT)
                    json_encode([
                        'id_eleicao' => $eleicao['id_eleicao'],
                        'curso' => $eleicao['curso'],
                        'semestre' => $eleicao['semestre'],
                        'data_inicio_candidatura' => $inscricao_inicio,
                        'data_fim_candidatura' => $inscricao_fim,
                        'data_inicio_votacao' => $votacao_inicio,
                        'data_fim_votacao' => $votacao_fim,
                        'status' => $status,
                        'criado_por' => $id_admin,
                        'lote_criacao' => $lote_criacao
                    ])
                );
            }

            // Redirecionar para evitar resubmissão do formulário (padrão PRG: Post-Redirect-Get)
            $total_criadas = count($eleicoes_criadas);
            header("Location: prazos.php?success=1&count=$total_criadas");
            exit();
        } catch (PDOException $e) {
            // Logar erro completo para debug
            error_log("Erro ao cadastrar eleição/prazo: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Mensagens específicas baseadas no tipo de erro
            $erro_msg = $e->getMessage();

            if (strpos($erro_msg, 'Duplicate entry') !== false) {
                $mensagem = "Já existe uma eleição cadastrada para este curso e semestre.";
            } elseif (strpos($erro_msg, 'data_inicio_votacao deve ser posterior a data_fim_candidatura') !== false) {
                $mensagem = "A data de <b>início da votação</b> deve ser posterior ao término das inscrições/candidatura.";
            } elseif (strpos($erro_msg, 'data_inicio_candidatura deve ser anterior a data_fim_candidatura') !== false) {
                $mensagem = "A data de <b>início das inscrições</b> deve ser anterior ao término.";
            } elseif (strpos($erro_msg, 'Unknown column') !== false && strpos($erro_msg, 'lote_criacao') !== false) {
                $mensagem = "Erro de estrutura do banco de dados. Execute o script de atualização do schema (ALTER TABLE ELEICAO).";
            } elseif (strpos($erro_msg, 'Já existe eleição') !== false) {
                $mensagem = $erro_msg; // Mensagem do trigger já é amigável
            } elseif (strpos($erro_msg, 'chk_ordem_fases') !== false) {
                $mensagem = "A data de <b>início da votação</b> deve ser posterior ao fim das inscrições.";
            } else {
                $mensagem = "Erro ao processar cadastro da eleição. Verifique os dados e tente novamente.<br><small>Detalhe técnico: " . htmlspecialchars($e->getMessage()) . "</small>";
            }
            $tipo_mensagem = "error";
        }
    }
}

// Mapear siglas para nomes completos
function obterNomeCurso($sigla) {
    $cursos = [
        'DSM' => 'Desenvolvimento de Software Multiplataforma',
        'GE' => 'Gestão Empresarial',
        'GPI' => 'Gestão da Produção Industrial',
        // Compatibilidade reversa
        'Desenvolvimento de Software Multiplataforma' => 'Desenvolvimento de Software Multiplataforma',
        'Gestão Empresarial' => 'Gestão Empresarial',
        'Gestão da Produção Industrial' => 'Gestão da Produção Industrial'
    ];
    return $cursos[$sigla] ?? $sigla;
}

// Mapear status
function obterStatusLegivel($status) {
    $status_map = [
        'candidatura_aberta' => 'Inscrições Abertas',
        'votacao_aberta' => 'Votação Aberta',
        'aguardando_finalizacao' => 'Aguardando Apuração',
        'encerrada' => 'Encerrada'
    ];
    return $status_map[$status] ?? $status;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Gerenciar Prazos de Eleições</title>

    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">

    <style>
        /* Layout principal */
        .manage-deadlines {
            padding: 30px 20px;
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
            font-size: 1.05em;
        }

        /* Grid de 2 colunas */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Eleições Existentes */
        .eleicoes-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 600px;
        }

        .eleicoes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .eleicoes-title {
            font-size: 1.3em;
            color: #333;
            font-weight: 600;
        }

        .eleicoes-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .filter-btn {
            padding: 6px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85em;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background: #f5f5f5;
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .eleicoes-list {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
        }

        .eleicao-item {
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 10px;
            transition: all 0.2s;
        }

        .eleicao-item:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .eleicao-header-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .eleicao-titulo {
            font-weight: 600;
            color: #333;
            font-size: 0.95em;
        }

        .eleicao-status {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
        }

        .status-candidatura_aberta {
            background: #d4edda;
            color: #155724;
        }

        .status-votacao_aberta {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-aguardando_finalizacao {
            background: #fff3cd;
            color: #856404;
        }

        .eleicao-datas {
            font-size: 0.8em;
            color: #666;
            line-height: 1.6;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        /* Card do Formulário */
        .form-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Inputs com validação visual */
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333;
            font-size: 0.95em;
        }

        .input-group input[type="date"],
        .input-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 0.95em;
            transition: all 0.2s;
        }

        .input-group input[type="date"]:focus,
        .input-group select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .input-group input.valid {
            border-color: #28a745;
        }

        .input-group input.invalid {
            border-color: #dc3545;
        }

        .input-feedback {
            font-size: 0.85em;
            margin-top: 5px;
            min-height: 18px;
        }

        .feedback-success {
            color: #28a745;
        }

        .feedback-error {
            color: #dc3545;
        }

        .feedback-info {
            color: #666;
        }

        /* Timeline Visual */
        .timeline-preview {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #e0e0e0;
        }

        .timeline-title {
            font-size: 0.95em;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .timeline-bar {
            position: relative;
            height: 60px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .timeline-segment {
            position: absolute;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.85em;
            font-weight: 600;
            transition: all 0.3s;
        }

        .timeline-inscricao {
            background: #28a745;
            left: 0;
        }

        .timeline-votacao {
            background: var(--primary);
        }

        .timeline-info {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            font-size: 0.85em;
            color: #666;
        }

        /* Aviso sobre "Todos os Cursos" */
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 12px 15px;
            margin: 15px 0;
            display: none;
        }

        .warning-box.show {
            display: block;
        }

        .warning-box-title {
            font-weight: 600;
            color: #856404;
            margin-bottom: 5px;
            font-size: 0.9em;
        }

        .warning-box-text {
            color: #856404;
            font-size: 0.85em;
        }

        /* Botões do formulário */
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95em;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #004654;
        }

        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-clear {
            background: #dc3545;
            color: white;
            border: none;
            transition: background 0.3s ease;
        }

        .btn-clear:hover {
            background: #c82333;
        }

        /* Modal de Confirmação */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 5000;
            animation: fadeIn 0.2s;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            animation: slideUp 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 8px;
        }

        .modal-subtitle {
            color: #666;
            font-size: 0.95em;
        }

        .modal-body {
            margin-bottom: 25px;
        }

        .preview-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .preview-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 4px;
        }

        .preview-value {
            font-weight: 600;
            color: #333;
        }

        .preview-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }

        .preview-list li {
            padding: 8px 12px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-bottom: 6px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        /* Modal de Feedback */
        .feedback-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,.65);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5000;
            animation: fadeIn 0.2s;
        }

        .feedback-modal-content {
            background: #fff;
            width: 420px;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 0 20px rgba(0,0,0,.2);
            animation: slideUp 0.3s;
        }

        .feedback-modal h3 {
            margin-bottom: 15px;
            font-size: 1.4em;
        }

        .feedback-modal button {
            margin-top: 15px;
            background: #b20000;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .feedback-modal button:hover {
            background: #8b0000;
        }
    </style>
</head>

<body>

<?php if (!empty($mensagem)): ?>
<div class="feedback-modal" id="feedbackModal">
    <div class="feedback-modal-content">
        <h3><?= $tipo_mensagem === "error" ? "Erro" : "Sucesso" ?></h3>
        <p><?= $mensagem ?></p>
        <button onclick="closeFeedbackModal()">OK</button>
    </div>
</div>
<?php endif; ?>

<?php require_once 'components/header.php'; ?>

<main class="manage-deadlines">
    <div class="page-header">
        <h1 class="page-title">Gerenciar Eleições</h1>
        <p class="page-subtitle">Crie novas eleições e visualize as existentes</p>
    </div>

    <div class="content-grid">
        <!-- Lista de Eleições Existentes -->
        <div class="eleicoes-card">
            <div class="eleicoes-header">
                <h2 class="eleicoes-title">Eleições Existentes</h2>
                <span style="font-size: 0.85em; color: #666;"><?= count($eleicoes_existentes) ?> eleições</span>
            </div>

            <div class="eleicoes-filters">
                <button class="filter-btn active" data-filter="all">Todas</button>
                <button class="filter-btn" data-filter="candidatura_aberta">Inscrições</button>
                <button class="filter-btn" data-filter="votacao_aberta">Votação</button>
                <button class="filter-btn" data-filter="aguardando_finalizacao">Aguardando</button>
            </div>

            <div class="eleicoes-list" id="eleicoesList">
                <?php if (count($eleicoes_existentes) > 0): ?>
                    <?php foreach($eleicoes_existentes as $eleicao): ?>
                        <div class="eleicao-item" data-status="<?= $eleicao['status'] ?>">
                            <div class="eleicao-header-item">
                                <div class="eleicao-titulo">
                                    <?= obterNomeCurso($eleicao['curso']) ?> - <?= $eleicao['semestre'] ?>º Semestre
                                </div>
                                <span class="eleicao-status status-<?= $eleicao['status'] ?>">
                                    <?= obterStatusLegivel($eleicao['status']) ?>
                                </span>
                            </div>
                            <div class="eleicao-datas">
                                <div>Inscrições: <?= date('d/m/Y', strtotime($eleicao['data_inicio_candidatura'])) ?> - <?= date('d/m/Y', strtotime($eleicao['data_fim_candidatura'])) ?></div>
                                <div>Votação: <?= date('d/m/Y', strtotime($eleicao['data_inicio_votacao'])) ?> - <?= date('d/m/Y', strtotime($eleicao['data_fim_votacao'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Nenhuma eleição cadastrada ainda</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulário de Nova Eleição -->
        <div class="form-card">
            <h2 class="form-title">Criar Nova Eleição</h2>

            <form id="formPrazos" method="POST" novalidate>
                <?= campoCSRF() ?>

                <div class="input-group">
                    <label for="curso">Curso *</label>
                    <select name="curso" id="curso" required>
                        <option value="">Selecione o curso</option>
                        <option value="DSM">DSM - Desenvolvimento de Software Multiplataforma</option>
                        <option value="GE">GE - Gestão Empresarial</option>
                        <option value="GPI">GPI - Gestão da Produção Industrial</option>
                        <option value="Todos os Cursos">Todos os Cursos</option>
                    </select>
                </div>

                <div class="warning-box" id="warningAllCourses">
                    <div class="warning-box-title">Atenção!</div>
                    <div class="warning-box-text">
                        Ao selecionar "Todos os Cursos", serão criadas <strong>3 eleições simultâneas</strong> (DSM, GE e GPI) com os mesmos prazos.
                    </div>
                </div>

                <div class="input-group">
                    <label for="semestre">Semestre *</label>
                    <select name="semestre" id="semestre" required>
                        <option value="">Selecione o semestre</option>
                        <option value="1">1º Semestre</option>
                        <option value="2">2º Semestre</option>
                        <option value="3">3º Semestre</option>
                        <option value="4">4º Semestre</option>
                        <option value="5">5º Semestre</option>
                        <option value="6">6º Semestre</option>
                        <option value="todos">Todos os Semestres</option>
                    </select>
                </div>

                <div class="warning-box" id="warningAllSemesters">
                    <div class="warning-box-title">Atenção!</div>
                    <div class="warning-box-text">
                        Ao selecionar "Todos os Semestres", serão criadas <strong>6 eleições simultâneas</strong> (1º ao 6º semestre) com os mesmos prazos.
                    </div>
                </div>

                <div class="input-group">
                    <label for="inscricao_inicio">Início das Inscrições *</label>
                    <input type="date" name="inscricao_inicio" id="inscricao_inicio" required>
                    <div class="input-feedback" id="feedback-inscricao-inicio"></div>
                </div>

                <div class="input-group">
                    <label for="inscricao_fim">Fim das Inscrições *</label>
                    <input type="date" name="inscricao_fim" id="inscricao_fim" required>
                    <div class="input-feedback" id="feedback-inscricao-fim"></div>
                </div>

                <div class="input-group">
                    <label for="votacao_inicio">Início da Votação *</label>
                    <input type="date" name="votacao_inicio" id="votacao_inicio" required>
                    <div class="input-feedback" id="feedback-votacao-inicio"></div>
                </div>

                <div class="input-group">
                    <label for="votacao_fim">Fim da Votação *</label>
                    <input type="date" name="votacao_fim" id="votacao_fim" required>
                    <div class="input-feedback" id="feedback-votacao-fim"></div>
                </div>

                <!-- Timeline Visual -->
                <div class="timeline-preview" id="timelinePreview" style="display: none;">
                    <div class="timeline-title">Visualização da Linha do Tempo</div>
                    <div class="timeline-bar">
                        <div class="timeline-segment timeline-inscricao" id="timelineInscricao"></div>
                        <div class="timeline-segment timeline-votacao" id="timelineVotacao"></div>
                    </div>
                    <div class="timeline-info">
                        <span id="duracao-inscricao"></span>
                        <span id="duracao-votacao"></span>
                    </div>
                    <div class="warning-box" id="warningDuracaoMinima" style="display: none; margin-top: 15px; background: #f8d7da; border-color: #dc3545;">
                        <div class="warning-box-title" style="color: #721c24;">⚠️ Duração Insuficiente!</div>
                        <div class="warning-box-text" style="color: #721c24;">
                            A duração total da eleição deve ser de <strong>no mínimo 7 dias</strong> (do início das inscrições ao fim da votação). Duração atual: <strong id="duracaoAtual">0 dias</strong>.
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-clear" onclick="limparFormulario()">Limpar</button>
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="button" class="btn btn-primary" id="btnSubmit" onclick="mostrarPreview()">Criar Eleição</button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Modal de Confirmação -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirmar Criação de Eleição</h3>
            <p class="modal-subtitle">Revise os dados antes de confirmar</p>
        </div>
        <div class="modal-body" id="modalPreviewContent">
            <!-- Conteúdo será preenchido via JavaScript -->
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="confirmarCriacao()">Confirmar Criação</button>
        </div>
    </div>
</div>

<script>
// ====================================
// CONFIGURAÇÃO INICIAL
// ====================================

const hoje = new Date().toISOString().split('T')[0];
let formData = {};

// Definir data mínima para os inputs
document.getElementById('inscricao_inicio').min = hoje;

// ====================================
// PERSISTÊNCIA COM LOCALSTORAGE
// ====================================

function salvarRascunho() {
    const dados = {
        curso: document.getElementById('curso').value,
        semestre: document.getElementById('semestre').value,
        inscricao_inicio: document.getElementById('inscricao_inicio').value,
        inscricao_fim: document.getElementById('inscricao_fim').value,
        votacao_inicio: document.getElementById('votacao_inicio').value,
        votacao_fim: document.getElementById('votacao_fim').value,
        timestamp: new Date().getTime()
    };
    localStorage.setItem('prazos_rascunho', JSON.stringify(dados));
}

function carregarRascunho() {
    const rascunho = localStorage.getItem('prazos_rascunho');
    if (rascunho) {
        const dados = JSON.parse(rascunho);
        // Verificar se não é muito antigo (> 24h)
        const umDia = 24 * 60 * 60 * 1000;
        if (new Date().getTime() - dados.timestamp < umDia) {
            document.getElementById('curso').value = dados.curso || '';
            document.getElementById('semestre').value = dados.semestre || '';
            document.getElementById('inscricao_inicio').value = dados.inscricao_inicio || '';
            document.getElementById('inscricao_fim').value = dados.inscricao_fim || '';
            document.getElementById('votacao_inicio').value = dados.votacao_inicio || '';
            document.getElementById('votacao_fim').value = dados.votacao_fim || '';

            // Trigger validações
            validarTudo();
        }
    }
}

// ====================================
// VALIDAÇÕES EM TEMPO REAL
// ====================================

function calcularDias(dataInicio, dataFim) {
    const inicio = new Date(dataInicio);
    const fim = new Date(dataFim);
    const diff = fim - inicio;
    return Math.ceil(diff / (1000 * 60 * 60 * 24)) + 1; // +1 para incluir o dia final
}

function validarInscricaoInicio() {
    const input = document.getElementById('inscricao_inicio');
    const feedback = document.getElementById('feedback-inscricao-inicio');
    const valor = input.value;

    if (!valor) {
        input.className = '';
        feedback.textContent = '';
        return false;
    }

    if (valor < hoje) {
        input.className = 'invalid';
        feedback.className = 'input-feedback feedback-error';
        feedback.textContent = 'A data não pode ser anterior a hoje';
        return false;
    }

    input.className = 'valid';
    feedback.className = 'input-feedback feedback-success';
    const dias = calcularDias(hoje, valor);
    feedback.textContent = dias === 1 ? 'Começa hoje' : `Começa em ${dias - 1} dias`;
    return true;
}

function validarInscricaoFim() {
    const inputInicio = document.getElementById('inscricao_inicio');
    const input = document.getElementById('inscricao_fim');
    const feedback = document.getElementById('feedback-inscricao-fim');
    const valor = input.value;

    if (!valor) {
        input.className = '';
        feedback.textContent = '';
        return false;
    }

    if (!inputInicio.value) {
        input.className = '';
        feedback.className = 'input-feedback feedback-info';
        feedback.textContent = 'Preencha a data de início primeiro';
        return false;
    }

    if (valor < inputInicio.value) {
        input.className = 'invalid';
        feedback.className = 'input-feedback feedback-error';
        feedback.textContent = 'Deve ser maior ou igual ao início';
        return false;
    }

    input.className = 'valid';
    feedback.className = 'input-feedback feedback-success';
    const dias = calcularDias(inputInicio.value, valor);
    feedback.textContent = `Período de ${dias} dias`;
    return true;
}

function validarVotacaoInicio() {
    const inputInscricaoFim = document.getElementById('inscricao_fim');
    const input = document.getElementById('votacao_inicio');
    const feedback = document.getElementById('feedback-votacao-inicio');
    const valor = input.value;

    if (!valor) {
        input.className = '';
        feedback.textContent = '';
        return false;
    }

    if (!inputInscricaoFim.value) {
        input.className = '';
        feedback.className = 'input-feedback feedback-info';
        feedback.textContent = 'Preencha o fim das inscrições primeiro';
        return false;
    }

    if (valor <= inputInscricaoFim.value) {
        input.className = 'invalid';
        feedback.className = 'input-feedback feedback-error';
        feedback.textContent = 'Deve ser posterior ao fim das inscrições';
        return false;
    }

    input.className = 'valid';
    feedback.className = 'input-feedback feedback-success';
    const intervalo = calcularDias(inputInscricaoFim.value, valor);
    feedback.textContent = `${intervalo - 1} dias após o fim das inscrições`;
    return true;
}

function validarVotacaoFim() {
    const inputInicio = document.getElementById('votacao_inicio');
    const input = document.getElementById('votacao_fim');
    const feedback = document.getElementById('feedback-votacao-fim');
    const valor = input.value;

    if (!valor) {
        input.className = '';
        feedback.textContent = '';
        return false;
    }

    if (!inputInicio.value) {
        input.className = '';
        feedback.className = 'input-feedback feedback-info';
        feedback.textContent = 'Preencha a data de início primeiro';
        return false;
    }

    if (valor < inputInicio.value) {
        input.className = 'invalid';
        feedback.className = 'input-feedback feedback-error';
        feedback.textContent = 'Deve ser maior ou igual ao início';
        return false;
    }

    input.className = 'valid';
    feedback.className = 'input-feedback feedback-success';
    const dias = calcularDias(inputInicio.value, valor);
    feedback.textContent = `Período de ${dias} dias`;
    return true;
}

function validarTudo() {
    const v1 = validarInscricaoInicio();
    const v2 = validarInscricaoFim();
    const v3 = validarVotacaoInicio();
    const v4 = validarVotacaoFim();

    atualizarTimeline();

    // Habilitar/desabilitar botão submit
    const btnSubmit = document.getElementById('btnSubmit');
    const curso = document.getElementById('curso').value;
    const semestre = document.getElementById('semestre').value;

    // VALIDAÇÃO ADICIONAL: Duração mínima total de 7 dias
    let duracaoValida = true;
    const inscricaoInicio = document.getElementById('inscricao_inicio').value;
    const votacaoFim = document.getElementById('votacao_fim').value;

    if (inscricaoInicio && votacaoFim) {
        const totalDias = calcularDias(inscricaoInicio, votacaoFim);
        if (totalDias < 7) {
            duracaoValida = false;
        }
    }

    if (v1 && v2 && v3 && v4 && curso && semestre && duracaoValida) {
        btnSubmit.disabled = false;
    } else {
        btnSubmit.disabled = true;
    }
}

// ====================================
// TIMELINE VISUAL
// ====================================

function atualizarTimeline() {
    const inscricaoInicio = document.getElementById('inscricao_inicio').value;
    const inscricaoFim = document.getElementById('inscricao_fim').value;
    const votacaoInicio = document.getElementById('votacao_inicio').value;
    const votacaoFim = document.getElementById('votacao_fim').value;

    if (!inscricaoInicio || !inscricaoFim || !votacaoInicio || !votacaoFim) {
        document.getElementById('timelinePreview').style.display = 'none';
        return;
    }

    document.getElementById('timelinePreview').style.display = 'block';

    const totalDias = calcularDias(inscricaoInicio, votacaoFim);
    const diasInscricao = calcularDias(inscricaoInicio, inscricaoFim);
    const diasVotacao = calcularDias(votacaoInicio, votacaoFim);

    const percentInscricao = (diasInscricao / totalDias) * 100;
    const percentVotacao = (diasVotacao / totalDias) * 100;

    const timelineInscricao = document.getElementById('timelineInscricao');
    const timelineVotacao = document.getElementById('timelineVotacao');

    timelineInscricao.style.width = percentInscricao + '%';
    timelineInscricao.textContent = `Inscrições (${diasInscricao}d)`;

    timelineVotacao.style.width = percentVotacao + '%';
    timelineVotacao.style.left = percentInscricao + '%';
    timelineVotacao.textContent = `Votação (${diasVotacao}d)`;

    // Mostrar aviso se duração total for menor que 7 dias
    const warningDuracao = document.getElementById('warningDuracaoMinima');
    if (totalDias < 7) {
        document.getElementById('duracao-inscricao').style.color = '#dc3545';
        document.getElementById('duracao-votacao').style.color = '#dc3545';
        warningDuracao.style.display = 'block';
        document.getElementById('duracaoAtual').textContent = `${totalDias} dias`;
    } else {
        document.getElementById('duracao-inscricao').style.color = '';
        document.getElementById('duracao-votacao').style.color = '';
        warningDuracao.style.display = 'none';
    }

    document.getElementById('duracao-inscricao').textContent = `${diasInscricao} dias de inscrição`;
    document.getElementById('duracao-votacao').textContent = `${diasVotacao} dias de votação (Total: ${totalDias} dias)`;
}

// ====================================
// AVISO "TODOS OS CURSOS" E "TODOS OS SEMESTRES"
// ====================================

document.getElementById('curso').addEventListener('change', function() {
    const warning = document.getElementById('warningAllCourses');
    if (this.value === 'Todos os Cursos') {
        warning.classList.add('show');
    } else {
        warning.classList.remove('show');
    }
    salvarRascunho();
    validarTudo();
});

document.getElementById('semestre').addEventListener('change', function() {
    const warning = document.getElementById('warningAllSemesters');
    if (this.value === 'todos') {
        warning.classList.add('show');
    } else {
        warning.classList.remove('show');
    }
    salvarRascunho();
    validarTudo();
});

// ====================================
// EVENT LISTENERS
// ====================================

document.getElementById('inscricao_inicio').addEventListener('change', function() {
    validarTudo();
    salvarRascunho();
});

document.getElementById('inscricao_fim').addEventListener('change', function() {
    validarTudo();
    salvarRascunho();
});

document.getElementById('votacao_inicio').addEventListener('change', function() {
    validarTudo();
    salvarRascunho();
});

document.getElementById('votacao_fim').addEventListener('change', function() {
    validarTudo();
    salvarRascunho();
});

// Validar também quando outros campos forem alterados
document.getElementById('curso').addEventListener('input', validarTudo);
document.getElementById('semestre').addEventListener('input', validarTudo);

// ====================================
// FILTROS DE ELEIÇÕES
// ====================================

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Atualizar botão ativo
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        const filtro = this.dataset.filter;
        const items = document.querySelectorAll('.eleicao-item');

        items.forEach(item => {
            if (filtro === 'all' || item.dataset.status === filtro) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

// ====================================
// MODAL DE CONFIRMAÇÃO
// ====================================

function mostrarPreview() {
    const curso = document.getElementById('curso');
    const semestre = document.getElementById('semestre');
    const inscricaoInicio = document.getElementById('inscricao_inicio').value;
    const inscricaoFim = document.getElementById('inscricao_fim').value;
    const votacaoInicio = document.getElementById('votacao_inicio').value;
    const votacaoFim = document.getElementById('votacao_fim').value;

    const cursoTexto = curso.options[curso.selectedIndex].text;
    const semestreTexto = semestre.options[semestre.selectedIndex].text;

    // Calcular total de eleições que serão criadas
    const numCursos = (curso.value === 'Todos os Cursos') ? 3 : 1;
    const numSemestres = (semestre.value === 'todos') ? 6 : 1;
    const totalEleicoes = numCursos * numSemestres;

    let html = '';

    // Mostrar aviso se criar múltiplas eleições
    if (totalEleicoes > 1) {
        html += '<div class="preview-item" style="background-color: #fff3cd; padding: 15px; margin-bottom: 15px;">';
        html += '<div class="preview-label" style="color: #856404; font-weight: bold;">⚠️ Atenção: Criação em Lote</div>';
        html += '<div class="preview-value" style="color: #856404;">';
        html += 'Serão criadas <strong>' + totalEleicoes + ' eleições simultâneas</strong> ';
        html += '(' + numCursos + ' curso' + (numCursos > 1 ? 's' : '') + ' × ';
        html += numSemestres + ' semestre' + (numSemestres > 1 ? 's' : '') + ')';
        html += '</div>';
        html += '</div>';
    }

    html += '<div class="preview-item">';
    html += '<div class="preview-label">Curso(s)</div>';

    if (curso.value === 'Todos os Cursos') {
        html += '<div class="preview-value">Todos os cursos:</div>';
        html += '<ul class="preview-list">';
        html += '<li>DSM - Desenvolvimento de Software Multiplataforma</li>';
        html += '<li>GE - Gestão Empresarial</li>';
        html += '<li>GPI - Gestão da Produção Industrial</li>';
        html += '</ul>';
    } else {
        html += '<div class="preview-value">' + cursoTexto + '</div>';
    }
    html += '</div>';

    html += '<div class="preview-item">';
    html += '<div class="preview-label">Semestre(s)</div>';

    if (semestre.value === 'todos') {
        html += '<div class="preview-value">Todos os semestres:</div>';
        html += '<ul class="preview-list">';
        html += '<li>1º Semestre</li>';
        html += '<li>2º Semestre</li>';
        html += '<li>3º Semestre</li>';
        html += '<li>4º Semestre</li>';
        html += '<li>5º Semestre</li>';
        html += '<li>6º Semestre</li>';
        html += '</ul>';
    } else {
        html += '<div class="preview-value">' + semestreTexto + '</div>';
    }
    html += '</div>';

    html += '<div class="preview-item">';
    html += '<div class="preview-label">Período de Inscrições</div>';
    html += '<div class="preview-value">' + formatarData(inscricaoInicio) + ' até ' + formatarData(inscricaoFim);
    html += ' (' + calcularDias(inscricaoInicio, inscricaoFim) + ' dias)</div>';
    html += '</div>';

    html += '<div class="preview-item">';
    html += '<div class="preview-label">Período de Votação</div>';
    html += '<div class="preview-value">' + formatarData(votacaoInicio) + ' até ' + formatarData(votacaoFim);
    html += ' (' + calcularDias(votacaoInicio, votacaoFim) + ' dias)</div>';
    html += '</div>';

    // Aviso sobre permanência das eleições
    html += '<div class="preview-item" style="background-color: #e7f3ff; padding: 15px; margin-top: 15px;">';
    html += '<div class="preview-label" style="color: #004085; font-weight: bold;">📌 Informação Importante</div>';
    html += '<div class="preview-value" style="color: #004085; font-size: 0.9em;">';
    html += 'As eleições criadas são <strong>permanentes</strong> e não podem ser facilmente removidas. ';
    html += 'Certifique-se de que todos os prazos estão corretos antes de confirmar.';
    html += '</div>';
    html += '</div>';

    // Checkbox de confirmação obrigatório para criação em lote
    if (totalEleicoes > 1) {
        html += '<div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px;">';
        html += '<label style="display: flex; align-items: start; gap: 10px; cursor: pointer;">';
        html += '<input type="checkbox" id="confirmCheckbox" style="margin-top: 3px; width: 18px; height: 18px; cursor: pointer;" onchange="toggleConfirmButton()">';
        html += '<span style="color: #856404; font-size: 0.95em;">';
        html += 'Confirmo que revisei os prazos e estou ciente que <strong>' + totalEleicoes + ' eleições permanentes</strong> serão criadas ';
        html += '(não poderão ser excluídas facilmente do sistema).';
        html += '</span>';
        html += '</label>';
        html += '</div>';
    }

    document.getElementById('modalPreviewContent').innerHTML = html;
    document.getElementById('confirmModal').classList.add('show');

    // Desabilitar botão de confirmação se for criação em lote
    const btnConfirm = document.querySelector('#confirmModal .btn-primary');
    if (totalEleicoes > 1) {
        btnConfirm.disabled = true;
        btnConfirm.style.opacity = '0.5';
        btnConfirm.style.cursor = 'not-allowed';
    } else {
        btnConfirm.disabled = false;
        btnConfirm.style.opacity = '1';
        btnConfirm.style.cursor = 'pointer';
    }
}

function fecharModal() {
    document.getElementById('confirmModal').classList.remove('show');
}

function toggleConfirmButton() {
    const checkbox = document.getElementById('confirmCheckbox');
    const btnConfirm = document.querySelector('#confirmModal .btn-primary');

    if (checkbox && checkbox.checked) {
        btnConfirm.disabled = false;
        btnConfirm.style.opacity = '1';
        btnConfirm.style.cursor = 'pointer';
    } else if (checkbox) {
        btnConfirm.disabled = true;
        btnConfirm.style.opacity = '0.5';
        btnConfirm.style.cursor = 'not-allowed';
    }
}

function confirmarCriacao() {
    const btnConfirm = document.querySelector('#confirmModal .btn-primary');
    if (btnConfirm.disabled) {
        return; // Não submeter se botão estiver desabilitado
    }
    document.getElementById('formPrazos').submit();
}

function formatarData(data) {
    const partes = data.split('-');
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

// ====================================
// FUNÇÕES AUXILIARES
// ====================================

function limparFormulario() {
    if (confirm('Tem certeza que deseja limpar todos os campos?')) {
        document.getElementById('formPrazos').reset();
        document.querySelectorAll('.input-feedback').forEach(el => el.textContent = '');
        document.querySelectorAll('input[type="date"]').forEach(el => el.className = '');
        document.getElementById('timelinePreview').style.display = 'none';
        document.getElementById('warningAllCourses').classList.remove('show');
        document.getElementById('warningAllSemesters').classList.remove('show');
        document.getElementById('btnSubmit').disabled = true;
        localStorage.removeItem('prazos_rascunho');
    }
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').remove();

    // Limpar localStorage quando fechar modal de sucesso
    localStorage.removeItem('prazos_rascunho');

    // Limpar parâmetro success da URL
    if (window.location.search.includes('success=1')) {
        window.history.replaceState({}, document.title, 'prazos.php');
    }
}

// ====================================
// INICIALIZAÇÃO
// ====================================

window.addEventListener('DOMContentLoaded', function() {
    carregarRascunho();
    validarTudo();
});
</script>

</body>
</html>
