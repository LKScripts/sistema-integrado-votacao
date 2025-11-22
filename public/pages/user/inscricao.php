<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/automacao_eleicoes.php';

// Verifica se é aluno logado
verificarAluno();

$usuario = obterUsuarioLogado();
$id_aluno = $usuario['id'];
$nome = $usuario['nome'];
$curso = $usuario['curso'];
$semestre = $usuario['semestre'];

$erro = "";
$inscricao_sucesso = isset($_GET['sucesso']) && $_GET['sucesso'] == '1';

// Buscar eleição ativa para candidatura (COM VERIFICAÇÃO AUTOMÁTICA)
$eleicao = buscarEleicaoAtivaComVerificacao($curso, $semestre, 'candidatura');

if (!$eleicao) {
    $erro = "Não há eleição aberta para candidatura no momento para seu curso e semestre.";
}

$id_eleicao = $eleicao['id_eleicao'] ?? null;

// Verificar se aluno já está cadastrado ANTES de mostrar o formulário
$ja_inscrito = false;
if ($id_eleicao) {
    $stmtVerifica = $conn->prepare("
        SELECT id_candidatura, status_validacao, data_inscricao
        FROM CANDIDATURA
        WHERE id_eleicao = ? AND id_aluno = ?
    ");
    $stmtVerifica->execute([$id_eleicao, $id_aluno]);
    $candidatura_existente = $stmtVerifica->fetch();

    if ($candidatura_existente) {
        $ja_inscrito = true;
    }
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_eleicao) {
    $proposta = trim($_POST['qualidades'] ?? '');

    // VERIFICAÇÃO EXTRA: Garantir que período de candidatura ainda está aberto
    $verificacao = verificarPeriodoCandidatura($id_eleicao);

    if (!$verificacao['valido']) {
        $erro = $verificacao['mensagem'];
    } else {
        // Verificar se já se candidatou nesta eleição
        $stmtVerifica = $conn->prepare("
            SELECT id_candidatura
            FROM CANDIDATURA
            WHERE id_eleicao = ? AND id_aluno = ?
        ");
        $stmtVerifica->execute([$id_eleicao, $id_aluno]);
        $candidaturaExistente = $stmtVerifica->fetch();

        if ($candidaturaExistente) {
            $erro = "Você já está inscrito nesta eleição!";
        } else {
            // Processar upload de foto (opcional)
            $foto_candidato = null;

            if (isset($_FILES['foto_candidato']) && $_FILES['foto_candidato']['error'] === UPLOAD_ERR_OK) {
                $arquivo = $_FILES['foto_candidato'];
                $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
                $tamanho_maximo = 5 * 1024 * 1024; // 5MB

                // Validar extensão
                if (!in_array($extensao, $extensoes_permitidas)) {
                    $erro = "Formato de imagem inválido. Use JPG, JPEG, PNG ou GIF.";
                }
                // Validar tamanho
                elseif ($arquivo['size'] > $tamanho_maximo) {
                    $erro = "A imagem deve ter no máximo 5MB.";
                }
                // Validar se é realmente uma imagem
                elseif (!getimagesize($arquivo['tmp_name'])) {
                    $erro = "O arquivo enviado não é uma imagem válida.";
                } else {
                    // Gerar nome único para o arquivo
                    $nome_arquivo = 'candidato_' . $id_aluno . '_' . time() . '.' . $extensao;
                    $caminho_destino = '../../../storage/uploads/candidatos/' . $nome_arquivo;

                    // Mover arquivo para pasta de uploads
                    if (move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
                        $foto_candidato = $nome_arquivo;
                    } else {
                        $erro = "Erro ao fazer upload da imagem. Tente novamente.";
                    }
                }
            }

            // Se não houve erro no upload, inserir candidatura
            if (empty($erro)) {
                $stmtInsert = $conn->prepare("
                    INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, foto_candidato, status_validacao)
                    VALUES (?, ?, ?, ?, 'pendente')
                ");

                if ($stmtInsert->execute([$id_eleicao, $id_aluno, $proposta, $foto_candidato])) {
                    // Redirecionar para evitar resubmissão de formulário
                    header("Location: inscricao.php?sucesso=1");
                    exit;
                } else {
                    $erro = "Erro ao registrar candidatura. Tente novamente.";

                    // Remover foto se a inserção falhar
                    if ($foto_candidato && file_exists($caminho_destino)) {
                        unlink($caminho_destino);
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
    <title>SIV - Sistema Integrado de Votações</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/user.css">
</head>

<body>
    <?php if ($inscricao_sucesso) : ?>
    <div id="modalSucesso" style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 40px; border-radius: 12px; max-width: 500px; text-align: center; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);">
            <h3 style="color: #28a745; margin-bottom: 20px; font-size: 28px; font-weight: bold;">Inscricao Confirmada!</h3>
            <p style="font-size: 18px; color: #333; margin-bottom: 15px;">Sua inscricao foi registrada com sucesso!</p>
            <p style="font-size: 15px; color: #666; margin-bottom: 30px;">Aguarde a validacao do administrador para que sua candidatura seja aprovada.</p>
            <a href="../../pages/user/index.php" class="button primary">Voltar ao Inicio</a>
        </div>
    </div>
    <?php endif; ?>

    <header class="site">
        <nav class="navbar">
            <div class="logo">
                <img src="../../assets/images/fatec-ogari.png" alt="Logo Fatec Itapira">
                <img src="../../assets/images/logo-cps.png" alt="Logo CPS">
            </div>
            <ul class="links">
                <li><a href="../../pages/user/index.php">Home</a></li>
                <li><a href="../../pages/user/inscricao.php" class="active">Inscrição</a></li>
                <li><a href="../../pages/user/votacao.php">Votação</a></li>
                <li><a href="../../pages/user/sobre.php">Sobre</a></li>
            </ul>
            <div class="actions">
                <img src="../../assets/images/user-icon.png" alt="Avatar do usuário" class="user-icon">
                <a href="../../logout.php">Sair da Conta</a>
            </div>
        </nav>
    </header>

    <main class="user-application">
        <div class="card-wrapper">
            <div class="card">
                <h1 class="title">Inscreva-se para Representante</h1>

                <?php if ($ja_inscrito): ?>
                    <div style="position: relative; padding: 12px 20px; margin-bottom: 20px; border: 1px solid #c3e6cb; border-radius: 4px; background-color: #d4edda; color: #155724;">
                        <strong>Inscricao ja realizada!</strong><br>
                        Status: <strong><?= ucfirst($candidatura_existente['status_validacao']) ?></strong><br>
                        Data da inscricao: <?= date('d/m/Y H:i', strtotime($candidatura_existente['data_inscricao'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($erro)): ?>
                    <div class="callout warning" style="margin-bottom: 20px;">
                        <div class="content">
                            <span><?= htmlspecialchars($erro) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="form-application" method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <label for="nome">Nome do aluno</label>
                        <input id="nome" name="nome" type="text" value="<?= htmlspecialchars($nome) ?>" readonly />
                    </div>

                    <div class="input-group">
                        <label for="curso">Curso</label>
                        <input id="curso" name="curso" type="text" value="<?= htmlspecialchars($curso) ?>" readonly />
                    </div>

                    <div class="input-group">
                        <label for="semestre">Semestre</label>
                        <input id="semestre" name="semestre" type="text" value="<?= htmlspecialchars($semestre) ?>" readonly />
                    </div>

                    <div class="input-group">
                        <label for="qualidades">Descreva suas qualidades (opcional)</label>
                        <textarea id="qualidades" name="qualidades" placeholder="Descreva suas qualidades." <?= $ja_inscrito ? 'disabled' : '' ?>></textarea>
                        <span class="textarea-count">0/400 caracteres</span>
                    </div>

                    <?php if (!$ja_inscrito): ?>
                    <div class="input-group">
                        <label>Foto do Candidato (opcional)</label>
                        <button type="button" class="button primary" onclick="event.preventDefault(); abrirModalFoto(); return false;">Enviar Foto</button>
                        <p id="foto-status" style="margin-top:10px; color:#28a745; display:none; font-weight:bold;">Foto selecionada com sucesso</p>
                    </div>
                    <?php endif; ?>

                    <div class="form-buttons">
                        <a href="../../pages/user/index.php" type="button" class="button secondary">Voltar</a>
                        <?php if (!$ja_inscrito): ?>
                        <button type="submit" class="button primary">Concluir</button>
                        <?php endif; ?>
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

    <!-- MODAL DE FOTO -->
    <div id="modalFoto" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; padding:30px; border-radius:10px; max-width:400px; text-align:center; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            <h3 class="title" style="margin-bottom:20px;">Enviar Foto do Candidato</h3>

            <img id="previewFoto" src="../../assets/images/user-icon.png" alt="Preview" style="width:180px; height:180px; border-radius:50%; object-fit:cover; margin-bottom:20px; border:3px solid #ddd;">

            <input type="file" id="inputFotoModal" name="foto_candidato" accept="image/jpeg,image/jpg,image/png,image/gif" style="display:block; margin:0 auto 20px; padding:10px; border:1px solid #ddd; border-radius:5px; width:100%;">

            <small style="display:block; color:#666; margin-bottom:20px;">Formatos: JPG, PNG, GIF. Tamanho maximo: 5MB</small>

            <div class="modal-buttons" style="display:flex; gap:10px; justify-content:center;">
                <button type="button" class="button secondary" onclick="fecharModalFoto()">Cancelar</button>
                <button type="button" class="button primary" onclick="confirmarFoto()">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
    function abrirModalFoto() {
        console.log('Abrindo modal...');
        const modal = document.getElementById('modalFoto');
        console.log('Modal encontrado:', modal);
        if (modal) {
            modal.style.display = 'flex';
        } else {
            alert('Erro: Modal nao encontrado');
        }
    }

    function fecharModalFoto() {
        document.getElementById('modalFoto').style.display = 'none';
    }

    document.getElementById('inputFotoModal').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validar tamanho
        if (file.size > 5 * 1024 * 1024) {
            alert('A imagem deve ter no maximo 5MB.');
            this.value = '';
            return;
        }

        // Validar tipo
        const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!tiposPermitidos.includes(file.type)) {
            alert('Formato invalido. Use JPG, PNG ou GIF.');
            this.value = '';
            return;
        }

        // Preview da imagem
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewFoto').src = e.target.result;
        }
        reader.readAsDataURL(file);
    });

    function confirmarFoto() {
        const input = document.getElementById('inputFotoModal');

        if (!input.files || !input.files[0]) {
            alert('Por favor, selecione uma foto primeiro.');
            return;
        }

        // Criar um novo input file no formulario principal (oculto)
        const formPrincipal = document.querySelector('.form-application');

        // Remover input anterior se existir
        const inputAnterior = document.getElementById('foto_candidato_hidden');
        if (inputAnterior) {
            inputAnterior.remove();
        }

        // Criar novo input hidden com o arquivo
        const inputHidden = document.createElement('input');
        inputHidden.type = 'file';
        inputHidden.name = 'foto_candidato';
        inputHidden.id = 'foto_candidato_hidden';
        inputHidden.style.display = 'none';

        // Transferir o arquivo
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(input.files[0]);
        inputHidden.files = dataTransfer.files;

        formPrincipal.appendChild(inputHidden);

        // Mostrar status
        document.getElementById('foto-status').style.display = 'block';

        // Fechar modal
        fecharModalFoto();
    }
    </script>

</body>
</html>
