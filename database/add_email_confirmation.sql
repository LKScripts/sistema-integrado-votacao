-- Tabela para armazenar tokens de confirmação de e-mail
CREATE TABLE IF NOT EXISTS `email_confirmacao` (
  `id_token` INT(11) NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(64) NOT NULL,
  `tipo_usuario` ENUM('aluno', 'admin') NOT NULL,
  `id_usuario` INT(11) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `data_criacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_expiracao` DATETIME NOT NULL,
  `confirmado` TINYINT(1) DEFAULT 0,
  `data_confirmacao` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_token`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_email` (`email`),
  KEY `idx_tipo_usuario` (`tipo_usuario`, `id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar campo 'ativo' na tabela ALUNO se não existir
-- Alunos começam com ativo=0 até confirmarem o e-mail
ALTER TABLE `aluno`
ADD COLUMN `ativo` TINYINT(1) DEFAULT 0 AFTER `ultimo_acesso`;

-- Atualizar alunos existentes para ativo=1 (já estão cadastrados)
UPDATE `aluno` SET `ativo` = 1 WHERE `ativo` IS NULL OR `ativo` = 0;
