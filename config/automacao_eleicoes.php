<?php

require_once __DIR__ . '/conexao.php';

// =====================================================
// CONFIGURAÇÕES DE CACHE
// =====================================================
const CACHE_DURACAO = 300; // 5 minutos (em segundos)
const CACHE_FILE_PREFIX = __DIR__ . '/../storage/cache/eleicao_status_';

/**
 * Cria diretório de cache se não existir
 */
function garantirDiretorioCache() {
    $cacheDir = __DIR__ . '/../storage/cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
}

/**
 * Obter cache de status de eleições
 *
 * @param string $chave Chave única do cache
 * @return mixed|null Dados em cache ou null se expirado
 */
function obterCache($chave) {
    garantirDiretorioCache();
    $arquivo = CACHE_FILE_PREFIX . md5($chave) . '.json';

    if (!file_exists($arquivo)) {
        return null;
    }

    $dados = json_decode(file_get_contents($arquivo), true);

    // Verificar se expirou
    if (!$dados || (time() - $dados['timestamp']) > CACHE_DURACAO) {
        @unlink($arquivo);
        return null;
    }

    return $dados['valor'];
}

/**
 * Salvar dados no cache
 *
 * @param string $chave Chave única do cache
 * @param mixed $valor Valor a ser cacheado
 */
function salvarCache($chave, $valor) {
    garantirDiretorioCache();
    $arquivo = CACHE_FILE_PREFIX . md5($chave) . '.json';

    $dados = [
        'timestamp' => time(),
        'valor' => $valor
    ];

    file_put_contents($arquivo, json_encode($dados));
}

/**
 * Limpar todo o cache de eleições
 */
function limparCacheEleicoes() {
    $cacheDir = __DIR__ . '/../storage/cache';
    if (is_dir($cacheDir)) {
        $arquivos = glob($cacheDir . '/eleicao_status_*.json');
        foreach ($arquivos as $arquivo) {
            @unlink($arquivo);
        }
    }
}

/**
 * Atualiza status das eleições baseado na data/hora atual
 * OTIMIZADO: Só atualiza se houver eleições que precisam mudar
 *
 * @param bool $forcar Forçar atualização ignorando cache
 * @return array Estatísticas de atualizações realizadas
 */
function atualizarStatusEleicoes($forcar = false) {
    global $conn;

    $stats = [
        'para_votacao' => 0,
        'para_finalizacao' => 0,
        'finalizadas' => 0,
        'cache_usado' => false
    ];

    // Verificar cache se não forçado
    if (!$forcar) {
        $cache = obterCache('ultima_atualizacao_status');
        if ($cache !== null) {
            $stats['cache_usado'] = true;
            return $cache;
        }
    }

    try {
        // Verificar SE há eleições para atualizar ANTES de fazer UPDATE
        $stmt = $conn->query("
            SELECT
                (SELECT COUNT(*) FROM ELEICAO
                 WHERE status = 'candidatura_aberta'
                   AND NOW() >= data_inicio_votacao
                   AND NOW() < data_fim_votacao) AS para_votacao,
                (SELECT COUNT(*) FROM ELEICAO
                 WHERE status = 'votacao_aberta'
                   AND NOW() >= data_fim_votacao) AS para_finalizacao
        ");
        $verificacao = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se não há nada para atualizar, retornar imediatamente
        if ($verificacao['para_votacao'] == 0 && $verificacao['para_finalizacao'] == 0) {
            salvarCache('ultima_atualizacao_status', $stats);
            return $stats;
        }

        // Usar transação para garantir atomicidade
        $conn->beginTransaction();

        // Atualizar para 'votacao_aberta' (se necessário)
        if ($verificacao['para_votacao'] > 0) {
            $stmt = $conn->prepare("
                UPDATE ELEICAO
                SET status = 'votacao_aberta'
                WHERE status = 'candidatura_aberta'
                  AND NOW() >= data_inicio_votacao
                  AND NOW() < data_fim_votacao
            ");
            $stmt->execute();
            $stats['para_votacao'] = $stmt->rowCount();
        }

        // Marcar para finalização (se necessário)
        if ($verificacao['para_finalizacao'] > 0) {
            $stmt = $conn->prepare("
                UPDATE ELEICAO
                SET status = 'aguardando_finalizacao'
                WHERE status = 'votacao_aberta'
                  AND NOW() >= data_fim_votacao
            ");
            $stmt->execute();
            $stats['para_finalizacao'] = $stmt->rowCount();

            // Finalizar automaticamente se houver
            if ($stats['para_finalizacao'] > 0) {
                $stats['finalizadas'] = finalizarEleicoesAutomaticamente();
            }
        }

        $conn->commit();

        // Limpar cache se houve atualizações
        if ($stats['para_votacao'] > 0 || $stats['para_finalizacao'] > 0) {
            limparCacheEleicoes();
        }

        // Salvar no cache
        salvarCache('ultima_atualizacao_status', $stats);

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
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
        // Buscar eleições para finalizar
        $stmt = $conn->query("
            SELECT id_eleicao
            FROM ELEICAO
            WHERE status = 'aguardando_finalizacao'
            LIMIT 10
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
 * Busca eleição ativa para um curso/semestre
 *
 * @param string $curso Nome do curso
 * @param int $semestre Número do semestre
 * @param string $tipo 'votacao' ou 'candidatura'
 * @return array|null Dados da eleição ou null se não encontrada
 */
function buscarEleicaoAtivaComVerificacao($curso, $semestre, $tipo = 'votacao') {
    global $conn;

    // Tentar obter do cache primeiro
    $chave_cache = "eleicao_ativa_{$curso}_{$semestre}_{$tipo}";
    $cache = obterCache($chave_cache);

    if ($cache !== null) {
        return $cache;
    }

    // Apenas verificar se há eleições que PODEM estar desatualizadas
    $pode_estar_desatualizado = verificarSeNecessitaAtualizacao();

    if ($pode_estar_desatualizado) {
        atualizarStatusEleicoes();
    }

    $status = ($tipo === 'votacao') ? 'votacao_aberta' : 'candidatura_aberta';

    try {
        //Query usa índice composto idx_busca_eleicao_ativa
        $stmt = $conn->prepare("
            SELECT *
            FROM ELEICAO
            WHERE curso = ?
              AND semestre = ?
              AND status = ?
            LIMIT 1
        ");
        $stmt->execute([$curso, $semestre, $status]);

        $resultado = $stmt->fetch();

        // Salvar no cache (mesmo se null)
        salvarCache($chave_cache, $resultado ?: false);

        return $resultado ?: null;

    } catch (PDOException $e) {
        error_log("Erro ao buscar eleição ativa: " . $e->getMessage());
        return null;
    }
}

/**
 * Verifica se há eleições que podem estar com status desatualizado
 *
 * @return bool True se pode haver eleições desatualizadas
 */
function verificarSeNecessitaAtualizacao() {
    global $conn;

    // Verificar cache primeiro
    $cache = obterCache('necessita_atualizacao');
    if ($cache !== null) {
        return $cache;
    }

    try {
        // Query verifica se há eleições em transição
        $stmt = $conn->query("
            SELECT EXISTS (
                SELECT 1 FROM ELEICAO
                WHERE (
                    (status = 'candidatura_aberta' AND NOW() >= data_inicio_votacao)
                    OR (status = 'votacao_aberta' AND NOW() >= data_fim_votacao)
                )
                LIMIT 1
            ) AS necessita
        ");

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $necessita = (bool) $resultado['necessita'];

        // Cache por 2 minutos
        salvarCache('necessita_atualizacao', $necessita);

        return $necessita;

    } catch (PDOException $e) {
        error_log("Erro ao verificar necessidade de atualização: " . $e->getMessage());
        return true; // Em caso de erro, assumir que precisa atualizar
    }
}

/**
 * Verifica se eleição está em período válido para votação
 * Sem atualização automática, apenas verificação
 *
 * @param int $id_eleicao ID da eleição
 * @return array ['valido' => bool, 'periodo' => string, 'mensagem' => string]
 */
function verificarPeriodoVotacao($id_eleicao) {
    global $conn;

    // Tentar cache primeiro
    $chave_cache = "periodo_votacao_{$id_eleicao}";
    $cache = obterCache($chave_cache);

    if ($cache !== null) {
        return $cache;
    }

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
            $resultado = [
                'valido' => false,
                'periodo' => 'nao_encontrada',
                'mensagem' => 'Eleição não encontrada'
            ];
            salvarCache($chave_cache, $resultado);
            return $resultado;
        }

        $agora = strtotime($eleicao['agora']);
        $inicio = strtotime($eleicao['data_inicio_votacao']);
        $fim = strtotime($eleicao['data_fim_votacao']);

        // Determinar período
        if ($agora < $inicio) {
            $resultado = [
                'valido' => false,
                'periodo' => 'nao_iniciada',
                'mensagem' => 'A votação ainda não começou. Início: ' . date('d/m/Y H:i', $inicio)
            ];
        } elseif ($agora >= $inicio && $agora < $fim) {
            $resultado = [
                'valido' => ($eleicao['status'] === 'votacao_aberta'),
                'periodo' => 'votacao',
                'mensagem' => 'Votação aberta até ' . date('d/m/Y H:i', $fim)
            ];
        } else {
            $resultado = [
                'valido' => false,
                'periodo' => 'encerrada',
                'mensagem' => 'A votação foi encerrada em ' . date('d/m/Y H:i', $fim)
            ];
        }

        // Cache por 1 minuto (período pode mudar)
        salvarCache($chave_cache, $resultado);
        return $resultado;

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
 * Verifica se eleição está em período válido para candidatura com cache
 *
 * @param int $id_eleicao ID da eleição
 * @return array ['valido' => bool, 'periodo' => string, 'mensagem' => string]
 */
function verificarPeriodoCandidatura($id_eleicao) {
    global $conn;

    // Tentar cache primeiro
    $chave_cache = "periodo_candidatura_{$id_eleicao}";
    $cache = obterCache($chave_cache);

    if ($cache !== null) {
        return $cache;
    }

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
            $resultado = [
                'valido' => false,
                'periodo' => 'nao_encontrada',
                'mensagem' => 'Eleição não encontrada'
            ];
            salvarCache($chave_cache, $resultado);
            return $resultado;
        }

        $agora = strtotime($eleicao['agora']);
        $inicio = strtotime($eleicao['data_inicio_candidatura']);
        $fim = strtotime($eleicao['data_fim_candidatura']);

        if ($agora < $inicio) {
            $resultado = [
                'valido' => false,
                'periodo' => 'nao_iniciada',
                'mensagem' => 'O período de inscrições ainda não começou. Início: ' . date('d/m/Y H:i', $inicio)
            ];
        } elseif ($agora >= $inicio && $agora < $fim) {
            $resultado = [
                'valido' => ($eleicao['status'] === 'candidatura_aberta'),
                'periodo' => 'candidatura',
                'mensagem' => 'Inscrições abertas até ' . date('d/m/Y H:i', $fim)
            ];
        } else {
            $resultado = [
                'valido' => false,
                'periodo' => 'encerrada',
                'mensagem' => 'O período de inscrições foi encerrado em ' . date('d/m/Y H:i', $fim)
            ];
        }

        // Cache por 1 minuto
        salvarCache($chave_cache, $resultado);
        return $resultado;

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
 * Força atualização de status e limpa cache
 * Use apenas em ações administrativas críticas
 */
function forcarAtualizacaoEleicoes() {
    limparCacheEleicoes();
    return atualizarStatusEleicoes(true);
}


//Função de manuntenção - limpar cache antigo

function limparCacheExpirado() {
    garantirDiretorioCache();
    $cacheDir = __DIR__ . '/../storage/cache';
    $arquivos = glob($cacheDir . '/eleicao_status_*.json');
    $agora = time();
    $removidos = 0;

    foreach ($arquivos as $arquivo) {
        $dados = json_decode(file_get_contents($arquivo), true);
        if (!$dados || ($agora - $dados['timestamp']) > CACHE_DURACAO) {
            @unlink($arquivo);
            $removidos++;
        }
    }

    return $removidos;
}

// Executar limpeza automática uma vez por dia
$arquivo_marca = __DIR__ . '/../storage/cache/.ultima_limpeza';
if (!file_exists($arquivo_marca) || (time() - filemtime($arquivo_marca)) > 86400) {
    limparCacheExpirado();
    touch($arquivo_marca);
}
