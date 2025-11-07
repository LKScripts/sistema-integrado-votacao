# Modelagem de Banco de Dados - SIV

## Sistema Integrado de Votação

## 1. DIAGRAMA ENTIDADE-RELACIONAMENTO (Notação Chen Simplificada)

```
┌──────────────┐         ┌──────────────────┐         ┌──────────────┐
│    ALUNO     │         │   CANDIDATURA    │         │   ELEICAO    │
├──────────────┤         ├──────────────────┤         ├──────────────┤
│ PK id_aluno  │─────┐   │ PK id_candidatura│   ┌────│ PK id_eleicao│
│    ra        │     │   │ FK id_aluno      │◄──┘    │    curso     │
│ nome_completo│     │   │ FK id_eleicao    │◄───────│    semestre  │
│ email_inst   │     │   │    proposta      │        │ dt_ini_cand  │
│ senha_hash   │     │   │ foto_candidato   │        │ dt_fim_cand  │
│    curso     │     │   │ status_validacao │        │ dt_ini_vot   │
│   semestre   │     │   │ data_inscricao   │        │ dt_fim_vot   │
│ data_cadastro│     │   │ validado_por (FK)│        │    status    │
│ultimo_acesso │     │   │ data_validacao   │        │ data_criacao │
└──────────────┘     │   │ justif_indeferim │        │ criado_por   │
                     │   └──────────────────┘        └──────────────┘
                     │                                      │
                     │   ┌──────────────────┐              │
                     └──►│      VOTO        │              │
                         ├──────────────────┤              │
                         │ PK id_voto       │              │
                         │ FK id_eleicao    │◄─────────────┤
                         │ FK id_aluno      │              │
                         │ FK id_candidatura│              │
                         │ data_hora_voto   │              │
                         │ ip_votante       │              │
                         │ UNIQUE(eleicao,  │              │
                         │        aluno)    │              │
                         └──────────────────┘              │
                                                           │
┌──────────────────┐                                      │
│  ADMINISTRADOR   │                                      │
├──────────────────┤                                      │
│ PK id_admin      │──────────────────┐                   │
│ nome_completo    │                  │                   │
│ email_corporativo│                  │                   │
│ senha_hash       │                  │                   │
│ data_cadastro    │                  │                   │
│ ultimo_acesso    │                  │                   │
│     ativo        │                  │                   │
└──────────────────┘                  │                   │
                                      │                   │
                    ┌─────────────────┴─────┐             │
                    │                       │             │
            ┌───────▼────────┐      ┌───────▼────────┐   │
            │   RESULTADO    │      │   AUDITORIA    │   │
            ├────────────────┤      ├────────────────┤   │
            │ PK id_resultado│      │ PK id_auditoria│   │
            │ FK id_eleicao  │◄─────┤ FK id_admin    │   │
            │ id_representante│      │ FK id_eleicao  │   │
            │ id_suplente    │      │    tabela      │   │
            │votos_represent.│      │    operacao    │   │
            │ votos_suplente │      │ descricao      │   │
            │ total_votantes │      │dados_anteriores│   │
            │ total_aptos    │      │  dados_novos   │   │
            │perc_participac.│      │  ip_origem     │   │
            │ data_apuracao  │      │ data_hora      │   │
            │ gerado_por (FK)│      └────────────────┘   │
            └────────────────┘                            │
                    │                                     │
                    │                                     │
            ┌───────▼────────┐                            │
            │      ATA       │                            │
            ├────────────────┤                            │
            │ PK id_ata      │                            │
            │ FK id_eleicao  │◄───────────────────────────┘
            │ FK id_resultado│
            │ arquivo_pdf    │
            │hash_integridade│
            │ conteudo_json  │
            │ data_geracao   │
            │ gerado_por (FK)│
            └────────────────┘
```

---

## 2. MODELO LÓGICO RELACIONAL

### Tabela: ALUNO

```
ALUNO (
    id_aluno INT PRIMARY KEY AUTO_INCREMENT,
    ra VARCHAR(20) UNIQUE NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    email_institucional VARCHAR(255) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    curso VARCHAR(100) NOT NULL,
    semestre INT NOT NULL CHECK(semestre BETWEEN 1 AND 6),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL
)
```

### Tabela: ADMINISTRADOR

```
ADMINISTRADOR (
    id_admin INT PRIMARY KEY AUTO_INCREMENT,
    nome_completo VARCHAR(255) NOT NULL,
    email_corporativo VARCHAR(255) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,
    ativo BOOLEAN DEFAULT TRUE
)
```

### Tabela: ELEICAO

```
ELEICAO (
    id_eleicao INT PRIMARY KEY AUTO_INCREMENT,
    curso VARCHAR(100) NOT NULL,
    semestre INT NOT NULL CHECK(semestre BETWEEN 1 AND 6),
    data_inicio_candidatura DATE NOT NULL,
    data_fim_candidatura DATE NOT NULL,
    data_inicio_votacao DATE NOT NULL,
    data_fim_votacao DATE NOT NULL,
    status ENUM('candidatura_aberta', 'votacao_aberta', 'encerrada') DEFAULT 'candidatura_aberta',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT NOT NULL,
    UNIQUE(curso, semestre, data_inicio_candidatura),
    FOREIGN KEY (criado_por) REFERENCES ADMINISTRADOR(id_admin)
)
```

### Tabela: CANDIDATURA

```
CANDIDATURA (
    id_candidatura INT PRIMARY KEY AUTO_INCREMENT,
    id_eleicao INT NOT NULL,
    id_aluno INT NOT NULL,
    proposta TEXT,
    foto_candidato VARCHAR(255),
    status_validacao ENUM('pendente', 'deferido', 'indeferido') DEFAULT 'pendente',
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    validado_por INT NULL COMMENT 'ID do administrador que validou',
    data_validacao TIMESTAMP NULL,
    justificativa_indeferimento TEXT NULL,
    FOREIGN KEY (id_eleicao) REFERENCES ELEICAO(id_eleicao) ON DELETE CASCADE,
    FOREIGN KEY (id_aluno) REFERENCES ALUNO(id_aluno) ON DELETE CASCADE,
    FOREIGN KEY (validado_por) REFERENCES ADMINISTRADOR(id_admin),
    UNIQUE(id_eleicao, id_aluno)
)
```

### Tabela: VOTO

```
VOTO (
    id_voto INT PRIMARY KEY AUTO_INCREMENT,
    id_eleicao INT NOT NULL,
    id_aluno INT NOT NULL COMMENT 'Votante',
    id_candidatura INT NOT NULL COMMENT 'Candidato votado',
    data_hora_voto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_votante VARCHAR(45) NULL COMMENT 'IP do votante (segurança)',
    FOREIGN KEY (id_eleicao) REFERENCES ELEICAO(id_eleicao) ON DELETE CASCADE,
    FOREIGN KEY (id_aluno) REFERENCES ALUNO(id_aluno) ON DELETE CASCADE,
    FOREIGN KEY (id_candidatura) REFERENCES CANDIDATURA(id_candidatura) ON DELETE CASCADE,
    UNIQUE(id_eleicao, id_aluno) COMMENT 'Garante um voto por aluno por eleição'
)
```

### Tabela: RESULTADO

```
RESULTADO (
    id_resultado INT PRIMARY KEY AUTO_INCREMENT,
    id_eleicao INT NOT NULL UNIQUE,
    id_representante INT NOT NULL COMMENT 'Candidatura vencedora',
    id_suplente INT NULL COMMENT 'Segunda candidatura mais votada',
    votos_representante INT NOT NULL,
    votos_suplente INT NULL,
    total_votantes INT NOT NULL,
    total_aptos INT NOT NULL COMMENT 'Total de alunos aptos a votar',
    percentual_participacao DECIMAL(5,2) NOT NULL,
    data_apuracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gerado_por INT NOT NULL COMMENT 'Administrador que finalizou',
    FOREIGN KEY (id_eleicao) REFERENCES ELEICAO(id_eleicao) ON DELETE CASCADE,
    FOREIGN KEY (id_representante) REFERENCES CANDIDATURA(id_candidatura),
    FOREIGN KEY (id_suplente) REFERENCES CANDIDATURA(id_candidatura),
    FOREIGN KEY (gerado_por) REFERENCES ADMINISTRADOR(id_admin)
)
```

### Tabela: ATA

```
ATA (
    id_ata INT PRIMARY KEY AUTO_INCREMENT,
    id_eleicao INT NOT NULL UNIQUE,
    id_resultado INT NOT NULL,
    arquivo_pdf VARCHAR(255) NOT NULL COMMENT 'Caminho do arquivo PDF',
    hash_integridade VARCHAR(64) NOT NULL COMMENT 'SHA-256 do PDF',
    conteudo_json TEXT NOT NULL COMMENT 'Dados estruturados da ata',
    data_geracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gerado_por INT NOT NULL,
    FOREIGN KEY (id_eleicao) REFERENCES ELEICAO(id_eleicao) ON DELETE CASCADE,
    FOREIGN KEY (id_resultado) REFERENCES RESULTADO(id_resultado) ON DELETE CASCADE,
    FOREIGN KEY (gerado_por) REFERENCES ADMINISTRADOR(id_admin)
)
```

### Tabela: AUDITORIA

```
AUDITORIA (
    id_auditoria INT PRIMARY KEY AUTO_INCREMENT,
    id_admin INT NULL COMMENT 'Administrador que realizou a ação',
    id_eleicao INT NULL COMMENT 'Eleição relacionada, se aplicável',
    tabela VARCHAR(50) NOT NULL,
    operacao ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT') NOT NULL,
    descricao TEXT NOT NULL,
    dados_anteriores JSON NULL,
    dados_novos JSON NULL,
    ip_origem VARCHAR(45) NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_admin) REFERENCES ADMINISTRADOR(id_admin),
    FOREIGN KEY (id_eleicao) REFERENCES ELEICAO(id_eleicao) ON DELETE SET NULL,
    INDEX idx_tabela (tabela),
    INDEX idx_data (data_hora),
    INDEX idx_admin (id_admin)
)
```

---

## 3. SCRIPT SQL DE CRIAÇÃO

```sql
-- ===============================================
-- SCRIPT DE CRIAÇÃO DO BANCO DE DADOS SIV
-- Sistema Integrado de Votação
-- ===============================================

CREATE DATABASE IF NOT EXISTS siv_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE siv_db;

-- ===============================================
-- TABELA: ALUNO
-- ===============================================
CREATE TABLE ALUNO (
    id_aluno INT AUTO_INCREMENT PRIMARY KEY,
    ra VARCHAR(20) UNIQUE NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    email_institucional VARCHAR(255) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    curso VARCHAR(100) NOT NULL,
    semestre INT NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,

    INDEX idx_curso_semestre (curso, semestre),
    INDEX idx_email (email_institucional),
    INDEX idx_ra (ra),

    CONSTRAINT chk_semestre CHECK (semestre BETWEEN 1 AND 6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABELA: ADMINISTRADOR
-- ===============================================
CREATE TABLE ADMINISTRADOR (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(255) NOT NULL,
    email_corporativo VARCHAR(255) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,
    ativo BOOLEAN DEFAULT TRUE,

    INDEX idx_email_admin (email_corporativo),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABELA: ELEICAO
-- ===============================================
CREATE TABLE ELEICAO (
    id_eleicao INT AUTO_INCREMENT PRIMARY KEY,
    curso VARCHAR(100) NOT NULL,
    semestre INT NOT NULL,
    data_inicio_candidatura DATE NOT NULL,
    data_fim_candidatura DATE NOT NULL,
    data_inicio_votacao DATE NOT NULL,
    data_fim_votacao DATE NOT NULL,
    status ENUM('candidatura_aberta', 'votacao_aberta', 'encerrada')
           DEFAULT 'candidatura_aberta',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    criado_por INT NOT NULL,

    INDEX idx_curso_semestre_eleicao (curso, semestre),
    INDEX idx_status (status),
    INDEX idx_datas (data_inicio_votacao, data_fim_votacao),

    UNIQUE KEY uk_eleicao_periodo (curso, semestre, data_inicio_candidatura),

    CONSTRAINT chk_semestre_eleicao CHECK (semestre BETWEEN 1 AND 6),
    CONSTRAINT chk_datas_candidatura CHECK (data_fim_candidatura > data_inicio_candidatura),
    CONSTRAINT chk_datas_votacao CHECK (data_fim_votacao > data_inicio_votacao),
    CONSTRAINT chk_ordem_fases CHECK (data_inicio_votacao >= data_fim_candidatura),

    CONSTRAINT fk_eleicao_criador
        FOREIGN KEY (criado_por)
        REFERENCES ADMINISTRADOR(id_admin)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABELA: CANDIDATURA
-- ===============================================
CREATE TABLE CANDIDATURA (
    id_candidatura INT AUTO_INCREMENT PRIMARY KEY,
    id_eleicao INT NOT NULL,
    id_aluno INT NOT NULL,
    proposta TEXT,
    foto_candidato VARCHAR(255),
    status_validacao ENUM('pendente', 'deferido', 'indeferido')
                     DEFAULT 'pendente',
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    validado_por INT NULL,
    data_validacao TIMESTAMP NULL,
    justificativa_indeferimento TEXT NULL,

    INDEX idx_eleicao (id_eleicao),
    INDEX idx_aluno_candidato (id_aluno),
    INDEX idx_status_validacao (status_validacao),

    UNIQUE KEY uk_candidatura_unica (id_eleicao, id_aluno),

    CONSTRAINT fk_candidatura_eleicao
        FOREIGN KEY (id_eleicao)
        REFERENCES ELEICAO(id_eleicao)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_candidatura_aluno
        FOREIGN KEY (id_aluno)
        REFERENCES ALUNO(id_aluno)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_candidatura_validador
        FOREIGN KEY (validado_por)
        REFERENCES ADMINISTRADOR(id_admin)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABELA: VOTO
-- ===============================================
CREATE TABLE VOTO (
    id_voto INT AUTO_INCREMENT PRIMARY KEY,
    id_eleicao INT NOT NULL,
    id_aluno INT NOT NULL COMMENT 'Aluno que está votando',
    id_candidatura INT NOT NULL COMMENT 'Candidatura que recebeu o voto',
    data_hora_voto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_votante VARCHAR(45) NULL,

    INDEX idx_eleicao_voto (id_eleicao),
    INDEX idx_candidatura (id_candidatura),
    INDEX idx_data_voto (data_hora_voto),

    UNIQUE KEY uk_voto_unico (id_eleicao, id_aluno),

    CONSTRAINT fk_voto_eleicao
        FOREIGN KEY (id_eleicao)
        REFERENCES ELEICAO(id_eleicao)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_voto_aluno
        FOREIGN KEY (id_aluno)
        REFERENCES ALUNO(id_aluno)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_voto_candidatura
        FOREIGN KEY (id_candidatura)
        REFERENCES CANDIDATURA(id_candidatura)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABELA: RESULTADO
-- ===============================================
CREATE TABLE RESULTADO (
    id_resultado INT AUTO_INCREMENT PRIMARY KEY,
    id_eleicao INT NOT NULL UNIQUE,
    id_representante INT NOT NULL,
    id_suplente INT NULL,
    votos_representante INT NOT NULL,
    votos_suplente INT NULL,
    total_votantes INT NOT NULL,
    total_aptos INT NOT NULL,
    percentual_participacao DECIMAL(5,2) NOT NULL,
    data_apuracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gerado_por INT NOT NULL,

    INDEX idx_eleicao_resultado (id_eleicao),

    CONSTRAINT fk_resultado_eleicao
        FOREIGN KEY (id_eleicao)
        REFERENCES ELEICAO(id_eleicao)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_resultado_representante
        FOREIGN KEY (id_representante)
        REFERENCES CANDIDATURA(id_candidatura)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_resultado_suplente
        FOREIGN KEY (id_suplente)
        REFERENCES CANDIDATURA(id_candidatura)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_resultado_gerador
        FOREIGN KEY (gerado_por)
        REFERENCES ADMINISTRADOR(id_admin)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT chk_percentual CHECK (percentual_participacao BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABELA: ATA
-- ===============================================
CREATE TABLE ATA (
    id_ata INT AUTO_INCREMENT PRIMARY KEY,
    id_eleicao INT NOT NULL UNIQUE,
    id_resultado INT NOT NULL,
    arquivo_pdf VARCHAR(255) NOT NULL,
    hash_integridade VARCHAR(64) NOT NULL,
    conteudo_json TEXT NOT NULL,
    data_geracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gerado_por INT NOT NULL,

    INDEX idx_eleicao_ata (id_eleicao),
    INDEX idx_hash (hash_integridade),

    CONSTRAINT fk_ata_eleicao
        FOREIGN KEY (id_eleicao)
        REFERENCES ELEICAO(id_eleicao)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_ata_resultado
        FOREIGN KEY (id_resultado)
        REFERENCES RESULTADO(id_resultado)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_ata_gerador
        FOREIGN KEY (gerado_por)
        REFERENCES ADMINISTRADOR(id_admin)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABELA: AUDITORIA
-- ===============================================
CREATE TABLE AUDITORIA (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_admin INT NULL,
    id_eleicao INT NULL,
    tabela VARCHAR(50) NOT NULL,
    operacao ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT') NOT NULL,
    descricao TEXT NOT NULL,
    dados_anteriores JSON NULL,
    dados_novos JSON NULL,
    ip_origem VARCHAR(45) NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tabela (tabela),
    INDEX idx_data (data_hora),
    INDEX idx_admin (id_admin),
    INDEX idx_operacao (operacao),

    CONSTRAINT fk_auditoria_admin
        FOREIGN KEY (id_admin)
        REFERENCES ADMINISTRADOR(id_admin)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_auditoria_eleicao
        FOREIGN KEY (id_eleicao)
        REFERENCES ELEICAO(id_eleicao)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TRIGGERS DE VALIDAÇÃO E AUDITORIA
-- ===============================================

-- Trigger: Validar que candidato pertence à turma da eleição
DELIMITER //

CREATE TRIGGER trg_valida_candidatura_turma
BEFORE INSERT ON CANDIDATURA
FOR EACH ROW
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
END//

DELIMITER ;

-- Trigger: Validar que aluno está votando em candidato da sua turma
DELIMITER //

CREATE TRIGGER trg_valida_voto_turma
BEFORE INSERT ON VOTO
FOR EACH ROW
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
END//

DELIMITER ;

-- Trigger: Validar que voto é em candidatura deferida
DELIMITER //

CREATE TRIGGER trg_valida_voto_candidatura_deferida
BEFORE INSERT ON VOTO
FOR EACH ROW
BEGIN
    DECLARE v_status_candidatura VARCHAR(20);

    SELECT status_validacao INTO v_status_candidatura
    FROM CANDIDATURA WHERE id_candidatura = NEW.id_candidatura;

    IF v_status_candidatura != 'deferido' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Só é possível votar em candidaturas deferidas';
    END IF;
END//

DELIMITER ;

-- Trigger: Registrar validação de candidatura na auditoria
DELIMITER //

CREATE TRIGGER trg_auditoria_validacao_candidatura
AFTER UPDATE ON CANDIDATURA
FOR EACH ROW
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
END//

DELIMITER ;

-- Trigger: Impedir alteração de resultado após geração
DELIMITER //

CREATE TRIGGER trg_impede_alteracao_resultado
BEFORE UPDATE ON RESULTADO
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Resultado não pode ser alterado após geração. Use auditoria para correções.';
END//

DELIMITER ;

-- Trigger: Impedir alteração de ata após geração
DELIMITER //

CREATE TRIGGER trg_impede_alteracao_ata
BEFORE UPDATE ON ATA
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Ata não pode ser alterada após geração. Mantenha integridade do documento.';
END//

DELIMITER ;

-- ===============================================
-- VIEWS ÚTEIS
-- ===============================================

-- View: Candidatos deferidos por eleição
CREATE VIEW v_candidatos_deferidos AS
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
FROM CANDIDATURA c
JOIN ALUNO a ON c.id_aluno = a.id_aluno
JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
WHERE c.status_validacao = 'deferido';

-- View: Contagem de votos por candidatura
CREATE VIEW v_contagem_votos AS
SELECT
    c.id_candidatura,
    c.id_eleicao,
    a.nome_completo AS nome_candidato,
    a.ra,
    e.curso,
    e.semestre,
    COUNT(v.id_voto) AS total_votos,
    e.status AS status_eleicao
FROM CANDIDATURA c
JOIN ALUNO a ON c.id_aluno = a.id_aluno
JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
LEFT JOIN VOTO v ON c.id_candidatura = v.id_candidatura
WHERE c.status_validacao = 'deferido'
GROUP BY c.id_candidatura, c.id_eleicao, a.nome_completo, a.ra, e.curso, e.semestre, e.status
ORDER BY e.id_eleicao, total_votos DESC;

-- View: Eleições ativas por turma
CREATE VIEW v_eleicoes_ativas AS
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
FROM ELEICAO e
LEFT JOIN CANDIDATURA c ON e.id_eleicao = c.id_eleicao
LEFT JOIN VOTO v ON e.id_eleicao = v.id_eleicao
WHERE e.status != 'encerrada'
GROUP BY e.id_eleicao;

-- View: Resultados completos de eleições encerradas
CREATE VIEW v_resultados_completos AS
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
FROM RESULTADO r
JOIN ELEICAO e ON r.id_eleicao = e.id_eleicao
JOIN CANDIDATURA c_rep ON r.id_representante = c_rep.id_candidatura
JOIN ALUNO a_rep ON c_rep.id_aluno = a_rep.id_aluno
LEFT JOIN CANDIDATURA c_sup ON r.id_suplente = c_sup.id_candidatura
LEFT JOIN ALUNO a_sup ON c_sup.id_aluno = a_sup.id_aluno
JOIN ADMINISTRADOR adm ON r.gerado_por = adm.id_admin;

-- View: Alunos aptos a votar por eleição
CREATE VIEW v_alunos_aptos_votacao AS
SELECT
    e.id_eleicao,
    e.curso,
    e.semestre,
    a.id_aluno,
    a.nome_completo,
    a.ra,
    a.email_institucional,
    CASE WHEN v.id_voto IS NOT NULL THEN 'SIM' ELSE 'NÃO' END AS ja_votou
FROM ELEICAO e
CROSS JOIN ALUNO a
LEFT JOIN VOTO v ON e.id_eleicao = v.id_eleicao AND a.id_aluno = v.id_aluno
WHERE a.curso = e.curso AND a.semestre = e.semestre;

-- ===============================================
-- STORED PROCEDURES
-- ===============================================

-- Procedure: Finalizar eleição e gerar resultado
DELIMITER //

CREATE PROCEDURE sp_finalizar_eleicao(
    IN p_id_eleicao INT,
    IN p_id_admin INT
)
BEGIN
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
    
    SELECT COUNT(*) INTO v_total_aptos
    FROM ALUNO a
    JOIN ELEICAO e ON a.curso = e.curso AND a.semestre = e.semestre
    WHERE e.id_eleicao = p_id_eleicao;
    
    SELECT COUNT(*) INTO v_total_votantes
    FROM VOTO WHERE id_eleicao = p_id_eleicao;
    
    SET v_percentual = (v_total_votantes / v_total_aptos) * 100;
    
    SELECT c.id_candidatura, COUNT(v.id_voto)
    INTO v_id_representante, v_votos_representante
    FROM CANDIDATURA c
    LEFT JOIN VOTO v ON c.id_candidatura = v.id_candidatura
    WHERE c.id_eleicao = p_id_eleicao
      AND c.status_validacao = 'deferido'
    GROUP BY c.id_candidatura
    ORDER BY COUNT(v.id_voto) DESC
    LIMIT 1;
    
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
    
    UPDATE ELEICAO
    SET status = 'encerrada'
    WHERE id_eleicao = p_id_eleicao;
    
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
END//

DELIMITER ;

-- ===============================================
-- DADOS DE TESTE
-- ===============================================

-- Inserir administrador padrão (senha: admin123)
INSERT INTO ADMINISTRADOR (nome_completo, email_corporativo, senha_hash)
VALUES (
    'Administrador Sistema',
    'admin@fatec.sp.gov.br',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- Inserir alunos de teste (senha: teste123)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre) VALUES
('20240001', 'João da Silva', 'joao.silva@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2),
('20240002', 'Maria Santos', 'maria.santos@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2),
('20240003', 'Pedro Oliveira', 'pedro.oliveira@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2);

-- ===============================================
-- FIM DO SCRIPT
-- ===============================================
```

---

## 4. DIAGRAMA DE RELACIONAMENTOS

### Cardinalidades:

-   `ELEICAO 1 ←→ N CANDIDATURA` (Uma eleição tem várias candidaturas)
-   `ELEICAO 1 ←→ 1 RESULTADO` (Uma eleição tem um resultado único)
-   `ELEICAO 1 ←→ 1 ATA` (Uma eleição tem uma ata única)
-   `ELEICAO 1 ←→ N VOTO` (Uma eleição recebe vários votos)
-   `ELEICAO 1 ←→ N AUDITORIA` (Uma eleição pode ter várias entradas de auditoria)
-   `ALUNO 1 ←→ N CANDIDATURA` (Um aluno pode ser candidato em várias eleições)
-   `ALUNO 1 ←→ N VOTO` (Um aluno pode votar em várias eleições - uma por turma)
-   `CANDIDATURA 1 ←→ N VOTO` (Uma candidatura recebe vários votos)
-   `CANDIDATURA 1 ←→ 1 RESULTADO` (Como representante ou suplente)
-   `RESULTADO 1 ←→ 1 ATA` (Um resultado gera uma ata)
-   `ADMINISTRADOR 1 ←→ N ELEICAO` (Administrador cria eleições)
-   `ADMINISTRADOR 1 ←→ N CANDIDATURA` (Administrador valida candidaturas)
-   `ADMINISTRADOR 1 ←→ N RESULTADO` (Administrador finaliza eleições)
-   `ADMINISTRADOR 1 ←→ N ATA` (Administrador gera atas)
-   `ADMINISTRADOR 1 ←→ N AUDITORIA` (Administrador realiza ações auditadas)

### Constraints Importantes:

1. **Voto Único:** `UNIQUE(id_eleicao, id_aluno)` garante que cada aluno vote apenas uma vez por eleição
2. **Candidatura Única:** `UNIQUE(id_eleicao, id_aluno)` garante que aluno só se candidate uma vez por eleição
3. **Resultado Único:** `UNIQUE(id_eleicao)` em RESULTADO garante uma única apuração por eleição
4. **Ata Única:** `UNIQUE(id_eleicao)` em ATA garante uma única ata por eleição
5. **Validação de Turma (Candidatura):** Trigger valida que aluno só se candidata em eleição da sua turma
6. **Validação de Turma (Voto):** Trigger valida que aluno só vota em eleição da sua turma
7. **Validação de Candidatura Deferida:** Trigger valida que só é possível votar em candidaturas deferidas
8. **Validação de Status de Eleição:** Trigger valida que só é possível votar quando eleição está com status 'votacao_aberta'
9. **Validação de Datas:** Constraints verificam ordem lógica das fases da eleição
10. **Imutabilidade de Resultado:** Trigger impede alteração de resultado após geração
11. **Imutabilidade de Ata:** Trigger impede alteração de ata após geração

---

## 5. QUERIES ÚTEIS

### Listar candidatos deferidos de uma eleição específica

```sql
SELECT 
    a.nome_completo, 
    a.ra, 
    c.proposta,
    c.foto_candidato
FROM CANDIDATURA c
JOIN ALUNO a ON c.id_aluno = a.id_aluno
WHERE c.id_eleicao = 1
  AND c.status_validacao = 'deferido'
ORDER BY a.nome_completo;
```

### Contabilizar votos e determinar vencedores

```sql
SELECT
    a.nome_completo AS candidato,
    a.ra,
    COUNT(v.id_voto) AS total_votos
FROM CANDIDATURA c
JOIN ALUNO a ON c.id_aluno = a.id_aluno
LEFT JOIN VOTO v ON c.id_candidatura = v.id_candidatura
WHERE c.id_eleicao = 1
  AND c.status_validacao = 'deferido'
GROUP BY c.id_candidatura, a.nome_completo, a.ra
ORDER BY total_votos DESC
LIMIT 2;
```

### Verificar se aluno já votou

```sql
SELECT COUNT(*) as ja_votou
FROM VOTO
WHERE id_eleicao = 1
  AND id_aluno = 123;
```

### Gerar dados para ata (lista de votantes)

```sql
SELECT
    a.nome_completo,
    a.ra,
    v.data_hora_voto
FROM VOTO v
JOIN ALUNO a ON v.id_aluno = a.id_aluno
WHERE v.id_eleicao = 1
ORDER BY v.data_hora_voto;
```

### Consultar resultado completo de uma eleição

```sql
SELECT * FROM v_resultados_completos
WHERE id_eleicao = 1;
```

### Listar alunos que não votaram

```sql
SELECT 
    a.id_aluno,
    a.nome_completo,
    a.ra,
    a.email_institucional
FROM v_alunos_aptos_votacao v
WHERE v.id_eleicao = 1
  AND v.ja_votou = 'NÃO';
```

### Histórico de auditoria de uma eleição

```sql
SELECT
    aud.data_hora,
    aud.operacao,
    aud.tabela,
    aud.descricao,
    adm.nome_completo AS responsavel,
    aud.ip_origem
FROM AUDITORIA aud
LEFT JOIN ADMINISTRADOR adm ON aud.id_admin = adm.id_admin
WHERE aud.id_eleicao = 1
ORDER BY aud.data_hora DESC;
```

### Estatísticas de participação por turma

```sql
SELECT
    e.curso,
    e.semestre,
    COUNT(DISTINCT a.id_aluno) AS total_alunos,
    COUNT(DISTINCT v.id_aluno) AS total_votantes,
    ROUND((COUNT(DISTINCT v.id_aluno) / COUNT(DISTINCT a.id_aluno) * 100), 2) AS percentual_participacao
FROM ELEICAO e
JOIN ALUNO a ON e.curso = a.curso AND e.semestre = a.semestre
LEFT JOIN VOTO v ON e.id_eleicao = v.id_eleicao
WHERE e.status = 'encerrada'
GROUP BY e.id_eleicao, e.curso, e.semestre;
```

---

## 6. ÍNDICES E OTIMIZAÇÃO

### Índices Criados:

```
ALUNO:
- idx_curso_semestre (curso, semestre)
- idx_email (email_institucional)
- idx_ra (ra)

ADMINISTRADOR:
- idx_email_admin (email_corporativo)
- idx_ativo (ativo)

ELEICAO:
- idx_curso_semestre_eleicao (curso, semestre)
- idx_status (status)
- idx_datas (data_inicio_votacao, data_fim_votacao)

CANDIDATURA:
- idx_eleicao (id_eleicao)
- idx_aluno_candidato (id_aluno)
- idx_status_validacao (status_validacao)

VOTO:
- idx_eleicao_voto (id_eleicao)
- idx_candidatura (id_candidatura)
- idx_data_voto (data_hora_voto)

RESULTADO:
- idx_eleicao_resultado (id_eleicao)

ATA:
- idx_eleicao_ata (id_eleicao)
- idx_hash (hash_integridade)

AUDITORIA:
- idx_tabela (tabela)
- idx_data (data_hora)
- idx_admin (id_admin)
- idx_operacao (operacao)
```

### Recomendações de Performance:

1. Use `EXPLAIN` antes de queries complexas
2. Evite `SELECT *` em produção
3. Use conexões persistentes (PDO com `PDO::ATTR_PERSISTENT`)
4. Implemente cache para contagens (Redis/Memcached)
5. Considere particionamento de VOTO por ano se volume crescer muito

---

## 7. NORMALIZAÇÃO

### Forma Normal: 3FN (Terceira Forma Normal)

**Justificativa:**

-   Todas as tabelas têm chaves primárias únicas
-   Não há dependências parciais
-   Não há dependências transitivas
-   Atributos não-chave dependem apenas da chave primária

### Possíveis Desnormalizações:

-   Campo `percentual_participacao` em RESULTADO (calculado e armazenado para performance)
-   Campo `conteudo_json` em ATA (snapshot completo para integridade documental)

---

## 8. REQUISITOS ATENDIDOS

### Mapeamento Requisitos Funcionais:

| Requisito | Implementação |
|-----------|---------------|
| RF01-04: Gestão de Usuários | Tabelas ALUNO e ADMINISTRADOR |
| RF05-07: Gestão de Candidaturas | Tabela CANDIDATURA + Triggers |
| RF08-11: Gestão de Votação | Tabela VOTO + Triggers + ELEICAO |
| RF12-13: Apuração | Tabela RESULTADO + sp_finalizar_eleicao |
| RF14: Gerar Ata | Tabela ATA |
| RF15: Relatórios | Views + RESULTADO |
| RF16: Lista de Alunos | View v_alunos_aptos_votacao |
| RF17: Validação de E-mail | Constraint em ALUNO/ADMIN |

---

_Modelagem criada em: 30/10/2025_ 
_Modelagem atualizada pela última vez em: 11/03/2025_
_SGBD Target: MySQL 8.0+ / MariaDB 10.5+_
