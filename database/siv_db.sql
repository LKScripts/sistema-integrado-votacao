-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2025 at 09:06 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `siv_db`
--
-- ===============================================
-- IMPORTANTE: CONSTRAINTS CHECK
-- ===============================================
-- Este dump foi gerado pelo phpMyAdmin, que NÃO exporta constraints CHECK.
-- As constraints CHECK estão implementadas no banco de dados original, mas
-- precisam ser adicionadas manualmente após importar este arquivo.
--
-- Para adicionar as constraints CHECK após importação, execute:
--   database/add_constraints.sql
--
-- Ou execute manualmente os comandos ALTER TABLE disponíveis nesse arquivo.
--
-- Constraints CHECK implementadas:
--   - ALUNO: chk_semestre (semestre BETWEEN 1 AND 6)
--   - ELEICAO: chk_semestre_eleicao, chk_datas_candidatura,
--              chk_datas_votacao, chk_ordem_fases
--   - RESULTADO: chk_percentual (percentual_participacao BETWEEN 0 AND 100)
-- ===============================================

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_finalizar_eleicao` (IN `p_id_eleicao` INT, IN `p_id_admin` INT)   BEGIN
    DECLARE v_total_aptos INT;
    DECLARE v_total_votantes INT;
    DECLARE v_id_representante INT;
    DECLARE v_votos_representante INT;
    DECLARE v_id_suplente INT;
    DECLARE v_votos_suplente INT;
    DECLARE v_percentual DECIMAL(5,2);

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

    -- CORREÇÃO: Calcular percentual com proteção contra divisão por zero
    SET v_percentual = IF(v_total_aptos > 0,
                          (v_total_votantes / v_total_aptos) * 100,
                          0);

    -- Obter representante (mais votado)
    SELECT c.id_candidatura, COUNT(v.id_voto)
    INTO v_id_representante, v_votos_representante
    FROM CANDIDATURA c
    LEFT JOIN VOTO v ON c.id_candidatura = v.id_candidatura
    WHERE c.id_eleicao = p_id_eleicao
      AND c.status_validacao = 'deferido'
    GROUP BY c.id_candidatura
    ORDER BY COUNT(v.id_voto) DESC
    LIMIT 1;

    -- Obter suplente (segundo mais votado)
    SELECT c.id_candidatura, COUNT(v.id_voto)
    INTO v_id_suplente, v_votos_suplente
    FROM CANDIDATURA c
    LEFT JOIN VOTO v ON c.id_candidatura = v.id_candidatura
    WHERE c.id_eleicao = p_id_eleicao
      AND c.status_validacao = 'deferido'
      AND c.id_candidatura != v_id_representante
    GROUP BY c.id_candidatura
    ORDER BY COUNT(v.id_voto) DESC
    LIMIT 1;

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
        descricao
    ) VALUES (
        p_id_admin,
        p_id_eleicao,
        'ELEICAO',
        'UPDATE',
        CONCAT('Eleição finalizada - ID: ', p_id_eleicao)
    );

    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `administrador`
--

CREATE TABLE `administrador` (
  `id_admin` int(11) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `email_corporativo` varchar(255) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `administrador`
--

INSERT INTO `administrador` (`id_admin`, `nome_completo`, `email_corporativo`, `senha_hash`, `data_cadastro`, `ultimo_acesso`, `ativo`) VALUES
(1, 'Administrador Sistema', 'admin@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-11-07 20:52:28', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `aluno`
--

CREATE TABLE `aluno` (
  `id_aluno` int(11) NOT NULL,
  `ra` varchar(20) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `email_institucional` varchar(255) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `curso` varchar(100) NOT NULL,
  `semestre` int(11) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acesso` timestamp NULL DEFAULT NULL
) ;

--
-- Dumping data for table `aluno`
--

INSERT INTO `aluno` (`id_aluno`, `ra`, `nome_completo`, `email_institucional`, `senha_hash`, `curso`, `semestre`, `data_cadastro`, `ultimo_acesso`) VALUES
(1, '20240001', 'João da Silva', 'joao.silva@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, '2025-11-07 20:52:28', NULL),
(2, '20240002', 'Maria Santos', 'maria.santos@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, '2025-11-07 20:52:28', NULL),
(3, '20240003', 'Pedro Oliveira', 'pedro.oliveira@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, '2025-11-07 20:52:28', NULL),
(4, '20240004', 'Ana Costa', 'ana.costa@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, '2025-11-07 20:52:28', NULL),
(5, '20240005', 'Carlos Souza', 'carlos.souza@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, '2025-11-07 20:52:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ata`
--

CREATE TABLE `ata` (
  `id_ata` int(11) NOT NULL,
  `id_eleicao` int(11) NOT NULL,
  `id_resultado` int(11) NOT NULL,
  `arquivo_pdf` varchar(255) NOT NULL,
  `hash_integridade` varchar(64) NOT NULL,
  `conteudo_json` text NOT NULL,
  `data_geracao` timestamp NOT NULL DEFAULT current_timestamp(),
  `gerado_por` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `ata`
--
DELIMITER $$
CREATE TRIGGER `trg_impede_alteracao_ata` BEFORE UPDATE ON `ata` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Ata não pode ser alterada após geração. Mantenha integridade do documento.';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria`
--

CREATE TABLE `auditoria` (
  `id_auditoria` int(11) NOT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `id_eleicao` int(11) DEFAULT NULL,
  `tabela` varchar(50) NOT NULL,
  `operacao` enum('INSERT','UPDATE','DELETE','LOGIN','LOGOUT') NOT NULL,
  `descricao` text NOT NULL,
  `dados_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_anteriores`)),
  `dados_novos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_novos`)),
  `ip_origem` varchar(45) DEFAULT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidatura`
--

CREATE TABLE `candidatura` (
  `id_candidatura` int(11) NOT NULL,
  `id_eleicao` int(11) NOT NULL,
  `id_aluno` int(11) NOT NULL,
  `proposta` text DEFAULT NULL,
  `foto_candidato` varchar(255) DEFAULT NULL,
  `status_validacao` enum('pendente','deferido','indeferido') DEFAULT 'pendente',
  `data_inscricao` timestamp NOT NULL DEFAULT current_timestamp(),
  `validado_por` int(11) DEFAULT NULL,
  `data_validacao` timestamp NULL DEFAULT NULL,
  `justificativa_indeferimento` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `candidatura`
--
DELIMITER $$
CREATE TRIGGER `trg_auditoria_validacao_candidatura` AFTER UPDATE ON `candidatura` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_valida_candidatura_turma` BEFORE INSERT ON `candidatura` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `eleicao`
--

CREATE TABLE `eleicao` (
  `id_eleicao` int(11) NOT NULL,
  `curso` varchar(100) NOT NULL,
  `semestre` int(11) NOT NULL,
  `data_inicio_candidatura` date NOT NULL,
  `data_fim_candidatura` date NOT NULL,
  `data_inicio_votacao` date NOT NULL,
  `data_fim_votacao` date NOT NULL,
  `status` enum('candidatura_aberta','votacao_aberta','encerrada') DEFAULT 'candidatura_aberta',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `criado_por` int(11) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `resultado`
--

CREATE TABLE `resultado` (
  `id_resultado` int(11) NOT NULL,
  `id_eleicao` int(11) NOT NULL,
  `id_representante` int(11) NOT NULL,
  `id_suplente` int(11) DEFAULT NULL,
  `votos_representante` int(11) NOT NULL,
  `votos_suplente` int(11) DEFAULT NULL,
  `total_votantes` int(11) NOT NULL,
  `total_aptos` int(11) NOT NULL,
  `percentual_participacao` decimal(5,2) NOT NULL,
  `data_apuracao` timestamp NOT NULL DEFAULT current_timestamp(),
  `gerado_por` int(11) NOT NULL
) ;

--
-- Triggers `resultado`
--
DELIMITER $$
CREATE TRIGGER `trg_impede_alteracao_resultado` BEFORE UPDATE ON `resultado` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Resultado não pode ser alterado após geração. Use auditoria para correções.';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `voto`
--

CREATE TABLE `voto` (
  `id_voto` int(11) NOT NULL,
  `id_eleicao` int(11) NOT NULL,
  `id_aluno` int(11) NOT NULL COMMENT 'Aluno que está votando',
  `id_candidatura` int(11) NOT NULL COMMENT 'Candidatura que recebeu o voto',
  `data_hora_voto` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_votante` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `voto`
--
DELIMITER $$
CREATE TRIGGER `trg_valida_voto_candidatura_deferida` BEFORE INSERT ON `voto` FOR EACH ROW BEGIN
    DECLARE v_status_candidatura VARCHAR(20);

    SELECT status_validacao INTO v_status_candidatura
    FROM CANDIDATURA WHERE id_candidatura = NEW.id_candidatura;

    IF v_status_candidatura != 'deferido' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Só é possível votar em candidaturas deferidas';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_valida_voto_turma` BEFORE INSERT ON `voto` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_alunos_aptos_votacao`
-- (See below for the actual view)
--
CREATE TABLE `v_alunos_aptos_votacao` (
`id_eleicao` int(11)
,`curso` varchar(100)
,`semestre` int(11)
,`id_aluno` int(11)
,`nome_completo` varchar(255)
,`ra` varchar(20)
,`email_institucional` varchar(255)
,`ja_votou` varchar(3)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_candidatos_deferidos`
-- (See below for the actual view)
--
CREATE TABLE `v_candidatos_deferidos` (
`id_candidatura` int(11)
,`id_eleicao` int(11)
,`id_aluno` int(11)
,`nome_completo` varchar(255)
,`ra` varchar(20)
,`proposta` text
,`foto_candidato` varchar(255)
,`curso` varchar(100)
,`semestre` int(11)
,`data_inscricao` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_contagem_votos`
-- (See below for the actual view)
--
CREATE TABLE `v_contagem_votos` (
`id_candidatura` int(11)
,`id_eleicao` int(11)
,`nome_candidato` varchar(255)
,`ra` varchar(20)
,`curso` varchar(100)
,`semestre` int(11)
,`total_votos` bigint(21)
,`status_eleicao` enum('candidatura_aberta','votacao_aberta','encerrada')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_eleicoes_ativas`
-- (See below for the actual view)
--
CREATE TABLE `v_eleicoes_ativas` (
`id_eleicao` int(11)
,`curso` varchar(100)
,`semestre` int(11)
,`status` enum('candidatura_aberta','votacao_aberta','encerrada')
,`data_inicio_candidatura` date
,`data_fim_candidatura` date
,`data_inicio_votacao` date
,`data_fim_votacao` date
,`total_candidatos` bigint(21)
,`candidatos_deferidos` bigint(21)
,`total_votos` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_resultados_completos`
-- (See below for the actual view)
--
CREATE TABLE `v_resultados_completos` (
`id_resultado` int(11)
,`id_eleicao` int(11)
,`curso` varchar(100)
,`semestre` int(11)
,`representante` varchar(255)
,`ra_representante` varchar(20)
,`votos_representante` int(11)
,`suplente` varchar(255)
,`ra_suplente` varchar(20)
,`votos_suplente` int(11)
,`total_votantes` int(11)
,`total_aptos` int(11)
,`percentual_participacao` decimal(5,2)
,`data_apuracao` timestamp
,`apurado_por` varchar(255)
);

-- --------------------------------------------------------

--
-- Structure for view `v_alunos_aptos_votacao`
--
DROP TABLE IF EXISTS `v_alunos_aptos_votacao`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_alunos_aptos_votacao`  AS SELECT `e`.`id_eleicao` AS `id_eleicao`, `e`.`curso` AS `curso`, `e`.`semestre` AS `semestre`, `a`.`id_aluno` AS `id_aluno`, `a`.`nome_completo` AS `nome_completo`, `a`.`ra` AS `ra`, `a`.`email_institucional` AS `email_institucional`, CASE WHEN `v`.`id_voto` is not null THEN 'SIM' ELSE 'NÃO' END AS `ja_votou` FROM ((`eleicao` `e` join `aluno` `a`) left join `voto` `v` on(`e`.`id_eleicao` = `v`.`id_eleicao` and `a`.`id_aluno` = `v`.`id_aluno`)) WHERE `a`.`curso` = `e`.`curso` AND `a`.`semestre` = `e`.`semestre` ;

-- --------------------------------------------------------

--
-- Structure for view `v_candidatos_deferidos`
--
DROP TABLE IF EXISTS `v_candidatos_deferidos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_candidatos_deferidos`  AS SELECT `c`.`id_candidatura` AS `id_candidatura`, `c`.`id_eleicao` AS `id_eleicao`, `a`.`id_aluno` AS `id_aluno`, `a`.`nome_completo` AS `nome_completo`, `a`.`ra` AS `ra`, `c`.`proposta` AS `proposta`, `c`.`foto_candidato` AS `foto_candidato`, `e`.`curso` AS `curso`, `e`.`semestre` AS `semestre`, `c`.`data_inscricao` AS `data_inscricao` FROM ((`candidatura` `c` join `aluno` `a` on(`c`.`id_aluno` = `a`.`id_aluno`)) join `eleicao` `e` on(`c`.`id_eleicao` = `e`.`id_eleicao`)) WHERE `c`.`status_validacao` = 'deferido' ;

-- --------------------------------------------------------

--
-- Structure for view `v_contagem_votos`
--
DROP TABLE IF EXISTS `v_contagem_votos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_contagem_votos`  AS SELECT `c`.`id_candidatura` AS `id_candidatura`, `c`.`id_eleicao` AS `id_eleicao`, `a`.`nome_completo` AS `nome_candidato`, `a`.`ra` AS `ra`, `e`.`curso` AS `curso`, `e`.`semestre` AS `semestre`, count(`v`.`id_voto`) AS `total_votos`, `e`.`status` AS `status_eleicao` FROM (((`candidatura` `c` join `aluno` `a` on(`c`.`id_aluno` = `a`.`id_aluno`)) join `eleicao` `e` on(`c`.`id_eleicao` = `e`.`id_eleicao`)) left join `voto` `v` on(`c`.`id_candidatura` = `v`.`id_candidatura`)) WHERE `c`.`status_validacao` = 'deferido' GROUP BY `c`.`id_candidatura`, `c`.`id_eleicao`, `a`.`nome_completo`, `a`.`ra`, `e`.`curso`, `e`.`semestre`, `e`.`status` ORDER BY `e`.`id_eleicao` ASC, count(`v`.`id_voto`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_eleicoes_ativas`
--
DROP TABLE IF EXISTS `v_eleicoes_ativas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_eleicoes_ativas`  AS SELECT `e`.`id_eleicao` AS `id_eleicao`, `e`.`curso` AS `curso`, `e`.`semestre` AS `semestre`, `e`.`status` AS `status`, `e`.`data_inicio_candidatura` AS `data_inicio_candidatura`, `e`.`data_fim_candidatura` AS `data_fim_candidatura`, `e`.`data_inicio_votacao` AS `data_inicio_votacao`, `e`.`data_fim_votacao` AS `data_fim_votacao`, count(distinct `c`.`id_candidatura`) AS `total_candidatos`, count(distinct case when `c`.`status_validacao` = 'deferido' then `c`.`id_candidatura` end) AS `candidatos_deferidos`, count(distinct `v`.`id_voto`) AS `total_votos` FROM ((`eleicao` `e` left join `candidatura` `c` on(`e`.`id_eleicao` = `c`.`id_eleicao`)) left join `voto` `v` on(`e`.`id_eleicao` = `v`.`id_eleicao`)) WHERE `e`.`status` <> 'encerrada' GROUP BY `e`.`id_eleicao` ;

-- --------------------------------------------------------

--
-- Structure for view `v_resultados_completos`
--
DROP TABLE IF EXISTS `v_resultados_completos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_resultados_completos`  AS SELECT `r`.`id_resultado` AS `id_resultado`, `e`.`id_eleicao` AS `id_eleicao`, `e`.`curso` AS `curso`, `e`.`semestre` AS `semestre`, `a_rep`.`nome_completo` AS `representante`, `a_rep`.`ra` AS `ra_representante`, `r`.`votos_representante` AS `votos_representante`, `a_sup`.`nome_completo` AS `suplente`, `a_sup`.`ra` AS `ra_suplente`, `r`.`votos_suplente` AS `votos_suplente`, `r`.`total_votantes` AS `total_votantes`, `r`.`total_aptos` AS `total_aptos`, `r`.`percentual_participacao` AS `percentual_participacao`, `r`.`data_apuracao` AS `data_apuracao`, `adm`.`nome_completo` AS `apurado_por` FROM ((((((`resultado` `r` join `eleicao` `e` on(`r`.`id_eleicao` = `e`.`id_eleicao`)) join `candidatura` `c_rep` on(`r`.`id_representante` = `c_rep`.`id_candidatura`)) join `aluno` `a_rep` on(`c_rep`.`id_aluno` = `a_rep`.`id_aluno`)) left join `candidatura` `c_sup` on(`r`.`id_suplente` = `c_sup`.`id_candidatura`)) left join `aluno` `a_sup` on(`c_sup`.`id_aluno` = `a_sup`.`id_aluno`)) join `administrador` `adm` on(`r`.`gerado_por` = `adm`.`id_admin`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrador`
--
ALTER TABLE `administrador`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email_corporativo` (`email_corporativo`),
  ADD KEY `idx_email_admin` (`email_corporativo`),
  ADD KEY `idx_ativo` (`ativo`);

--
-- Indexes for table `aluno`
--
ALTER TABLE `aluno`
  ADD PRIMARY KEY (`id_aluno`),
  ADD UNIQUE KEY `ra` (`ra`),
  ADD UNIQUE KEY `email_institucional` (`email_institucional`),
  ADD KEY `idx_curso_semestre` (`curso`,`semestre`),
  ADD KEY `idx_email` (`email_institucional`),
  ADD KEY `idx_ra` (`ra`);

--
-- Indexes for table `ata`
--
ALTER TABLE `ata`
  ADD PRIMARY KEY (`id_ata`),
  ADD UNIQUE KEY `id_eleicao` (`id_eleicao`),
  ADD KEY `idx_eleicao_ata` (`id_eleicao`),
  ADD KEY `idx_hash` (`hash_integridade`),
  ADD KEY `fk_ata_resultado` (`id_resultado`),
  ADD KEY `fk_ata_gerador` (`gerado_por`);

--
-- Indexes for table `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `idx_tabela` (`tabela`),
  ADD KEY `idx_data` (`data_hora`),
  ADD KEY `idx_admin` (`id_admin`),
  ADD KEY `idx_operacao` (`operacao`),
  ADD KEY `fk_auditoria_eleicao` (`id_eleicao`);

--
-- Indexes for table `candidatura`
--
ALTER TABLE `candidatura`
  ADD PRIMARY KEY (`id_candidatura`),
  ADD UNIQUE KEY `uk_candidatura_unica` (`id_eleicao`,`id_aluno`),
  ADD KEY `idx_eleicao` (`id_eleicao`),
  ADD KEY `idx_aluno_candidato` (`id_aluno`),
  ADD KEY `idx_status_validacao` (`status_validacao`),
  ADD KEY `fk_candidatura_validador` (`validado_por`);

--
-- Indexes for table `eleicao`
--
ALTER TABLE `eleicao`
  ADD PRIMARY KEY (`id_eleicao`),
  ADD UNIQUE KEY `uk_eleicao_periodo` (`curso`,`semestre`,`data_inicio_candidatura`),
  ADD KEY `idx_curso_semestre_eleicao` (`curso`,`semestre`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_datas` (`data_inicio_votacao`,`data_fim_votacao`),
  ADD KEY `fk_eleicao_criador` (`criado_por`);

--
-- Indexes for table `resultado`
--
ALTER TABLE `resultado`
  ADD PRIMARY KEY (`id_resultado`),
  ADD UNIQUE KEY `id_eleicao` (`id_eleicao`),
  ADD KEY `idx_eleicao_resultado` (`id_eleicao`),
  ADD KEY `fk_resultado_representante` (`id_representante`),
  ADD KEY `fk_resultado_suplente` (`id_suplente`),
  ADD KEY `fk_resultado_gerador` (`gerado_por`);

--
-- Indexes for table `voto`
--
ALTER TABLE `voto`
  ADD PRIMARY KEY (`id_voto`),
  ADD UNIQUE KEY `uk_voto_unico` (`id_eleicao`,`id_aluno`),
  ADD KEY `idx_eleicao_voto` (`id_eleicao`),
  ADD KEY `idx_candidatura` (`id_candidatura`),
  ADD KEY `idx_aluno_voto` (`id_aluno`),
  ADD KEY `idx_data_voto` (`data_hora_voto`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `administrador`
--
ALTER TABLE `administrador`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `aluno`
--
ALTER TABLE `aluno`
  MODIFY `id_aluno` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ata`
--
ALTER TABLE `ata`
  MODIFY `id_ata` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_auditoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidatura`
--
ALTER TABLE `candidatura`
  MODIFY `id_candidatura` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eleicao`
--
ALTER TABLE `eleicao`
  MODIFY `id_eleicao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resultado`
--
ALTER TABLE `resultado`
  MODIFY `id_resultado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `voto`
--
ALTER TABLE `voto`
  MODIFY `id_voto` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ata`
--
ALTER TABLE `ata`
  ADD CONSTRAINT `fk_ata_eleicao` FOREIGN KEY (`id_eleicao`) REFERENCES `eleicao` (`id_eleicao`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ata_gerador` FOREIGN KEY (`gerado_por`) REFERENCES `administrador` (`id_admin`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ata_resultado` FOREIGN KEY (`id_resultado`) REFERENCES `resultado` (`id_resultado`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_auditoria_admin` FOREIGN KEY (`id_admin`) REFERENCES `administrador` (`id_admin`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_auditoria_eleicao` FOREIGN KEY (`id_eleicao`) REFERENCES `eleicao` (`id_eleicao`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `candidatura`
--
ALTER TABLE `candidatura`
  ADD CONSTRAINT `fk_candidatura_aluno` FOREIGN KEY (`id_aluno`) REFERENCES `aluno` (`id_aluno`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_candidatura_eleicao` FOREIGN KEY (`id_eleicao`) REFERENCES `eleicao` (`id_eleicao`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_candidatura_validador` FOREIGN KEY (`validado_por`) REFERENCES `administrador` (`id_admin`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `eleicao`
--
ALTER TABLE `eleicao`
  ADD CONSTRAINT `fk_eleicao_criador` FOREIGN KEY (`criado_por`) REFERENCES `administrador` (`id_admin`) ON UPDATE CASCADE;

--
-- Constraints for table `resultado`
--
ALTER TABLE `resultado`
  ADD CONSTRAINT `fk_resultado_eleicao` FOREIGN KEY (`id_eleicao`) REFERENCES `eleicao` (`id_eleicao`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resultado_gerador` FOREIGN KEY (`gerado_por`) REFERENCES `administrador` (`id_admin`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resultado_representante` FOREIGN KEY (`id_representante`) REFERENCES `candidatura` (`id_candidatura`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resultado_suplente` FOREIGN KEY (`id_suplente`) REFERENCES `candidatura` (`id_candidatura`) ON UPDATE CASCADE;

--
-- Constraints for table `voto`
--
ALTER TABLE `voto`
  ADD CONSTRAINT `fk_voto_aluno` FOREIGN KEY (`id_aluno`) REFERENCES `aluno` (`id_aluno`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_voto_candidatura` FOREIGN KEY (`id_candidatura`) REFERENCES `candidatura` (`id_candidatura`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_voto_eleicao` FOREIGN KEY (`id_eleicao`) REFERENCES `eleicao` (`id_eleicao`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
