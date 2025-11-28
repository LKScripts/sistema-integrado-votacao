<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';

// Verificar se é administrador
verificarAdmin();

// Buscar resultados de eleições encerradas usando a view
$filtro_curso = $_GET['curso'] ?? '';
$filtro_semestre = $_GET['semestre'] ?? '';

$sql = "SELECT * FROM v_resultados_completos WHERE 1=1";
$params = [];
$types = "";

if (!empty($filtro_curso) && $filtro_curso !== 'Todos os Cursos') {
    $sql .= " AND curso = ?";
    $params[] = $filtro_curso;
    $types .= "s";
}

if (!empty($filtro_semestre) && $filtro_semestre !== 'Todos Semestres') {
    $sql .= " AND semestre = ?";
    $params[] = intval($filtro_semestre);
    $types .= "i";
}

$sql .= " ORDER BY data_apuracao DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll();
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
                <li><a href="../../pages/admin/cadastro-admin.php">Cadastro Admin</a></li>
                <li><a href="../../pages/admin/gerenciar-alunos.php">Gerenciar Alunos</a></li>
            </ul>

            <div class="actions">
                <img src="../../assets/images/user-icon.png" alt="Avatar do usuário" class="user-icon">
                <a href="../../logout.php">Sair da Conta</a>
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
                    <h3 class="title">Resultados de Eleições Finalizadas</h3>
                    <?php if(count($resultados) > 0): ?>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                            <thead>
                                <tr style="background-color: #f5f5f5;">
                                    <th style="padding: 10px; border: 1px solid #ddd;">Curso</th>
                                    <th style="padding: 10px; border: 1px solid #ddd;">Semestre</th>
                                    <th style="padding: 10px; border: 1px solid #ddd;">Representante</th>
                                    <th style="padding: 10px; border: 1px solid #ddd;">Votos</th>
                                    <th style="padding: 10px; border: 1px solid #ddd;">Suplente</th>
                                    <th style="padding: 10px; border: 1px solid #ddd;">Votos</th>
                                    <th style="padding: 10px; border: 1px solid #ddd;">Participação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($resultados as $res): ?>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($res['curso']) ?></td>
                                        <td style="padding: 10px; border: 1px solid #ddd;"><?= $res['semestre'] ?>º</td>
                                        <td style="padding: 10px; border: 1px solid #ddd;">
                                            <?= htmlspecialchars($res['representante']) ?>
                                            <br><small>(RA: <?= htmlspecialchars($res['ra_representante']) ?>)</small>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #ddd;"><?= $res['votos_representante'] ?></td>
                                        <td style="padding: 10px; border: 1px solid #ddd;">
                                            <?= htmlspecialchars($res['suplente'] ?? '-') ?>
                                            <?php if($res['suplente']): ?>
                                                <br><small>(RA: <?= htmlspecialchars($res['ra_suplente']) ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #ddd;"><?= $res['votos_suplente'] ?? '-' ?></td>
                                        <td style="padding: 10px; border: 1px solid #ddd;">
                                            <?= number_format($res['percentual_participacao'], 2) ?>%
                                            <br><small>(<?= $res['total_votantes'] ?>/<?= $res['total_aptos'] ?>)</small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
