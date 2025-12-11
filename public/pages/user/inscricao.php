<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/helpers.php';
require_once '../../../config/automacao-eleicoes.php';
require_once '../../../config/csrf.php';

// Verifica se é aluno logado
verificarAluno();

$usuario = obterUsuarioLogado();
$id_aluno = $usuario['id'];
$nome = $usuario['nome'];
$curso = $usuario['curso'];
$semestre = $usuario['semestre'];

$erro = "";
$inscricao_sucesso = isset($_GET['sucesso']) && $_GET['sucesso'] == '1';

// Buscar eleição ativa para candidatura (FORÇANDO ATUALIZAÇÃO para garantir sincronização)
$eleicao = buscarEleicaoAtivaComVerificacao($curso, $semestre, 'candidatura', true);
$eleicaoCandidatura = $eleicao; // Para uso no header

if (!$eleicao) {
    // Redirecionar para home se não houver eleição aberta para candidatura
    header('Location: ../../pages/user/index.php');
    exit;
}

$id_eleicao = $eleicao['id_eleicao'] ?? null;

// Verificar se aluno já está cadastrado ANTES de mostrar o formulário
$ja_inscrito = false;
$candidatura_existente = null;

if ($id_eleicao) {
    $candidatura_existente = alunoCandidatouNaEleicao($conn, $id_eleicao, $id_aluno);
    $ja_inscrito = ($candidatura_existente !== false);
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_eleicao) {
    // VALIDAR CSRF PRIMEIRO
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente se inscrever novamente.");

    $proposta = trim($_POST['qualidades'] ?? '');
    $usar_foto_perfil = isset($_POST['usar_foto_perfil']) && $_POST['usar_foto_perfil'] === '1';

    // VERIFICAÇÃO EXTRA: Garantir que período de candidatura ainda está aberto
    $verificacao = verificarPeriodoCandidatura($id_eleicao);

    if (!$verificacao['valido']) {
        $erro = $verificacao['mensagem'];
    } else {
        // Verificar se já se candidatou nesta eleição
        if (alunoCandidatouNaEleicao($conn, $id_eleicao, $id_aluno)) {
            $erro = "Você já está inscrito nesta eleição!";
        } else {
            // Processar foto do candidato
            $foto_candidato = null;

            // OPÇÃO 1: Usar foto de perfil existente
            if ($usar_foto_perfil) {
                $foto_perfil_atual = $usuario['foto'] ?? null;

                if ($foto_perfil_atual && !empty($foto_perfil_atual)) {
                    // Copiar foto de perfil para pasta de candidatos
                    $caminho_origem = __DIR__ . '/../../' . str_replace('../../', '', $foto_perfil_atual);

                    if (file_exists($caminho_origem)) {
                        $extensao = pathinfo($caminho_origem, PATHINFO_EXTENSION);
                        $nome_arquivo = 'candidato_' . $id_aluno . '_' . uniqid() . '.' . $extensao;
                        $caminho_destino = __DIR__ . '/../../../storage/uploads/candidatos/' . $nome_arquivo;

                        // Criar diretório se não existir
                        $dir_candidatos = dirname($caminho_destino);
                        if (!is_dir($dir_candidatos)) {
                            mkdir($dir_candidatos, 0755, true);
                        }

                        if (copy($caminho_origem, $caminho_destino)) {
                            $foto_candidato = $nome_arquivo;
                        } else {
                            $erro = "Erro ao copiar foto de perfil. Tente novamente.";
                        }
                    } else {
                        $erro = "Foto de perfil não encontrada.";
                    }
                } else {
                    $erro = "Você não possui foto de perfil cadastrada.";
                }
            }
            // OPÇÃO 2: Upload de nova foto
            elseif (isset($_FILES['foto_candidato']) && $_FILES['foto_candidato']['error'] === UPLOAD_ERR_OK) {
                $arquivo = $_FILES['foto_candidato'];
                $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $tamanho_maximo = 5 * 1024 * 1024; // 5MB

                // Validar extensão
                if (!in_array($extensao, $extensoes_permitidas)) {
                    $erro = "Formato de imagem inválido. Use JPG, JPEG, PNG, GIF ou WebP.";
                }
                // Validar tamanho
                elseif ($arquivo['size'] > $tamanho_maximo) {
                    $erro = "A imagem deve ter no máximo 5MB.";
                }
                // VALIDAÇÃO MIME TYPE (Issue #8)
                else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $arquivo['tmp_name']);
                    finfo_close($finfo);

                    $mimePermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                    if (!in_array($mimeType, $mimePermitidos)) {
                        $erro = "Tipo de arquivo inválido. O arquivo não é uma imagem válida.";
                    }
                    // Validar se é realmente uma imagem (backup validation)
                    elseif (!getimagesize($arquivo['tmp_name'])) {
                        $erro = "O arquivo enviado não é uma imagem válida.";
                    } else {
                        // Gerar nome único para o arquivo (Issue #9: usar uniqid())
                        $nome_arquivo = 'candidato_' . $id_aluno . '_' . uniqid() . '.' . $extensao;
                        $dir_destino = __DIR__ . '/../../../storage/uploads/candidatos/';

                        // Criar diretório se não existir
                        if (!is_dir($dir_destino)) {
                            mkdir($dir_destino, 0755, true);
                        }

                        $caminho_destino = $dir_destino . $nome_arquivo;

                        // Mover arquivo para pasta de uploads
                        if (move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
                            $foto_candidato = $nome_arquivo;
                        } else {
                            $erro = "Erro ao fazer upload da imagem. Tente novamente.";
                        }
                    }
                }
            }

            // Validar se foto foi fornecida (OBRIGATÓRIO)
            if (empty($erro) && $foto_candidato === null) {
                $erro = "A foto do candidato é obrigatória. Por favor, selecione uma foto.";
            }

            // Se não houve erro, inserir candidatura
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
                    if ($foto_candidato) {
                        $caminho_remover = __DIR__ . '/../../../storage/uploads/candidatos/' . $foto_candidato;
                        if (file_exists($caminho_remover)) {
                            unlink($caminho_remover);
                        }
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
                <?php if ($eleicaoCandidatura): ?>
                    <li><a href="../../pages/user/inscricao.php" class="active">Inscrição</a></li>
                <?php endif; ?>
                <li><a href="../../pages/user/votacao.php">Votação</a></li>
                <li><a href="../../pages/user/sobre.php">Sobre</a></li>
            </ul>
            <div class="actions">
                <img src="<?= htmlspecialchars(obterFotoUsuario()) ?>" alt="Avatar do usuário" class="user-icon">
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
                    <?= campoCSRF() ?>
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
                        <label>Foto do Candidato <span style="color:red;">*</span></label>
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
    <div id="modalFoto" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center; overflow-y:auto;">
        <div style="background:#fff; padding:35px; border-radius:12px; max-width:500px; text-align:center; box-shadow:0 8px 20px rgba(0,0,0,0.2); margin:20px;">
            <h3 class="title" style="margin-bottom:25px; color:#005f73; font-size:24px;">📸 Foto para Candidatura</h3>

            <!-- PREVIEW DA FOTO -->
            <img id="previewFoto" src="<?= htmlspecialchars(obterFotoUsuario()) ?>" alt="Preview" style="width:200px; height:200px; border-radius:50%; object-fit:cover; margin-bottom:25px; border:4px solid #005f73; box-shadow:0 4px 12px rgba(0,0,0,0.15);">

            <!-- AVISO IMPORTANTE -->
            <div style="background:#fff3cd; border:2px solid #ffc107; border-radius:8px; padding:15px; margin-bottom:25px; text-align:left;">
                <p style="margin:0; color:#856404; font-weight:600; font-size:14px;">
                    ⚠️ <strong>ATENÇÃO:</strong> Esta foto será usada na votação e <strong>NÃO PODERÁ ser alterada</strong> após confirmar a inscrição!
                </p>
            </div>

            <!-- OPÇÕES DE FOTO -->
            <div style="text-align:left; margin-bottom:25px;">
                <p style="font-weight:600; margin-bottom:15px; color:#333;">Escolha uma opção:</p>

                <label style="display:flex; align-items:center; gap:10px; padding:12px; border:2px solid #ddd; border-radius:8px; margin-bottom:10px; cursor:pointer; transition:all 0.2s;" onmouseover="this.style.borderColor='#005f73'; this.style.backgroundColor='#f0f9fa';" onmouseout="this.style.borderColor='#ddd'; this.style.backgroundColor='transparent';">
                    <input type="radio" name="opcao_foto" value="perfil" id="radioUsarPerfil" checked style="width:20px; height:20px; cursor:pointer;">
                    <span style="flex:1; text-align:left;">Usar minha foto de perfil atual</span>
                </label>

                <label style="display:flex; align-items:center; gap:10px; padding:12px; border:2px solid #ddd; border-radius:8px; cursor:pointer; transition:all 0.2s;" onmouseover="this.style.borderColor='#005f73'; this.style.backgroundColor='#f0f9fa';" onmouseout="this.style.borderColor='#ddd'; this.style.backgroundColor='transparent';">
                    <input type="radio" name="opcao_foto" value="upload" id="radioUpload" style="width:20px; height:20px; cursor:pointer;">
                    <span style="flex:1; text-align:left;">Fazer upload de nova foto</span>
                </label>
            </div>

            <!-- INPUT DE UPLOAD (inicialmente escondido) -->
            <div id="divUpload" style="display:none; margin-bottom:25px;">
                <input type="file" id="inputFotoModal" name="foto_candidato" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display:block; margin:0 auto; padding:12px; border:2px solid #005f73; border-radius:8px; width:100%; cursor:pointer;">
                <small style="display:block; color:#666; margin-top:8px; font-size:13px;">Formatos: JPG, PNG, GIF, WebP | Tamanho máximo: 5MB</small>
            </div>

            <!-- CHECKBOX DE CONFIRMAÇÃO -->
            <label style="display:flex; align-items:flex-start; gap:10px; padding:15px; background:#f8f9fa; border-radius:8px; margin-bottom:25px; cursor:pointer; text-align:left;">
                <input type="checkbox" id="checkboxConfirmacao" style="width:20px; height:20px; margin-top:2px; cursor:pointer;">
                <span style="flex:1; font-size:14px; color:#333;">
                    <strong>Confirmo que revisei a foto</strong> e estou ciente de que ela não poderá ser alterada após a confirmação da inscrição.
                </span>
            </label>

            <!-- BOTÕES -->
            <div class="modal-buttons" style="display:flex; gap:12px; justify-content:center;">
                <button type="button" class="button secondary" onclick="fecharModalFoto()" style="flex:1; padding:12px 24px;">Cancelar</button>
                <button type="button" class="button primary" onclick="confirmarFoto()" style="flex:1; padding:12px 24px;">Confirmar Foto</button>
            </div>
        </div>
    </div>

    <script>
    // Resetar preview ao abrir modal
    const fotoPerfilInicial = '<?= htmlspecialchars(obterFotoUsuario()) ?>';

    function abrirModalFoto() {
        const modal = document.getElementById('modalFoto');
        if (modal) {
            // Resetar para foto de perfil
            document.getElementById('previewFoto').src = fotoPerfilInicial;
            document.getElementById('radioUsarPerfil').checked = true;
            document.getElementById('divUpload').style.display = 'none';
            document.getElementById('inputFotoModal').value = '';
            document.getElementById('checkboxConfirmacao').checked = false;

            modal.style.display = 'flex';
        }
    }

    function fecharModalFoto() {
        document.getElementById('modalFoto').style.display = 'none';
    }

    // Gerenciar mudança de opção (perfil vs upload)
    document.querySelectorAll('input[name="opcao_foto"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const divUpload = document.getElementById('divUpload');
            const preview = document.getElementById('previewFoto');

            if (this.value === 'upload') {
                divUpload.style.display = 'block';
            } else {
                divUpload.style.display = 'none';
                // Resetar preview para foto de perfil
                preview.src = fotoPerfilInicial;
                document.getElementById('inputFotoModal').value = '';
            }
        });
    });

    // Preview ao selecionar arquivo
    document.getElementById('inputFotoModal').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validar tamanho
        if (file.size > 5 * 1024 * 1024) {
            alert('A imagem deve ter no máximo 5MB.');
            this.value = '';
            return;
        }

        // Validar tipo
        const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!tiposPermitidos.includes(file.type)) {
            alert('Formato inválido. Use JPG, PNG, GIF ou WebP.');
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
        const checkboxConfirmacao = document.getElementById('checkboxConfirmacao');
        const opcaoSelecionada = document.querySelector('input[name="opcao_foto"]:checked').value;
        const inputFoto = document.getElementById('inputFotoModal');
        const formPrincipal = document.querySelector('.form-application');

        // Validar checkbox
        if (!checkboxConfirmacao.checked) {
            alert('⚠️ Você precisa confirmar que revisou a foto antes de continuar.');
            return;
        }

        // Remover inputs anteriores
        const inputAnteriorFoto = document.getElementById('foto_candidato_hidden');
        const inputAnteriorOpcao = document.getElementById('usar_foto_perfil_hidden');
        if (inputAnteriorFoto) inputAnteriorFoto.remove();
        if (inputAnteriorOpcao) inputAnteriorOpcao.remove();

        // OPÇÃO 1: Usar foto de perfil
        if (opcaoSelecionada === 'perfil') {
            // Criar input hidden informando para usar foto de perfil
            const inputOpcao = document.createElement('input');
            inputOpcao.type = 'hidden';
            inputOpcao.name = 'usar_foto_perfil';
            inputOpcao.id = 'usar_foto_perfil_hidden';
            inputOpcao.value = '1';
            formPrincipal.appendChild(inputOpcao);

            document.getElementById('foto-status').textContent = '✓ Usando foto de perfil';
            document.getElementById('foto-status').style.display = 'block';
        }
        // OPÇÃO 2: Upload de nova foto
        else {
            if (!inputFoto.files || !inputFoto.files[0]) {
                alert('Por favor, selecione uma foto para fazer upload.');
                return;
            }

            // Criar input file hidden com o arquivo
            const inputHidden = document.createElement('input');
            inputHidden.type = 'file';
            inputHidden.name = 'foto_candidato';
            inputHidden.id = 'foto_candidato_hidden';
            inputHidden.style.display = 'none';

            // Transferir o arquivo
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(inputFoto.files[0]);
            inputHidden.files = dataTransfer.files;

            formPrincipal.appendChild(inputHidden);

            document.getElementById('foto-status').textContent = '✓ Nova foto selecionada';
            document.getElementById('foto-status').style.display = 'block';
        }

        // Fechar modal
        fecharModalFoto();
    }

    // Prevenir fechar modal ao clicar no conteúdo
    document.getElementById('modalFoto').addEventListener('click', function(e) {
        if (e.target === this) {
            fecharModalFoto();
        }
    });
    </script>

</body>
</html>
