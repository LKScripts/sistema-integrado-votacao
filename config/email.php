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
     * Envia e-mail de notificação de apuração pendente
     *
     * @param string $destinatario Email do administrador
     * @param string $nomeAdmin Nome do administrador
     * @param array $eleicoes Array de eleições pendentes
     * @param string $tipo 'individual' ou 'lote'
     * @return bool
     */
    public function enviarNotificacaoApuracao($destinatario, $nomeAdmin, $eleicoes, $tipo = 'individual') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($destinatario, $nomeAdmin);

            $this->mailer->isHTML(true);

            $baseUrl = env('BASE_URL', 'http://localhost/sistema-integrado-votacao');
            $linkApuracao = "{$baseUrl}/public/pages/admin/apuracao.php";

            // Definir assunto baseado no tipo
            if ($tipo === 'lote') {
                $qtd = count($eleicoes);
                $this->mailer->Subject = "Apuração Pendente - {$qtd} Eleições Aguardando";
            } else {
                $eleicao = $eleicoes[0];
                $this->mailer->Subject = "Apuração Pendente - {$eleicao['curso']} {$eleicao['semestre']}º Semestre";
            }

            // Construir lista de eleições
            $listaEleicoesHTML = '';
            foreach ($eleicoes as $e) {
                $listaEleicoesHTML .= "
                    <li style='margin-bottom: 15px; padding: 10px; background-color: #fff; border-left: 4px solid #c8102e;'>
                        <strong>{$e['curso']} - {$e['semestre']}º Semestre</strong><br>
                        <small style='color: #666;'>
                            ID: {$e['id_eleicao']} |
                            Votação finalizada em: " . date('d/m/Y H:i', strtotime($e['data_fim_votacao'])) . "
                        </small>
                    </li>
                ";
            }

            $this->mailer->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #c8102e; color: white; padding: 20px; text-align: center; }
                        .content { background-color: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                        .eleicoes-lista { list-style: none; padding: 0; margin: 20px 0; }
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
                        a.button:link, a.button:visited, a.button:hover, a.button:active {
                            color: white !important;
                        }
                        a.button:hover {
                            background-color: #a00d24;
                        }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                        .alert-box {
                            background-color: #fff3cd;
                            padding: 15px;
                            margin: 20px 0;
                        }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>⚠️ Apuração Pendente</h1>
                        </div>
                        <div class='content'>
                            <h2>Olá, {$nomeAdmin}!</h2>

                            <div class='alert-box'>
                                <strong>⏰ Ação Necessária</strong><br>
                                " . ($tipo === 'lote'
                                    ? count($eleicoes) . " eleições finalizaram a votação e aguardam apuração."
                                    : "A eleição abaixo finalizou a votação e aguarda apuração.") . "
                            </div>

                            <p>As seguintes eleições estão prontas para serem apuradas:</p>

                            <ul class='eleicoes-lista'>
                                {$listaEleicoesHTML}
                            </ul>

                            <p><strong>O que fazer agora?</strong></p>
                            <ol>
                                <li>Acesse o painel de apuração clicando no botão abaixo</li>
                                <li>Revise os votos de cada eleição</li>
                                <li>Finalize a apuração para gerar os resultados oficiais</li>
                            </ol>

                            <p style='text-align: center;'>
                                <a href='{$linkApuracao}' class='button'>Acessar Painel de Apuração</a>
                            </p>

                            <p style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666;'>
                                <small>
                                    Este é um e-mail automático enviado pelo Sistema Integrado de Votação.<br>
                                    A apuração deve ser realizada o mais breve possível para divulgar os resultados aos alunos.
                                </small>
                            </p>
                        </div>
                        <div class='footer'>
                            <p>Sistema Integrado de Votação - FATEC/CPS</p>
                            <p>Este é um e-mail automático, não responda.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            // Texto alternativo para clientes sem HTML
            $listaEleicoesTexto = '';
            foreach ($eleicoes as $e) {
                $listaEleicoesTexto .= "- {$e['curso']} - {$e['semestre']}º Semestre (ID: {$e['id_eleicao']})\n";
            }

            $this->mailer->AltBody = "Olá, {$nomeAdmin}!\n\n"
                . "APURAÇÃO PENDENTE\n\n"
                . ($tipo === 'lote'
                    ? count($eleicoes) . " eleições finalizaram a votação e aguardam apuração.\n\n"
                    : "A eleição abaixo finalizou a votação e aguarda apuração.\n\n")
                . "Eleições pendentes:\n{$listaEleicoesTexto}\n\n"
                . "Acesse o painel de apuração: {$linkApuracao}\n\n"
                . "Sistema de Votação FATEC";

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erro ao enviar notificação de apuração: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Envia e-mail de recuperação de senha
     *
     * @param string $destinatario Email do usuário
     * @param string $nomeUsuario Nome do usuário
     * @param string $token Token de recuperação
     * @param string $tipoUsuario 'aluno' ou 'admin'
     * @return bool
     */
    public function enviarRecuperacaoSenha($destinatario, $nomeUsuario, $token, $tipoUsuario) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($destinatario, $nomeUsuario);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Recuperação de Senha - Sistema de Votação FATEC';

            $baseUrl = env('BASE_URL', 'http://localhost/sistema-integrado-votacao');
            $linkRecuperacao = "{$baseUrl}/public/pages/guest/redefinir-senha.php?token={$token}";

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
                        a.button:link, a.button:visited, a.button:hover, a.button:active {
                            color: white !important;
                        }
                        a.button:hover {
                            background-color: #a00d24;
                        }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                        .alert-box {
                            background-color: #fff3cd;
                            padding: 15px;
                            margin: 20px 0;
                        }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1> Recuperação de Senha</h1>
                        </div>
                        <div class='content'>
                            <h2>Olá, {$nomeUsuario}!</h2>
                            <p>Você solicitou a recuperação de senha para sua conta de <strong>{$tipoTexto}</strong> no Sistema Integrado de Votação.</p>

                            <div class='alert-box'>
                                <strong> Importante:</strong><br>
                                Se você não solicitou esta recuperação, ignore este e-mail. Sua senha permanecerá inalterada.
                            </div>

                            <p>Para redefinir sua senha, clique no botão abaixo:</p>
                            <p style='text-align: center;'>
                                <a href='{$linkRecuperacao}' class='button'>Redefinir Minha Senha</a>
                            </p>
                            <p>Ou copie e cole este link no seu navegador:</p>
                            <p style='word-break: break-all; background-color: #fff; padding: 10px; border: 1px solid #ddd;'>{$linkRecuperacao}</p>

                            <p><strong>Este link é válido por 1 hora.</strong></p>
                            <p style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666;'>
                                <small>
                                    Por motivos de segurança, este link expirará automaticamente após o uso ou após 1 hora.<br>
                                    Se o link expirar, você precisará solicitar uma nova recuperação de senha.
                                </small>
                            </p>
                        </div>
                        <div class='footer'>
                            <p>Sistema Integrado de Votação - FATEC/CPS</p>
                            <p>Este é um e-mail automático, não responda.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            $this->mailer->AltBody = "Olá, {$nomeUsuario}!\n\n"
                . "Você solicitou a recuperação de senha para sua conta de {$tipoTexto}.\n\n"
                . "Acesse o link abaixo para redefinir sua senha:\n{$linkRecuperacao}\n\n"
                . "Este link é válido por 1 hora.\n\n"
                . "Se você não solicitou esta recuperação, ignore este e-mail.\n\n"
                . "Sistema de Votação FATEC";

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail de recuperação: " . $this->mailer->ErrorInfo);
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
