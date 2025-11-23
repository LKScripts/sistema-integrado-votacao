-- =====================================================
-- ATUALIZAÇÕES DE SEGURANÇA - SIV
-- =====================================================
-- Execute este arquivo para adicionar recursos de segurança
-- Comando: mysql -u root -p -P 3307 siv_db < database/adicionar_seguranca.sql
-- =====================================================

USE siv_db;

-- =====================================================
-- TABELA: LOGIN_TENTATIVAS
-- =====================================================
-- Armazena tentativas de login para implementar rate limiting
-- Bloqueia após 3 tentativas incorretas por 15 minutos

CREATE TABLE IF NOT EXISTS LOGIN_TENTATIVAS (
    id_tentativa INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_origem VARCHAR(45) NOT NULL,
    data_tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sucesso BOOLEAN DEFAULT FALSE,

    INDEX idx_email_ip (email, ip_origem),
    INDEX idx_data (data_tentativa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PROCEDURE: Limpar tentativas antigas (automático)
-- =====================================================
-- Remove tentativas com mais de 15 minutos automaticamente

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_limpar_tentativas_antigas$$

CREATE PROCEDURE sp_limpar_tentativas_antigas()
BEGIN
    DELETE FROM LOGIN_TENTATIVAS
    WHERE data_tentativa < DATE_SUB(NOW(), INTERVAL 15 MINUTE);
END$$

DELIMITER ;

-- =====================================================
-- EVENT: Limpar tentativas antigas a cada hora
-- =====================================================

DELIMITER $$

DROP EVENT IF EXISTS evt_limpar_tentativas$$

CREATE EVENT evt_limpar_tentativas
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    CALL sp_limpar_tentativas_antigas();
END$$

DELIMITER ;

-- =====================================================
-- VERIFICAÇÃO
-- =====================================================
-- Confirmar que tabela foi criada
SELECT 'Tabela LOGIN_TENTATIVAS criada com sucesso!' as status;
SHOW TABLES LIKE 'LOGIN_TENTATIVAS';

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================
