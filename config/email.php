<?php
/**
 * Helper para envio de e-mails usando PHPMailer
 */

require_once __DIR__ . '/env.php';

// Carregar autoload do Composer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    throw new Exception('Composer autoload não encontrado. Execute: composer require phpmailer/phpmailer');
}
require $autoloadPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);

        try {
            // Configurações do servidor SMTP
            $this->mailer->isSMTP();
            $this->mailer->Host       = env('SMTP_HOST', 'smtp.gmail.com');
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = env('SMTP_USER');
            $this->mailer->Password   = env('SMTP_PASS');
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = env('SMTP_PORT', 587);
            $this->mailer->CharSet    = 'UTF-8';

            // Remetente padrão
            $this->mailer->setFrom(
                env('SMTP_FROM_EMAIL'),
                env('SMTP_FROM_NAME', 'Sistema de Votação FATEC')
            );
        } catch (Exception $e) {
            error_log("Erro ao configurar e-mail: " . $e->getMessage());
            throw new Exception("Erro ao configurar serviço de e-mail.");
        }
    }

    /**
     * Envia e-mail de confirmação de cadastro
     *
     * @param string $destinatario Email do destinatário
     * @param string $nomeDestinatario Nome do destinatário
     * @param string $token Token de confirmação
     * @param string $tipoUsuario 'aluno' ou 'admin'
     * @return bool
     */
    public function enviarConfirmacaoCadastro($destinatario, $nomeDestinatario, $token, $tipoUsuario) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($destinatario, $nomeDestinatario);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Confirme seu cadastro - Sistema de Votação FATEC';

            $baseUrl = env('BASE_URL', 'http://localhost/sistema-integrado-votacao');
            $linkConfirmacao = "{$baseUrl}/public/pages/guest/confirmar-email.php?token={$token}";

            $tipoTexto = $tipoUsuario === 'admin' ? 'Administrador' : 'Aluno';

            $this->mailer->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #c8102e; color: white; padding: 20px; text-align: center; }
                        .content { background-color: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                        .button {
                            display: inline-block;
                            padding: 12px 30px;
                            background-color: #c8102e;
                            color: white !important;
                            text-decoration: none;
                            border-radius: 5px;
                            margin: 20px 0;
                            font-weight: bold;
                        }
                        /* Garantir cor branca em todos os estados do link */
                        a.button:link { color: white !important; }
                        a.button:visited { color: white !important; }
                        a.button:hover { color: white !important; background-color: #a00d24; }
                        a.button:active { color: white !important; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Sistema de Votação FATEC</h1>
                        </div>
                        <div class='content'>
                            <h2>Olá, {$nomeDestinatario}!</h2>
                            <p>Você está recebendo este e-mail porque solicitou o cadastro como <strong>{$tipoTexto}</strong> no Sistema Integrado de Votação.</p>
                            <p>Para ativar sua conta, clique no botão abaixo:</p>
                            <p style='text-align: center;'>
                                <a href='{$linkConfirmacao}' class='button'>Confirmar Cadastro</a>
                            </p>
                            <p>Ou copie e cole este link no seu navegador:</p>
                            <p style='word-break: break-all; background-color: #fff; padding: 10px; border: 1px solid #ddd;'>{$linkConfirmacao}</p>
                            <p><strong>Este link é válido por 24 horas.</strong></p>
                            <p>Se você não solicitou este cadastro, ignore este e-mail.</p>
                        </div>
                        <div class='footer'>
                            <p>Sistema Integrado de Votação - FATEC/CPS</p>
                            <p>Este é um e-mail automático, não responda.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            $this->mailer->AltBody = "Olá, {$nomeDestinatario}!\n\n"
                . "Confirme seu cadastro acessando o link: {$linkConfirmacao}\n\n"
                . "Este link é válido por 24 horas.\n\n"
                . "Sistema de Votação FATEC";

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Método genérico para enviar e-mail
     */
    public function enviar($destinatario, $assunto, $corpo, $isHTML = true) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($destinatario);
            $this->mailer->isHTML($isHTML);
            $this->mailer->Subject = $assunto;
            $this->mailer->Body = $corpo;

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
}

// Função helper global
function enviarEmail($destinatario, $assunto, $corpo, $isHTML = true) {
    try {
        $emailService = new EmailService();
        return $emailService->enviar($destinatario, $assunto, $corpo, $isHTML);
    } catch (Exception $e) {
        error_log("Erro no envio de e-mail: " . $e->getMessage());
        return false;
    }
}
?>
