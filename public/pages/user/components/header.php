<?php
// Verificar se há eleição em fase de candidatura (para mostrar/ocultar link Inscrição)
if (!isset($eleicaoCandidatura)) {
    $usuario = obterUsuarioLogado();
    $curso = $usuario['curso'];
    $semestre = $usuario['semestre'];
    $eleicaoCandidatura = buscarEleicaoAtivaComVerificacao($curso, $semestre, 'candidatura');
}

// Verificar se o aluno já tem candidatura cadastrada
if (!isset($tem_candidatura)) {
    $usuario = obterUsuarioLogado();
    $id_aluno = $usuario['id'];
    $curso = $usuario['curso'];
    $semestre = $usuario['semestre'];

    $tem_candidatura = false;

    if ($eleicaoCandidatura) {
        $stmt = $conn->prepare("
            SELECT c.id_candidatura
            FROM CANDIDATURA c
            JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
            WHERE c.id_aluno = ?
            AND e.curso = ?
            AND e.semestre = ?
            ORDER BY c.data_inscricao DESC
            LIMIT 1
        ");
        $stmt->execute([$id_aluno, $curso, $semestre]);
        $tem_candidatura = ($stmt->fetch() !== false);
    }
}
?>
<header class="site">
    <nav class="navbar">
        <div class="logo">
            <img src="../../assets/images/fatec-ogari.png" alt="Logo Fatec Itapira">
            <img src="../../assets/images/logo-cps.png" alt="Logo CPS">
        </div>

        <ul class="links">
            <li><a href="../../pages/user/index.php" <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : '' ?>>Home</a></li>
            <?php if ($eleicaoCandidatura && !$tem_candidatura): ?>
                <li><a href="../../pages/user/inscricao.php" <?= basename($_SERVER['PHP_SELF']) == 'inscricao.php' ? 'class="active"' : '' ?>>Inscrição</a></li>
            <?php endif; ?>
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
                    <a href="../../pages/user/editar_perfil.php">
                        <i class="fas fa-user-edit"></i>
                        <span>Editar Perfil</span>
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
