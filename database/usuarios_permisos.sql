-- Script SQL para Gestión de Usuarios y Auditoría
-- Ejecutar después del schema.sql principal

-- Tabla de permisos personalizados por usuario
CREATE TABLE IF NOT EXISTS permisos_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    puede_ver BOOLEAN DEFAULT FALSE,
    puede_crear BOOLEAN DEFAULT FALSE,
    puede_editar BOOLEAN DEFAULT FALSE,
    puede_eliminar BOOLEAN DEFAULT FALSE,
    puede_exportar BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_modulo (usuario_id, modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de permisos por rol (si no existe)
CREATE TABLE IF NOT EXISTS permisos_rol (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol ENUM('ADMINISTRADOR', 'SECRETARIA', 'DOCENTE', 'OPERADOR') NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    puede_ver BOOLEAN DEFAULT FALSE,
    puede_crear BOOLEAN DEFAULT FALSE,
    puede_editar BOOLEAN DEFAULT FALSE,
    puede_eliminar BOOLEAN DEFAULT FALSE,
    puede_exportar BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rol_modulo (rol, modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mejorar tabla de auditoría (agregar campos si no existen)
ALTER TABLE auditoria 
ADD COLUMN IF NOT EXISTS usuario_nombre VARCHAR(255),
ADD COLUMN IF NOT EXISTS modulo VARCHAR(50),
ADD INDEX idx_usuario_fecha (usuario_id, created_at),
ADD INDEX idx_modulo_fecha (modulo, created_at);

-- Insertar permisos por defecto para cada rol
INSERT INTO permisos_rol (rol, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar, puede_exportar) VALUES
-- ADMINISTRADOR (acceso total)
('ADMINISTRADOR', 'dashboard', 1, 1, 1, 1, 1),
('ADMINISTRADOR', 'centro_datos', 1, 1, 1, 1, 1),
('ADMINISTRADOR', 'nueva_matricula', 1, 1, 1, 1, 1),
('ADMINISTRADOR', 'ratificacion', 1, 1, 1, 1, 1),
('ADMINISTRADOR', 'directorio', 1, 1, 1, 1, 1),
('ADMINISTRADOR', 'traslado', 1, 1, 1, 1, 1),
('ADMINISTRADOR', 'descarga_fichas', 1, 1, 1, 1, 1),
('ADMINISTRADOR', 'listas_oficiales', 1, 1, 1, 1, 1),
('ADMINISTRADOR', 'reportes', 1, 1, 1, 1, 1),
('ADMINISTRADOR', 'responsables', 1, 1, 1, 1, 1),
('ADMINISTRADOR', 'ajustes', 1, 1, 1, 1, 1),
('ADMINISTRADOR', 'usuarios', 1, 1, 1, 1, 1),

-- SECRETARIA (acceso a matrícula y ratificación)
('SECRETARIA', 'dashboard', 1, 0, 0, 0, 0),
('SECRETARIA', 'centro_datos', 1, 0, 0, 0, 1),
('SECRETARIA', 'nueva_matricula', 1, 1, 1, 0, 1),
('SECRETARIA', 'ratificacion', 1, 1, 1, 0, 1),
('SECRETARIA', 'directorio', 1, 0, 0, 0, 1),
('SECRETARIA', 'descarga_fichas', 1, 0, 0, 0, 1),
('SECRETARIA', 'listas_oficiales', 1, 0, 0, 0, 1),
('SECRETARIA', 'reportes', 1, 0, 0, 0, 1),

-- OPERADOR (solo matrícula y ratificación)
('OPERADOR', 'dashboard', 1, 0, 0, 0, 0),
('OPERADOR', 'nueva_matricula', 1, 1, 1, 0, 0),
('OPERADOR', 'ratificacion', 1, 1, 1, 0, 0),

-- DOCENTE (solo lectura)
('DOCENTE', 'dashboard', 1, 0, 0, 0, 0),
('DOCENTE', 'centro_datos', 1, 0, 0, 0, 0),
('DOCENTE', 'directorio', 1, 0, 0, 0, 1),
('DOCENTE', 'listas_oficiales', 1, 0, 0, 0, 1),
('DOCENTE', 'reportes', 1, 0, 0, 0, 1)
ON DUPLICATE KEY UPDATE
    puede_ver = VALUES(puede_ver),
    puede_crear = VALUES(puede_crear),
    puede_editar = VALUES(puede_editar),
    puede_eliminar = VALUES(puede_eliminar),
    puede_exportar = VALUES(puede_exportar);

-- Agregar nuevo rol OPERADOR a la tabla usuarios si no existe
ALTER TABLE usuarios 
MODIFY COLUMN rol ENUM('ADMINISTRADOR', 'SECRETARIA', 'DOCENTE', 'OPERADOR') DEFAULT 'OPERADOR';
