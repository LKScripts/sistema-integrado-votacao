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

// Verificar se há eleição em fase de candidatura (para mostrar/ocultar link Inscrição)
$eleicaoCandidatura = buscarEleicaoAtivaComVerificacao($curso, $semestre, 'candidatura');

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

    // VALIDAR SENHA DE CONFIRMAÇÃO
    if (!isset($_POST['senha_confirmacao']) || empty($_POST['senha_confirmacao'])) {
        $erro = "Senha de confirmação não fornecida.";
    } else {
        // Buscar senha do aluno no banco
        $stmtSenha = $conn->prepare("SELECT senha_hash FROM ALUNO WHERE id_aluno = ?");
        $stmtSenha->execute([$id_aluno]);
        $aluno = $stmtSenha->fetch();

        if (!$aluno || !password_verify($_POST['senha_confirmacao'], $aluno['senha_hash'])) {
            $erro = "Senha incorreta. Não foi possível confirmar o voto.";
        }
    }

    // Se não houver erro de senha, prosseguir com o voto
    if (empty($erro)) {
        // Aceitar voto em branco ou voto em candidato específico
        $id_candidatura = $_POST['vote'] === 'branco' ? null : intval($_POST['vote']);

        // VERIFICAÇÃO EXTRA: Garantir que votação ainda está aberta (proteção contra formulários abertos após prazo)
        $verificacao = verificarPeriodoVotacao($eleicao['id_eleicao']);

        if (!$verificacao['valido']) {
            $erro = $verificacao['mensagem'];
        } else {
            // Se for voto em branco, inserir direto sem validação de candidato
            if ($id_candidatura === null) {
                $stmtVoto = $conn->prepare("
                    INSERT INTO VOTO (id_eleicao, id_aluno, id_candidatura, ip_votante, assinatura_digital)
                    VALUES (?, ?, NULL, ?, TRUE)
                ");
                $ip = $_SERVER['REMOTE_ADDR'];

                if ($stmtVoto->execute([$eleicao['id_eleicao'], $id_aluno, $ip])) {
                    $voto_confirmado = true;
                    $ja_votou = true;
                } else {
                    $erro = "Erro ao registrar voto em branco. Tente novamente.";
                }
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
                    // Inserir voto com assinatura digital (confirmado com senha)
                    $stmtVoto = $conn->prepare("
                        INSERT INTO VOTO (id_eleicao, id_aluno, id_candidatura, ip_votante, assinatura_digital)
                        VALUES (?, ?, ?, ?, TRUE)
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
    <link rel="stylesheet" href="../../assets/styles/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Garantir que modal de voto apareça */
        #modalConfirmarVoto.modal.show {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
    </style>
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
            <?php if ($eleicaoCandidatura): ?>
                <li><a href="../../pages/user/inscricao.php">Inscrição</a></li>
            <?php endif; ?>
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

        <div class="callout info">
            <div class="content">
                <div class="instructions">
                    <p class="title">Como votar</p>
                    <ol>
                        <li><strong>Escolha seu candidato:</strong> Clique no botão "VOTAR" do seu candidato preferido, ou opte por votar em branco.</li>
                        <li><strong>Confirme com sua senha:</strong> Por segurança, você precisará confirmar sua identidade digitando sua senha.</li>
                        <li><strong>Voto registrado:</strong> Após confirmação, seu voto é registrado de forma segura e anônima no banco de dados.</li>
                        <li><strong>Uma única escolha:</strong> Cada eleitor pode votar apenas uma vez por eleição.</li>
                        <li><strong>Sigilo garantido:</strong> Seu voto não pode ser vinculado à sua identidade, garantindo total sigilo eleitoral.</li>
                        <li><strong>Voto em branco:</strong> É uma opção válida - seu voto conta na participação sem escolher candidato.</li>
                    </ol>
                </div>
            </div>
        </div>

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
                        <p>Seu voto foi registrado com sucesso!</p>
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
                    <div class="candidate-card">
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
                                    <span class="ver-mais-badge" onclick="openProposalModal(<?= $candidato['id_candidatura'] ?>); event.stopPropagation();" style="cursor: pointer;">
                                        <i class="fas fa-eye"></i> Clique para ver proposta completa
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!$ja_votou): ?>
                            <button type="button" class="vote" onclick="console.log('Botão clicado'); event.stopPropagation(); abrirModalVoto(<?= $candidato['id_candidatura'] ?>, '<?= addslashes($candidato['nome_completo']) ?>');">
                                <i class="fas fa-vote-yea"></i>
                                <span>VOTAR</span>
                            </button>
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

            <?php if (!$ja_votou): ?>
                <!-- Opção de Voto em Branco -->
                <div class="voto-branco-container">
                    <h3>Não quer votar em nenhum candidato?</h3>
                    <p class="voto-branco-descricao">
                        O voto em branco é uma opção democrática válida. Seu voto será registrado e contabilizado na participação total, mas não será atribuído a nenhum candidato específico. Esta escolha demonstra seu exercício de direito eleitoral.
                    </p>
                    <button type="button" class="button-voto-branco" onclick="abrirModalVoto('branco', 'VOTO EM BRANCO');">
                        <i class="fas fa-ban"></i>
                        <span>VOTAR EM BRANCO</span>
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Modal de Confirmação de Voto -->
<div id="modalConfirmarVoto" class="modal">
    <div class="modal-content" style="max-width: 450px; width: auto;">
        <div class="modal-header">
            <h2>Confirmar Voto</h2>
            <button class="btn-close" onclick="fecharModalVoto()">&times;</button>
        </div>

        <form id="formConfirmarVoto" method="POST" action="">
            <div class="modal-body">
                <?php if (!empty($erro) && strpos($erro, 'Senha incorreta') !== false): ?>
                    <div style="margin-bottom: 20px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 6px;">
                        <strong>Erro:</strong> <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 20px; padding: 12px; background: #e8f4f8; border-radius: 6px; color: #004654;">
                    Você está prestes a votar em: <strong id="nomeCandidatoModal"></strong>
                </div>

                <div class="form-group">
                    <label for="senha_confirmacao">Digite sua senha para confirmar</label>
                    <input
                        type="password"
                        id="senha_confirmacao"
                        name="senha_confirmacao"
                        required
                        placeholder="Digite sua senha"
                        autocomplete="current-password"
                        <?php if (!empty($erro) && strpos($erro, 'Senha incorreta') !== false): ?>
                            style="border-color: #f5c6cb;"
                        <?php endif; ?>>
                    <small>Por segurança, confirme sua identidade antes de votar.</small>
                </div>

                <input type="hidden" id="vote_value" name="vote" value="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? gerarTokenCSRF() ?>">
            </div>

            <div class="form-buttons">
                <button type="button" class="button secondary" onclick="fecharModalVoto()">
                    Cancelar
                </button>
                <button type="submit" class="button primary">
                    Confirmar Voto
                </button>
            </div>
        </form>
    </div>
</div>

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

// Função para abrir modal de confirmação de voto
function abrirModalVoto(idCandidato, nomeCandidato) {
    console.log('abrirModalVoto chamada', idCandidato, nomeCandidato);

    const modal = document.getElementById('modalConfirmarVoto');
    const nomeModalElement = document.getElementById('nomeCandidatoModal');
    const voteValueInput = document.getElementById('vote_value');
    const senhaInput = document.getElementById('senha_confirmacao');

    console.log('Elementos:', {modal, nomeModalElement, voteValueInput, senhaInput});

    if (modal && nomeModalElement && voteValueInput) {
        nomeModalElement.textContent = nomeCandidato;
        voteValueInput.value = idCandidato;
        senhaInput.value = '';

        modal.classList.add('show');
        console.log('Classe show adicionada ao modal');
        console.log('Classes do modal:', modal.classList);

        // Focar no campo de senha após abrir
        setTimeout(() => {
            senhaInput.focus();
        }, 100);
    } else {
        console.error('Algum elemento não foi encontrado!');
    }
}

// Função para fechar modal de confirmação de voto
function fecharModalVoto() {
    const modal = document.getElementById('modalConfirmarVoto');
    const senhaInput = document.getElementById('senha_confirmacao');

    if (modal) {
        modal.classList.remove('show');
        senhaInput.value = '';
    }
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modalVoto = document.getElementById('modalConfirmarVoto');
    if (event.target === modalVoto) {
        fecharModalVoto();
    }
}

// Fechar modais ao pressionar ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        // Fechar modal de voto
        fecharModalVoto();

        // Fechar modais de proposta
        const modals = document.querySelectorAll('.modal-proposta');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    }
});

// Reabrir modal se houver erro de senha
<?php if (!empty($erro) && strpos($erro, 'Senha incorreta') !== false && isset($_POST['vote'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const voteValue = '<?= htmlspecialchars($_POST['vote'] ?? '', ENT_QUOTES) ?>';
        // Buscar o nome do candidato ou "VOTO EM BRANCO"
        <?php if ($_POST['vote'] === 'branco'): ?>
            abrirModalVoto('branco', 'VOTO EM BRANCO');
        <?php else: ?>
            // Buscar nome do candidato pelo ID
            <?php
            $voteId = intval($_POST['vote']);
            $stmtNome = $conn->prepare("SELECT a.nome_completo FROM CANDIDATURA c JOIN ALUNO a ON c.id_aluno = a.id_aluno WHERE c.id_candidatura = ?");
            $stmtNome->execute([$voteId]);
            $candidatoNome = $stmtNome->fetchColumn();
            ?>
            abrirModalVoto(<?= $voteId ?>, '<?= addslashes($candidatoNome) ?>');
        <?php endif; ?>
    });
<?php endif; ?>
</script>
</body>
</html>
