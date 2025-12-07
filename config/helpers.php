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

<?php

// =====================================================
// Funções que transformam IDs em informações legíveis
// =====================================================

/**
 * Transforma IDs em nomes, adiciona contexto e formata para exibição
 * 
 * @param PDO $conn Conexão PDO
 * @param string|null $json_dados JSON string com dados
 * @param string $tabela Nome da tabela para contexto
 * @return string HTML formatado ou string vazia
 */
function enriquecerDadosAuditoria($conn, $json_dados, $tabela) {
    if (empty($json_dados)) {
        return '<em style="color: #999;">Nenhum dado</em>';
    }
    
    try {
        $dados = json_decode($json_dados, true);
        
        if (!is_array($dados)) {
            return '<em style="color: #999;">Dados inválidos</em>';
        }
        
        // Enriquecer dados baseado na tabela
        $dados_enriquecidos = [];
        
        foreach ($dados as $chave => $valor) {
            $rotulo = traduzirCampoAuditoria($chave);
            $valor_legivel = formatarValorAuditoria($conn, $chave, $valor, $tabela);
            
            $dados_enriquecidos[] = [
                'rotulo' => $rotulo,
                'valor' => $valor_legivel,
                'chave_original' => $chave
            ];
        }
        
        // Gerar HTML formatado
        return gerarHTMLDadosAuditoria($dados_enriquecidos);
        
    } catch (Exception $e) {
        error_log("Erro ao enriquecer dados de auditoria: " . $e->getMessage());
        return '<em style="color: #999;">Erro ao processar dados</em>';
    }
}

/**
 * Traduz nome técnico de campo para algo amigável
 * 
 * @param string $campo Nome técnico do campo
 * @return string Rótulo amigável
 */
function traduzirCampoAuditoria($campo) {
    $traducoes = [
        // Campos de identificação
        'id_aluno' => 'Aluno',
        'id_admin' => 'Administrador',
        'id_eleicao' => 'Eleição',
        'id_candidatura' => 'Candidatura',
        
        // Campos de dados pessoais
        'nome_completo' => 'Nome Completo',
        'email_institucional' => 'E-mail Institucional',
        'email_corporativo' => 'E-mail Corporativo',
        'ra' => 'RA',
        
        // Campos de curso
        'curso' => 'Curso',
        'curso_atual' => 'Curso Atual',
        'curso_novo' => 'Curso Novo',
        'semestre' => 'Semestre',
        'semestre_atual' => 'Semestre Atual',
        'semestre_novo' => 'Semestre Novo',
        
        // Campos de status
        'status' => 'Status',
        'status_validacao' => 'Status de Validação',
        'ativo' => 'Ativo',
        'aprovado_por' => 'Aprovado Por',
        'validado_por' => 'Validado Por',
        'rejeitado_por' => 'Rejeitado Por',
        
        // Campos de datas
        'data_cadastro' => 'Data de Cadastro',
        'data_inscricao' => 'Data de Inscrição',
        'data_aprovacao' => 'Data de Aprovação',
        'data_rejeicao' => 'Data de Rejeição',
        'data_inicio_candidatura' => 'Início Candidatura',
        'data_fim_candidatura' => 'Fim Candidatura',
        'data_inicio_votacao' => 'Início Votação',
        'data_fim_votacao' => 'Fim Votação',
        
        // Campos de texto
        'proposta' => 'Proposta',
        'justificativa' => 'Justificativa',
        'justificativa_indeferimento' => 'Justificativa de Indeferimento',
        'motivo_rejeicao' => 'Motivo de Rejeição',
        'motivo_recusa' => 'Motivo de Recusa',
        'observacoes_admin' => 'Observações do Admin',
        
        // Campos técnicos
        'foto_candidato' => 'Foto',
        'foto_perfil' => 'Foto de Perfil'
    ];
    
    return $traducoes[$campo] ?? ucfirst(str_replace('_', ' ', $campo));
}

/**
 * Formata valor de campo para exibição legível
 * Busca no banco quando necessário para resolver IDs
 * 
 * @param PDO $conn Conexão PDO
 * @param string $campo Nome do campo
 * @param mixed $valor Valor do campo
 * @param string $tabela Tabela de contexto
 * @return string Valor formatado
 */
function formatarValorAuditoria($conn, $campo, $valor, $tabela) {
    // NULL values
    if ($valor === null) {
        return '<em style="color: #999;">Não informado</em>';
    }
    
    // Campos de ID - resolver para nomes
    if (strpos($campo, 'id_') === 0 || strpos($campo, '_por') !== false) {
        return resolverIDParaNome($conn, $campo, $valor);
    }
    
    // Status - adicionar badge colorido
    if (strpos($campo, 'status') !== false) {
        return formatarStatusComBadge($valor);
    }
    
    // Boolean - transformar em Sim/Não
    if (is_bool($valor) || $valor === '0' || $valor === '1') {
        $booleano = (bool)$valor;
        $cor = $booleano ? '#28a745' : '#dc3545';
        $texto = $booleano ? 'Sim' : 'Não';
        return "<strong style='color: {$cor};'>{$texto}</strong>";
    }
    
    // Datas - formatar
    if (strpos($campo, 'data_') === 0 && preg_match('/^\d{4}-\d{2}-\d{2}/', $valor)) {
        return formatarDataLegivel($valor);
    }
    
    // Curso - nome completo
    if ($campo === 'curso' || $campo === 'curso_atual' || $campo === 'curso_novo') {
        return obterNomeCursoCompleto($valor);
    }
    
    // Semestre - adicionar "º"
    if (strpos($campo, 'semestre') !== false) {
        return $valor . 'º Semestre';
    }
    
    // Textos longos - truncar com tooltip
    if (is_string($valor) && strlen($valor) > 100) {
        $resumo = substr($valor, 0, 100) . '...';
        return "<span title='" . htmlspecialchars($valor) . "'>{$resumo}</span>";
    }
    
    // Valor padrão
    return htmlspecialchars($valor);
}

/**
 * Resolve ID para nome legível (busca no banco)
 * 
 * @param PDO $conn Conexão PDO
 * @param string $campo Nome do campo
 * @param mixed $valor ID a resolver
 * @return string Nome ou ID formatado
 */
function resolverIDParaNome($conn, $campo, $valor) {
    if (empty($valor)) {
        return '<em style="color: #999;">Nenhum</em>';
    }
    
    try {
        $stmt = null;
        
        switch ($campo) {
            case 'id_aluno':
                $stmt = $conn->prepare("SELECT nome_completo, ra FROM ALUNO WHERE id_aluno = ?");
                $stmt->execute([$valor]);
                $resultado = $stmt->fetch();
                if ($resultado) {
                    return "<strong>{$resultado['nome_completo']}</strong> <small style='color: #666;'>(RA: {$resultado['ra']})</small>";
                }
                break;
                
            case 'id_admin':
            case 'aprovado_por':
            case 'validado_por':
            case 'rejeitado_por':
                $stmt = $conn->prepare("SELECT nome_completo FROM ADMINISTRADOR WHERE id_admin = ?");
                $stmt->execute([$valor]);
                $resultado = $stmt->fetch();
                if ($resultado) {
                    return "<strong>{$resultado['nome_completo']}</strong>";
                }
                break;
                
            case 'id_eleicao':
                $stmt = $conn->prepare("SELECT curso, semestre, status FROM ELEICAO WHERE id_eleicao = ?");
                $stmt->execute([$valor]);
                $resultado = $stmt->fetch();
                if ($resultado) {
                    $curso_nome = obterNomeCursoCompleto($resultado['curso']);
                    return "<strong>{$curso_nome}</strong> - {$resultado['semestre']}º Sem <small style='color: #666;'>({$resultado['status']})</small>";
                }
                break;
                
            case 'id_candidatura':
                $stmt = $conn->prepare("
                    SELECT a.nome_completo, a.ra, e.curso, e.semestre
                    FROM CANDIDATURA c
                    JOIN ALUNO a ON c.id_aluno = a.id_aluno
                    JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
                    WHERE c.id_candidatura = ?
                ");
                $stmt->execute([$valor]);
                $resultado = $stmt->fetch();
                if ($resultado) {
                    return "<strong>{$resultado['nome_completo']}</strong> ({$resultado['curso']}-{$resultado['semestre']}º)";
                }
                break;
        }
        
        // Se não encontrou ou campo não reconhecido, retornar ID formatado
        return "<code style='background: #f0f0f0; padding: 2px 6px; border-radius: 3px;'>ID: {$valor}</code>";
        
    } catch (PDOException $e) {
        error_log("Erro ao resolver ID {$campo}={$valor}: " . $e->getMessage());
        return "<code>ID: {$valor}</code>";
    }
}

/**
 * Formata status com badge colorido
 * 
 * @param string $status Status a formatar
 * @return string HTML com badge
 */
function formatarStatusComBadge($status) {
    $badges = [
        'pendente' => ['cor' => '#ffc107', 'icone' => 'clock', 'texto' => 'Pendente'],
        'deferido' => ['cor' => '#28a745', 'icone' => 'check', 'texto' => 'Deferido'],
        'indeferido' => ['cor' => '#dc3545', 'icone' => 'times', 'texto' => 'Indeferido'],
        'aprovado' => ['cor' => '#28a745', 'icone' => 'check', 'texto' => 'Aprovado'],
        'rejeitado' => ['cor' => '#dc3545', 'icone' => 'times', 'texto' => 'Rejeitado'],
        'recusado' => ['cor' => '#dc3545', 'icone' => 'times', 'texto' => 'Recusado'],
        'candidatura_aberta' => ['cor' => '#007bff', 'icone' => 'user-plus', 'texto' => 'Candidatura Aberta'],
        'votacao_aberta' => ['cor' => '#28a745', 'icone' => 'vote-yea', 'texto' => 'Votação Aberta'],
        'aguardando_finalizacao' => ['cor' => '#ffc107', 'icone' => 'hourglass-half', 'texto' => 'Aguardando Finalização'],
        'encerrada' => ['cor' => '#6c757d', 'icone' => 'flag-checkered', 'texto' => 'Encerrada']
    ];
    
    $badge = $badges[$status] ?? ['cor' => '#6c757d', 'icone' => 'info', 'texto' => ucfirst($status)];
    
    return "<span style='background: {$badge['cor']}; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block;'>
                <i class='fas fa-{$badge['icone']}'></i> {$badge['texto']}
            </span>";
}

/**
 * Formata data para legibilidade
 * 
 * @param string $data Data em formato SQL
 * @return string Data formatada
 */
function formatarDataLegivel($data) {
    if (empty($data)) {
        return '<em style="color: #999;">-</em>';
    }
    
    try {
        $timestamp = strtotime($data);
        
        // Formato: 15/01/2025 14:30
        $formatado = date('d/m/Y H:i', $timestamp);
        
        // Adicionar "hoje", "ontem" se relevante
        $hoje = date('Y-m-d');
        $data_apenas = date('Y-m-d', $timestamp);
        
        if ($data_apenas === $hoje) {
            $formatado = "<strong style='color: #007bff;'>Hoje</strong> às " . date('H:i', $timestamp);
        } elseif ($data_apenas === date('Y-m-d', strtotime('-1 day'))) {
            $formatado = "<strong style='color: #6c757d;'>Ontem</strong> às " . date('H:i', $timestamp);
        }
        
        return $formatado;
        
    } catch (Exception $e) {
        return htmlspecialchars($data);
    }
}

/**
 * Obtém nome completo do curso a partir da sigla
 * 
 * @param string $sigla Sigla do curso (DSM, GE, GPI)
 * @return string Nome completo
 */
function obterNomeCursoCompleto($sigla) {
    $cursos = [
        'DSM' => 'Desenvolvimento de Software Multiplataforma',
        'GE' => 'Gestão Empresarial',
        'GPI' => 'Gestão da Produção Industrial'
    ];
    
    return $cursos[$sigla] ?? $sigla;
}

/**
 * Gera HTML formatado para dados de auditoria
 * 
 * @param array $dados_enriquecidos Array com dados enriquecidos
 * @return string HTML formatado
 */
function gerarHTMLDadosAuditoria($dados_enriquecidos) {
    if (empty($dados_enriquecidos)) {
        return '<em style="color: #999;">Sem dados</em>';
    }
    
    $html = "<div style='background: #f8f9fa; padding: 10px; border-radius: 6px; font-size: 13px;'>";
    
    foreach ($dados_enriquecidos as $item) {
        $html .= "<div style='margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #e9ecef;'>";
        $html .= "<strong style='color: #495057; display: block; margin-bottom: 3px;'>{$item['rotulo']}:</strong>";
        $html .= "<div style='color: #212529; padding-left: 10px;'>{$item['valor']}</div>";
        $html .= "</div>";
    }
    
    // Remover última borda
    $html = preg_replace('/; border-bottom: 1px solid #e9ecef;\'>[^<]*<\/div>$/', '\'>', $html);
    
    $html .= "</div>";
    
    return $html;
}

/**
 * Gera comparação visual entre dados anteriores e novos
 * Mostra diff lado a lado com destaque de mudanças
 * 
 * @param PDO $conn Conexão PDO
 * @param string|null $json_anterior JSON com dados anteriores
 * @param string|null $json_novo JSON com dados novos
 * @param string $tabela Nome da tabela para contexto
 * @return string HTML com comparação visual
 */
function gerarComparacaoAuditoria($conn, $json_anterior, $json_novo, $tabela) {
    if (empty($json_anterior) && empty($json_novo)) {
        return '<em style="color: #999;">Sem dados para comparar</em>';
    }
    
    try {
        $dados_anteriores = $json_anterior ? json_decode($json_anterior, true) : [];
        $dados_novos = $json_novo ? json_decode($json_novo, true) : [];
        
        if (!is_array($dados_anteriores)) $dados_anteriores = [];
        if (!is_array($dados_novos)) $dados_novos = [];
        
        // Unir todas as chaves
        $todas_chaves = array_unique(array_merge(array_keys($dados_anteriores), array_keys($dados_novos)));
        
        $html = "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 13px;'>";
        
        // Coluna ANTES
        $html .= "<div style='background: #fff3cd; padding: 12px; border-radius: 6px; border: 2px solid #ffc107;'>";
        $html .= "<h4 style='margin: 0 0 10px 0; color: #856404; font-size: 14px;'><i class='fas fa-history'></i> ANTES</h4>";
        
        foreach ($todas_chaves as $chave) {
            $valor_anterior = $dados_anteriores[$chave] ?? null;
            $rotulo = traduzirCampoAuditoria($chave);
            $valor_formatado = formatarValorAuditoria($conn, $chave, $valor_anterior, $tabela);
            
            $html .= "<div style='margin-bottom: 8px;'>";
            $html .= "<strong style='color: #856404;'>{$rotulo}:</strong><br>";
            $html .= "<span style='color: #212529;'>{$valor_formatado}</span>";
            $html .= "</div>";
        }
        
        $html .= "</div>";
        
        // Coluna DEPOIS
        $html .= "<div style='background: #d4edda; padding: 12px; border-radius: 6px; border: 2px solid #28a745;'>";
        $html .= "<h4 style='margin: 0 0 10px 0; color: #155724; font-size: 14px;'><i class='fas fa-check'></i> DEPOIS</h4>";
        
        foreach ($todas_chaves as $chave) {
            $valor_novo = $dados_novos[$chave] ?? null;
            $valor_anterior = $dados_anteriores[$chave] ?? null;
            $rotulo = traduzirCampoAuditoria($chave);
            $valor_formatado = formatarValorAuditoria($conn, $chave, $valor_novo, $tabela);
            
            // Destacar se mudou
            $mudou = $valor_novo !== $valor_anterior;
            $estilo_destaque = $mudou ? "background: #fff; padding: 5px; margin-left: -5px; padding-left: 8px;" : "";
            
            $html .= "<div style='margin-bottom: 8px; {$estilo_destaque}'>";
            $html .= "<strong style='color: #155724;'>{$rotulo}:</strong>";
            if ($mudou) {
                $html .= " <span style='color: #28a745; font-size: 10px;'><i class='fas fa-arrow-right'></i> MUDOU</span>";
            }
            $html .= "<br><span style='color: #212529;'>{$valor_formatado}</span>";
            $html .= "</div>";
        }
        
        $html .= "</div>";
        $html .= "</div>";
        
        return $html;
        
    } catch (Exception $e) {
        error_log("Erro ao gerar comparação de auditoria: " . $e->getMessage());
        return '<em style="color: #999;">Erro ao processar comparação</em>';
    }
}