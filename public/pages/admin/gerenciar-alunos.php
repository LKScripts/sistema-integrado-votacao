<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';

verificarAdmin();

$usuario = obterUsuarioLogado();
$id_admin = $usuario['id'];

$mensagem = "";
$tipo_mensagem = ""; // success | error

// Processar ações (criar, editar)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente novamente.");

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        // Criar novo aluno
        $nome = trim($_POST["nome"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $ra = trim($_POST["ra"] ?? "");
        $curso = trim($_POST["curso"] ?? "");
        $semestre = trim($_POST["semestre"] ?? "");
        $senha = $_POST["senha"] ?? "";

        // Validações
        if (empty($nome) || empty($email) || empty($ra) || empty($curso) || empty($semestre) || empty($senha)) {
            $mensagem = "Preencha todos os campos obrigatórios.";
            $tipo_mensagem = "error";
        } elseif (strlen($senha) < 6) {
            $mensagem = "A senha deve ter pelo menos 6 caracteres.";
            $tipo_mensagem = "error";
        } else {
            try {
                // Verificar duplicatas
                $stmtCheck = $conn->prepare("SELECT id_aluno FROM ALUNO WHERE email_institucional = ? OR ra = ?");
                $stmtCheck->execute([$email, $ra]);

                if ($stmtCheck->fetch()) {
                    $mensagem = "Email ou RA já cadastrado.";
                    $tipo_mensagem = "error";
                } else {
                    $senha_hash = password_hash($senha, PASSWORD_BCRYPT);

                    $stmtInsert = $conn->prepare("
                        INSERT INTO ALUNO (nome_completo, email_institucional, ra, curso, semestre, senha_hash, ativo)
                        VALUES (?, ?, ?, ?, ?, ?, 1)
                    ");

                    if ($stmtInsert->execute([$nome, $email, $ra, $curso, $semestre, $senha_hash])) {
                        // Tentar registrar auditoria (não crítico)
                        try {
                            $stmtAudit = $conn->prepare("
                                INSERT INTO AUDITORIA (id_admin, tabela, operacao, descricao, ip_origem)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmtAudit->execute([
                                $id_admin,
                                'ALUNO',
                                'INSERT',
                                "Cadastrou aluno: $ra - $nome",
                                $_SERVER['REMOTE_ADDR']
                            ]);
                        } catch (PDOException $e) {
                            // Log do erro mas não interrompe o fluxo
                            error_log("Erro ao registrar auditoria: " . $e->getMessage());
                        }

                        $mensagem = "Aluno cadastrado com sucesso!";
                        $tipo_mensagem = "success";
                    } else {
                        $mensagem = "Erro ao cadastrar aluno.";
                        $tipo_mensagem = "error";
                    }
                }
            } catch (PDOException $e) {
                error_log("Erro ao cadastrar aluno: " . $e->getMessage());
                $mensagem = "Erro ao processar cadastro. Tente novamente.";
                $tipo_mensagem = "error";
            }
        }
    } elseif ($acao === 'editar') {
        // Editar aluno existente
        $id_aluno = $_POST["id_aluno"] ?? 0;
        $nome = trim($_POST["nome"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $ra = trim($_POST["ra"] ?? "");
        $curso = trim($_POST["curso"] ?? "");
        $semestre = trim($_POST["semestre"] ?? "");
        $senha = $_POST["senha"] ?? "";

        if (empty($nome) || empty($email) || empty($ra) || empty($curso) || empty($semestre)) {
            $mensagem = "Preencha todos os campos obrigatórios.";
            $tipo_mensagem = "error";
        } else {
            try {
                // Verificar duplicatas (exceto o próprio aluno)
                $stmtCheck = $conn->prepare("
                    SELECT id_aluno FROM ALUNO
                    WHERE (email_institucional = ? OR ra = ?) AND id_aluno != ?
                ");
                $stmtCheck->execute([$email, $ra, $id_aluno]);

                if ($stmtCheck->fetch()) {
                    $mensagem = "Email ou RA já cadastrado para outro aluno.";
                    $tipo_mensagem = "error";
                } else {
                    // Atualizar com ou sem senha
                    if (!empty($senha)) {
                        if (strlen($senha) < 6) {
                            $mensagem = "A senha deve ter pelo menos 6 caracteres.";
                            $tipo_mensagem = "error";
                        } else {
                            $senha_hash = password_hash($senha, PASSWORD_BCRYPT);
                            $stmtUpdate = $conn->prepare("
                                UPDATE ALUNO
                                SET nome_completo = ?, email_institucional = ?, ra = ?,
                                    curso = ?, semestre = ?, senha_hash = ?
                                WHERE id_aluno = ?
                            ");
                            $stmtUpdate->execute([$nome, $email, $ra, $curso, $semestre, $senha_hash, $id_aluno]);
                        }
                    } else {
                        $stmtUpdate = $conn->prepare("
                            UPDATE ALUNO
                            SET nome_completo = ?, email_institucional = ?, ra = ?,
                                curso = ?, semestre = ?
                            WHERE id_aluno = ?
                        ");
                        $stmtUpdate->execute([$nome, $email, $ra, $curso, $semestre, $id_aluno]);
                    }

                    if (isset($stmtUpdate) && $stmtUpdate->rowCount() > 0 || empty($senha)) {
                        // Tentar registrar auditoria (não crítico)
                        try {
                            $stmtAudit = $conn->prepare("
                                INSERT INTO AUDITORIA (id_admin, tabela, operacao, descricao, ip_origem)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmtAudit->execute([
                                $id_admin,
                                'ALUNO',
                                'UPDATE',
                                "Editou dados do aluno: $ra - $nome",
                                $_SERVER['REMOTE_ADDR']
                            ]);
                        } catch (PDOException $e) {
                            // Log do erro mas não interrompe o fluxo
                            error_log("Erro ao registrar auditoria de edição: " . $e->getMessage());
                        }

                        $mensagem = "Dados do aluno atualizados com sucesso!";
                        $tipo_mensagem = "success";
                    } else {
                        $mensagem = "Nenhuma alteração foi feita.";
                        $tipo_mensagem = "info";
                    }
                }
            } catch (PDOException $e) {
                error_log("Erro ao editar aluno: " . $e->getMessage());
                $mensagem = "Erro ao processar edição. Tente novamente.";
                $tipo_mensagem = "error";
            }
        }
    }
}

// Buscar e filtrar alunos com paginação
$busca = $_GET['busca'] ?? '';
$filtro_curso = $_GET['curso'] ?? '';
$filtro_semestre = $_GET['semestre'] ?? '';

// Paginação
$registros_por_pagina = 15;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Query para contar total de registros
$sql_count = "SELECT COUNT(*) as total FROM ALUNO WHERE 1=1";

// Query para buscar dados
$sql = "SELECT id_aluno, nome_completo, email_institucional, ra, curso, semestre, ativo, data_cadastro
        FROM ALUNO WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $condicao_busca = " AND (nome_completo LIKE ? OR ra LIKE ? OR email_institucional LIKE ?)";
    $sql .= $condicao_busca;
    $sql_count .= $condicao_busca;
    $busca_param = "%$busca%";
    $params[] = $busca_param;
    $params[] = $busca_param;
    $params[] = $busca_param;
}

if (!empty($filtro_curso)) {
    $condicao_curso = " AND curso = ?";
    $sql .= $condicao_curso;
    $sql_count .= $condicao_curso;
    $params[] = $filtro_curso;
}

if (!empty($filtro_semestre)) {
    $condicao_semestre = " AND semestre = ?";
    $sql .= $condicao_semestre;
    $sql_count .= $condicao_semestre;
    $params[] = $filtro_semestre;
}

// Contar total de registros
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Calcular números para exibição
$primeiro_registro = $total_registros > 0 ? $offset + 1 : 0;
$ultimo_registro = min($offset + $registros_por_pagina, $total_registros);

// Adicionar ordenação e paginação
$sql .= " ORDER BY nome_completo ASC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
// Executar com todos os parâmetros + limit + offset
$params[] = $registros_por_pagina;
$params[] = $offset;
$stmt->execute($params);
$alunos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Alunos - SIV</title>
    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/styles/modal.css">
    <link rel="stylesheet" href="../../assets/styles/gerenciar.css">
</head>
<body>
    <?php require_once 'components/header.php'; ?>

    <main class="manage-admin-registration">
        <div class="card-wrapper">
            <div class="card">
                <h1 class="title">
                    <i class="fas fa-users-cog"></i>
                    Gerenciar Alunos
                </h1>

                <?php if (!empty($mensagem)): ?>
                    <div class="message-box <?= $tipo_mensagem ?>">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>

                <!-- Botão Adicionar -->
                <button class="btn-add" onclick="abrirModalCriar()">
                    <i class="fas fa-user-plus"></i>
                    Cadastrar Novo Aluno
                </button>

                <!-- Filtros -->
                <form method="GET" class="filters-bar">
                    <div class="filter-group">
                        <label for="busca">Buscar</label>
                        <input type="text" id="busca" name="busca"
                               placeholder="Nome, RA ou email..."
                               value="<?= htmlspecialchars($busca) ?>">
                    </div>

                    <div class="filter-group">
                        <label for="curso">Curso</label>
                        <select id="curso" name="curso">
                            <option value="">Todos</option>
                            <option value="DSM" <?= $filtro_curso === 'DSM' ? 'selected' : '' ?>>DSM</option>
                            <option value="GE" <?= $filtro_curso === 'GE' ? 'selected' : '' ?>>GE</option>
                            <option value="GPI" <?= $filtro_curso === 'GPI' ? 'selected' : '' ?>>GPI</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="semestre">Semestre</label>
                        <select id="semestre" name="semestre">
                            <option value="">Todos</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>" <?= $filtro_semestre == $i ? 'selected' : '' ?>>
                                    <?= $i ?>º Semestre
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i>
                        Filtrar
                    </button>
                </form>

                <!-- Tabela de Alunos -->
                <?php if (empty($alunos)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>Nenhum aluno encontrado</h3>
                        <p>Ajuste os filtros ou cadastre um novo aluno.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>RA</th>
                                    <th>Email</th>
                                    <th>Curso</th>
                                    <th>Semestre</th>
                                    <th>Status</th>
                                    <th>Cadastro</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alunos as $aluno): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($aluno['nome_completo']) ?></td>
                                        <td><?= htmlspecialchars($aluno['ra']) ?></td>
                                        <td><?= htmlspecialchars($aluno['email_institucional']) ?></td>
                                        <td><?= htmlspecialchars($aluno['curso']) ?></td>
                                        <td><?= $aluno['semestre'] ?>º</td>
                                        <td>
                                            <span class="badge <?= $aluno['ativo'] ? 'active' : 'inactive' ?>">
                                                <?= $aluno['ativo'] ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($aluno['data_cadastro'])) ?></td>
                                        <td>
                                            <button class="btn-edit"
                                                    data-id="<?= $aluno['id_aluno'] ?>"
                                                    data-nome="<?= htmlspecialchars($aluno['nome_completo']) ?>"
                                                    data-email="<?= htmlspecialchars($aluno['email_institucional']) ?>"
                                                    data-ra="<?= htmlspecialchars($aluno['ra']) ?>"
                                                    data-curso="<?= $aluno['curso'] ?>"
                                                    data-semestre="<?= $aluno['semestre'] ?>"
                                                    onclick="abrirModalEditar(this)">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="pagination">
                        <div class="results">
                            Mostrando <?= $primeiro_registro ?> a <?= $ultimo_registro ?> de <?= $total_registros ?> aluno<?= $total_registros != 1 ? 's' : '' ?>
                        </div>
                        <?php if ($total_paginas > 1): ?>
                        <ul>
                            <?php
                            // Construir query string com filtros
                            $query_params = [];
                            if (!empty($busca)) $query_params[] = 'busca=' . urlencode($busca);
                            if (!empty($filtro_curso)) $query_params[] = 'curso=' . urlencode($filtro_curso);
                            if (!empty($filtro_semestre)) $query_params[] = 'semestre=' . urlencode($filtro_semestre);
                            $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
                            ?>

                            <!-- Botão Anterior -->
                            <li>
                                <?php if ($pagina_atual > 1): ?>
                                    <a href="?pagina=<?= $pagina_atual - 1 ?><?= $query_string ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="opacity: 0.3; cursor: not-allowed;">
                                        <i class="fas fa-chevron-left"></i>
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
                                       <?= $i == $pagina_atual ? 'class="active"' : '' ?>>
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Botão Próximo -->
                            <li>
                                <?php if ($pagina_atual < $total_paginas): ?>
                                    <a href="?pagina=<?= $pagina_atual + 1 ?><?= $query_string ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="opacity: 0.3; cursor: not-allowed;">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                <?php endif; ?>
                            </li>
                        </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Criar Aluno -->
    <div id="modalCriar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cadastrar Novo Aluno</h2>
                <button class="btn-close" onclick="fecharModal('modalCriar')">&times;</button>
            </div>

            <form method="POST" action="">
                <div class="modal-body">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="acao" value="criar">

                    <div class="input-group">
                        <label for="criar_nome">Nome Completo *</label>
                        <input type="text" id="criar_nome" name="nome" required
                               placeholder="Nome completo do aluno">
                    </div>

                    <div class="input-group">
                        <label for="criar_email">Email Institucional *</label>
                        <input type="email" id="criar_email" name="email" required
                               placeholder="aluno@fatec.sp.gov.br"
                               pattern=".*@fatec\.sp\.gov\.br$"
                               title="O email deve ser do domínio @fatec.sp.gov.br">
                    </div>

                    <div class="input-group">
                        <label for="criar_ra">RA *</label>
                        <input type="text" id="criar_ra" name="ra" required
                               placeholder="Ex: 1234567890123">
                    </div>

                    <div class="input-group">
                        <label for="criar_curso">Curso *</label>
                        <select id="criar_curso" name="curso" required>
                            <option value="">Selecione...</option>
                            <option value="DSM">DSM - Desenvolvimento de Software Multiplataforma</option>
                            <option value="GE">GE - Gestão Empresarial</option>
                            <option value="GPI">GPI - Gestão da Produção Industrial</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="criar_semestre">Semestre *</label>
                        <select id="criar_semestre" name="semestre" required>
                            <option value="">Selecione...</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?>º Semestre</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="criar_senha">Senha *</label>
                        <input type="password" id="criar_senha" name="senha" required
                               minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="button" class="button secondary" onclick="fecharModal('modalCriar')">
                        Cancelar
                    </button>
                    <button type="submit" class="button primary">
                        <i class="fas fa-save"></i> Cadastrar Aluno
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Aluno -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Aluno</h2>
                <button class="btn-close" onclick="fecharModal('modalEditar')">&times;</button>
            </div>

            <form method="POST" action="">
                <div class="modal-body">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" id="editar_id" name="id_aluno">

                    <div class="input-group">
                        <label for="editar_nome">Nome Completo *</label>
                        <input type="text" id="editar_nome" name="nome" required>
                    </div>

                    <div class="input-group">
                        <label for="editar_email">Email Institucional *</label>
                        <input type="email" id="editar_email" name="email" required
                               pattern=".*@fatec\.sp\.gov\.br$"
                               title="O email deve ser do domínio @fatec.sp.gov.br">
                    </div>

                    <div class="input-group">
                        <label for="editar_ra">RA *</label>
                        <input type="text" id="editar_ra" name="ra" required>
                    </div>

                    <div class="input-group">
                        <label for="editar_curso">Curso *</label>
                        <select id="editar_curso" name="curso" required>
                            <option value="DSM">DSM - Desenvolvimento de Software Multiplataforma</option>
                            <option value="GE">GE - Gestão Empresarial</option>
                            <option value="GPI">GPI - Gestão da Produção Industrial</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="editar_semestre">Semestre *</label>
                        <select id="editar_semestre" name="semestre" required>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?>º Semestre</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="editar_senha">Nova Senha (opcional)</label>
                        <input type="password" id="editar_senha" name="senha"
                               minlength="6" placeholder="Deixe vazio para manter senha atual">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            Preencha apenas se desejar alterar a senha do aluno
                        </small>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="button" class="button secondary" onclick="fecharModal('modalEditar')">
                        Cancelar
                    </button>
                    <button type="submit" class="button primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="site">
        <div class="content">
            <img src="../../assets/images/logo-governo-do-estado-sp.png" alt="Logo Governo SP" class="logo-governo">
            <a href="../../pages/guest/sobre.php" class="btn-about">SOBRE O SISTEMA</a>
            <p>Sistema Integrado de Votação - FATEC/CPS</p>
            <p>Versão 0.1 (11/06/2025)</p>
        </div>
    </footer>

    <script>
        function abrirModalCriar() {
            console.log('Abrindo modal criar');
            const modal = document.getElementById('modalCriar');
            if (modal) {
                modal.classList.add('show');
                console.log('Modal criar aberto');
            } else {
                console.error('Modal criar não encontrado!');
            }
        }

        function abrirModalEditar(button) {
            console.log('Abrindo modal editar', button);

            // Pegar dados dos atributos data-*
            const id = button.getAttribute('data-id');
            const nome = button.getAttribute('data-nome');
            const email = button.getAttribute('data-email');
            const ra = button.getAttribute('data-ra');
            const curso = button.getAttribute('data-curso');
            const semestre = button.getAttribute('data-semestre');

            console.log('Dados:', { id, nome, email, ra, curso, semestre });

            // Preencher o formulário
            const campos = {
                'editar_id': id,
                'editar_nome': nome,
                'editar_email': email,
                'editar_ra': ra,
                'editar_curso': curso,
                'editar_semestre': semestre,
                'editar_senha': ''
            };

            for (const [fieldId, value] of Object.entries(campos)) {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.value = value;
                } else {
                    console.error(`Campo ${fieldId} não encontrado!`);
                }
            }

            // Abrir modal
            const modal = document.getElementById('modalEditar');
            if (modal) {
                modal.classList.add('show');
                console.log('Modal editar aberto');
            } else {
                console.error('Modal editar não encontrado!');
            }
        }

        function fecharModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
                console.log('Modal fechado:', modalId);
            }
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }

        // Fechar modal com ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>
