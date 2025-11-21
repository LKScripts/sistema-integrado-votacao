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
                <li><a href="../../pages/user/votacao.php">Votação</a></li>
                <li><a href="../../pages/user/sobre.php" class="active">Sobre</a></li>
            </ul>

            <div class="actions">
                <img src="../../assets/images/user-icon.png" alt="Avatar do usuário" class="user-icon">
                <a href="../../logout.php">Sair da Conta</a>
            </div>
        </nav>
    </header>

    <main class="about">
        <div class="card-wrapper">
            <div class="card">
                <section class="voting">
                    <div class="voting-text">
                        <h1>SOBRE A PLATAFORMA</h1>
                        <p>
                            Seja bem-vindo ao SIV, o Sistema Integrado de Votação desenvolvido para facilitar e
                            modernizar o processo de eleição de
                            representantes de turma da FATEC Itapira.
                        </p>
                        <p>
                            O SIV foi criado com o objetivo de tornar as eleições acadêmicas mais transparentes, seguras
                            e organizadas, promovendo a
                            participação ativa dos alunos na escolha de seus representantes.
                        </p>
                        <p>Por meio deste sistema, é possível:</p>
                        <ul>
                            <li>Realizar login seguro para alunos e administradores;</li>
                            <li>Efetuar a inscrição de candidatos dentro do prazo definido;</li>
                            <li>Participar da votação online, de forma simples e rápida;</li>
                            <li>Acompanhar a contagem de votos em tempo real;</li>
                            <li>Gerar automaticamente a ata da votação, com todos os dados registrados;</li>
                            <li>Gerenciar relatórios administrativos e prazos com praticidade.</li>
                        </ul>
                    </div>

                    <div class="voting-illustration">
                        <img src="../../assets/images/man-thinking-amico.svg" alt="Ilustração da votação">
                        <img src="../../assets/images/voting-amico.svg" alt="Ilustração da votação">
                    </div>
                </section>

                <section class="security">
                    <h2>SOBRE NÓS</h2>
                    <p>
                        Este sistema é um projeto acadêmico desenvolvido pelos alunos do 1º semestre do curso de
                        Desenvolvimento de Software
                        Multiplataforma da FATEC Itapira. O SIV foi idealizado com base nos conhecimentos adquiridos no
                        semestre, representando
                        um importante passo na nossa jornada como desenvolvedores.
                        Esperamos que o SIV torne o processo eleitoral mais eficiente e democrático para todos!
                    </p>
                </section>

                <a href="../../pages/user/index.php" class="button primary">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
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
