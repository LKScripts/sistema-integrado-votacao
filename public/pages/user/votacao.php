<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/automacao_eleicoes.php';
require_once '../../../config/csrf.php';

// Verifica se é aluno logado
verificarAluno();

$usuario = obterUsuarioLogado();
$id_aluno = $usuario['id'];
$curso = $usuario['curso'];
$semestre = $usuario['semestre'];

$erro = "";
$voto_confirmado = false;

// Buscar eleição ativa para votação (COM VERIFICAÇÃO AUTOMÁTICA)
$eleicao = buscarEleicaoAtivaComVerificacao($curso, $semestre, 'votacao');

// Verificar se já votou
$ja_votou = false;
if ($eleicao) {
    $stmtVerifica = $conn->prepare("SELECT id_voto FROM VOTO WHERE id_eleicao = ? AND id_aluno = ?");
    $stmtVerifica->execute([$eleicao['id_eleicao'], $id_aluno]);
    $ja_votou = ($stmtVerifica->fetch() !== false);
}

// Processa o voto se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote']) && $eleicao && !$ja_votou) {
    // VALIDAR CSRF PRIMEIRO
    validarCSRFOuMorrer("Token de segurança inválido. Recarregue a página e tente votar novamente.");

    $id_candidatura = intval($_POST['vote']);

    // VERIFICAÇÃO EXTRA: Garantir que votação ainda está aberta (proteção contra formulários abertos após prazo)
    $verificacao = verificarPeriodoVotacao($eleicao['id_eleicao']);

    if (!$verificacao['valido']) {
        $erro = $verificacao['mensagem'];
    } else {
        // VALIDAÇÃO CRÍTICA: Verificar se o candidato pertence a esta eleição e está deferido
        $stmtValidaCandidato = $conn->prepare("
            SELECT id_candidatura
            FROM CANDIDATURA
            WHERE id_candidatura = ?
              AND id_eleicao = ?
              AND status_validacao = 'deferido'
        ");
        $stmtValidaCandidato->execute([$id_candidatura, $eleicao['id_eleicao']]);

        if (!$stmtValidaCandidato->fetch()) {
            $erro = "Candidato inválido ou não aprovado para esta eleição.";
        } else {
            // Inserir voto
            $stmtVoto = $conn->prepare("
                INSERT INTO VOTO (id_eleicao, id_aluno, id_candidatura, ip_votante)
                VALUES (?, ?, ?, ?)
            ");
            $ip = $_SERVER['REMOTE_ADDR'];

            if ($stmtVoto->execute([$eleicao['id_eleicao'], $id_aluno, $id_candidatura, $ip])) {
                $voto_confirmado = true;
                $ja_votou = true;
            } else {
                $erro = "Erro ao registrar voto. Tente novamente.";
            }
        }
    }
}

// Buscar candidatos deferidos para a eleição
$candidatos = [];
if ($eleicao) {
    $stmtCandidatos = $conn->prepare("
        SELECT c.id_candidatura, a.nome_completo, a.ra, c.proposta, c.foto_candidato, a.foto_perfil
        FROM CANDIDATURA c
        JOIN ALUNO a ON c.id_aluno = a.id_aluno
        WHERE c.id_eleicao = ? AND c.status_validacao = 'deferido'
        ORDER BY a.nome_completo
    ");
    $stmtCandidatos->execute([$eleicao['id_eleicao']]);
    $candidatos = $stmtCandidatos->fetchAll();
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
<header class="site">
    <nav class="navbar">
        <div class="logo">
            <img src="../../assets/images/fatec-ogari.png" alt="Logo Fatec Itapira">
            <img src="../../assets/images/logo-cps.png" alt="Logo CPS">
        </div>

        <ul class="links">
            <li><a href="../../pages/user/index.php">Home</a></li>
            <li><a href="../../pages/user/inscricao.php">Inscrição</a></li>
            <li><a href="../../pages/user/votacao.php" class="active">Votação</a></li>
            <li><a href="../../pages/user/sobre.php">Sobre</a></li>
        </ul>

        <div class="actions">
            <img src="<?= htmlspecialchars(obterFotoUsuario()) ?>" alt="Avatar do usuário" class="user-icon">
            <a href="../../logout.php">Sair da Conta</a>
        </div>
    </nav>
</header>

<main class="user-vote">
    <div class="container">
        <header>
            <h1>Candidatos <?= htmlspecialchars($curso) ?> - <?= $semestre ?>º Semestre</h1>
            <p>Vote para o candidato que você quer que represente você durante esse semestre na sua sala.</p>
        </header>

        <?php if ($ja_votou && !$voto_confirmado): ?>
            <div class="callout info" style="margin-bottom: 20px;">
                <div class="content">
                    <span><strong>Você já votou nesta eleição!</strong> Não é possível votar novamente.</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($voto_confirmado)): ?>
            <div class="modal feedback" style="display:block;">
                <div class="content">
                    <h3 class="title">Voto Confirmado!</h3>
                    <div class="text">
                        <p>✅ Seu voto foi registrado com sucesso!</p>
                        <p>Obrigado por participar das votações!</p>
                    </div>
                    <div class="modal-buttons">
                        <a href="../../pages/user/index.php" class="button primary">Voltar</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="callout danger" style="margin-bottom: 20px;">
                <div class="content">
                    <span><strong>Erro:</strong> <?= htmlspecialchars($erro) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$eleicao): ?>
            <div class="callout warning">
                <div class="content">
                    <span><strong>Não há eleição aberta para votação no momento</strong> para seu curso e semestre.</span>
                </div>
            </div>
        <?php elseif (empty($candidatos)): ?>
            <div class="callout warning">
                <div class="content">
                    <span><strong>Nenhum candidato aprovado</strong> para esta eleição.</span>
                </div>
            </div>
        <?php else: ?>
            <section class="candidates">
                <?php foreach ($candidatos as $candidato): ?>
                    <div class="candidate-card" onclick="<?= !empty($candidato['proposta']) ? 'openProposalModal(' . $candidato['id_candidatura'] . ')' : '' ?>" style="<?= !empty($candidato['proposta']) ? 'cursor: pointer;' : '' ?>">
                        <div class="media">
                            <?php
                            // PRIORIZAR foto_candidato (congelada) sobre foto_perfil
                            if (!empty($candidato['foto_candidato'])) {
                                // Verificar se é URL completa (dados antigos de teste) ou arquivo local
                                if (filter_var($candidato['foto_candidato'], FILTER_VALIDATE_URL)) {
                                    // É uma URL completa (ex: https://i.pravatar.cc/...)
                                    $foto_exibir = $candidato['foto_candidato'];
                                } else {
                                    // É nome de arquivo local
                                    $foto_exibir = '../../../storage/uploads/candidatos/' . $candidato['foto_candidato'];
                                }
                            } elseif (!empty($candidato['foto_perfil'])) {
                                $foto_exibir = $candidato['foto_perfil'];
                            } else {
                                $foto_exibir = null;
                            }
                            ?>
                            <?php if (!empty($foto_exibir)): ?>
                                <img src="<?= htmlspecialchars($foto_exibir) ?>" alt="Foto de <?= htmlspecialchars($candidato['nome_completo']) ?>" onerror="console.error('Erro ao carregar:', this.src); this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="placeholder" style="display:none;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php else: ?>
                                <div class="placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="content">
                            <h2><?= htmlspecialchars($candidato['nome_completo']) ?></h2>
                            <div class="info-row">
                                <i class="fas fa-id-card"></i>
                                <span>RA: <?= htmlspecialchars($candidato['ra']) ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-graduation-cap"></i>
                                <span><?= htmlspecialchars($curso) ?></span>
                            </div>
                            <?php if (!empty($candidato['proposta'])): ?>
                                <div class="info-row proposta-preview">
                                    <p style="margin-top: 10px;"><strong>Proposta:</strong> <?= htmlspecialchars(substr($candidato['proposta'], 0, 100)) ?>...</p>
                                    <span class="ver-mais-badge">
                                        <i class="fas fa-eye"></i> Clique para ver completa
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!$ja_votou): ?>
                            <form method="post" onsubmit="return confirm('Confirma seu voto em <?= htmlspecialchars($candidato['nome_completo']) ?>?');" onclick="event.stopPropagation();">
                                <?= campoCSRF() ?>
                                <input type="hidden" name="vote" value="<?= $candidato['id_candidatura'] ?>">
                                <button type="submit" class="vote">
                                    <i class="fas fa-vote-yea"></i>
                                    <span>VOTAR</span>
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="vote" disabled style="opacity: 0.5; cursor: not-allowed;" onclick="event.stopPropagation();">
                                <i class="fas fa-check"></i>
                                <span>JÁ VOTOU</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Modal para ver proposta completa -->
                    <?php if (!empty($candidato['proposta'])): ?>
                        <div id="modal-proposta-<?= $candidato['id_candidatura'] ?>" class="modal-proposta" onclick="closeProposalModal(<?= $candidato['id_candidatura'] ?>)">
                            <div class="modal-proposta-content" onclick="event.stopPropagation();">
                                <div class="modal-proposta-header">
                                    <h3><?= htmlspecialchars($candidato['nome_completo']) ?></h3>
                                    <button class="modal-close" onclick="closeProposalModal(<?= $candidato['id_candidatura'] ?>)">&times;</button>
                                </div>
                                <div class="modal-proposta-body">
                                    <h4>Proposta Completa:</h4>
                                    <p><?= nl2br(htmlspecialchars($candidato['proposta'])) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <div class="callout info">
            <div class="content">
                <div class="instructions">
                    <p class="title">Como votar</p>
                    <ol>
                        <li><strong>Escolha seu candidato:</strong> Clique no botão "VOTAR" abaixo do seu candidato preferido.</li>
                        <li><strong>Confirme sua escolha:</strong> Você verá uma janela de confirmação para verificar seu voto.</li>
                        <li><strong>Finalizando seu voto:</strong> Após confirmação, seu voto será registrado com segurança no sistema.</li>
                        <li><strong>Apenas um voto:</strong> Você pode votar em apenas um candidato!</li>
                    </ol>
                </div>
            </div>
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

<script>
function openProposalModal(id) {
    const modal = document.getElementById('modal-proposta-' + id);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeProposalModal(id) {
    const modal = document.getElementById('modal-proposta-' + id);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Fechar modal ao pressionar ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal-proposta');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    }
});
</script>
</body>
</html>
