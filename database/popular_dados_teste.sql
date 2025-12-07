USE siv_db;

-- =====================================================
-- SCRIPT DE DADOS DE TESTE - SIV
-- =====================================================
-- IMPORTANTE: Este script é apenas para TESTE/DESENVOLVIMENTO
-- NÃO execute em ambiente de produção
--
-- FOTOS DOS CANDIDATOS:
-- Todas as imagens são de paisagens/lugares/comidas do Unsplash (sem pessoas)
-- - Montanhas, praias, comidas, cidades, natureza
-- - Um candidato por eleição não tem foto (demonstrar não-obrigatoriedade)
-- =====================================================

-- =====================================================
-- LIMPEZA DE DADOS ANTERIORES
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Limpar tabelas de dados (preservar estrutura e admin)
DELETE FROM VOTO;
DELETE FROM RESULTADO;
DELETE FROM CANDIDATURA;
DELETE FROM ELEICAO;
DELETE FROM ALUNO;
DELETE FROM AUDITORIA;

-- Resetar auto_increment
ALTER TABLE VOTO AUTO_INCREMENT = 1;
ALTER TABLE RESULTADO AUTO_INCREMENT = 1;
ALTER TABLE CANDIDATURA AUTO_INCREMENT = 1;
ALTER TABLE ELEICAO AUTO_INCREMENT = 1;
ALTER TABLE ALUNO AUTO_INCREMENT = 1;
ALTER TABLE AUDITORIA AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- POPULAÇÃO DE ADMINISTRADORES
-- =====================================================
-- Senha para todos: "password" (hash bcrypt)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- IMPORTANTE: Todos os admins já estão com email_confirmado = 1 (prontos para uso)

INSERT INTO ADMINISTRADOR (nome_completo, email_corporativo, senha_hash, ativo, email_confirmado) VALUES
('Admin Principal', 'admin@cps.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
('Coordenador DSM', 'coordenador.dsm@cps.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
('Secretaria Acadêmica', 'secretaria@cps.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);

-- =====================================================
-- POPULAÇÃO DE ALUNOS
-- =====================================================
-- Senha: "password" (hash bcrypt)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- -----------------------------------------------------
-- CURSO: DSM (Desenvolvimento de Software Multiplataforma)
-- -----------------------------------------------------

-- DSM - Semestre 1 (10 alunos)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2024DSM001', 'Lucas Henrique Silva', 'lucas.silva@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 1, 1),
('2024DSM002', 'Beatriz Costa Santos', 'beatriz.santos@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 1, 1),
('2024DSM003', 'Rafael Oliveira Lima', 'rafael.lima@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 1, 1),
('2024DSM004', 'Júlia Fernandes Alves', 'julia.alves@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 1, 1),
('2024DSM005', 'Matheus Souza Rocha', 'matheus.rocha@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 1, 1),
('2024DSM006', 'Amanda Pereira Dias', 'amanda.dias@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 1, 1),
('2024DSM007', 'Gabriel Martins Cruz', 'gabriel.cruz@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 1, 1),
('2024DSM008', 'Larissa Rodrigues Nunes', 'larissa.nunes@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 1, 1),
('2024DSM009', 'Thiago Barros Lopes', 'thiago.lopes@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 1, 1),
('2024DSM010', 'Camila Araújo Silva', 'camila.araujo@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 1, 1);

-- DSM - Semestre 2 (20 alunos - 12 já votaram, 8 disponíveis para teste)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2023DSM001', 'Felipe Gomes Cardoso', 'felipe.cardoso@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM002', 'Isabela Mendes Freitas', 'isabela.freitas@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM003', 'Vinicius Castro Ribeiro', 'vinicius.ribeiro@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM004', 'Fernanda Carvalho Sousa', 'fernanda.sousa@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM005', 'Bruno Almeida Teixeira', 'bruno.teixeira@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM006', 'Mariana Barbosa Costa', 'mariana.costa@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM007', 'Rodrigo Nascimento Pinto', 'rodrigo.pinto@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM008', 'Letícia Campos Moraes', 'leticia.moraes@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM009', 'Gustavo Ferreira Santos', 'gustavo.ferreira@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM010', 'Carolina Vieira Melo', 'carolina.melo@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM011', 'Diego Rezende Cunha', 'diego.cunha@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM012', 'Patrícia Monteiro Ramos', 'patricia.ramos@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
-- NOVOS ALUNOS DSM-2 (disponíveis para teste de voto em branco)
('2023DSM013', 'João Pedro Silva Teste', 'joao.teste@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM014', 'Maria Eduarda Teste', 'maria.teste@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM015', 'Pedro Henrique Teste', 'pedro.teste@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM016', 'Ana Clara Teste', 'ana.teste@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM017', 'Lucas Gabriel Teste', 'lucas.teste@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM018', 'Sophia Oliveira Teste', 'sophia.teste@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM019', 'Miguel Santos Teste', 'miguel.teste@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1),
('2023DSM020', 'Alice Costa Teste', 'alice.teste@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1);

-- DSM - Semestre 3 (10 alunos)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2022DSM001', 'André Correia Batista', 'andre.batista@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 3, 1),
('2022DSM002', 'Natália Duarte Azevedo', 'natalia.azevedo@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 3, 1),
('2022DSM003', 'Renato Fonseca Guimarães', 'renato.guimaraes@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 3, 1),
('2022DSM004', 'Bruna Siqueira Xavier', 'bruna.xavier@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 3, 1),
('2022DSM005', 'Leonardo Macedo Pires', 'leonardo.pires@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 3, 1),
('2022DSM006', 'Vanessa Lopes Miranda', 'vanessa.miranda@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 3, 1),
('2022DSM007', 'Eduardo Tavares Borges', 'eduardo.borges@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 3, 1),
('2022DSM008', 'Aline Neves Monteiro', 'aline.monteiro@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 3, 1),
('2022DSM009', 'Marcelo Santana Rocha', 'marcelo.rocha@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 3, 1),
('2022DSM010', 'Priscila Amaral Pinto', 'priscila.pinto@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 3, 1);

-- -----------------------------------------------------
-- CURSO: GE (Gestão Empresarial)
-- -----------------------------------------------------

-- GE - Semestre 2 (11 alunos)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2024GE001', 'Ricardo Moreira Santos', 'ricardo.moreira@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 2, 1),
('2024GE002', 'Tatiana Ribeiro Lopes', 'tatiana.lopes@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 2, 1),
('2024GE003', 'Fábio Andrade Melo', 'fabio.melo@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 2, 1),
('2024GE004', 'Daniela Carvalho Dias', 'daniela.dias@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 2, 1),
('2024GE005', 'Henrique Souza Neto', 'henrique.neto@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 2, 1),
('2024GE006', 'Sabrina Alves Castro', 'sabrina.castro@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 2, 1),
('2024GE007', 'Paulo César Lima', 'paulo.lima@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 2, 1),
('2024GE008', 'Carla Mendes Farias', 'carla.farias@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 2, 1),
('2024GE009', 'Roberto Freitas Araújo', 'roberto.araujo@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 2, 1),
('2024GE010', 'Simone Barbosa Prado', 'simone.prado@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 2, 1),
('2024GE011', 'Anderson Silva Martins', 'anderson.martins@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 2, 1);

-- GE - Semestre 4 (20 alunos - 13 já votaram, 7 disponíveis para teste)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2023GE001', 'Marcos Vinícius Correia', 'marcos.correia@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE002', 'Bianca Rocha Teixeira', 'bianca.teixeira@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE003', 'Alexandre Campos Neves', 'alexandre.neves@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE004', 'Juliana Costa Ramos', 'juliana.ramos@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE005', 'Fernando Oliveira Luz', 'fernando.luz@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE006', 'Adriana Pereira Gomes', 'adriana.gomes@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE007', 'César Augusto Soares', 'cesar.soares@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE008', 'Renata Figueiredo Duarte', 'renata.duarte@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE009', 'Leandro Batista Reis', 'leandro.reis@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE010', 'Viviane Santos Cardoso', 'viviane.cardoso@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE011', 'Cristiano Moura Machado', 'cristiano.machado@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE012', 'Luciana Rezende Fonseca', 'luciana.fonseca@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE013', 'Sérgio Henrique Barros', 'sergio.barros@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
-- NOVOS ALUNOS GE-4 (disponíveis para teste de voto em branco)
('2023GE014', 'Rafael Mendes Teste', 'rafael.teste.ge@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE015', 'Camila Rodrigues Teste', 'camila.teste.ge@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE016', 'Thiago Alves Teste', 'thiago.teste.ge@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE017', 'Beatriz Martins Teste', 'beatriz.teste.ge@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE018', 'Gabriel Costa Teste', 'gabriel.teste.ge@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE019', 'Larissa Souza Teste', 'larissa.teste.ge@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1),
('2023GE020', 'Felipe Lima Teste', 'felipe.teste.ge@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1);

-- GE - Semestre 5 (10 alunos)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2022GE001', 'Jorge Luís Tavares', 'jorge.tavares@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 5, 1),
('2022GE002', 'Mônica Aparecida Silva', 'monica.silva@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 5, 1),
('2022GE003', 'Antônio Carlos Lopes', 'antonio.lopes@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 5, 1),
('2022GE004', 'Eliane Cristina Borges', 'eliane.borges@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 5, 1),
('2022GE005', 'Márcio José Ferreira', 'marcio.ferreira@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 5, 1),
('2022GE006', 'Sandra Regina Cunha', 'sandra.cunha@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 5, 1),
('2022GE007', 'Wagner Almeida Souza', 'wagner.souza@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 5, 1),
('2022GE008', 'Vera Lúcia Monteiro', 'vera.monteiro@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 5, 1),
('2022GE009', 'Ronaldo Pereira Costa', 'ronaldo.costa@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 5, 1),
('2022GE010', 'Silvia Mara Ramos', 'silvia.ramos@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 5, 1);

-- -----------------------------------------------------
-- CURSO: GPI (Gestão da Produção Industrial)
-- -----------------------------------------------------

-- GPI - Semestre 3 (10 alunos)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2024GPI001', 'Alberto Santos Nunes', 'alberto.nunes@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 3, 1),
('2024GPI002', 'Claudia Oliveira Xavier', 'claudia.xavier@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 3, 1),
('2024GPI003', 'Denis Ferreira Macedo', 'denis.macedo@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 3, 1),
('2024GPI004', 'Elizabete Lima Pires', 'elizabete.pires@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 3, 1),
('2024GPI005', 'Francisco Gomes Miranda', 'francisco.miranda@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 3, 1),
('2024GPI006', 'Gisele Martins Azevedo', 'gisele.azevedo@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 3, 1),
('2024GPI007', 'Hugo Barbosa Guimarães', 'hugo.guimaraes@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 3, 1),
('2024GPI008', 'Ingrid Carvalho Batista', 'ingrid.batista@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 3, 1),
('2024GPI009', 'Júlio César Rocha', 'julio.rocha@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 3, 1),
('2024GPI010', 'Kelly Andrade Siqueira', 'kelly.siqueira@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 3, 1);

-- GPI - Semestre 6 (20 alunos - 12 já votaram, 8 disponíveis para teste)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2022GPI001', 'Nilton Souza Lopes', 'nilton.lopes@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI002', 'Olívia Ribeiro Dias', 'olivia.dias@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI003', 'Pedro Augusto Melo', 'pedro.melo@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI004', 'Queila Mendes Castro', 'queila.castro@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI005', 'Robson Alves Freitas', 'robson.freitas@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI006', 'Suzana Costa Neto', 'suzana.neto@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI007', 'Tiago Pereira Araújo', 'tiago.araujo@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI008', 'Úrsula Campos Prado', 'ursula.prado@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI009', 'Valter Lima Martins', 'valter.martins@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI010', 'Wanda Silva Farias', 'wanda.farias@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI011', 'Xavier Gomes Correia', 'xavier.correia@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI012', 'Yasmin Rodrigues Teixeira', 'yasmin.teixeira@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
-- NOVOS ALUNOS GPI-6 (disponíveis para teste de voto em branco)
('2022GPI013', 'Amanda Silva Teste', 'amanda.teste.gpi@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI014', 'Bruno Oliveira Teste', 'bruno.teste.gpi@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI015', 'Carolina Pereira Teste', 'carolina.teste.gpi@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI016', 'Daniel Santos Teste', 'daniel.teste.gpi@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI017', 'Eduarda Lima Teste', 'eduarda.teste.gpi@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI018', 'Fernando Costa Teste', 'fernando.teste.gpi@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI019', 'Gabriela Alves Teste', 'gabriela.teste.gpi@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1),
('2022GPI020', 'Henrique Souza Teste', 'henrique.teste.gpi@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1);

-- =====================================================
-- ELEIÇÕES
-- =====================================================
-- Metade com candidatura_aberta, metade com votacao_aberta
-- DATAS DINÂMICAS: Funcionam de 26/11/2025 até 10/12/2025

-- ELEIÇÕES COM CANDIDATURA ABERTA (3 eleições)
-- Período de candidatura: 5 dias antes de hoje até 16 dias após hoje
-- Período de votação: 17 dias após hoje até 30 dias após hoje
-- GARANTIA: Válido até 10/12 se executado em 26/11

-- DSM - Semestre 1
INSERT INTO ELEICAO (curso, semestre, data_inicio_candidatura, data_fim_candidatura, data_inicio_votacao, data_fim_votacao, status, criado_por)
VALUES ('DSM', 1,
        DATE_SUB(CURDATE(), INTERVAL 5 DAY),  -- 5 dias atrás
        DATE_ADD(CURDATE(), INTERVAL 16 DAY), -- +16 dias (fim candidatura)
        DATE_ADD(CURDATE(), INTERVAL 17 DAY), -- +17 dias (início votação)
        DATE_ADD(CURDATE(), INTERVAL 30 DAY), -- +30 dias (fim votação)
        'candidatura_aberta',
        1); -- Criado pelo admin padrão

-- GE - Semestre 2
INSERT INTO ELEICAO (curso, semestre, data_inicio_candidatura, data_fim_candidatura, data_inicio_votacao, data_fim_votacao, status, criado_por)
VALUES ('GE', 2,
        DATE_SUB(CURDATE(), INTERVAL 4 DAY),  -- 4 dias atrás
        DATE_ADD(CURDATE(), INTERVAL 17 DAY), -- +17 dias (fim candidatura)
        DATE_ADD(CURDATE(), INTERVAL 18 DAY), -- +18 dias (início votação)
        DATE_ADD(CURDATE(), INTERVAL 31 DAY), -- +31 dias (fim votação)
        'candidatura_aberta',
        1); -- Criado pelo admin padrão

-- GPI - Semestre 3
INSERT INTO ELEICAO (curso, semestre, data_inicio_candidatura, data_fim_candidatura, data_inicio_votacao, data_fim_votacao, status, criado_por)
VALUES ('GPI', 3,
        DATE_SUB(CURDATE(), INTERVAL 6 DAY),  -- 6 dias atrás
        DATE_ADD(CURDATE(), INTERVAL 15 DAY), -- +15 dias (fim candidatura)
        DATE_ADD(CURDATE(), INTERVAL 16 DAY), -- +16 dias (início votação)
        DATE_ADD(CURDATE(), INTERVAL 29 DAY), -- +29 dias (fim votação)
        'candidatura_aberta',
        1); -- Criado pelo admin padrão

-- ELEIÇÕES COM VOTAÇÃO ABERTA (3 eleições)
-- Período de candidatura: 20 dias atrás até 6 dias atrás
-- Período de votação: 5 dias atrás até 16 dias após hoje
-- GARANTIA: Válido até 10/12 se executado em 26/11 (26/11 + 16 = 12/12)

-- DSM - Semestre 2
INSERT INTO ELEICAO (curso, semestre, data_inicio_candidatura, data_fim_candidatura, data_inicio_votacao, data_fim_votacao, status, criado_por)
VALUES ('DSM', 2,
        DATE_SUB(CURDATE(), INTERVAL 20 DAY), -- 20 dias atrás (início candidatura)
        DATE_SUB(CURDATE(), INTERVAL 6 DAY),  -- 6 dias atrás (fim candidatura)
        DATE_SUB(CURDATE(), INTERVAL 5 DAY),  -- 5 dias atrás (início votação)
        DATE_ADD(CURDATE(), INTERVAL 16 DAY), -- +16 dias (fim votação) = 12/12
        'votacao_aberta',
        1); -- Criado pelo admin padrão

-- GE - Semestre 4
INSERT INTO ELEICAO (curso, semestre, data_inicio_candidatura, data_fim_candidatura, data_inicio_votacao, data_fim_votacao, status, criado_por)
VALUES ('GE', 4,
        DATE_SUB(CURDATE(), INTERVAL 18 DAY), -- 18 dias atrás (início candidatura)
        DATE_SUB(CURDATE(), INTERVAL 4 DAY),  -- 4 dias atrás (fim candidatura)
        DATE_SUB(CURDATE(), INTERVAL 3 DAY),  -- 3 dias atrás (início votação)
        DATE_ADD(CURDATE(), INTERVAL 18 DAY), -- +18 dias (fim votação) = 14/12
        'votacao_aberta',
        1); -- Criado pelo admin padrão

-- GPI - Semestre 6
INSERT INTO ELEICAO (curso, semestre, data_inicio_candidatura, data_fim_candidatura, data_inicio_votacao, data_fim_votacao, status, criado_por)
VALUES ('GPI', 6,
        DATE_SUB(CURDATE(), INTERVAL 19 DAY), -- 19 dias atrás (início candidatura)
        DATE_SUB(CURDATE(), INTERVAL 5 DAY),  -- 5 dias atrás (fim candidatura)
        DATE_SUB(CURDATE(), INTERVAL 4 DAY),  -- 4 dias atrás (início votação)
        DATE_ADD(CURDATE(), INTERVAL 17 DAY), -- +17 dias (fim votação) = 13/12
        'votacao_aberta',
        1); -- Criado pelo admin padrão

-- =====================================================
-- CANDIDATURAS - Eleições com CANDIDATURA ABERTA
-- =====================================================

-- Eleição 1: DSM - Semestre 1 (candidatura_aberta)
-- 4 candidatos: 2 deferidos (AMBOS COM foto), 1 pendente, 1 indeferido
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, foto_candidato, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(1, 1, 'Proposta para melhorar a comunicação entre alunos e professores, criando grupos de estudo e eventos de integração.', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 3, 'Melhorar a estrutura do laboratório de informática e promover hackathons internos para desenvolver habilidades práticas.', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 5, 'Criar canal de feedback direto com coordenação e organizar palestras com profissionais da área de tecnologia.', 'https://images.unsplash.com/photo-1511884642898-4c92249e20b6?w=400', 'pendente', NULL, NULL, NULL),
(1, 7, 'Implementar aulas extras aos finais de semana.', 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=400', 'indeferido', 'Proposta inviável devido a restrições de disponibilidade de professores e infraestrutura.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Eleição 2: GE - Semestre 2 (candidatura_aberta)
-- 3 candidatos: 2 deferidos (AMBOS COM foto), 1 pendente
-- NOTA: IDs ajustados após adição de 8 alunos em DSM-2 (deslocamento de +8)
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, foto_candidato, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(2, 42, 'Fortalecer a relação com empresas locais para conseguir mais estágios e visitas técnicas.', 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 44, 'Criar projeto de mentoria entre veteranos e calouros para facilitar adaptação ao curso.', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 46, 'Organizar semana de empreendedorismo com workshops e palestras sobre gestão de negócios.', 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=400', 'pendente', NULL, NULL, NULL);

-- Eleição 3: GPI - Semestre 3 (candidatura_aberta)
-- 3 candidatos: 1 deferido (COM foto), 2 pendentes
-- NOTA: IDs ajustados (deslocamento de +15)
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, foto_candidato, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(3, 83, 'Promover visitas técnicas a indústrias da região e criar parcerias para projetos práticos.', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 85, 'Implementar grupos de estudo focados em certificações industriais e normas de qualidade.', 'https://images.unsplash.com/photo-1465146344425-f00d5f5c8f07?w=400', 'pendente', NULL, NULL, NULL),
(3, 87, 'Melhorar o acesso a softwares de simulação industrial e criar biblioteca de cases práticos.', 'https://images.unsplash.com/photo-1513836279014-a89f7a76ae86?w=400', 'pendente', NULL, NULL, NULL);

-- =====================================================
-- CANDIDATURAS - Eleições com VOTAÇÃO ABERTA
-- =====================================================

-- Eleição 4: DSM - Semestre 2 (votacao_aberta)
-- 4 candidatos, TODOS deferidos (TODOS COM foto)
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, foto_candidato, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(4, 11, 'Criar clube de programação com competições e desafios semanais para aprimorar habilidades técnicas.', 'https://images.unsplash.com/photo-1534452203293-494d7ddbf7e0?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 13, 'Estabelecer parcerias com empresas de tecnologia para palestras, workshops e oportunidades de estágio.', 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 15, 'Desenvolver projetos open-source em equipe e criar repositório de códigos para consulta dos alunos.', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 17, 'Organizar eventos de networking com ex-alunos e profissionais atuantes na área de desenvolvimento.', 'https://images.unsplash.com/photo-1527489377706-5bf97e608852?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Eleição 5: GE - Semestre 4 (votacao_aberta)
-- 3 candidatos, TODOS deferidos (TODOS COM foto)
-- NOTA: IDs ajustados (deslocamento de +15)
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, foto_candidato, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(5, 60, 'Criar núcleo de estudos em gestão estratégica e realizar simulações de negócios.', 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 62, 'Organizar feira de empreendedorismo com participação de startups e empresas consolidadas.', 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 64, 'Implementar programa de consultoria júnior para empresas locais com supervisão de professores.', 'https://images.unsplash.com/photo-1463453091185-61582044d556?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Eleição 6: GPI - Semestre 6 (votacao_aberta)
-- 4 candidatos, TODOS deferidos (TODOS COM foto)
-- NOTA: IDs ajustados (GPI-6 agora IDs 92-111, deslocamento +15)
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, foto_candidato, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(6, 94, 'Criar laboratório de processos industriais com equipamentos de automação e controle.', 'https://images.unsplash.com/photo-1581094794329-c8112a89af12?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 96, 'Desenvolver projetos de melhoria contínua em parceria com indústrias da região.', 'https://images.unsplash.com/photo-1504309092620-4d0ec726efa4?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 98, 'Organizar semana de qualidade e produtividade com certificações e workshops especializados.', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 100, 'Implementar sistema de gestão à vista e painéis de indicadores no laboratório de práticas.', 'https://images.unsplash.com/photo-1518976024611-28bf4b48222e?w=400', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY));

-- =====================================================
-- VOTOS - Apenas nas Eleições com VOTAÇÃO ABERTA
-- =====================================================

-- Votos Eleição 4: DSM - Semestre 2 (12 alunos aptos - IDs 11 a 22)
-- Distribuição: Candidato 1=5 votos, Candidato 2=4 votos, Candidato 3=2 votos, Candidato 4=1 voto
-- (IDs das candidaturas: 11, 12, 13, 14)
-- DATAS DINÂMICAS: Votos de 3 dias atrás até hoje
INSERT INTO VOTO (id_eleicao, id_candidatura, id_aluno, data_hora_voto) VALUES
(4, 11, 11, DATE_SUB(NOW(), INTERVAL 3 DAY)), -- Felipe (ID 11) vota em candidato 1
(4, 11, 12, DATE_SUB(NOW(), INTERVAL 68 HOUR)), -- Isabela (ID 12) vota em candidato 1
(4, 11, 14, DATE_SUB(NOW(), INTERVAL 59 HOUR)), -- Fernanda (ID 14) vota em candidato 1
(4, 11, 16, DATE_SUB(NOW(), INTERVAL 50 HOUR)), -- Mariana (ID 16) vota em candidato 1
(4, 11, 19, DATE_SUB(NOW(), INTERVAL 39 HOUR)), -- Gustavo (ID 19) vota em candidato 1
(4, 12, 13, DATE_SUB(NOW(), INTERVAL 60 HOUR)), -- Vinicius (ID 13) vota em candidato 2
(4, 12, 15, DATE_SUB(NOW(), INTERVAL 58 HOUR)), -- Bruno (ID 15) vota em candidato 2
(4, 12, 18, DATE_SUB(NOW(), INTERVAL 48 HOUR)), -- Letícia (ID 18) vota em candidato 2
(4, 12, 21, DATE_SUB(NOW(), INTERVAL 37 HOUR)), -- Diego (ID 21) vota em candidato 2
(4, 13, 17, DATE_SUB(NOW(), INTERVAL 51 HOUR)), -- Rodrigo (ID 17) vota em candidato 3
(4, 13, 20, DATE_SUB(NOW(), INTERVAL 38 HOUR)), -- Carolina (ID 20) vota em candidato 3
(4, 14, 22, DATE_SUB(NOW(), INTERVAL 26 HOUR)); -- Patrícia (ID 22) vota em candidato 4

-- Votos Eleição 5: GE - Semestre 4 (20 alunos aptos - IDs 52 a 71, antes eram 44 a 56)
-- Distribuição: Candidato 1=6 votos, Candidato 2=4 votos, Candidato 3=3 votos
-- (IDs das candidaturas: 15, 16, 17)
-- DATAS DINÂMICAS: Votos de 2 dias atrás até hoje
-- NOTA: IDs dos alunos ajustados (+15 de deslocamento)
INSERT INTO VOTO (id_eleicao, id_candidatura, id_aluno, data_hora_voto) VALUES
(5, 15, 52, DATE_SUB(NOW(), INTERVAL 2 DAY)), -- Marcos vota em candidato 1
(5, 15, 53, DATE_SUB(NOW(), INTERVAL 47 HOUR)), -- Bianca vota em candidato 1
(5, 15, 55, DATE_SUB(NOW(), INTERVAL 45 HOUR)), -- Juliana vota em candidato 1
(5, 15, 57, DATE_SUB(NOW(), INTERVAL 43 HOUR)), -- Adriana vota em candidato 1
(5, 15, 59, DATE_SUB(NOW(), INTERVAL 41 HOUR)), -- Renata vota em candidato 1
(5, 15, 61, DATE_SUB(NOW(), INTERVAL 15 HOUR)), -- Viviane vota em candidato 1
(5, 16, 54, DATE_SUB(NOW(), INTERVAL 46 HOUR)), -- Alexandre vota em candidato 2
(5, 16, 56, DATE_SUB(NOW(), INTERVAL 44 HOUR)), -- Fernando vota em candidato 2
(5, 16, 60, DATE_SUB(NOW(), INTERVAL 40 HOUR)), -- Leandro vota em candidato 2
(5, 16, 63, DATE_SUB(NOW(), INTERVAL 13 HOUR)), -- Luciana vota em candidato 2
(5, 17, 58, DATE_SUB(NOW(), INTERVAL 42 HOUR)), -- César vota em candidato 3
(5, 17, 62, DATE_SUB(NOW(), INTERVAL 14 HOUR)), -- Cristiano vota em candidato 3
(5, 17, 64, DATE_SUB(NOW(), INTERVAL 12 HOUR)); -- Sérgio vota em candidato 3

-- Votos Eleição 6: GPI - Semestre 6 (20 alunos aptos - IDs 92 a 111, antes eram 77 a 88)
-- Distribuição: Candidato 1=4 votos, Candidato 2=3 votos, Candidato 3=3 votos, Candidato 4=2 votos
-- (IDs das candidaturas: 18, 19, 20, 21)
-- DATAS DINÂMICAS: Votos de 3 dias atrás até 1 dia atrás
-- NOTA: IDs dos alunos ajustados (+15 de deslocamento)
INSERT INTO VOTO (id_eleicao, id_candidatura, id_aluno, data_hora_voto) VALUES
(6, 18, 92, DATE_SUB(NOW(), INTERVAL 3 DAY)), -- Nilton vota em candidato 1
(6, 18, 94, DATE_SUB(NOW(), INTERVAL 66 HOUR)), -- Pedro vota em candidato 1
(6, 18, 97, DATE_SUB(NOW(), INTERVAL 62 HOUR)), -- Suzana vota em candidato 1
(6, 18, 100, DATE_SUB(NOW(), INTERVAL 39 HOUR)), -- Úrsula vota em candidato 1
(6, 19, 93, DATE_SUB(NOW(), INTERVAL 67 HOUR)), -- Olívia vota em candidato 2
(6, 19, 96, DATE_SUB(NOW(), INTERVAL 64 HOUR)), -- Robson vota em candidato 2
(6, 19, 99, DATE_SUB(NOW(), INTERVAL 56 HOUR)), -- Tiago vota em candidato 2
(6, 20, 95, DATE_SUB(NOW(), INTERVAL 65 HOUR)), -- Queila vota em candidato 3
(6, 20, 98, DATE_SUB(NOW(), INTERVAL 61 HOUR)), -- Valter vota em candidato 3
(6, 20, 102, DATE_SUB(NOW(), INTERVAL 37 HOUR)), -- Xavier vota em candidato 3
(6, 21, 101, DATE_SUB(NOW(), INTERVAL 38 HOUR)), -- Wanda vota em candidato 4
(6, 21, 103, DATE_SUB(NOW(), INTERVAL 35 HOUR)); -- Yasmin vota em candidato 4

-- =====================================================
-- FINALIZAÇÃO
-- =====================================================

-- Resumo da população:
SELECT 'RESUMO DA POPULAÇÃO DE DADOS' as '';
SELECT '=====================================' as '';

SELECT CONCAT('Total de alunos cadastrados: ', COUNT(*)) as 'ALUNOS'
FROM ALUNO;

SELECT curso, semestre, COUNT(*) as total_alunos
FROM ALUNO
GROUP BY curso, semestre
ORDER BY curso, semestre;

SELECT CONCAT('Total de eleições criadas: ', COUNT(*)) as 'ELEIÇÕES'
FROM ELEICAO;

SELECT id_eleicao, curso, semestre, status,
       DATE_FORMAT(data_inicio_candidatura, '%d/%m/%Y') as inicio_candidatura,
       DATE_FORMAT(data_fim_votacao, '%d/%m/%Y') as fim_votacao
FROM ELEICAO
ORDER BY id_eleicao;

SELECT CONCAT('Total de candidaturas: ', COUNT(*)) as 'CANDIDATURAS'
FROM CANDIDATURA;

SELECT status_validacao, COUNT(*) as total
FROM CANDIDATURA
GROUP BY status_validacao;

SELECT CONCAT('Total de votos registrados: ', COUNT(*)) as 'VOTOS'
FROM VOTO;

SELECT e.curso, e.semestre, e.status, COUNT(v.id_voto) as total_votos
FROM ELEICAO e
LEFT JOIN VOTO v ON e.id_eleicao = v.id_eleicao
GROUP BY e.id_eleicao, e.curso, e.semestre, e.status
ORDER BY e.id_eleicao;

-- =====================================================
-- ALUNOS PARA NOVOS SEMESTRES (para teste de apuração)
-- =====================================================

-- GE - Semestre 1 (12 alunos para teste aguardando_finalizacao)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2024GE101', 'Alberto Souza Martins', 'alberto.martins.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1),
('2024GE102', 'Bruna Lima Santos', 'bruna.santos.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1),
('2024GE103', 'Carlos Eduardo Silva', 'carlos.silva.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1),
('2024GE104', 'Diana Oliveira Costa', 'diana.costa.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1),
('2024GE105', 'Eduardo Pereira Lima', 'eduardo.lima.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1),
('2024GE106', 'Fernanda Rocha Alves', 'fernanda.alves.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1),
('2024GE107', 'Gustavo Mendes Freitas', 'gustavo.freitas.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1),
('2024GE108', 'Helena Castro Ribeiro', 'helena.ribeiro.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1),
('2024GE109', 'Igor Carvalho Sousa', 'igor.sousa.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1),
('2024GE110', 'Juliana Barbosa Dias', 'juliana.dias.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1),
('2024GE111', 'Kevin Almeida Teixeira', 'kevin.teixeira.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1),
('2024GE112', 'Larissa Campos Moraes', 'larissa.moraes.ge1@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 1, 1);

-- DSM - Semestre 4 (15 alunos para teste aguardando_finalizacao)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2022DSM401', 'Marcos Vinícius Santos', 'marcos.santos.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM402', 'Natália Rodrigues Lima', 'natalia.lima.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM403', 'Otávio Costa Ferreira', 'otavio.ferreira.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM404', 'Paula Martins Nunes', 'paula.nunes.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM405', 'Quintino Barbosa Lopes', 'quintino.lopes.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM406', 'Rafaela Alves Cruz', 'rafaela.cruz.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM407', 'Samuel Gomes Moraes', 'samuel.moraes.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM408', 'Tatiana Silva Pinto', 'tatiana.pinto.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM409', 'Ulisses Pereira Ramos', 'ulisses.ramos.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM410', 'Vanessa Oliveira Cunha', 'vanessa.cunha.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM411', 'Wagner Santos Rezende', 'wagner.rezende.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM412', 'Ximena Lima Monteiro', 'ximena.monteiro.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM413', 'Yuri Carvalho Borges', 'yuri.borges.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM414', 'Zilda Rocha Miranda', 'zilda.miranda.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1),
('2022DSM415', 'André Souza Tavares', 'andre.tavares.dsm4@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 4, 1);

-- GPI - Semestre 2 (18 alunos para teste encerrada)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2023GPI201', 'Adriano Ferreira Costa', 'adriano.costa.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI202', 'Beatriz Mendes Lima', 'beatriz.lima.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI203', 'Caio Almeida Santos', 'caio.santos.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI204', 'Débora Rodrigues Silva', 'debora.silva.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI205', 'Elias Barbosa Nunes', 'elias.nunes.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI206', 'Flávia Carvalho Moraes', 'flavia.moraes.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI207', 'Guilherme Gomes Freitas', 'guilherme.freitas.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI208', 'Heloísa Martins Cruz', 'heloisa.cruz.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI209', 'Ivan Oliveira Lopes', 'ivan.lopes.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI210', 'Jéssica Pereira Dias', 'jessica.dias.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI211', 'Kleber Santos Castro', 'kleber.castro.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI212', 'Lívia Rocha Ribeiro', 'livia.ribeiro.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI213', 'Mauro Costa Souza', 'mauro.souza.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI214', 'Nadia Lima Alves', 'nadia.alves.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI215', 'Oscar Ferreira Pinto', 'oscar.pinto.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI216', 'Priscila Mendes Ramos', 'priscila.ramos.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI217', 'Quênia Silva Monteiro', 'quenia.monteiro.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1),
('2023GPI218', 'Ricardo Alves Borges', 'ricardo.borges.gpi2@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 2, 1);

-- DSM - Semestre 5 (16 alunos para teste encerrada)
INSERT INTO ALUNO (ra, nome_completo, email_institucional, senha_hash, curso, semestre, ativo) VALUES
('2021DSM501', 'Alexandre Costa Santos', 'alexandre.santos.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM502', 'Bianca Oliveira Lima', 'bianca.lima.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM503', 'Cristiano Pereira Silva', 'cristiano.silva.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM504', 'Daniela Rodrigues Costa', 'daniela.costa.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM505', 'Éderson Almeida Nunes', 'ederson.nunes.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM506', 'Fabiana Santos Moraes', 'fabiana.moraes.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM507', 'Giovani Carvalho Freitas', 'giovani.freitas.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM508', 'Heloísa Mendes Cruz', 'heloisa.cruz.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM509', 'Ícaro Barbosa Lopes', 'icaro.lopes.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM510', 'Jaqueline Gomes Dias', 'jaqueline.dias.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM511', 'Kauê Silva Castro', 'kaue.castro.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM512', 'Letícia Rocha Ribeiro', 'leticia.ribeiro.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM513', 'Murilo Costa Souza', 'murilo.souza.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM514', 'Nathalia Lima Alves', 'nathalia.alves.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM515', 'Osvaldo Ferreira Pinto', 'osvaldo.pinto.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1),
('2021DSM516', 'Patrícia Mendes Ramos', 'patricia.ramos.dsm5@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 5, 1);

-- =====================================================
-- ELEIÇÕES PARA TESTE DE APURAÇÃO
-- =====================================================
-- IMPORTANTE: Criar com status 'votacao_aberta' primeiro para permitir inserção de votos
-- Depois atualizar para o status correto (aguardando_finalizacao ou encerrada)

-- ELEIÇÃO 7: GE - Semestre 1 (será AGUARDANDO_FINALIZACAO)
-- Votação já terminou (ontem), aguarda apuração manual
INSERT INTO ELEICAO (curso, semestre, data_inicio_candidatura, data_fim_candidatura, data_inicio_votacao, data_fim_votacao, status, criado_por)
VALUES ('GE', 1,
        DATE_SUB(CURDATE(), INTERVAL 15 DAY), -- 15 dias atrás (início candidatura)
        DATE_SUB(CURDATE(), INTERVAL 8 DAY),  -- 8 dias atrás (fim candidatura)
        DATE_SUB(CURDATE(), INTERVAL 7 DAY),  -- 7 dias atrás (início votação)
        DATE_SUB(CURDATE(), INTERVAL 1 DAY),  -- 1 dia atrás (fim votação)
        'votacao_aberta',  -- Temporariamente aberta para inserir votos
        1);

-- ELEIÇÃO 8: DSM - Semestre 4 (será AGUARDANDO_FINALIZACAO)
-- Votação terminou há 2 dias, aguarda apuração
INSERT INTO ELEICAO (curso, semestre, data_inicio_candidatura, data_fim_candidatura, data_inicio_votacao, data_fim_votacao, status, criado_por)
VALUES ('DSM', 4,
        DATE_SUB(CURDATE(), INTERVAL 20 DAY), -- 20 dias atrás (início candidatura)
        DATE_SUB(CURDATE(), INTERVAL 10 DAY), -- 10 dias atrás (fim candidatura)
        DATE_SUB(CURDATE(), INTERVAL 9 DAY),  -- 9 dias atrás (início votação)
        DATE_SUB(CURDATE(), INTERVAL 2 DAY),  -- 2 dias atrás (fim votação)
        'votacao_aberta',  -- Temporariamente aberta para inserir votos
        1);

-- ELEIÇÃO 9: GPI - Semestre 2 (será ENCERRADA)
-- Votação terminou há 5 dias, já foi apurada
INSERT INTO ELEICAO (curso, semestre, data_inicio_candidatura, data_fim_candidatura, data_inicio_votacao, data_fim_votacao, status, criado_por)
VALUES ('GPI', 2,
        DATE_SUB(CURDATE(), INTERVAL 25 DAY), -- 25 dias atrás (início candidatura)
        DATE_SUB(CURDATE(), INTERVAL 12 DAY), -- 12 dias atrás (fim candidatura)
        DATE_SUB(CURDATE(), INTERVAL 11 DAY), -- 11 dias atrás (início votação)
        DATE_SUB(CURDATE(), INTERVAL 5 DAY),  -- 5 dias atrás (fim votação)
        'votacao_aberta',  -- Temporariamente aberta para inserir votos
        1);

-- ELEIÇÃO 10: DSM - Semestre 5 (será ENCERRADA)
-- Votação terminou há 3 dias, já foi apurada
INSERT INTO ELEICAO (curso, semestre, data_inicio_candidatura, data_fim_candidatura, data_inicio_votacao, data_fim_votacao, status, criado_por)
VALUES ('DSM', 5,
        DATE_SUB(CURDATE(), INTERVAL 22 DAY), -- 22 dias atrás (início candidatura)
        DATE_SUB(CURDATE(), INTERVAL 10 DAY), -- 10 dias atrás (fim candidatura)
        DATE_SUB(CURDATE(), INTERVAL 9 DAY),  -- 9 dias atrás (início votação)
        DATE_SUB(CURDATE(), INTERVAL 3 DAY),  -- 3 dias atrás (fim votação)
        'votacao_aberta',  -- Temporariamente aberta para inserir votos
        1);

-- =====================================================
-- CANDIDATURAS PARA ELEIÇÕES DE TESTE
-- =====================================================

-- Eleição 7: GE-1 (aguardando_finalizacao) - 3 candidatos deferidos
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, status_validacao, validado_por, data_validacao)
SELECT 7, id_aluno, proposta, 'deferido', 1, DATE_SUB(NOW(), INTERVAL 9 DAY)
FROM (
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE101') as id_aluno,
        'Implementar programa de visitas técnicas a empresas de diferentes portes e setores.' as proposta
    UNION ALL
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE103'),
        'Criar laboratório de simulação empresarial com software de gestão integrado.'
    UNION ALL
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE105'),
        'Desenvolver parceria com incubadoras de empresas para projetos práticos.'
) as candidatos;

-- Eleição 8: DSM-4 (aguardando_finalizacao) - 4 candidatos deferidos
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, status_validacao, validado_por, data_validacao)
SELECT 8, id_aluno, proposta, 'deferido', 1, DATE_SUB(NOW(), INTERVAL 11 DAY)
FROM (
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM401') as id_aluno,
        'Criar hackathon semestral com premiações e participação de empresas parceiras.' as proposta
    UNION ALL
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM403'),
        'Implementar laboratório de DevOps com ferramentas de CI/CD e cloud computing.'
    UNION ALL
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM405'),
        'Desenvolver programa de mentoria com profissionais seniores da área de tecnologia.'
    UNION ALL
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM407'),
        'Organizar maratona de programação e competições de algoritmos semanais.'
) as candidatos;

-- Eleição 9: GPI-2 (encerrada) - 3 candidatos deferidos
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, status_validacao, validado_por, data_validacao)
SELECT 9, id_aluno, proposta, 'deferido', 1, DATE_SUB(NOW(), INTERVAL 13 DAY)
FROM (
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI201') as id_aluno,
        'Criar laboratório de automação industrial com PLCs e sistemas supervisórios.' as proposta
    UNION ALL
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI203'),
        'Implementar programa de visitas a plantas industriais da região.'
    UNION ALL
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI205'),
        'Desenvolver projetos de melhoria contínua aplicando metodologias Lean e Six Sigma.'
) as candidatos;

-- Eleição 10: DSM-5 (encerrada) - 3 candidatos deferidos
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, status_validacao, validado_por, data_validacao)
SELECT 10, id_aluno, proposta, 'deferido', 1, DATE_SUB(NOW(), INTERVAL 11 DAY)
FROM (
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM501') as id_aluno,
        'Criar núcleo de desenvolvimento de aplicações mobile com foco em projetos reais.' as proposta
    UNION ALL
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM503'),
        'Implementar programa de certificações em cloud e desenvolvimento full-stack.'
    UNION ALL
    SELECT
        (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM505'),
        'Organizar semana de tecnologia com palestras, workshops e feira de projetos.'
) as candidatos;

-- =====================================================
-- VOTOS PARA ELEIÇÕES AGUARDANDO_FINALIZACAO
-- =====================================================

-- Votos Eleição 7: GE-1 (12 alunos, 10 votaram)
-- Distribuição: Candidato 1=5 votos, Candidato 2=3 votos, Candidato 3=2 votos
INSERT INTO VOTO (id_eleicao, id_candidatura, id_aluno, data_hora_voto)
SELECT 7, id_candidatura, id_aluno, data_voto
FROM (
    -- Votos para candidato 1 (5 votos)
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 7 ORDER BY id_candidatura LIMIT 1) as id_candidatura,
           (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE101') as id_aluno,
           DATE_SUB(NOW(), INTERVAL 6 DAY) as data_voto
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 7 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE102'),
           DATE_SUB(NOW(), INTERVAL 5 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 7 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE104'),
           DATE_SUB(NOW(), INTERVAL 4 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 7 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE106'),
           DATE_SUB(NOW(), INTERVAL 3 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 7 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE108'),
           DATE_SUB(NOW(), INTERVAL 2 DAY)
    -- Votos para candidato 2 (3 votos)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 7 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE103'),
           DATE_SUB(NOW(), INTERVAL 5 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 7 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE107'),
           DATE_SUB(NOW(), INTERVAL 3 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 7 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE109'),
           DATE_SUB(NOW(), INTERVAL 2 DAY)
    -- Votos para candidato 3 (2 votos)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 7 ORDER BY id_candidatura LIMIT 2,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE105'),
           DATE_SUB(NOW(), INTERVAL 4 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 7 ORDER BY id_candidatura LIMIT 2,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2024GE110'),
           DATE_SUB(NOW(), INTERVAL 2 DAY)
) as votos;

-- Votos Eleição 8: DSM-4 (15 alunos, 13 votaram, incluindo 2 votos em branco)
-- Distribuição: Candidato 1=5 votos, Candidato 2=4 votos, Candidato 3=2 votos, Voto branco=2
INSERT INTO VOTO (id_eleicao, id_candidatura, id_aluno, data_hora_voto)
SELECT 8, id_candidatura, id_aluno, data_voto
FROM (
    -- Votos para candidato 1 (5 votos)
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 8 ORDER BY id_candidatura LIMIT 1) as id_candidatura,
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM401') as id_aluno,
           DATE_SUB(NOW(), INTERVAL 8 DAY) as data_voto
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 8 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM402'),
           DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 8 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM404'),
           DATE_SUB(NOW(), INTERVAL 6 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 8 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM406'),
           DATE_SUB(NOW(), INTERVAL 5 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 8 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM408'),
           DATE_SUB(NOW(), INTERVAL 4 DAY)
    -- Votos para candidato 2 (4 votos)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 8 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM403'),
           DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 8 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM407'),
           DATE_SUB(NOW(), INTERVAL 5 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 8 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM409'),
           DATE_SUB(NOW(), INTERVAL 4 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 8 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM411'),
           DATE_SUB(NOW(), INTERVAL 3 DAY)
    -- Votos para candidato 3 (2 votos)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 8 ORDER BY id_candidatura LIMIT 2,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM405'),
           DATE_SUB(NOW(), INTERVAL 6 DAY)
    UNION ALL
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 8 ORDER BY id_candidatura LIMIT 2,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM413'),
           DATE_SUB(NOW(), INTERVAL 3 DAY)
    -- Votos em branco (2 votos)
    UNION ALL
    SELECT NULL,
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM410'),
           DATE_SUB(NOW(), INTERVAL 5 DAY)
    UNION ALL
    SELECT NULL,
           (SELECT id_aluno FROM ALUNO WHERE ra = '2022DSM412'),
           DATE_SUB(NOW(), INTERVAL 3 DAY)
) as votos;

-- =====================================================
-- VOTOS PARA ELEIÇÕES ENCERRADAS
-- =====================================================

-- Votos Eleição 9: GPI-2 (18 alunos, 15 votaram)
-- Distribuição: Candidato 1=7 votos, Candidato 2=5 votos, Candidato 3=3 votos
INSERT INTO VOTO (id_eleicao, id_candidatura, id_aluno, data_hora_voto)
SELECT 9, id_candidatura, id_aluno, data_voto
FROM (
    -- Votos para candidato 1 (7 votos)
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1) as id_candidatura,
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI201') as id_aluno,
           DATE_SUB(NOW(), INTERVAL 10 DAY) as data_voto
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI202'), DATE_SUB(NOW(), INTERVAL 9 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI204'), DATE_SUB(NOW(), INTERVAL 8 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI206'), DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI208'), DATE_SUB(NOW(), INTERVAL 6 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI210'), DATE_SUB(NOW(), INTERVAL 6 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI212'), DATE_SUB(NOW(), INTERVAL 5 DAY)
    -- Votos para candidato 2 (5 votos)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI203'), DATE_SUB(NOW(), INTERVAL 9 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI207'), DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI209'), DATE_SUB(NOW(), INTERVAL 6 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI213'), DATE_SUB(NOW(), INTERVAL 6 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI215'), DATE_SUB(NOW(), INTERVAL 5 DAY)
    -- Votos para candidato 3 (3 votos)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 2,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI205'), DATE_SUB(NOW(), INTERVAL 8 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 2,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI211'), DATE_SUB(NOW(), INTERVAL 6 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 9 ORDER BY id_candidatura LIMIT 2,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2023GPI214'), DATE_SUB(NOW(), INTERVAL 5 DAY)
) as votos;

-- Votos Eleição 10: DSM-5 (16 alunos, 14 votaram, incluindo 1 voto em branco)
-- Distribuição: Candidato 1=6 votos, Candidato 2=4 votos, Candidato 3=3 votos, Voto branco=1
INSERT INTO VOTO (id_eleicao, id_candidatura, id_aluno, data_hora_voto)
SELECT 10, id_candidatura, id_aluno, data_voto
FROM (
    -- Votos para candidato 1 (6 votos)
    SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 1) as id_candidatura,
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM501') as id_aluno,
           DATE_SUB(NOW(), INTERVAL 8 DAY) as data_voto
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM502'), DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM504'), DATE_SUB(NOW(), INTERVAL 6 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM506'), DATE_SUB(NOW(), INTERVAL 5 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM508'), DATE_SUB(NOW(), INTERVAL 4 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM510'), DATE_SUB(NOW(), INTERVAL 4 DAY)
    -- Votos para candidato 2 (4 votos)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM503'), DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM507'), DATE_SUB(NOW(), INTERVAL 5 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM509'), DATE_SUB(NOW(), INTERVAL 4 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 1,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM513'), DATE_SUB(NOW(), INTERVAL 4 DAY)
    -- Votos para candidato 3 (3 votos)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 2,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM505'), DATE_SUB(NOW(), INTERVAL 6 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 2,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM511'), DATE_SUB(NOW(), INTERVAL 5 DAY)
    UNION ALL SELECT (SELECT id_candidatura FROM CANDIDATURA WHERE id_eleicao = 10 ORDER BY id_candidatura LIMIT 2,1),
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM514'), DATE_SUB(NOW(), INTERVAL 4 DAY)
    -- Voto em branco (1 voto)
    UNION ALL SELECT NULL,
           (SELECT id_aluno FROM ALUNO WHERE ra = '2021DSM512'), DATE_SUB(NOW(), INTERVAL 5 DAY)
) as votos;

-- =====================================================
-- ATUALIZAR STATUS DAS ELEIÇÕES
-- =====================================================
-- Agora que os votos foram inseridos, atualizar para os status corretos

-- Eleições 7 e 8: Atualizar para aguardando_finalizacao
UPDATE ELEICAO SET status = 'aguardando_finalizacao' WHERE id_eleicao IN (7, 8);

-- =====================================================
-- APURAÇÃO DAS ELEIÇÕES ENCERRADAS
-- =====================================================

-- Apurar Eleição 9: GPI-2
CALL sp_finalizar_eleicao(9, 1);

-- Apurar Eleição 10: DSM-5
CALL sp_finalizar_eleicao(10, 1);

-- =====================================================
-- VERIFICAÇÃO E RESUMO FINAL
-- =====================================================

-- Verificação de datas das eleições
SELECT '' as '';
SELECT 'VERIFICAÇÃO DE DATAS DAS ELEIÇÕES:' as '';
SELECT '' as '';
SELECT
    id_eleicao,
    CONCAT(curso, '-', semestre) as turma,
    status,
    DATE_FORMAT(data_inicio_candidatura, '%d/%m/%Y') as inicio_candidatura,
    DATE_FORMAT(data_fim_candidatura, '%d/%m/%Y') as fim_candidatura,
    DATE_FORMAT(data_inicio_votacao, '%d/%m/%Y') as inicio_votacao,
    DATE_FORMAT(data_fim_votacao, '%d/%m/%Y') as fim_votacao,
    CASE
        WHEN CURDATE() BETWEEN data_inicio_candidatura AND data_fim_candidatura THEN '[OK] Candidatura OK'
        WHEN CURDATE() BETWEEN data_inicio_votacao AND data_fim_votacao THEN '[OK] Votação OK'
        ELSE '[X] Fora do período'
    END as validacao
FROM ELEICAO
ORDER BY id_eleicao;
