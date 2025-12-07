<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';
require_once '../../../config/helpers.php';

verificarAdmin();

$usuario = obterUsuarioLogado();
$id_admin_logado = $usuario['id'];

$mensagem = "";
$tipo_mensagem = ""; // success | error

// Processar ações (criar, editar, aprovar, rejeitar, remover)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente novamente.");

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        // Criar novo admin (status pendente)
        $nome = trim($_POST["nome"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $senha = $_POST["senha"] ?? "";

        // Validações
        if (empty($nome) || empty($email) || empty($senha)) {
            $mensagem = "Preencha todos os campos obrigatórios.";
            $tipo_mensagem = "error";
        } elseif (strlen($senha) < 6) {
            $mensagem = "A senha deve ter pelo menos 6 caracteres.";
            $tipo_mensagem = "error";
        } elseif (!preg_match('/@cps\.sp\.gov\.br$/i', $email)) {
            $mensagem = "O e-mail deve ser do domínio @cps.sp.gov.br";
            $tipo_mensagem = "error";
        } else {
            try {
                // Verificar email duplicado
                $stmtCheck = $conn->prepare("SELECT id_admin FROM ADMINISTRADOR WHERE email_corporativo = ?");
                $stmtCheck->execute([$email]);

                if ($stmtCheck->fetch()) {
                    $mensagem = "Este e-mail já está cadastrado.";
                    $tipo_mensagem = "error";
                } else {
                    $senha_hash = password_hash($senha, PASSWORD_BCRYPT);

                    // Inserir com status pendente (ativo = 0)
                    $stmtInsert = $conn->prepare("
                        INSERT INTO ADMINISTRADOR (nome_completo, email_corporativo, senha_hash, ativo)
                        VALUES (?, ?, ?, 0)
                    ");

                    if ($stmtInsert->execute([$nome, $email, $senha_hash])) {
                        // Registrar auditoria
                        try {
                            $stmtAudit = $conn->prepare("
                                INSERT INTO AUDITORIA (id_admin, tabela, operacao, descricao, ip_origem)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmtAudit->execute([
                                $id_admin_logado,
                                'ADMINISTRADOR',
                                'INSERT',
                                "Cadastrou admin pendente: $email",
                                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                            ]);
                        } catch (PDOException $e) {
                            error_log("Erro ao registrar auditoria: " . $e->getMessage());
                        }

                        $mensagem = "Administrador cadastrado! Status: Pendente de aprovação.";
                        $tipo_mensagem = "success";
                    } else {
                        $mensagem = "Erro ao cadastrar administrador.";
                        $tipo_mensagem = "error";
                    }
                }
            } catch (PDOException $e) {
                error_log("Erro ao cadastrar admin: " . $e->getMessage());
                $mensagem = "Erro ao processar cadastro. Tente novamente.";
                $tipo_mensagem = "error";
            }
        }
    } elseif ($acao === 'aprovar') {
        $id_admin = $_POST["id_admin"] ?? 0;

        try {
            // Buscar dados do admin ANTES da aprovação
            $stmtAntes = $conn->prepare("
                SELECT nome_completo, email_corporativo, ativo, data_cadastro
                FROM ADMINISTRADOR WHERE id_admin = ?
            ");
            $stmtAntes->execute([$id_admin]);
            $dados_antes = $stmtAntes->fetch();

            $stmtUpdate = $conn->prepare("
                UPDATE ADMINISTRADOR
                SET ativo = 1, aprovado_por = ?, data_aprovacao = NOW(), motivo_rejeicao = NULL
                WHERE id_admin = ?
            ");

            if ($stmtUpdate->execute([$id_admin_logado, $id_admin])) {
                // Registrar auditoria com before/after
                registrarAuditoria(
                    $conn,
                    $id_admin_logado,
                    'ADMINISTRADOR',
                    'UPDATE',
                    "Aprovou administrador: {$dados_antes['email_corporativo']}",
                    null,
                    null,
                    json_encode([
                        'id_admin' => $id_admin,
                        'nome' => $dados_antes['nome_completo'],
                        'email' => $dados_antes['email_corporativo'],
                        'status' => 'pendente',
                        'data_cadastro' => $dados_antes['data_cadastro']
                    ]),
                    json_encode([
                        'id_admin' => $id_admin,
                        'nome' => $dados_antes['nome_completo'],
                        'email' => $dados_antes['email_corporativo'],
                        'status' => 'aprovado',
                        'aprovado_por' => $id_admin_logado,
                        'data_aprovacao' => date('Y-m-d H:i:s')
                    ])
                );

                $mensagem = "Administrador aprovado com sucesso!";
                $tipo_mensagem = "success";
            }
        } catch (PDOException $e) {
            error_log("Erro ao aprovar admin: " . $e->getMessage());
            $mensagem = "Erro ao aprovar administrador.";
            $tipo_mensagem = "error";
        }
    } elseif ($acao === 'rejeitar') {
        $id_admin = $_POST["id_admin"] ?? 0;
        $motivo = trim($_POST["motivo"] ?? "");

        try {
            // Buscar dados do admin ANTES da rejeição
            $stmtAntes = $conn->prepare("
                SELECT nome_completo, email_corporativo, ativo, data_cadastro
                FROM ADMINISTRADOR WHERE id_admin = ?
            ");
            $stmtAntes->execute([$id_admin]);
            $dados_antes = $stmtAntes->fetch();

            $stmtUpdate = $conn->prepare("
                UPDATE ADMINISTRADOR
                SET ativo = 2, aprovado_por = ?, data_aprovacao = NOW(), motivo_rejeicao = ?
                WHERE id_admin = ?
            ");

            if ($stmtUpdate->execute([$id_admin_logado, $motivo, $id_admin])) {
                // Registrar auditoria com before/after e motivo
                registrarAuditoria(
                    $conn,
                    $id_admin_logado,
                    'ADMINISTRADOR',
                    'UPDATE',
                    "Rejeitou administrador: {$dados_antes['email_corporativo']}",
                    null,
                    null,
                    json_encode([
                        'id_admin' => $id_admin,
                        'nome' => $dados_antes['nome_completo'],
                        'email' => $dados_antes['email_corporativo'],
                        'status' => 'pendente'
                    ]),
                    json_encode([
                        'id_admin' => $id_admin,
                        'nome' => $dados_antes['nome_completo'],
                        'email' => $dados_antes['email_corporativo'],
                        'status' => 'rejeitado',
                        'rejeitado_por' => $id_admin_logado,
                        'motivo_rejeicao' => $motivo,
                        'data_rejeicao' => date('Y-m-d H:i:s')
                    ])
                );

                $mensagem = "Administrador rejeitado.";
                $tipo_mensagem = "success";
            }
        } catch (PDOException $e) {
            error_log("Erro ao rejeitar admin: " . $e->getMessage());
            $mensagem = "Erro ao rejeitar administrador.";
            $tipo_mensagem = "error";
        }
    } elseif ($acao === 'remover') {
        $id_admin = $_POST["id_admin"] ?? 0;
        $motivo = trim($_POST["motivo"] ?? "");

        // Não permitir remover a si mesmo
        if ($id_admin == $id_admin_logado) {
            $mensagem = "Você não pode remover sua própria conta.";
            $tipo_mensagem = "error";
        } else {
            try {
                $stmtUpdate = $conn->prepare("
                    UPDATE ADMINISTRADOR
                    SET ativo = 2, motivo_rejeicao = ?
                    WHERE id_admin = ?
                ");

                if ($stmtUpdate->execute([$motivo, $id_admin])) {
                    // Auditoria
                    try {
                        $stmtAudit = $conn->prepare("
                            INSERT INTO AUDITORIA (id_admin, tabela, operacao, descricao, ip_origem)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmtAudit->execute([
                            $id_admin_logado,
                            'ADMINISTRADOR',
                            'UPDATE',
                            "Removeu admin ID: $id_admin. Motivo: $motivo",
                            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                        ]);
                    } catch (PDOException $e) {
                        error_log("Erro ao registrar auditoria: " . $e->getMessage());
                    }

                    $mensagem = "Administrador removido com sucesso.";
                    $tipo_mensagem = "success";
                }
            } catch (PDOException $e) {
                error_log("Erro ao remover admin: " . $e->getMessage());
                $mensagem = "Erro ao remover administrador.";
                $tipo_mensagem = "error";
            }
        }
    } elseif ($acao === 'reativar') {
        $id_admin = $_POST["id_admin"] ?? 0;

        try {
            $stmtUpdate = $conn->prepare("
                UPDATE ADMINISTRADOR
                SET ativo = 1, aprovado_por = ?, data_aprovacao = NOW(), motivo_rejeicao = NULL
                WHERE id_admin = ?
            ");

            if ($stmtUpdate->execute([$id_admin_logado, $id_admin])) {
                // Auditoria
                try {
                    $stmtAudit = $conn->prepare("
                        INSERT INTO AUDITORIA (id_admin, tabela, operacao, descricao, ip_origem)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmtAudit->execute([
                        $id_admin_logado,
                        'ADMINISTRADOR',
                        'UPDATE',
                        "Reativou admin ID: $id_admin",
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                } catch (PDOException $e) {
                    error_log("Erro ao registrar auditoria: " . $e->getMessage());
                }

                $mensagem = "Administrador reativado com sucesso!";
                $tipo_mensagem = "success";
            }
        } catch (PDOException $e) {
            error_log("Erro ao reativar admin: " . $e->getMessage());
            $mensagem = "Erro ao reativar administrador.";
            $tipo_mensagem = "error";
        }
    } elseif ($acao === 'editar') {
        $id_admin = $_POST["id_admin"] ?? 0;
        $nome = trim($_POST["nome"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $senha = $_POST["senha"] ?? "";

        if (empty($nome) || empty($email)) {
            $mensagem = "Preencha todos os campos obrigatórios.";
            $tipo_mensagem = "error";
        } else {
            try {
                // Verificar email duplicado (exceto o próprio admin)
                $stmtCheck = $conn->prepare("
                    SELECT id_admin FROM ADMINISTRADOR
                    WHERE email_corporativo = ? AND id_admin != ?
                ");
                $stmtCheck->execute([$email, $id_admin]);

                if ($stmtCheck->fetch()) {
                    $mensagem = "Este e-mail já está cadastrado.";
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
                                UPDATE ADMINISTRADOR
                                SET nome_completo = ?, email_corporativo = ?, senha_hash = ?
                                WHERE id_admin = ?
                            ");
                            $stmtUpdate->execute([$nome, $email, $senha_hash, $id_admin]);
                        }
                    } else {
                        $stmtUpdate = $conn->prepare("
                            UPDATE ADMINISTRADOR
                            SET nome_completo = ?, email_corporativo = ?
                            WHERE id_admin = ?
                        ");
                        $stmtUpdate->execute([$nome, $email, $id_admin]);
                    }

                    if (isset($stmtUpdate) && $stmtUpdate->rowCount() >= 0) {
                        // Auditoria
                        try {
                            $stmtAudit = $conn->prepare("
                                INSERT INTO AUDITORIA (id_admin, tabela, operacao, descricao, ip_origem)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmtAudit->execute([
                                $id_admin_logado,
                                'ADMINISTRADOR',
                                'UPDATE',
                                "Editou dados do admin: $email",
                                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                            ]);
                        } catch (PDOException $e) {
                            error_log("Erro ao registrar auditoria: " . $e->getMessage());
                        }

                        $mensagem = "Dados atualizados com sucesso!";
                        $tipo_mensagem = "success";
                    }
                }
            } catch (PDOException $e) {
                error_log("Erro ao editar admin: " . $e->getMessage());
                $mensagem = "Erro ao processar edição. Tente novamente.";
                $tipo_mensagem = "error";
            }
        }
    }
}

// Buscar e filtrar admins com paginação
$busca = $_GET['busca'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Paginação
$registros_por_pagina = 15;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Query para contar total de registros
$sql_count = "SELECT COUNT(*) as total FROM ADMINISTRADOR WHERE 1=1";

// Query para buscar dados
$sql = "SELECT id_admin, nome_completo, email_corporativo, ativo, data_cadastro,
               aprovado_por, data_aprovacao, ultimo_acesso
        FROM ADMINISTRADOR WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $condicao_busca = " AND (nome_completo LIKE ? OR email_corporativo LIKE ?)";
    $sql .= $condicao_busca;
    $sql_count .= $condicao_busca;
    $busca_param = "%$busca%";
    $params[] = $busca_param;
    $params[] = $busca_param;
}

if ($filtro_status !== '') {
    $condicao_status = " AND ativo = ?";
    $sql .= $condicao_status;
    $sql_count .= $condicao_status;
    $params[] = $filtro_status;
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
$sql .= " ORDER BY
          CASE
            WHEN ativo = 0 THEN 1
            WHEN ativo = 1 THEN 2
            WHEN ativo = 2 THEN 3
          END,
          nome_completo ASC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $registros_por_pagina;
$params[] = $offset;
$stmt->execute($params);
$admins = $stmt->fetchAll();

// Buscar nomes dos aprovadores
$aprovadores = [];
$stmt_aprovadores = $conn->query("SELECT id_admin, nome_completo FROM ADMINISTRADOR");
while ($row = $stmt_aprovadores->fetch()) {
    $aprovadores[$row['id_admin']] = $row['nome_completo'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Administradores - SIV</title>
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
                    <i class="fas fa-user-shield"></i>
                    Gerenciar Administradores
                </h1>

                <?php if (!empty($mensagem)): ?>
                    <div class="message-box <?= $tipo_mensagem ?>">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>

                <!-- Botão Adicionar -->
                <button class="btn-add" onclick="abrirModalCriar()">
                    <i class="fas fa-user-plus"></i>
                    Cadastrar Novo Administrador
                </button>

                <!-- Filtros -->
                <form method="GET" class="filters-bar">
                    <div class="filter-group">
                        <label for="busca">Buscar</label>
                        <input type="text" id="busca" name="busca"
                               placeholder="Nome ou email..."
                               value="<?= htmlspecialchars($busca) ?>">
                    </div>

                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">Todos</option>
                            <option value="0" <?= $filtro_status === '0' ? 'selected' : '' ?>>Pendente</option>
                            <option value="1" <?= $filtro_status === '1' ? 'selected' : '' ?>>Ativo</option>
                            <option value="2" <?= $filtro_status === '2' ? 'selected' : '' ?>>Removido</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i>
                        Filtrar
                    </button>
                </form>

                <!-- Tabela de Admins -->
                <?php if (empty($admins)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-shield"></i>
                        <h3>Nenhum administrador encontrado</h3>
                        <p>Ajuste os filtros ou cadastre um novo administrador.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Cadastro</th>
                                    <th>Aprovado Por</th>
                                    <th>Último Acesso</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($admin['nome_completo']) ?></td>
                                        <td><?= htmlspecialchars($admin['email_corporativo']) ?></td>
                                        <td>
                                            <?php if ($admin['ativo'] == 0): ?>
                                                <span class="badge pendente">Pendente</span>
                                            <?php elseif ($admin['ativo'] == 1): ?>
                                                <span class="badge ativo">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge removido">Removido</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($admin['data_cadastro'])) ?></td>
                                        <td>
                                            <?php
                                            if ($admin['aprovado_por']) {
                                                echo htmlspecialchars($aprovadores[$admin['aprovado_por']] ?? 'N/A');
                                                if ($admin['data_aprovacao']) {
                                                    echo '<br><small>' . date('d/m/Y', strtotime($admin['data_aprovacao'])) . '</small>';
                                                }
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?= $admin['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($admin['ultimo_acesso'])) : 'Nunca' ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($admin['ativo'] == 0): ?>
                                                    <!-- Pendente: Aprovar ou Rejeitar -->
                                                    <button class="btn-approve"
                                                            onclick="aprovarAdmin(<?= $admin['id_admin'] ?>)">
                                                        <i class="fas fa-check"></i> Aprovar
                                                    </button>
                                                    <button class="btn-reject"
                                                            onclick="abrirModalRejeitar(<?= $admin['id_admin'] ?>)">
                                                        <i class="fas fa-times"></i> Rejeitar
                                                    </button>
                                                <?php elseif ($admin['ativo'] == 1): ?>
                                                    <!-- Ativo: Editar ou Remover -->
                                                    <?php if ($admin['id_admin'] != $id_admin_logado): ?>
                                                        <button class="btn-edit"
                                                                data-id="<?= $admin['id_admin'] ?>"
                                                                data-nome="<?= htmlspecialchars($admin['nome_completo']) ?>"
                                                                data-email="<?= htmlspecialchars($admin['email_corporativo']) ?>"
                                                                onclick="abrirModalEditar(this)">
                                                            <i class="fas fa-edit"></i> Editar
                                                        </button>
                                                        <button class="btn-remove"
                                                                onclick="abrirModalRemover(<?= $admin['id_admin'] ?>)">
                                                            <i class="fas fa-user-times"></i> Remover
                                                        </button>
                                                    <?php else: ?>
                                                        <span style="font-size: 11px; color: #999;">Você mesmo</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <!-- Removido: Reativar -->
                                                    <button class="btn-reactivate"
                                                            onclick="reativarAdmin(<?= $admin['id_admin'] ?>)">
                                                        <i class="fas fa-redo"></i> Reativar
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="pagination">
                        <div class="results">
                            Mostrando <?= $primeiro_registro ?> a <?= $ultimo_registro ?> de <?= $total_registros ?> administrador<?= $total_registros != 1 ? 'es' : '' ?>
                        </div>
                        <?php if ($total_paginas > 1): ?>
                        <ul>
                            <?php
                            $query_params = [];
                            if (!empty($busca)) $query_params[] = 'busca=' . urlencode($busca);
                            if ($filtro_status !== '') $query_params[] = 'status=' . urlencode($filtro_status);
                            $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
                            ?>

                            <li>
                                <?php if ($pagina_atual > 1): ?>
                                    <a href="?pagina=<?= $pagina_atual - 1 ?><?= $query_string ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span><i class="fas fa-chevron-left"></i></span>
                                <?php endif; ?>
                            </li>

                            <?php
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

                            <li>
                                <?php if ($pagina_atual < $total_paginas): ?>
                                    <a href="?pagina=<?= $pagina_atual + 1 ?><?= $query_string ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span><i class="fas fa-chevron-right"></i></span>
                                <?php endif; ?>
                            </li>
                        </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Criar Admin -->
    <div id="modalCriar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cadastrar Novo Administrador</h2>
                <button class="btn-close" onclick="fecharModal('modalCriar')">&times;</button>
            </div>

            <form method="POST" action="">
                <div class="modal-body">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="acao" value="criar">

                    <div class="input-group">
                        <label for="criar_nome">Nome Completo *</label>
                        <input type="text" id="criar_nome" name="nome" required
                               placeholder="Nome completo do administrador">
                    </div>

                    <div class="input-group">
                        <label for="criar_email">Email Corporativo (@cps.sp.gov.br) *</label>
                        <input type="email" id="criar_email" name="email" required
                               placeholder="usuario@cps.sp.gov.br"
                               pattern=".*@cps\.sp\.gov\.br$"
                               title="O email deve ser do domínio @cps.sp.gov.br">
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
                        <i class="fas fa-save"></i> Cadastrar Administrador
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Admin -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Administrador</h2>
                <button class="btn-close" onclick="fecharModal('modalEditar')">&times;</button>
            </div>

            <form method="POST" action="">
                <div class="modal-body">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" id="editar_id" name="id_admin">

                    <div class="input-group">
                        <label for="editar_nome">Nome Completo *</label>
                        <input type="text" id="editar_nome" name="nome" required>
                    </div>

                    <div class="input-group">
                        <label for="editar_email">Email Corporativo *</label>
                        <input type="email" id="editar_email" name="email" required
                               pattern=".*@cps\.sp\.gov\.br$"
                               title="O email deve ser do domínio @cps.sp.gov.br">
                    </div>

                    <div class="input-group">
                        <label for="editar_senha">Nova Senha (opcional)</label>
                        <input type="password" id="editar_senha" name="senha"
                               minlength="6" placeholder="Deixe vazio para manter senha atual">
                        <small class="info-text">
                            <i class="fas fa-info-circle"></i>
                            Preencha apenas se desejar alterar a senha
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

    <!-- Modal Rejeitar Admin -->
    <div id="modalRejeitar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Rejeitar Administrador</h2>
                <button class="btn-close" onclick="fecharModal('modalRejeitar')">&times;</button>
            </div>

            <form method="POST" action="">
                <div class="modal-body">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="acao" value="rejeitar">
                    <input type="hidden" id="rejeitar_id" name="id_admin">

                    <div class="input-group">
                        <label for="rejeitar_motivo">Motivo da Rejeição *</label>
                        <textarea id="rejeitar_motivo" name="motivo" rows="4" required
                                  placeholder="Descreva o motivo da rejeição desta solicitação..."></textarea>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="button" class="button secondary" onclick="fecharModal('modalRejeitar')">
                        Cancelar
                    </button>
                    <button type="submit" class="button primary" style="background: #dc3545;">
                        <i class="fas fa-times"></i> Confirmar Rejeição
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Remover Admin -->
    <div id="modalRemover" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Remover Administrador</h2>
                <button class="btn-close" onclick="fecharModal('modalRemover')">&times;</button>
            </div>

            <form method="POST" action="">
                <div class="modal-body">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="acao" value="remover">
                    <input type="hidden" id="remover_id" name="id_admin">

                    <div class="input-group">
                        <label for="remover_motivo">Motivo da Remoção *</label>
                        <textarea id="remover_motivo" name="motivo" rows="4" required
                                  placeholder="Descreva o motivo da remoção deste administrador..."></textarea>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="button" class="button secondary" onclick="fecharModal('modalRemover')">
                        Cancelar
                    </button>
                    <button type="submit" class="button primary" style="background: #6c757d;">
                        <i class="fas fa-user-times"></i> Confirmar Remoção
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
            document.getElementById('modalCriar').classList.add('show');
        }

        function abrirModalEditar(button) {
            const id = button.getAttribute('data-id');
            const nome = button.getAttribute('data-nome');
            const email = button.getAttribute('data-email');

            document.getElementById('editar_id').value = id;
            document.getElementById('editar_nome').value = nome;
            document.getElementById('editar_email').value = email;
            document.getElementById('editar_senha').value = '';

            document.getElementById('modalEditar').classList.add('show');
        }

        function abrirModalRejeitar(id) {
            document.getElementById('rejeitar_id').value = id;
            document.getElementById('rejeitar_motivo').value = '';
            document.getElementById('modalRejeitar').classList.add('show');
        }

        function abrirModalRemover(id) {
            document.getElementById('remover_id').value = id;
            document.getElementById('remover_motivo').value = '';
            document.getElementById('modalRemover').classList.add('show');
        }

        function fecharModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function aprovarAdmin(id) {
            if (confirm('Tem certeza que deseja aprovar este administrador?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <?= campoCSRF() ?>
                    <input type="hidden" name="acao" value="aprovar">
                    <input type="hidden" name="id_admin" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function reativarAdmin(id) {
            if (confirm('Tem certeza que deseja reativar este administrador?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <?= campoCSRF() ?>
                    <input type="hidden" name="acao" value="reativar">
                    <input type="hidden" name="id_admin" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
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
