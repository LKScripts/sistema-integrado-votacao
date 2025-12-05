<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/helpers.php';
require_once '../../../config/csrf.php';
require_once '../../../config/email.php';
require_once '../../../config/dev_mode.php';

$erro = "";
$sucesso = false;
$dev_mode_html = ""; // Para exibir mensagem de dev mode

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

    // Variáveis para upload de foto
    $foto_perfil = null;
    $foto_perfil_original = null;

    // Identificar tipo (admin ou aluno)
    $is_admin = (
        empty($ra) &&
        empty($curso) &&
        ($semestre === 0) &&
        (isDevMode() || preg_match('/@cps\.sp\.gov\.br$/i', $email))
    );

    $is_aluno = (
        !empty($ra) &&
        !empty($curso) &&
        $semestre >= 1 &&
        (isDevMode() || preg_match('/@fatec\.sp\.gov\.br$/i', $email))
    );

    // Validações básicas comuns
    if (empty($nome) || empty($email) || empty($senha) || empty($confirma_senha)) {
        $erro = "Preencha os campos obrigatórios: nome, e-mail e senha.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido! Verifique o formato do endereço de e-mail.";
    } elseif ($senha !== $confirma_senha) {
        $erro = "As senhas não coincidem!";
    } else {
        $validacao_senha = validarSenha($senha);
        if (!$validacao_senha['valido']) {
            $erro = $validacao_senha['erro'];
        }
    }

    if (empty($erro) && !($is_admin || $is_aluno)) {
        $erro = "Dados incompatíveis: para ALUNO use @fatec.sp.gov.br com RA/curso/semestre preenchidos; para ADMIN deixe RA/curso/semestre vazios e use @cps.sp.gov.br.";
    }

    if (empty($erro)) {
        // --------------------------------------------------
        // CADASTRAR ADMINISTRADOR
        // --------------------------------------------------
        if ($is_admin) {
            try {
                // Verificar email duplicado na tabela ADMINISTRADOR
                if (emailAdminExiste($conn, $email)) {
                    $erro = "Este e-mail já está cadastrado como administrador!";
                } else {
                    $senha_hash = hashearSenha($senha);

                    $stmtInsert = $conn->prepare("
                        INSERT INTO ADMINISTRADOR (nome_completo, email_corporativo, senha_hash, ativo)
                        VALUES (?, ?, ?, 0)
                    ");

                    if ($stmtInsert->execute([$nome, $email, $senha_hash])) {
                        $id_admin = $conn->lastInsertId();

                        // Gerar token de confirmação
                        $token = bin2hex(random_bytes(32));
                        $dataExpiracao = date('Y-m-d H:i:s', strtotime('+24 hours'));

                        $stmtToken = $conn->prepare("
                            INSERT INTO email_confirmacao (token, tipo_usuario, id_usuario, email, data_expiracao)
                            VALUES (?, 'admin', ?, ?, ?)
                        ");

                        if ($stmtToken->execute([$token, $id_admin, $email, $dataExpiracao])) {
                            // Enviar email (produção ou hybrid mode)
                            if (shouldSendRealEmail()) {
                                try {
                                    $emailService = new EmailService();
                                    $emailEnviado = $emailService->enviarConfirmacaoCadastro($email, $nome, $token, 'admin');

                                    if ($emailEnviado) {
                                        $sucesso = true;
                                        // Modo hybrid: mostra popup E envia email
                                        if (isDevMode()) {
                                            $dev_mode_html = exibirMensagemDevMode($token, $email);
                                        }
                                    } else {
                                        $erro = "Cadastro realizado, mas houve erro ao enviar o e-mail de confirmação. Entre em contato com o suporte.";
                                    }
                                } catch (Exception $e) {
                                    $erro = "Cadastro realizado, mas o serviço de e-mail não está configurado. Entre em contato com o suporte.";
                                }
                            } else {
                                // Modo dev puro: só mostra popup
                                $sucesso = true;
                                $dev_mode_html = exibirMensagemDevMode($token, $email);
                            }
                        } else {
                            $erro = "Erro ao gerar token de confirmação.";
                        }
                    } else {
                        $erro = "Erro ao cadastrar administrador.";
                    }
                }
            } catch (PDOException $e) {
                // Logar erro completo para debug (não mostrar ao usuário)
                error_log("Erro ao cadastrar admin: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());

                // Mensagem genérica para o usuário
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $erro = "Este e-mail já está cadastrado no sistema.";
                } else {
                    $erro = "Erro ao processar cadastro. Tente novamente ou contate o suporte.";
                }
            }
        }

        // --------------------------------------------------
        // CADASTRAR ALUNO
        // --------------------------------------------------
        elseif ($is_aluno) {
            // Processar upload de foto (opcional)
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $arquivo = $_FILES['foto_perfil'];
                $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $tamanho_maximo = 5 * 1024 * 1024; // 5MB

                if (!in_array($extensao, $extensoes_permitidas)) {
                    $erro = "Formato de imagem inválido. Use JPG, PNG, GIF ou WebP.";
                } elseif ($arquivo['size'] > $tamanho_maximo) {
                    $erro = "A imagem deve ter no máximo 5MB.";
                } else {
                    // Diretório de upload (mesmo padrão de mudar_foto.php)
                    $diretorio_upload = __DIR__ . '/../../storage/uploads/perfil/';
                    if (!is_dir($diretorio_upload)) {
                        mkdir($diretorio_upload, 0755, true);
                    }

                    // Nome único para o arquivo
                    $nome_arquivo = uniqid('perfil_', true) . '.' . $extensao;
                    $caminho_completo = $diretorio_upload . $nome_arquivo;

                    if (move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
                        // Caminho relativo (mesmo padrão de mudar_foto.php)
                        // Funciona em qualquer página pois é relativo ao HTML renderizado
                        $foto_perfil = '../../storage/uploads/perfil/' . $nome_arquivo;
                        $foto_perfil_original = $arquivo['name'];
                    } else {
                        $erro = "Erro ao fazer upload da foto.";
                    }
                }
            }

            // Validar RA numérico
            if (empty($erro) && !preg_match('/^\d+$/', $ra)) {
                $erro = "O RA deve conter apenas números!";
            }

            if (empty($erro)) {
                try {
                    // Verificar RA duplicado
                    if (raExiste($conn, $ra)) {
                        $erro = "Este RA já está cadastrado!";
                    } elseif (emailAlunoExiste($conn, $email)) {
                        $erro = "Este e-mail já está cadastrado!";
                    } else {
                        $senha_hash = hashearSenha($senha);

                        $stmtInsert = $conn->prepare("
                            INSERT INTO ALUNO (ra, nome_completo, email_institucional, foto_perfil, senha_hash, curso, semestre, ativo)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
                        ");

                        if ($stmtInsert->execute([$ra, $nome, $email, $foto_perfil, $senha_hash, $curso, $semestre])) {
                            $id_aluno = $conn->lastInsertId();

                            // Gerar token de confirmação
                            $token = bin2hex(random_bytes(32));
                            $dataExpiracao = date('Y-m-d H:i:s', strtotime('+24 hours'));

                            $stmtToken = $conn->prepare("
                                INSERT INTO email_confirmacao (token, tipo_usuario, id_usuario, email, data_expiracao)
                                VALUES (?, 'aluno', ?, ?, ?)
                            ");

                            if ($stmtToken->execute([$token, $id_aluno, $email, $dataExpiracao])) {
                                // Enviar email (produção ou hybrid mode)
                                if (shouldSendRealEmail()) {
                                    try {
                                        $emailService = new EmailService();
                                        $emailEnviado = $emailService->enviarConfirmacaoCadastro($email, $nome, $token, 'aluno');

                                        if ($emailEnviado) {
                                            $sucesso = true;
                                            // Modo hybrid: mostra popup E envia email
                                            if (isDevMode()) {
                                                $dev_mode_html = exibirMensagemDevMode($token, $email);
                                            }
                                        } else {
                                            $erro = "Cadastro realizado, mas houve erro ao enviar o e-mail de confirmação. Entre em contato com o suporte.";
                                        }
                                    } catch (Exception $e) {
                                        $erro = "Cadastro realizado, mas o serviço de e-mail não está configurado. Entre em contato com o suporte.";
                                    }
                                } else {
                                    // Modo dev puro: só mostra popup
                                    $sucesso = true;
                                    $dev_mode_html = exibirMensagemDevMode($token, $email);
                                }
                            } else {
                                $erro = "Erro ao gerar token de confirmação.";
                            }
                        } else {
                            $erro = "Erro ao cadastrar aluno.";
                        }
                    }
                } catch (PDOException $e) {
                    // Logar erro completo para debug (não mostrar ao usuário)
                    error_log("Erro ao cadastrar aluno: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());

                    // Mensagem genérica para o usuário
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        if (strpos($e->getMessage(), 'email') !== false) {
                            $erro = "Este e-mail já está cadastrado no sistema.";
                        } elseif (strpos($e->getMessage(), 'ra') !== false) {
                            $erro = "Este RA já está cadastrado no sistema.";
                        } else {
                            $erro = "Estes dados já estão cadastrados no sistema.";
                        }
                    } else {
                        $erro = "Erro ao processar cadastro. Tente novamente ou contate o suporte.";
                    }
                }
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
<?php if (!empty($dev_mode_html)) echo $dev_mode_html; ?>

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
                            <strong>Cadastro realizado com sucesso!</strong><br>
                            Um e-mail de confirmação foi enviado para sua caixa de entrada. Verifique sua caixa de e-mail e clique no link para ativar sua conta.
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
                            <strong>Cadastro no Sistema SIV</strong><br>
                            <strong>Para ALUNOS:</strong> Use seu email institucional @fatec.sp.gov.br e preencha todos os campos (RA, curso e semestre).<br>
                            <strong>Para ADMINISTRADORES:</strong> Use o email corporativo @cps.sp.gov.br e deixe os campos RA, curso e semestre vazios.<br>
                            Após o cadastro, você receberá um email de confirmação. Clique no link para ativar sua conta antes de fazer login.
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

                <form method="POST" id="form-cadastro" enctype="multipart/form-data" novalidate>
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
                        <label for="email">Email</label>
                        <div class="input-field">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email"
                                   placeholder="seu.nome@fatec.sp.gov.br ou @cps.sp.gov.br"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   required>
                        </div>
                    </div>

                    <div class="input-group" id="foto-perfil-group">
                        <label for="foto_perfil">Foto de Perfil (Opcional)</label>
                        <div class="upload-foto-perfil" style="display: flex; flex-direction: column; align-items: center; gap: 15px; padding: 20px; border: 2px dashed #ddd; border-radius: 10px; background: #f9f9f9;">
                            <div class="preview-foto" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; background: #e0e0e0; display: flex; align-items: center; justify-content: center; border: 4px solid #005c6d;">
                                <i class="fas fa-user" style="font-size: 3rem; color: #005c6d;" id="icon-placeholder"></i>
                                <img id="preview-img" src="" alt="Preview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <input type="file" id="foto_perfil" name="foto_perfil" accept="image/jpeg,image/jpg,image/png,image/gif" style="display: none;">
                            <label for="foto_perfil" class="button secondary" style="cursor: pointer; margin: 0;">
                                <i class="fas fa-camera"></i>
                                Escolher Foto
                            </label>
                            <p style="font-size: 0.85rem; color: #666; text-align: center; margin: 0;">JPG, PNG ou GIF (máx. 5MB)</p>
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

                    <a class="button secondary" href="../../index.php">
                        <i class="fas fa-home"></i>
                        Voltar à Homepage
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

// Ajustar required dinamicamente (melhora UX)
// Se email terminar em @cps.sp.gov.br => REMOVE required de RA/curso/semestre (admin)
// Se email terminar em @fatec.sp.gov.br => ADICIONA required (aluno)
function ajustarRequiredPorEmail() {
    const email = (document.getElementById('email')?.value || '').toLowerCase();
    const ra = document.getElementById('ra');
    const curso = document.getElementById('curso');
    const semestre = document.getElementById('semestre');

    if (email.endsWith('@cps.sp.gov.br')) {
        ra.removeAttribute('required');
        curso.removeAttribute('required');
        semestre.removeAttribute('required');
    } else if (email.endsWith('@fatec.sp.gov.br')) {
        ra.setAttribute('required', 'required');
        curso.setAttribute('required', 'required');
        semestre.setAttribute('required', 'required');
    } else {
        // domínio desconhecido: mantém sem required para RA/curso/semestre,
        // mas o servidor validará e mostrará mensagem adequada.
        ra.removeAttribute('required');
        curso.removeAttribute('required');
        semestre.removeAttribute('required');
    }
}

document.getElementById('email')?.addEventListener('input', ajustarRequiredPorEmail);
// Ao carregar a página, aplicar a regra se já houver valor
window.addEventListener('load', ajustarRequiredPorEmail);

// Preview da foto de perfil
document.getElementById('foto_perfil')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('preview-img');
    const placeholder = document.getElementById('icon-placeholder');

    if (file) {
        // Validar tamanho
        if (file.size > 5 * 1024 * 1024) {
            alert('A imagem deve ter no máximo 5MB.');
            this.value = '';
            return;
        }

        // Validar tipo
        const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!tiposPermitidos.includes(file.type)) {
            alert('Formato inválido. Use JPG, PNG ou GIF.');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(event) {
            preview.src = event.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

// Ocultar campo de foto para administradores
function ajustarCampoFoto() {
    const email = (document.getElementById('email')?.value || '').toLowerCase();
    const fotoGroup = document.getElementById('foto-perfil-group');

    if (fotoGroup) {
        if (email.endsWith('@cps.sp.gov.br')) {
            fotoGroup.style.display = 'none';
        } else {
            fotoGroup.style.display = 'block';
        }
    }
}

document.getElementById('email')?.addEventListener('input', ajustarCampoFoto);
window.addEventListener('load', ajustarCampoFoto);
</script>
</body>
</html>
