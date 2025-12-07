<?php
require_once '../../../config/session.php';
require_once '../../../config/conexao.php';

// Verificar se é administrador
verificarAdmin();

$id_eleicao = $_GET['id'] ?? null;

if (!$id_eleicao) {
    header('Location: apuracao.php');
    exit;
}

// Buscar dados completos do resultado
$sql = "SELECT * FROM v_resultados_completos WHERE id_eleicao = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id_eleicao]);
$resultado = $stmt->fetch();

if (!$resultado) {
    $_SESSION['erro'] = 'Resultado não encontrado para esta eleição.';
    header('Location: apuracao.php');
    exit;
}

// Buscar lista de alunos aptos para assinatura com verificação de voto
$sql = "SELECT
            a.nome_completo,
            a.ra,
            v.id_voto
        FROM ALUNO a
        LEFT JOIN VOTO v ON a.id_aluno = v.id_aluno AND v.id_eleicao = ?
        WHERE a.curso = ? AND a.semestre = ? AND a.ativo = 1
        ORDER BY a.nome_completo";
$stmt = $conn->prepare($sql);
$stmt->execute([$id_eleicao, $resultado['curso'], $resultado['semestre']]);
$alunos = $stmt->fetchAll();

// Função para converter data para extenso
function dataExtenso($data) {
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];

    $timestamp = strtotime($data);
    $dia = date('j', $timestamp);
    $mes = (int)date('n', $timestamp);
    $ano = date('Y', $timestamp);

    // Converter números para palavras
    $unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
    $dezenas = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
    $especiais = ['dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
    $centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

    // Dia
    $dia_extenso = '';
    if ($dia < 10) {
        $dia_extenso = $unidades[$dia];
    } elseif ($dia >= 10 && $dia < 20) {
        $dia_extenso = $especiais[$dia - 10];
    } else {
        $d = floor($dia / 10);
        $u = $dia % 10;
        $dia_extenso = $dezenas[$d];
        if ($u > 0) {
            $dia_extenso .= ' e ' . $unidades[$u];
        }
    }

    // Ano (simplificado - apenas para anos 2000-2099)
    $milhar = floor($ano / 1000);
    $resto = $ano % 1000;
    $centena = floor($resto / 100);
    $resto = $resto % 100;

    $ano_extenso = 'dois mil';
    if ($centena > 0) {
        $ano_extenso .= ' e ' . $centenas[$centena];
    }
    if ($resto >= 10 && $resto < 20) {
        $ano_extenso .= ' e ' . $especiais[$resto - 10];
    } elseif ($resto >= 20) {
        $d = floor($resto / 10);
        $u = $resto % 10;
        $ano_extenso .= ' e ' . $dezenas[$d];
        if ($u > 0) {
            $ano_extenso .= ' e ' . $unidades[$u];
        }
    } elseif ($resto > 0) {
        $ano_extenso .= ' e ' . $unidades[$resto];
    }

    return [
        'dia' => $dia_extenso,
        'mes' => $meses[$mes],
        'ano' => $ano_extenso,
        'completa' => "$dia_extenso de {$meses[$mes]} de $ano_extenso"
    ];
}

// Mapear siglas para nomes completos
function obterNomeCurso($sigla) {
    $cursos = [
        'DSM' => 'Desenvolvimento de Software Multiplataforma',
        'GE' => 'Gestão Empresarial',
        'GPI' => 'Gestão da Produção Industrial'
    ];
    return strtoupper($cursos[$sigla] ?? $sigla);
}

// Função para obter período por extenso
function getPeriodoExtenso($semestre) {
    $periodos = [
        1 => 'primeiro período',
        2 => 'segundo período',
        3 => 'terceiro período',
        4 => 'quarto período',
        5 => 'quinto período',
        6 => 'sexto período'
    ];
    return $periodos[$semestre] ?? "{$semestre}º período";
}

// Função para obter ano letivo por extenso (primeiro ou segundo semestre)
// Baseado na data de apuração, determina se é 1º ou 2º semestre do ano
function getSemestreAnoExtenso($data_apuracao) {
    $mes = (int)date('n', strtotime($data_apuracao));
    // Janeiro a Junho = Primeiro Semestre, Julho a Dezembro = Segundo Semestre
    return ($mes <= 6) ? 'primeiro' : 'segundo';
}

$data_apuracao = dataExtenso($resultado['data_apuracao']);
$nome_curso = obterNomeCurso($resultado['curso']);
$periodo = getPeriodoExtenso($resultado['semestre']);
$semestre_ano = getSemestreAnoExtenso($resultado['data_apuracao']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ata de Eleição - <?= $nome_curso ?> - <?= $resultado['semestre'] ?>º Semestre</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 1.5cm 2cm 2cm 2cm;
        }

        @media print {
            .footer {
                margin-top: 30px;
                page-break-inside: avoid;
                text-align: center;
                font-size: 9pt;
                color: #666;
                border-top: 1px solid #8b0000;
                padding-top: 5px;
            }

            .assinaturas-section {
                page-break-inside: auto;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }

        .container {
            max-width: 21cm;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #8b0000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-logos {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .header-logos img {
            height: 60px;
        }

        .header h1 {
            color: #8b0000;
            font-size: 14pt;
            font-weight: bold;
            margin: 5px 0;
        }

        .header h2 {
            color: #8b0000;
            font-size: 12pt;
            font-weight: normal;
            margin: 3px 0;
        }

        .header-divider {
            width: 100%;
            height: 2px;
            background: #8b0000;
            margin: 10px 0;
        }

        .footer {
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #8b0000;
            padding-top: 5px;
            margin-top: 20px;
        }

        .content {
            margin: 20px 0;
        }

        .title {
            text-align: justify;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .paragraph {
            text-align: justify;
            text-indent: 2cm;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .signature-table th,
        .signature-table td {
            border: 1px solid #000;
            padding: 8px 5px;
            font-size: 10pt;
        }

        .signature-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .signature-table td {
            text-align: left;
        }

        .signature-table .col-num {
            width: 5%;
            text-align: center;
        }

        .signature-table .col-nome {
            width: 45%;
        }

        .signature-table .col-ra {
            width: 25%;
        }

        .signature-table .col-assinatura {
            width: 25%;
        }

        .date-location {
            margin-top: 30px;
            text-align: justify;
            font-size: 11pt;
        }

        @media print {
            body {
                background: white;
            }

            .container {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-before: always;
            }
        }

        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .btn-action {
            padding: 15px 30px;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14pt;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .btn-download {
            background: #28a745;
        }

        .btn-download:hover {
            background: #218838;
        }

        .btn-print {
            background: #007bff;
        }

        .btn-print:hover {
            background: #0056b3;
        }

        .btn-action:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <div class="action-buttons no-print">
        <button onclick="downloadPDF()" class="btn-action btn-download" id="btnDownload">
            Baixar PDF
        </button>
        <button onclick="window.print()" class="btn-action btn-print">
            Imprimir
        </button>
    </div>

    <div class="container" id="ata-content">
        <div class="header">
            <div class="header-logos">
                <img src="../../assets/images/Logo.png" alt="Logo CPS">
                <img src="../../assets/images/mceclip0.png" alt="Logo Fatec Itapira">
                <img src="../../assets/images/sp_gov.png" alt="Governo do Estado de SP">
            </div>
            <h1>Faculdade de Tecnologia de Itapira – "Ogari de Castro Pacheco"</h1>
            <h2>Diretoria Acadêmica</h2>
            <div class="header-divider"></div>
        </div>

        <div class="content">
            <div class="title">
                ATA DE ELEIÇÃO DE REPRESENTANTES DE TURMA DO <?= strtoupper($periodo) ?> DO <?= strtoupper($semestre_ano) ?>
                SEMESTRE DE <?= strtoupper($data_apuracao['ano']) ?>, DO CURSO DE TECNOLOGIA EM
                <?= $nome_curso ?> DA FACULDADE DE TECNOLOGIA DE ITAPIRA "OGARI DE CASTRO PACHECO".
            </div>

            <div class="paragraph">
                Ao <?= $data_apuracao['completa'] ?>, foram apurados os votos dos alunos regularmente
                matriculados no <?= $periodo ?> do <?= $semestre_ano ?> semestre de
                <?= $data_apuracao['ano'] ?> do Curso Superior de Tecnologia em <?= $nome_curso ?>
                para eleição de novos representantes de turma. Os representantes eleitos fazem a
                representação dos alunos nos órgãos colegiados da Faculdade, com direito a voz e voto,
                conforme o disposto no artigo 69 da Deliberação CEETEPS nº 07, de 15 de dezembro de 2006.
                Foi eleito(a) como representante o(a) aluno(a)
                <strong><?= htmlspecialchars($resultado['representante']) ?></strong>,
                R.A. nº <strong><?= htmlspecialchars($resultado['ra_representante']) ?></strong>
                <?php if ($resultado['suplente']): ?>
                    e eleito como vice o(a) aluno(a)
                    <strong><?= htmlspecialchars($resultado['suplente']) ?></strong>,
                    R.A. nº <strong><?= htmlspecialchars($resultado['ra_suplente']) ?></strong>.
                <?php else: ?>
                    .
                <?php endif; ?>
                A presente ata, após leitura e concordância, vai assinada por todos os alunos participantes.
            </div>

            <div class="date-location">
                Itapira, <?= $data_apuracao['completa'] ?>.
            </div>

            <div class="assinaturas-section">
            <table class="signature-table">
                <thead>
                    <tr>
                        <th class="col-num">Nº</th>
                        <th class="col-nome">NOME</th>
                        <th class="col-ra">R.A. COMPLETO</th>
                        <th class="col-assinatura">ASSINATURA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $numero = 1;
                    $total_alunos = count($alunos);

                    // Separar alunos que votaram e que não votaram
                    $alunos_votaram = array_filter($alunos, function($a) { return $a['id_voto'] !== null; });
                    $alunos_nao_votaram = array_filter($alunos, function($a) { return $a['id_voto'] === null; });

                    // Exibir primeiro os que votaram
                    foreach ($alunos_votaram as $aluno):
                    ?>
                        <tr>
                            <td class="col-num"><?= $numero++ ?>.</td>
                            <td class="col-nome"><?= htmlspecialchars($aluno['nome_completo']) ?></td>
                            <td class="col-ra"><?= htmlspecialchars($aluno['ra']) ?></td>
                            <td class="col-assinatura">
                                <em style="color: #006400; font-size: 9pt;">Votou</em>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php
                    // Depois exibir os que não votaram
                    foreach ($alunos_nao_votaram as $aluno):
                    ?>
                        <tr>
                            <td class="col-num"><?= $numero++ ?>.</td>
                            <td class="col-nome"><?= htmlspecialchars($aluno['nome_completo']) ?></td>
                            <td class="col-ra"><?= htmlspecialchars($aluno['ra']) ?></td>
                            <td class="col-assinatura">
                                <em style="color: #dc3545; font-size: 9pt;">Não votou</em>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php
                    // Adicionar linhas vazias apenas se necessário para manter um mínimo visual
                    // Mínimo de 10 linhas totais para manter layout profissional
                    $minimo_linhas = 10;
                    if ($total_alunos < $minimo_linhas) {
                        while ($numero <= $minimo_linhas):
                    ?>
                        <tr>
                            <td class="col-num"><?= $numero++ ?>.</td>
                            <td class="col-nome"></td>
                            <td class="col-ra"></td>
                            <td class="col-assinatura"></td>
                        </tr>
                    <?php
                        endwhile;
                    }
                    ?>
                </tbody>
            </table>
            </div>

        <div class="footer">
            www.fatecitapira.edu.br<br>
            Rua Tereza Lera Paoletti, 590 • Jardim Bela Vista • 13974-080 • Itapira • SP • Tel.: (19) 3843-7537
        </div>
    </div>

    <script>
        function downloadPDF() {
            const button = document.getElementById('btnDownload');
            button.disabled = true;
            button.textContent = 'Gerando PDF...';

            const element = document.getElementById('ata-content');
            const opt = {
                margin: [10, 10, 15, 10],
                filename: 'ata-eleicao-<?= $resultado['curso'] ?>-<?= $resultado['semestre'] ?>sem-<?= date('Y', strtotime($resultado['data_apuracao'])) ?>.pdf',
                image: { type: 'jpeg', quality: 0.95 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    letterRendering: true,
                    windowHeight: element.scrollHeight
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait',
                    compress: true
                },
                pagebreak: {
                    mode: ['css', 'legacy']
                }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                button.disabled = false;
                button.textContent = 'Baixar PDF';
            }).catch(err => {
                console.error('Erro ao gerar PDF:', err);
                alert('Erro ao gerar PDF. Tente usar a opção "Imprimir" e salvar como PDF.');
                button.disabled = false;
                button.textContent = 'Baixar PDF';
            });
        }
    </script>
</body>
</html>
