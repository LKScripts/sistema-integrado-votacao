-- =====================================================
-- Script para adicionar fotos de stock aos candidatos
-- APENAS PARA ELEIÇÕES COM VOTAÇÃO ABERTA
-- =====================================================
-- Execute este script no phpMyAdmin para adicionar fotos de exemplo aos candidatos
-- Estas são URLs de fotos de exemplo gratuitas do serviço Pravatar

-- =====================================================
-- ELEIÇÃO 4: DSM - Semestre 2 (votacao_aberta)
-- =====================================================
-- 4 candidatos deferidos (IDs: 11, 12, 13, 14)

-- Candidatura 11 - Felipe Gomes Cardoso
UPDATE CANDIDATURA
SET foto_candidato = 'https://i.pravatar.cc/300?img=59'
WHERE id_candidatura = 11;

-- Candidatura 12 - Vinicius Castro Ribeiro
UPDATE CANDIDATURA
SET foto_candidato = 'https://i.pravatar.cc/300?img=68'
WHERE id_candidatura = 12;

-- Candidatura 13 - Bruno Almeida Teixeira
UPDATE CANDIDATURA
SET foto_candidato = 'https://i.pravatar.cc/300?img=61'
WHERE id_candidatura = 13;

-- Candidatura 14 - Rodrigo Nascimento Pinto
UPDATE CANDIDATURA
SET foto_candidato = 'https://i.pravatar.cc/300?img=67'
WHERE id_candidatura = 14;

-- =====================================================
-- ELEIÇÃO 5: GE - Semestre 4 (votacao_aberta)
-- =====================================================
-- 3 candidatos deferidos (IDs: 15, 16, 17)

-- Candidatura 15 - Marcos Vinícius Correia
UPDATE CANDIDATURA
SET foto_candidato = 'https://i.pravatar.cc/300?img=60'
WHERE id_candidatura = 15;

-- Candidatura 16 - Juliana Costa Ramos
UPDATE CANDIDATURA
SET foto_candidato = 'https://i.pravatar.cc/300?img=47'
WHERE id_candidatura = 16;

-- Candidatura 17 - Adriana Pereira Gomes
UPDATE CANDIDATURA
SET foto_candidato = 'https://i.pravatar.cc/300?img=32'
WHERE id_candidatura = 17;

-- =====================================================
-- ELEIÇÃO 6: GPI - Semestre 6 (votacao_aberta)
-- =====================================================
-- 4 candidatos deferidos (IDs: 18, 19, 20, 21)

-- Candidatura 18 - Nilton Souza Lopes
UPDATE CANDIDATURA
SET foto_candidato = 'https://i.pravatar.cc/300?img=57'
WHERE id_candidatura = 18;

-- Candidatura 19 - Robson Alves Freitas
UPDATE CANDIDATURA
SET foto_candidato = 'https://i.pravatar.cc/300?img=66'
WHERE id_candidatura = 19;

-- Candidatura 20 - Valter Lima Martins
UPDATE CANDIDATURA
SET foto_candidato = 'https://i.pravatar.cc/300?img=63'
WHERE id_candidatura = 20;

-- Candidatura 21 - Úrsula Campos Prado
UPDATE CANDIDATURA
SET foto_candidato = 'https://i.pravatar.cc/300?img=38'
WHERE id_candidatura = 21;

-- =====================================================
-- VERIFICAÇÃO DOS CANDIDATOS ATUALIZADOS
-- =====================================================
SELECT
    c.id_candidatura,
    e.curso,
    e.semestre,
    e.status as status_eleicao,
    a.nome_completo,
    c.status_validacao,
    c.foto_candidato
FROM CANDIDATURA c
JOIN ALUNO a ON c.id_aluno = a.id_aluno
JOIN ELEICAO e ON c.id_eleicao = e.id_eleicao
WHERE e.status = 'votacao_aberta'
ORDER BY c.id_candidatura;

-- =====================================================
-- MENSAGEM DE CONFIRMAÇÃO
-- =====================================================
SELECT 'FOTOS ADICIONADAS COM SUCESSO!' as '';
SELECT 'Total de 11 candidatos com fotos nas eleições com votação aberta' as '';
SELECT '- DSM Semestre 2: 4 candidatos' as '';
SELECT '- GE Semestre 4: 3 candidatos' as '';
SELECT '- GPI Semestre 6: 4 candidatos' as '';
