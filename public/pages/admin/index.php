<?php
// pages/admin/index.php
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
                <li><a href="../../pages/admin/index.php" class="active">Home</a></li>
                <li><a href="../../pages/admin/inscricoes.php">Inscrições</a></li>
                <li><a href="../../pages/admin/prazos.php">Prazos</a></li>
                <li><a href="../../pages/admin/relatorios.php">Relatórios</a></li>
                <li><a href="../../pages/admin/cadastro-admin.php">Cadastro Admin</a></li>
            </ul>

            <div class="actions">
                <img src="../../assets/images/user-icon.png" alt="Avatar do usuário" class="user-icon">
                <a href="../../logout.php">Sair da Conta</a>
            </div>
        </nav>
    </header>

    <main class="dashboard">
        <div class="container">
            <div class="title">
                <h1>Estatísticas de Votação</h1>
            </div>

            <div class="graphs">
                <div class="graph-container">
                    <h1>DSM 2025-1</h1>
                    <div class="card-wrapper">
                        <div class="card">
                            <h3>Alunos Matriculados: 40</h3>
                            <h4>Inscrições para representante: 03</h4>
                        </div>
                    </div>

                    <div>
                        <div class="graph-card-title">
                            <h3>CONTAGEM DE VOTOS</h3>
                        </div>

                        <div class="graph-card">

                            <div class="bar-container-100">
                                <h3 class="nome-dash">15</h3>
                                <div class="bar"></div>
                                <h3 class="nome-dash">Julia Rodrigues</h3>
                            </div>

                            <div class="bar-container-50">
                                <h3 class="nome-dash">10</h3>
                                <div class="bar"></div>
                                <h3 class="nome-dash">Rafael Moraes</h3>
                            </div>

                            <div class="bar-container-75">
                                <h3 class="nome-dash">5</h3>
                                <div class="bar"></div>
                                <h3 class="nome-dash">Lucas Simões</h3>
                            </div>

                        </div>
                        <div class="graph-footer">
                            <h4>Quantitade de votos totais</h4>
                            <h4>30/40</h4>
                        </div>
                    </div>

                </div>

                <!-- Você pode duplicar os blocos para GE 2025-1, GPI 2025-1, etc., ou futuramente gerar dinamicamente via PHP -->
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
