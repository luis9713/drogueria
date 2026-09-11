-- ============================================================
-- SCRIPT SQL: Valor de flete en compras
-- Base de datos: farmaciasistema
-- ============================================================

ALTER TABLE `compra`
ADD COLUMN `flete` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `total`;

-- ============================================================
-- VERIFICACION
-- ============================================================
-- SHOW COLUMNS FROM compra;
