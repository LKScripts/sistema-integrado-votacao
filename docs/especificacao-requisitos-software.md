# Especificação de Requisitos de Software (ERS)

## Sistema Integrado de Votação (SIV)

**Projeto:** Eleição de Representante de Turma
**Disciplina:** Engenharia de Software II
**Curso:** Desenvolvimento de Software Multiplataforma
**Equipe:** Gabriel Bueno Garcia, Gabriel Henrique Delalana Borges, Gian Miguel, Lucas da Silva Costa Simões

---

## 1. Introdução

### 1.1. Objetivo do Documento

Este documento apresenta a especificação completa de requisitos do **SIV (Sistema Integrado de Votação)**, desenvolvido no contexto do Projeto Interdisciplinar do 2º semestre (PI-2) do curso de Desenvolvimento de Software Multiplataforma da FATEC Itapira.

O SIV visa automatizar e modernizar o processo de eleição de representantes de turma, que atualmente é realizado de forma manual através do preenchimento físico de atas durante o horário de aula. O sistema proporciona praticidade, segurança, agilidade na apuração dos resultados e garantia de sigilo dos votos através de autenticação digital.

### 1.2. Escopo do Projeto PI-2

Este documento reflete o escopo do segundo semestre do projeto, com foco em:

-   **Desenvolvimento do Backend:** Implementação completa da camada de servidor usando PHP
-   **Modelagem de Banco de Dados:** Criação do modelo lógico e físico para MySQL/PostgreSQL
-   **Integração Frontend-Backend:** Conectar a interface desenvolvida no 1º semestre com a API PHP
-   **Implementação de Segurança:** Autenticação, criptografia de senhas, proteção contra SQL Injection e CSRF
-   **Testes Integrados:** Testes funcionais, de integração e de aceitação do sistema completo
-   **Documentação Técnica:** Finalização da documentação do projeto com diagramas e manuais

---

## 2. Visão Geral do Sistema

### 2.1. Visão do Produto

O SIV é uma plataforma web desenvolvida para modernizar e otimizar o processo de eleição de representantes de turma na FATEC de Itapira. Substitui o processo manual atual por uma solução digital segura, ágil e confiável.

**Funcionalidades Principais:**

-   **Login Segregado:** Áreas específicas para alunos e administradores
-   **Cadastro Simplificado:** Criação de contas com validação de e-mail institucional/corporativo
-   **Candidatura a Representante:** Inscrição online com validação administrativa
-   **Votação Online:** Sistema intuitivo organizado por curso e semestre, com voto secreto e seguro
-   **Gestão Administrativa:** Controle de prazos, validação de candidaturas, acompanhamento em tempo real e geração de relatórios
-   **Segurança e Integridade:** Votos secretos, autenticação segura e assinaturas digitais

### 2.2. Justificativa

O processo manual atual apresenta diversos problemas que comprometem a eficiência e confiabilidade do processo eleitoral:

-   Processo burocrático e moroso com alto risco de erros humanos
-   Falta de sigilo e possível constrangimento dos votantes
-   Ausência de transparência e dificuldade de acompanhamento
-   Fragilidade na segurança contra fraudes e votos duplicados
-   Restrição de participação por exigir presença física
-   Dificuldade no controle de prazos e gestão operacional

### 2.3. Arquitetura do Sistema

**Stack Tecnológico:**

| Camada         | Tecnologia                     |
| -------------- | ------------------------------ |
| Frontend       | HTML5, CSS3, JavaScript (ES6+) |
| Backend        | PHP 8.x                        |
| Banco de Dados | MySQL 8.0+                     |
| Servidor Web   | Apache 2.4+ / Nginx            |
| Versionamento  | Git / GitHub                   |

**Padrão de Arquitetura:**

-   MVC (Model-View-Controller) adaptado para PHP
-   API RESTful para comunicação Frontend-Backend
-   Separação clara entre camadas de apresentação, lógica de negócio e dados

---

## 3. Requisitos do Sistema

No contexto da Engenharia de Software, requisitos são descrições das funcionalidades, comportamentos e restrições que o sistema deve atender. Dividem-se em **requisitos funcionais** (o que o sistema deve fazer) e **não funcionais** (como o sistema deve funcionar).

### 3.1. Requisitos Funcionais

#### Gestão de Usuários

-   **RF1:** O sistema deve permitir o cadastro de alunos com e-mail institucional, RA, nome completo, curso, semestre e senha
-   **RF2:** O sistema deve permitir o login de alunos com e-mail institucional e senha
-   **RF3:** O sistema deve permitir o cadastro de administradores com e-mail corporativo, nome completo e senha
-   **RF4:** O sistema deve permitir o login de administradores com e-mail corporativo e senha

#### Gestão de Candidaturas

-   **RF5:** O aluno autenticado deve conseguir se candidatar como representante de turma, informando curso, semestre e proposta
-   **RF6:** O sistema deve listar os candidatos por curso e semestre para os alunos aptos a votar
-   **RF7:** O administrador deve validar (deferir ou indeferir) as candidaturas antes da votação

#### Gestão de Votação

-   **RF8:** O aluno autenticado deve poder votar em um candidato da sua turma (curso + semestre)
-   **RF9:** O sistema deve garantir que cada aluno vote apenas uma vez por eleição
-   **RF10:** O administrador deve definir o período de candidatura e o período de votação
-   **RF11:** O sistema deve desabilitar a votação fora do prazo definido

#### Apuração e Resultados

-   **RF12:** O sistema deve gerar a contagem de votos automaticamente após o encerramento do prazo
-   **RF13:** O sistema deve eleger o candidato mais votado como representante e o segundo como suplente
-   **RF14:** O sistema deve gerar uma ata digital de votação com nome e RA dos votantes
-   **RF15:** O administrador deve visualizar relatórios de resultado por curso/semestre
-   **RF16:** O administrador deve ter acesso à lista de alunos por curso e semestre

#### Validação e Segurança

-   **RF17:** O sistema deve validar o e-mail (institucional ou corporativo) durante o cadastro

### 3.2. Requisitos Não Funcionais

-   **RNF1 - Responsividade:** A aplicação deve ser responsiva e acessível via dispositivos móveis (smartphones e tablets)
-   **RNF2 - Segurança:** Os dados dos usuários e votos devem ser armazenados de forma segura (criptografia de senhas com bcrypt/Argon2, comunicação via HTTPS, proteção contra SQL Injection e CSRF)
-   **RNF3 - Performance:** O sistema deve ter um tempo de resposta inferior a 3 segundos para ações comuns (login, cadastro, voto)
-   **RNF4 - Usabilidade:** O sistema deve seguir boas práticas de usabilidade e acessibilidade (WCAG 2.1), incluindo contraste adequado e navegação por teclado
-   **RNF5 - Compatibilidade:** O sistema deve ser compatível com os principais navegadores (Chrome, Firefox, Safari, Edge) nas versões mais recentes
-   **RNF6 - Manutenibilidade:** O código deve seguir padrões de codificação PHP (PSR-12), ser modular e bem documentado

---

## 4. Modelagem de Banco de Dados

O modelo completo de banco de dados com diagramas ER, scripts SQL, views, triggers e otimizações está documentado em: **[modelagem-banco-dados-SIV.md](./modelagem-banco-dados-SIV.md)**

**Resumo das Entidades:**

-   **ALUNO:** Armazena dados dos estudantes (RA, nome, e-mail, curso, semestre)
-   **ADMINISTRADOR:** Armazena dados dos gestores do sistema
-   **ELEICAO:** Define períodos eleitorais por curso/semestre
-   **CANDIDATURA:** Registra inscrições de candidatos com proposta e status de validação
-   **VOTO:** Registra votos com constraint UNIQUE para garantir um voto por aluno

**Principais Relacionamentos:**

-   ELEICAO (1) → (N) CANDIDATURA
-   ALUNO (1) → (N) CANDIDATURA
-   ELEICAO (1) → (N) VOTO
-   CANDIDATURA (1) → (N) VOTO

---

## 5. Modelagem de Casos de Uso

A modelagem de casos de uso é uma técnica da Engenharia de Software utilizada para descrever como os usuários (atores) interagem com um sistema para atingir seus objetivos.

### 5.1. Atores do Sistema

-   **Aluno:** Usuário final que pode se cadastrar, votar e candidatar-se
-   **Administrador:** Gestor do sistema que controla eleições, valida candidaturas e gera relatórios
-   **Sistema:** Processa regras de negócio automaticamente (apuração, validação de prazos)

### 5.2. Diagrama de Casos de Uso

![Diagrama de Casos de Uso - SIV](/assets/images/uml-use-case.webp)

### 5.3. Descrição dos Casos de Uso

#### UC01 - Login no Sistema

**Ator:** Aluno / Administrador

**Fluxo Principal:**

1. O usuário acessa a tela de login
2. O sistema solicita e-mail e senha
3. O usuário insere as credenciais
4. O sistema valida os dados no banco de dados
5. O sistema redireciona ao painel correspondente e cria sessão PHP

**Fluxo Alternativo:**

-   **3a.** Credenciais inválidas: Sistema exibe mensagem de erro e retorna ao passo 2
-   **3b.** Após 3 tentativas incorretas: Sistema bloqueia acesso por 15 minutos

---

#### UC02 - Inscrever-se como Candidato

**Ator:** Aluno

**Pré-condição:** Estar logado e dentro do prazo de inscrição

**Fluxo Principal:**

1. O aluno acessa o menu de inscrição
2. O sistema exibe formulário (curso, semestre, proposta, foto)
3. O aluno preenche e submete os dados
4. O backend PHP valida e armazena no banco com status 'pendente'
5. O sistema confirma a inscrição e notifica o aluno

**Fluxo Alternativo:**

-   **1a.** Fora do prazo: Sistema exibe mensagem informativa e desabilita formulário
-   **3a.** Aluno já inscrito: Sistema informa que candidatura já existe

---

#### UC03 - Votar em Candidato

**Ator:** Aluno

**Pré-condição:** Estar logado e no período de votação

**Fluxo Principal:**

1. O aluno acessa o menu de votação
2. O sistema lista candidatos deferidos da turma do aluno
3. O aluno seleciona um candidato
4. O sistema solicita confirmação
5. O aluno confirma
6. O backend registra o voto e bloqueia nova votação (constraint UNIQUE)
7. O sistema exibe confirmação de voto computado

**Fluxo Alternativo:**

-   **2a.** Aluno já votou: Sistema exibe mensagem informando que voto já foi computado
-   **2b.** Sem candidatos: Sistema informa que não há candidatos deferidos

---

#### UC04 - Validar Candidaturas

**Ator:** Administrador

**Pré-condição:** Existem candidaturas com status 'pendente'

**Fluxo Principal:**

1. O administrador acessa painel de inscrições
2. O sistema lista candidaturas pendentes
3. O administrador analisa cada candidatura
4. O administrador decide por deferir ou indeferir
5. O sistema atualiza o status e notifica o candidato por e-mail

---

#### UC05 - Gerar Ata da Votação

**Ator:** Administrador

**Pré-condição:** Votação encerrada

**Fluxo Principal:**

1. O administrador acessa painel de resultados
2. O sistema calcula votos por candidato via SQL (COUNT)
3. O administrador solicita geração da ata
4. O sistema gera PDF com biblioteca PHP (FPDF/TCPDF)
5. O sistema disponibiliza download da ata

---

## 6. Segurança e Estratégia de Testes

### 6.1. Medidas de Segurança

#### Autenticação e Sessões

-   Criptografia de senhas com bcrypt ou Argon2 (PHP `password_hash`)
-   Sessões PHP seguras com regeneração de ID após login
-   Cookies com flags `Secure` e `HttpOnly`

#### Proteção Contra Ataques

-   **SQL Injection:** Uso de Prepared Statements (PDO ou MySQLi)
-   **XSS:** Escape de output com `htmlspecialchars()`
-   **CSRF:** Tokens CSRF em todos os formulários
-   **HTTPS:** Comunicação criptografada obrigatória (SSL/TLS)

#### Validação de Dados

-   Validação server-side de todos os inputs
-   Verificação de formato de e-mail institucional/corporativo
-   Limitação de tamanho de uploads (fotos de candidatos: máx. 2MB)

### 6.2. Estratégia de Testes

#### Testes Unitários

-   Testar funções PHP isoladamente (validações, cálculos, queries)
-   Framework sugerido: **PHPUnit**

#### Testes de Integração

-   Testar comunicação Frontend-Backend via requisições HTTP
-   Verificar persistência correta no banco de dados
-   Testar fluxo completo: login → votação → apuração

#### Testes de Segurança

-   Tentar SQL Injection em formulários
-   Verificar proteção CSRF
-   Testar acessos não autorizados a páginas restritas
-   Verificar votos duplicados (constraint de BD)

#### Testes de Aceitação do Usuário (UAT)

-   Simular eleição completa com usuários reais
-   Validar usabilidade e compreensão do fluxo
-   Coletar feedback para melhorias

---

## 7. Considerações Finais

### 7.1. Aprendizados do PI-2

Durante o desenvolvimento do segundo semestre, a equipe adquiriu conhecimentos fundamentais em:

-   **Desenvolvimento Backend com PHP:** Criação de APIs, manipulação de banco de dados, gestão de sessões
-   **Modelagem de Banco de Dados:** Normalização, relacionamentos, constraints e otimização de queries
-   **Segurança da Informação:** Implementação de autenticação, criptografia e proteção contra vulnerabilidades
-   **Integração Full-Stack:** Conexão entre frontend e backend, comunicação via HTTP/AJAX
-   **Testes de Software:** Planejamento e execução de testes em diferentes níveis

### 7.2. Próximos Passos

-   Finalizar implementação do backend PHP
-   Completar integração frontend-backend
-   Executar bateria completa de testes
-   Realizar simulações de eleição com usuários reais
-   Preparar apresentação para o seminário
-   Documentar código e criar manual de implantação

### 7.3. Referências

-   Projeto Pedagógico do Curso DSM - FATEC Itapira (2024)
-   Manual de Projetos Interdisciplinares para o CST em DSM - CPS (2021)
-   [PHP: The Right Way](https://phptherightway.com)
-   [OWASP Top Ten](https://owasp.org/www-project-top-ten)
-   [PSR-12: Extended Coding Style](https://www.php-fig.org/psr/psr-12)

---

**Documento versão 2.0** | **Última atualização:** Novembro 2025
