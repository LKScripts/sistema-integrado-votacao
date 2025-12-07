<?php
// Incluir funções de sessão se ainda não foram carregadas
if (!function_exists('obterUsuarioLogado')) {
    require_once __DIR__ . '/../../../config/session.php';
}

$usuario_logado = obterUsuarioLogado();
$nome_admin = $usuario_logado['nome_completo'] ?? 'Administrador';
?>
<header class="site">
    <nav class="navbar">
        <div class="logo">
            <img src="../../assets/images/fatec-ogari.png" alt="Logo Fatec Itapira">
            <img src="../../assets/images/logo-cps.png" alt="Logo CPS">
        </div>

        <ul class="links">
            <li><a href="../../pages/admin/index.php" <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : '' ?>>Home</a></li>
            <li><a href="../../pages/admin/inscricoes.php" <?= basename($_SERVER['PHP_SELF']) == 'inscricoes.php' ? 'class="active"' : '' ?>>Inscrições</a></li>
            <li><a href="../../pages/admin/prazos.php" <?= basename($_SERVER['PHP_SELF']) == 'prazos.php' ? 'class="active"' : '' ?>>Prazos</a></li>
            <li><a href="../../pages/admin/apuracao.php" <?= basename($_SERVER['PHP_SELF']) == 'apuracao.php' ? 'class="active"' : '' ?>>Apuração</a></li>
            <li><a href="../../pages/admin/gerenciar-admins.php" <?= basename($_SERVER['PHP_SELF']) == 'gerenciar-admins.php' ? 'class="active"' : '' ?>>Gerenciar Admins</a></li>
            <li><a href="../../pages/admin/gerenciar-alunos.php" <?= basename($_SERVER['PHP_SELF']) == 'gerenciar-alunos.php' ? 'class="active"' : '' ?>>Gerenciar Alunos</a></li>
        </ul>

        <div class="actions">
            <span class="admin-name"><?= htmlspecialchars($nome_admin) ?></span>
            <a href="../../logout.php">Sair da Conta</a>
        </div>
    </nav>
</header>
