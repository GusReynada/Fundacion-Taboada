-- ============================================================================
-- Esquema de base de datos para el formulario de registro - Fundación Taboada
-- Tabla real en Hostinger: `alumnos`
-- ============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `alumnos` (
    `idAlumno` INT(11) NOT NULL AUTO_INCREMENT,
    `aloNombreCompleto` VARCHAR(200) NOT NULL,
    `aloFoto` LONGBLOB NOT NULL,
    `aloCarrera` VARCHAR(150) NOT NULL,
    `aloUniversidad` VARCHAR(150) NOT NULL,
    `aloCorreo` VARCHAR(250) NOT NULL,
    `aloTelefono` VARCHAR(150) NOT NULL,
    `aloFechaNacimiento` DATE NULL DEFAULT NULL,
    `aloFotoMiniatura` LONGBLOB NULL DEFAULT NULL,
    PRIMARY KEY (`idAlumno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
