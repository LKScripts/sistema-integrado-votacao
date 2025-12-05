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
        WHERE a.curso = ? AND a.semestre = ?
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

// Função para obter semestre por extenso baseado na data da votação
function getSemestreExtenso($data_votacao) {
    $mes_votacao = (int)date('n', strtotime($data_votacao));

    // Janeiro a Junho = Primeiro Semestre, Julho a Dezembro = Segundo Semestre
    $semestre_num = ($mes_votacao <= 6) ? 1 : 2;
    $semestre_texto = $semestre_num == 1 ? 'primeiro' : 'segundo';

    return $semestre_texto;
}

$data_apuracao = dataExtenso($resultado['data_apuracao']);
$nome_curso = obterNomeCurso($resultado['curso']);
$periodo = getPeriodoExtenso($resultado['semestre']);
$semestre_ano = getSemestreExtenso($resultado['data_apuracao']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ata de Eleição - <?= $nome_curso ?> - <?= $resultado['semestre'] ?>º Semestre</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 2cm 2cm 3cm 2cm;
        }

        @media print {
            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 9pt;
                color: #666;
                border-top: 1px solid #8b0000;
                padding-top: 5px;
                background: white;
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

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 30px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14pt;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button no-print">Imprimir / Salvar PDF</button>

    <div class="container">
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
                    foreach ($alunos as $aluno):
                    ?>
                        <tr>
                            <td class="col-num"><?= $numero++ ?>.</td>
                            <td class="col-nome"><?= htmlspecialchars($aluno['nome_completo']) ?></td>
                            <td class="col-ra"><?= htmlspecialchars($aluno['ra']) ?></td>
                            <td class="col-assinatura">
                                <?php if ($aluno['id_voto']): ?>
                                    <em style="color: #006400; font-size: 9pt;">Votou</em>
                                <?php else: ?>
                                    <em style="color: #dc3545; font-size: 9pt;">Não votou</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php
                    // Adicionar linhas vazias se houver menos de 48 alunos
                    while ($numero <= 48):
                    ?>
                        <tr>
                            <td class="col-num"><?= $numero++ ?>.</td>
                            <td class="col-nome"></td>
                            <td class="col-ra"></td>
                            <td class="col-assinatura"></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="footer">
            www.fatecitapira.edu.br<br>
            Rua Tereza Lera Paoletti, 590 • Jardim Bela Vista • 13974-080 • Itapira • SP • Tel.: (19) 3843-7537
        </div>
    </div>
</body>
</html>
