# Sistema Integrado de Votação (SIV)

## Projeto Interdisciplinar 2 - FATEC Itapira

O **SIV (Sistema Integrado de Votação)** é uma plataforma web desenvolvida para automatizar o processo de eleição de representantes de turma na FATEC Itapira. Este projeto integra os conhecimentos adquiridos no 2º semestre do curso de Desenvolvimento de Software Multiplataforma.

---

## Índice

- [Sobre o Projeto](#sobre-o-projeto)
- [Participantes](#participantes)
- [Stack Tecnológico](#stack-tecnológico)
- [Arquitetura](#arquitetura)
- [Configuração do Ambiente (XAMPP)](#configuração-do-ambiente-xampp)
- [Credenciais de Teste](#credenciais-de-teste)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Funcionalidades](#funcionalidades)
- [Segurança](#segurança)
- [Documentação Técnica](#documentação-técnica)
- [Convenções de Código](#convenções-de-código)

---

## Sobre o Projeto

O sistema propõe uma alternativa digital ao processo manual atual, permitindo que alunos se candidatem como representantes e votem de forma sigilosa, enquanto administradores gerenciam todo o ciclo eleitoral, desde a criação de eleições até a geração de atas digitais e divulgação de resultados.

---

## Participantes

- Lucas Simões
- Gabriel Bueno
- Gabriel Borges
- Gian Miguel Oliveira

---

## Stack Tecnológico

| Camada               | Tecnologia                        |
| -------------------- | --------------------------------- |
| **Frontend**         | HTML5, CSS3, JavaScript (ES6+)    |
| **Backend**          | PHP 8.x com PDO                   |
| **Banco de Dados**   | MySQL 8.0+ ou MariaDB 10.4+       |
| **Servidor Web**     | Apache 2.4+                       |
| **Gerenciador**      | Composer                          |
| **Email**            | PHPMailer 7.0+                    |
| **Versionamento**    | Git / GitHub                      |


---

## Arquitetura

O projeto utiliza uma **arquitetura em camadas** com separação lógica de responsabilidades:

```
┌─────────────────────────────────────────┐
│  Camada de Apresentação                 │
│  (Páginas PHP com HTML/CSS/JS)          │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  Camada de Processamento                │
│  (Lógica de negócios e helpers)         │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  Camada de Persistência                 │
│  (PDO com Prepared Statements)          │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  Banco de Dados MySQL                   │
│  (Tabelas, Views, Triggers, Events)     │
└─────────────────────────────────────────┘
```

### Características:

- **Helpers reutilizáveis:** Funções de validação e utilitários centralizados
- **Configuração modular:** Separação de responsabilidades em `config/`
- **Segurança em múltiplas camadas:** CSRF, XSS, SQL Injection, Rate Limiting
- **Cache inteligente:** Sistema de cache para otimização de performance

---

## Configuração do Ambiente (XAMPP)

### Pré-requisitos

- **XAMPP 8.0+** (inclui PHP 8.x, MySQL/MariaDB, Apache)
- **Composer** (https://getcomposer.org/)
- **Navegador moderno** (Chrome, Firefox, Safari, Edge)
- **Git** (opcional, para clonar o repositório)

---

### Passo 1: Instalar XAMPP

1. Baixe o XAMPP em: https://www.apachefriends.org/
2. Instale o XAMPP em `C:\xampp` (Windows) ou `/opt/lampp` (Linux)
3. Inicie o **Painel de Controle do XAMPP**
4. Inicie os módulos **Apache** e **MySQL**

---

### Passo 2: Clonar o Projeto

Navegue até a pasta `htdocs` do XAMPP e clone o repositório:

```bash
cd C:\xampp\htdocs
git clone https://github.com/seu-usuario/sistema-integrado-votacao.git
cd sistema-integrado-votacao
```

**OU** faça o download manual e extraia para `C:\xampp\htdocs\sistema-integrado-votacao`

---

### Passo 3: Instalar Dependências com Composer

No diretório raiz do projeto, execute:

```bash
composer install
```

Isso irá instalar o PHPMailer e outras dependências automaticamente na pasta `vendor/`.

---

### Passo 4: Configurar Variáveis de Ambiente

1. Copie o arquivo de exemplo `.env.example` para `.env`:

```bash
cp .env.example .env
```

2. Edite o arquivo `.env` e configure suas credenciais SMTP:

```env
# Configurações de Email (SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=seu_email@gmail.com
SMTP_PASS=sua_senha_de_aplicativo_google
SMTP_FROM_EMAIL=seu_email@gmail.com
SMTP_FROM_NAME=SIV FATEC

# URL Base do Sistema
BASE_URL=http://localhost/sistema-integrado-votacao

# Modo de Desenvolvimento
# false = apenas emails institucionais + envia email real
# true = qualquer email + mostra link de confirmação na tela
# hybrid = qualquer email + envia email real
DEV_MODE=hybrid
```

**IMPORTANTE:** Para Gmail, você precisa gerar uma **Senha de Aplicativo**:
1. Acesse: https://myaccount.google.com/apppasswords
2. Crie uma senha de aplicativo para "Email"
3. Use essa senha no campo `SMTP_PASS`

---

### Passo 5: Configurar o Banco de Dados

#### 5.1 Criar o Banco de Dados

Acesse o **phpMyAdmin** em: http://localhost/phpmyadmin

Ou use o terminal MySQL:

```bash
# Entre no MySQL (senha padrão do XAMPP é vazia)
mysql -u root -p
```

Execute o script SQL principal:

```bash
# Importar estrutura completa (tabelas, views, triggers, constraints)
mysql -u root -p < database/siv_db.sql
```

**Nota:** Se estiver usando MySQL na porta 3307, adicione `-P 3307`:

```bash
mysql -u root -p -P 3307 < database/siv_db.sql
```

#### 5.2 Popular com Dados de Teste (Opcional)

Para facilitar o desenvolvimento, você pode popular o banco com dados de teste:

```bash
mysql -u root -p < database/popular_dados_teste.sql
```

Isso criará:
- 3 administradores
- 71 alunos distribuídos em diferentes cursos e semestres
- 2 eleições de teste (uma em votação, outra finalizada)
- Candidatos e votos de exemplo

---

### Passo 6: Configurar Conexão com o Banco

Verifique se as credenciais em `config/conexao.php` estão corretas:

```php
<?php
$host = "localhost";
$usuario = "root";
$senha = ""; // Vazio no XAMPP padrão
$banco = "siv_db";
$porta = 3306; // 3307 se estiver usando XAMPP com MySQL customizado
?>
```

---

### Passo 7: Acessar o Sistema

Abra seu navegador e acesse:

```
http://localhost/sistema-integrado-votacao
```

Se tudo estiver configurado corretamente, você verá a página inicial do SIV.

---

## Credenciais de Teste

Após popular o banco de dados com os dados de teste, você pode usar as seguintes credenciais:

### Administradores

| Nome                    | Email                          | Senha      |
| ----------------------- | ------------------------------ | ---------- |
| Admin Principal         | `admin@cps.sp.gov.br`          | `password` |
| Coordenador DSM         | `coordenador.dsm@cps.sp.gov.br`| `password` |
| Secretaria Acadêmica    | `secretaria@cps.sp.gov.br`     | `password` |

### Alunos de Teste (5 de Diferentes Semestres/Cursos)

| Nome                      | RA          | Email                            | Curso | Semestre | Senha      |
| ------------------------- | ----------- | -------------------------------- | ----- | -------- | ---------- |
| Lucas Henrique Silva      | 2024DSM001  | `lucas.silva@fatec.sp.gov.br`    | DSM   | 1        | `password` |
| Felipe Gomes Cardoso      | 2023DSM001  | `felipe.cardoso@fatec.sp.gov.br` | DSM   | 2        | `password` |
| André Correia Batista     | 2022DSM001  | `andre.batista@fatec.sp.gov.br`  | DSM   | 3        | `password` |
| Ricardo Moreira Santos    | 2024GE001   | `ricardo.moreira@fatec.sp.gov.br`| GE    | 2        | `password` |
| Marcos Vinícius Correia   | 2023GE001   | `marcos.correia@fatec.sp.gov.br` | GE    | 4        | `password` |

**Nota:** Todos os alunos de teste usam a senha `password`. No total, existem **71 alunos** distribuídos em:
- **DSM:** Semestres 1, 2 e 3 (40 alunos)
- **GE:** Semestres 2 e 4 (31 alunos)

---

## Estrutura do Projeto

```
sistema-integrado-votacao/
├── config/                          # Configurações (conexão, sessão, CSRF, email, helpers)
├── public/                          # Diretório público (páginas, assets, uploads)
│   ├── pages/guest/                # Páginas públicas (login, cadastro, recuperação)
│   ├── pages/user/                 # Páginas de alunos (dashboard, votação, inscrição)
│   └── pages/admin/                # Páginas administrativas (gestão, apuração, relatórios)
├── database/                        # Scripts SQL do banco de dados
├── storage/                         # Logs, cache e uploads privados
├── docs/                            # Documentação técnica completa
└── vendor/                          # Dependências Composer
```

> **Nota:** Para visualizar a estrutura detalhada do projeto, consulte a [documentação técnica](./docs/README.md).

---

## Funcionalidades

### Para Alunos
- Autenticação com email institucional `@fatec.sp.gov.br`
- Inscrição e gestão de candidaturas
- Votação online com garantia de voto único e secreto
- Acompanhamento de status das eleições

### Para Administradores
- Painel com estatísticas em tempo real
- Gerenciamento de eleições e prazos
- Validação de candidaturas
- Apuração e geração de atas em PDF
- Gerenciamento de usuários e relatórios

### Segurança e Automação
- Rate limiting (5 tentativas/15min), proteção CSRF/XSS/SQL Injection
- Cache de status e notificações por email
- Event Scheduler para automações do MySQL

---

## Segurança

- **Criptografia:** Senhas com bcrypt, tokens CSRF, regeneração de sessão
- **SQL Injection:** Prepared Statements com PDO em todas as queries
- **XSS:** `htmlspecialchars()` e Content Security Policy
- **Rate Limiting:** Máximo de 5 tentativas de login a cada 15 minutos
- **Auditoria:** Registro de ações administrativas com timestamp e IP

---

## Documentação Técnica

A documentação completa do projeto está disponível no diretório `/docs`:

- **[Especificação de Requisitos de Software (ERS)](./docs/especificacao-requisitos-software.md)**
  Requisitos funcionais (17 RF) e não funcionais (6 RNF), casos de uso, arquitetura

- **[Histórias de Usuário](./docs/historias-usuario-SIV.md)**
  Backlog do produto com 21 histórias e critérios de aceitação

- **[Modelagem de Banco de Dados](./docs/modelagem-banco-dados-SIV.md)**
  Diagrama ER, modelo relacional, scripts SQL, índices e otimizações

- **[Planejamento do Projeto](./docs/planejamento-projeto.md)**
  EAP, cronograma (7 semanas) e marcos do projeto


---

## Convenções de Código

| Elemento | Padrão | Exemplos |
|----------|--------|----------|
| **Arquivos PHP** | `kebab-case` | `gerenciar-alunos.php`, `editar-perfil.php` |
| **Arquivos CSS/JS** | `kebab-case` | `dashboard-admin.css`, `login.js` |
| **Classes CSS** | `kebab-case` | `.container`, `.header-site` |
| **Funções PHP** | `camelCase` | `validarSenha()`, `loginAluno()` |
| **Variáveis PHP** | `snake_case` | `$id_aluno`, `$senha_hash` |
| **Parâmetros PHP** | `camelCase` | `function validarSenha($senha, $minLength)` |
| **Classes PHP** | `PascalCase` | `class EmailService` |
| **Tabelas SQL** | `UPPER_CASE` | `ALUNO`, `ELEICAO`, `VOTO` |
| **Colunas SQL** | `snake_case` | `id_aluno`, `nome_completo` |
| **Indentação** | 4 espaços | Todos os arquivos |

---

## Commits Semânticos

Utilize o padrão de commits semânticos:

- `feat`: nova funcionalidade
- `fix`: correção de bug
- `docs`: apenas documentação
- `style`: formatação de código (sem alteração de lógica)
- `refactor`: refatoração de código
- `test`: adição ou modificação de testes
- `chore`: tarefas de manutenção

**Exemplos:**
```
feat(votacao): adiciona confirmação de senha ao votar
fix(login): corrige rate limiting não sendo aplicado
docs(readme): atualiza instruções de instalação
refactor(helpers): simplifica validação de email
```