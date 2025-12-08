-- =====================================================
-- ACTUALIZACIÓN BASE DE DATOS - SISTEMA DE CAJA Y CONTABILIDAD
-- Fecha: 2025-12-08
-- Descripción: Agrega tablas para control de caja diaria y contabilidad general
-- =====================================================

-- =====================================================
-- MÓDULO 1: CAJA DIARIA (Control de efectivo del día)
-- =====================================================

-- Tabla principal de caja (apertura/cierre diario)
CREATE TABLE IF NOT EXISTS caja (
  id_caja INT PRIMARY KEY AUTO_INCREMENT,
  fecha_apertura DATETIME NOT NULL,
  fecha_cierre DATETIME NULL,
  monto_inicial DECIMAL(10,2) NOT NULL COMMENT 'Efectivo base para dar vueltos',
  monto_final DECIMAL(10,2) NULL COMMENT 'Efectivo contado al cerrar',
  total_ventas_contado DECIMAL(10,2) DEFAULT 0 COMMENT 'Suma de ventas de contado del día',
  total_ventas_credito DECIMAL(10,2) DEFAULT 0 COMMENT 'Suma de ventas a crédito del día',
  total_depositos_credito DECIMAL(10,2) DEFAULT 0 COMMENT 'Depósitos recibidos de créditos',
  efectivo_esperado DECIMAL(10,2) NULL COMMENT 'Calculado: inicial + contado + depósitos',
  efectivo_real DECIMAL(10,2) NULL COMMENT 'Efectivo realmente contado',
  diferencia DECIMAL(10,2) NULL COMMENT 'Real - Esperado (positivo=sobrante, negativo=faltante)',
  estado ENUM('abierta', 'cerrada') DEFAULT 'abierta',
  id_usuario_apertura INT NOT NULL,
  id_usuario_cierre INT NULL,
  observaciones TEXT NULL,
  FOREIGN KEY (id_usuario_apertura) REFERENCES usuario(id_usuario),
  FOREIGN KEY (id_usuario_cierre) REFERENCES usuario(id_usuario),
  INDEX idx_estado (estado),
  INDEX idx_fecha_apertura (fecha_apertura)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- =====================================================
-- MÓDULO 2: CONTABILIDAD GENERAL (Balance del negocio)
-- =====================================================

-- Tabla de estado contable actual (solo 1 registro)
CREATE TABLE IF NOT EXISTS contabilidad (
  id_contabilidad INT PRIMARY KEY,
  saldo_actual DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Dinero total disponible en el negocio',
  saldo_confirmado BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Indica si el saldo inicial fue confirmado',
  fecha_actualizacion DATETIME NOT NULL,
  ultima_modificacion VARCHAR(255) NULL COMMENT 'Descripción del último movimiento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar registro inicial de contabilidad
INSERT INTO contabilidad (id_contabilidad, saldo_actual, saldo_confirmado, fecha_actualizacion, ultima_modificacion) 
VALUES (1, 0, FALSE, NOW(), 'Inicialización del sistema de contabilidad')
ON DUPLICATE KEY UPDATE id_contabilidad = id_contabilidad;

-- Tabla de historial de movimientos contables
CREATE TABLE IF NOT EXISTS movimientos_contabilidad (
  id_movimiento INT PRIMARY KEY AUTO_INCREMENT,
  fecha_movimiento DATETIME NOT NULL,
  tipo ENUM('Ingreso', 'Egreso') NOT NULL,
  concepto VARCHAR(255) NOT NULL COMMENT 'Concepto del movimiento',
  monto DECIMAL(10,2) NOT NULL,
  categoria VARCHAR(100) NULL COMMENT 'Servicios, Compras, Sueldos, Cierre de Caja, etc.',
  descripcion TEXT NULL,
  saldo_anterior DECIMAL(10,2) NOT NULL COMMENT 'Saldo antes del movimiento',
  saldo DECIMAL(10,2) NOT NULL COMMENT 'Saldo después del movimiento',
  id_usuario INT NOT NULL,
  referencia VARCHAR(100) NULL COMMENT 'ID de caja, comprobante, etc.',
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
  INDEX idx_fecha_movimiento (fecha_movimiento),
  INDEX idx_tipo (tipo),
  INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS DE EJEMPLO (OPCIONAL - Comentar si no se desea)
-- =====================================================

-- Ejemplo de apertura de caja
-- INSERT INTO caja (fecha_apertura, monto_inicial, estado, id_usuario_apertura) 
-- VALUES (NOW(), 100000, 'abierta', 1);

-- Modificar tabla venta para asociar cada venta a una caja
ALTER TABLE venta 
ADD COLUMN id_caja INT NULL COMMENT 'Caja a la que pertenece esta venta' AFTER tipo_pago,
ADD FOREIGN KEY fk_venta_caja (id_caja) REFERENCES caja(id_caja);

-- Agregar campo saldo_confirmado si la tabla contabilidad ya existe
ALTER TABLE contabilidad 
ADD COLUMN IF NOT EXISTS saldo_confirmado BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Indica si el saldo inicial fue confirmado' AFTER saldo_actual;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
