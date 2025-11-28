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

// Buscar candidaturas com filtros e paginação
$filtro_curso = $_GET['curso'] ?? '';
$filtro_semestre = $_GET['semestre'] ?? '';
$filtro_nome = $_GET['nome'] ?? '';

// Paginação
$registros_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Query para contar total de registros
$sql_count = "
    SELECT COUNT(*) as total
    FROM CANDIDATURA c
    JOIN ALUNO a ON c.id_aluno = a.id_aluno
    JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
    WHERE 1=1
";

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

if (!empty($filtro_curso) && $filtro_curso !== 'Todos os Cursos') {
    $sql .= " AND e.curso = ?";
    $sql_count .= " AND e.curso = ?";
    $params[] = $filtro_curso;
}

if (!empty($filtro_semestre) && $filtro_semestre !== 'Todos Semestres') {
    $sql .= " AND e.semestre = ?";
    $sql_count .= " AND e.semestre = ?";
    $params[] = intval($filtro_semestre);
}

if (!empty($filtro_nome)) {
    $sql .= " AND a.nome_completo LIKE ?";
    $sql_count .= " AND a.nome_completo LIKE ?";
    $params[] = "%$filtro_nome%";
}

// Contar total de registros
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar registros da página atual
$sql .= " ORDER BY c.data_inscricao DESC LIMIT ? OFFSET ?";
$params[] = $registros_por_pagina;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$candidaturas = $stmt->fetchAll();

// Calcular range de registros exibidos
$primeiro_registro = $total_registros > 0 ? $offset + 1 : 0;
$ultimo_registro = min($offset + $registros_por_pagina, $total_registros);
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
    <style>
        .form-filters .button.primary:hover {
            background: #004654 !important;
        }
        .form-filters .button.secondary:hover {
            background: #5a6268 !important;
        }
    </style>
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
            <li><a href="../../pages/admin/cadastro-admin.php">Cadastro Admin</a></li>
            <li><a href="../../pages/admin/gerenciar-alunos.php">Gerenciar Alunos</a></li>
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
                <textarea readonly><?= htmlspecialchars($cand['proposta']) ?></textarea>
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
                        placeholder="Digite o motivo do indeferimento..."></textarea>
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
                <textarea readonly><?= htmlspecialchars($cand['justificativa_indeferimento']) ?></textarea>
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
            <form class="form-filters" method="GET" action="">
                <div class="column">
                    <label for="nome">Nome do aluno</label>
                    <input id="nome" name="nome" type="text" value="<?= htmlspecialchars($filtro_nome) ?>" placeholder="Digite o nome do aluno" />
                </div>

                <div class="column half">
                    <div class="input-group">
                        <label for="curso">Curso</label>
                        <div class="wrapper-select">
                            <select id="curso" name="curso">
                                <option value="">Todos os Cursos</option>
                                <option value="DSM" <?= $filtro_curso === 'DSM' ? 'selected' : '' ?>>DSM - Desenvolvimento de Software Multiplataforma</option>
                                <option value="GE" <?= $filtro_curso === 'GE' ? 'selected' : '' ?>>GE - Gestão Empresarial</option>
                                <option value="GPI" <?= $filtro_curso === 'GPI' ? 'selected' : '' ?>>GPI - Gestão da Produção Industrial</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="column half">
                    <div class="input-group">
                        <label for="semestre">Semestre</label>
                        <div class="wrapper-select">
                            <select id="semestre" name="semestre">
                                <option value="">Todos os Semestres</option>
                                <option value="1" <?= $filtro_semestre === '1' ? 'selected' : '' ?>>1º Semestre</option>
                                <option value="2" <?= $filtro_semestre === '2' ? 'selected' : '' ?>>2º Semestre</option>
                                <option value="3" <?= $filtro_semestre === '3' ? 'selected' : '' ?>>3º Semestre</option>
                                <option value="4" <?= $filtro_semestre === '4' ? 'selected' : '' ?>>4º Semestre</option>
                                <option value="5" <?= $filtro_semestre === '5' ? 'selected' : '' ?>>5º Semestre</option>
                                <option value="6" <?= $filtro_semestre === '6' ? 'selected' : '' ?>>6º Semestre</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: row; gap: 10px; align-items: flex-end; margin-top: 15px;">
                    <button type="submit" class="button primary" style="padding: 12px 30px; background: #005f73; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: background 0.3s;">
                        Aplicar Filtros
                    </button>
                    <a href="inscricoes.php" class="button secondary" style="padding: 12px 30px; background: #6c757d; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: background 0.3s;">
                        Limpar
                    </a>
                </div>
            </form>

            <div style="width: 100%; height: 2px; background-color: #999; margin: 25px 0;"></div>

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
                                <td colspan="6" style="text-align: center; padding: 40px 20px;">
                                    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 20px; display: inline-block;">
                                        <i class="fa-solid fa-info-circle" style="color: #856404; font-size: 1.5rem; margin-bottom: 10px;"></i>
                                        <p style="color: #856404; margin: 10px 0 0 0; font-weight: 500;">
                                            Nenhuma candidatura encontrada.
                                        </p>
                                        <?php if (!empty($filtro_curso) || !empty($filtro_semestre) || !empty($filtro_nome)): ?>
                                        <p style="color: #856404; margin: 15px 0 0 0; font-size: 0.9rem;">
                                            Tente ajustar os filtros ou <a href="inscricoes.php" style="display: inline-block; background: #005f73; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 5px; transition: background 0.3s;">limpar a busca</a>.
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                    <?php if ($total_registros > 0): ?>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <div class="pagination">
                                    <div class="results">
                                        Mostrando <?= $primeiro_registro ?> a <?= $ultimo_registro ?> de <?= $total_registros ?> resultado<?= $total_registros != 1 ? 's' : '' ?>
                                    </div>
                                    <?php if ($total_paginas > 1): ?>
                                    <ul>
                                        <?php
                                        // Construir query string com filtros
                                        $query_params = [];
                                        if (!empty($filtro_nome)) $query_params[] = 'nome=' . urlencode($filtro_nome);
                                        if (!empty($filtro_curso)) $query_params[] = 'curso=' . urlencode($filtro_curso);
                                        if (!empty($filtro_semestre)) $query_params[] = 'semestre=' . urlencode($filtro_semestre);
                                        $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
                                        ?>

                                        <!-- Botão Anterior -->
                                        <li>
                                            <?php if ($pagina_atual > 1): ?>
                                                <a href="?pagina=<?= $pagina_atual - 1 ?><?= $query_string ?>">
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                </a>
                                            <?php else: ?>
                                                <span style="opacity: 0.3; cursor: not-allowed;">
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                </span>
                                            <?php endif; ?>
                                        </li>

                                        <?php
                                        // Mostrar até 5 páginas
                                        $inicio = max(1, $pagina_atual - 2);
                                        $fim = min($total_paginas, $pagina_atual + 2);

                                        for ($i = $inicio; $i <= $fim; $i++):
                                        ?>
                                            <li>
                                                <a href="?pagina=<?= $i ?><?= $query_string ?>"
                                                   <?= $i == $pagina_atual ? 'style="background: #005f73; color: white; font-weight: bold;"' : '' ?>>
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <!-- Botão Próximo -->
                                        <li>
                                            <?php if ($pagina_atual < $total_paginas): ?>
                                                <a href="?pagina=<?= $pagina_atual + 1 ?><?= $query_string ?>">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </a>
                                            <?php else: ?>
                                                <span style="opacity: 0.3; cursor: not-allowed;">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
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

    <script>
    // Fechar modal ao clicar fora do conteúdo
    document.addEventListener('DOMContentLoaded', function() {
        const modals = document.querySelectorAll('.modal');

        modals.forEach(function(modal) {
            modal.addEventListener('click', function(e) {
                // Se clicou no backdrop (fora do .content), fecha o modal
                if (e.target === modal) {
                    window.location.hash = '';
                    // Alternativa: history.pushState("", document.title, window.location.pathname);
                }
            });
        });

        // Fechar modal com tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && window.location.hash) {
                window.location.hash = '';
            }
        });
    });
    </script>
</body>

</html>
