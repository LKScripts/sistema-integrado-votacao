<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';

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
$stmtEleicao = $conn->prepare("
    SELECT id_eleicao, data_inicio_candidatura, data_fim_candidatura, status
    FROM ELEICAO
    WHERE curso = ? AND semestre = ?
    AND status = 'candidatura_aberta'
    AND CURDATE() BETWEEN data_inicio_candidatura AND data_fim_candidatura
    LIMIT 1
");
$stmtEleicao->execute([$curso, $semestre]);
$eleicao = $stmtEleicao->fetch();

if (!$eleicao) {
    $erro = "Não há eleição aberta para candidatura no momento para seu curso e semestre.";
}

$id_eleicao = $eleicao['id_eleicao'] ?? null;

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_eleicao) {
    $proposta = trim($_POST['qualidades'] ?? '');

    if (strlen($proposta) < 10) {
        $erro = "A proposta deve ter pelo menos 10 caracteres.";
    } else {
        // Verificar se já se candidatou nesta eleição
        $stmtVerifica = $conn->prepare("
            SELECT id_candidatura
            FROM CANDIDATURA
            WHERE id_eleicao = ? AND id_aluno = ?
        ");
        $stmtVerifica->execute([$id_eleicao, $id_aluno]);
        $candidaturaExistente = $stmtVerifica->fetch();

        if ($candidaturaExistente) {
            $erro = "Você já está inscrito nesta eleição!";
        } else {
            // Inserir candidatura
            $stmtInsert = $conn->prepare("
                INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, status_validacao)
                VALUES (?, ?, ?, 'pendente')
            ");

            if ($stmtInsert->execute([$id_eleicao, $id_aluno, $proposta])) {
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

                <?php if (!empty($erro)): ?>
                    <div class="callout warning" style="margin-bottom: 20px;">
                        <div class="content">
                            <span><?= htmlspecialchars($erro) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

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
