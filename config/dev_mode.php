<?php
/**
 * Configurações de Modo de Desenvolvimento
 *
 * Permite testar o sistema sem precisar de emails institucionais reais
 */

require_once __DIR__ . '/env.php';

/**
 * Verifica se está em modo de desenvolvimento
 */
function isDevMode() {
    $mode = env('DEV_MODE', 'false');
    return $mode === 'true' || $mode === 'hybrid';
}

/**
 * Verifica se deve enviar email real (mesmo em dev mode)
 */
function shouldSendRealEmail() {
    $mode = env('DEV_MODE', 'false');
    return $mode === 'false' || $mode === 'hybrid';
}

/**
 * Valida domínio de email - versão flexível para dev mode
 *
 * @param string $email Email a validar
 * @param string $tipo Tipo de usuário ('aluno' ou 'admin')
 * @return array ['valido' => bool, 'mensagem' => string]
 */
function validarDominioEmail($email, $tipo = 'aluno') {
    // Em modo de desenvolvimento, aceita qualquer email
    if (isDevMode()) {
        return [
            'valido' => true,
            'mensagem' => '✅ [DEV MODE] Email aceito: ' . $email,
            'dev_mode' => true
        ];
    }

    // Modo produção: validação rigorosa
    if ($tipo === 'aluno') {
        if (!preg_match('/@fatec\.sp\.gov\.br$/i', $email)) {
            return [
                'valido' => false,
                'mensagem' => 'O e-mail deve ser do domínio @fatec.sp.gov.br',
                'dev_mode' => false
            ];
        }
    } elseif ($tipo === 'admin') {
        if (!preg_match('/@cps\.sp\.gov\.br$/i', $email)) {
            return [
                'valido' => false,
                'mensagem' => 'O e-mail deve ser do domínio @cps.sp.gov.br',
                'dev_mode' => false
            ];
        }
    }

    return [
        'valido' => true,
        'mensagem' => 'Email válido',
        'dev_mode' => false
    ];
}

/**
 * Gera link de confirmação para exibição em dev mode
 *
 * @param string $token Token de confirmação
 * @param string $tipo Tipo de usuário
 * @return string URL completa
 */
function gerarLinkConfirmacao($token, $tipo = 'aluno') {
    $baseUrl = env('BASE_URL', 'http://localhost/sistema-integrado-votacao');
    return $baseUrl . '/public/pages/guest/confirmar-email.php?token=' . $token;
}

/**
 * Exibe mensagem de dev mode na tela
 *
 * @param string $token Token gerado
 * @param string $email Email usado
 * @return string HTML para exibir
 */
function exibirMensagemDevMode($token, $email) {
    $link = gerarLinkConfirmacao($token);

    return '
    <div style="
        position: fixed;
        top: 20px;
        right: 20px;
        background: #fff3cd;
        border: 2px solid #ffc107;
        border-radius: 8px;
        padding: 20px;
        max-width: 500px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 9999;
        font-family: monospace;
    ">
        <h3 style="margin: 0 0 10px 0; color: #856404;">
            🔧 DEV MODE ATIVO
        </h3>
        <p style="margin: 5px 0; font-size: 14px;">
            <strong>Email:</strong> ' . htmlspecialchars($email) . '
        </p>
        <p style="margin: 5px 0; font-size: 14px;">
            <strong>Token:</strong> <code>' . htmlspecialchars($token) . '</code>
        </p>
        <p style="margin: 10px 0 5px 0; font-size: 14px;">
            <strong>Link de confirmação:</strong>
        </p>
        <a href="' . htmlspecialchars($link) . '"
           style="
               display: inline-block;
               padding: 10px 15px;
               background: #28a745;
               color: white;
               text-decoration: none;
               border-radius: 4px;
               font-size: 12px;
               word-break: break-all;
           "
           target="_blank">
            ✅ Confirmar Email (Clique aqui)
        </a>
        <p style="margin: 10px 0 0 0; font-size: 11px; color: #856404;">
            💡 Em produção, este link seria enviado por email
        </p>
    </div>
    ';
}
?>
