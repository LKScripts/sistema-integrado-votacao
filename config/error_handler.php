<?php
/**
 * Sistema de tratamento de erros do banco de dados
 *
 * Este arquivo contém funções para lidar com erros de forma segura,
 * sem expor detalhes internos do sistema aos usuários.
 */

/**
 * Executa uma query preparada e trata erros de forma segura
 *
 * @param PDOStatement $stmt Statement preparado
 * @param array $params Parâmetros para bind
 * @param string $erro_usuario Mensagem amigável para o usuário
 * @return bool True se sucesso, False se erro
 */
function executarQuerySegura($stmt, $params, $erro_usuario = "Erro ao processar operação. Tente novamente.") {
    try {
        return $stmt->execute($params);
    } catch (PDOException $e) {
        // Logar erro completo para debug (não mostrar ao usuário)
        error_log("Erro SQL: " . $e->getMessage());
        error_log("Query: " . $stmt->queryString);
        error_log("Params: " . json_encode($params));

        // Retornar false para permitir tratamento personalizado
        return false;
    }
}

/**
 * Prepara e executa uma query de forma segura
 *
 * @param PDO $conn Conexão com banco
 * @param string $query Query SQL
 * @param array $params Parâmetros
 * @param string $erro_usuario Mensagem para usuário
 * @return PDOStatement|false Statement ou false em caso de erro
 */
function executarQueryPreparada($conn, $query, $params = [], $erro_usuario = "Erro ao processar operação.") {
    try {
        $stmt = $conn->prepare($query);
        if ($stmt->execute($params)) {
            return $stmt;
        }
        return false;
    } catch (PDOException $e) {
        // Logar erro completo
        error_log("Erro SQL: " . $e->getMessage());
        error_log("Query: " . $query);
        error_log("Params: " . json_encode($params));

        return false;
    }
}

/**
 * Mapeia códigos de erro SQL para mensagens amigáveis
 *
 * @param PDOException $e Exceção do PDO
 * @return string Mensagem amigável
 */
function obterMensagemErroSQL($e) {
    // Códigos de erro comuns do MySQL/MariaDB
    $errorCode = $e->getCode();

    switch ($errorCode) {
        case 23000: // Integrity constraint violation
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return "Este registro já existe no sistema.";
            }
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                return "Não é possível realizar esta operação devido a dependências.";
            }
            return "Violação de integridade dos dados.";

        case '42S02': // Table not found
            error_log("ERRO CRÍTICO: Tabela não encontrada - " . $e->getMessage());
            return "Erro de configuração do sistema. Contate o administrador.";

        case '42S22': // Column not found
            error_log("ERRO CRÍTICO: Coluna não encontrada - " . $e->getMessage());
            return "Erro de configuração do sistema. Contate o administrador.";

        case '08S01': // Communication link failure
            return "Erro de conexão com o banco de dados. Tente novamente.";

        default:
            // Logar erro desconhecido
            error_log("Erro SQL não mapeado: " . $errorCode . " - " . $e->getMessage());
            return "Erro ao processar operação. Tente novamente ou contate o suporte.";
    }
}

/**
 * Trata erros de INSERT/UPDATE/DELETE com try-catch
 *
 * @param callable $callback Função que executa a operação
 * @param string $erro_padrao Mensagem padrão de erro
 * @return array ['sucesso' => bool, 'mensagem' => string, 'dados' => mixed]
 */
function executarOperacaoDB($callback, $erro_padrao = "Erro ao processar operação.") {
    try {
        $resultado = $callback();
        return [
            'sucesso' => true,
            'mensagem' => '',
            'dados' => $resultado
        ];
    } catch (PDOException $e) {
        // Logar erro
        error_log("Erro em operação DB: " . $e->getMessage());

        return [
            'sucesso' => false,
            'mensagem' => obterMensagemErroSQL($e),
            'dados' => null
        ];
    }
}

/**
 * Wrapper para prepared statements com tratamento automático
 *
 * Uso:
 * $resultado = querySegura($conn, "SELECT * FROM ALUNO WHERE id_aluno = ?", [1]);
 * if ($resultado['sucesso']) {
 *     $aluno = $resultado['dados']->fetch();
 * } else {
 *     echo $resultado['mensagem'];
 * }
 */
function querySegura($conn, $query, $params = []) {
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        return [
            'sucesso' => true,
            'mensagem' => '',
            'dados' => $stmt
        ];
    } catch (PDOException $e) {
        error_log("Erro SQL: " . $e->getMessage());
        error_log("Query: " . $query);

        return [
            'sucesso' => false,
            'mensagem' => obterMensagemErroSQL($e),
            'dados' => null
        ];
    }
}
?>
