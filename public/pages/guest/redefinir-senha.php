<?php
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';

$mensagem = "";
$tipo_mensagem = ""; // success | error
$token_valido = false;
$token = $_GET['token'] ?? '';
$senha_redefinida = false;

// Verificar token
if (!empty($token)) {
    try {
        $stmt = $conn->prepare("
            SELECT email, tipo_usuario, data_expiracao, usado
            FROM tokens_recuperacao_senha
            WHERE token = ?
        ");
        $stmt->execute([$token]);
        $token_data = $stmt->fetch();

        if (!$token_data) {
            $mensagem = "Link de recuperação inválido.";
            $tipo_mensagem = "error";
        } elseif ($token_data['usado'] == 1) {
            $mensagem = "Este link de recuperação já foi utilizado. Solicite um novo link se necessário.";
            $tipo_mensagem = "error";
        } elseif (strtotime($token_data['data_expiracao']) < time()) {
            $mensagem = "Este link de recuperação expirou. Solicite um novo link.";
            $tipo_mensagem = "error";
        } else {
            $token_valido = true;
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar token: " . $e->getMessage());
        $mensagem = "Erro ao verificar link. Tente novamente.";
        $tipo_mensagem = "error";
    }
} else {
    $mensagem = "Link de recuperação inválido.";
    $tipo_mensagem = "error";
}

// Processar redefinição de senha
if ($_SERVER["REQUEST_METHOD"] === "POST" && $token_valido) {
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente novamente.");

    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (empty($senha) || empty($confirmar_senha)) {
        $mensagem = "Preencha todos os campos.";
        $tipo_mensagem = "error";
    } elseif (strlen($senha) < 6) {
        $mensagem = "A senha deve ter no mínimo 6 caracteres.";
        $tipo_mensagem = "error";
    } elseif ($senha !== $confirmar_senha) {
        $mensagem = "As senhas não coincidem.";
        $tipo_mensagem = "error";
    } else {
        try {
            $conn->beginTransaction();

            // Hash da nova senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Atualizar senha do usuário
            if ($token_data['tipo_usuario'] === 'aluno') {
                $stmt_update = $conn->prepare("UPDATE ALUNO SET senha_hash = ? WHERE email_institucional = ?");
            } else {
                $stmt_update = $conn->prepare("UPDATE ADMINISTRADOR SET senha_hash = ? WHERE email_corporativo = ?");
            }
            $stmt_update->execute([$senha_hash, $token_data['email']]);

            // Marcar token como usado
            $stmt_usar_token = $conn->prepare("
                UPDATE tokens_recuperacao_senha
                SET usado = 1, data_uso = NOW(), ip_uso = ?
                WHERE token = ?
            ");
            $stmt_usar_token->execute([$ip, $token]);

            $conn->commit();

            $mensagem = "Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.";
            $tipo_mensagem = "success";
            $token_valido = false; // Impedir nova submissão
            $senha_redefinida = true;

        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Erro ao redefinir senha: " . $e->getMessage());
            $mensagem = "Erro ao redefinir senha. Tente novamente.";
            $tipo_mensagem = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Sistema Integrado de Votações</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
</head>
<body>
<main class="login">
    <div class="container">
        <div class="wrapper-form">
            <header>
                <h4><?= $senha_redefinida ? 'Senha Atualizada!' : 'Redefinir Senha' ?></h4>
                <h1><?= $senha_redefinida ? 'Tudo Pronto!' : 'Nova Senha' ?></h1>
            </header>

            <?php if ($token_valido): ?>
                <div class="callout info">
                    <div class="content">
                        <span>
                            <strong>E-mail:</strong> <?= htmlspecialchars($token_data['email']) ?><br>
                            Digite sua nova senha abaixo. A senha deve ter no mínimo 6 caracteres.
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(!empty($mensagem) && $tipo_mensagem === "error"): ?>
                <div class="callout warning">
                    <div class="content">
                        <span><?= htmlspecialchars($mensagem) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(!empty($mensagem) && $tipo_mensagem === "success"): ?>
                <div class="callout success">
                    <div class="content">
                        <span><?= htmlspecialchars($mensagem) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($token_valido && !$senha_redefinida): ?>
                <form method="POST">
                    <?= campoCSRF() ?>

                    <div class="input-group">
                        <label for="senha">Nova Senha</label>
                        <div class="input-field">
                            <i class="fas fa-lock"></i>
                            <input
                                type="password"
                                id="senha"
                                name="senha"
                                placeholder="Mínimo 6 caracteres"
                                required
                                minlength="6"
                                autocomplete="new-password"
                            >
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="confirmar_senha">Confirmar Nova Senha</label>
                        <div class="input-field">
                            <i class="fas fa-lock"></i>
                            <input
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                placeholder="Digite a senha novamente"
                                required
                                minlength="6"
                                autocomplete="new-password"
                            >
                        </div>
                    </div>

                    <button type="submit" class="button primary">
                        <i class="fas fa-check"></i>
                        Redefinir Senha
                    </button>
                </form>

                <div style="background-color: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 4px;">
                    <p style="margin: 0; color: #856404; font-size: 0.9em;">
                        <i class="fas fa-shield-alt"></i>
                        <strong>Dicas de Segurança:</strong><br>
                        • Use uma senha forte e única<br>
                        • Não compartilhe sua senha com ninguém<br>
                        • Evite usar informações pessoais óbvias
                    </p>
                </div>
            <?php else: ?>
                <a class="button primary" href="<?= $senha_redefinida ? 'login.php' : 'recuperar-senha.php' ?>">
                    <i class="fas fa-<?= $senha_redefinida ? 'sign-in-alt' : 'redo' ?>"></i>
                    <?= $senha_redefinida ? 'Fazer Login' : 'Solicitar Novo Link' ?>
                </a>
            <?php endif; ?>

            <a class="button secondary" href="login.php">
                <i class="fas fa-arrow-left"></i>
                Voltar ao Login
            </a>

            <?php if (!$token_valido && !$senha_redefinida): ?>
                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                    <p style="color: #666; font-size: 0.9em;">
                        O link pode ter expirado ou já foi utilizado.<br>
                        Solicite um novo link de recuperação se necessário.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="wrapper-visual">
            <div class="wrapper-fatec">
                <div class="decoration"></div>
                <img src="../../assets/images/fatec-ogari.png" alt="Logo FATEC" width="120" />
            </div>
            <div class="wrapper-siv">
                <div class="decoration"></div>
                <img src="../../assets/images/logo-novo.png" alt="Logo SIV" width="140" />
                <h2>Sistema Integrado de Votações</h2>
                <p>Plataforma completa para eleições acadêmicas: segura, automatizada e transparente. Sistema com backend robusto, auditoria imutável e gestão automatizada de prazos eleitorais.</p>
            </div>
        </div>

    </div>
</main>
</body>
</html>
