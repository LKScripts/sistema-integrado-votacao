# Planejamento do Projeto SIV
## Estrutura Analítica do Projeto (EAP) e Cronograma

---

## 1. Estrutura Analítica do Projeto (EAP/WBS)

![EAP - Projeto Integrador Segundo Semestre SIV](/assets/images/EAP.png)

### Legenda
- 🟢 **Verde:** Nó Principal do Projeto
- 🔵 **Azul:** Fases Principais (Nível 1)
- 🟡 **Amarelo:** Tarefas (Nível 2)
- ⚪ **Cinza:** Subtarefas (Nível 3)

---

### 1.1. Planejamento & Setup
- Review do kick-off e requisitos
- Setup do ambiente de desenvolvimento
- Design do banco de dados e criação do schema

### 1.2. Documentação & Refinamento de Design
- Atualizar protótipo no Figma
- Converter documentação para histórias de usuário

### 1.3. Refinamento de Front-End
- Refatorar HTML para preparar conteúdo dinâmico (PHP)
- Melhorar CSS e responsividade

### 1.4. Desenvolvimento Back-End

#### 1.4.1. Autenticação de Usuário
- Registro de usuários (alunos e admin)
- Forms de login
- Gerenciamento de sessão

#### 1.4.2. Gerenciamento de Eleições
- Criar e configurar eleições
- Registrar candidatos
- Lista de candidatos
- Finalizar eleição (trancar votação, calcular resultados)

#### 1.4.3. Sistema de Votação
- Lógica de votação
- Validação de voto único

#### 1.4.4. Relatórios
- Gerar ata em PDF

### 1.5. Integração e Segurança
- Integração de dados
- Implementação de segurança (SQL Injection, XSS, CSRF)
- Tratamento de erros

### 1.6. Teste e Garantia de Qualidade
- Testes unitários de funções e componentes
- Testes de integração e fluxos de usuários
- Testes de aceitação de usuários (UAT)
- Testes de segurança e edge cases

### 1.7. Documentação e Encerramento
- Compilação final da documentação no GitHub
- Preparação para apresentação
- Garantir funcionamento no ambiente de apresentação
- Retrospectiva e encerramento

---

## 2. Cronograma de Atividades

### Semana 1: Planejamento e Setup (27/10 – 02/11)
**Foco:** Planejamento do projeto e preparação do ambiente

- Converter documentação para histórias de usuário
- Design e implementação inicial do banco de dados
  - Criar schema completo
  - Configurar ambiente de BD (desenvolvimento e testes)
  - Scripts de criação de tabelas e relacionamentos

---

### Semana 2: Design + Integração Inicial (03/11 – 09/11)
**Foco:** Atualização visual e primeira integração

- Atualizar design do Figma
- Primeira integração Frontend-Backend:
  - Refatorar HTML base para PHP
  - Criar conexão com banco de dados
  - Implementar CRUD básico (entidade usuários)
  - Testar comunicação BD → Backend → Frontend
- Melhorar CSS e responsividade das páginas principais

---

### Semana 3: Backend Core - Autenticação (10/11 – 16/11)
**Foco:** Sistema de autenticação e autorização

**Sistema de Autenticação:**
- Cadastro de usuários
- Login/Logout
- Validação de credenciais
- Hash de senhas

**Gerenciamento de Sessão:**
- Controle de sessões PHP
- Middleware de autenticação
- Níveis de permissão (admin/aluno)
- Interface integrada de login/cadastro

---

### Semana 4: Backend Core - Funcionalidades Principais (17/11 – 23/11)
**Foco:** Eleições e candidaturas

**Gerenciamento de Eleição:**
- Criar/editar/listar eleições
- Definir períodos de votação
- Cadastrar candidatos
- Interface admin completa

**Sistema de Deferimento:**
- Validação de elegibilidade para votar
- Regras de negócio aplicadas
- Interface de aprovação/reprovação

**Início do Sistema de Votação:**
- Estrutura básica de registro de votos
- Garantir voto único por eleitor

---

### Semana 5: Votação + Segurança (24/11 – 30/11)
**Foco:** Completar votação e implementar segurança

**Conclusão da Lógica de Votação:**
- Interface de votação para alunos
- Confirmação de voto
- Validações (período, elegibilidade)

**Sistema de Resultado/Ata:**
- Apuração de votos
- Geração de relatórios
- Visualização de resultados

**Segurança de Dados:**
- Proteção contra SQL Injection
- Validação de inputs
- Segurança de sessões
- Logs de auditoria

---

### Semana 6: Testes e Refinamento (01/12 – 07/12)
**Foco:** Testes abrangentes e correções

**Testes:**
- Testes unitários de funções críticas (PHPUnit)
- Testes de integração (fluxos completos)
- Testes de aceitação de usuários
- Tratamento de edge cases

**Qualidade e Validação:**
- Correção de bugs identificados
- Tratamento robusto de erros
- Validação de todos os requisitos
- Prévia da apresentação (final da semana)

---

### Semana 7: Finalização (08/12 – 10/12)
**Foco:** Ajustes finais e preparação para entrega

- Ajustes finais baseados na prévia

**Revisão da Documentação:**
- Manual de usuário
- Documentação técnica
- Histórias de usuário atualizadas

**Preparação da Apresentação:**
- Slides de apresentação
- Demonstração do sistema
- Distribuição de falas
- Último teste geral do sistema

---

## 3. Marcos (Milestones)

| Data | Marco | Entrega |
|------|-------|---------|
| 02/11 | Setup Completo | BD modelado, ambiente configurado |
| 09/11 | Primeira Integração | CRUD funcional, conexão BD |
| 16/11 | Autenticação Funcional | Login/cadastro implementado |
| 23/11 | Core Backend | Eleições e candidaturas funcionais |
| 30/11 | Votação Completa | Sistema de votação e apuração |
| 07/12 | Testes Concluídos | Bateria completa de testes |
| 10/12 | Apresentação | Entrega final do PI-2 |

---

## 4. Distribuição de Esforço por Fase

| Fase | Estimativa | Prioridade |
|------|-----------|------------|
| 1. Planejamento & Setup | 1 semana | Alta |
| 2. Documentação & Design | 1 semana | Média |
| 3. Refinamento Frontend | 1 semana | Média |
| 4. Desenvolvimento Backend | 3 semanas | **Crítica** |
| 5. Integração e Segurança | 1 semana | Alta |
| 6. Testes e QA | 1 semana | Alta |
| 7. Documentação Final | 0.5 semana | Média |

**Total:** 7 semanas (27/10 a 10/12)

---

**Documento versão 1.0** | **Última atualização:** Novembro 2025
