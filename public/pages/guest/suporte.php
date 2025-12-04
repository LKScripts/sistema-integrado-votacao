<?php
// Nenhum processamento necessário aqui, apenas renderização da página.
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIV - Sistema Integrado de Votações</title>

    <link rel="stylesheet" href="../../assets/styles/guest.css">
    <link rel="stylesheet" href="../../assets/styles/base.css">
    <link rel="stylesheet" href="../../assets/styles/fonts.css">
    <link rel="stylesheet" href="../../assets/styles/footer-site.css">
    <link rel="stylesheet" href="../../assets/styles/header-site.css">
</head>

<body>
    <main class="login-support">
        <div class="container">

            <header>
                <h1>
                    <i class="fas fa-life-ring"></i>
                    Suporte e Ajuda
                </h1>
                <p>Sistema Integrado de Votações - SIV</p>
            </header>

            <div class="support-content">
                <div class="callout info" style="margin-bottom: 30px;">
                    <div class="content">
                        <i class="fas fa-info-circle"></i>
                        <p>
                            <strong>Sobre o Sistema SIV:</strong>
                            O Sistema Integrado de Votações é uma plataforma web desenvolvida para facilitar e automatizar
                            o processo eleitoral acadêmico da FATEC Itapira. Se você está tendo dificuldades para acessar
                            ou utilizar o sistema, consulte as informações abaixo.
                        </p>
                    </div>
                </div>

                <h2>Problemas Comuns e Soluções</h2>

                <p style="margin-top: 15px; color: #555; line-height: 1.6;">
                    <strong>Não recebi o email de confirmação:</strong><br>
                    Verifique sua caixa de spam ou lixo eletrônico. O email é enviado automaticamente após o cadastro.
                    Se não recebeu após alguns minutos, entre em contato com a coordenação.
                </p>

                <p style="margin-top: 15px; color: #555; line-height: 1.6;">
                    <strong>Esqueci minha senha:</strong><br>
                    Como suas credenciais estão vinculadas ao sistema institucional, é necessário redefinir sua senha
                    diretamente na plataforma ou através da secretaria da sua instituição.
                </p>

                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h3>Problemas com Email de Confirmação</h3>
                        <p>Se não recebeu o email após o cadastro, verifique sua caixa de spam. O sistema envia automaticamente
                           um link de confirmação válido por 24 horas. Se expirou, entre em contato com a administração.</p>
                    </div>

                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Conta Bloqueada por Tentativas de Login</h3>
                        <p>O sistema possui proteção contra múltiplas tentativas falhas de login (5 tentativas em 15 minutos).
                           Aguarde o tempo especificado ou entre em contato com a secretaria acadêmica.</p>
                    </div>

                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Dúvidas Sobre o Processo Eleitoral</h3>
                        <p>Para questões sobre prazos, candidaturas ou votação, consulte o edital da eleição ou entre em contato
                           com a coordenação do curso. O sistema automatiza os prazos conforme configurado pelos administradores.</p>
                    </div>
                </div>

                <div class="callout warning">
                    <div class="content">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>
                            <strong>Precisa de Ajuda Adicional?</strong>
                            Para problemas técnicos ou dúvidas não resolvidas acima, entre em contato com a secretaria
                            acadêmica da FATEC Itapira durante o horário de funcionamento. Tenha em mãos seu RA e email
                            institucional para facilitar o atendimento.
                        </p>
                    </div>
                </div>

                <a href="../../index.php" class="button primary">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
            </div>

            <footer>
                <p>2025 Sistema Integrado de Votação FATEC CPS</p>
            </footer>

        </div>
    </main>
</body>

</html>
