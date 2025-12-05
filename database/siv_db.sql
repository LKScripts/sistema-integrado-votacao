-- =====================================================
-- SETUP OTIMIZADO - SISTEMA INTEGRADO DE VOTAÇÃO (SIV)
-- =====================================================
-- Data: 2025-12-02
-- Compatibilidade: MariaDB 10.4.32+ / MySQL 8.0+
-- Idempotente (pode executar múltiplas vezes com segurança)
--
--
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- 1. CRIAR/USAR BANCO DE DADOS
-- =====================================================
CREATE DATABASE IF NOT EXISTS `siv_db`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `siv_db`;

-- =====================================================
-- 2. VERIFICAR PRÉ-REQUISITOS
-- =====================================================
-- Verificar versão do MariaDB/MySQL
SET @versao_ok = (
    SELECT VERSION() >= '10.2.1' OR VERSION() >= '8.0.0'
);

-- Habilitar event scheduler (necessário para automação)
SET GLOBAL event_scheduler = ON;

-- =====================================================
-- 3. TABELAS PRINCIPAIS
-- =====================================================

-- Tabela: ADMINISTRADOR
CREATE TABLE IF NOT EXISTS `administrador` (
  `id_admin` int(11) NOT NULL AUTO_INCREMENT,
  `nome_completo` varchar(255) NOT NULL,
  `email_corporativo` varchar(255) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `aprovado_por` int(11) DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `email_confirmado` tinyint(1) DEFAULT 1,
  `token_confirmacao` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `email_corporativo` (`email_corporativo`),
  KEY `idx_email_admin` (`email_corporativo`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_token_confirmacao_admin` (`token_confirmacao`),
  KEY `idx_ultimo_acesso_admin` (`ultimo_acesso` DESC),
  KEY `fk_admin_aprovador` (`aprovado_por`),
  CONSTRAINT `fk_admin_aprovador` FOREIGN KEY (`aprovado_por`) REFERENCES `administrador` (`id_admin`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: ALUNO
CREATE TABLE IF NOT EXISTS `aluno` (
  `id_aluno` int(11) NOT NULL AUTO_INCREMENT,
  `ra` varchar(20) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `email_institucional` varchar(255) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `curso` varchar(100) NOT NULL,
  `semestre` int(11) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `email_confirmado` tinyint(1) DEFAULT 1,
  `token_confirmacao` varchar(64) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_aluno`),
  UNIQUE KEY `ra` (`ra`),
  UNIQUE KEY `email_institucional` (`email_institucional`),
  KEY `idx_curso_semestre` (`curso`,`semestre`),
  KEY `idx_email` (`email_institucional`),
  KEY `idx_ra` (`ra`),
  KEY `idx_ativo_aluno` (`ativo`),
  KEY `idx_token_confirmacao_aluno` (`token_confirmacao`),
  KEY `idx_ultimo_acesso` (`ultimo_acesso` DESC),
  CONSTRAINT `chk_semestre` CHECK (`semestre` BETWEEN 1 AND 6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: ELEICAO
CREATE TABLE IF NOT EXISTS `eleicao` (
  `id_eleicao` int(11) NOT NULL AUTO_INCREMENT,
  `curso` varchar(100) NOT NULL,
  `semestre` int(11) NOT NULL,
  `data_inicio_candidatura` date NOT NULL,
  `data_fim_candidatura` date NOT NULL,
  `data_inicio_votacao` date NOT NULL,
  `data_fim_votacao` date NOT NULL,
  `status` enum('candidatura_aberta','votacao_aberta','aguardando_finalizacao','encerrada') DEFAULT 'candidatura_aberta',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `criado_por` int(11) NOT NULL,
  PRIMARY KEY (`id_eleicao`),
  UNIQUE KEY `uk_eleicao_periodo` (`curso`,`semestre`,`data_inicio_candidatura`),
  KEY `idx_curso_semestre_eleicao` (`curso`,`semestre`),
  KEY `idx_status` (`status`),
  KEY `idx_datas` (`data_inicio_votacao`,`data_fim_votacao`),
  KEY `idx_status_datas` (`status`,`data_inicio_votacao`,`data_fim_votacao`),
  KEY `fk_eleicao_criador` (`criado_por`),
  CONSTRAINT `fk_eleicao_criador` FOREIGN KEY (`criado_por`) REFERENCES `administrador` (`id_admin`) ON UPDATE CASCADE,
  CONSTRAINT `chk_semestre_eleicao` CHECK (`semestre` BETWEEN 1 AND 6),
  CONSTRAINT `chk_datas_candidatura` CHECK (`data_fim_candidatura` > `data_inicio_candidatura`),
  CONSTRAINT `chk_datas_votacao` CHECK (`data_fim_votacao` > `data_inicio_votacao`),
  CONSTRAINT `chk_ordem_fases` CHECK (`data_inicio_votacao` >= `data_fim_candidatura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: CANDIDATURA
CREATE TABLE IF NOT EXISTS `candidatura` (
  `id_candidatura` int(11) NOT NULL AUTO_INCREMENT,
  `id_eleicao` int(11) NOT NULL,
  `id_aluno` int(11) NOT NULL,
  `proposta` text DEFAULT NULL,
  `foto_candidato` varchar(255) DEFAULT NULL,
  `status_validacao` enum('pendente','deferido','indeferido') DEFAULT 'pendente',
  `data_inscricao` timestamp NOT NULL DEFAULT current_timestamp(),
  `validado_por` int(11) DEFAULT NULL,
  `data_validacao` timestamp NULL DEFAULT NULL,
  `justificativa_indeferimento` text DEFAULT NULL,
  PRIMARY KEY (`id_candidatura`),
  UNIQUE KEY `uk_candidatura_unica` (`id_eleicao`,`id_aluno`),
  KEY `idx_eleicao` (`id_eleicao`),
  KEY `idx_aluno_candidato` (`id_aluno`),
  KEY `idx_status_validacao` (`status_validacao`),
  KEY `idx_eleicao_status` (`id_eleicao`,`status_validacao`),
  KEY `idx_aluno_eleicao_status` (`id_aluno`,`id_eleicao`,`status_validacao`),
  KEY `fk_candidatura_validador` (`validado_por`),
  CONSTRAINT `fk_candidatura_aluno` FOREIGN KEY (`id_aluno`) REFERENCES `aluno` (`id_aluno`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_candidatura_eleicao` FOREIGN KEY (`id_eleicao`) REFERENCES `eleicao` (`id_eleicao`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_candidatura_validador` FOREIGN KEY (`validado_por`) REFERENCES `administrador` (`id_admin`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: VOTO
CREATE TABLE IF NOT EXISTS `voto` (
  `id_voto` int(11) NOT NULL AUTO_INCREMENT,
  `id_eleicao` int(11) NOT NULL,
  `id_aluno` int(11) NOT NULL COMMENT 'Aluno que está votando',
  `id_candidatura` int(11) DEFAULT NULL COMMENT 'Candidatura que recebeu o voto (NULL = voto branco)',
  `data_hora_voto` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_votante` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_voto`),
  UNIQUE KEY `uk_voto_unico` (`id_eleicao`,`id_aluno`),
  KEY `idx_eleicao_voto` (`id_eleicao`),
  KEY `idx_candidatura` (`id_candidatura`),
  KEY `idx_aluno_voto` (`id_aluno`),
  KEY `idx_data_voto` (`data_hora_voto`),
  KEY `idx_eleicao_candidatura` (`id_eleicao`,`id_candidatura`),
  KEY `idx_aluno_eleicao` (`id_aluno`,`id_eleicao`),
  KEY `idx_votos_brancos` (`id_candidatura`,`id_eleicao`),
  CONSTRAINT `fk_voto_aluno` FOREIGN KEY (`id_aluno`) REFERENCES `aluno` (`id_aluno`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_voto_candidatura` FOREIGN KEY (`id_candidatura`) REFERENCES `candidatura` (`id_candidatura`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_voto_eleicao` FOREIGN KEY (`id_eleicao`) REFERENCES `eleicao` (`id_eleicao`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: RESULTADO
CREATE TABLE IF NOT EXISTS `resultado` (
  `id_resultado` int(11) NOT NULL AUTO_INCREMENT,
  `id_eleicao` int(11) NOT NULL,
  `id_representante` int(11) DEFAULT NULL,
  `id_suplente` int(11) DEFAULT NULL,
  `votos_representante` int(11) NOT NULL,
  `votos_suplente` int(11) DEFAULT NULL,
  `total_votantes` int(11) NOT NULL,
  `total_aptos` int(11) NOT NULL,
  `percentual_participacao` decimal(5,2) NOT NULL,
  `data_apuracao` timestamp NOT NULL DEFAULT current_timestamp(),
  `gerado_por` int(11) NOT NULL,
  PRIMARY KEY (`id_resultado`),
  UNIQUE KEY `id_eleicao` (`id_eleicao`),
  KEY `idx_eleicao_resultado` (`id_eleicao`),
  KEY `idx_data_apuracao` (`data_apuracao` DESC),
  KEY `fk_resultado_representante` (`id_representante`),
  KEY `fk_resultado_suplente` (`id_suplente`),
  KEY `fk_resultado_gerador` (`gerado_por`),
  CONSTRAINT `fk_resultado_eleicao` FOREIGN KEY (`id_eleicao`) REFERENCES `eleicao` (`id_eleicao`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_resultado_gerador` FOREIGN KEY (`gerado_por`) REFERENCES `administrador` (`id_admin`) ON UPDATE CASCADE,
  CONSTRAINT `fk_resultado_representante` FOREIGN KEY (`id_representante`) REFERENCES `candidatura` (`id_candidatura`) ON UPDATE CASCADE,
  CONSTRAINT `fk_resultado_suplente` FOREIGN KEY (`id_suplente`) REFERENCES `candidatura` (`id_candidatura`) ON UPDATE CASCADE,
  CONSTRAINT `chk_percentual` CHECK (`percentual_participacao` BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: ATA
CREATE TABLE IF NOT EXISTS `ata` (
  `id_ata` int(11) NOT NULL AUTO_INCREMENT,
  `id_eleicao` int(11) NOT NULL,
  `id_resultado` int(11) NOT NULL,
  `arquivo_pdf` varchar(255) NOT NULL,
  `hash_integridade` varchar(64) NOT NULL,
  `conteudo_json` text NOT NULL,
  `data_geracao` timestamp NOT NULL DEFAULT current_timestamp(),
  `gerado_por` int(11) NOT NULL,
  PRIMARY KEY (`id_ata`),
  UNIQUE KEY `id_eleicao` (`id_eleicao`),
  KEY `idx_eleicao_ata` (`id_eleicao`),
  KEY `idx_hash` (`hash_integridade`),
  KEY `idx_data_geracao` (`data_geracao` DESC),
  KEY `fk_ata_resultado` (`id_resultado`),
  KEY `fk_ata_gerador` (`gerado_por`),
  CONSTRAINT `fk_ata_eleicao` FOREIGN KEY (`id_eleicao`) REFERENCES `eleicao` (`id_eleicao`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ata_gerador` FOREIGN KEY (`gerado_por`) REFERENCES `administrador` (`id_admin`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ata_resultado` FOREIGN KEY (`id_resultado`) REFERENCES `resultado` (`id_resultado`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: AUDITORIA
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
  `id_admin` int(11) DEFAULT NULL,
  `id_eleicao` int(11) DEFAULT NULL,
  `tabela` varchar(50) NOT NULL,
  `operacao` enum('INSERT','UPDATE','DELETE','LOGIN','LOGOUT') NOT NULL,
  `descricao` text NOT NULL,
  `dados_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_anteriores`)),
  `dados_novos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_novos`)),
  `ip_origem` varchar(45) DEFAULT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_tabela` (`tabela`),
  KEY `idx_data` (`data_hora`),
  KEY `idx_admin` (`id_admin`),
  KEY `idx_operacao` (`operacao`),
  KEY `idx_admin_data` (`id_admin`,`data_hora`),
  KEY `idx_operacao_data` (`operacao`,`data_hora`),
  KEY `fk_auditoria_eleicao` (`id_eleicao`),
  CONSTRAINT `fk_auditoria_admin` FOREIGN KEY (`id_admin`) REFERENCES `administrador` (`id_admin`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_auditoria_eleicao` FOREIGN KEY (`id_eleicao`) REFERENCES `eleicao` (`id_eleicao`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: EMAIL_CONFIRMACAO (tokens de verificação de email)
CREATE TABLE IF NOT EXISTS `email_confirmacao` (
  `id_token` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `tipo_usuario` enum('aluno','admin') NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_expiracao` datetime NOT NULL,
  `confirmado` tinyint(1) DEFAULT 0,
  `data_confirmacao` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_token`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_email` (`email`),
  KEY `idx_tipo_usuario` (`tipo_usuario`,`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: LOGIN_TENTATIVAS (rate limiting de login)
CREATE TABLE IF NOT EXISTS `login_tentativas` (
  `id_tentativa` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `ip_origem` varchar(45) NOT NULL,
  `data_tentativa` timestamp NOT NULL DEFAULT current_timestamp(),
  `sucesso` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_tentativa`),
  KEY `idx_email_ip` (`email`,`ip_origem`),
  KEY `idx_data` (`data_tentativa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: SOLICITACAO_MUDANCA
CREATE TABLE IF NOT EXISTS `solicitacao_mudanca` (
  `id_solicitacao` int(11) NOT NULL AUTO_INCREMENT,
  `id_aluno` int(11) NOT NULL,
  `tipo_mudanca` enum('curso','semestre','ambos') NOT NULL COMMENT 'Tipo de mudança solicitada',
  `curso_atual` varchar(100) NOT NULL,
  `semestre_atual` int(11) NOT NULL,
  `curso_novo` varchar(100) DEFAULT NULL COMMENT 'Novo curso (NULL se não mudar)',
  `semestre_novo` int(11) DEFAULT NULL COMMENT 'Novo semestre (NULL se não mudar)',
  `justificativa` text DEFAULT NULL COMMENT 'Motivo da solicitação',
  `status` enum('pendente','aprovado','recusado') NOT NULL DEFAULT 'pendente',
  `data_solicitacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_resposta` timestamp NULL DEFAULT NULL,
  `id_admin_responsavel` int(11) DEFAULT NULL COMMENT 'Admin que aprovou/recusou',
  `motivo_recusa` text DEFAULT NULL COMMENT 'Justificativa em caso de recusa',
  `observacoes_admin` text DEFAULT NULL COMMENT 'Notas internas do admin',
  PRIMARY KEY (`id_solicitacao`),
  KEY `idx_aluno_solicitacao` (`id_aluno`),
  KEY `idx_status_solicitacao` (`status`),
  KEY `idx_data_solicitacao` (`data_solicitacao` DESC),
  KEY `idx_admin_responsavel` (`id_admin_responsavel`),
  KEY `idx_status_data` (`status`,`data_solicitacao`),
  CONSTRAINT `fk_solicitacao_aluno` FOREIGN KEY (`id_aluno`) REFERENCES `aluno` (`id_aluno`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_solicitacao_admin` FOREIGN KEY (`id_admin_responsavel`) REFERENCES `administrador` (`id_admin`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_semestre_novo` CHECK (`semestre_novo` IS NULL OR `semestre_novo` BETWEEN 1 AND 6),
  CONSTRAINT `chk_mudanca_valida` CHECK (
    (`tipo_mudanca` = 'curso' AND `curso_novo` IS NOT NULL) OR
    (`tipo_mudanca` = 'semestre' AND `semestre_novo` IS NOT NULL) OR
    (`tipo_mudanca` = 'ambos' AND `curso_novo` IS NOT NULL AND `semestre_novo` IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. TRIGGERS
-- =====================================================

DELIMITER $$

-- Trigger: Impedir alteração de ATA após geração
DROP TRIGGER IF EXISTS `trg_impede_alteracao_ata`$$
CREATE TRIGGER `trg_impede_alteracao_ata` BEFORE UPDATE ON `ata` FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Ata não pode ser alterada após geração. Mantenha integridade do documento.';
END$$

-- Trigger: Impedir alteração de RESULTADO após geração
DROP TRIGGER IF EXISTS `trg_impede_alteracao_resultado`$$
CREATE TRIGGER `trg_impede_alteracao_resultado` BEFORE UPDATE ON `resultado` FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Resultado não pode ser alterado após geração. Use auditoria para correções.';
END$$

-- Trigger: Auditoria de validação de candidatura
DROP TRIGGER IF EXISTS `trg_auditoria_validacao_candidatura`$$
CREATE TRIGGER `trg_auditoria_validacao_candidatura` AFTER UPDATE ON `candidatura` FOR EACH ROW
BEGIN
    IF OLD.status_validacao != NEW.status_validacao THEN
        INSERT INTO AUDITORIA (
            id_admin,
            id_eleicao,
            tabela,
            operacao,
            descricao,
            dados_anteriores,
            dados_novos
        ) VALUES (
            NEW.validado_por,
            NEW.id_eleicao,
            'CANDIDATURA',
            'UPDATE',
            CONCAT('Candidatura ', NEW.status_validacao, ' - ID: ', NEW.id_candidatura),
            JSON_OBJECT('status', OLD.status_validacao),
            JSON_OBJECT('status', NEW.status_validacao, 'justificativa', NEW.justificativa_indeferimento)
        );
    END IF;
END$$

-- Trigger: IMUTABILIDADE - Impedir UPDATE em registros de auditoria
-- IMPORTANTE: Logs de auditoria NÃO podem ser alterados após criação
-- Garante integridade e rastreabilidade completa do sistema
DROP TRIGGER IF EXISTS `trg_auditoria_impedir_update`$$
CREATE TRIGGER `trg_auditoria_impedir_update` BEFORE UPDATE ON `auditoria` FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'PROIBIDO: Registros de auditoria são IMUTÁVEIS e não podem ser alterados. Logs devem permanecer íntegros para rastreabilidade.';
END$$

-- Trigger: IMUTABILIDADE - Impedir DELETE em registros de auditoria
-- IMPORTANTE: Logs de auditoria NÃO podem ser deletados
-- Garante preservação histórica completa de todas as operações
DROP TRIGGER IF EXISTS `trg_auditoria_impedir_delete`$$
CREATE TRIGGER `trg_auditoria_impedir_delete` BEFORE DELETE ON `auditoria` FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'PROIBIDO: Registros de auditoria são IMUTÁVEIS e não podem ser deletados. Logs devem ser preservados permanentemente.';
END$$

-- Trigger: Validar candidatura na mesma turma
DROP TRIGGER IF EXISTS `trg_valida_candidatura_turma`$$
CREATE TRIGGER `trg_valida_candidatura_turma` BEFORE INSERT ON `candidatura` FOR EACH ROW
BEGIN
    DECLARE v_curso_aluno VARCHAR(100);
    DECLARE v_semestre_aluno INT;
    DECLARE v_curso_eleicao VARCHAR(100);
    DECLARE v_semestre_eleicao INT;

    SELECT curso, semestre INTO v_curso_aluno, v_semestre_aluno
    FROM ALUNO WHERE id_aluno = NEW.id_aluno;

    SELECT curso, semestre INTO v_curso_eleicao, v_semestre_eleicao
    FROM ELEICAO WHERE id_eleicao = NEW.id_eleicao;

    IF v_curso_aluno != v_curso_eleicao OR v_semestre_aluno != v_semestre_eleicao THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Aluno só pode se candidatar em eleição da sua turma';
    END IF;
END$$

-- Trigger: Validar voto apenas em candidaturas deferidas
DROP TRIGGER IF EXISTS `trg_valida_voto_candidatura_deferida`$$
CREATE TRIGGER `trg_valida_voto_candidatura_deferida` BEFORE INSERT ON `voto` FOR EACH ROW
BEGIN
    DECLARE v_status_candidatura VARCHAR(20);

    -- Permitir voto branco (id_candidatura NULL)
    IF NEW.id_candidatura IS NOT NULL THEN
        SELECT status_validacao INTO v_status_candidatura
        FROM CANDIDATURA WHERE id_candidatura = NEW.id_candidatura;

        IF v_status_candidatura != 'deferido' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Só é possível votar em candidaturas deferidas';
        END IF;
    END IF;
END$$

-- Trigger: Validar voto na mesma turma
DROP TRIGGER IF EXISTS `trg_valida_voto_turma`$$
CREATE TRIGGER `trg_valida_voto_turma` BEFORE INSERT ON `voto` FOR EACH ROW
BEGIN
    DECLARE v_curso_votante VARCHAR(100);
    DECLARE v_semestre_votante INT;
    DECLARE v_curso_eleicao VARCHAR(100);
    DECLARE v_semestre_eleicao INT;
    DECLARE v_status_eleicao VARCHAR(20);

    SELECT curso, semestre INTO v_curso_votante, v_semestre_votante
    FROM ALUNO WHERE id_aluno = NEW.id_aluno;

    SELECT curso, semestre, status INTO v_curso_eleicao, v_semestre_eleicao, v_status_eleicao
    FROM ELEICAO WHERE id_eleicao = NEW.id_eleicao;

    IF v_status_eleicao != 'votacao_aberta' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Votação não está aberta para esta eleição';
    END IF;

    IF v_curso_votante != v_curso_eleicao OR v_semestre_votante != v_semestre_eleicao THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Aluno só pode votar em eleição da sua turma';
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- 5. STORED PROCEDURES - OTIMIZADAS (SEM CURSORS)
-- =====================================================

DELIMITER $$

-- Procedure: Finalizar Eleição Individual
DROP PROCEDURE IF EXISTS `sp_finalizar_eleicao`$$
CREATE PROCEDURE `sp_finalizar_eleicao` (IN `p_id_eleicao` INT, IN `p_id_admin` INT)
BEGIN
    DECLARE v_total_aptos INT DEFAULT 0;
    DECLARE v_total_votantes INT DEFAULT 0;
    DECLARE v_id_representante INT DEFAULT NULL;
    DECLARE v_votos_representante INT DEFAULT 0;
    DECLARE v_id_suplente INT DEFAULT NULL;
    DECLARE v_votos_suplente INT DEFAULT 0;
    DECLARE v_percentual DECIMAL(5,2) DEFAULT 0;
    DECLARE v_count_candidatos INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erro ao finalizar eleição';
    END;

    START TRANSACTION;

    -- Contar alunos aptos a votar
    SELECT COUNT(*) INTO v_total_aptos
    FROM ALUNO a
    JOIN ELEICAO e ON a.curso = e.curso AND a.semestre = e.semestre
    WHERE e.id_eleicao = p_id_eleicao;

    -- Contar votantes
    SELECT COUNT(*) INTO v_total_votantes
    FROM VOTO WHERE id_eleicao = p_id_eleicao;

    -- Calcular percentual
    SET v_percentual = IF(v_total_aptos > 0,
                          (v_total_votantes / v_total_aptos) * 100,
                          0);

    -- Verificar se existem candidatos deferidos
    SELECT COUNT(*) INTO v_count_candidatos
    FROM CANDIDATURA
    WHERE id_eleicao = p_id_eleicao
      AND status_validacao = 'deferido';

    -- Obter representante apenas se houver candidatos
    IF v_count_candidatos > 0 THEN
        SELECT c.id_candidatura, IFNULL(COUNT(v.id_voto), 0)
        INTO v_id_representante, v_votos_representante
        FROM CANDIDATURA c
        LEFT JOIN VOTO v ON c.id_candidatura = v.id_candidatura
        WHERE c.id_eleicao = p_id_eleicao
          AND c.status_validacao = 'deferido'
        GROUP BY c.id_candidatura
        ORDER BY COUNT(v.id_voto) DESC
        LIMIT 1;

        -- Obter suplente se houver mais de 1 candidato
        IF v_count_candidatos > 1 THEN
            SELECT c.id_candidatura, IFNULL(COUNT(v.id_voto), 0)
            INTO v_id_suplente, v_votos_suplente
            FROM CANDIDATURA c
            LEFT JOIN VOTO v ON c.id_candidatura = v.id_candidatura
            WHERE c.id_eleicao = p_id_eleicao
              AND c.status_validacao = 'deferido'
              AND c.id_candidatura != v_id_representante
            GROUP BY c.id_candidatura
            ORDER BY COUNT(v.id_voto) DESC
            LIMIT 1;
        END IF;
    END IF;

    -- Inserir resultado
    INSERT INTO RESULTADO (
        id_eleicao,
        id_representante,
        id_suplente,
        votos_representante,
        votos_suplente,
        total_votantes,
        total_aptos,
        percentual_participacao,
        gerado_por
    ) VALUES (
        p_id_eleicao,
        v_id_representante,
        v_id_suplente,
        v_votos_representante,
        v_votos_suplente,
        v_total_votantes,
        v_total_aptos,
        v_percentual,
        p_id_admin
    );

    -- Atualizar status da eleição
    UPDATE ELEICAO
    SET status = 'encerrada'
    WHERE id_eleicao = p_id_eleicao;

    -- Registrar na auditoria
    INSERT INTO AUDITORIA (
        id_admin,
        id_eleicao,
        tabela,
        operacao,
        descricao,
        ip_origem,
        data_hora
    ) VALUES (
        p_id_admin,
        p_id_eleicao,
        'ELEICAO',
        'UPDATE',
        CONCAT('Eleição ID ', p_id_eleicao, ' finalizada'),
        '127.0.0.1',
        NOW()
    );

    COMMIT;
END$$

-- Procedure: Atualizar Status de Eleições (OTIMIZADA)
DROP PROCEDURE IF EXISTS `sp_atualizar_status_eleicoes`$$
CREATE PROCEDURE `sp_atualizar_status_eleicoes`()
BEGIN
    DECLARE v_eleicoes_atualizadas INT DEFAULT 0;

    -- Atualizar para 'votacao_aberta'
    UPDATE ELEICAO
    SET status = 'votacao_aberta'
    WHERE status = 'candidatura_aberta'
      AND NOW() >= data_inicio_votacao
      AND NOW() < data_fim_votacao;

    SET v_eleicoes_atualizadas = ROW_COUNT();

    IF v_eleicoes_atualizadas > 0 THEN
        INSERT INTO AUDITORIA (id_admin, operacao, descricao, ip_origem, data_hora)
        VALUES (
            1,
            'UPDATE',
            CONCAT(v_eleicoes_atualizadas, ' eleição(ões) mudou(aram) para votacao_aberta'),
            '127.0.0.1',
            NOW()
        );
    END IF;

    -- Marcar para finalização
    UPDATE ELEICAO
    SET status = 'aguardando_finalizacao'
    WHERE status = 'votacao_aberta'
      AND NOW() >= data_fim_votacao;

    SET v_eleicoes_atualizadas = ROW_COUNT();

    IF v_eleicoes_atualizadas > 0 THEN
        INSERT INTO AUDITORIA (id_admin, operacao, descricao, ip_origem, data_hora)
        VALUES (
            1,
            'UPDATE',
            CONCAT(v_eleicoes_atualizadas, ' eleição(ões) finalizou(aram) votação'),
            '127.0.0.1',
            NOW()
        );
    END IF;
END$$

-- Procedure: Auto-finalizar Eleições (OTIMIZADA - SEM CURSOR)
DROP PROCEDURE IF EXISTS `sp_auto_finalizar_eleicoes`$$
CREATE PROCEDURE `sp_auto_finalizar_eleicoes`()
BEGIN
    DECLARE v_id_eleicao INT;
    DECLARE v_done INT DEFAULT 0;

    -- Usar query direta ao invés de cursor para eleições em lote
    -- Muito mais rápido para múltiplas eleições
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_eleicoes_finalizar (
        id_eleicao INT PRIMARY KEY
    );

    TRUNCATE TABLE temp_eleicoes_finalizar;

    INSERT INTO temp_eleicoes_finalizar (id_eleicao)
    SELECT id_eleicao
    FROM ELEICAO
    WHERE status = 'aguardando_finalizacao'
    LIMIT 50; -- Processar no máximo 50 por vez

    -- Processar cada eleição
    WHILE EXISTS (SELECT 1 FROM temp_eleicoes_finalizar) DO
        SELECT id_eleicao INTO v_id_eleicao
        FROM temp_eleicoes_finalizar
        LIMIT 1;

        -- Finalizar
        CALL sp_finalizar_eleicao(v_id_eleicao, 1);

        -- Remover da temp table
        DELETE FROM temp_eleicoes_finalizar WHERE id_eleicao = v_id_eleicao;
    END WHILE;

    DROP TEMPORARY TABLE IF EXISTS temp_eleicoes_finalizar;
END$$

-- Procedure: Gerenciar Eleições (Master)
DROP PROCEDURE IF EXISTS `sp_gerenciar_eleicoes_automaticamente`$$
CREATE PROCEDURE `sp_gerenciar_eleicoes_automaticamente`()
BEGIN
    CALL sp_atualizar_status_eleicoes();
    CALL sp_auto_finalizar_eleicoes();
END$$

DELIMITER ;

-- =====================================================
-- 6. VIEWS OTIMIZADAS (MERGE ALGORITHM)
-- =====================================================

-- View: Alunos Aptos para Votação
DROP VIEW IF EXISTS `v_alunos_aptos_votacao`;
CREATE ALGORITHM=MERGE
DEFINER=`root`@`localhost`
SQL SECURITY DEFINER
VIEW `v_alunos_aptos_votacao` AS
SELECT
    e.id_eleicao,
    e.curso,
    e.semestre,
    a.id_aluno,
    a.nome_completo,
    a.ra,
    a.email_institucional,
    CASE WHEN v.id_voto IS NOT NULL THEN 'SIM' ELSE 'NÃO' END AS ja_votou
FROM eleicao e
INNER JOIN aluno a ON a.curso = e.curso AND a.semestre = e.semestre
LEFT JOIN voto v ON e.id_eleicao = v.id_eleicao AND a.id_aluno = v.id_aluno;

-- View: Candidatos Deferidos
DROP VIEW IF EXISTS `v_candidatos_deferidos`;
CREATE ALGORITHM=MERGE
DEFINER=`root`@`localhost`
SQL SECURITY DEFINER
VIEW `v_candidatos_deferidos` AS
SELECT
    c.id_candidatura,
    c.id_eleicao,
    a.id_aluno,
    a.nome_completo,
    a.ra,
    c.proposta,
    c.foto_candidato,
    e.curso,
    e.semestre,
    c.data_inscricao
FROM candidatura c
INNER JOIN aluno a ON c.id_aluno = a.id_aluno
INNER JOIN eleicao e ON c.id_eleicao = e.id_eleicao
WHERE c.status_validacao = 'deferido';

-- View: Contagem de Votos (OTIMIZADA)
DROP VIEW IF EXISTS `v_contagem_votos`;
CREATE ALGORITHM=MERGE
DEFINER=`root`@`localhost`
SQL SECURITY DEFINER
VIEW `v_contagem_votos` AS
SELECT
    c.id_candidatura,
    c.id_eleicao,
    a.nome_completo AS nome_candidato,
    a.ra,
    e.curso,
    e.semestre,
    COUNT(v.id_voto) AS total_votos,
    e.status AS status_eleicao
FROM candidatura c
INNER JOIN aluno a ON c.id_aluno = a.id_aluno
INNER JOIN eleicao e ON c.id_eleicao = e.id_eleicao
LEFT JOIN voto v ON c.id_candidatura = v.id_candidatura
WHERE c.status_validacao = 'deferido'
GROUP BY c.id_candidatura, c.id_eleicao, a.nome_completo, a.ra, e.curso, e.semestre, e.status
ORDER BY e.id_eleicao ASC, COUNT(v.id_voto) DESC;

-- View: Eleições Ativas
DROP VIEW IF EXISTS `v_eleicoes_ativas`;
CREATE ALGORITHM=MERGE
DEFINER=`root`@`localhost`
SQL SECURITY DEFINER
VIEW `v_eleicoes_ativas` AS
SELECT
    e.id_eleicao,
    e.curso,
    e.semestre,
    e.status,
    e.data_inicio_candidatura,
    e.data_fim_candidatura,
    e.data_inicio_votacao,
    e.data_fim_votacao,
    COUNT(DISTINCT c.id_candidatura) AS total_candidatos,
    COUNT(DISTINCT CASE WHEN c.status_validacao = 'deferido' THEN c.id_candidatura END) AS candidatos_deferidos,
    COUNT(DISTINCT v.id_voto) AS total_votos
FROM eleicao e
LEFT JOIN candidatura c ON e.id_eleicao = c.id_eleicao
LEFT JOIN voto v ON e.id_eleicao = v.id_eleicao
WHERE e.status <> 'encerrada'
GROUP BY e.id_eleicao;

-- View: Resultados Completos (pode ser pesada, manter algorithm=undefined)
DROP VIEW IF EXISTS `v_resultados_completos`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`localhost`
SQL SECURITY DEFINER
VIEW `v_resultados_completos` AS
SELECT
    r.id_resultado,
    e.id_eleicao,
    e.curso,
    e.semestre,
    a_rep.nome_completo AS representante,
    a_rep.ra AS ra_representante,
    r.votos_representante,
    a_sup.nome_completo AS suplente,
    a_sup.ra AS ra_suplente,
    r.votos_suplente,
    r.total_votantes,
    r.total_aptos,
    r.percentual_participacao,
    r.data_apuracao,
    adm.nome_completo AS apurado_por
FROM resultado r
INNER JOIN eleicao e ON r.id_eleicao = e.id_eleicao
LEFT JOIN candidatura c_rep ON r.id_representante = c_rep.id_candidatura
LEFT JOIN aluno a_rep ON c_rep.id_aluno = a_rep.id_aluno
LEFT JOIN candidatura c_sup ON r.id_suplente = c_sup.id_candidatura
LEFT JOIN aluno a_sup ON c_sup.id_aluno = a_sup.id_aluno
INNER JOIN administrador adm ON r.gerado_por = adm.id_admin;

-- =====================================================
-- 7. EVENT SCHEDULER - AUTOMAÇÃO
-- =====================================================

-- Remover evento se existir
DROP EVENT IF EXISTS `evt_gerenciar_eleicoes`;

DELIMITER $$

-- Evento: Gerenciar eleições automaticamente (a cada 1 hora)
CREATE EVENT `evt_gerenciar_eleicoes`
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    CALL sp_gerenciar_eleicoes_automaticamente();
END$$

DELIMITER ;

-- =====================================================
-- 7.2. EVENTO DE PROGRESSÃO SEMESTRAL AUTOMÁTICA
-- =====================================================

-- Remover evento se existir
DROP EVENT IF EXISTS `evt_progressao_semestral`;

DELIMITER $$

-- Evento: Progressão semestral automática (2x por ano)
-- Executa início de fevereiro (01/02) e final de julho (25/07)
CREATE EVENT `evt_progressao_semestral`
ON SCHEDULE EVERY 1 YEAR
STARTS '2026-02-01 00:00:00'
ON COMPLETION PRESERVE
ENABLE
DO
BEGIN
    DECLARE total_alunos_atualizados INT DEFAULT 0;
    DECLARE mes_atual INT;

    SET mes_atual = MONTH(CURDATE());

    -- Executar apenas em fevereiro (2) ou julho (7)
    IF mes_atual IN (2, 7) THEN
        -- Atualizar semestre dos alunos ativos (não passa do 6º semestre)
        UPDATE `aluno`
        SET semestre = semestre + 1
        WHERE ativo = 1
          AND semestre < 6;

        SET total_alunos_atualizados = ROW_COUNT();

        -- Registrar auditoria da progressão automática
        INSERT INTO `auditoria` (
            id_admin,
            tabela,
            operacao,
            descricao,
            dados_novos,
            ip_origem
        ) VALUES (
            NULL,  -- Sistema, não admin
            'ALUNO',
            'UPDATE',
            CONCAT('Progressão semestral automática - ', total_alunos_atualizados, ' alunos atualizados'),
            JSON_OBJECT(
                'semestre_incrementado', 1,
                'data_progressao', NOW(),
                'total_alunos', total_alunos_atualizados,
                'mes', mes_atual
            ),
            '127.0.0.1'
        );
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- 8. DADOS INICIAIS (ADMIN PADRÃO)
-- =====================================================

-- Inserir admin padrão se não existir
INSERT IGNORE INTO `administrador`
    (`id_admin`, `nome_completo`, `email_corporativo`, `senha_hash`, `data_cadastro`, `ativo`)
VALUES
    (1, 'Administrador Sistema', 'admin@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 1);

-- =====================================================
-- 9. PADRONIZAÇÃO DE CURSOS (SIGLAS)
-- =====================================================

-- Atualizar registros existentes para siglas padronizadas
UPDATE `eleicao` SET curso = 'DSM'
WHERE curso IN (
    'Desenvolvimento de Software Multiplataforma',
    'desenvolvimento de software multiplataforma',
    'DSM - Desenvolvimento de Software Multiplataforma',
    'Desenv. Software Multiplataforma'
);

UPDATE `eleicao` SET curso = 'GE'
WHERE curso IN (
    'Gestão Empresarial',
    'Gestao Empresarial',
    'gestao empresarial',
    'GESTAO EMPRESARIAL'
);

UPDATE `eleicao` SET curso = 'GPI'
WHERE curso IN (
    'Gestão da Produção Industrial',
    'Gestao da Producao Industrial',
    'gestao da producao industrial',
    'GESTAO DA PRODUCAO INDUSTRIAL',
    'Gestao Producao Industrial'
);

UPDATE `aluno` SET curso = 'DSM'
WHERE curso IN (
    'Desenvolvimento de Software Multiplataforma',
    'desenvolvimento de software multiplataforma',
    'DSM - Desenvolvimento de Software Multiplataforma',
    'Desenv. Software Multiplataforma'
);

UPDATE `aluno` SET curso = 'GE'
WHERE curso IN (
    'Gestão Empresarial',
    'Gestao Empresarial',
    'gestao empresarial',
    'GESTAO EMPRESARIAL'
);

UPDATE `aluno` SET curso = 'GPI'
WHERE curso IN (
    'Gestão da Produção Industrial',
    'Gestao da Producao Industrial',
    'gestao da producao industrial',
    'GESTAO DA PRODUCAO INDUSTRIAL',
    'Gestao Producao Industrial'
);

-- =====================================================
-- 10. SISTEMA DE NOTIFICAÇÃO POR E-MAIL
-- =====================================================

-- Campo para rastrear se notificação de apuração foi enviada
ALTER TABLE `eleicao`
ADD COLUMN IF NOT EXISTS `notificacao_apuracao_enviada` TINYINT(1) DEFAULT 0
COMMENT 'Indica se admin foi notificado sobre apuração pendente';

-- Campo para agrupar eleições criadas em lote (mesmo momento)
ALTER TABLE `eleicao`
ADD COLUMN IF NOT EXISTS `lote_criacao` VARCHAR(32) NULL
COMMENT 'Hash para agrupar eleições criadas juntas';

-- Índice para facilitar queries
ALTER TABLE `eleicao`
ADD INDEX IF NOT EXISTS `idx_notificacao_apuracao` (`status`, `notificacao_apuracao_enviada`);

ALTER TABLE `eleicao`
ADD INDEX IF NOT EXISTS `idx_lote_criacao` (`lote_criacao`);

-- Tabela de log de notificações
CREATE TABLE IF NOT EXISTS `notificacoes_email` (
  `id_notificacao` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo` ENUM('apuracao_individual', 'apuracao_lote') NOT NULL,
  `id_admin` INT NOT NULL,
  `email_destino` VARCHAR(255) NOT NULL,
  `assunto` VARCHAR(255) NOT NULL,
  `eleicoes_ids` TEXT NOT NULL COMMENT 'JSON array com IDs das eleições',
  `status_envio` ENUM('enviado', 'falhou') NOT NULL,
  `mensagem_erro` TEXT NULL,
  `data_envio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX `idx_tipo_notif` (`tipo`),
  INDEX `idx_admin_notif` (`id_admin`),
  INDEX `idx_data_envio` (`data_envio` DESC),

  FOREIGN KEY (`id_admin`) REFERENCES `administrador` (`id_admin`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log de notificações enviadas aos administradores';

-- Tabela de tokens de recuperação de senha
CREATE TABLE IF NOT EXISTS `tokens_recuperacao_senha` (
  `id_token` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `tipo_usuario` ENUM('aluno', 'admin') NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_expiracao` TIMESTAMP NOT NULL,
  `usado` TINYINT(1) DEFAULT 0,
  `data_uso` TIMESTAMP NULL,
  `ip_solicitacao` VARCHAR(45) NULL,
  `ip_uso` VARCHAR(45) NULL,

  UNIQUE KEY `uk_token` (`token`),
  INDEX `idx_email_tipo` (`email`, `tipo_usuario`),
  INDEX `idx_token_valido` (`token`, `usado`, `data_expiracao`),
  INDEX `idx_data_expiracao` (`data_expiracao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tokens para recuperação de senha de alunos e administradores';

DELIMITER $$

-- Procedure: Notificar apuração pendente
DROP PROCEDURE IF EXISTS `sp_notificar_apuracao_pendente`$$
CREATE PROCEDURE `sp_notificar_apuracao_pendente`()
BEGIN
    DECLARE v_eleicoes_pendentes INT DEFAULT 0;

    -- Contar eleições que precisam de notificação
    SELECT COUNT(*) INTO v_eleicoes_pendentes
    FROM eleicao
    WHERE status = 'aguardando_finalizacao'
      AND notificacao_apuracao_enviada = 0;

    IF v_eleicoes_pendentes > 0 THEN
        -- Marcar eleições como "precisa notificar"
        UPDATE eleicao
        SET notificacao_apuracao_enviada = 1
        WHERE status = 'aguardando_finalizacao'
          AND notificacao_apuracao_enviada = 0;

        -- Registrar na auditoria
        INSERT INTO AUDITORIA (
            id_admin,
            tabela,
            operacao,
            descricao,
            ip_origem,
            data_hora
        ) VALUES (
            1,
            'ELEICAO',
            'UPDATE',
            CONCAT(v_eleicoes_pendentes, ' eleição(ões) marcada(s) para notificação de apuração'),
            '127.0.0.1',
            NOW()
        );
    END IF;
END$$

-- Atualizar procedure existente para incluir notificações
DROP PROCEDURE IF EXISTS `sp_atualizar_status_eleicoes`$$
CREATE PROCEDURE `sp_atualizar_status_eleicoes`()
BEGIN
    DECLARE v_eleicoes_atualizadas INT DEFAULT 0;

    -- Atualizar para 'votacao_aberta'
    UPDATE ELEICAO
    SET status = 'votacao_aberta'
    WHERE status = 'candidatura_aberta'
      AND NOW() >= data_inicio_votacao
      AND NOW() < data_fim_votacao;

    SET v_eleicoes_atualizadas = ROW_COUNT();

    IF v_eleicoes_atualizadas > 0 THEN
        INSERT INTO AUDITORIA (id_admin, operacao, descricao, ip_origem, data_hora)
        VALUES (
            1,
            'UPDATE',
            CONCAT(v_eleicoes_atualizadas, ' eleição(ões) mudou(aram) para votacao_aberta'),
            '127.0.0.1',
            NOW()
        );
    END IF;

    -- Marcar para finalização (COM NOTIFICAÇÃO)
    UPDATE ELEICAO
    SET status = 'aguardando_finalizacao',
        notificacao_apuracao_enviada = 0
    WHERE status = 'votacao_aberta'
      AND NOW() >= data_fim_votacao;

    SET v_eleicoes_atualizadas = ROW_COUNT();

    IF v_eleicoes_atualizadas > 0 THEN
        INSERT INTO AUDITORIA (id_admin, operacao, descricao, ip_origem, data_hora)
        VALUES (
            1,
            'UPDATE',
            CONCAT(v_eleicoes_atualizadas, ' eleição(ões) finalizou(aram) votação e aguardam notificação'),
            '127.0.0.1',
            NOW()
        );

        -- Disparar marcação de notificações
        CALL sp_notificar_apuracao_pendente();
    END IF;
END$$

DELIMITER ;