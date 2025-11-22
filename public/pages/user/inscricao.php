<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/automacao_eleicoes.php';

// Verifica se é aluno logado
verificarAluno();

$usuario = obterUsuarioLogado();
$id_aluno = $usuario['id'];
$nome = $usuario['nome'];
$curso = $usuario['curso'];
$semestre = $usuario['semestre'];

$erro = "";
$inscricao_sucesso = false;

// Buscar eleição ativa para candidatura
$eleicao = buscarEleicaoAtivaComVerificacao($curso, $semestre, 'candidatura');

$id_eleicao = $eleicao['id_eleicao'] ?? null;

file_put_contents("debug_foto.txt", "TAMANHO: " . strlen($_POST['foto_base64'] ?? '') . "\n");
file_put_contents("debug_foto.txt", "INTEIRO:\n" . substr($_POST['foto_base64'] ?? '', 0, 5000), FILE_APPEND);


// PROCESSAMENTO DO POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_eleicao) {

    $proposta = trim($_POST['qualidades'] ?? '');

    // ✔ FOTO CORRETA — RECEBE BASE64 COMPLETO
    $foto_base64 = $_POST['foto_base64'] ?? '';

    // Verificação do período
    $verificacao = verificarPeriodoCandidatura($id_eleicao);

    if (!$verificacao['valido']) {
        $erro = $verificacao['mensagem'];

    } elseif (strlen($proposta) < 10) {
        $erro = "A proposta deve conter pelo menos 10 caracteres.";

    } elseif (empty($foto_base64)) {
        $erro = "Envie a foto de candidato antes de concluir a inscrição.";

    } else {

        // Verifica se já existe candidatura
        $stmtVerifica = $conn->prepare("
            SELECT id_candidatura
            FROM candidatura
            WHERE id_eleicao = ? AND id_aluno = ?
        ");
        $stmtVerifica->execute([$id_eleicao, $id_aluno]);
        $candidaturaExistente = $stmtVerifica->fetch();

        if ($candidaturaExistente) {
            $erro = "Você já está inscrito nesta eleição!";
        } else {

            // ✔ INSERT FINAL — AGORA GRAVA A FOTO COMPLETA
            $stmtInsert = $conn->prepare("
                INSERT INTO candidatura
                (id_eleicao, id_aluno, proposta, foto_candidato, status_validacao)
                VALUES (?, ?, ?, ?, 'pendente')
            ");

            if ($stmtInsert->execute([$id_eleicao, $id_aluno, $proposta, $foto_base64])) {
                $inscricao_sucesso = true;
            } else {
                $erro = "Erro ao registrar candidatura. Tente novamente.";
            }
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

    <link rel="stylesheet" href="/assets/styles/user.css">
    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">

    <style>
        .modal-foto {
            display: none;
            position: fixed;
            top:0; left:0;
            width:100%; height:100%;
            background: rgba(0,0,0,0.6);
            justify-content:center;
            align-items:center;
        }
        .modal-foto .box {
            background:#fff;
            padding:25px;
            border-radius:10px;
            width:350px;
            text-align:center;
        }
        .modal-foto img {
            width:150px; height:150px;
            border-radius:50%;
            object-fit:cover;
            margin-bottom:15px;
        }
    </style>
</head>

<body>

<?php if ($inscricao_sucesso): ?>
    <div class="modal-wrapper">
        <div class="modal feedback" style="display:block;">
            <div class="content">
                <a href="#" class="close" onclick="this.parentElement.parentElement.style.display='none'">&times;</a>
                <h3 class="title">Inscrição Confirmada!</h3>
                <div class="text">
                    <p>✅ Sua inscrição foi registrada com sucesso!</p>
                </div>
                <div class="modal-buttons">
                    <a href="../../pages/user/index.php" class="button primary">Voltar</a>
                </div>
            </div>
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
            <li><a href="../../pages/user/index.php">Home</a></li>
            <li><a href="../../pages/user/inscricao.php" class="active">Inscrição</a></li>
            <li><a href="../../pages/user/votacao.php">Votação</a></li>
            <li><a href="../../pages/user/sobre.php">Sobre</a></li>
        </ul>
        <div class="actions">
            <img src="../../assets/images/user-icon.png" class="user-icon">
            <a href="../../logout.php">Sair</a>
        </div>
    </nav>
</header>

<main class="user-application">
<div class="card-wrapper">
<div class="card">

    <h1 class="title">Inscreva-se para Representante</h1>

    <?php if (!empty($erro)): ?>
        <div class="callout warning" style="margin-bottom:20px;">
            <div class="content"><span><?= htmlspecialchars($erro) ?></span></div>
        </div>
    <?php endif; ?>

    <?php if ($eleicao): ?>
        <p><strong>📢 Eleição aberta:</strong> <?= htmlspecialchars($eleicao["descricao"]) ?>  
        <br><strong>ID:</strong> <?= $eleicao["id_eleicao"] ?></p>
    <?php endif; ?>

    <form class="form-application" method="POST">
        <input type="hidden" name="foto_base64" id="foto_base64">

        <div class="input-group">
            <label>Nome</label>
            <input type="text" value="<?= htmlspecialchars($nome) ?>" readonly>
        </div>

        <div class="input-group">
            <label>Curso</label>
            <input type="text" value="<?= htmlspecialchars($curso) ?>" readonly>
        </div>

        <div class="input-group">
            <label>Semestre</label>
            <input type="text" value="<?= htmlspecialchars($semestre) ?>" readonly>
        </div>

        <!-- FOTO DO CANDIDATO -->
        <div class="input-group">
            <label>Foto do candidato</label>
            <button type="button" class="button primary" onclick="abrirModalFoto()">Enviar Foto</button>
            <p id="foto-status" style="margin-top:5px;color:green;display:none;">Foto enviada ✔️</p>
        </div>

        <div class="input-group">
            <label for="qualidades">Descreva suas qualidades</label>
            <textarea id="qualidades" name="qualidades" maxlength="400" required></textarea>
        </div>

        <div class="form-buttons">
            <a href="../../pages/user/index.php" class="button secondary">Cancelar</a>
            <button type="submit" class="button primary">Concluir</button>
        </div>
    </form>

</div>
</div>
</main>

<footer class="site">
    <div class="content">
        <img src="../../assets/images/logo-governo-do-estado-sp.png" class="logo-governo">
        <a href="../../pages/guest/sobre.php" class="btn-about">SOBRE O SISTEMA</a>
        <p>Sistema Integrado de Votação - FATEC/CPS</p>
        <p>Versão 0.1 (11/06/2025)</p>
    </div>
</footer>

<!-- MODAL DE FOTO -->
<div class="modal-foto" id="modalFoto">
    <div class="box">
        <h3>Enviar Foto</h3>
        <img id="previewFoto" src="../../assets/images/user-icon.png">
        <input type="file" id="inputFoto" accept="image/*">
        <br><br>
        <button class="button primary" onclick="confirmarFoto()">Confirmar</button>
        <button class="button secondary" onclick="fecharModalFoto()">Cancelar</button>
    </div>
</div>

<script>
function abrirModalFoto() {
    document.getElementById('modalFoto').style.display = 'flex';
}

function fecharModalFoto() {
    document.getElementById('modalFoto').style.display = 'none';
}

document.getElementById('inputFoto').addEventListener('change', function(){
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e){
        document.getElementById('previewFoto').src = e.target.result;
    }
    reader.readAsDataURL(file);
});

function confirmarFoto(){
    const img = document.getElementById('previewFoto').src;
    document.getElementById('foto_base64').value = img;
    document.getElementById('foto-status').style.display = 'block';
    fecharModalFoto();
}
</script>

</body>
</html>
