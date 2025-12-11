<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/helpers.php';
require_once '../../../config/csrf.php';
require_once '../../../config/automacao-eleicoes.php';

// Verifica se é aluno logado
verificarAluno();

$usuario = obterUsuarioLogado();
$id_aluno = $usuario['id'];
$nome = $usuario['nome'];
$curso = $usuario['curso'];
$semestre = $usuario['semestre'];

$erro = "";
$edicao_sucesso = isset($_GET['sucesso']) && $_GET['sucesso'] == '1';

// Buscar ID da candidatura
$id_candidatura = $_GET['id'] ?? null;

if (!$id_candidatura) {
    header('Location: acompanhar-inscricao.php');
    exit;
}

// Buscar candidatura e verificar se pertence ao aluno e está indeferida
$stmt = $conn->prepare("
    SELECT c.*, e.status as status_eleicao, e.data_fim_candidatura
    FROM CANDIDATURA c
    JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
    WHERE c.id_candidatura = ? AND c.id_aluno = ? AND c.status_validacao = 'indeferido'
");
$stmt->execute([$id_candidatura, $id_aluno]);
$candidatura = $stmt->fetch();

if (!$candidatura) {
    $_SESSION['erro'] = 'Candidatura não encontrada ou você não tem permissão para editá-la.';
    header('Location: acompanhar-inscricao.php');
    exit;
}

// Verificar se ainda está no período de candidatura
if (strtotime($candidatura['data_fim_candidatura']) < time()) {
    $_SESSION['erro'] = 'O período de candidaturas já foi encerrado. Não é mais possível editar.';
    header('Location: acompanhar-inscricao.php');
    exit;
}

$id_eleicao = $candidatura['id_eleicao'];

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VALIDAR CSRF PRIMEIRO
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente novamente.");

    $proposta = trim($_POST['qualidades'] ?? '');
    $usar_foto_perfil = isset($_POST['usar_foto_perfil']) && $_POST['usar_foto_perfil'] === '1';
    $manter_foto_atual = isset($_POST['manter_foto_atual']) && $_POST['manter_foto_atual'] === '1';

    // Foto atual da candidatura
    $foto_candidato = $candidatura['foto_candidato'];

    // OPÇÃO 1: Manter foto atual
    if ($manter_foto_atual) {
        // Não faz nada, mantém a foto atual
    }
    // OPÇÃO 2: Usar foto de perfil existente
    elseif ($usar_foto_perfil) {
        $foto_perfil_atual = $usuario['foto'] ?? null;

        if ($foto_perfil_atual && !empty($foto_perfil_atual)) {
            // Copiar foto de perfil para pasta de candidatos
            $caminho_origem = __DIR__ . '/../../' . str_replace('../../', '', $foto_perfil_atual);

            if (file_exists($caminho_origem)) {
                $extensao = pathinfo($caminho_origem, PATHINFO_EXTENSION);
                $nome_arquivo = 'candidato_' . $id_aluno . '_' . time() . '.' . $extensao;
                $caminho_destino = __DIR__ . '/../../../storage/uploads/candidatos/' . $nome_arquivo;

                // Criar diretório se não existir
                $dir_candidatos = dirname($caminho_destino);
                if (!is_dir($dir_candidatos)) {
                    mkdir($dir_candidatos, 0755, true);
                }

                if (copy($caminho_origem, $caminho_destino)) {
                    // Remover foto antiga se existir
                    if ($foto_candidato && file_exists(__DIR__ . '/../../../storage/uploads/candidatos/' . $foto_candidato)) {
                        unlink(__DIR__ . '/../../../storage/uploads/candidatos/' . $foto_candidato);
                    }
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
    // OPÇÃO 3: Upload de nova foto
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
        // Validar se é realmente uma imagem
        elseif (!getimagesize($arquivo['tmp_name'])) {
            $erro = "O arquivo enviado não é uma imagem válida.";
        } else {
            // Gerar nome único para o arquivo
            $nome_arquivo = 'candidato_' . $id_aluno . '_' . time() . '.' . $extensao;
            $dir_destino = __DIR__ . '/../../../storage/uploads/candidatos/';

            // Criar diretório se não existir
            if (!is_dir($dir_destino)) {
                mkdir($dir_destino, 0755, true);
            }

            $caminho_destino = $dir_destino . $nome_arquivo;

            // Mover arquivo para pasta de uploads
            if (move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
                // Remover foto antiga se existir
                if ($foto_candidato && file_exists(__DIR__ . '/../../../storage/uploads/candidatos/' . $foto_candidato)) {
                    unlink(__DIR__ . '/../../../storage/uploads/candidatos/' . $foto_candidato);
                }
                $foto_candidato = $nome_arquivo;
            } else {
                $erro = "Erro ao fazer upload da imagem. Tente novamente.";
            }
        }
    }

    // Validar se foto foi fornecida (OBRIGATÓRIO)
    if (empty($erro) && $foto_candidato === null) {
        $erro = "A foto do candidato é obrigatória. Por favor, selecione uma foto.";
    }

    // Se não houve erro, atualizar candidatura e resetar status para 'pendente'
    if (empty($erro)) {
        $stmtUpdate = $conn->prepare("
            UPDATE CANDIDATURA
            SET proposta = ?,
                foto_candidato = ?,
                status_validacao = 'pendente',
                validado_por = NULL,
                data_validacao = NULL,
                justificativa_indeferimento = NULL
            WHERE id_candidatura = ?
        ");

        if ($stmtUpdate->execute([$proposta, $foto_candidato, $id_candidatura])) {
            // Registrar auditoria
            registrarAuditoria(
                $conn,
                null, // Não é admin, é o próprio aluno
                'CANDIDATURA',
                'UPDATE',
                "Aluno $nome (RA: {$usuario['ra']}) editou candidatura indeferida #$id_candidatura - Status resetado para pendente",
                null,
                $id_eleicao,
                json_encode([
                    'status_anterior' => 'indeferido',
                    'proposta_anterior' => $candidatura['proposta'],
                    'foto_anterior' => $candidatura['foto_candidato']
                ]),
                json_encode([
                    'status_novo' => 'pendente',
                    'proposta_nova' => $proposta,
                    'foto_nova' => $foto_candidato
                ])
            );

            // Redirecionar para página de acompanhamento com mensagem de sucesso
            header("Location: acompanhar-inscricao.php?edicao_sucesso=1");
            exit;
        } else {
            $erro = "Erro ao atualizar candidatura. Tente novamente.";

            // Remover foto nova se a atualização falhar
            if ($foto_candidato !== $candidatura['foto_candidato'] && $foto_candidato) {
                $caminho_remover = __DIR__ . '/../../../storage/uploads/candidatos/' . $foto_candidato;
                if (file_exists($caminho_remover)) {
                    unlink($caminho_remover);
                }
            }
        }
    }
}

// Determinar foto atual para preview
$foto_preview = null;
if (!empty($candidatura['foto_candidato'])) {
    if (filter_var($candidatura['foto_candidato'], FILTER_VALIDATE_URL)) {
        $foto_preview = $candidatura['foto_candidato'];
    } else {
        $foto_preview = '../../../storage/uploads/candidatos/' . $candidatura['foto_candidato'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Candidatura - SIV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include 'components/header.php'; ?>

    <main class="user-application">
        <div class="card-wrapper">
            <div class="card">
                <h1 class="title">
                    <i class="fas fa-edit"></i>
                    Editar Candidatura
                </h1>

                <!-- Alerta de Indeferimento -->
                <div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 12px; padding: 20px; margin-bottom: 25px;">
                    <div style="display: flex; align-items: flex-start; gap: 15px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #856404; margin-top: 3px;"></i>
                        <div>
                            <h3 style="margin: 0 0 10px 0; color: #856404; font-size: 18px;">
                                Sua candidatura foi indeferida
                            </h3>
                            <?php if (!empty($candidatura['justificativa_indeferimento'])): ?>
                                <p style="margin: 0 0 15px 0; color: #856404; font-size: 14px; line-height: 1.6;">
                                    <strong>Motivo:</strong> <?= nl2br(htmlspecialchars($candidatura['justificativa_indeferimento'])) ?>
                                </p>
                            <?php endif; ?>
                            <p style="margin: 0; color: #856404; font-size: 14px;">
                                Você pode editar sua proposta e/ou foto abaixo. Após salvar, sua candidatura voltará ao status <strong>"pendente"</strong> e será reavaliada pelo administrador.
                            </p>
                        </div>
                    </div>
                </div>

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
                        <textarea id="qualidades" name="qualidades" placeholder="Descreva suas qualidades."><?= htmlspecialchars($candidatura['proposta'] ?? '') ?></textarea>
                        <span class="textarea-count">0/400 caracteres</span>
                    </div>

                    <div class="input-group">
                        <label>Foto do Candidato <span style="color:red;">*</span></label>

                        <?php if ($foto_preview): ?>
                            <div style="margin-bottom: 15px; text-align: center;">
                                <p style="margin-bottom: 10px; color: #666; font-size: 14px;">Foto atual:</p>
                                <img src="<?= htmlspecialchars($foto_preview) ?>" alt="Foto atual" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #005f73;">
                            </div>
                        <?php endif; ?>

                        <button type="button" class="button primary" onclick="event.preventDefault(); abrirModalFoto(); return false;">
                            Alterar Foto
                        </button>
                        <p id="foto-status" style="margin-top:10px; color:#28a745; display:none; font-weight:bold;"></p>
                    </div>

                    <div class="form-buttons">
                        <a href="acompanhar-inscricao.php" type="button" class="button secondary">Cancelar</a>
                        <button type="submit" class="button primary">Salvar Alterações</button>
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
            <h3 class="title" style="margin-bottom:25px; color:#005f73; font-size:24px;">📸 Alterar Foto da Candidatura</h3>

            <!-- PREVIEW DA FOTO -->
            <img id="previewFoto" src="<?= htmlspecialchars($foto_preview ?: obterFotoUsuario()) ?>" alt="Preview" style="width:200px; height:200px; border-radius:50%; object-fit:cover; margin-bottom:25px; border:4px solid #005f73; box-shadow:0 4px 12px rgba(0,0,0,0.15);">

            <!-- AVISO IMPORTANTE -->
            <div style="background:#fff3cd; border:2px solid #ffc107; border-radius:8px; padding:15px; margin-bottom:25px; text-align:left;">
                <p style="margin:0; color:#856404; font-weight:600; font-size:14px;">
                    ⚠️ <strong>ATENÇÃO:</strong> A nova foto será usada na votação após aprovação do administrador.
                </p>
            </div>

            <!-- OPÇÕES DE FOTO -->
            <div style="text-align:left; margin-bottom:25px;">
                <p style="font-weight:600; margin-bottom:15px; color:#333;">Escolha uma opção:</p>

                <label style="display:flex; align-items:center; gap:10px; padding:12px; border:2px solid #ddd; border-radius:8px; margin-bottom:10px; cursor:pointer; transition:all 0.2s;" onmouseover="this.style.borderColor='#005f73'; this.style.backgroundColor='#f0f9fa';" onmouseout="this.style.borderColor='#ddd'; this.style.backgroundColor='transparent';">
                    <input type="radio" name="opcao_foto" value="manter" id="radioManterAtual" checked style="width:20px; height:20px; cursor:pointer;">
                    <span style="flex:1; text-align:left;">Manter foto atual</span>
                </label>

                <label style="display:flex; align-items:center; gap:10px; padding:12px; border:2px solid #ddd; border-radius:8px; margin-bottom:10px; cursor:pointer; transition:all 0.2s;" onmouseover="this.style.borderColor='#005f73'; this.style.backgroundColor='#f0f9fa';" onmouseout="this.style.borderColor='#ddd'; this.style.backgroundColor='transparent';">
                    <input type="radio" name="opcao_foto" value="perfil" id="radioUsarPerfil" style="width:20px; height:20px; cursor:pointer;">
                    <span style="flex:1; text-align:left;">Usar minha foto de perfil</span>
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
                    <strong>Confirmo que revisei a foto</strong> e estou ciente de que a candidatura será reavaliada pelo administrador.
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
    // Foto inicial
    const fotoAtualInicial = '<?= htmlspecialchars($foto_preview ?: obterFotoUsuario()) ?>';
    const fotoPerfilInicial = '<?= htmlspecialchars(obterFotoUsuario()) ?>';

    // Contador de caracteres
    const textarea = document.getElementById('qualidades');
    const contador = document.querySelector('.textarea-count');

    if (textarea && contador) {
        // Atualizar contador inicial
        contador.textContent = `${textarea.value.length}/400 caracteres`;

        textarea.addEventListener('input', function() {
            const length = this.value.length;
            contador.textContent = `${length}/400 caracteres`;

            if (length > 400) {
                this.value = this.value.substring(0, 400);
                contador.textContent = '400/400 caracteres';
            }
        });
    }

    function abrirModalFoto() {
        const modal = document.getElementById('modalFoto');
        if (modal) {
            // Resetar para foto atual
            document.getElementById('previewFoto').src = fotoAtualInicial;
            document.getElementById('radioManterAtual').checked = true;
            document.getElementById('divUpload').style.display = 'none';
            document.getElementById('inputFotoModal').value = '';
            document.getElementById('checkboxConfirmacao').checked = false;

            modal.style.display = 'flex';
        }
    }

    function fecharModalFoto() {
        document.getElementById('modalFoto').style.display = 'none';
    }

    // Gerenciar mudança de opção
    document.querySelectorAll('input[name="opcao_foto"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const divUpload = document.getElementById('divUpload');
            const preview = document.getElementById('previewFoto');

            if (this.value === 'upload') {
                divUpload.style.display = 'block';
            } else {
                divUpload.style.display = 'none';

                if (this.value === 'perfil') {
                    preview.src = fotoPerfilInicial;
                } else if (this.value === 'manter') {
                    preview.src = fotoAtualInicial;
                }

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
        const inputAnteriorManter = document.getElementById('manter_foto_atual_hidden');
        if (inputAnteriorFoto) inputAnteriorFoto.remove();
        if (inputAnteriorOpcao) inputAnteriorOpcao.remove();
        if (inputAnteriorManter) inputAnteriorManter.remove();

        // OPÇÃO 1: Manter foto atual
        if (opcaoSelecionada === 'manter') {
            const inputManter = document.createElement('input');
            inputManter.type = 'hidden';
            inputManter.name = 'manter_foto_atual';
            inputManter.id = 'manter_foto_atual_hidden';
            inputManter.value = '1';
            formPrincipal.appendChild(inputManter);

            document.getElementById('foto-status').textContent = '✓ Mantendo foto atual';
            document.getElementById('foto-status').style.display = 'block';
        }
        // OPÇÃO 2: Usar foto de perfil
        else if (opcaoSelecionada === 'perfil') {
            const inputOpcao = document.createElement('input');
            inputOpcao.type = 'hidden';
            inputOpcao.name = 'usar_foto_perfil';
            inputOpcao.id = 'usar_foto_perfil_hidden';
            inputOpcao.value = '1';
            formPrincipal.appendChild(inputOpcao);

            document.getElementById('foto-status').textContent = '✓ Usando foto de perfil';
            document.getElementById('foto-status').style.display = 'block';
        }
        // OPÇÃO 3: Upload de nova foto
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
