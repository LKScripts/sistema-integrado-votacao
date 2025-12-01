<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';

// Verificar se é administrador
verificarAdmin();

$usuario = obterUsuarioLogado();
$id_admin_criador = $usuario['id'];

$mensagem = "";
$tipo_mensagem = ""; // success | error

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // VALIDAR CSRF PRIMEIRO
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente novamente.");

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirma_senha = $_POST["confirma_senha"] ?? "";

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha) || empty($confirma_senha)) {
        $mensagem = "Preencha todos os campos obrigatórios.";
        $tipo_mensagem = "error";
    } elseif ($senha !== $confirma_senha) {
        $mensagem = "As senhas não coincidem!";
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
            $stmtEmail = $conn->prepare("SELECT id_admin FROM ADMINISTRADOR WHERE email_corporativo = ?");
            $stmtEmail->execute([$email]);

            if ($stmtEmail->fetch()) {
                $mensagem = "Este e-mail já está cadastrado como administrador.";
                $tipo_mensagem = "error";
            } else {
                $senha_hash = password_hash($senha, PASSWORD_BCRYPT);

                $stmtInsert = $conn->prepare("
                    INSERT INTO ADMINISTRADOR (nome_completo, email_corporativo, senha_hash, ativo)
                    VALUES (?, ?, ?, 1)
                ");

                if ($stmtInsert->execute([$nome, $email, $senha_hash])) {
                    // Registrar na auditoria
                    $stmtAuditoria = $conn->prepare("
                        INSERT INTO AUDITORIA (id_admin, acao, ip_origem)
                        VALUES (?, ?, ?)
                    ");
                    $stmtAuditoria->execute([
                        $id_admin_criador,
                        "Cadastrou novo administrador: $email",
                        $_SERVER['REMOTE_ADDR']
                    ]);

                    $mensagem = "Administrador cadastrado com sucesso!";
                    $tipo_mensagem = "success";

                    // Limpar campos após sucesso
                    $nome = $email = $senha = $confirma_senha = "";
                } else {
                    $mensagem = "Erro ao cadastrar administrador.";
                    $tipo_mensagem = "error";
                }
            }
        } catch (PDOException $e) {
            // Logar erro completo para debug
            error_log("Erro ao cadastrar admin (por outro admin): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Mensagem genérica para o usuário
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $mensagem = "Este e-mail já está cadastrado no sistema.";
            } else {
                $mensagem = "Erro ao processar cadastro. Tente novamente.";
            }
            $tipo_mensagem = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Novo Administrador - SIV</title>
    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
</head>
<body>
    <?php require_once 'components/header.php'; ?>

    <main class="manage-admin-registration">
        <div class="card-wrapper">
            <div class="card">
                <h1 class="title">Cadastrar Novo Administrador</h1>

                <div class="callout info">
                    <div class="content">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>
                            Apenas administradores podem criar novas contas de administrador.
                            O novo administrador terá acesso total ao sistema.
                        </span>
                    </div>
                </div>

                <?php if (!empty($mensagem)): ?>
                    <div class="message-box <?= $tipo_mensagem ?>">
                        <?= $mensagem ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="form-admin-registration">
                    <?= campoCSRF() ?>

                    <div class="input-group">
                        <label for="nome">Nome Completo *</label>
                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            value="<?= htmlspecialchars($nome ?? '') ?>"
                            required
                            placeholder="Digite o nome completo"
                        >
                    </div>

                    <div class="input-group">
                        <label for="email">E-mail Corporativo (@cps.sp.gov.br) *</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email ?? '') ?>"
                            required
                            placeholder="usuario@cps.sp.gov.br"
                            pattern=".*@cps\.sp\.gov\.br$"
                            title="O e-mail deve ser do domínio @cps.sp.gov.br"
                        >
                    </div>

                    <div class="input-group">
                        <label for="senha">Senha *</label>
                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            required
                            minlength="6"
                            placeholder="Mínimo 6 caracteres"
                        >
                    </div>

                    <div class="input-group">
                        <label for="confirma_senha">Confirmar Senha *</label>
                        <input
                            type="password"
                            id="confirma_senha"
                            name="confirma_senha"
                            required
                            minlength="6"
                            placeholder="Digite a senha novamente"
                        >
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="button primary">
                            Cadastrar Administrador
                        </button>
                    </div>
                </form>
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
