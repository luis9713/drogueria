-- ============================================================
-- SCRIPT SQL: Medio de pago explicito para abonos de credito
-- Base de datos: farmaciasistema
-- ============================================================

-- 1) Agregar columna medio_pago a venta
ALTER TABLE `venta`
ADD COLUMN `medio_pago` VARCHAR(20) NOT NULL DEFAULT 'Efectivo'
AFTER `tipo_pago`;

-- 2) (Opcional) Normalizar historicos de ventas Nequi directas
UPDATE `venta`
SET `medio_pago` = 'Nequi'
WHERE `tipo_pago` = 'Nequi';

-- 3) (Opcional) Si en algún momento usaste prefijo legado DepositoNequi_
UPDATE `venta`
SET `medio_pago` = 'Nequi'
WHERE `tipo_pago` LIKE 'DepositoNequi_%';

-- ============================================================
-- VERIFICACION
-- ============================================================
-- SHOW COLUMNS FROM venta;
-- SELECT id_venta, tipo_pago, medio_pago, total, depositado FROM venta ORDER BY id_venta DESC LIMIT 50;
