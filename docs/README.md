# Documentação do Projeto SIV

Esta pasta contém toda a documentação técnica e de planejamento do Sistema Integrado de Votação (SIV).

---

## Documentos Disponíveis

### 1. [Especificação de Requisitos de Software (ERS)](./especificacao-requisitos-software.md)

**Documento principal do projeto**

Contém:

-   Visão geral e justificativa do sistema
-   Stack tecnológico e arquitetura
-   **Requisitos Funcionais (RF1-RF17)**
-   **Requisitos Não Funcionais (RNF1-RNF6)**
-   Referência à modelagem de banco de dados
-   **Diagrama de Casos de Uso (UML)**
-   Descrição detalhada dos casos de uso (UC01-UC05)
-   Medidas de segurança e estratégia de testes

---

### 2. [Histórias de Usuário](./historias-usuario-SIV.md)

**Backlog completo do produto**

Contém:

-   15 Histórias de Usuário Funcionais (HU-01 a HU-15)
-   6 Histórias Técnicas (HT-01 a HT-06)
-   Critérios de aceitação detalhados
-   Estimativas em pontos de história
-   Matriz de rastreabilidade (HU → RF)
-   Definition of Done (DoD)
-   Planejamento de 6 sprints

---

### 3. [Modelagem de Banco de Dados](./modelagem-banco-dados-SIV.md)

**Modelo completo do banco de dados**

Contém:

-   Diagrama Entidade-Relacionamento (Notação Chen)
-   Modelo Lógico Relacional
-   **Script SQL completo de criação**
-   Triggers de validação
-   Views úteis (candidatos deferidos, contagem de votos)
-   Índices e otimizações
-   Normalização (3FN)
-   Queries úteis para operações comuns

**SGBD:** MySQL 8.0+ / MariaDB 10.5+

---

### 4. [Planejamento do Projeto](./planejamento-projeto.md)

**EAP (WBS) e cronograma consolidado**

Contém:

-   Estrutura Analítica do Projeto (7 fases)
-   Cronograma semanal detalhado (7 semanas)
-   Marcos (milestones) e entregas
-   Distribuição de esforço por fase

**Período:** 27/10 a 10/12 (7 semanas)

---

## Relacionamento entre Documentos

```
ERS (Requisitos RF/RNF)
    ↓ rastreabilidade
Histórias de Usuário (HU → RF)
    ↓ referência
Planejamento (Sprints baseados em HU)
    ↓ implementação
Modelagem BD (Suporta requisitos)
```

---

## Resumo do Projeto

| Aspecto                       | Detalhes                                             |
| ----------------------------- | ---------------------------------------------------- |
| **Requisitos Funcionais**     | 17 (RF1-RF17)                                        |
| **Requisitos Não Funcionais** | 6 (RNF1-RNF6)                                        |
| **Histórias de Usuário**      | 21 (15 funcionais + 6 técnicas)                      |
| **Pontos de História**        | 108 pontos                                           |
| **Entidades do BD**           | 5 (ALUNO, ADMINISTRADOR, ELEICAO, CANDIDATURA, VOTO) |
| **Casos de Uso**              | 5 principais (UC01-UC05)                             |
| **Sprints**                   | 6 (2 semanas cada)                                   |

---

## Para Iniciar

1. **Leia primeiro:** [ERS](./especificacao-requisitos-software.md) para entender o projeto
2. **Implementação:** Consulte [Histórias de Usuário](./historias-usuario-SIV.md) para tarefas
3. **Banco de Dados:** Use o script SQL em [Modelagem BD](./modelagem-banco-dados-SIV.md)
4. **Cronograma:** Acompanhe o progresso em [Planejamento](./planejamento-projeto.md)

---

**Última atualização:** Novembro 2025
