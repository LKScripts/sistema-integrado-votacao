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
                <li><a href="./index.php" class="active">Home</a></li>
                <li><a href="./pages/guest/sobre.php">Sobre</a></li>
                <li><a href="./pages/guest/login.php">Votação</a></li>
                <li><a href="./pages/guest/login.php">Inscrição</a></li>
            </ul>

            <div class="actions">
                <a href="./pages/guest/cadastro.php" class="btn-secondary">CADASTRAR</a>
                <a href="./pages/guest/login.php">LOGIN</a>
            </div>
        </nav>
    </header>

    <main class="guest-home">
        <section class="upper">
            <h1>Sistema Integrado de Votações da<br>FATEC - Centro Paula Souza</h1>
            <p>
                Uma plataforma digital completa para eleições acadêmicas, ágil e segura.<br>
                Sistema automatizado com backend robusto para simplificar o processo eleitoral.
            </p>
        </section>

        <div class="container">
            <div class="guest-container">
                <div class="guest-image-container"><img src="./assets/images/selecting-team-cuate.svg" alt=""></div>
                <div class="guest-text-container">
                    <h2>Deseja se Candidatar?</h2>
                    <p>Para se candidatar, você precisa ter uma conta ativa no sistema SIV com email institucional confirmado e estar apto às regras do edital eleitoral.
                    </p>
                    <a href="./pages/guest/cadastro.php" class="button primary">Cadastre-se Aqui</a>
                </div>
            </div>

            <div class="guest-container">
                <div class="guest-text-container">
                    <h2>Como se Candidatar?</h2>
                    <ul>
                        <li>Faça login no sistema com seu email institucional (@fatec.sp.gov.br)</li>
                        <li>Confirme seu email através do link enviado</li>
                        <li>Acesse a seção "Inscrição" durante o período de candidaturas</li>
                        <li>Preencha sua proposta e envie sua foto</li>
                        <li>Aguarde aprovação da administração</li>
                    </ul>

                    <p>A inscrição só estará disponível durante o período de candidaturas definido no edital. O sistema automatiza os prazos para garantir transparência no processo.</p>
                </div>
                <div class="guest-image-container"><img src="./assets/images/political-candidate-bro.svg" alt=""></div>
            </div>

            <div class="guest-container">
                <div class="guest-image-container"><img src="./assets/images/active-support-amico.svg" alt=""></div>
                <div class="guest-text-container">
                    <h2>Precisa de Ajuda?</h2>
                    <p>Encontrou dificuldades no acesso, esqueceu suas credenciais ou não recebeu o email de confirmação?

                        Clique <a href="./pages/guest/suporte.php">aqui</a> ou no botão abaixo para acessar informações de suporte e contato.</p>
                    <a href="./pages/guest/suporte.php" class="button primary">Acessar Suporte</a>
                </div>
            </div>

        </div>
    </main>

    <footer class="site">
        <div class="content">
            <img src="./assets/images/logo-governo-do-estado-sp.png" alt="Logo Governo SP" class="logo-governo">

            <a href="./pages/guest/sobre.php" class="btn-about">SOBRE O SISTEMA</a>

            <p>Sistema Integrado de Votação - FATEC/CPS</p>
            <p>Versão 0.1 (11/06/2025)</p>
        </div>
    </footer>
</body>

</html>