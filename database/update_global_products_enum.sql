-- Script per aggiornare la tabella jta_global_prods per supportare ENUM
-- Assistente Farmacia Panel

USE `jt_assistente_farmacia`;

-- Aggiorna la colonna is_active per supportare ENUM
ALTER TABLE `jta_global_prods` 
MODIFY COLUMN `is_active` ENUM('active', 'inactive', 'pending_approval', 'deleted') NOT NULL DEFAULT 'active';

-- Aggiorna i valori esistenti da tinyint a ENUM
UPDATE `jta_global_prods` SET `is_active` = 'active' WHERE `is_active` = '1';
UPDATE `jta_global_prods` SET `is_active` = 'inactive' WHERE `is_active` = '0';

-- Verifica che tutti i record abbiano valori validi
SELECT `id`, `sku`, `name`, `is_active` FROM `jta_global_prods` ORDER BY `id`; 