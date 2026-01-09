-- Base de datos para Sistema de Matrícula IE Las Capullanas 2026
-- Ejecutar este script en MySQL

CREATE DATABASE IF NOT EXISTS colegio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE colegio;

-- Tabla de estudiantes
CREATE TABLE IF NOT EXISTS estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(8) UNIQUE NOT NULL,
    nombres VARCHAR(255) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    direccion VARCHAR(255),
    nivel ENUM('INICIAL', 'PRIMARIA', 'SECUNDARIA') NOT NULL,
    grado VARCHAR(50) NOT NULL,
    seccion CHAR(1) DEFAULT 'A',
    estado ENUM('ACTIVO', 'RETIRADO', 'TRASLADADO') DEFAULT 'ACTIVO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dni (dni),
    INDEX idx_nivel_grado_seccion (nivel, grado, seccion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de datos de salud
CREATE TABLE IF NOT EXISTS salud_estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    esquema_vacunas ENUM('COMPLETO', 'INCOMPLETO', 'NO_ESPECIFICADO') DEFAULT 'NO_ESPECIFICADO',
    dosis_covid INT DEFAULT 0,
    peso_kg DECIMAL(5,2),
    talla_cm DECIMAL(5,2),
    seguro_salud ENUM('SIS', 'ESSALUD', 'PRIVADO', 'NINGUNO') DEFAULT 'NINGUNO',
    grupo_sanguineo VARCHAR(5),
    tiene_alergias BOOLEAN DEFAULT FALSE,
    detalle_alergias TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_estudiante_salud (estudiante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de representantes/padres
CREATE TABLE IF NOT EXISTS representantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(8) UNIQUE NOT NULL,
    nombres VARCHAR(255) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NOT NULL,
    parentesco ENUM('MADRE', 'PADRE', 'APODERADO', 'TUTOR') NOT NULL,
    celular VARCHAR(15),
    whatsapp VARCHAR(15),
    email VARCHAR(100),
    direccion VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dni (dni)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relación estudiante-representante (muchos a muchos)
CREATE TABLE IF NOT EXISTS estudiante_representante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    representante_id INT NOT NULL,
    es_principal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (representante_id) REFERENCES representantes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_estudiante_representante (estudiante_id, representante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de contactos de emergencia
CREATE TABLE IF NOT EXISTS contactos_emergencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    parentesco VARCHAR(50) NOT NULL,
    celular VARCHAR(15) NOT NULL,
    whatsapp VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de hermanas en la institución
CREATE TABLE IF NOT EXISTS hermanas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    hermana_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (hermana_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hermanas (estudiante_id, hermana_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de datos sacramentales
CREATE TABLE IF NOT EXISTS sacramentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    bautismo BOOLEAN DEFAULT FALSE,
    primera_comunion BOOLEAN DEFAULT FALSE,
    confirmacion BOOLEAN DEFAULT FALSE,
    estado_matrimonio_padres ENUM('RELIGIOSO_CIVIL', 'SOLO_CIVIL', 'SOLO_RELIGIOSO', 'CONVIVIENTES', 'NINGUNO') DEFAULT 'NINGUNO',
    observacion_preparacion VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_estudiante_sacramento (estudiante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de documentos
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    tipo_documento ENUM('FICHA_UNICA', 'CONSTANCIA_MATRICULA', 'CERTIFICADO_ESTUDIOS', 'BOLETA_NOTAS', 'DNI_ESTUDIANTE', 'CARTA_PODER') NOT NULL,
    tiene_documento BOOLEAN DEFAULT FALSE,
    fecha_entrega DATE,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_estudiante_documento (estudiante_id, tipo_documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de matrículas (registro histórico)
CREATE TABLE IF NOT EXISTS matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    anio_escolar YEAR NOT NULL,
    nivel VARCHAR(50) NOT NULL,
    grado VARCHAR(50) NOT NULL,
    seccion CHAR(1) NOT NULL,
    estado ENUM('RATIFICADO', 'NUEVO', 'TRASLADADO', 'RETIRADO') DEFAULT 'NUEVO',
    fecha_matricula DATE NOT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    INDEX idx_anio (anio_escolar),
    INDEX idx_nivel_grado_seccion (nivel, grado, seccion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de usuarios del sistema
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    rol ENUM('ADMINISTRADOR', 'DOCENTE', 'SECRETARIA') DEFAULT 'SECRETARIA',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario administrador
INSERT INTO usuarios (username, password, nombre_completo, rol) VALUES 
('administrador', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador General', 'ADMINISTRADOR');
-- Contraseña: 2026sistema

-- Insertar datos de ejemplo
INSERT INTO estudiantes (dni, nombres, apellido_paterno, apellido_materno, fecha_nacimiento, direccion, nivel, grado, seccion) VALUES
('62725382', 'SAORI ANTUANET', 'ABREGU', 'HUACCHILLO', '1996-05-25', 'AV. SANTA CRUZ 407 OBRERO', 'SECUNDARIA', '4° SECUNDARIA', 'A'),
('91681721', 'ISABELLA ANGELI', 'AGURTO', 'BURGOS', '2018-03-15', 'JR. LOS PINOS 234', 'PRIMARIA', '2° PRIMARIA', 'A'),
('90666627', 'TILSA KALANI', 'ALAYO', 'AREVALO', '2018-07-22', 'AV. GRAU 567', 'PRIMARIA', '2° PRIMARIA', 'B'),
('63764008', 'MILENA ALEXANDRA', 'ALBAN', 'SALDARRIAGA', '2018-11-10', 'CALLE LIMA 890', 'PRIMARIA', '2° PRIMARIA', 'C'),
('88123456', 'MARIA FERNANDA', 'CASTRO', 'LOPEZ', '2017-06-05', 'AV. SULLANA 123', 'PRIMARIA', '3° PRIMARIA', 'A');

-- Insertar representantes de ejemplo
INSERT INTO representantes (dni, nombres, apellido_paterno, apellido_materno, parentesco, celular, whatsapp) VALUES
('45678901', 'MARIA ELENA', 'HUACCHILLO', 'GARCIA', 'MADRE', '950601937', '950601937'),
('45678902', 'CARMEN ROSA', 'BURGOS', 'SILVA', 'MADRE', '987654321', '987654321'),
('45678903', 'LUCIA PATRICIA', 'AREVALO', 'TORRES', 'MADRE', '976543210', '976543210');

-- Relacionar estudiantes con representantes
INSERT INTO estudiante_representante (estudiante_id, representante_id, es_principal) VALUES
(1, 1, TRUE),
(2, 2, TRUE),
(3, 3, TRUE);

-- Insertar datos de salud de ejemplo
INSERT INTO salud_estudiantes (estudiante_id, esquema_vacunas, dosis_covid, peso_kg, talla_cm, seguro_salud, grupo_sanguineo) VALUES
(1, 'COMPLETO', 3, 80.00, 155.00, 'SIS', 'O+'),
(2, 'COMPLETO', 2, 35.50, 120.00, 'ESSALUD', 'A+'),
(3, 'COMPLETO', 2, 33.00, 118.00, 'SIS', 'B+');

-- Insertar matrículas 2026
INSERT INTO matriculas (estudiante_id, anio_escolar, nivel, grado, seccion, estado, fecha_matricula) VALUES
(1, 2026, 'SECUNDARIA', '4° SECUNDARIA', 'A', 'RATIFICADO', '2026-01-08'),
(2, 2026, 'PRIMARIA', '2° PRIMARIA', 'A', 'RATIFICADO', '2026-01-08'),
(3, 2026, 'PRIMARIA', '2° PRIMARIA', 'B', 'RATIFICADO', '2026-01-08'),
(4, 2026, 'PRIMARIA', '2° PRIMARIA', 'C', 'RATIFICADO', '2026-01-08'),
(5, 2026, 'PRIMARIA', '3° PRIMARIA', 'A', 'RATIFICADO', '2026-01-08');
