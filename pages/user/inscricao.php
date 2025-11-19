<?php
session_start();
include '../../conexao.php'; // conexão com o banco

// Verifica se usuário está logado
if (!isset($_SESSION["id"])) {
    header("Location: ../guest/index.php");
    exit;
}

// Inicializa variáveis
$nome = "";
$curso = "";
$semestre = "";

// Pega os dados do usuário logado
$id_aluno = $_SESSION["id"];
$stmt = $conn->prepare("SELECT nome_completo, curso, semestre FROM aluno WHERE id_aluno = ?");
$stmt->bind_param("i", $id_aluno);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $usuario = $result->fetch_assoc();
    $nome = $usuario["nome_completo"];
    $curso = $usuario["curso"];
    $semestre = $usuario["semestre"];
}

$stmt->close();

// Caminho do arquivo .txt onde os dados serão salvos
$arquivo = "../../assets/data/inscricoes.txt";

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qualidades = $_POST['qualidades'] ?? '';

    // Monta a linha a ser gravada no arquivo
    $linha = "$nome | $curso | $semestre | $qualidades" . PHP_EOL;

    // Salva no arquivo
    file_put_contents($arquivo, $linha, FILE_APPEND | LOCK_EX);

    // Flag para exibir modal de sucesso
    $inscricao_sucesso = true;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Sistema Integrado de Votações</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <!-- CSS ABSOLUTO e relativo igual ao seu código funcional -->
    <link rel="stylesheet" href="/assets/styles/user.css">
    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
</head>

<body>
    <?php if (!empty($inscricao_sucesso)) : ?>
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
                <img src="../../assets/images/fatec-ogari.png" alt="Logo Fatec Itapira">
                <img src="../../assets/images/logo-cps.png" alt="Logo CPS">
            </div>
            <ul class="links">
                <li><a href="../../pages/user/index.php">Home</a></li>
                <li><a href="../../pages/user/inscricao.php" class="active">Inscrição</a></li>
                <li><a href="../../pages/user/votacao.php">Votação</a></li>
                <li><a href="../../pages/user/sobre.php">Sobre</a></li>
            </ul>
            <div class="actions">
                <img src="../../assets/images/user-icon.png" alt="Avatar do usuário" class="user-icon">
                <a href="../../pages/guest/index.php">Sair da Conta</a>
            </div>
        </nav>
    </header>

    <main class="user-application">
        <div class="card-wrapper">
            <div class="card">
                <h1 class="title">Inscreva-se para Representante</h1>

                <form class="form-application" method="POST">
                    <div class="input-group">
                        <label for="nome">Nome do aluno</label>
                        <input id="nome" name="nome" type="text" value="<?= htmlspecialchars($nome) ?>" readonly />
                    </div>

                    <div class="input-group">
                        <label for="curso">Curso</label>
                        <input id="curso" name="curso" type="text" value="<?= htmlspecialchars($curso) ?>" readonly />
                    </div>

                    <div class="input-group">
                        <label for="semestre">Semestre</label>
                        <input id="semestre" name="semestre" type="text" value="<?= htmlspecialchars($semestre) ?>" readonly />
                    </div>

                    <div class="input-group">
                        <label for="qualidades">Descreva suas qualidades</label>
                        <textarea id="qualidades" name="qualidades" placeholder="Descreva suas qualidades." required></textarea>
                        <span class="textarea-count">0/400 caracteres</span>
                    </div>

                    <div class="form-buttons">
                        <a href="../../pages/user/index.php" type="button" class="button secondary">Cancelar</a>
                        <button type="submit" class="button primary">Concluir</button>
                    </div>
                </form>
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
