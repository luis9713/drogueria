-- ============================================================
-- SCRIPT SQL: Soporte para pagos con Nequi
-- Ejecutar en la base de datos: farmaciasistema
-- ============================================================

-- 1. Agregar columna total_ventas_nequi a la tabla caja
ALTER TABLE `caja` 
ADD COLUMN `total_ventas_nequi` DECIMAL(12,2) NOT NULL DEFAULT 0 
AFTER `total_ventas_credito`;

-- 2. Agregar columna saldo_nequi a la tabla contabilidad
ALTER TABLE `contabilidad` 
ADD COLUMN `saldo_nequi` DECIMAL(12,2) NOT NULL DEFAULT 0 
AFTER `saldo_actual`;

-- 3. Agregar columna saldo_nequi a la tabla movimientos_contabilidad (para rastrear saldo nequi por movimiento)
ALTER TABLE `movimientos_contabilidad` 
ADD COLUMN `es_nequi` TINYINT(1) NOT NULL DEFAULT 0 
AFTER `referencia`;

-- ============================================================
-- VERIFICACIÓN: Ejecutar estas consultas para confirmar
-- ============================================================
-- SHOW COLUMNS FROM caja;
-- SHOW COLUMNS FROM contabilidad;
-- SHOW COLUMNS FROM movimientos_contabilidad;
