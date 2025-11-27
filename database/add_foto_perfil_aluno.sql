-- =====================================================
-- Script para adicionar campo foto_perfil na tabela ALUNO
-- =====================================================
-- Execute este script no phpMyAdmin para adicionar o campo de foto de perfil

USE siv_db;

-- Adicionar coluna foto_perfil na tabela ALUNO
ALTER TABLE ALUNO
ADD COLUMN foto_perfil VARCHAR(500) NULL AFTER email_institucional,
ADD COLUMN foto_perfil_original VARCHAR(255) NULL AFTER foto_perfil;

SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'siv_db'
  AND TABLE_NAME = 'ALUNO'
  AND COLUMN_NAME IN ('foto_perfil', 'foto_perfil_original');

-- Mensagem de confirmação
SELECT 'Campo foto_perfil adicionado com sucesso na tabela ALUNO!' as '';
SELECT 'Agora os alunos podem fazer upload de foto no cadastro.' as '';
