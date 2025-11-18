-- ===============================================
-- SCRIPT DE ADIÇÃO DE CONSTRAINTS CHECK
-- Sistema Integrado de Votação (SIV)
-- ===============================================
--
-- Este script adiciona as constraints CHECK que não são exportadas
-- pelo phpMyAdmin no dump padrão.
--
-- IMPORTANTE: Execute este script APÓS importar o arquivo siv_db.sql
--
-- Versão do MariaDB requerida: 10.2.1+
-- Data de criação: 17/11/2025
-- ===============================================

USE siv_db;

-- ===============================================
-- TABELA: ALUNO
-- ===============================================
-- Garante que o semestre esteja entre 1 e 6
-- (Cursos típicos de graduação tecnológica têm 6 semestres)

ALTER TABLE `aluno`
ADD CONSTRAINT `chk_semestre` CHECK (`semestre` BETWEEN 1 AND 6);


-- ===============================================
-- TABELA: ELEICAO
-- ===============================================
-- Múltiplas validações para garantir integridade das datas
-- e semestre válido para eleições

-- 1. Valida que o semestre da eleição está entre 1 e 6
ALTER TABLE `eleicao`
ADD CONSTRAINT `chk_semestre_eleicao` CHECK (`semestre` BETWEEN 1 AND 6);

-- 2. Valida que a data de fim de candidatura é posterior ao início
ALTER TABLE `eleicao`
ADD CONSTRAINT `chk_datas_candidatura` CHECK (`data_fim_candidatura` > `data_inicio_candidatura`);

-- 3. Valida que a data de fim de votação é posterior ao início
ALTER TABLE `eleicao`
ADD CONSTRAINT `chk_datas_votacao` CHECK (`data_fim_votacao` > `data_inicio_votacao`);

-- 4. Valida que a votação começa após o término das candidaturas
ALTER TABLE `eleicao`
ADD CONSTRAINT `chk_ordem_fases` CHECK (`data_inicio_votacao` >= `data_fim_candidatura`);


-- ===============================================
-- TABELA: RESULTADO
-- ===============================================
-- Garante que o percentual de participação está entre 0 e 100

ALTER TABLE `resultado`
ADD CONSTRAINT `chk_percentual` CHECK (`percentual_participacao` BETWEEN 0 AND 100);


-- ===============================================
-- VERIFICAÇÃO
-- ===============================================
-- Para verificar se as constraints foram criadas corretamente,
-- execute os seguintes comandos:
--
-- SHOW CREATE TABLE `aluno`;
-- SHOW CREATE TABLE `eleicao`;
-- SHOW CREATE TABLE `resultado`;
--
-- Você deve ver as constraints CHECK listadas na definição de cada tabela.

-- ===============================================
-- TESTES (OPCIONAL)
-- ===============================================
-- Descomente os comandos abaixo para testar se as constraints
-- estão funcionando corretamente. Todos devem FALHAR com erro
-- "Check constraint is violated".

-- Teste 1: Tentar inserir semestre inválido em ALUNO (deve FALHAR)
-- INSERT INTO aluno (ra, nome_completo, email_institucional, senha_hash, curso, semestre)
-- VALUES ('99999', 'Teste Invalido', 'teste@fatec.sp.gov.br', 'hash_teste', 'DSM', 10);

-- Teste 2: Tentar inserir percentual inválido em RESULTADO (deve FALHAR)
-- Primeiro você precisa ter dados válidos em outras tabelas para testar isso
-- INSERT INTO resultado (id_eleicao, id_representante, votos_representante,
--                        total_votantes, total_aptos, percentual_participacao, gerado_por)
-- VALUES (1, 1, 10, 20, 30, 150, 1);

-- ===============================================
-- FIM DO SCRIPT
-- ===============================================
