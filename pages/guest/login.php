<?php
session_start();
include '../../conexao.php'; // conexão com o banco

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST["email"] ?? "";
    $senha = $_POST["password"] ?? "";

    if (!empty($email) && !empty($senha)) {

        // ===== LOGIN DE ADMINISTRADOR =====
        $stmtAdmin = $conn->prepare("
            SELECT id_admin, nome_completo, email_corporativo, senha_hash 
            FROM administrador 
            WHERE email_corporativo = ? AND senha_hash = ?
        ");

        $stmtAdmin->bind_param("ss", $email, $senha);
        $stmtAdmin->execute();
        $resultadoAdmin = $stmtAdmin->get_result();

        if ($resultadoAdmin->num_rows === 1) {

            $admin = $resultadoAdmin->fetch_assoc();

            // CRIA AS SESSÕES CORRETAS
            $_SESSION["id_admin"] = $admin["id_admin"];   // ESSA É A CHAVE QUE PRAZOS.PHP PRECISA
            $_SESSION["nome"]     = $admin["nome_completo"];
            $_SESSION["email"]    = $admin["email_corporativo"];
            $_SESSION["admin"]    = true;

            header("Location: ../admin/index.php");
            exit;
        }

        $stmtAdmin->close();



        // ===== LOGIN DE ALUNO =====
        $stmt = $conn->prepare("
            SELECT id_aluno, nome_completo, email_inst, senha_hash 
            FROM aluno 
            WHERE email_inst = ? AND senha_hash = ?
        ");

        $stmt->bind_param("ss", $email, $senha);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {

            $usuario = $resultado->fetch_assoc();

            $_SESSION["id"]    = $usuario["id_aluno"];
            $_SESSION["nome"]  = $usuario["nome_completo"];
            $_SESSION["email"] = $usuario["email_inst"];
            $_SESSION["admin"] = false;

            header("Location: ../user/index.php");
            exit;

        } else {
            $erro = "E-mail ou senha incorretos!";
        }

        $stmt->close();

    } else {
        $erro = "Preencha todos os campos!";
    }
}

$conn->close();
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
