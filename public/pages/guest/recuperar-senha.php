<?php
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';
require_once '../../../config/email.php';

$mensagem = "";
$tipo_mensagem = ""; // success | error
$sucesso = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validar CSRF
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente novamente.");

    $email = trim($_POST['email'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (empty($email)) {
        $mensagem = "Por favor, informe seu e-mail.";
        $tipo_mensagem = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "E-mail inválido.";
        $tipo_mensagem = "error";
    } else {
        try {
            // Verificar se o e-mail existe (aluno ou admin)
            $stmt_aluno = $conn->prepare("SELECT id_aluno, nome_completo, 'aluno' as tipo FROM ALUNO WHERE email_institucional = ? AND ativo = 1");
            $stmt_aluno->execute([$email]);
            $usuario = $stmt_aluno->fetch();

            if (!$usuario) {
                $stmt_admin = $conn->prepare("SELECT id_admin, nome_completo, 'admin' as tipo FROM ADMINISTRADOR WHERE email_corporativo = ? AND ativo = 1");
                $stmt_admin->execute([$email]);
                $usuario = $stmt_admin->fetch();
            }

            // SEMPRE mostrar mensagem de sucesso (segurança - não revelar se email existe)
            if (!$usuario) {
                // Não fazer nada, mas mostrar mensagem de sucesso
                $mensagem = "Se este e-mail estiver cadastrado, você receberá as instruções para recuperação de senha em instantes. Verifique sua caixa de entrada e spam.";
                $tipo_mensagem = "success";
                $sucesso = true;
            } else {
                // Gerar token único
                $token = bin2hex(random_bytes(32));
                $data_expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Salvar token no banco
                $stmt_token = $conn->prepare("
                    INSERT INTO tokens_recuperacao_senha
                    (email, tipo_usuario, token, data_expiracao, ip_solicitacao)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_token->execute([
                    $email,
                    $usuario['tipo'],
                    $token,
                    $data_expiracao,
                    $ip
                ]);

                // Enviar e-mail
                $emailService = new EmailService();
                $enviado = $emailService->enviarRecuperacaoSenha(
                    $email,
                    $usuario['nome_completo'],
                    $token,
                    $usuario['tipo']
                );

                if ($enviado) {
                    $mensagem = "Se este e-mail estiver cadastrado, você receberá as instruções para recuperação de senha em instantes. Verifique sua caixa de entrada e spam.";
                    $tipo_mensagem = "success";
                    $sucesso = true;
                } else {
                    error_log("Falha ao enviar e-mail de recuperação para: {$email}");
                    $mensagem = "Se este e-mail estiver cadastrado, você receberá as instruções para recuperação de senha em instantes. Verifique sua caixa de entrada e spam.";
                    $tipo_mensagem = "success"; // Ainda mostra sucesso por segurança
                    $sucesso = true;
                }
            }

        } catch (PDOException $e) {
            error_log("Erro ao processar recuperação de senha: " . $e->getMessage());
            $mensagem = "Erro ao processar solicitação. Tente novamente.";
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
    <title>Recuperar Senha - Sistema Integrado de Votações</title>
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
                <h4>Esqueceu sua senha?</h4>
                <h1>Recuperar Acesso</h1>
            </header>

            <div class="callout info">
                <div class="content">
                    <span>
                        Digite seu e-mail institucional cadastrado no sistema. Se o e-mail estiver registrado,
                        você receberá um link para redefinir sua senha. O link é válido por 1 hora.
                    </span>
                </div>
            </div>

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

            <?php if (!$sucesso): ?>
            <form method="POST">
                <?= campoCSRF() ?>

                <div class="input-group">
                    <label for="email">E-mail Institucional</label>
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="seu.email@fatec.sp.gov.br"
                            required
                            autocomplete="email"
                        >
                    </div>
                    <small style="color: #666; display: block; margin-top: 5px; font-size: 0.85em;">
                        Use o mesmo e-mail que você cadastrou no sistema
                    </small><br>
                </div>

                <button type="submit" class="button primary">
                    <i class="fas fa-paper-plane"></i>
                    Enviar Link de Recuperação
                </button>

                <a class="button secondary" href="login.php">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Login
                </a>
            </form>
            <?php else: ?>
                <a class="button primary" href="login.php">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Login
                </a>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <p style="color: #666; margin-bottom: 10px; font-size: 0.9em;">
                    <i class="fas fa-info-circle"></i>
                    Não recebeu o e-mail? Verifique sua caixa de spam.
                </p>
                <p style="color: #666; font-size: 0.85em;">
                    Se continuar com problemas, entre em contato com a secretaria acadêmica.
                </p>
            </div>
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
