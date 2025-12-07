<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';

$mensagem = "";
$tipo = ""; // success | error
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $mensagem = "Token de confirmação inválido.";
    $tipo = "error";
} else {
    try {
        // Buscar token no banco
        $stmt = $conn->prepare("
            SELECT id_token, tipo_usuario, id_usuario, email, data_expiracao, confirmado
            FROM email_confirmacao
            WHERE token = ?
        ");
        $stmt->execute([$token]);
        $dadosToken = $stmt->fetch();

        if (!$dadosToken) {
            $mensagem = "Token de confirmação não encontrado ou inválido.";
            $tipo = "error";
        } elseif ($dadosToken['confirmado'] == 1) {
            $mensagem = "Este e-mail já foi confirmado anteriormente. Você pode fazer login.";
            $tipo = "success";
        } elseif (strtotime($dadosToken['data_expiracao']) < time()) {
            $mensagem = "Este link de confirmação expirou. Solicite um novo cadastro.";
            $tipo = "error";
        } else {
            // Confirmar o cadastro
            $conn->beginTransaction();

            try {
                // Marcar token como confirmado
                $stmtToken = $conn->prepare("
                    UPDATE email_confirmacao
                    SET confirmado = 1, data_confirmacao = NOW()
                    WHERE id_token = ?
                ");
                $stmtToken->execute([$dadosToken['id_token']]);

                // Ativar usuário
                if ($dadosToken['tipo_usuario'] === 'aluno') {
                    $stmtAtivar = $conn->prepare("
                        UPDATE aluno
                        SET ativo = 1
                        WHERE id_aluno = ?
                    ");
                    $stmtAtivar->execute([$dadosToken['id_usuario']]);
                } else {
                    $stmtAtivar = $conn->prepare("
                        UPDATE administrador
                        SET ativo = 1
                        WHERE id_admin = ?
                    ");
                    $stmtAtivar->execute([$dadosToken['id_usuario']]);
                }

                $conn->commit();

                $mensagem = "E-mail confirmado com sucesso! Sua conta foi ativada. Você já pode fazer login.";
                $tipo = "success";

            } catch (Exception $e) {
                $conn->rollBack();
                $mensagem = "Erro ao confirmar e-mail. Tente novamente mais tarde.";
                $tipo = "error";
                error_log("Erro na confirmação: " . $e->getMessage());
            }
        }

    } catch (PDOException $e) {
        $mensagem = "Erro ao processar confirmação. Tente novamente mais tarde.";
        $tipo = "error";
        error_log("Erro no banco: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar E-mail - SIV</title>
    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
</head>
<body>
<main class="login">
    <div class="container">
        <div class="wrapper-form cadastro">
            <header>
                <h4>Sistema Integrado de Votação</h4>
                <h1>Confirmação de E-mail</h1>
            </header>

            <div class="callout" style="background-color: <?= $tipo === 'success' ? '#d4edda' : '#f8d7da' ?>; border-color: <?= $tipo === 'success' ? '#c3e6cb' : '#f5c6cb' ?>;">
                <div class="content">
                    <span style="color: <?= $tipo === 'success' ? '#155724' : '#721c24' ?>;">
                        <strong><?= htmlspecialchars($mensagem) ?></strong>
                    </span>
                </div>
            </div>

            <?php if ($tipo === 'success'): ?>
                <a href="login.php" class="button primary" style="width: 100%; margin-top: 15px;">
                    <i class="fas fa-sign-in-alt"></i>
                    Ir para o Login
                </a>
            <?php else: ?>
                <a href="cadastro.php" class="button secondary" style="width: 100%; margin-top: 15px;">
                    <i class="fas fa-user-plus"></i>
                    Voltar para Cadastro
                </a>
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
                <p>Eleições acadêmicas seguras e transparentes para toda a comunidade FATEC</p>
            </div>
        </div>
    </div>
</main>
</body>
</html>
