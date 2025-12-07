<?php
/**
 * Script para processar notificações pendentes de apuração
 *
 * Este script deve ser executado via cron job a cada 10-15 minutos
 * Exemplo de cron: */15 * * * * php /path/to/processar_notificacoes.php
 *
 * Ou pode ser executado manualmente:
 * php processar_notificacoes.php
 */

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/email.php';

// Evitar execução via browser (só via CLI ou cron)
if (php_sapi_name() !== 'cli') {
    // Permitir também se executado diretamente no servidor via HTTP (com proteção)
    // Recomendação: comentar este bloco em produção e usar apenas CLI
    if (!isset($_GET['token']) || $_GET['token'] !== md5('notificacao_cron_' . date('Y-m-d-H'))) {
        die('Acesso negado. Execute via CLI: php processar_notificacoes.php');
    }
}

echo "===========================================\n";
echo "Processador de Notificações de Apuração\n";
echo "Iniciado em: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

try {
    // Buscar eleições que precisam de notificação
    // Agrupadas por lote_criacao (se for NULL, cada uma é individual)
    $sql = "
        SELECT
            e.id_eleicao,
            e.curso,
            e.semestre,
            e.data_fim_votacao,
            e.lote_criacao,
            e.criado_por,
            a.nome_completo AS admin_nome,
            a.email_corporativo AS admin_email
        FROM eleicao e
        INNER JOIN administrador a ON e.criado_por = a.id_admin
        WHERE e.status = 'aguardando_finalizacao'
          AND e.notificacao_apuracao_enviada = 0
        ORDER BY e.lote_criacao, e.id_eleicao
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $eleicoes_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($eleicoes_pendentes)) {
        echo "✓ Nenhuma notificação pendente no momento.\n";
        exit(0);
    }

    echo "Encontradas " . count($eleicoes_pendentes) . " eleição(ões) pendente(s).\n\n";

    // Agrupar eleições por lote_criacao e admin
    $grupos = [];
    foreach ($eleicoes_pendentes as $eleicao) {
        $lote = $eleicao['lote_criacao'] ?? 'individual_' . $eleicao['id_eleicao'];
        $admin_id = $eleicao['criado_por'];

        $chave = $lote . '_' . $admin_id;

        if (!isset($grupos[$chave])) {
            $grupos[$chave] = [
                'admin_id' => $admin_id,
                'admin_nome' => $eleicao['admin_nome'],
                'admin_email' => $eleicao['admin_email'],
                'lote' => $eleicao['lote_criacao'],
                'eleicoes' => []
            ];
        }

        $grupos[$chave]['eleicoes'][] = $eleicao;
    }

    echo "Agrupadas em " . count($grupos) . " notificação(ões) a enviar.\n\n";

    // Processar cada grupo
    $emailService = new EmailService();
    $notificacoes_enviadas = 0;
    $notificacoes_falhas = 0;

    foreach ($grupos as $chave => $grupo) {
        $qtd_eleicoes = count($grupo['eleicoes']);
        $tipo = ($grupo['lote'] !== null && $qtd_eleicoes > 1) ? 'lote' : 'individual';

        echo "-------------------------------------------\n";
        echo "Enviando notificação: {$tipo}\n";
        echo "Admin: {$grupo['admin_nome']} <{$grupo['admin_email']}>\n";
        echo "Eleições: {$qtd_eleicoes}\n";

        $ids_eleicoes = array_column($grupo['eleicoes'], 'id_eleicao');
        echo "IDs: " . implode(', ', $ids_eleicoes) . "\n";

        // Tentar enviar e-mail
        $enviado = $emailService->enviarNotificacaoApuracao(
            $grupo['admin_email'],
            $grupo['admin_nome'],
            $grupo['eleicoes'],
            $tipo === 'lote' ? 'lote' : 'individual'
        );

        if ($enviado) {
            echo "✓ E-mail enviado com sucesso!\n";
            $notificacoes_enviadas++;

            // Marcar eleições como notificadas
            $placeholders = implode(',', array_fill(0, count($ids_eleicoes), '?'));
            $update_sql = "
                UPDATE eleicao
                SET notificacao_apuracao_enviada = 1
                WHERE id_eleicao IN ($placeholders)
            ";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute($ids_eleicoes);

            // Registrar no log de notificações
            $log_sql = "
                INSERT INTO notificacoes_email
                (tipo, id_admin, email_destino, assunto, eleicoes_ids, status_envio)
                VALUES (?, ?, ?, ?, ?, 'enviado')
            ";
            $log_stmt = $conn->prepare($log_sql);
            $assunto = $tipo === 'lote'
                ? "Apuração Pendente - {$qtd_eleicoes} Eleições Aguardando"
                : "Apuração Pendente - {$grupo['eleicoes'][0]['curso']} {$grupo['eleicoes'][0]['semestre']}º";

            $log_stmt->execute([
                $tipo === 'lote' ? 'apuracao_lote' : 'apuracao_individual',
                $grupo['admin_id'],
                $grupo['admin_email'],
                $assunto,
                json_encode($ids_eleicoes)
            ]);

        } else {
            echo "✗ Falha ao enviar e-mail!\n";
            $notificacoes_falhas++;

            // Registrar falha no log
            $log_sql = "
                INSERT INTO notificacoes_email
                (tipo, id_admin, email_destino, assunto, eleicoes_ids, status_envio, mensagem_erro)
                VALUES (?, ?, ?, ?, ?, 'falhou', ?)
            ";
            $log_stmt = $conn->prepare($log_sql);
            $assunto = $tipo === 'lote'
                ? "Apuração Pendente - {$qtd_eleicoes} Eleições Aguardando"
                : "Apuração Pendente - {$grupo['eleicoes'][0]['curso']} {$grupo['eleicoes'][0]['semestre']}º";

            $log_stmt->execute([
                $tipo === 'lote' ? 'apuracao_lote' : 'apuracao_individual',
                $grupo['admin_id'],
                $grupo['admin_email'],
                $assunto,
                json_encode($ids_eleicoes),
                'Erro ao enviar via PHPMailer - verifique logs do servidor'
            ]);
        }

        echo "\n";
    }

    echo "===========================================\n";
    echo "Processamento concluído!\n";
    echo "Enviadas: {$notificacoes_enviadas}\n";
    echo "Falhas: {$notificacoes_falhas}\n";
    echo "Finalizado em: " . date('Y-m-d H:i:s') . "\n";
    echo "===========================================\n";

} catch (PDOException $e) {
    echo "✗ ERRO DE BANCO DE DADOS: " . $e->getMessage() . "\n";
    error_log("Erro ao processar notificações: " . $e->getMessage());
    exit(1);
} catch (Exception $e) {
    echo "✗ ERRO GERAL: " . $e->getMessage() . "\n";
    error_log("Erro ao processar notificações: " . $e->getMessage());
    exit(1);
}

exit(0);
