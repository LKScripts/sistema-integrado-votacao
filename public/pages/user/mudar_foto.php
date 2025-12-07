<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';
require_once '../../../config/automacao_eleicoes.php';


verificarAluno();

$usuario = obterUsuarioLogado();
$mensagem = '';
$erro = '';

// Processar upload da foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente novamente.");

    $arquivo = $_FILES['foto_perfil'];

    // Validações
    if ($arquivo['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($extensao, $extensoesPermitidas)) {
            $erro = "Formato de arquivo não permitido. Use: JPG, PNG, GIF ou WebP.";
        } elseif ($arquivo['size'] > 5 * 1024 * 1024) { // 5MB
            $erro = "Arquivo muito grande. Tamanho máximo: 5MB.";
        } else {
            // VALIDAÇÃO MIME TYPE (Issue #8)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $arquivo['tmp_name']);
            finfo_close($finfo);

            $mimePermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!in_array($mimeType, $mimePermitidos)) {
                $erro = "Tipo de arquivo inválido. O arquivo não é uma imagem válida.";
            } else {
                // Diretório de upload
                $diretorioUpload = __DIR__ . '/../../storage/uploads/perfil/';

                if (!is_dir($diretorioUpload)) {
                    mkdir($diretorioUpload, 0755, true);
                }

                // Nome único para o arquivo (Issue #9: usar uniqid())
                $nomeArquivo = 'perfil_' . $usuario['id'] . '_' . uniqid() . '.' . $extensao;
                $caminhoCompleto = $diretorioUpload . $nomeArquivo;

                // Mover arquivo
                if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                // Caminho relativo do ponto de vista do HTML renderizado em public/pages/user/
                $caminhoRelativo = '../../storage/uploads/perfil/' . $nomeArquivo;

                // Buscar foto antiga para deletar
                $stmtOld = $conn->prepare("SELECT foto_perfil FROM ALUNO WHERE id_aluno = ?");
                $stmtOld->execute([$usuario['id']]);
                $fotoAntiga = $stmtOld->fetch();

                // Atualizar no banco
                $stmt = $conn->prepare("UPDATE ALUNO SET foto_perfil = ?, foto_perfil = ? WHERE id_aluno = ?");

                if ($stmt->execute([$caminhoRelativo, $arquivo['name'], $usuario['id']])) {
                    // Atualizar sessão
                    $_SESSION['usuario_foto'] = $caminhoRelativo;

                    // Deletar foto antiga se existir e for diferente da padrão
                    if ($fotoAntiga && !empty($fotoAntiga['foto_perfil']) && strpos($fotoAntiga['foto_perfil'], 'storage/uploads/perfil/') !== false) {
                        $caminhoAntigoCompleto = __DIR__ . '/../../' . str_replace('../../', '', $fotoAntiga['foto_perfil']);
                        if (file_exists($caminhoAntigoCompleto)) {
                            unlink($caminhoAntigoCompleto);
                        }
                    }

                    $mensagem = "Foto de perfil atualizada com sucesso!";
                } else {
                    $erro = "Erro ao salvar foto no banco de dados.";
                }
                } else {
                    $erro = "Erro ao fazer upload do arquivo.";
                }
            }
        }
    } else {
        $erro = "Erro no upload: " . $arquivo['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mudar Foto de Perfil - SIV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .photo-upload-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .photo-upload-container h1 {
            color: var(--secondary);
            margin-bottom: 20px;
            text-align: center;
        }

        .current-photo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            gap: 15px;
        }

        .current-photo img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .current-photo p {
            color: #666;
            font-size: 14px;
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .file-input-wrapper {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .file-input-wrapper input[type="file"] {
            display: none;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background-color: var(--primary);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 600;
        }

        .file-input-label:hover {
            background-color: #004654;
        }

        .file-name {
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 8px;
            color: #666;
            font-size: 14px;
            text-align: center;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-container {
            display: none;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }

        .preview-container.show {
            display: flex;
        }

        .preview-container img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .preview-container p {
            font-weight: 600;
            color: var(--primary);
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .button-group button,
        .button-group a {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #004654;
        }

        .btn-secondary {
            background-color: #ccc;
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #bbb;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info-text {
            font-size: 14px;
            color: #666;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <?php include 'components/header.php'; ?>

    <main>
        <div class="photo-upload-container">
            <h1>Mudar Foto de Perfil</h1>

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

            <div class="current-photo">
                <img src="<?= htmlspecialchars(obterFotoUsuario()) ?>" alt="Foto atual" id="currentPhoto">
                <p>Foto atual</p>
            </div>

            <form method="post" enctype="multipart/form-data" class="upload-form" id="uploadForm">
                <?= campoCSRF() ?>

                <div class="file-input-wrapper">
                    <label for="foto_perfil" class="file-input-label">
                        <i class="fas fa-upload"></i>
                        <span>Escolher Nova Foto</span>
                    </label>
                    <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*" required>
                    <div class="file-name" id="fileName">Nenhum arquivo selecionado</div>
                </div>

                <div class="preview-container" id="previewContainer">
                    <p>Pré-visualização:</p>
                    <img src="" alt="Preview" id="preview">
                </div>

                <p class="info-text">
                    <i class="fas fa-info-circle"></i>
                    Formatos aceitos: JPG, PNG, GIF, WebP | Tamanho máximo: 5MB
                </p>

                <div class="button-group">
                    <a href="../../pages/user/index.php" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Salvar Foto
                    </button>
                </div>
            </form>
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

    <script>
        const fileInput = document.getElementById('foto_perfil');
        const fileName = document.getElementById('fileName');
        const preview = document.getElementById('preview');
        const previewContainer = document.getElementById('previewContainer');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (file) {
                fileName.textContent = file.name;

                // Mostrar preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.add('show');
                };
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = 'Nenhum arquivo selecionado';
                previewContainer.classList.remove('show');
            }
        });
    </script>
</body>

</html>
