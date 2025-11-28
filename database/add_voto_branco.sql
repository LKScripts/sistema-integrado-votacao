USE `siv_db`;

-- =====================================================
-- PASSO 1: Permitir NULL em id_candidatura
-- =====================================================
-- Modifica a coluna para aceitar votos sem candidato (voto em branco)

ALTER TABLE `voto`
MODIFY COLUMN `id_candidatura` int(11) NULL COMMENT 'Candidatura que recebeu o voto (NULL = voto em branco)';

-- =====================================================
-- PASSO 2: Atualizar trigger de validação
-- =====================================================
-- O trigger agora permite votos em branco (id_candidatura = NULL)
-- e só valida candidaturas quando um candidato foi escolhido

DROP TRIGGER IF EXISTS `trg_valida_voto_candidatura_deferida`;

DELIMITER $$
CREATE TRIGGER `trg_valida_voto_candidatura_deferida`
BEFORE INSERT ON `voto`
FOR EACH ROW
BEGIN
    DECLARE v_status_candidatura VARCHAR(20);

    -- Se id_candidatura for NULL, é voto em branco - permitir sem validação
    IF NEW.id_candidatura IS NOT NULL THEN
        -- Validar apenas se for voto em candidato específico
        SELECT status_validacao INTO v_status_candidatura
        FROM CANDIDATURA WHERE id_candidatura = NEW.id_candidatura;

        IF v_status_candidatura != 'deferido' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Apenas candidaturas deferidas podem receber votos';
        END IF;
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- PASSO 3: Criar view para relatórios completos
-- =====================================================
-- Esta view facilita a geração de relatórios incluindo votos em branco

CREATE OR REPLACE VIEW `v_contagem_votos_completa` AS
SELECT
    v.id_eleicao,
    e.curso,
    e.semestre,
    e.status AS status_eleicao,
    COALESCE(c.id_candidatura, 0) AS id_candidatura,
    CASE
        WHEN c.id_candidatura IS NULL THEN 'VOTO EM BRANCO'
        ELSE a.nome_completo
    END AS nome_candidato,
    CASE
        WHEN c.id_candidatura IS NULL THEN NULL
        ELSE a.ra
    END AS ra,
    COUNT(*) AS total_votos
FROM VOTO v
JOIN ELEICAO e ON v.id_eleicao = e.id_eleicao
LEFT JOIN CANDIDATURA c ON v.id_candidatura = c.id_candidatura
LEFT JOIN ALUNO a ON c.id_aluno = a.id_aluno
GROUP BY v.id_eleicao, c.id_candidatura, e.curso, e.semestre, e.status, a.nome_completo, a.ra
ORDER BY v.id_eleicao DESC, total_votos DESC;

-- =====================================================
-- VERIFICAÇÃO
-- =====================================================
-- Execute estas queries para confirmar que as mudanças foram aplicadas:

-- 1. Verificar se id_candidatura aceita NULL
-- SHOW COLUMNS FROM voto LIKE 'id_candidatura';

-- 2. Verificar se o trigger foi recriado
-- SHOW TRIGGERS LIKE 'voto';

-- 3. Verificar se a view foi criada
-- SELECT * FROM v_contagem_votos_completa LIMIT 5;

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================
-- 1. A procedure sp_finalizar_eleicao NÃO precisa ser modificada
--    porque ela já conta total_votantes corretamente e ignora
--    automaticamente votos em branco ao calcular vencedor/suplente
--
-- 2. Votos em branco são contabilizados no total_votantes para
--    calcular percentual de participação, mas não influenciam
--    na escolha do representante e suplente
--
-- 3. Para ver quantos votos em branco uma eleição teve:
--    SELECT COUNT(*) FROM VOTO
--    WHERE id_eleicao = ? AND id_candidatura IS NULL;
-- =====================================================
