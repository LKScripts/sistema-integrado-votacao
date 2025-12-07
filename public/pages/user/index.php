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

// Verificar se existe eleição encerrada para este curso/semestre
$eleicao_encerrada = null;
$resultado = null;
$stmt_encerrada = $conn->prepare("
    SELECT
        e.id_eleicao,
        e.curso,
        e.semestre,
        e.data_fim_votacao,
        r.id_resultado,
        r.votos_representante,
        r.votos_suplente,
        r.total_votantes,
        r.total_aptos,
        r.percentual_participacao,
        rep_cand.proposta as proposta_representante,
        rep_cand.foto_candidato as foto_representante,
        rep_aluno.nome_completo as nome_representante,
        rep_aluno.ra as ra_representante,
        sup_cand.proposta as proposta_suplente,
        sup_cand.foto_candidato as foto_suplente,
        sup_aluno.nome_completo as nome_suplente,
        sup_aluno.ra as ra_suplente
    FROM ELEICAO e
    LEFT JOIN resultado r ON e.id_eleicao = r.id_eleicao
    LEFT JOIN CANDIDATURA rep_cand ON r.id_representante = rep_cand.id_candidatura
    LEFT JOIN ALUNO rep_aluno ON rep_cand.id_aluno = rep_aluno.id_aluno
    LEFT JOIN CANDIDATURA sup_cand ON r.id_suplente = sup_cand.id_candidatura
    LEFT JOIN ALUNO sup_aluno ON sup_cand.id_aluno = sup_aluno.id_aluno
    WHERE e.curso = ?
    AND e.semestre = ?
    AND e.status = 'encerrada'
    ORDER BY e.data_fim_votacao DESC
    LIMIT 1
");
$stmt_encerrada->execute([$curso, $semestre]);
$eleicao_encerrada = $stmt_encerrada->fetch();

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
$pode_inscrever = !empty($eleicao_candidatura) && !$tem_candidatura; // Desabilita se já tem candidatura
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
    <link rel="stylesheet" href="../../assets/styles/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include 'components/header.php'; ?>

    <main class="user-home">
        <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="callout success" style="margin: 20px auto; max-width: 1200px;">
                <div class="content">
                    <span><?= $_SESSION['sucesso'] ?></span>
                </div>
            </div>
            <?php unset($_SESSION['sucesso']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="callout error" style="margin: 20px auto; max-width: 1200px;">
                <div class="content">
                    <span><?= $_SESSION['erro'] ?></span>
                </div>
            </div>
            <?php unset($_SESSION['erro']); ?>
        <?php endif; ?>

        <div class="card-wrapper">
            <div class="card">
                <h1>AÇÕES DISPONÍVEIS</h1>
                <p>Confira as ações disponíveis de acordo com o período eleitoral:</p>

                <?php if (!$pode_inscrever && !$pode_votar && !$pode_acompanhar && !$eleicao_encerrada): ?>
                    <div class="callout warning" style="margin-top: 20px;">
                        <div class="content">
                            <span><strong>Nenhuma eleição ativa no momento</strong> para seu curso e semestre. Aguarde a abertura de novos editais.</span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="button-group">
                    <!-- Botão Inscrever -->
                    <?php if ($pode_inscrever): ?>
                        <a href="../../pages/user/inscricao.php" class="button primary">
                            <i class="fas fa-user-plus"></i> QUERO ME INSCREVER
                        </a>
                    <?php else: ?>
                        <?php
                        $titulo_desabilitado = $tem_candidatura
                            ? "Você já possui uma candidatura cadastrada"
                            : "Período de inscrições encerrado";
                        ?>
                        <button class="button primary disabled" disabled title="<?= htmlspecialchars($titulo_desabilitado) ?>">
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

                <!-- Botão Solicitar Mudança de Dados -->
                <div style="margin-top: 25px; padding-top: 25px; border-top: 1px solid #e0e0e0;">
                    <button id="btnSolicitarMudanca" class="button secondary" style="width: 100%; background: #4a5568; border-color: #4a5568;">
                        <i class="fas fa-edit"></i> SOLICITAR MUDANÇA DE CURSO/SEMESTRE
                    </button>
                    <p style="text-align: center; font-size: 12px; color: #666; margin-top: 10px;">
                        Dados incorretos? Mudou de curso ou foi retido? Solicite a correção aqui.
                    </p>
                </div>

                <?php if ($eleicao_encerrada && $eleicao_encerrada['id_resultado']): ?>
                    <!-- Seção de Resultados da Eleição Encerrada -->
                    <div class="resultados-eleicao" style="margin-top: 30px;">
                        <h2 style="color: #c8102e; margin-bottom: 20px;">
                            <i class="fas fa-trophy"></i> RESULTADO DA ÚLTIMA ELEIÇÃO
                        </h2>

                        <div class="info-eleicao" style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <p style="margin: 0; color: #666;">
                                <strong>Curso:</strong> <?= htmlspecialchars($eleicao_encerrada['curso']) ?> -
                                <strong>Semestre:</strong> <?= htmlspecialchars($eleicao_encerrada['semestre']) ?>º |
                                <strong>Participação:</strong> <?= number_format($eleicao_encerrada['percentual_participacao'], 1) ?>%
                                (<?= $eleicao_encerrada['total_votantes'] ?>/<?= $eleicao_encerrada['total_aptos'] ?> votantes)
                            </p>
                        </div>

                        <div class="vencedores" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                            <!-- Representante -->
                            <?php if ($eleicao_encerrada['nome_representante']): ?>
                                <div class="vencedor representante" style="background: linear-gradient(135deg, #c8102e 0%, #8b0a1f 100%); padding: 20px; border-radius: 12px; color: white; text-align: center;">
                                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #fff;">
                                        <i class="fas fa-crown"></i> REPRESENTANTE ELEITO
                                    </div>

                                    <?php if ($eleicao_encerrada['foto_representante']): ?>
                                        <?php
                                        // Detecta se é URL externa (http/https) ou caminho local
                                        $foto_rep_src = (filter_var($eleicao_encerrada['foto_representante'], FILTER_VALIDATE_URL))
                                            ? htmlspecialchars($eleicao_encerrada['foto_representante'])
                                            : '../../storage/uploads/candidatos/' . htmlspecialchars($eleicao_encerrada['foto_representante']);
                                        ?>
                                        <img src="<?= $foto_rep_src ?>"
                                             alt="Foto do representante"
                                             style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid white; margin-bottom: 15px;">
                                    <?php else: ?>
                                        <div style="width: 100px; height: 100px; border-radius: 50%; background: rgba(255,255,255,0.2); margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; border: 3px solid white;">
                                            <i class="fas fa-user" style="font-size: 40px; color: #fff;"></i>
                                        </div>
                                    <?php endif; ?>

                                    <h3 style="font-size: 18px; margin: 10px 0 15px 0; font-weight: 700; color: #fff;">
                                        <?= htmlspecialchars($eleicao_encerrada['nome_representante']) ?>
                                    </h3>

                                    <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px; margin-top: 15px;">
                                        <div style="font-size: 28px; font-weight: 700; margin-bottom: 5px; color: #fff;">
                                            <?= $eleicao_encerrada['votos_representante'] ?> votos
                                        </div>
                                        <div style="font-size: 12px; color: #f0f0f0;">
                                            <?= number_format(($eleicao_encerrada['votos_representante'] / $eleicao_encerrada['total_votantes']) * 100, 1) ?>% dos votos
                                        </div>
                                    </div>

                                    <?php if ($eleicao_encerrada['proposta_representante']): ?>
                                        <details open style="margin-top: 15px; text-align: left;">
                                            <summary style="cursor: pointer; font-size: 13px; font-weight: 600; color: #fff;">
                                                Proposta
                                            </summary>
                                            <p style="font-size: 13px; margin-top: 10px; line-height: 1.5; color: #f0f0f0;">
                                                <?= htmlspecialchars($eleicao_encerrada['proposta_representante']) ?>
                                            </p>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Suplente -->
                            <?php if ($eleicao_encerrada['nome_suplente']): ?>
                                <div class="vencedor suplente" style="background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%); padding: 20px; border-radius: 12px; color: white; text-align: center;">
                                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #fff;">
                                        <i class="fas fa-medal"></i> SUPLENTE
                                    </div>

                                    <?php if ($eleicao_encerrada['foto_suplente']): ?>
                                        <?php
                                        // Detecta se é URL externa (http/https) ou caminho local
                                        $foto_sup_src = (filter_var($eleicao_encerrada['foto_suplente'], FILTER_VALIDATE_URL))
                                            ? htmlspecialchars($eleicao_encerrada['foto_suplente'])
                                            : '../../storage/uploads/candidatos/' . htmlspecialchars($eleicao_encerrada['foto_suplente']);
                                        ?>
                                        <img src="<?= $foto_sup_src ?>"
                                             alt="Foto do suplente"
                                             style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid white; margin-bottom: 15px;">
                                    <?php else: ?>
                                        <div style="width: 100px; height: 100px; border-radius: 50%; background: rgba(255,255,255,0.2); margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; border: 3px solid white;">
                                            <i class="fas fa-user" style="font-size: 40px; color: #fff;"></i>
                                        </div>
                                    <?php endif; ?>

                                    <h3 style="font-size: 18px; margin: 10px 0 15px 0; font-weight: 700; color: #fff;">
                                        <?= htmlspecialchars($eleicao_encerrada['nome_suplente']) ?>
                                    </h3>

                                    <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px; margin-top: 15px;">
                                        <div style="font-size: 28px; font-weight: 700; margin-bottom: 5px; color: #fff;">
                                            <?= $eleicao_encerrada['votos_suplente'] ?> votos
                                        </div>
                                        <div style="font-size: 12px; color: #f0f0f0;">
                                            <?= number_format(($eleicao_encerrada['votos_suplente'] / $eleicao_encerrada['total_votantes']) * 100, 1) ?>% dos votos
                                        </div>
                                    </div>

                                    <?php if ($eleicao_encerrada['proposta_suplente']): ?>
                                        <details open style="margin-top: 15px; text-align: left;">
                                            <summary style="cursor: pointer; font-size: 13px; font-weight: 600; color: #fff;">
                                                Proposta
                                            </summary>
                                            <p style="font-size: 13px; margin-top: 10px; line-height: 1.5; color: #f0f0f0;">
                                                <?= htmlspecialchars($eleicao_encerrada['proposta_suplente']) ?>
                                            </p>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-wrapper">
            <div class="card">
                <section class="voting">
                    <div class="voting-text">
                        <h1>AMBIENTE DE VOTAÇÃO</h1>
                        <p>
                            Esta interface exibe todos os processos eleitorais disponíveis para você com base no seu
                            curso e semestre. O sistema automatiza os prazos através de eventos de banco de dados.
                            Observe que:
                        </p>
                        <ul>
                            <li>
                                Os botões de ação são habilitados/desabilitados automaticamente conforme o período eleitoral ativo.
                            </li>
                            <li>
                                As eleições transitam automaticamente entre as fases: candidatura → votação → encerrada.
                            </li>
                            <li>
                                Os prazos são rigorosos: o sistema bloqueia o acesso automaticamente após o horário de encerramento.
                            </li>
                        </ul>
                    </div>

                    <div class="voting-illustration">
                        <img src="../../assets/images/voting-amico.svg" alt="Ilustração da votação">
                    </div>
                </section>

                <section class="security">
                    <h2>SEGURANÇA E TRANSPARÊNCIA</h2>
                    <p>
                        Sua identidade é verificada apenas no momento do acesso. O registro do voto no banco de dados
                        não contém informações que possam vinculá-lo às suas escolhas, garantindo sigilo absoluto.
                        Todas as operações administrativas são registradas em um sistema de auditoria imutável,
                        protegido por triggers de banco de dados que impedem alteração ou exclusão de logs.
                    </p>
                </section>

                <div class="alert">
                    <span>⚠️ ATENÇÃO: Verifique os prazos eleitorais para não perder seu direito de voto! O sistema automatiza as transições de fase, mas você deve estar atento aos horários definidos no edital.</span>
                </div>
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

    <!-- Modal Solicitar Mudança -->
    <div id="modalSolicitarMudanca" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Solicitar Mudança de Dados</h2>
                <button class="btn-close" type="button" onclick="document.getElementById('modalSolicitarMudanca').classList.remove('show')">&times;</button>
            </div>

            <form id="formSolicitarMudanca" method="POST" action="processar_solicitacao_mudanca.php">
                <div class="modal-body">
                <div class="info-atual" style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; color: #333; margin-bottom: 10px;">📋 Dados Atuais:</h3>
                    <p style="margin: 5px 0; font-size: 14px;">
                        <strong>Curso:</strong> <?= htmlspecialchars($curso) ?>
                    </p>
                    <p style="margin: 5px 0; font-size: 14px;">
                        <strong>Semestre:</strong> <?= htmlspecialchars($semestre) ?>º
                    </p>
                </div>

                <div class="form-group">
                    <label for="tipoMudanca">Tipo de Mudança <span style="color: red;">*</span></label>
                    <select id="tipoMudanca" name="tipo_mudanca" required>
                        <option value="">Selecione...</option>
                        <option value="curso">Apenas Curso</option>
                        <option value="semestre">Apenas Semestre</option>
                        <option value="ambos">Curso e Semestre</option>
                    </select>
                </div>

                <div class="form-group" id="grupoCurso" style="display: none;">
                    <label for="cursoNovo">Novo Curso <span style="color: red;">*</span></label>
                    <select id="cursoNovo" name="curso_novo">
                        <option value="">Selecione...</option>
                        <option value="DSM">DSM - Desenvolvimento de Software Multiplataforma</option>
                        <option value="GE">GE - Gestão Empresarial</option>
                        <option value="GPI">GPI - Gestão da Produção Industrial</option>
                    </select>
                </div>

                <div class="form-group" id="grupoSemestre" style="display: none;">
                    <label for="semestreNovo">Novo Semestre <span style="color: red;">*</span></label>
                    <select id="semestreNovo" name="semestre_novo">
                        <option value="">Selecione...</option>
                        <option value="1">1º Semestre</option>
                        <option value="2">2º Semestre</option>
                        <option value="3">3º Semestre</option>
                        <option value="4">4º Semestre</option>
                        <option value="5">5º Semestre</option>
                        <option value="6">6º Semestre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="justificativa">Justificativa <span style="color: red;">*</span></label>
                    <textarea id="justificativa" name="justificativa" rows="4" required
                              placeholder="Ex: Fui aprovado em processo de transferência interna para o curso de DSM..."></textarea>
                    <small style="color: #666; font-size: 12px;">
                        Explique o motivo da mudança (transferência, retenção, erro no cadastro, etc.)
                    </small>
                </div>

                    <div style="background: #fff3cd; padding: 12px 15px; margin: 20px 0; border-radius: 4px;">
                        <span style="font-size: 13px; color: #856404;">
                            <strong>⚠️ Atenção:</strong> Solicitações podem ser recusadas se você tiver participação em eleições ativas.
                            Aguarde a aprovação antes de participar de novos processos eleitorais.
                        </span>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="button" class="button secondary" id="btnCancelarMudanca">
                        Cancelar
                    </button>
                    <button type="submit" class="button primary">
                        <i class="fas fa-paper-plane"></i> Enviar Solicitação
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal de Solicitar Mudança
        document.addEventListener('DOMContentLoaded', function() {
            const modalMudanca = document.getElementById('modalSolicitarMudanca');
            const btnAbrirMudanca = document.getElementById('btnSolicitarMudanca');
            const btnCancelarMudanca = document.getElementById('btnCancelarMudanca');
            const formMudanca = document.getElementById('formSolicitarMudanca');
            const tipoMudanca = document.getElementById('tipoMudanca');
            const grupoCurso = document.getElementById('grupoCurso');
            const grupoSemestre = document.getElementById('grupoSemestre');
            const cursoNovo = document.getElementById('cursoNovo');
            const semestreNovo = document.getElementById('semestreNovo');

            if (!btnAbrirMudanca) {
                console.error('Botão btnSolicitarMudanca não encontrado!');
                return;
            }

            // Abrir modal
            btnAbrirMudanca.addEventListener('click', () => {
                modalMudanca.classList.add('show');
            });

        // Fechar modal - botão cancelar
        if (btnCancelarMudanca) {
            btnCancelarMudanca.addEventListener('click', () => {
                modalMudanca.classList.remove('show');
            });
        }

        // Fechar ao clicar fora
        window.addEventListener('click', (e) => {
            if (e.target === modalMudanca) {
                modalMudanca.classList.remove('show');
            }
        });

        // Mostrar/esconder campos baseado no tipo de mudança
        tipoMudanca.addEventListener('change', () => {
            const tipo = tipoMudanca.value;

            if (tipo === 'curso') {
                grupoCurso.style.display = 'block';
                grupoSemestre.style.display = 'none';
                cursoNovo.required = true;
                semestreNovo.required = false;
                semestreNovo.value = '';
            } else if (tipo === 'semestre') {
                grupoCurso.style.display = 'none';
                grupoSemestre.style.display = 'block';
                cursoNovo.required = false;
                semestreNovo.required = true;
                cursoNovo.value = '';
            } else if (tipo === 'ambos') {
                grupoCurso.style.display = 'block';
                grupoSemestre.style.display = 'block';
                cursoNovo.required = true;
                semestreNovo.required = true;
            } else {
                grupoCurso.style.display = 'none';
                grupoSemestre.style.display = 'none';
                cursoNovo.required = false;
                semestreNovo.required = false;
                cursoNovo.value = '';
                semestreNovo.value = '';
            }
        });

        // Validação ao submeter
        formMudanca.addEventListener('submit', (e) => {
            const tipo = tipoMudanca.value;

            if (!tipo) {
                e.preventDefault();
                alert('Por favor, selecione o tipo de mudança.');
                return;
            }

            if ((tipo === 'curso' || tipo === 'ambos') && !cursoNovo.value) {
                e.preventDefault();
                alert('Por favor, selecione o novo curso.');
                return;
            }

            if ((tipo === 'semestre' || tipo === 'ambos') && !semestreNovo.value) {
                e.preventDefault();
                alert('Por favor, selecione o novo semestre.');
                return;
            }
        });
        }); // Fecha DOMContentLoaded
    </script>
</body>

</html>
