<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';

header("Content-Type: application/json; charset=utf-8");

// Validar entrada
if (!isset($_GET['curso']) || empty($_GET['curso'])) {
    echo json_encode(["erro" => "Curso inválido"]);
    exit;
}

$curso = $_GET['curso'];

// =============================
// 1. TOTAL DE ALUNOS DO CURSO
// =============================
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM ALUNO
    WHERE curso = ?
");
$stmt->execute([$curso]);
$totalAlunos = $stmt->fetchColumn() ?: 0;

// =============================
// 2. ELEIÇÕES DO CURSO
// =============================
$stmt = $conn->prepare("
    SELECT id_eleicao, semestre
    FROM ELEICAO
    WHERE curso = ?
    ORDER BY semestre ASC
");
$stmt->execute([$curso]);
$eleicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalVotosCurso = 0;
$totalCandidatosCurso = 0;

$votosPorCandidato = [];
$semestreLabels = [];
$semestreVotos = [];

// =============================
// 3. PROCESSAR CADA ELEIÇÃO
// =============================
foreach ($eleicoes as $eleicao) {

    $idEleicao = $eleicao['id_eleicao'];
    $semestre  = $eleicao['semestre'];

    // --- total de candidatos deferidos
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM CANDIDATURA
        WHERE id_eleicao = ? AND status_validacao = 'deferido'
    ");
    $stmt->execute([$idEleicao]);
    $totalCandidatosCurso += $stmt->fetchColumn();

    // --- votos por candidato (JOIN correto)
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(a.nome_completo, 'Voto em Branco') AS nome,
            COUNT(v.id_voto) AS total
        FROM VOTO v
        LEFT JOIN CANDIDATURA c 
            ON v.id_candidatura = c.id_candidatura
        LEFT JOIN ALUNO a
            ON c.id_aluno = a.id_aluno
        WHERE v.id_eleicao = ?
        GROUP BY nome
        ORDER BY total DESC
    ");
    $stmt->execute([$idEleicao]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalVotosSemestre = 0;

    foreach ($result as $row) {

        $totalVotosSemestre += (int)$row['total'];
        $totalVotosCurso    += (int)$row['total'];

        $votosPorCandidato[] = [
            "candidato" => $row['nome'],
            "votos"     => (int)$row['total'],
            "semestre"  => $semestre
        ];
    }

    $semestreLabels[] = $semestre . "º";
    $semestreVotos[]  = $totalVotosSemestre;
}

// =============================
// 4. RETORNO FINAL
// =============================
echo json_encode([
    "curso"        => $curso,
    "total_alunos" => $totalAlunos,
    "total_votos"  => $totalVotosCurso,
    "candidatos"   => $totalCandidatosCurso,

    "votos"        => $votosPorCandidato,

    "semestres" => [
        "labels" => $semestreLabels,
        "votos"  => $semestreVotos
    ],
]);

exit;
