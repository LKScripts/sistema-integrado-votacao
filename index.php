<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Sistema Integrado de Votações</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="./assets/styles/guest.css">
    <link rel="stylesheet" href="./assets/styles/base.css">
    <link rel="stylesheet" href="./assets/styles/fonts.css">
    <link rel="stylesheet" href="./assets/styles/footer-site.css">
    <link rel="stylesheet" href="./assets/styles/header-site.css">
    



</head>

<body>
    <header class="site">
        <nav class="navbar">
            <div class="logo">
                <img src="./assets/images/fatec-ogari.png" alt="Logo Fatec Itapira">
                <img src="./assets/images/logo-cps.png" alt="Logo CPS">
            </div>

            <ul class="links">
                <li><a href="./pages/guest/index.html" class="active">Home</a></li>
                <li><a href="./pages/guest/sobre.html">Sobre</a></li>
                <li><a href="./pages/guest/login.html">Votação</a></li>
                <li><a href="./pages/guest/login.html">Inscrição</a></li>
            </ul>

            <div class="actions">
                <a href="./pages/guest/login.html">LOGIN</a>
            </div>
        </nav>
    </header>

    <main class="guest-home">
        <section class="upper">
            <h1>Sistema Integrado de Votações da<br>FATEC - Centro Paula Souza</h1>
            <p>
                Uma plataforma digital completa para eleições virtuais, ágil e segura.<br>
                Desenvolvido para simplificar o processo eleitoral acadêmico.
            </p>
        </section>

        <div class="container">
            <div class="guest-container">
                <div class="guest-image-container"><img src="/assets/images/selecting-team-cuate.svg" alt=""></div>
                <div class="guest-text-container">
                    <h2>Deseja se Candidatar?</h2>
                    <p>Você deve possuir credencial ativa no sistema SIV e estar apto ás regras específicas do edital.
                    </p>
                </div>
            </div>

            <div class="guest-container">
                <div class="guest-text-container">
                    <h2>Como se Candidatar?</h2>
                    <ul>
                        <li>Acesse o sistema com suas credenciais SIV.</li>
                        <li>Clique em “Inscrição”</li>
                        <li>Navegue até o edital desejado.</li>
                        <li>Confirme seus Dados</li>
                    </ul>

                    <p>O botão estará disponível apenas durante o
                        período de inscrições.</p>
                </div>
                <div class="guest-image-container"><img src="./assets/images/political-candidate-bro.svg" alt=""></div>
            </div>

            <div class="guest-container">
                <div class="guest-image-container"><img src="./assets/images/active-support-amico.svg" alt=""></div>
                <div class="guest-text-container">
                    <h2>Suporte</h2>
                    <p>Encontrou dificuldades no acesso ou esqueceu
                        suas credenciais?

                        Clique <a href="suporte.html">aqui</a> ou no botão abaixo para maiores informações.</p>
                    <a href="suporte.html" class="button primary">Suporte</a>
                </div>
            </div>

        </div>

        <section class="form-wrapper">
            <form action="email.php" method="GET">
                <input type="email" name="email" id="email" placeholder="Seu e-mail" required>
                <button type="submit">
                    <img src="/assets/images/enviar.png" alt="Enviar">
                </button>
            </form>

            <p class="description">
                Insira seu e-mail para receber informações sobre novas eleições da sua FATEC!
            </p>
        </section>
    </main>

    <footer class="site">
        <div class="content">
            <img src="/assets/images/logo-governo-do-estado-sp.png" alt="Logo Governo SP" class="logo-governo">

            <a href="/pages/guest/sobre.html" class="btn-about">SOBRE O SISTEMA</a>

            <p>Sistema Integrado de Votação - FATEC/CPS</p>
            <p>Versão 0.1 (11/06/2025)</p>
        </div>
    </footer>
</body>

</html>