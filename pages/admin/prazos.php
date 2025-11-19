<?php
session_start();
include '../../conexao.php';

// BLOQUEIO PARA NÃO ADMIN
if (!isset($_SESSION['id_admin'])) {
    echo "<script>alert('Acesso negado. Faça login como administrador.'); window.location.href='../../pages/guest/login.php';</script>";
    exit;
}

$mensagem = "";
$tipo_mensagem = ""; // success | error

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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

    if ($inscricao_inicio <= $hoje) {
        $mensagem = "⚠ A data de <b>início das inscrições</b> não pode ser anterior a hoje.";
        $tipo_mensagem = "error";

        if ($inscricao_fim <= $inscricao_inicio) {
            $mensagem = "⚠ A data de <b>término das inscrições</b> deve ser maior ou igual ao início.";
            $tipo_mensagem = "error";

            if ($votacao_inicio <= $inscricao_fim) {
                $mensagem = "⚠ A data de <b>início da votação</b> deve ser igual ou maior que o fim das inscrições.";
                $tipo_mensagem = "error";

                if ($votacao_fim <= $votacao_inicio) {
                    $mensagem = "⚠ A data de <b>término da votação</b> deve ser igual ou maior que o início da votação.";
                    $tipo_mensagem = "error";
                }
            }


        }
    }

    //  ERRO → só exibe modal, NÃO salva
    if ($tipo_mensagem === "error") {
        // não executa salvar
    }
    else {

        // ============================
        // ✔ SALVAR NO BANCO
        // ============================

        if ($curso === "Todos os Cursos") {
            $lista_cursos = [
                "Desenvolvimento de Software Multiplataforma",
                "Gestão Empresarial",
                "Gestão da Produção Industrial"
            ];
        } else {
            $lista_cursos = [$curso];
        }

        $id_admin = $_SESSION['id_admin'];
        $status = "Ativa";

        $stmt = $conn->prepare("
            INSERT INTO eleicao 
            (curso, semestre, dt_ini_cand, dt_fim_cand, dt_ini_vot, dt_fim_vot, status, data_criacao, criado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");

        if (!$stmt) {
            $mensagem = "Erro ao preparar SQL: " . $conn->error;
            $tipo_mensagem = "error";
        } else {

            foreach ($lista_cursos as $c) {
                $stmt->bind_param(
                    "sisssssi",
                    $c,
                    $semestre,
                    $inscricao_inicio,
                    $inscricao_fim,
                    $votacao_inicio,
                    $votacao_fim,
                    $status,
                    $id_admin
                );
                $stmt->execute();
            }

            $mensagem = "✅ Prazo cadastrado com sucesso!";
            $tipo_mensagem = "success";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Sistema Integrado de Votações</title>

    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">

    <style>
        /* Modal fix */
        .custom-modal-bg {
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
        }
        .custom-modal {
            background: #fff;
            width: 420px;
            padding: 25px;
            border-radius: 12px;
            animation: fade .3s ease;
            text-align: center;
            box-shadow: 0 0 20px rgba(0,0,0,.2);
        }
        .custom-modal button {
            margin-top: 15px;
            background: #b20000;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
        }
        @keyframes fade { from {opacity:0; margin-top:-20px;} to {opacity:1; margin-top:0;} }
    </style>
</head>

<body>

<?php if (!empty($mensagem)): ?>
<div class="custom-modal-bg" id="modalErro">
    <div class="custom-modal">
        <h3><?= $tipo_mensagem === "error" ? " Erro" : "Sucesso" ?></h3>
        <p><?= $mensagem ?></p>
        <button onclick="document.getElementById('modalErro').remove()">OK</button>
    </div>
</div>
<?php endif; ?>

<header class="site">
    <nav class="navbar">
        <div class="logo">
            <img src="../../assets/images/fatec-ogari.png">
            <img src="../../assets/images/logo-cps.png">
        </div>

        <ul class="links">
            <li><a href="index.php">Home</a></li>
            <li><a href="inscricoes.php">Inscrições</a></li>
            <li><a href="prazos.php" class="active">Prazos</a></li>
            <li><a href="relatorios.php">Relatórios</a></li>
        </ul>

        <div class="actions">
            <img src="../../assets/images/user-icon.png" class="user-icon">
            <a href="../../pages/guest/index.php">Sair</a>
        </div>
    </nav>
</header>

<main class="manage-deadlines">
    <div class="card-wrapper">
        <div class="card">
            <h1 class="title">Gerenciar Prazos</h1>

            <form class="form-deadline" method="POST">

                <div class="input-group">
                    <label>Curso</label>
                    <div class="wrapper-select">
                        <select name="curso" required>
                            <option value="">Selecione</option>
                            <option value="Desenvolvimento de Software Multiplataforma">DSM</option>
                            <option value="Gestão Empresarial">Gestão Empresarial</option>
                            <option value="Gestão da Produção Industrial">Gestão Produção</option>
                            <option value="Todos os Cursos">Todos os Cursos</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label>Semestre</label>
                    <div class="wrapper-select">
                        <select name="semestre" required>
                            <option value="">Selecione</option>
                            <option value="1">1º Semestre</option>
                            <option value="2">2º Semestre</option>
                            <option value="3">3º Semestre</option>
                            <option value="4">4º Semestre</option>
                            <option value="5">5º Semestre</option>
                            <option value="6">6º Semestre</option>
                            <option value="7">Todos Semestres</option>
                        </select>
                    </div>
                </div>

                <div class="date-interval-group">
                    <h3 class="title">Período de Inscrição</h3>
                    <div class="row">
                        <div class="input-group">
                            <label>Data inicial</label>
                            <input type="date" name="inscricao_inicio" required>
                        </div>

                        <div class="input-group">
                            <label>Data final</label>
                            <input type="date" name="inscricao_fim" required>
                        </div>
                    </div>
                </div>

                <div class="date-interval-group">
                    <h3 class="title">Período de Votação</h3>
                    <div class="row">
                        <div class="input-group">
                            <label>Data inicial</label>
                            <input type="date" name="votacao_inicio" required>
                        </div>

                        <div class="input-group">
                            <label>Data final</label>
                            <input type="date" name="votacao_fim" required>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <a href="index.php" class="button secondary">Voltar</a>
                    <button type="submit" class="button primary">Confirmar</button>
                </div>
            </form>

        </div>
    </div>
</main>

</body>
</html>