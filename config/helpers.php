<?php

// Garantir que session.php e conexao.php já foram incluídos
if (!isset($conn)) {
    die("ERRO: helpers.php requer que config/conexao.php seja incluído primeiro.");
}

/**
 * VALIDAÇÃO DE SENHAS
 */

/**
 * Valida se uma senha atende os requisitos mínimos
 * @param string $senha Senha a validar
 * @param int $min_length Tamanho mínimo (padrão: 6)
 * @return array ['valido' => bool, 'erro' => string|null]
 */
function validarSenha($senha, $min_length = 6) {
    if (empty($senha)) {
        return ['valido' => false, 'erro' => 'A senha é obrigatória.'];
    }

    if (strlen($senha) < $min_length) {
        return ['valido' => false, 'erro' => "A senha deve ter pelo menos {$min_length} caracteres."];
    }

    return ['valido' => true, 'erro' => null];
}

/**
 * Cria hash de senha usando bcrypt
 * @param string $senha Senha em texto plano
 * @return string Hash da senha
 */
function hashearSenha($senha) {
    return password_hash($senha, PASSWORD_BCRYPT);
}

/**
 * VALIDAÇÃO DE EMAILS
 */

/**
 * Valida se email pertence a um dos domínios institucionais
 *
 * Dev Mode: Quando ativado, aceita qualquer email válido (útil para demos e testes)
 * - Permite usar emails pessoais (gmail, outlook, etc) em apresentações
 * - Permite testar funcionalidades de email sem domínios institucionais
 * - Ainda valida formato básico do email
 *
 * @param string $email Email a validar
 * @param string $tipo 'aluno' ou 'admin' (em dev mode, usado apenas como sugestão)
 * @param bool $dev_mode Se true, aceita qualquer email válido (bypassa regra de domínio)
 * @return array ['valido' => bool, 'erro' => string|null, 'tipo_detectado' => string|null]
 */
function validarEmailInstitucional($email, $tipo = null, $dev_mode = false) {
    if (empty($email)) {
        return ['valido' => false, 'erro' => 'O e-mail é obrigatório.', 'tipo_detectado' => null];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valido' => false, 'erro' => 'E-mail inválido.', 'tipo_detectado' => null];
    }

    // DEV MODE: Aceitar qualquer email válido (para demos e testes)
    // Útil para apresentações onde precisa demonstrar envio de email
    // sem ter acesso a emails institucionais reais
    if ($dev_mode) {
        // Se domínio institucional for detectado, definir tipo automaticamente
        if (preg_match('/@cps\.sp\.gov\.br$/i', $email)) {
            $tipo_detectado = 'admin';
        } elseif (preg_match('/@fatec\.sp\.gov\.br$/i', $email)) {
            $tipo_detectado = 'aluno';
        } else {
            // Email não institucional: usar tipo fornecido ou deixar null
            $tipo_detectado = $tipo;
        }

        return ['valido' => true, 'erro' => null, 'tipo_detectado' => $tipo_detectado];
    }

    // Validação de domínio
    $eh_admin = preg_match('/@cps\.sp\.gov\.br$/i', $email);
    $eh_aluno = preg_match('/@fatec\.sp\.gov\.br$/i', $email);

    // Se não é nenhum dos dois domínios
    if (!$eh_admin && !$eh_aluno) {
        return [
            'valido' => false,
            'erro' => 'E-mail deve ser institucional (@fatec.sp.gov.br para alunos ou @cps.sp.gov.br para administradores).',
            'tipo_detectado' => null
        ];
    }

    // Se tipo foi especificado, validar correspondência
    if ($tipo === 'admin' && !$eh_admin) {
        return ['valido' => false, 'erro' => 'E-mail de administrador deve ser @cps.sp.gov.br', 'tipo_detectado' => null];
    }

    if ($tipo === 'aluno' && !$eh_aluno) {
        return ['valido' => false, 'erro' => 'E-mail de aluno deve ser @fatec.sp.gov.br', 'tipo_detectado' => null];
    }

    // Detectar tipo automaticamente
    $tipo_detectado = $eh_admin ? 'admin' : 'aluno';

    return ['valido' => true, 'erro' => null, 'tipo_detectado' => $tipo_detectado];
}

/**
 * VERIFICAÇÕES DE DUPLICATAS
 */

/**
 * Verifica se email de aluno já existe no banco
 * @param PDO $conn Conexão PDO
 * @param string $email Email a verificar
 * @param int|null $excluir_id ID do aluno a excluir da verificação (para edições)
 * @return bool True se email já existe
 */
function emailAlunoExiste($conn, $email, $excluir_id = null) {
    if ($excluir_id) {
        $stmt = $conn->prepare("SELECT id_aluno FROM ALUNO WHERE email_institucional = ? AND id_aluno != ?");
        $stmt->execute([$email, $excluir_id]);
    } else {
        $stmt = $conn->prepare("SELECT id_aluno FROM ALUNO WHERE email_institucional = ?");
        $stmt->execute([$email]);
    }

    return $stmt->fetch() !== false;
}

/**
 * Verifica se email de admin já existe no banco
 * @param PDO $conn Conexão PDO
 * @param string $email Email a verificar
 * @param int|null $excluir_id ID do admin a excluir da verificação (para edições)
 * @return bool True se email já existe
 */
function emailAdminExiste($conn, $email, $excluir_id = null) {
    if ($excluir_id) {
        $stmt = $conn->prepare("SELECT id_admin FROM ADMINISTRADOR WHERE email_corporativo = ? AND id_admin != ?");
        $stmt->execute([$email, $excluir_id]);
    } else {
        $stmt = $conn->prepare("SELECT id_admin FROM ADMINISTRADOR WHERE email_corporativo = ?");
        $stmt->execute([$email]);
    }

    return $stmt->fetch() !== false;
}

/**
 * Verifica se RA já existe no banco
 * @param PDO $conn Conexão PDO
 * @param string $ra RA a verificar
 * @param int|null $excluir_id ID do aluno a excluir da verificação (para edições)
 * @return bool True se RA já existe
 */
function raExiste($conn, $ra, $excluir_id = null) {
    if ($excluir_id) {
        $stmt = $conn->prepare("SELECT id_aluno FROM ALUNO WHERE ra = ? AND id_aluno != ?");
        $stmt->execute([$ra, $excluir_id]);
    } else {
        $stmt = $conn->prepare("SELECT id_aluno FROM ALUNO WHERE ra = ?");
        $stmt->execute([$ra]);
    }

    return $stmt->fetch() !== false;
}

/**
 * VERIFICAÇÕES DE ELEIÇÃO
 */

/**
 * Verifica se aluno já se candidatou em uma eleição
 * @param PDO $conn Conexão PDO
 * @param int $id_eleicao ID da eleição
 * @param int $id_aluno ID do aluno
 * @return array|false Dados da candidatura ou false se não existe
 */
function alunoCandidatouNaEleicao($conn, $id_eleicao, $id_aluno) {
    $stmt = $conn->prepare("
        SELECT id_candidatura, status_validacao, data_inscricao
        FROM CANDIDATURA
        WHERE id_eleicao = ? AND id_aluno = ?
    ");
    $stmt->execute([$id_eleicao, $id_aluno]);
    return $stmt->fetch();
}

/**
 * Verifica se aluno já votou em uma eleição
 * @param PDO $conn Conexão PDO
 * @param int $id_eleicao ID da eleição
 * @param int $id_aluno ID do aluno
 * @return bool True se já votou
 */
function alunoVotouNaEleicao($conn, $id_eleicao, $id_aluno) {
    $stmt = $conn->prepare("SELECT id_voto FROM VOTO WHERE id_eleicao = ? AND id_aluno = ?");
    $stmt->execute([$id_eleicao, $id_aluno]);
    return $stmt->fetch() !== false;
}

/**
 * AUDITORIA
 */

/**
 * Registra operação no log de auditoria (VERSÃO COMPLETA)
 *
 * Função expandida para suportar todos os campos da tabela AUDITORIA:
 * - id_eleicao: Rastreia qual eleição foi afetada pela operação
 * - dados_anteriores: JSON com estado antes da mudança (before snapshot)
 * - dados_novos: JSON com estado depois da mudança (after snapshot)
 *
 * IMPORTANTE: Tabela AUDITORIA é IMUTÁVEL (não pode ser editada/deletada via SQL)
 * - Triggers do banco impedem UPDATE/DELETE em registros de auditoria
 * - Garante integridade e rastreabilidade completa de todas as operações
 *
 * @param PDO $conn Conexão PDO
 * @param int $id_admin ID do administrador que executou a operação
 * @param string $tabela Nome da tabela afetada (ALUNO, ELEICAO, CANDIDATURA, etc)
 * @param string $operacao Tipo de operação (INSERT, UPDATE, DELETE, LOGIN, LOGOUT)
 * @param string $descricao Descrição textual da operação (ex: "Aprovou candidatura #123")
 * @param string|null $ip_origem IP de origem (padrão: $_SERVER['REMOTE_ADDR'])
 * @param int|null $id_eleicao ID da eleição relacionada (opcional, para operações em eleições)
 * @param string|null $dados_anteriores JSON com estado anterior (opcional, ex: json_encode(['status' => 'pendente']))
 * @param string|null $dados_novos JSON com estado novo (opcional, ex: json_encode(['status' => 'aprovado']))
 * @return bool True se registrou com sucesso, False em caso de erro
 *
 * @example Uso básico (compatível com código existente):
 * registrarAuditoria($conn, $id_admin, 'ALUNO', 'DELETE', 'Deletou aluno João Silva');
 *
 * @example Uso completo com JSON (recomendado para operações críticas):
 * registrarAuditoria(
 *     $conn,
 *     $_SESSION['usuario_id'],
 *     'CANDIDATURA',
 *     'UPDATE',
 *     'Aprovou candidatura #123',
 *     null, // IP será detectado automaticamente
 *     $id_eleicao,
 *     json_encode(['status' => 'pendente', 'validado_por' => null]),
 *     json_encode(['status' => 'aprovado', 'validado_por' => $id_admin])
 * );
 */
function registrarAuditoria(
    $conn,
    $id_admin,
    $tabela,
    $operacao,
    $descricao,
    $ip_origem = null,
    $id_eleicao = null,
    $dados_anteriores = null,
    $dados_novos = null
) {
    try {
        // Auto-detecção de IP se não fornecido
        if ($ip_origem === null) {
            $ip_origem = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        // Validação básica de JSON (se fornecidos)
        if ($dados_anteriores !== null && !is_string($dados_anteriores)) {
            error_log("AVISO: dados_anteriores deve ser string JSON válida");
            $dados_anteriores = json_encode($dados_anteriores);
        }

        if ($dados_novos !== null && !is_string($dados_novos)) {
            error_log("AVISO: dados_novos deve ser string JSON válida");
            $dados_novos = json_encode($dados_novos);
        }

        $stmt = $conn->prepare("
            INSERT INTO AUDITORIA (
                id_admin, tabela, operacao, descricao,
                ip_origem, id_eleicao, dados_anteriores, dados_novos
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $id_admin,
            $tabela,
            $operacao,
            $descricao,
            $ip_origem,
            $id_eleicao,
            $dados_anteriores,
            $dados_novos
        ]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
        // Falha silenciosa para não interromper operação principal
        return false;
    }
}

/**
 * UTILITÁRIOS
 */

/**
 * Sanitiza array de inputs usando trim
 * @param array $inputs Array associativo de inputs
 * @return array Array com valores trimados
 */
function sanitizarInputs($inputs) {
    $sanitized = [];
    foreach ($inputs as $key => $value) {
        if (is_string($value)) {
            $sanitized[$key] = trim($value);
        } else {
            $sanitized[$key] = $value;
        }
    }
    return $sanitized;
}

/**
 * Extrai dados do POST com valores padrão
 * @param array $campos Array de campos a extrair ['nome_campo' => 'valor_padrao']
 * @return array Array associativo com valores extraídos
 */
function extrairDadosPost($campos) {
    $dados = [];
    foreach ($campos as $campo => $valor_padrao) {
        $valor = $_POST[$campo] ?? $valor_padrao;

        // Aplicar trim se for string
        if (is_string($valor) && $valor !== $valor_padrao) {
            $valor = trim($valor);
        }

        $dados[$campo] = $valor;
    }
    return $dados;
}

/**
 * Verifica se campos obrigatórios estão preenchidos
 * @param array $campos Array associativo ['nome_campo' => 'valor']
 * @return array ['valido' => bool, 'campos_vazios' => array]
 */
function validarCamposObrigatorios($campos) {
    $vazios = [];

    foreach ($campos as $nome => $valor) {
        if (empty($valor) && $valor !== 0 && $valor !== '0') {
            $vazios[] = $nome;
        }
    }

    return [
        'valido' => empty($vazios),
        'campos_vazios' => $vazios
    ];
}

/**
 * Formata lista de campos para mensagem de erro
 * @param array $campos Array de nomes de campos
 * @return string Campos formatados para exibição
 */
function formatarCamposErro($campos) {
    if (empty($campos)) {
        return '';
    }

    // Traduzir nomes técnicos para nomes amigáveis
    $traducoes = [
        'nome' => 'Nome',
        'nome_completo' => 'Nome Completo',
        'email' => 'E-mail',
        'senha' => 'Senha',
        'ra' => 'RA',
        'curso' => 'Curso',
        'semestre' => 'Semestre',
        'proposta' => 'Proposta'
    ];

    $campos_formatados = array_map(function($campo) use ($traducoes) {
        return $traducoes[$campo] ?? ucfirst($campo);
    }, $campos);

    if (count($campos_formatados) === 1) {
        return $campos_formatados[0];
    }

    $ultimo = array_pop($campos_formatados);
    return implode(', ', $campos_formatados) . ' e ' . $ultimo;
}
?>
