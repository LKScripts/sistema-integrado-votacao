-- Modificar campo 'ativo' para suportar status de aprovação
-- 0 = Pendente de aprovação
-- 1 = Ativo/Aprovado
-- 2 = Rejeitado/Removido
ALTER TABLE `administrador`
MODIFY COLUMN `ativo` TINYINT(1) DEFAULT 0 COMMENT '0=Pendente, 1=Ativo, 2=Removido';

-- Adicionar campo para rastrear quem aprovou o admin
ALTER TABLE `administrador`
ADD COLUMN `aprovado_por` INT(11) NULL DEFAULT NULL AFTER `ativo`,
ADD COLUMN `data_aprovacao` TIMESTAMP NULL DEFAULT NULL AFTER `aprovado_por`,
ADD COLUMN `motivo_rejeicao` TEXT NULL DEFAULT NULL AFTER `data_aprovacao`;

-- Adicionar índice e foreign key
ALTER TABLE `administrador`
ADD KEY `idx_status_ativo` (`ativo`),
ADD KEY `fk_admin_aprovador` (`aprovado_por`),
ADD CONSTRAINT `fk_admin_aprovador` FOREIGN KEY (`aprovado_por`) REFERENCES `administrador` (`id_admin`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Atualizar o admin inicial (ID 1) para estar ativo e auto-aprovado
UPDATE `administrador`
SET `ativo` = 1,
    `aprovado_por` = 1,
    `data_aprovacao` = NOW()
WHERE `id_admin` = 1;

-- Status de administrador:
-- 0 (Pendente): Admin cadastrado mas aguardando aprovação de outro admin
-- 1 (Ativo): Admin aprovado e com acesso total ao sistema
-- 2 (Removido): Admin desativado/removido do sistema
