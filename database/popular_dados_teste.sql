USE siv_db;

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

-- DSM - Semestre 2 (12 alunos)
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
('2023DSM012', 'Patrícia Monteiro Ramos', 'patricia.ramos@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DSM', 2, 1);

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

-- GE - Semestre 4 (13 alunos)
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
('2023GE013', 'Sérgio Henrique Barros', 'sergio.barros@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GE', 4, 1);

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

-- GPI - Semestre 6 (12 alunos)
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
('2022GPI012', 'Yasmin Rodrigues Teixeira', 'yasmin.teixeira@fatec.sp.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GPI', 6, 1);

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
-- 4 candidatos: 2 deferidos, 1 pendente, 1 indeferido
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(1, 1, 'Proposta para melhorar a comunicação entre alunos e professores, criando grupos de estudo e eventos de integração.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 3, 'Melhorar a estrutura do laboratório de informática e promover hackathons internos para desenvolver habilidades práticas.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 5, 'Criar canal de feedback direto com coordenação e organizar palestras com profissionais da área de tecnologia.', 'pendente', NULL, NULL, NULL),
(1, 7, 'Implementar aulas extras aos finais de semana.', 'indeferido', 'Proposta inviável devido a restrições de disponibilidade de professores e infraestrutura.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Eleição 2: GE - Semestre 2 (candidatura_aberta)
-- 3 candidatos: 2 deferidos, 1 pendente
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(2, 34, 'Fortalecer a relação com empresas locais para conseguir mais estágios e visitas técnicas.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 36, 'Criar projeto de mentoria entre veteranos e calouros para facilitar adaptação ao curso.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 38, 'Organizar semana de empreendedorismo com workshops e palestras sobre gestão de negócios.', 'pendente', NULL, NULL, NULL);

-- Eleição 3: GPI - Semestre 3 (candidatura_aberta)
-- 3 candidatos: 1 deferido, 2 pendentes
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(3, 68, 'Promover visitas técnicas a indústrias da região e criar parcerias para projetos práticos.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 70, 'Implementar grupos de estudo focados em certificações industriais e normas de qualidade.', 'pendente', NULL, NULL, NULL),
(3, 72, 'Melhorar o acesso a softwares de simulação industrial e criar biblioteca de cases práticos.', 'pendente', NULL, NULL, NULL);

-- =====================================================
-- CANDIDATURAS - Eleições com VOTAÇÃO ABERTA
-- =====================================================

-- Eleição 4: DSM - Semestre 2 (votacao_aberta)
-- 4 candidatos, TODOS deferidos (necessário para permitir votação)
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(4, 11, 'Criar clube de programação com competições e desafios semanais para aprimorar habilidades técnicas.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 13, 'Estabelecer parcerias com empresas de tecnologia para palestras, workshops e oportunidades de estágio.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 15, 'Desenvolver projetos open-source em equipe e criar repositório de códigos para consulta dos alunos.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 17, 'Organizar eventos de networking com ex-alunos e profissionais atuantes na área de desenvolvimento.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Eleição 5: GE - Semestre 4 (votacao_aberta)
-- 3 candidatos, TODOS deferidos
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(5, 45, 'Criar núcleo de estudos em gestão estratégica e realizar simulações de negócios.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 47, 'Organizar feira de empreendedorismo com participação de startups e empresas consolidadas.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 49, 'Implementar programa de consultoria júnior para empresas locais com supervisão de professores.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Eleição 6: GPI - Semestre 6 (votacao_aberta)
-- 4 candidatos, TODOS deferidos
INSERT INTO CANDIDATURA (id_eleicao, id_aluno, proposta, status_validacao, justificativa_indeferimento, validado_por, data_validacao)
VALUES
(6, 79, 'Criar laboratório de processos industriais com equipamentos de automação e controle.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 81, 'Desenvolver projetos de melhoria contínua em parceria com indústrias da região.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 83, 'Organizar semana de qualidade e produtividade com certificações e workshops especializados.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 85, 'Implementar sistema de gestão à vista e painéis de indicadores no laboratório de práticas.', 'deferido', NULL, 1, DATE_SUB(NOW(), INTERVAL 4 DAY));

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

-- Votos Eleição 5: GE - Semestre 4 (13 alunos aptos - IDs 44 a 56)
-- Distribuição: Candidato 1=6 votos, Candidato 2=4 votos, Candidato 3=3 votos
-- (IDs das candidaturas: 15, 16, 17)
-- DATAS DINÂMICAS: Votos de 2 dias atrás até hoje
INSERT INTO VOTO (id_eleicao, id_candidatura, id_aluno, data_hora_voto) VALUES
(5, 15, 44, DATE_SUB(NOW(), INTERVAL 2 DAY)), -- Marcos (ID 44) vota em candidato 1
(5, 15, 45, DATE_SUB(NOW(), INTERVAL 47 HOUR)), -- Bianca (ID 45) vota em candidato 1
(5, 15, 47, DATE_SUB(NOW(), INTERVAL 45 HOUR)), -- Juliana (ID 47) vota em candidato 1
(5, 15, 49, DATE_SUB(NOW(), INTERVAL 43 HOUR)), -- Adriana (ID 49) vota em candidato 1
(5, 15, 51, DATE_SUB(NOW(), INTERVAL 41 HOUR)), -- Renata (ID 51) vota em candidato 1
(5, 15, 53, DATE_SUB(NOW(), INTERVAL 15 HOUR)), -- Viviane (ID 53) vota em candidato 1
(5, 16, 46, DATE_SUB(NOW(), INTERVAL 46 HOUR)), -- Alexandre (ID 46) vota em candidato 2
(5, 16, 48, DATE_SUB(NOW(), INTERVAL 44 HOUR)), -- Fernando (ID 48) vota em candidato 2
(5, 16, 52, DATE_SUB(NOW(), INTERVAL 40 HOUR)), -- Leandro (ID 52) vota em candidato 2
(5, 16, 55, DATE_SUB(NOW(), INTERVAL 13 HOUR)), -- Luciana (ID 55) vota em candidato 2
(5, 17, 50, DATE_SUB(NOW(), INTERVAL 42 HOUR)), -- César (ID 50) vota em candidato 3
(5, 17, 54, DATE_SUB(NOW(), INTERVAL 14 HOUR)), -- Cristiano (ID 54) vota em candidato 3
(5, 17, 56, DATE_SUB(NOW(), INTERVAL 12 HOUR)); -- Sérgio (ID 56) vota em candidato 3

-- Votos Eleição 6: GPI - Semestre 6 (12 alunos aptos - IDs 77 a 88)
-- Distribuição: Candidato 1=4 votos, Candidato 2=3 votos, Candidato 3=3 votos, Candidato 4=2 votos
-- (IDs das candidaturas: 18, 19, 20, 21)
-- DATAS DINÂMICAS: Votos de 3 dias atrás até 1 dia atrás
INSERT INTO VOTO (id_eleicao, id_candidatura, id_aluno, data_hora_voto) VALUES
(6, 18, 77, DATE_SUB(NOW(), INTERVAL 3 DAY)), -- Nilton (ID 77) vota em candidato 1
(6, 18, 79, DATE_SUB(NOW(), INTERVAL 66 HOUR)), -- Pedro (ID 79) vota em candidato 1
(6, 18, 82, DATE_SUB(NOW(), INTERVAL 62 HOUR)), -- Suzana (ID 82) vota em candidato 1
(6, 18, 85, DATE_SUB(NOW(), INTERVAL 39 HOUR)), -- Úrsula (ID 85) vota em candidato 1
(6, 19, 78, DATE_SUB(NOW(), INTERVAL 67 HOUR)), -- Olívia (ID 78) vota em candidato 2
(6, 19, 81, DATE_SUB(NOW(), INTERVAL 64 HOUR)), -- Robson (ID 81) vota em candidato 2
(6, 19, 84, DATE_SUB(NOW(), INTERVAL 56 HOUR)), -- Tiago (ID 84) vota em candidato 2
(6, 20, 80, DATE_SUB(NOW(), INTERVAL 65 HOUR)), -- Queila (ID 80) vota em candidato 3
(6, 20, 83, DATE_SUB(NOW(), INTERVAL 61 HOUR)), -- Valter (ID 83) vota em candidato 3
(6, 20, 87, DATE_SUB(NOW(), INTERVAL 37 HOUR)), -- Xavier (ID 87) vota em candidato 3
(6, 21, 86, DATE_SUB(NOW(), INTERVAL 38 HOUR)), -- Wanda (ID 86) vota em candidato 4
(6, 21, 88, DATE_SUB(NOW(), INTERVAL 35 HOUR)); -- Yasmin (ID 88) vota em candidato 4

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

-- Verificação de datas das eleições
SELECT '' as '';
SELECT '📅 VERIFICAÇÃO DE DATAS DAS ELEIÇÕES:' as '';
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
        WHEN CURDATE() BETWEEN data_inicio_candidatura AND data_fim_candidatura THEN '✅ Candidatura OK'
        WHEN CURDATE() BETWEEN data_inicio_votacao AND data_fim_votacao THEN '✅ Votação OK'
        ELSE '❌ Fora do período'
    END as validacao
FROM ELEICAO
ORDER BY id_eleicao;

-- Mensagem final
SELECT '' as '';
SELECT '✅ DADOS POPULADOS COM SUCESSO!' as '';
SELECT '' as '';
SELECT CONCAT('📅 Data de execução: ', DATE_FORMAT(NOW(), '%d/%m/%Y às %H:%i:%s')) as '';
SELECT '' as '';
SELECT 'CREDENCIAIS DE TESTE:' as '';
SELECT '- Email de qualquer aluno listado acima' as '';
SELECT '- Senha: password' as '';
SELECT '' as '';
SELECT 'ELEIÇÕES DISPONÍVEIS:' as '';
SELECT '- 3 eleições com CANDIDATURA ABERTA (DSM-1, GE-2, GPI-3)' as '';
SELECT '- 3 eleições com VOTAÇÃO ABERTA (DSM-2, GE-4, GPI-6)' as '';
SELECT '' as '';
SELECT '🔒 GARANTIA DE SEGURANÇA:' as '';
SELECT '- Eleições válidas até 12-14/12 (se executado hoje)' as '';
SELECT '- Cobre com FOLGA as apresentações de 02/12 e 08/12' as '';
SELECT '- Pode re-executar em 10/12 para maior segurança (opcional)' as '';
