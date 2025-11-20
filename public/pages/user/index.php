<?php
// pages/user/index.php
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
                <li><a href="../../pages/user/index.php" class="active">Home</a></li>
                <li><a href="../../pages/user/inscricao.php">Inscrição</a></li>
                <li><a href="../../pages/user/votacao.php">Votação</a></li>
                <li><a href="../../pages/user/sobre.php">Sobre</a></li>
            </ul>

            <div class="actions">
                <img src="../../assets/images/user-icon.png" alt="Avatar do usuário" class="user-icon">
                <a href="../../pages/guest/index.php">Sair da Conta</a>
            </div>
        </nav>
    </header>

    <main class="user-home">
        <div class="card-wrapper">
            <div class="card">
                <section class="voting">
                    <div class="voting-text">
                        <h1>AMBIENTE DE VOTAÇÃO</h1>
                        <p>
                            Esta interface exibe todos os processos eleitorais em andamento nos quais você está
                            cadastrado como eleitor.
                            Observe que:
                        </p>
                        <ul>
                            <li>
                                Cada processo exibirá o botão <b>VOTAR</b> para acessar a cédula eleitoral.
                            </li>
                            <li>
                                Os prazos são rigorosos: o sistema <b>bloqueará</b> o acesso no horário de encerramento.
                            </li>
                        </ul>
                    </div>

                    <div class="voting-illustration">
                        <img src="../../assets/images/voting-amico.svg" alt="Ilustração da votação">
                    </div>
                </section>

                <section class="security">
                    <h2>SEGURANÇA DO SEU VOTO</h2>
                    <p>
                        Sua identidade é verificada apenas no acesso e o registro de voto não contém informações que
                        possam
                        vinculá-lo
                        às suas escolhas, garantindo seu sigilo absoluto para exercício livre do seu direito.
                    </p>
                </section>

                <div class="alert">
                    <span>⚠️ATENÇÃO: Verifique os prazos de cada edital para não perder seu direito de voto!</span>
                </div>
            </div>
        </div>

        <div class="card-wrapper">
            <div class="card">
                <h1>EDITAIS ABERTOS PARA INSCRIÇÃO</h1>
                <p>Clique no botão "QUERO ME INSCREVER" para ver as regras.</p>
            </div>

            <div class="button-group">
                <a href="../../pages/user/inscricao.php" class="button primary">QUERO ME INSCREVER</a>
                <a href="../../assets/user/votacao.php" class="button primary disabled">QUERO VOTAR</a>
                <a href="#" class="button primary disabled">ACOMPANHAR INSCRIÇÃO</a>
            </div>
        </div>
    </main>

    <footer class="site">
        <div class="content">
            <img src="../../assets/images/logo-governo-do-estado-sp.png" alt="Logo Governo SP" class="logo-governo">

            <a href="../../assets/guest/sobre.php" class="btn-about">SOBRE O SISTEMA</a>

            <p>Sistema Integrado de Votação - FATEC/CPS</p>
            <p>Versão 0.1 (11/06/2025)</p>
        </div>
    </footer>
</body>

</html>
