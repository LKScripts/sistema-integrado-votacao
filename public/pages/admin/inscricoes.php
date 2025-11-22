<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';

// Verifica se é administrador logado
verificarAdmin();

$usuario = obterUsuarioLogado();
$id_admin = $usuario['id'];

$mensagem = "";
$erro = "";

// Processar validação de candidatura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_candidatura'])) {
    $id_candidatura = intval($_POST['id_candidatura']);
    $acao = $_POST['acao'] ?? '';
    $justificativa = trim($_POST['justificativa'] ?? '');

    if ($acao === 'deferir') {
        $stmt = $conn->prepare("
            UPDATE CANDIDATURA
            SET status_validacao = 'deferido',
                validado_por = ?,
                data_validacao = NOW()
            WHERE id_candidatura = ?
        ");
        if ($stmt->execute([$id_admin, $id_candidatura])) {
            $mensagem = "Candidatura deferida com sucesso!";
        }

    } elseif ($acao === 'indeferir' && !empty($justificativa)) {
        $stmt = $conn->prepare("
            UPDATE CANDIDATURA
            SET status_validacao = 'indeferido',
                validado_por = ?,
                data_validacao = NOW(),
                justificativa_indeferimento = ?
            WHERE id_candidatura = ?
        ");
        if ($stmt->execute([$id_admin, $justificativa, $id_candidatura])) {
            $mensagem = "Candidatura indeferida com sucesso!";
        }
    } else {
        $erro = "Para indeferir é necessário informar a justificativa.";
    }
}

// Buscar candidaturas com filtros
$filtro_curso = $_GET['curso'] ?? '';
$filtro_semestre = $_GET['semestre'] ?? '';
$filtro_nome = $_GET['nome'] ?? '';

$sql = "
    SELECT
        c.id_candidatura,
        c.data_inscricao,
        c.status_validacao,
        c.proposta,
        a.nome_completo,
        a.ra,
        e.curso,
        e.semestre,
        e.id_eleicao
    FROM CANDIDATURA c
    JOIN ALUNO a ON c.id_aluno = a.id_aluno
    JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
    WHERE 1=1
";

$params = [];

if (!empty($filtro_curso)) {
    $sql .= " AND e.curso = ?";
    $params[] = $filtro_curso;
}

if (!empty($filtro_semestre)) {
    $sql .= " AND e.semestre = ?";
    $params[] = intval($filtro_semestre);
}

if (!empty($filtro_nome)) {
    $sql .= " AND a.nome_completo LIKE ?";
    $params[] = "%$filtro_nome%";
}

$sql .= " ORDER BY c.data_inscricao DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$candidaturas = $stmt->fetchAll();

$total_resultados = count($candidaturas); // ADIÇÃO
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Sistema Integrado de Votações</title>

    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/guest.css">
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
            <li><a href="../../pages/admin/inscricoes.php" class="active">Inscrições</a></li>
            <li><a href="../../pages/admin/prazos.php">Prazos</a></li>
            <li><a href="../../pages/admin/relatorios.php">Relatórios</a></li>
        </ul>

        <div class="actions">
            <img src="../../assets/images/user-icon.png" alt="Avatar do usuário" class="user-icon">
            <a href="../../logout.php">Sair da Conta</a>
        </div>
    </nav>
</header>

<main class="manage-applicants">
    <div class="container">
        <h1>Gerenciar Inscrições</h1>

        <!-- FORM GET FUNCIONAL -->
        <form class="form-filters" method="GET"> <!-- ADIÇÃO: method GET -->
            <div class="column">
                <label for="applicant-name">Nome do aluno</label>
                <input 
                    id="applicant-name" 
                    type="text" 
                    name="nome" <!-- ADIÇÃO -->
                    value="<?= htmlspecialchars($filtro_nome) ?>"> <!-- mantém filtro -->
            </div>

            <div class="column half">
                <div class="input-group">
                    <label for="course">Curso</label>
                    <div class="wrapper-select">
                        <select id="course" name="curso"> <!-- ADIÇÃO -->
                            <option value="">Selecione uma opção</option>
                            <option value="DSM" <?= $filtro_curso == "DSM" ? "selected" : "" ?>>Desenvolvimento de Software Multiplataforma</option>
                            <option value="GE" <?= $filtro_curso == "GE" ? "selected" : "" ?>>Gestão Empresarial</option>
                            <option value="GPI" <?= $filtro_curso == "GPI" ? "selected" : "" ?>>Gestão da Produção Industrial</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="column half">
                <div class="input-group">
                    <label for="semester">Semestre</label>
                    <div class="wrapper-select">
                        <select id="semester" name="semestre"> <!-- ADIÇÃO -->
                            <option value="">Selecione uma opção</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>" <?= $filtro_semestre == $i ? "selected" : "" ?>>
                                    <?= $i ?>º Semestre
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
        </form>

        <section class="list-applicants">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Data inscrição</th>
                        <th scope="col">Nome</th>
                        <th scope="col">Curso</th>
                        <th scope="col">Semestre</th>
                        <th scope="col">Status</th>
                        <th scope="col">Ações</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($total_resultados > 0): ?>
                    <?php foreach ($candidaturas as $cand): ?>
                        <?php
                        $status_class = 'text-warning';
                        $status_texto = 'Aguardando análise';

                        if ($cand['status_validacao'] === 'deferido') {
                            $status_class = 'text-success';
                            $status_texto = 'Deferido';
                        } elseif ($cand['status_validacao'] === 'indeferido') {
                            $status_class = 'text-danger';
                            $status_texto = 'Indeferido';
                        }

                        $data_formatada = date('d/m/Y H:i', strtotime($cand['data_inscricao']));
                        ?>
                        <tr>
                            <td><?= $data_formatada ?></td>
                            <td><?= htmlspecialchars($cand['nome_completo']) ?></td>
                            <td><?= htmlspecialchars($cand['curso']) ?></td>
                            <td><?= $cand['semestre'] ?>º Semestre</td>
                            <td class="<?= $status_class ?>"><?= $status_texto ?></td>
                            <td><a href="#edit-user-modal-<?= $cand['id_candidatura'] ?>">Editar</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:20px;">Nenhuma candidatura encontrada.</td>
                    </tr>
                <?php endif; ?>

                <tfoot>
                <tr>
                    <td colspan="6">
                        <div class="pagination">
                            <div class="results">
                                Mostrando <strong><?= $total_resultados ?></strong> de <strong><?= $total_resultados ?></strong> resultados
                            </div>
                            <!-- Paginação removida (falsa) como solicitado -->
                        </div>
                    </td>
                </tr>
                </tfoot>

                </tbody>
            </table>
        </section>
    </div>
</main>

<footer class="site">
    <div class="content">
        <img src="../../assets/images/logo-governo-do-estado-sp.png" alt="Logo Governo SP" class="logo-governo">
        <a href="../../pages/guest/sobre.html" class="btn-about">SOBRE O SISTEMA</a>
        <p>Sistema Integrado de Votação - FATEC/CPS</p>
        <p>Versão 0.1 (11/06/2025)</p>
    </div>
</footer>

</body>
</html>
