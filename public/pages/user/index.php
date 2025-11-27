<?php
// pages/user/index.php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/automacao_eleicoes.php';

verificarAluno();

$usuario = obterUsuarioLogado();
$id_aluno = $usuario['id'];
$curso = $usuario['curso'];
$semestre = $usuario['semestre'];

// Verificar estado das eleições para este aluno
$eleicao_candidatura = buscarEleicaoAtivaComVerificacao($curso, $semestre, 'candidatura');
$eleicao_votacao = buscarEleicaoAtivaComVerificacao($curso, $semestre, 'votacao');

// Verificar se o aluno tem candidatura cadastrada
$tem_candidatura = false;
$candidatura = null;

if ($eleicao_candidatura || $eleicao_votacao) {
    $id_eleicao = $eleicao_candidatura['id_eleicao'] ?? $eleicao_votacao['id_eleicao'] ?? null;

    if ($id_eleicao) {
        $stmt = $conn->prepare("
            SELECT c.*, e.status as status_eleicao
            FROM CANDIDATURA c
            JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
            WHERE c.id_aluno = ?
            AND e.curso = ?
            AND e.semestre = ?
            ORDER BY c.data_inscricao DESC
            LIMIT 1
        ");
        $stmt->execute([$id_aluno, $curso, $semestre]);
        $candidatura = $stmt->fetch();

        if ($candidatura) {
            $tem_candidatura = true;
        }
    }
}

// Determinar estado dos botões
$pode_inscrever = !empty($eleicao_candidatura);
$pode_votar = !empty($eleicao_votacao);
$pode_acompanhar = $tem_candidatura;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include 'components/header.php'; ?>

    <main class="user-home">
        <div class="card-wrapper">
            <div class="card">
                <section class="voting">
                    <div class="voting-text">
                        <h1>AMBIENTE DE VOTAÇÃO</h1>
                        <p>
                            Esta interface exibe todos os processos eleitorais em andamento nos quais você está
                            cadastrado como eleitor.
                            Observe que:
                        </p>
                        <ul>
                            <li>
                                Cada processo exibirá o botão <b>VOTAR</b> para acessar a cédula eleitoral.
                            </li>
                            <li>
                                Os prazos são rigorosos: o sistema <b>bloqueará</b> o acesso no horário de encerramento.
                            </li>
                        </ul>
                    </div>

                    <div class="voting-illustration">
                        <img src="../../assets/images/voting-amico.svg" alt="Ilustração da votação">
                    </div>
                </section>

                <section class="security">
                    <h2>SEGURANÇA DO SEU VOTO</h2>
                    <p>
                        Sua identidade é verificada apenas no acesso e o registro de voto não contém informações que
                        possam
                        vinculá-lo
                        às suas escolhas, garantindo seu sigilo absoluto para exercício livre do seu direito.
                    </p>
                </section>

                <div class="alert">
                    <span>⚠️ATENÇÃO: Verifique os prazos de cada edital para não perder seu direito de voto!</span>
                </div>
            </div>
        </div>

        <div class="card-wrapper">
            <div class="card">
                <h1>AÇÕES DISPONÍVEIS</h1>
                <p>Confira as ações disponíveis de acordo com o período eleitoral:</p>

                <?php if (!$pode_inscrever && !$pode_votar && !$pode_acompanhar): ?>
                    <div class="callout warning" style="margin-top: 20px;">
                        <div class="content">
                            <span>⏳ <strong>Nenhuma eleição ativa no momento</strong> para seu curso e semestre. Aguarde a abertura de novos editais.</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="button-group">
                <!-- Botão Inscrever -->
                <?php if ($pode_inscrever): ?>
                    <a href="../../pages/user/inscricao.php" class="button primary">
                        <i class="fas fa-user-plus"></i> QUERO ME INSCREVER
                    </a>
                <?php else: ?>
                    <button class="button primary disabled" disabled title="Período de inscrições encerrado">
                        <i class="fas fa-user-plus"></i> QUERO ME INSCREVER
                    </button>
                <?php endif; ?>

                <!-- Botão Votar -->
                <?php if ($pode_votar): ?>
                    <a href="../../pages/user/votacao.php" class="button primary">
                        <i class="fas fa-vote-yea"></i> QUERO VOTAR
                    </a>
                <?php else: ?>
                    <button class="button primary disabled" disabled title="Período de votação não iniciado ou encerrado">
                        <i class="fas fa-vote-yea"></i> QUERO VOTAR
                    </button>
                <?php endif; ?>

                <!-- Botão Acompanhar -->
                <?php if ($pode_acompanhar): ?>
                    <a href="../../pages/user/acompanhar_inscricao.php" class="button primary">
                        <i class="fas fa-clipboard-check"></i> ACOMPANHAR INSCRIÇÃO
                    </a>
                <?php else: ?>
                    <button class="button primary disabled" disabled title="Você não possui inscrição cadastrada">
                        <i class="fas fa-clipboard-check"></i> ACOMPANHAR INSCRIÇÃO
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="site">
        <div class="content">
            <img src="../../assets/images/logo-governo-do-estado-sp.png" alt="Logo Governo SP" class="logo-governo">

            <a href="../../assets/guest/sobre.php" class="btn-about">SOBRE O SISTEMA</a>

            <p>Sistema Integrado de Votação - FATEC/CPS</p>
            <p>Versão 0.1 (11/06/2025)</p>
        </div>
    </footer>
</body>

</html>
