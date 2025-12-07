<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';
require_once '../../../config/rate_limit.php';
require_once '../../../config/helpers.php';

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // VALIDAR CSRF PRIMEIRO
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente fazer login novamente.");

    $email = $_POST["email"] ?? "";
    $senha = $_POST["password"] ?? "";
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (!empty($email) && !empty($senha)) {

        // ===== VERIFICAR RATE LIMITING =====
        $bloqueio = verificarBloqueio($email, $ip);

        if ($bloqueio['bloqueado']) {
            $tempo = formatarTempoRestante($bloqueio['tempo_restante']);
            $erro = "Muitas tentativas de login falhadas. Tente novamente em {$tempo}.";
            error_log("Login bloqueado por rate limit - Email: {$email}, IP: {$ip}");
        } else {

        // ===== LOGIN DE ADMINISTRADOR =====
        try {
            $stmtAdmin = $conn->prepare("
                SELECT id_admin, nome_completo, email_corporativo, senha_hash, ativo, email_confirmado
                FROM ADMINISTRADOR
                WHERE email_corporativo = ? AND ativo = 1 AND email_confirmado = 1
            ");

            $stmtAdmin->execute([$email]);
            $admin = $stmtAdmin->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar admin no login: " . $e->getMessage());
            $erro = "Erro ao processar login. Tente novamente.";
            $admin = false;
        }

        if ($admin) {
            // Verificar senha com password_verify
            if (password_verify($senha, $admin["senha_hash"])) {
                // ===== LOGIN BEM-SUCEDIDO =====
                // Limpar tentativas anteriores
                limparTentativas($email, $ip);

                // Registrar sucesso na tabela de tentativas
                registrarTentativaLogin($email, $ip, true);

                // Registrar login na auditoria com dados completos
                registrarAuditoria(
                    $conn,
                    $admin["id_admin"],
                    'ADMINISTRADOR',
                    'LOGIN',
                    "Login bem-sucedido - {$admin['nome_completo']} ({$admin['email_corporativo']})",
                    $ip,
                    null,  // Não relacionado a eleição
                    null,  // Sem dados anteriores
                    json_encode([
                        'id_admin' => $admin["id_admin"],
                        'nome' => $admin["nome_completo"],
                        'email' => $admin["email_corporativo"],
                        'timestamp' => date('Y-m-d H:i:s'),
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ])
                );

                // Fazer login usando função da session.php
                loginAdmin($admin["id_admin"], $admin["nome_completo"], $admin["email_corporativo"]);

                header("Location: ../admin/index.php");
                exit;
            }
        }

        // ===== LOGIN DE ALUNO =====
        try {
            $stmt = $conn->prepare("
                SELECT id_aluno, nome_completo, email_institucional, senha_hash, ra, curso, semestre, ativo, foto_perfil
                FROM ALUNO
                WHERE email_institucional = ?
            ");

            $stmt->execute([$email]);
            $aluno = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar aluno no login: " . $e->getMessage());
            $erro = "Erro ao processar login. Tente novamente.";
            $aluno = false;
        }

        if ($aluno) {
            // Verificar se a conta está ativa
            if ($aluno["ativo"] != 1) {
                $erro = "Sua conta ainda não foi ativada. Verifique seu e-mail e clique no link de confirmação.";
            }
            // Verificar senha com password_verify
            elseif (password_verify($senha, $aluno["senha_hash"])) {
                // ===== LOGIN BEM-SUCEDIDO =====
                // Limpar tentativas anteriores
                limparTentativas($email, $ip);

                // Registrar sucesso na tabela de tentativas
                registrarTentativaLogin($email, $ip, true);

                // Fazer login usando função da session.php
                loginAluno(
                    $aluno["id_aluno"],
                    $aluno["nome_completo"],
                    $aluno["email_institucional"],
                    $aluno["ra"],
                    $aluno["curso"],
                    $aluno["semestre"],
                    $aluno["foto_perfil"]
                );

                header("Location: ../user/index.php");
                exit;
            }
        }

        // ===== LOGIN FALHOU =====
        // Registrar tentativa falha
        registrarTentativaLogin($email, $ip, false);

        $erro = "E-mail ou senha incorretos!";

        } // Fecha o bloco do rate limiting (else do if bloqueado)

    } else {
        $erro = "Preencha todos os campos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Sistema Integrado de Votações</title>
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
                <h4>Bem vindo ao SIV</h4>
                <h1>Faça seu Login!</h1>
            </header>

            <div class="callout info">
                <div class="content">
                    <span>
                        O SIV é um sistema para realização de eleições acadêmicas completamente online e seguro,
                        com proteção contra tentativas indevidas de acesso e sistema de auditoria integrado.
                        Para acessar, utilize seu email institucional (@fatec.sp.gov.br ou @cps.sp.gov.br) e senha cadastrados.
                    </span>
                </div>
            </div>

            <?php if(!empty($erro)): ?>
                <div class="callout warning">
                    <div class="content">
                        <span><?= $erro ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?= campoCSRF() ?>
                <div class="input-group">
                    <label for="email">Email</label>
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="seu.email@fatec.sp.gov.br" autocomplete="email" required>
                    </div>
                </div>

                <div class="input-group">
                    <label for="password">Senha</label>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Digite sua senha" autocomplete="current-password" required>
                    </div>
                </div>

                <button type="submit" class="button primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar
                </button>

                <a class="button secondary" href="../../pages/guest/recuperar-senha.php">
                    <i class="fa fa-key"></i>
                    Esqueci minha senha
                </a>

                <a class="button secondary" href="../../index.php">
                    <i class="fas fa-home"></i>
                    Voltar à Homepage
                </a>
            </form>

            <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <p style="color: #666; margin-bottom: 10px;">Ainda não tem uma conta?</p>
                <a href="cadastro.php" style="color: #b20000; font-weight: 600; text-decoration: none;">
                    <i class="fas fa-user-plus"></i>
                    Cadastre-se aqui
                </a>
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

<script src="../../assets/js/login.js"></script>
</body>
</html>
