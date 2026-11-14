-- ============================================================================
-- Esquema de base de datos para el formulario de registro - Fundación Taboada
-- Compatible con MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.3+ (Hostinger y local)
-- ============================================================================
-- IMPORTANTE PARA HOSTINGER:
-- 1. Crea la base de datos desde Hostinger hPanel -> Bases de datos -> Gestión.
-- 2. Entra a phpMyAdmin, selecciona tu base de datos en el menú izquierdo
--    y luego ve a la pestaña "Importar" para subir este archivo.
-- (No se incluye CREATE DATABASE porque Hostinger no permite crear bases
--  de datos mediante SQL en planes compartidos).
--
-- Si estás probando en local (XAMPP/Laragon), crea primero una base de datos
-- llamada `fundacion_taboada` con cotejamiento `utf8mb4_unicode_ci` y luego
-- importa este archivo.
-- ============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `registros` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(150) NOT NULL,
    `foto` VARCHAR(255) NOT NULL COMMENT 'Ruta relativa del archivo de imagen guardado en el servidor',
    `carrera` VARCHAR(150) NOT NULL,
    `universidad` VARCHAR(150) NOT NULL,
    `correo` VARCHAR(180) NOT NULL,
    `telefono` VARCHAR(25) NOT NULL,
    `fecha_cumpleanos` DATE NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_registros_correo` (`correo`),
    KEY `idx_registros_universidad` (`universidad`),
    KEY `idx_registros_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
