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

### Documentação Técnica

A documentação completa do projeto está disponível no diretório `/docs`:

-   **[Especificação de Requisitos de Software (ERS)](./docs/especificacao-requisitos-software.md)** - Requisitos funcionais e não funcionais, casos de uso, arquitetura
-   **[Histórias de Usuário](./docs/historias-usuario-SIV.md)** - Backlog do produto com 21 histórias e critérios de aceitação
-   **[Modelagem de Banco de Dados](./docs/modelagem-banco-dados-SIV.md)** - Diagrama ER, modelo relacional, scripts SQL
-   **[Planejamento do Projeto](./docs/planejamento-projeto.md)** - EAP, cronograma e marcos do projeto

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

2. Configure o banco de dados:
```bash
mysql -u root -p < database/schema.sql
```

3. Configure as credenciais do banco em `src/config/database.php`

4. Inicie o servidor PHP:
```bash
php -S localhost:8000 -t public
```

5. Acesse no navegador: `http://localhost:8000`

### Convenções de Código

**Nomenclatura de arquivos:**
-   Utilizar `kebab-case` para nome dos arquivos
-   Utilizar `caminho absoluto` para referenciar arquivos (href, imports)

**HTML/CSS:**
-   Classes: `kebab-case`
-   Indentação: 4 espaços

**JavaScript:**
-   Variáveis e funções: `camelCase`
-   Indentação: 4 espaços

**PHP:**
-   Seguir padrão PSR-12
-   Classes: `PascalCase`
-   Métodos e variáveis: `camelCase`
-   Indentação: 4 espaços

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
