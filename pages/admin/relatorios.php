<?php
// pages/admin/relatorios.php

// Caminho do arquivo de prazos
$arquivo_prazos = '../../data/prazos.txt';

// Inicializa array de linhas
$linhas = [];
if(file_exists($arquivo_prazos)) {
    $linhas = file($arquivo_prazos, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
                <li><a href="../../pages/admin/index.php">Home</a></li>
                <li><a href="../../pages/admin/inscricoes.php">Inscrições</a></li>
                <li><a href="../../pages/admin/prazos.php">Prazos</a></li>
                <li><a href="../../pages/admin/relatorios.php" class="active">Relatórios</a></li>
            </ul>

            <div class="actions">
                <img src="../../assets/images/user-icon.png" alt="Avatar do usuário" class="user-icon">
                <a href="../../pages/guest/index.php">Sair da Conta</a>
            </div>
        </nav>
    </header>

    <main class="manage-reports">
        <div class="card-wrapper">
            <div class="card">
                <h1 class="title">Gerar Relatório (Ata de votação)</h1>

                <form class="form-report">
                    <div class="input-group">
                        <label for="semester">Semestre</label>
                        <div class="wrapper-select">
                            <select id="semester" name="semester">
                                <option value="" selected>Selecione uma opção</option>
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

                    <div class="input-group">
                        <label for="course">Curso</label>
                        <div class="wrapper-select">
                            <select id="course" name="course">
                                <option value="" selected>Selecione uma opção</option>
                                <option value="Desenvolvimento de Software Multiplataforma">Desenvolvimento de Software Multiplataforma</option>
                                <option value="Gestão Empresarial">Gestão Empresarial</option>
                                <option value="Gestão da Produção Industrial">Gestão da Produção Industrial</option>
                                <option value="Todos os Cursos">Todos os Cursos</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-buttons">
                        <a href="../../pages/admin/index.php" class="button secondary">Voltar</a>
                        <button type="button" class="button primary" onclick="window.print()">Gerar Relatório em PDF</button>
                    </div>
                </form>

                <div class="report-list">
                    <h3 class="title">Prazos Registrados</h3>
                    <?php if(!empty($linhas)): ?>
                        <ul>
                            <?php foreach($linhas as $linha): ?>
                                <li><?php echo htmlspecialchars($linha); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Nenhum prazo registrado ainda.</p>
                    <?php endif; ?>
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
