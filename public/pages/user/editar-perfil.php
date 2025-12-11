<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';
require_once '../../../config/automacao-eleicoes.php'; // garante disponibilidade das funções do header

verificarAluno();

$usuario = obterUsuarioLogado();
$mensagem = '';
$erro = '';

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente novamente.");

    $nome = trim($_POST['nome'] ?? '');
    $senha_atual = $_POST['senha_atual'] ?? '';
    $senha_nova = $_POST['senha_nova'] ?? '';
    $senha_conf = $_POST['senha_conf'] ?? '';
    $arquivo = $_FILES['foto_perfil'] ?? null;

    // Validações básicas
    if (empty($nome)) {
        $erro = "O nome não pode ficar vazio.";
    } else {
        try {
            // Começar transação
            $conn->beginTransaction();

            // Buscar dados atuais do usuário
            $stmtUser = $conn->prepare("SELECT senha_hash, foto_perfil FROM ALUNO WHERE id_aluno = ?");
            $stmtUser->execute([$usuario['id']]);
            $dadosAtual = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$dadosAtual) {
                throw new Exception("Usuário não encontrado.");
            }

            $alteracoes = [];    // partes SET
            $params = [];        // params para UPDATE
            $atualizouAlgo = false;

            // --- Tratar alteração de nome ---
            if ($nome !== $usuario['nome']) {
                $alteracoes[] = "nome_completo = ?";
                $params[] = $nome;
                $atualizouAlgo = true;
            }

            // --- Tratar alteração de senha (se informado) ---
            if ($senha_nova !== '' || $senha_atual !== '' || $senha_conf !== '') {
                // exigir todos os campos
                if (empty($senha_atual) || empty($senha_nova) || empty($senha_conf)) {
                    throw new Exception("Para alterar a senha, preencha: senha atual, nova senha e confirmar nova senha.");
                }

                // verificar senha atual
                if (!password_verify($senha_atual, $dadosAtual['senha_hash'])) {
                    throw new Exception("Senha atual incorreta.");
                }

                // nova senha
                if (strlen($senha_nova) < 6) {
                    throw new Exception("A nova senha deve ter pelo menos 6 caracteres.");
                }

                if ($senha_nova !== $senha_conf) {
                    throw new Exception("A confirmação da nova senha não corresponde.");
                }

                $senha_hash = password_hash($senha_nova, PASSWORD_BCRYPT);
                $alteracoes[] = "senha_hash = ?";
                $params[] = $senha_hash;
                $atualizouAlgo = true;
            }

            // --- Tratar upload de foto (opcional) ---
            if ($arquivo && $arquivo['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($arquivo['error'] === UPLOAD_ERR_OK) {
                    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (!in_array($extensao, $extensoesPermitidas)) {
                        throw new Exception("Formato de arquivo não permitido. Use: JPG, PNG, GIF ou WebP.");
                    } elseif ($arquivo['size'] > 5 * 1024 * 1024) {
                        throw new Exception("Arquivo muito grande. Tamanho máximo: 5MB.");
                    } else {
                        $diretorioUpload = __DIR__ . '/../../storage/uploads/perfil/';
                        if (!is_dir($diretorioUpload)) {
                            mkdir($diretorioUpload, 0755, true);
                        }

                        $nomeArquivo = 'perfil_' . $usuario['id'] . '_' . time() . '.' . $extensao;
                        $caminhoCompleto = $diretorioUpload . $nomeArquivo;

                        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                            throw new Exception("Erro ao mover o arquivo enviado.");
                        }

                        // caminho relativo (visível pelo HTML nas páginas em public/pages/user/)
                        $caminhoRelativo = '../../storage/uploads/perfil/' . $nomeArquivo;

                        // Preparar para atualizar no DB
                        $alteracoes[] = "foto_perfil = ?";
                        $params[] = $caminhoRelativo;

                        $alteracoes[] = "foto_perfil = ?";
                        $params[] = $arquivo['name'];

                        $atualizouAlgo = true;

                        // Excluir foto antiga (após commit bem sucedido vamos apagar; mas já podemos guardar a referência)
                        $fotoAntiga = $dadosAtual['foto_perfil'] ?? null;
                    }
                } else {
                    throw new Exception("Erro no upload: " . $arquivo['error']);
                }
            }

            // Se há alterações, montar e executar UPDATE
            if ($atualizouAlgo && count($alteracoes) > 0) {
                $sql = "UPDATE ALUNO SET " . implode(', ', $alteracoes) . " WHERE id_aluno = ?";
                $params[] = $usuario['id'];
                $stmtUpdate = $conn->prepare($sql);
                $stmtUpdate->execute($params);
            }

            $conn->commit();

            // Após commit: excluir foto antiga (se trocou)
            if (!empty($fotoAntiga) && isset($caminhoRelativo) && strpos($fotoAntiga, 'storage/uploads/perfil/') !== false) {
                // caminho absoluto do arquivo antigo
                $caminhoAntigoCompleto = __DIR__ . '/../../' . str_replace('../../', '', $fotoAntiga);
                if (file_exists($caminhoAntigoCompleto)) {
                    @unlink($caminhoAntigoCompleto);
                }
            }

            // Atualizar dados da sessão local (caso tenham mudado)
            if ($atualizouAlgo) {
                // Atualiza sessão para refletir mudanças — nomes dos índices dependem de sua implementação de session
                $_SESSION['usuario_nome'] = $nome;
                if (isset($caminhoRelativo)) {
                    $_SESSION['usuario_foto'] = $caminhoRelativo;
                }

                // Recarregar $usuario para refletir mudanças
                $usuario = obterUsuarioLogado();
                $mensagem = "Perfil atualizado com sucesso!";
            } else {
                $mensagem = "Nenhuma alteração foi feita.";
            }

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $erro = $e->getMessage();
            error_log("Erro ao atualizar perfil: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Perfil - SIV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/user.css">
    <link rel="stylesheet" href="../../assets/styles/editar_perfil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>
    <?php include 'components/header.php'; ?>

    <main>
        <div class="profile-edit-container">
            <h1>Editar Perfil</h1>

            <?php if (!empty($mensagem)): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($mensagem) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($erro)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($erro) ?></span>
                </div>
            <?php endif; ?>

            <div class="current-photo" style="display:flex;align-items:center;gap:16px;margin-bottom:18px;">
                <img src="<?= htmlspecialchars(obterFotoUsuario()) ?>" alt="Foto atual" id="currentPhoto">
                <div>
                    <p><strong><?= htmlspecialchars($usuario['nome']) ?></strong></p>
                    <p style="color:#666;font-size:13px;"><?= htmlspecialchars($usuario['ra'] ?? '') ?></p>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" id="editarPerfilForm" class="upload-form">
                <?= campoCSRF() ?>

                <div class="form-grid">
                    <div class="input-group full">
                        <label for="nome">Nome Completo *</label>
                        <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($usuario['nome']) ?>">
                    </div>

                    <div class="input-group">
                        <label for="senha_atual">Senha Atual</label>
                        <input type="password" id="senha_atual" name="senha_atual" placeholder="Digite sua senha atual (necessária para trocar)">
                    </div>

                    <div class="input-group">
                        <label for="senha_nova">Nova Senha</label>
                        <input type="password" id="senha_nova" name="senha_nova" minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>

                    <div class="input-group">
                        <label for="senha_conf">Confirmar Nova Senha</label>
                        <input type="password" id="senha_conf" name="senha_conf" minlength="6" placeholder="Confirme a nova senha">
                    </div>

                    <div class="input-group full">
                        <label for="foto_perfil">Trocar Foto de Perfil</label>
                        <label for="foto_perfil" class="file-input-label" style="display:inline-flex;">
                            <i class="fas fa-upload"></i>&nbsp;Escolher Nova Foto
                        </label>
                        <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*">
                        <div class="file-name" id="fileName">Nenhum arquivo selecionado</div>
                    </div>

                    <div class="preview-container full" id="previewContainer" style="display:none;">
                        <p>Pré-visualização:</p>
                        <img src="" alt="Preview" id="preview" style="width:120px;height:120px;border-radius:50%;object-fit:cover;">
                    </div>
                </div>

                <div style="display:flex;gap:12px;margin-top:18px;">
                    <a href="../../pages/user/index.php" class="btn-secondary" style="padding:10px 18px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Cancelar</a>
                    <button type="submit" class="btn-primary" style="padding:10px 18px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;">
                        <i class="fas fa-save"></i>&nbsp;Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="site" style="margin-top:36px;">
        <div class="content">
            <img src="../../assets/images/logo-governo-do-estado-sp.png" alt="Logo Governo SP" class="logo-governo">
            <a href="../../pages/guest/sobre.php" class="btn-about">SOBRE O SISTEMA</a>
            <p>Sistema Integrado de Votação - FATEC/CPS</p>
            <p>Versão 0.1 (11/06/2025)</p>
        </div>
    </footer>

    <script>
        // Preview e UX do campo de arquivo (mesma lógica do mudar_foto)
        const fileInput = document.getElementById('foto_perfil');
        const fileName = document.getElementById('fileName');
        const preview = document.getElementById('preview');
        const previewContainer = document.getElementById('previewContainer');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (file) {
                fileName.textContent = file.name;

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = 'Nenhum arquivo selecionado';
                previewContainer.style.display = 'none';
            }
        });

        // Melhorar usabilidade: se o usuário preenche nova senha sem confirmar, avisar antes do submit (cliente-side)
        document.getElementById('editarPerfilForm').addEventListener('submit', function(e) {
            const nova = document.getElementById('senha_nova').value;
            const conf = document.getElementById('senha_conf').value;
            const atual = document.getElementById('senha_atual').value;

            if ((nova || conf || atual) && (!atual || !nova || !conf)) {
                e.preventDefault();
                alert('Para alterar a senha, preencha: senha atual, nova senha e confirmar nova senha.');
                return;
            }

            if (nova && nova.length < 6) {
                e.preventDefault();
                alert('A nova senha deve ter pelo menos 6 caracteres.');
                return;
            }

            if (nova && nova !== conf) {
                e.preventDefault();
                alert('A confirmação da nova senha não corresponde.');
                return;
            }
        });
    </script>
</body>
</html>
