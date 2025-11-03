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
│ data_cadastro│     │   └──────────────────┘        │    status    │
└──────────────┘     │                               │ data_criacao │
                     │   ┌──────────────────┐        └──────────────┘
                     └──►│      VOTO        │              │
                         ├──────────────────┤              │
                         │ PK id_voto       │              │
                         │ FK id_eleicao    │◄─────────────┘
                         │ FK id_aluno      │
                         │ FK id_candidatura│
                         │ data_hora_voto   │
                         │ UNIQUE(eleicao,  │
                         │        aluno)    │
                         └──────────────────┘

┌──────────────────┐
│  ADMINISTRADOR   │
├──────────────────┤
│ PK id_admin      │
│ nome_completo    │
│ email_corporativo│
│ senha_hash       │
│ data_cadastro    │
└──────────────────┘
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
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

### Tabela: ADMINISTRADOR

```
ADMINISTRADOR (
    id_admin INT PRIMARY KEY AUTO_INCREMENT,
    nome_completo VARCHAR(255) NOT NULL,
    email_corporativo VARCHAR(255) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    UNIQUE(curso, semestre, data_inicio_candidatura)
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
    FOREIGN KEY (id_eleicao) REFERENCES ELEICAO(id_eleicao) ON DELETE CASCADE,
    FOREIGN KEY (id_aluno) REFERENCES ALUNO(id_aluno) ON DELETE CASCADE,
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
    FOREIGN KEY (id_eleicao) REFERENCES ELEICAO(id_eleicao) ON DELETE CASCADE,
    FOREIGN KEY (id_aluno) REFERENCES ALUNO(id_aluno) ON DELETE CASCADE,
    FOREIGN KEY (id_candidatura) REFERENCES CANDIDATURA(id_candidatura) ON DELETE CASCADE,
    UNIQUE(id_eleicao, id_aluno) COMMENT 'Garante um voto por aluno por eleição'
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

    INDEX idx_curso_semestre (curso, semestre),
    INDEX idx_email (email_institucional),

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

    INDEX idx_email_admin (email_corporativo)
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

    INDEX idx_curso_semestre_eleicao (curso, semestre),
    INDEX idx_status (status),

    UNIQUE KEY uk_eleicao_periodo (curso, semestre, data_inicio_candidatura),

    CONSTRAINT chk_semestre_eleicao CHECK (semestre BETWEEN 1 AND 6),
    CONSTRAINT chk_datas_candidatura CHECK (data_fim_candidatura > data_inicio_candidatura),
    CONSTRAINT chk_datas_votacao CHECK (data_fim_votacao > data_inicio_votacao),
    CONSTRAINT chk_ordem_fases CHECK (data_inicio_votacao >= data_fim_candidatura)
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

    INDEX idx_eleicao_voto (id_eleicao),
    INDEX idx_candidatura (id_candidatura),

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
-- TRIGGERS E PROCEDURES (OPCIONAL MAS RECOMENDADO)
-- ===============================================

-- Trigger para validar que aluno está votando em candidato da sua turma
DELIMITER //

CREATE TRIGGER trg_valida_voto_turma
BEFORE INSERT ON VOTO
FOR EACH ROW
BEGIN
    DECLARE v_curso_votante VARCHAR(100);
    DECLARE v_semestre_votante INT;
    DECLARE v_curso_eleicao VARCHAR(100);
    DECLARE v_semestre_eleicao INT;

    -- Pega curso e semestre do votante
    SELECT curso, semestre INTO v_curso_votante, v_semestre_votante
    FROM ALUNO WHERE id_aluno = NEW.id_aluno;

    -- Pega curso e semestre da eleição
    SELECT curso, semestre INTO v_curso_eleicao, v_semestre_eleicao
    FROM ELEICAO WHERE id_eleicao = NEW.id_eleicao;

    -- Valida se são da mesma turma
    IF v_curso_votante != v_curso_eleicao OR v_semestre_votante != v_semestre_eleicao THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Aluno só pode votar em eleição da sua turma';
    END IF;
END//

DELIMITER ;

-- ===============================================
-- VIEWS ÚTEIS
-- ===============================================

-- View para listar candidatos deferidos por eleição
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
    e.semestre
FROM CANDIDATURA c
JOIN ALUNO a ON c.id_aluno = a.id_aluno
JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
WHERE c.status_validacao = 'deferido';

-- View para contagem de votos por candidatura
CREATE VIEW v_contagem_votos AS
SELECT
    c.id_candidatura,
    c.id_eleicao,
    a.nome_completo AS nome_candidato,
    a.ra,
    e.curso,
    e.semestre,
    COUNT(v.id_voto) AS total_votos
FROM CANDIDATURA c
JOIN ALUNO a ON c.id_aluno = a.id_aluno
JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
LEFT JOIN VOTO v ON c.id_candidatura = v.id_candidatura
WHERE c.status_validacao = 'deferido'
GROUP BY c.id_candidatura, c.id_eleicao, a.nome_completo, a.ra, e.curso, e.semestre
ORDER BY e.id_eleicao, total_votos DESC;

-- ===============================================
-- DADOS DE TESTE (OPCIONAL)
-- ===============================================

-- Inserir um administrador padrão (senha: admin123)
INSERT INTO ADMINISTRADOR (nome_completo, email_corporativo, senha_hash)
VALUES (
    'Administrador Sistema',
    'admin@fatecitapira.edu.br',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- ===============================================
-- FIM DO SCRIPT
-- ===============================================
```

---

## 4. DIAGRAMA DE RELACIONAMENTOS

### Cardinalidades:

-   `ELEICAO 1 ←→ N CANDIDATURA` (Uma eleição tem várias candidaturas)
-   `ALUNO 1 ←→ N CANDIDATURA` (Um aluno pode ser candidato em várias eleições)
-   `ELEICAO 1 ←→ N VOTO` (Uma eleição recebe vários votos)
-   `ALUNO 1 ←→ N VOTO` (Um aluno pode votar em várias eleições - uma por turma)
-   `CANDIDATURA 1 ←→ N VOTO` (Uma candidatura recebe vários votos)

### Constraints Importantes:

1. **Voto Único:** `UNIQUE(id_eleicao, id_aluno)` garante que cada aluno vote apenas uma vez por eleição
2. **Candidatura Única:** `UNIQUE(id_eleicao, id_aluno)` garante que aluno só se candidate uma vez por eleição
3. **Validação de Turma:** Trigger valida que aluno só vota em eleição da sua turma
4. **Validação de Datas:** Constraints verificam ordem lógica das fases da eleição

---

## 5. QUERIES ÚTEIS

### Listar candidatos deferidos de uma eleição específica

```sql
SELECT a.nome_completo, a.ra, c.proposta
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
LIMIT 2; -- Representante e Suplente
```

### Verificar se aluno já votou

```sql
SELECT COUNT(*) as ja_votou
FROM VOTO
WHERE id_eleicao = 1
  AND id_aluno = 123;
```

### Gerar ata com lista de votantes

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

---

## 6. ÍNDICES E OTIMIZAÇÃO

### Índices Criados:

```
ALUNO:
- idx_curso_semestre (curso, semestre) → Para filtrar alunos por turma
- idx_email (email_institucional) → Para login rápido

ELEICAO:
- idx_curso_semestre_eleicao (curso, semestre) → Para buscar eleições por turma
- idx_status (status) → Para filtrar eleições ativas

CANDIDATURA:
- idx_eleicao (id_eleicao) → Para listar candidatos de uma eleição
- idx_status_validacao (status_validacao) → Para filtrar candidatos deferidos

VOTO:
- idx_eleicao_voto (id_eleicao) → Para contagem de votos
- idx_candidatura (id_candidatura) → Para contar votos por candidato
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

### Possíveis Desnormalizações (se necessário):

-   Adicionar campo `total_votos` em CANDIDATURA (atualizado por trigger) para performance
-   Adicionar campo `nome_curso` em ELEICAO para evitar joins constantes

---

_Modelagem criada em: 30/10/2025_  
_SGBD Target: MySQL 8.0+ / MariaDB 10.5+_
