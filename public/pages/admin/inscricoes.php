<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';

// Verifica se é administrador logado
verificarAdmin();

$usuario = obterUsuarioLogado();
$id_admin = $usuario['id'];

$mensagem = "";
$erro = "";

// Processar validação de candidatura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_candidatura'])) {
    // VALIDAR CSRF PRIMEIRO
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente validar novamente.");

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
        c.foto_candidato,
        c.justificativa_indeferimento,
        a.nome_completo,
        a.ra,
        a.email_institucional,
        e.curso,
        e.semestre,
        e.id_eleicao
    FROM CANDIDATURA c
    JOIN ALUNO a ON c.id_aluno = a.id_aluno
    JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
    WHERE 1=1
";

$params = [];
$types = "";

if (!empty($filtro_curso)) {
    $sql .= " AND e.curso = ?";
    $params[] = $filtro_curso;
    $types .= "s";
}

if (!empty($filtro_semestre)) {
    $sql .= " AND e.semestre = ?";
    $params[] = intval($filtro_semestre);
    $types .= "i";
}

if (!empty($filtro_nome)) {
    $sql .= " AND a.nome_completo LIKE ?";
    $params[] = "%$filtro_nome%";
    $types .= "s";
}

$sql .= " ORDER BY c.data_inscricao DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$candidaturas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Sistema Integrado de Votações</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="../../assets/styles/admin.css">

    <link rel="stylesheet" href="../../assets/styles/guest.css">
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
            <li><a href="../../pages/admin/inscricoes.php"class="active">Inscrições</a></li>
            <li><a href="../../pages/admin/prazos.php" >Prazos</a></li>
            <li><a href="../../pages/admin/relatorios.php">Relatórios</a></li>
            </ul>

            <div class="actions">
                <img src="../../assets/images/user-icon.png" alt="Avatar do usuário" class="user-icon">
                <a href="../../logout.php">Sair da Conta</a>
            </div>
        </nav>
    </header>

    <?php if (!empty($mensagem)): ?>
        <div class="modal feedback" style="display:block;">
            <div class="content">
                <a href="inscricoes.php" class="close">&times;</a>
                <h3 class="title">Sucesso!</h3>
                <div class="text">
                    <p><?= htmlspecialchars($mensagem) ?></p>
                </div>
                <div class="modal-buttons">
                    <a href="inscricoes.php" class="button primary">OK</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($erro)): ?>
        <div class="modal feedback" style="display:block;">
            <div class="content">
                <a href="inscricoes.php" class="close">&times;</a>
                <h3 class="title">Erro</h3>
                <div class="text">
                    <p><?= htmlspecialchars($erro) ?></p>
                </div>
                <div class="modal-buttons">
                    <a href="inscricoes.php" class="button primary">OK</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($candidaturas as $cand): ?>
    <div class="modal" id="edit-user-modal-<?= $cand['id_candidatura'] ?>">
        <div class="content">
            <a href="#" class="close">&times;</a>

            <h3 class="title">Validar Candidatura</h3>

            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" value="<?= htmlspecialchars($cand['nome_completo']) ?>" readonly />
            </div>

            <div class="form-group">
                <label>RA</label>
                <input type="text" value="<?= htmlspecialchars($cand['ra']) ?>" readonly />
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="text" value="<?= htmlspecialchars($cand['email_institucional']) ?>" readonly />
            </div>

            <div class="form-group">
                <label>Curso</label>
                <input type="text" value="<?= htmlspecialchars($cand['curso']) ?>" readonly />
            </div>

            <div class="form-group">
                <label>Semestre</label>
                <input type="text" value="<?= $cand['semestre'] ?>º Semestre" readonly />
            </div>

            <div class="form-group">
                <label>Proposta do Candidato</label>
                <textarea readonly style="min-height: 100px;"><?= htmlspecialchars($cand['proposta']) ?></textarea>
            </div>

            <?php if (!empty($cand['foto_candidato'])): ?>
            <div class="form-group">
                <label>Foto do Candidato</label>
                <img src="../../../storage/uploads/candidatos/<?= htmlspecialchars($cand['foto_candidato']) ?>"
                     alt="Foto do candidato"
                     style="max-width: 300px; max-height: 300px; border-radius: 8px; display: block; margin-top: 10px;" />
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Status Atual</label>
                <input type="text" value="<?= ucfirst($cand['status_validacao']) ?>" readonly />
            </div>

            <?php if ($cand['status_validacao'] === 'pendente'): ?>
            <form method="POST" id="form-deferir-<?= $cand['id_candidatura'] ?>">
                <?= campoCSRF() ?>
                <input type="hidden" name="id_candidatura" value="<?= $cand['id_candidatura'] ?>" />
                <input type="hidden" name="acao" value="deferir" />
            </form>

            <form method="POST" id="form-indeferir-<?= $cand['id_candidatura'] ?>">
                <?= campoCSRF() ?>
                <input type="hidden" name="id_candidatura" value="<?= $cand['id_candidatura'] ?>" />
                <input type="hidden" name="acao" value="indeferir" />

                <div class="form-group">
                    <label for="justificativa-<?= $cand['id_candidatura'] ?>">Justificativa para Indeferimento</label>
                    <textarea
                        id="justificativa-<?= $cand['id_candidatura'] ?>"
                        name="justificativa"
                        placeholder="Digite o motivo do indeferimento..."
                        style="min-height: 80px;"></textarea>
                </div>
            </form>

            <div class="modal-buttons">
                <a href="#" class="button secondary">Cancelar</a>
                <button type="button" onclick="document.getElementById('form-deferir-<?= $cand['id_candidatura'] ?>').submit();" class="button primary" style="background-color: #28a745;">Deferir</button>
                <button type="button" onclick="if(document.getElementById('justificativa-<?= $cand['id_candidatura'] ?>').value.trim() === '') { alert('Por favor, informe a justificativa para indeferir.'); return false; } document.getElementById('form-indeferir-<?= $cand['id_candidatura'] ?>').submit();" class="button primary" style="background-color: #dc3545;">Indeferir</button>
            </div>
            <?php else: ?>
            <?php if ($cand['status_validacao'] === 'indeferido' && !empty($cand['justificativa_indeferimento'])): ?>
            <div class="form-group">
                <label>Justificativa do Indeferimento</label>
                <textarea readonly style="min-height: 80px;"><?= htmlspecialchars($cand['justificativa_indeferimento']) ?></textarea>
            </div>
            <?php endif; ?>
            <div class="modal-buttons">
                <a href="#" class="button secondary">Fechar</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>


    <main class="manage-applicants">
        <div class="container">
            <h1>Gerenciar Inscrições</h1>
            <form class="form-filters">
                <div class="column">
                    <label for="name">Nome do aluno</label>
                    <input id="applicant-name" type="text" />
                </div>

                <div class="column half">
                    <div class="input-group">
                        <label for="course">Curso</label>
                        <div class="wrapper-select">
                            <select id="course">
                                <option value="" selected>Selecione uma opção</option>
                                <option value="1">Desenvolvimento de Software Multiplataforma</option>
                                <option value="2">Gestão Empresarial</option>
                                <option value="3">Gestão da Produção Industrial</option>
                                <option value="4">Todos os Cursos</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="column half">
                    <div class="input-group">
                        <label for="semester">Semestre</label>
                        <div class="wrapper-select">
                            <select id="semester">
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
                        <?php if (count($candidaturas) > 0): ?>
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
                                    <th scope="row"><?= htmlspecialchars($cand['nome_completo']) ?></th>
                                    <td><?= htmlspecialchars($cand['curso']) ?></td>
                                    <td><?= $cand['semestre'] ?>º Semestre</td>
                                    <td class="<?= $status_class ?>"><?= $status_texto ?></td>
                                    <td>
                                        <a href="#edit-user-modal-<?= $cand['id_candidatura'] ?>">Editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">
                                    Nenhuma candidatura encontrada.
                                </td>
                            </tr>
                        <?php endif; ?>

                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <div class="pagination">
                                    <div class="results">Mostrando 10 de 100 resultados</div>
                                    <ul>
                                        <li>
                                            <i class="fa-solid fa-chevron-left"></i>
                                        </li>
                                        <li>
                                            <a href="#">1</a>
                                        </li>
                                        <li>
                                            <a href="#">2</a>
                                        </li>
                                        <li>
                                            <a href="#">3</a>
                                        </li>
                                        <li>
                                            <a href="#">4</a>
                                        </li>
                                        <li>
                                            <a href="#">5</a>
                                        </li>
                                        <li>
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </li>
                                    </ul>
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
