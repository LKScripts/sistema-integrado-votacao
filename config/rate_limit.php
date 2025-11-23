<?php
/**
 * Sistema de Rate Limiting para Login
 *
 * Previne ataques de força bruta limitando tentativas de login.
 * Configuração atual: 5 tentativas a cada 15 minutos por EMAIL
 *
 * Importante: O bloqueio é feito apenas por email, não por IP.
 * Isso permite que diferentes usuários na mesma rede (ex: laboratório)
 * possam tentar login independentemente.
 *
 * Funções:
 * - registrarTentativaLogin(): Registra tentativa (sucesso ou falha)
 * - verificarBloqueio(): Verifica se email está bloqueado
 * - limparTentativas(): Remove tentativas após login bem-sucedido
 */

require_once __DIR__ . '/conexao.php';

// Configurações
define('MAX_TENTATIVAS', 5);           // Número máximo de tentativas
define('JANELA_TEMPO_MINUTOS', 15);    // Período de análise em minutos

/**
 * Registra uma tentativa de login no banco de dados
 *
 * @param string $email Email utilizado na tentativa
 * @param string $ip_origem IP do usuário
 * @param bool $sucesso Se a tentativa foi bem-sucedida
 * @return bool True se registrou com sucesso
 */
function registrarTentativaLogin($email, $ip_origem, $sucesso = false) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            INSERT INTO LOGIN_TENTATIVAS (email, ip_origem, sucesso)
            VALUES (?, ?, ?)
        ");

        return $stmt->execute([$email, $ip_origem, $sucesso ? 1 : 0]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar tentativa de login: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se um email/IP está bloqueado por excesso de tentativas
 *
 * @param string $email Email a verificar
 * @param string $ip_origem IP a verificar
 * @return array ['bloqueado' => bool, 'tentativas' => int, 'tempo_restante' => int]
 */
function verificarBloqueio($email, $ip_origem) {
    global $conn;

    try {
        // Conta tentativas FALHAS nos últimos X minutos (apenas por EMAIL)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as tentativas,
                   MIN(data_tentativa) as primeira_tentativa
            FROM LOGIN_TENTATIVAS
            WHERE email = ?
              AND sucesso = 0
              AND data_tentativa >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");

        $stmt->execute([$email, JANELA_TEMPO_MINUTOS]);
        $resultado = $stmt->fetch();

        $tentativas = intval($resultado['tentativas']);
        $bloqueado = ($tentativas >= MAX_TENTATIVAS);

        // Calcular tempo restante de bloqueio (em segundos)
        $tempo_restante = 0;
        if ($bloqueado && $resultado['primeira_tentativa']) {
            $primeira = strtotime($resultado['primeira_tentativa']);
            $expira = $primeira + (JANELA_TEMPO_MINUTOS * 60);
            $agora = time();
            $tempo_restante = max(0, $expira - $agora);
        }

        return [
            'bloqueado' => $bloqueado,
            'tentativas' => $tentativas,
            'tempo_restante' => $tempo_restante
        ];

    } catch (PDOException $e) {
        error_log("Erro ao verificar bloqueio: " . $e->getMessage());
        // Em caso de erro, não bloquear (fail-open para não travar sistema)
        return [
            'bloqueado' => false,
            'tentativas' => 0,
            'tempo_restante' => 0
        ];
    }
}

/**
 * Limpa todas as tentativas de login de um email específico
 * Chamado após login bem-sucedido para resetar contador
 *
 * @param string $email Email do usuário
 * @param string $ip_origem IP do usuário (mantido para compatibilidade, mas não usado)
 * @return bool True se limpou com sucesso
 */
function limparTentativas($email, $ip_origem) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            DELETE FROM LOGIN_TENTATIVAS
            WHERE email = ?
        ");

        return $stmt->execute([$email]);
    } catch (PDOException $e) {
        error_log("Erro ao limpar tentativas: " . $e->getMessage());
        return false;
    }
}

/**
 * Formata tempo restante para exibição amigável
 *
 * @param int $segundos Segundos restantes
 * @return string Texto formatado (ex: "5 minutos", "30 segundos")
 */
function formatarTempoRestante($segundos) {
    if ($segundos <= 0) {
        return "alguns segundos";
    }

    $minutos = floor($segundos / 60);
    $segundos_resto = $segundos % 60;

    if ($minutos > 0) {
        return $minutos . " minuto" . ($minutos > 1 ? "s" : "");
    } else {
        return $segundos_resto . " segundo" . ($segundos_resto > 1 ? "s" : "");
    }
}
