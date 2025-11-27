<header class="site">
    <nav class="navbar">
        <div class="logo">
            <img src="../../assets/images/fatec-ogari.png" alt="Logo Fatec Itapira">
            <img src="../../assets/images/logo-cps.png" alt="Logo CPS">
        </div>

        <ul class="links">
            <li><a href="../../pages/user/index.php" <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : '' ?>>Home</a></li>
            <li><a href="../../pages/user/inscricao.php" <?= basename($_SERVER['PHP_SELF']) == 'inscricao.php' ? 'class="active"' : '' ?>>Inscrição</a></li>
            <li><a href="../../pages/user/votacao.php" <?= basename($_SERVER['PHP_SELF']) == 'votacao.php' ? 'class="active"' : '' ?>>Votação</a></li>
            <li><a href="../../pages/user/sobre.php" <?= basename($_SERVER['PHP_SELF']) == 'sobre.php' ? 'class="active"' : '' ?>>Sobre</a></li>
        </ul>

        <div class="actions">
            <div class="user-menu">
                <img src="<?= htmlspecialchars(obterFotoUsuario()) ?>" alt="Avatar do usuário" class="user-icon" onclick="toggleUserDropdown()">
                <div class="user-dropdown" id="userDropdown">
                    <a href="../../pages/user/mudar_foto.php">
                        <i class="fas fa-camera"></i>
                        <span>Mudar Foto de Perfil</span>
                    </a>
                </div>
            </div>
            <a href="../../logout.php">Sair da Conta</a>
        </div>
    </nav>
</header>

<script>
    function toggleUserDropdown() {
        const dropdown = document.getElementById('userDropdown');
        dropdown.classList.toggle('show');
    }

    // Fechar dropdown ao clicar fora
    window.addEventListener('click', function(event) {
        if (!event.target.matches('.user-icon')) {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }
    });
</script>
