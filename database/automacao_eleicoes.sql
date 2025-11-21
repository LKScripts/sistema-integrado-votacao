-- =====================================================
-- AUTOMAÇÃO COMPLETA DE ELEIÇÕES - SIV
-- =====================================================
-- Funções que gerenciam automaticamente o ciclo de vida
-- de eleições baseado nas datas configuradas
-- =====================================================

DELIMITER $$

-- =====================================================
-- 1. PROCEDURE: Atualizar Status das Eleições
-- =====================================================
-- Atualiza o status das eleições baseado na data/hora atual
-- Transições:
--   candidatura_aberta → votacao_aberta (quando inicia votação)
--   votacao_aberta → aguardando_finalizacao (quando termina votação)

DROP PROCEDURE IF EXISTS sp_atualizar_status_eleicoes$$

CREATE PROCEDURE sp_atualizar_status_eleicoes()
BEGIN
    DECLARE v_eleicoes_atualizadas INT DEFAULT 0;

    -- Atualizar para 'votacao_aberta'
    -- Quando: data/hora atual >= data_inicio_votacao E < data_fim_votacao
    UPDATE ELEICAO
    SET status = 'votacao_aberta'
    WHERE status = 'candidatura_aberta'
      AND NOW() >= data_inicio_votacao
      AND NOW() < data_fim_votacao;

    SET v_eleicoes_atualizadas = ROW_COUNT();

    -- Log de mudanças (se houver)
    IF v_eleicoes_atualizadas > 0 THEN
        INSERT INTO AUDITORIA (id_admin, operacao, descricao, ip_origem, data_hora)
        VALUES (
            1, -- ID do sistema (admin automático)
            'UPDATE',
            CONCAT(v_eleicoes_atualizadas, ' eleição(ões) mudou(aram) para votacao_aberta automaticamente'),
            '127.0.0.1',
            NOW()
        );
    END IF;

    -- Marcar eleições que precisam ser finalizadas
    -- Quando: data/hora atual >= data_fim_votacao
    UPDATE ELEICAO
    SET status = 'aguardando_finalizacao'
    WHERE status = 'votacao_aberta'
      AND NOW() >= data_fim_votacao;

    SET v_eleicoes_atualizadas = ROW_COUNT();

    IF v_eleicoes_atualizadas > 0 THEN
        INSERT INTO AUDITORIA (id_admin, operacao, descricao, ip_origem, data_hora)
        VALUES (
            1,
            'UPDATE',
            CONCAT(v_eleicoes_atualizadas, ' eleição(ões) finalizou(aram) votação automaticamente'),
            '127.0.0.1',
            NOW()
        );
    END IF;

END$$

-- =====================================================
-- 2. PROCEDURE: Finalizar Eleições Automaticamente
-- =====================================================
-- Finaliza eleições que estão com status 'aguardando_finalizacao'
-- Chama sp_finalizar_eleicao para cada uma

DROP PROCEDURE IF EXISTS sp_auto_finalizar_eleicoes$$

CREATE PROCEDURE sp_auto_finalizar_eleicoes()
BEGIN
    DECLARE v_id_eleicao INT;
    DECLARE v_done INT DEFAULT FALSE;

    -- Cursor para eleições que precisam ser finalizadas
    DECLARE cur_eleicoes CURSOR FOR
        SELECT id_eleicao
        FROM ELEICAO
        WHERE status = 'aguardando_finalizacao';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;

    OPEN cur_eleicoes;

    read_loop: LOOP
        FETCH cur_eleicoes INTO v_id_eleicao;

        IF v_done THEN
            LEAVE read_loop;
        END IF;

        -- Finalizar eleição (com admin ID 1 = sistema)
        CALL sp_finalizar_eleicao(v_id_eleicao, 1);

    END LOOP;

    CLOSE cur_eleicoes;

END$$

-- =====================================================
-- 3. PROCEDURE: Gerenciamento Completo (Master)
-- =====================================================
-- Chama todas as procedures de automação em sequência

DROP PROCEDURE IF EXISTS sp_gerenciar_eleicoes_automaticamente$$

CREATE PROCEDURE sp_gerenciar_eleicoes_automaticamente()
BEGIN
    -- Passo 1: Atualizar status baseado em datas
    CALL sp_atualizar_status_eleicoes();

    -- Passo 2: Finalizar eleições que terminaram
    CALL sp_auto_finalizar_eleicoes();

END$$

-- =====================================================
-- 4. EVENT: Execução Automática Periódica
-- =====================================================
-- Roda a cada 1 hora (pode ajustar intervalo se necessário)

-- Habilitar event scheduler (necessário para eventos funcionarem)
SET GLOBAL event_scheduler = ON$$

-- Remover evento se já existir
DROP EVENT IF EXISTS evt_gerenciar_eleicoes$$

-- Criar evento
CREATE EVENT evt_gerenciar_eleicoes
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    CALL sp_gerenciar_eleicoes_automaticamente();
END$$

-- =====================================================
-- 5. FUNÇÃO: Verificar se Eleição está em Período Válido
-- =====================================================
-- Retorna: 'candidatura' | 'votacao' | 'encerrada' | 'nao_iniciada'

DROP FUNCTION IF EXISTS fn_verificar_periodo_eleicao$$

CREATE FUNCTION fn_verificar_periodo_eleicao(p_id_eleicao INT)
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    DECLARE v_inicio_candidatura DATETIME;
    DECLARE v_fim_candidatura DATETIME;
    DECLARE v_inicio_votacao DATETIME;
    DECLARE v_fim_votacao DATETIME;
    DECLARE v_agora DATETIME;

    SET v_agora = NOW();

    SELECT
        data_inicio_candidatura,
        data_fim_candidatura,
        data_inicio_votacao,
        data_fim_votacao
    INTO
        v_inicio_candidatura,
        v_fim_candidatura,
        v_inicio_votacao,
        v_fim_votacao
    FROM ELEICAO
    WHERE id_eleicao = p_id_eleicao;

    -- Verificar período
    IF v_agora < v_inicio_candidatura THEN
        RETURN 'nao_iniciada';
    ELSEIF v_agora >= v_inicio_candidatura AND v_agora < v_fim_candidatura THEN
        RETURN 'candidatura';
    ELSEIF v_agora >= v_inicio_votacao AND v_agora < v_fim_votacao THEN
        RETURN 'votacao';
    ELSE
        RETURN 'encerrada';
    END IF;

END$$

DELIMITER ;

-- =====================================================
-- TESTAR A AUTOMAÇÃO
-- =====================================================
-- Você pode testar manualmente com:
-- CALL sp_gerenciar_eleicoes_automaticamente();

-- Verificar se evento está ativo:
-- SHOW EVENTS;
-- SELECT * FROM information_schema.EVENTS WHERE EVENT_NAME = 'evt_gerenciar_eleicoes';

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================
-- 1. O event_scheduler precisa estar ON (já configurado acima)
-- 2. O evento roda a cada 1 HORA (pode mudar para EVERY 30 MINUTE se preferir)
-- 3. Admin com ID 1 é considerado "Sistema Automático" para auditoria
-- 4. Status novo: 'aguardando_finalizacao' (entre votacao_aberta e encerrada)
-- 5. A procedure sp_finalizar_eleicao precisa existir (já existe no banco)

-- Para desabilitar automação temporariamente:
-- SET GLOBAL event_scheduler = OFF;
-- DROP EVENT evt_gerenciar_eleicoes;
