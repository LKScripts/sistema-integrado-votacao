<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';

$erro = "";
$sucesso = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // VALIDAR CSRF PRIMEIRO
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente cadastrar novamente.");

    $nome = trim($_POST["nome"] ?? "");
    $ra = trim($_POST["ra"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirma_senha = $_POST["confirma_senha"] ?? "";
    $curso = $_POST["curso"] ?? "";
    $semestre = intval($_POST['semestre'] ?? 0);

    // Validações básicas para ALUNO
    if (empty($nome) || empty($email) || empty($senha) || empty($confirma_senha)) {
        $erro = "Preencha os campos obrigatórios: nome, e-mail e senha.";
    } elseif (empty($ra) || empty($curso) || $semestre < 1) {
        $erro = "Preencha todos os campos: RA, curso e semestre são obrigatórios.";
    } elseif ($senha !== $confirma_senha) {
        $erro = "As senhas não coincidem!";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres!";
    } elseif (!preg_match('/@fatec\.sp\.gov\.br$/i', $email)) {
        $erro = "Use um e-mail institucional @fatec.sp.gov.br";
    } else {
        // --------------------------------------------------
        // CADASTRAR ALUNO
        // --------------------------------------------------
            // Validar RA numérico
            if (!preg_match('/^\d+$/', $ra)) {
                $erro = "O RA deve conter apenas números!";
            } else {
                try {
                    // Verificar RA duplicado
                    $stmtCheckRA = $conn->prepare("SELECT id_aluno FROM ALUNO WHERE ra = ?");
                    $stmtCheckRA->execute([$ra]);
                    if ($stmtCheckRA->fetch()) {
                        $erro = "Este RA já está cadastrado!";
                    } else {
                        // Verificar email duplicado na tabela ALUNO
                        $stmtCheckEmail = $conn->prepare("SELECT id_aluno FROM ALUNO WHERE email_institucional = ?");
                        $stmtCheckEmail->execute([$email]);

                        if ($stmtCheckEmail->fetch()) {
                            $erro = "Este e-mail já está cadastrado!";
                        } else {
                            $senha_hash = password_hash($senha, PASSWORD_BCRYPT);

                            $stmtInsert = $conn->prepare("
                                INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");

                            if ($stmtInsert->execute([$ra, $nome, $email, $senha_hash, $curso, $semestre])) {
                                $sucesso = true;
                            } else {
                                $erro = "Erro ao cadastrar aluno.";
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $erro = "Erro no cadastro do aluno: " . $e->getMessage();
                }
            }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Cadastro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">

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
                <h4>Bem vindo ao SIV</h4>
                <h1>Cadastre-se!</h1>
            </header>

            <?php if ($sucesso): ?>
                <div class="callout" style="background-color: #d4edda; border-color: #c3e6cb;">
                    <div class="content">
                        <span style="color: #155724;">
                            <strong>✅ Cadastro realizado com sucesso!</strong><br>
                            Você já pode fazer login com suas credenciais.
                        </span>
                    </div>
                </div>
                <a href="login.php" class="button primary" style="width: 100%; margin-top: 15px;">
                    <i class="fas fa-sign-in-alt"></i>
                    Ir para o Login
                </a>
            <?php else: ?>

                <div class="callout info">
                    <div class="content">
                        <span>
                            <strong>Cadastro de Aluno</strong><br>
                            Use seu e-mail institucional @fatec.sp.gov.br e preencha todos os campos: RA, curso e semestre.
                        </span>
                    </div>
                </div>

                <?php if(!empty($erro)): ?>
                    <div class="callout warning">
                        <div class="content">
                            <span><?= htmlspecialchars($erro) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" id="form-cadastro" novalidate>
                    <?= campoCSRF() ?>
                    <div class="input-group">
                        <label for="nome">Nome Completo</label>
                        <div class="input-field">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nome" name="nome"
                                   placeholder="Seu nome completo"
                                   value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                                   required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label for="ra">RA (Registro Acadêmico)</label>
                            <div class="input-field">
                                <i class="fas fa-id-card"></i>
                                <input type="text" id="ra" name="ra"
                                       placeholder="00000000"
                                       value="<?= htmlspecialchars($_POST['ra'] ?? '') ?>"
                                       maxlength="20">
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="semestre">Semestre</label>
                            <div class="input-field">
                                <i class="fas fa-calendar"></i>
                                <select id="semestre" name="semestre">
                                    <option value="">Selecione</option>
                                    <?php for($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?= $i ?>" <?= (isset($_POST['semestre']) && $_POST['semestre'] == $i) ? 'selected' : '' ?>>
                                            <?= $i ?>º Semestre
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="curso">Curso</label>
                        <div class="input-field">
                            <i class="fas fa-graduation-cap"></i>
                            <select id="curso" name="curso">
                                <option value="">Selecione seu curso</option>
                                <option value="DSM" <?= (isset($_POST['curso']) && $_POST['curso'] == 'DSM') ? 'selected' : '' ?>>
                                    Desenvolvimento de Software Multiplataforma (DSM)
                                </option>
                                <option value="GE" <?= (isset($_POST['curso']) && $_POST['curso'] == 'GE') ? 'selected' : '' ?>>
                                    Gestão Empresarial (GE)
                                </option>
                                <option value="GPI" <?= (isset($_POST['curso']) && $_POST['curso'] == 'GPI') ? 'selected' : '' ?>>
                                    Gestão da Produção Industrial (GPI)
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="email">Email Institucional</label>
                        <div class="input-field">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email"
                                   placeholder="seu.nome@fatec.sp.gov.br"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   pattern=".*@fatec\.sp\.gov\.br$"
                                   title="Use um e-mail institucional @fatec.sp.gov.br"
                                   required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="senha">Senha</label>
                        <div class="input-field">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="senha" name="senha"
                                   placeholder="Mínimo 6 caracteres"
                                   minlength="6"
                                   required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="confirma_senha">Confirme a Senha</label>
                        <div class="input-field">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirma_senha" name="confirma_senha"
                                   placeholder="Digite a senha novamente"
                                   minlength="6"
                                   required>
                        </div>
                    </div>

                    <button type="submit" class="button primary">
                        <i class="fas fa-user-plus"></i>
                        Criar Conta
                    </button>

                    <a class="button secondary" href="login.php">
                        <i class="fas fa-arrow-left"></i>
                        Já tenho conta
                    </a>
                </form>

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

<script>
// Validação de senha em tempo real
document.getElementById('confirma_senha')?.addEventListener('input', function() {
    const senha = document.getElementById('senha').value;
    const confirmaSenha = this.value;

    if (confirmaSenha && senha !== confirmaSenha) {
        this.setCustomValidity('As senhas não coincidem');
    } else {
        this.setCustomValidity('');
    }
});

// Formatar RA para aceitar apenas números
document.getElementById('ra')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '');
});

// Garantir que RA, curso e semestre são sempre obrigatórios (cadastro apenas de aluno)
function garantirCamposObrigatorios() {
    const ra = document.getElementById('ra');
    const curso = document.getElementById('curso');
    const semestre = document.getElementById('semestre');

    if (ra) ra.setAttribute('required', 'required');
    if (curso) curso.setAttribute('required', 'required');
    if (semestre) semestre.setAttribute('required', 'required');
}

window.addEventListener('load', garantirCamposObrigatorios);
</script>
</body>
</html>
