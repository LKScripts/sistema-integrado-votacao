<?php
// pages/user/processar-solicitacao-mudanca.php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';
require_once '../../../config/csrf.php';

verificarAluno();

// Validar requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$usuario = obterUsuarioLogado();
$id_aluno = $usuario['id'];
$curso_atual = $usuario['curso'];
$semestre_atual = $usuario['semestre'];

// Obter dados do formulário
$tipo_mudanca = trim($_POST['tipo_mudanca'] ?? '');
$curso_novo = trim($_POST['curso_novo'] ?? '');
$semestre_novo = trim($_POST['semestre_novo'] ?? '');
$justificativa = trim($_POST['justificativa'] ?? '');

// Validações
$erros = [];

if (!in_array($tipo_mudanca, ['curso', 'semestre', 'ambos'])) {
    $erros[] = 'Tipo de mudança inválido.';
}

// Validar campos obrigatórios baseado no tipo
if ($tipo_mudanca === 'curso' || $tipo_mudanca === 'ambos') {
    if (empty($curso_novo)) {
        $erros[] = 'Novo curso é obrigatório para este tipo de mudança.';
    } elseif (!in_array($curso_novo, ['DSM', 'GE', 'GPI'])) {
        $erros[] = 'Curso selecionado é inválido.';
    } elseif ($curso_novo === $curso_atual && $tipo_mudanca === 'curso') {
        $erros[] = 'O novo curso deve ser diferente do atual.';
    }
} else {
    $curso_novo = null; // Não muda curso
}

if ($tipo_mudanca === 'semestre' || $tipo_mudanca === 'ambos') {
    if (empty($semestre_novo)) {
        $erros[] = 'Novo semestre é obrigatório para este tipo de mudança.';
    } elseif (!filter_var($semestre_novo, FILTER_VALIDATE_INT) || $semestre_novo < 1 || $semestre_novo > 6) {
        $erros[] = 'Semestre selecionado é inválido (deve ser entre 1 e 6).';
    } elseif ($semestre_novo == $semestre_atual && $tipo_mudanca === 'semestre') {
        $erros[] = 'O novo semestre deve ser diferente do atual.';
    }
} else {
    $semestre_novo = null; // Não muda semestre
}

// Verificar se já existe solicitação pendente
$stmt_pendente = $conn->prepare("
    SELECT id_solicitacao
    FROM solicitacao_mudanca
    WHERE id_aluno = ?
    AND status = 'pendente'
");
$stmt_pendente->execute([$id_aluno]);
if ($stmt_pendente->fetch()) {
    $erros[] = 'Você já possui uma solicitação pendente. Aguarde a resposta do administrador.';
}

// Se houver erros, redirecionar com mensagem
if (!empty($erros)) {
    $_SESSION['erro'] = implode('<br>', $erros);
    header('Location: index.php');
    exit;
}

try {
    // Inserir solicitação
    $stmt = $conn->prepare("
        INSERT INTO solicitacao_mudanca (
            id_aluno,
            tipo_mudanca,
            curso_atual,
            semestre_atual,
            curso_novo,
            semestre_novo,
            justificativa,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente')
    ");

    $stmt->execute([
        $id_aluno,
        $tipo_mudanca,
        $curso_atual,
        $semestre_atual,
        $curso_novo,
        $semestre_novo,
        $justificativa ?: null
    ]);

    $_SESSION['sucesso'] = 'Solicitação enviada com sucesso! Aguarde a análise do administrador.';

} catch (PDOException $e) {
    error_log("Erro ao criar solicitação de mudança: " . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao processar solicitação. Tente novamente mais tarde.';
}

header('Location: index.php');
exit;
