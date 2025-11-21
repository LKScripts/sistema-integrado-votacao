# Sistema Integrado de Votação (SIV)

## Projeto Interdisciplinar 2 - FATEC Itapira

O **SIV (Sistema Integrado de Votação)** é uma plataforma web desenvolvida para automatizar o processo de eleição de representantes de turma na FATEC Itapira. Este projeto integra os conhecimentos adquiridos no 2º semestre do curso de Desenvolvimento de Software Multiplataforma.

### Sobre o Projeto

O sistema propõe uma alternativa digital ao processo manual atual, permitindo que alunos se candidatem como representantes e votem de forma sigilosa, enquanto administradores gerenciam todo o ciclo eleitoral, desde a criação de eleições até a geração de atas digitais e divulgação de resultados.

**Principais funcionalidades:**

-   Autenticação segura com sessões PHP para alunos e administradores
-   Cadastro e gestão de candidaturas com validação administrativa
-   Sistema de votação online com garantia de voto único e secreto
-   Definição e controle automático de prazos eleitorais
-   Apuração automática de votos e geração de atas digitais
-   Painel administrativo completo com relatórios e dashboards

### Participantes

-   Lucas Simões
-   Gabriel Bueno
-   Gabriel Borges
-   Gian Miguel Oliveira

### Tecnologias Utilizadas

O projeto utiliza tecnologias full-stack para desenvolvimento web:

| Camada         | Tecnologia                     |
| -------------- | ------------------------------ |
| Frontend       | HTML5, CSS3, JavaScript (ES6+) |
| Backend        | PHP 8.x                        |
| Banco de Dados | MySQL 8.0+                     |
| Servidor Web   | Apache 2.4+                    |
| Versionamento  | Git / GitHub                   |

**Arquitetura:** MVC (Model-View-Controller) com API RESTful para comunicação entre frontend e backend.

### Estrutura do Projeto

```
sistema-integrado-votacao/
├── config/              # Arquivos de configuração
│   ├── conexao.php     # Configuração de banco de dados
│   └── session.php     # Gerenciamento de sessões
├── public/             # Arquivos acessíveis publicamente
│   ├── index.php       # Página inicial
│   ├── assets/         # CSS, JS, imagens
│   └── pages/          # Páginas da aplicação
│       ├── guest/      # Páginas públicas
│       ├── user/       # Páginas de alunos
│       └── admin/      # Páginas administrativas
├── storage/            # Arquivos gerados
│   ├── logs/           # Logs do sistema
│   └── uploads/        # Uploads de usuários
├── database/           # Scripts SQL
│   ├── siv_db.sql               # Schema principal (tabelas, views, triggers)
│   ├── add_constraints.sql       # Constraints CHECK
│   └── automacao_eleicoes.sql    # Procedures, functions e events
├── docs/               # Documentação
└── src/                # Código fonte (futuro)
```

### Documentação Técnica

A documentação completa do projeto está disponível no diretório `/docs`:

-   **[Especificação de Requisitos de Software (ERS)](./docs/especificacao-requisitos-software.md)** - Requisitos funcionais e não funcionais, casos de uso, arquitetura
-   **[Histórias de Usuário](./docs/historias-usuario-SIV.md)** - Backlog do produto com 21 histórias e critérios de aceitação
-   **[Modelagem de Banco de Dados](./docs/modelagem-banco-dados-SIV.md)** - Diagrama ER, modelo relacional, scripts SQL
-   **[Planejamento do Projeto](./docs/planejamento-projeto.md)** - EAP, cronograma e marcos do projeto
-   **[Integração com Banco de Dados](./docs/doc-temporario/INTEGRACAO_BANCO.md)** - Documentação da integração completa com MySQL
-   **[Guia de Estruturação](./docs/GUIA_ESTRUTURACAO.md)** - Guia de organização de pastas e melhores práticas

### Requisitos do Sistema

**Para executar o projeto localmente:**

-   PHP 8.0 ou superior
-   MySQL 8.0 ou superior
-   Servidor web Apache 2.4+
-   Navegador moderno (Chrome, Firefox, Safari, Edge)

### Como Executar

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/sistema-integrado-votacao.git
cd sistema-integrado-votacao
```

2. Configure o banco de dados em 3 etapas:
```bash
# Passo 1: Importar estrutura base (tabelas, views, triggers)
mysql -u root -p -P 3307 < database/siv_db.sql

# Passo 2: Adicionar constraints CHECK
mysql -u root -p -P 3307 < database/add_constraints.sql

# Passo 3: Configurar automação de eleições (procedures, functions, events)
mysql -u root -p -P 3307 < database/automacao_eleicoes.sql
```

**Nota:** A porta 3307 é para XAMPP. Se estiver usando MySQL padrão, remova `-P 3307`.

3. Configure as credenciais do banco em `config/conexao.php`

4. Inicie o servidor PHP (apontando para a pasta public):
```bash
# Opção 1: Servidor embutido do PHP
php -S localhost:8000 -t public

# Opção 2: XAMPP - copie o projeto para htdocs e acesse:
# http://localhost/sistema-integrado-votacao/public
```

5. Acesse no navegador: `http://localhost:8000`

### Credenciais Padrão

**Administrador:**
- Email: `admin@fatec.sp.gov.br`
- Senha: `password`

**Aluno de Teste:**
- Email: `joao.silva@fatec.sp.gov.br`
- Senha: `password`

### Convenções de Código

**Nomenclatura de arquivos:**
-   Utilizar `kebab-case` para nome dos arquivos
-   Utilizar caminhos relativos para referenciar arquivos
    -   Exemplo: `../../assets/styles/guest.css`

**HTML/CSS:**
-   Classes: `kebab-case`
-   Indentação: 4 espaços
-   Caminhos de imagens e CSS: sempre relativos ao arquivo atual

**JavaScript:**
-   Variáveis e funções: `camelCase`
-   Indentação: 4 espaços
-   Imports: caminhos relativos

**PHP:**
-   Seguir padrão PSR-12
-   Classes: `PascalCase`
-   Métodos e variáveis: `camelCase`
-   Indentação: 4 espaços
-   Includes: usar `require_once` com caminhos relativos ou `__DIR__`

### Commits Semânticos

-   `feat`: nova funcionalidade
-   `fix`: correção de bug
-   `docs`: apenas documentação
-   `style`: formatação de código (sem alteração de lógica)
-   `refactor`: refatoração de código
-   `test`: adição ou modificação de testes
-   `chore`: tarefas de manutenção

### Segurança

O sistema implementa as seguintes medidas de segurança:

-   Criptografia de senhas com bcrypt/Argon2
-   Proteção contra SQL Injection (Prepared Statements)
-   Proteção contra XSS (escape de output)
-   Tokens CSRF em formulários
-   Sessões PHP seguras
-   Comunicação HTTPS obrigatória

### Licença

Este projeto é desenvolvido para fins acadêmicos como parte do Projeto Interdisciplinar do curso DSM - FATEC Itapira.
