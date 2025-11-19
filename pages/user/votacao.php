<?php
// Processa o voto se o formulário foi enviado
if (isset($_POST['vote'])) {
    $candidato = $_POST['vote'];
    $data = date('d/m/Y H:i:s');

    // Salva o voto em votos.txt
    $linha = $candidato . " | " . $data . PHP_EOL;
    file_put_contents('../../assets/votos.txt', $linha, FILE_APPEND);

    $voto_confirmado = true;
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
    <link rel="stylesheet" href="../../assets/styles/user.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
</head>

<body>
<header class="site">
    <nav class="navbar">
        <div class="logo">
            <img src="../../assets/images/fatec-ogari.png" alt="Logo Fatec Itapira">
            <img src="../../assets/images/logo-cps.png" alt="Logo CPS">
        </div>

        <ul class="links">
            <li><a href="../../pages/user/index.php">Home</a></li>
            <li><a href="../../pages/user/inscricao.php">Inscrição</a></li>
            <li><a href="../../pages/user/votacao.php" class="active">Votação</a></li>
            <li><a href="../../pages/user/sobre.php">Sobre</a></li>
        </ul>

        <div class="actions">
            <img src="../../assets/images/user-icon.png" alt="Avatar do usuário" class="user-icon">
            <a href="../../pages/guest/index.php">Sair da Conta</a>
        </div>
    </nav>
</header>

<main class="user-vote">
    <div class="container">
        <header>
            <h1>Candidatos DSM 2025-1</h1>
            <p>Vote para o candidato que você quer que represente você durante esse semestre na sua sala.</p>
        </header>

        <?php if (!empty($voto_confirmado)): ?>
            <div class="modal feedback" style="display:block;">
                <div class="content">
                    <h3 class="title">Voto Confirmado!</h3>
                    <div class="text">
                        <p>✅ Seu voto foi registrado com sucesso!</p>
                        <p>Obrigado por participar das votações!</p>
                    </div>
                    <div class="modal-buttons">
                        <a href="../../pages/user/index.php" class="button primary">Voltar</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <section class="candidates">
            <?php
            // Lista de candidatos
            $candidatos = [
                "Lucas Simões" => "1º Semestre",
                "Julia Rodrigues" => "1º Semestre",
                "Rafael Moraes" => "1º Semestre",
                "Gabriel Bueno" => "1º Semestre",
                "Gabriel Borges" => "1º Semestre"
            ];

            foreach ($candidatos as $nome => $semestre): ?>
                <div class="candidate-card">
                    <div class="media">
                        <div class="placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <div class="content">
                        <h2><?php echo $nome; ?></h2>
                        <div class="info-row">
                            <i class="fas fa-graduation-cap"></i>
                            <span><?php echo $semestre; ?></span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-users"></i>
                            <span>DSM</span>
                        </div>
                    </div>
                    <form method="post">
                        <input type="hidden" name="vote" value="<?php echo $nome; ?>">
                        <button type="submit" class="vote">
                            <i class="fas fa-vote-yea"></i>
                            <span>VOTAR</span>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </section>

        <div class="callout info">
            <div class="content">
                <div class="instructions">
                    <p class="title">Como votar</p>
                    <ol>
                        <li><strong>Escolha seu candidato:</strong> Clique no botão "VOTAR" abaixo do seu candidato preferido.</li>
                        <li><strong>Confirme sua escolha:</strong> Você verá uma janela de confirmação para verificar seu voto.</li>
                        <li><strong>Finalizando seu voto:</strong> Após confirmação, seu voto será registrado com segurança no sistema.</li>
                        <li><strong>Apenas um voto:</strong> Você pode votar em apenas um candidato!</li>
                    </ol>
                </div>
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
