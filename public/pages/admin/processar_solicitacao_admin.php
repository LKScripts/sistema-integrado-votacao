<?php
// pages/admin/processar_solicitacao_admin.php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/helpers.php';

verificarAdmin();

// Validar requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$usuario = obterUsuarioLogado();
$id_admin = $usuario['id'];

$id_solicitacao = filter_input(INPUT_POST, 'id_solicitacao', FILTER_VALIDATE_INT);
$acao = trim($_POST['acao'] ?? '');
$observacoes = trim($_POST['observacoes'] ?? '');

// Validações básicas
if (!$id_solicitacao || !in_array($acao, ['aprovar', 'recusar'])) {
    $_SESSION['erro'] = 'Dados inválidos na solicitação.';
    header('Location: index.php');
    exit;
}

try {
    // Buscar solicitação
    $stmt_sol = $conn->prepare("
        SELECT
            s.*,
            a.nome_completo,
            a.ra,
            a.curso AS curso_atual_db,
            a.semestre AS semestre_atual_db
        FROM solicitacao_mudanca s
        INNER JOIN aluno a ON s.id_aluno = a.id_aluno
        WHERE s.id_solicitacao = ?
        AND s.status = 'pendente'
    ");
    $stmt_sol->execute([$id_solicitacao]);
    $solicitacao = $stmt_sol->fetch();

    if (!$solicitacao) {
        $_SESSION['erro'] = 'Solicitação não encontrada ou já foi processada.';
        header('Location: index.php');
        exit;
    }

    $id_aluno = $solicitacao['id_aluno'];
    $nome_aluno = $solicitacao['nome_completo'];
    $ra_aluno = $solicitacao['ra'];

    // === PROCESSAR RECUSA ===
    if ($acao === 'recusar') {
        $stmt_recusar = $conn->prepare("
            UPDATE solicitacao_mudanca
            SET status = 'recusado',
                data_resposta = NOW(),
                id_admin_responsavel = ?,
                motivo_recusa = ?,
                observacoes_admin = ?
            WHERE id_solicitacao = ?
        ");
        $stmt_recusar->execute([
            $id_admin,
            $observacoes ?: 'Não especificado',
            $observacoes,
            $id_solicitacao
        ]);

        // Registrar auditoria
        registrarAuditoria(
            $conn,
            $id_admin,
            'SOLICITACAO_MUDANCA',
            'UPDATE',
            "Recusou solicitação de mudança #$id_solicitacao do aluno $nome_aluno (RA: $ra_aluno)",
            null,
            null,
            json_encode([
                'status_anterior' => 'pendente',
                'tipo_mudanca' => $solicitacao['tipo_mudanca'],
                'curso_solicitado' => $solicitacao['curso_novo'],
                'semestre_solicitado' => $solicitacao['semestre_novo']
            ]),
            json_encode([
                'status_novo' => 'recusado',
                'motivo_recusa' => $observacoes ?: 'Não especificado'
            ])
        );

        $_SESSION['sucesso'] = 'Solicitação recusada com sucesso.';
        header('Location: index.php');
        exit;
    }

    // === PROCESSAR APROVAÇÃO ===
    if ($acao === 'aprovar') {
        // VALIDAÇÃO 1: Verificar se aluno tem voto em eleição ativa
        $stmt_voto_ativo = $conn->prepare("
            SELECT COUNT(*) as total
            FROM voto v
            INNER JOIN eleicao e ON v.id_eleicao = e.id_eleicao
            WHERE v.id_aluno = ?
            AND e.status != 'encerrada'
        ");
        $stmt_voto_ativo->execute([$id_aluno]);
        $tem_voto_ativo = $stmt_voto_ativo->fetchColumn() > 0;

        if ($tem_voto_ativo) {
            $_SESSION['erro'] = '⚠️ BLOQUEADO: O aluno possui voto(s) registrado(s) em eleição(ões) ativa(s). Não é possível alterar curso/semestre até que todas as eleições sejam encerradas.';
            header('Location: index.php');
            exit;
        }

        // VALIDAÇÃO 2: Verificar se aluno é candidato aprovado em eleição ativa
        $stmt_candidatura_ativa = $conn->prepare("
            SELECT COUNT(*) as total
            FROM candidatura c
            INNER JOIN eleicao e ON c.id_eleicao = e.id_eleicao
            WHERE c.id_aluno = ?
            AND c.status_validacao = 'aprovado'
            AND e.status != 'encerrada'
        ");
        $stmt_candidatura_ativa->execute([$id_aluno]);
        $tem_candidatura_ativa = $stmt_candidatura_ativa->fetchColumn() > 0;

        if ($tem_candidatura_ativa) {
            $_SESSION['erro'] = '⚠️ BLOQUEADO: O aluno possui candidatura APROVADA em eleição ativa. Não é possível alterar curso/semestre até que a eleição seja encerrada.';
            header('Location: index.php');
            exit;
        }

        // VALIDAÇÃO 3: Avisar sobre candidaturas pendentes (não bloqueia, apenas alerta)
        $stmt_candidatura_pendente = $conn->prepare("
            SELECT COUNT(*) as total
            FROM candidatura c
            INNER JOIN eleicao e ON c.id_eleicao = e.id_eleicao
            WHERE c.id_aluno = ?
            AND c.status_validacao = 'pendente'
            AND e.status != 'encerrada'
        ");
        $stmt_candidatura_pendente->execute([$id_aluno]);
        $tem_candidatura_pendente = $stmt_candidatura_pendente->fetchColumn() > 0;

        // Preparar dados para atualização
        $curso_novo = $solicitacao['curso_novo'];
        $semestre_novo = $solicitacao['semestre_novo'];

        // Se tipo é 'curso', manter semestre atual
        if ($solicitacao['tipo_mudanca'] === 'curso') {
            $semestre_novo = $solicitacao['semestre_atual_db'];
        }

        // Se tipo é 'semestre', manter curso atual
        if ($solicitacao['tipo_mudanca'] === 'semestre') {
            $curso_novo = $solicitacao['curso_atual_db'];
        }

        // Iniciar transação
        $conn->beginTransaction();

        try {
            // Atualizar dados do aluno
            $stmt_update = $conn->prepare("
                UPDATE aluno
                SET curso = ?,
                    semestre = ?
                WHERE id_aluno = ?
            ");
            $stmt_update->execute([$curso_novo, $semestre_novo, $id_aluno]);

            // Atualizar status da solicitação
            $stmt_aprovar = $conn->prepare("
                UPDATE solicitacao_mudanca
                SET status = 'aprovado',
                    data_resposta = NOW(),
                    id_admin_responsavel = ?,
                    observacoes_admin = ?
                WHERE id_solicitacao = ?
            ");
            $stmt_aprovar->execute([
                $id_admin,
                $observacoes ?: 'Aprovado',
                $id_solicitacao
            ]);

            // Registrar auditoria
            registrarAuditoria(
                $conn,
                $id_admin,
                'ALUNO',
                'UPDATE',
                "Aprovou mudança de dados do aluno $nome_aluno (RA: $ra_aluno) via solicitação #$id_solicitacao" .
                ($tem_candidatura_pendente ? ' [ALERTA: Aluno tinha candidatura pendente]' : ''),
                null,
                null,
                json_encode([
                    'curso_anterior' => $solicitacao['curso_atual'],
                    'semestre_anterior' => $solicitacao['semestre_atual'],
                    'id_solicitacao' => $id_solicitacao
                ]),
                json_encode([
                    'curso_novo' => $curso_novo,
                    'semestre_novo' => $semestre_novo,
                    'tipo_mudanca' => $solicitacao['tipo_mudanca'],
                    'observacoes_admin' => $observacoes,
                    'tinha_candidatura_pendente' => $tem_candidatura_pendente
                ])
            );

            // Commit da transação
            $conn->commit();

            $mensagem_sucesso = 'Solicitação aprovada e dados atualizados com sucesso!';
            if ($tem_candidatura_pendente) {
                $mensagem_sucesso .= '<br><strong>Atenção:</strong> Este aluno possuía candidatura pendente. Verifique se ainda é elegível para a eleição.';
            }

            $_SESSION['sucesso'] = $mensagem_sucesso;

        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Erro ao aprovar solicitação: " . $e->getMessage());
            $_SESSION['erro'] = 'Erro ao processar aprovação. Tente novamente.';
        }
    }

} catch (PDOException $e) {
    error_log("Erro ao processar solicitação: " . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao processar solicitação. Tente novamente.';
}

header('Location: index.php');
exit;
