# Histórias de Usuário - SIV

## Sistema Integrado de Votação

---

## 1. Épicos e Histórias de Usuário

### Épico 1: Gestão de Acesso e Autenticação

#### HU-01: Cadastro de Aluno

**Como** aluno da FATEC Itapira  
**Quero** me cadastrar no sistema usando meu e-mail institucional  
**Para** poder participar das eleições de representante de turma

**Critérios de Aceitação:**

-   O sistema deve validar o formato do e-mail institucional (@fatec.sp.gov.br)
-   Todos os campos obrigatórios devem ser preenchidos (RA, nome completo, curso, semestre, senha)
-   A senha deve ter no mínimo 8 caracteres, incluindo letras e números
-   O RA deve ser único no sistema
-   Deve ser enviado um e-mail de confirmação de cadastro

**Prioridade:** Alta  

---

#### HU-02: Login de Aluno

**Como** aluno cadastrado  
**Quero** fazer login no sistema  
**Para** acessar as funcionalidades de votação e candidatura

**Critérios de Aceitação:**

-   Login deve ser realizado com e-mail institucional e senha
-   Sistema deve validar credenciais no banco de dados
-   Após 3 tentativas incorretas, bloquear acesso por 15 minutos
-   Criar sessão PHP segura após login bem-sucedido
-   Opção "Esqueci minha senha" deve estar disponível

**Prioridade:** Alta  

---

#### HU-03: Cadastro de Administrador

**Como** gestor da FATEC  
**Quero** cadastrar administradores do sistema  
**Para** gerenciar o processo eleitoral

**Critérios de Aceitação:**

-   E-mail deve ser corporativo (@fatec.sp.gov.br ou @cps.sp.gov.br)
-   Apenas administradores master podem cadastrar novos administradores
-   Campos obrigatórios: nome completo, e-mail corporativo, senha
-   Registro de log de quem criou o administrador

**Prioridade:** Alta  

---

#### HU-04: Login de Administrador

**Como** administrador do sistema  
**Quero** fazer login com minhas credenciais  
**Para** gerenciar eleições e validar candidaturas

**Critérios de Aceitação:**

-   Login com e-mail corporativo e senha
-   Acesso a painel administrativo diferenciado
-   Autenticação em duas etapas (opcional)
-   Timeout de sessão após 30 minutos de inatividade

**Prioridade:** Alta  

---

### Épico 2: Gestão de Candidaturas

#### HU-05: Inscrever-se como Candidato

**Como** aluno autenticado  
**Quero** me candidatar como representante da minha turma  
**Para** concorrer à eleição

**Critérios de Aceitação:**

-   Candidatura só pode ser feita dentro do prazo estabelecido
-   Aluno deve pertencer ao curso/semestre para o qual está se candidatando
-   Deve permitir upload de foto (máx. 2MB, formatos: jpg, png)
-   Campo para proposta de candidatura (máx. 500 caracteres)
-   Aluno pode se candidatar apenas uma vez por eleição

**Prioridade:** Alta  

---

#### HU-06: Visualizar Candidatos

**Como** aluno da turma  
**Quero** ver a lista de candidatos da minha turma  
**Para** conhecer as opções de voto

**Critérios de Aceitação:**

-   Exibir apenas candidatos deferidos
-   Mostrar foto, nome e proposta de cada candidato
-   Filtrar automaticamente por curso/semestre do aluno logado
-   Ordem alfabética ou aleatória (configurável)
-   Indicar claramente o período de votação

**Prioridade:** Alta  

---

#### HU-07: Validar Candidaturas

**Como** administrador  
**Quero** validar (deferir/indeferir) candidaturas  
**Para** garantir que apenas candidatos elegíveis participem

**Critérios de Aceitação:**

-   Listar todas as candidaturas pendentes
-   Opções de deferir ou indeferir com justificativa obrigatória
-   Notificar candidato por e-mail sobre o resultado
-   Registrar histórico de validações
-   Prazo limite para validação antes do início da votação

**Prioridade:** Alta  

---

### Épico 3: Processo de Votação

#### HU-08: Votar em Candidato

**Como** aluno autenticado  
**Quero** votar em um candidato da minha turma  
**Para** escolher meu representante

**Critérios de Aceitação:**

-   Votação apenas dentro do período estabelecido
-   Um voto por aluno (constraint no banco de dados)
-   Confirmação antes de registrar o voto
-   Voto secreto - não pode ser rastreado ao votante
-   Comprovante de votação (sem mostrar em quem votou)
-   Interface clara e intuitiva para seleção

**Prioridade:** Alta  

---

#### HU-09: Configurar Período de Eleição

**Como** administrador  
**Quero** definir os períodos de candidatura e votação  
**Para** organizar o calendário eleitoral

**Critérios de Aceitação:**

-   Definir data/hora de início e fim para candidaturas
-   Definir data/hora de início e fim para votação
-   Período de candidatura deve terminar antes do início da votação
-   Possibilidade de estender prazos se necessário
-   Sistema deve respeitar automaticamente os períodos configurados

**Prioridade:** Alta  

---

#### HU-10: Impedir Votação Fora do Prazo

**Como** sistema  
**Quero** bloquear automaticamente votações fora do período estabelecido  
**Para** garantir a integridade do processo eleitoral

**Critérios de Aceitação:**

-   Desabilitar botões de votação fora do prazo
-   Exibir mensagem clara sobre status da eleição
-   Mostrar contador regressivo quando próximo de abrir/fechar
-   Validação server-side para prevenir manipulação

**Prioridade:** Alta  

---

### Épico 4: Apuração e Resultados

#### HU-11: Apurar Votos Automaticamente

**Como** administrador  
**Quero** que o sistema conte os votos automaticamente  
**Para** ter o resultado imediato e preciso

**Critérios de Aceitação:**

-   Contagem automática após encerramento do prazo
-   Identificar representante (mais votado) e suplente (segundo)
-   Tratamento de empates (critérios de desempate definidos)
-   Apresentar estatísticas: total de votos, abstenções, participação %
-   Garantir integridade da contagem (logs de auditoria)

**Prioridade:** Alta  

---

#### HU-12: Gerar Ata Digital

**Como** administrador  
**Quero** gerar uma ata digital da votação  
**Para** formalizar o processo eleitoral

**Critérios de Aceitação:**

-   Incluir lista de votantes (nome e RA)
-   Data e horário da eleição
-   Total de votos por candidato
-   Representante e suplente eleitos
-   Assinatura digital ou hash de validação
-   Exportar em PDF com marca d'água

**Prioridade:** Alta  

---

#### HU-13: Visualizar Relatórios

**Como** administrador  
**Quero** acessar relatórios detalhados por curso/semestre  
**Para** acompanhar e documentar o processo eleitoral

**Critérios de Aceitação:**

-   Filtros por curso, semestre, período
-   Gráficos de participação
-   Histórico de eleições anteriores
-   Exportar dados em CSV/Excel
-   Dashboard com métricas principais

**Prioridade:** Média  

---

#### HU-14: Consultar Lista de Alunos

**Como** administrador  
**Quero** visualizar a lista de alunos por curso e semestre  
**Para** verificar o eleitorado e gerenciar o processo

**Critérios de Aceitação:**

-   Exibir RA, nome completo, curso, semestre
-   Indicar se votou ou não (sem mostrar o voto)
-   Filtros e busca por nome/RA
-   Paginação para listas grandes
-   Exportar lista em PDF ou Excel

**Prioridade:** Média  

---

### Épico 5: Validação e Notificações

#### HU-15: Validar E-mail Institucional

**Como** sistema  
**Quero** validar o e-mail durante o cadastro  
**Para** garantir que apenas membros da instituição se cadastrem

**Critérios de Aceitação:**

-   Verificar domínio @fatec.sp.gov.br para alunos
-   Verificar domínios corporativos para administradores
-   Enviar e-mail de confirmação com link de ativação
-   Conta fica pendente até confirmação do e-mail
-   Reenvio de e-mail de confirmação se necessário

**Prioridade:** Alta  
**Pontos de História:** 5

---

---

## 2. Histórias Técnicas (Requisitos Não Funcionais)

### HT-01: Responsividade Mobile

**Como** desenvolvedor  
**Quero** implementar design responsivo  
**Para** garantir acesso via dispositivos móveis

**Critérios de Aceitação:**

-   Layout adaptável para telas de 320px até 1920px
-   Touch-friendly para elementos interativos (mínimo 44x44px)
-   Testes em iOS Safari, Chrome Android
-   Performance otimizada para 3G/4G
-   Modo offline básico (PWA opcional)

**Prioridade:** Alta  
**Pontos de História:** 8

---

### HT-02: Segurança de Dados

**Como** desenvolvedor  
**Quero** implementar medidas de segurança robustas  
**Para** proteger dados sensíveis dos usuários

**Critérios de Aceitação:**

-   Criptografia de senhas com bcrypt (mínimo 10 rounds)
-   HTTPS obrigatório em produção
-   Proteção contra SQL Injection (prepared statements)
-   Proteção CSRF em todos os formulários
-   Rate limiting para prevenir brute force
-   Sanitização de inputs e escape de outputs

**Prioridade:** Alta  
**Pontos de História:** 13

---

### HT-03: Performance

**Como** desenvolvedor  
**Quero** otimizar o desempenho do sistema  
**Para** garantir resposta rápida aos usuários

**Critérios de Aceitação:**

-   Tempo de resposta < 3 segundos para operações comuns
-   Lazy loading para imagens
-   Cache de queries frequentes
-   Compressão de assets (gzip)
-   CDN para recursos estáticos
-   Índices apropriados no banco de dados

**Prioridade:** Média  
**Pontos de História:** 8

---

### HT-04: Acessibilidade

**Como** desenvolvedor  
**Quero** implementar padrões de acessibilidade  
**Para** garantir uso por pessoas com deficiência

**Critérios de Aceitação:**

-   Conformidade WCAG 2.1 nível AA
-   Navegação completa via teclado
-   Alto contraste (mínimo 4.5:1 para texto normal)
-   Textos alternativos para imagens
-   ARIA labels apropriados
-   Testes com leitores de tela

**Prioridade:** Média  
**Pontos de História:** 5

---

### HT-05: Compatibilidade de Navegadores

**Como** desenvolvedor  
**Quero** garantir compatibilidade cross-browser  
**Para** atender todos os usuários

**Critérios de Aceitação:**

-   Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
-   Polyfills para funcionalidades não suportadas
-   Testes automatizados em diferentes navegadores
-   Graceful degradation para navegadores antigos
-   Mensagem de aviso para navegadores não suportados

**Prioridade:** Média  
**Pontos de História:** 5

---

### HT-06: Manutenibilidade do Código

**Como** desenvolvedor  
**Quero** seguir padrões de código limpo  
**Para** facilitar manutenção futura

**Critérios de Aceitação:**

-   PSR-12 para código PHP
-   Documentação inline (PHPDoc)
-   Arquitetura MVC clara
-   Testes unitários com cobertura > 70%
-   README completo com instruções de instalação
-   Versionamento semântico

**Prioridade:** Alta  
**Pontos de História:** 8

---

## 3. Matriz de Rastreabilidade

| ID História | Requisito ERS | Épico                    | Prioridade | Dependências |
| ----------- | ------------- | ------------------------ | ---------- | ------------ |
| HU-01       | RF1           | Gestão de Acesso         | Alta       | -            |
| HU-02       | RF2           | Gestão de Acesso         | Alta       | HU-01        |
| HU-03       | RF3           | Gestão de Acesso         | Alta       | -            |
| HU-04       | RF4           | Gestão de Acesso         | Alta       | HU-03        |
| HU-05       | RF5           | Gestão de Candidaturas   | Alta       | HU-02        |
| HU-06       | RF6           | Gestão de Candidaturas   | Alta       | HU-05, HU-07 |
| HU-07       | RF7           | Gestão de Candidaturas   | Alta       | HU-04        |
| HU-08       | RF8-RF9       | Processo de Votação      | Alta       | HU-02, HU-06 |
| HU-09       | RF10          | Processo de Votação      | Alta       | HU-04        |
| HU-10       | RF11          | Processo de Votação      | Alta       | HU-09        |
| HU-11       | RF12-RF13     | Apuração e Resultados    | Alta       | HU-08, HU-10 |
| HU-12       | RF14          | Apuração e Resultados    | Alta       | HU-11        |
| HU-13       | RF15          | Apuração e Resultados    | Média      | HU-11        |
| HU-14       | RF16          | Apuração e Resultados    | Média      | HU-04        |
| HU-15       | RF17          | Validação e Notificações | Alta       | HU-01, HU-03 |
| HT-01       | RNF1          | Técnico                  | Alta       | -            |
| HT-02       | RNF2          | Técnico                  | Alta       | -            |
| HT-03       | RNF3          | Técnico                  | Média      | -            |
| HT-04       | RNF4          | Técnico                  | Média      | -            |
| HT-05       | RNF5          | Técnico                  | Média      | -            |
| HT-06       | RNF6          | Técnico                  | Alta       | -            |

> **Nota:** Os requisitos RF e RNF correspondem à documentação completa em [especificacao-requisitos-software.md](./especificacao-requisitos-software.md)

---

## 4. Definition of Done (DoD)

Para que uma história seja considerada completa:

1. **Código**

    - Implementação completa conforme critérios de aceitação
    - Code review aprovado por pelo menos 1 desenvolvedor
    - Sem erros de linting (PSR-12 para PHP)

2. **Testes**

    - Testes unitários escritos e passando
    - Testes de integração para fluxos críticos
    - Teste manual realizado pelo QA

3. **Documentação**

    - Código comentado (PHPDoc)
    - README atualizado se necessário
    - Documentação de API para endpoints

4. **Segurança**

    - Sem vulnerabilidades críticas identificadas
    - Validação de inputs implementada
    - Sanitização de outputs aplicada

5. **Deploy**
    - Merge na branch develop sem conflitos
    - Deploy em ambiente de staging bem-sucedido
    - Aprovação do Product Owner

---

## 5. Planejamento de Sprints

### Sprint 1 (2 semanas) - Setup e Autenticação

-   HU-01: Cadastro de Aluno (5 pts)
-   HU-02: Login de Aluno (3 pts)
-   HU-03: Cadastro de Administrador (3 pts)
-   HU-04: Login de Administrador (3 pts)
-   HT-02: Segurança básica (5 pts)
    **Total: 19 pontos**

### Sprint 2 (2 semanas) - Candidaturas

-   HU-05: Inscrever-se como Candidato (5 pts)
-   HU-06: Visualizar Candidatos (3 pts)
-   HU-07: Validar Candidaturas (5 pts)
-   HU-15: Validar E-mail (5 pts)
    **Total: 18 pontos**

### Sprint 3 (2 semanas) - Votação

-   HU-08: Votar em Candidato (8 pts)
-   HU-09: Configurar Período (5 pts)
-   HU-10: Impedir Votação Fora do Prazo (3 pts)
    **Total: 16 pontos**

### Sprint 4 (2 semanas) - Resultados

-   HU-11: Apurar Votos (5 pts)
-   HU-12: Gerar Ata Digital (5 pts)
-   HU-13: Visualizar Relatórios (8 pts)
    **Total: 18 pontos**

### Sprint 5 (2 semanas) - Refinamentos e Testes

-   HU-14: Consultar Lista de Alunos (3 pts)
-   HT-01: Responsividade (8 pts)
-   HT-03: Performance (8 pts)
    **Total: 19 pontos**

### Sprint 6 (2 semanas) - Finalização

-   HT-04: Acessibilidade (5 pts)
-   HT-05: Compatibilidade (5 pts)
-   HT-06: Manutenibilidade (8 pts)
-   Testes de aceitação e correções
    **Total: 18 pontos**

**Velocity médio estimado:** 18 pontos por sprint
**Total de pontos do projeto:** 108 pontos
**Duração estimada:** 12 semanas (6 sprints)

---

**Documento versão 1.0** | **Última atualização:** Novembro 2025
