<?php
/**
 * AUTOMAÇÃO DE ELEIÇÕES
 *
 * Funções para verificar e atualizar status de eleições em tempo real
 * Complementa a automação do MySQL garantindo resposta imediata
 */

require_once __DIR__ . '/conexao.php';

/**
 * Atualiza status das eleições baseado na data/hora atual
 * Deve ser chamado em páginas críticas (votação, inscrição)
 *
 * @return array Estatísticas de atualizações realizadas
 */
function atualizarStatusEleicoes() {
    global $conn;

    $stats = [
        'para_votacao' => 0,
        'para_finalizacao' => 0,
        'finalizadas' => 0
    ];

    try {
        // 1. Atualizar para 'votacao_aberta'
        $stmt = $conn->prepare("
            UPDATE ELEICAO
            SET status = 'votacao_aberta'
            WHERE status = 'candidatura_aberta'
              AND NOW() >= data_inicio_votacao
              AND NOW() < data_fim_votacao
        ");
        $stmt->execute();
        $stats['para_votacao'] = $stmt->rowCount();

        // 2. Marcar para finalização
        $stmt = $conn->prepare("
            UPDATE ELEICAO
            SET status = 'aguardando_finalizacao'
            WHERE status = 'votacao_aberta'
              AND NOW() >= data_fim_votacao
        ");
        $stmt->execute();
        $stats['para_finalizacao'] = $stmt->rowCount();

        // 3. Finalizar eleições automaticamente (se houver)
        if ($stats['para_finalizacao'] > 0) {
            $stats['finalizadas'] = finalizarEleicoesAutomaticamente();
        }

    } catch (PDOException $e) {
        error_log("Erro ao atualizar status de eleições: " . $e->getMessage());
    }

    return $stats;
}

/**
 * Finaliza eleições que estão aguardando finalização
 *
 * @return int Número de eleições finalizadas
 */
function finalizarEleicoesAutomaticamente() {
    global $conn;

    $finalizadas = 0;

    try {
        // Buscar eleições que precisam ser finalizadas
        $stmt = $conn->query("
            SELECT id_eleicao
            FROM ELEICAO
            WHERE status = 'aguardando_finalizacao'
        ");

        $eleicoes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Finalizar cada uma
        foreach ($eleicoes as $id_eleicao) {
            try {
                $stmt_finalizar = $conn->prepare("CALL sp_finalizar_eleicao(?, 1)");
                $stmt_finalizar->execute([$id_eleicao]);
                $finalizadas++;
            } catch (PDOException $e) {
                error_log("Erro ao finalizar eleição {$id_eleicao}: " . $e->getMessage());
            }
        }

    } catch (PDOException $e) {
        error_log("Erro ao buscar eleições para finalizar: " . $e->getMessage());
    }

    return $finalizadas;
}

/**
 * Verifica se eleição está em período válido para votação
 *
 * @param int $id_eleicao ID da eleição
 * @return array ['valido' => bool, 'periodo' => string, 'mensagem' => string]
 */
function verificarPeriodoVotacao($id_eleicao) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT
                status,
                data_inicio_votacao,
                data_fim_votacao,
                NOW() as agora
            FROM ELEICAO
            WHERE id_eleicao = ?
        ");
        $stmt->execute([$id_eleicao]);
        $eleicao = $stmt->fetch();

        if (!$eleicao) {
            return [
                'valido' => false,
                'periodo' => 'nao_encontrada',
                'mensagem' => 'Eleição não encontrada'
            ];
        }

        $agora = strtotime($eleicao['agora']);
        $inicio = strtotime($eleicao['data_inicio_votacao']);
        $fim = strtotime($eleicao['data_fim_votacao']);

        // Verificar período
        if ($agora < $inicio) {
            return [
                'valido' => false,
                'periodo' => 'nao_iniciada',
                'mensagem' => 'A votação ainda não começou. Início: ' . date('d/m/Y H:i', $inicio)
            ];
        } elseif ($agora >= $inicio && $agora < $fim) {
            // Período válido - mas verificar status também
            if ($eleicao['status'] !== 'votacao_aberta') {
                // Tentar atualizar status
                atualizarStatusEleicoes();

                return [
                    'valido' => true,
                    'periodo' => 'votacao',
                    'mensagem' => 'Votação aberta até ' . date('d/m/Y H:i', $fim)
                ];
            }

            return [
                'valido' => true,
                'periodo' => 'votacao',
                'mensagem' => 'Votação aberta até ' . date('d/m/Y H:i', $fim)
            ];
        } else {
            return [
                'valido' => false,
                'periodo' => 'encerrada',
                'mensagem' => 'A votação foi encerrada em ' . date('d/m/Y H:i', $fim)
            ];
        }

    } catch (PDOException $e) {
        error_log("Erro ao verificar período de votação: " . $e->getMessage());
        return [
            'valido' => false,
            'periodo' => 'erro',
            'mensagem' => 'Erro ao verificar período de votação'
        ];
    }
}

/**
 * Verifica se eleição está em período válido para candidatura
 *
 * @param int $id_eleicao ID da eleição
 * @return array ['valido' => bool, 'periodo' => string, 'mensagem' => string]
 */
function verificarPeriodoCandidatura($id_eleicao) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT
                status,
                data_inicio_candidatura,
                data_fim_candidatura,
                NOW() as agora
            FROM ELEICAO
            WHERE id_eleicao = ?
        ");
        $stmt->execute([$id_eleicao]);
        $eleicao = $stmt->fetch();

        if (!$eleicao) {
            return [
                'valido' => false,
                'periodo' => 'nao_encontrada',
                'mensagem' => 'Eleição não encontrada'
            ];
        }

        $agora = strtotime($eleicao['agora']);
        $inicio = strtotime($eleicao['data_inicio_candidatura']);
        $fim = strtotime($eleicao['data_fim_candidatura']);

        if ($agora < $inicio) {
            return [
                'valido' => false,
                'periodo' => 'nao_iniciada',
                'mensagem' => 'O período de inscrições ainda não começou. Início: ' . date('d/m/Y H:i', $inicio)
            ];
        } elseif ($agora >= $inicio && $agora < $fim) {
            if ($eleicao['status'] !== 'candidatura_aberta') {
                return [
                    'valido' => false,
                    'periodo' => 'ja_passou',
                    'mensagem' => 'O período de inscrições foi encerrado'
                ];
            }

            return [
                'valido' => true,
                'periodo' => 'candidatura',
                'mensagem' => 'Inscrições abertas até ' . date('d/m/Y H:i', $fim)
            ];
        } else {
            return [
                'valido' => false,
                'periodo' => 'encerrada',
                'mensagem' => 'O período de inscrições foi encerrado em ' . date('d/m/Y H:i', $fim)
            ];
        }

    } catch (PDOException $e) {
        error_log("Erro ao verificar período de candidatura: " . $e->getMessage());
        return [
            'valido' => false,
            'periodo' => 'erro',
            'mensagem' => 'Erro ao verificar período de inscrição'
        ];
    }
}

/**
 * Busca eleição ativa para um curso/semestre
 * Atualiza status antes de buscar
 *
 * @param string $curso Nome do curso
 * @param int $semestre Número do semestre
 * @param string $tipo 'votacao' ou 'candidatura'
 * @return array|null Dados da eleição ou null se não encontrada
 */
function buscarEleicaoAtivaComVerificacao($curso, $semestre, $tipo = 'votacao') {
    // Primeiro, atualizar status
    atualizarStatusEleicoes();

    global $conn;

    $status = ($tipo === 'votacao') ? 'votacao_aberta' : 'candidatura_aberta';

    try {
        $stmt = $conn->prepare("
            SELECT *
            FROM ELEICAO
            WHERE curso = ?
              AND semestre = ?
              AND status = ?
            LIMIT 1
        ");
        $stmt->execute([$curso, $semestre, $status]);

        return $stmt->fetch();

    } catch (PDOException $e) {
        error_log("Erro ao buscar eleição ativa: " . $e->getMessage());
        return null;
    }
}
?>
