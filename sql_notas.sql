-- ============================================================
-- Script SQL para crear la tabla de notas
-- Sistema de Farmacia - Módulo de Notas y Recordatorios
-- Ejecutar en la base de datos: farmaciasistema
-- ============================================================

CREATE TABLE IF NOT EXISTS `notas` (
  `id_nota`             INT(11)       NOT NULL AUTO_INCREMENT,
  `titulo`              VARCHAR(100)  NOT NULL,
  `contenido`           TEXT          NOT NULL,
  `color`               VARCHAR(20)   NOT NULL DEFAULT 'yellow',
  `id_usuario`          INT(11)       NOT NULL,
  `fecha_creacion`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_nota`),
  KEY `idx_notas_usuario` (`id_usuario`),
  CONSTRAINT `fk_notas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
