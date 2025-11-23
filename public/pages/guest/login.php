<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // VALIDAR CSRF PRIMEIRO
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente fazer login novamente.");

    $email = $_POST["email"] ?? "";
    $senha = $_POST["password"] ?? "";

    if (!empty($email) && !empty($senha)) {

        // ===== LOGIN DE ADMINISTRADOR =====
        $stmtAdmin = $conn->prepare("
            SELECT id_admin, nome_completo, email_corporativo, senha_hash, ativo
            FROM ADMINISTRADOR
            WHERE email_corporativo = ? AND ativo = 1
        ");

        $stmtAdmin->execute([$email]);
        $admin = $stmtAdmin->fetch();

        if ($admin) {
            // Verificar senha com password_verify
            if (password_verify($senha, $admin["senha_hash"])) {
                // Registrar login na auditoria
                $stmtAudit = $conn->prepare("
                    INSERT INTO AUDITORIA (id_admin, tabela, operacao, descricao, ip_origem)
                    VALUES (?, 'ADMINISTRADOR', 'LOGIN', 'Login realizado', ?)
                ");
                $ip = $_SERVER['REMOTE_ADDR'];
                $stmtAudit->execute([$admin["id_admin"], $ip]);

                // Fazer login usando função da session.php
                loginAdmin($admin["id_admin"], $admin["nome_completo"], $admin["email_corporativo"]);

                header("Location: ../admin/index.php");
                exit;
            }
        }

        // ===== LOGIN DE ALUNO =====
        $stmt = $conn->prepare("
            SELECT id_aluno, nome_completo, email_institucional, senha_hash, ra, curso, semestre
            FROM ALUNO
            WHERE email_institucional = ?
        ");

        $stmt->execute([$email]);
        $aluno = $stmt->fetch();

        if ($aluno) {
            // Verificar senha com password_verify
            if (password_verify($senha, $aluno["senha_hash"])) {
                // Fazer login usando função da session.php
                loginAluno(
                    $aluno["id_aluno"],
                    $aluno["nome_completo"],
                    $aluno["email_institucional"],
                    $aluno["ra"],
                    $aluno["curso"],
                    $aluno["semestre"]
                );

                header("Location: ../user/index.php");
                exit;
            }
        }

        $erro = "E-mail ou senha incorretos!";

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

    <link rel="stylesheet" href="/assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/admin.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
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
                        O SIV é um sistema para a realização de eleições completamente online e seguro.
                        Para utilizar o sistema, preencha os campos abaixo com as suas credenciais do SIGA.
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
                        <input type="email" id="email" name="email" placeholder="seu.email@fatec.sp.gov.br" required>
                    </div>
                </div>

                <div class="input-group">
                    <label for="password">Senha</label>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
                    </div>
                </div>

                <button type="submit" class="button primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar
                </button>

                <a class="button secondary" href="../../pages/guest/suporte.php">
                    <i class="fa fa-user-times"></i>
                    Esqueci minhas credenciais
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
                <p>Eleições acadêmicas seguras e transparentes para toda a comunidade FATEC</p>
            </div>
        </div>

    </div>
</main>

<script src="../../assets/js/login.js"></script>
</body>
</html>
