-- database/esquema.sql

CREATE DATABASE IF NOT EXISTS tecno DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tecno;

-- 1. TABLA DE USUARIOS
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- 2. TABLA OTP (Tokens efímeros)
CREATE TABLE otp_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    codigo VARCHAR(6) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expira_en DATETIME NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_codigo (usuario_id, codigo)
) ENGINE=InnoDB;

-- 3. TABLA DE AUDITORÍA (Registra IPs, eventos y fallos)
CREATE TABLE auditoria_accesos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    email_provisto VARCHAR(150) NOT NULL,
    tipo_evento ENUM('LOGIN_EXITOSO', 'LOGIN_FALLIDO', 'OTP_EXITOSO', 'OTP_FALLIDO', 'LOGOUT', 'CUENTA_BLOQUEADA') NOT NULL,
    detalles VARCHAR(255) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    fecha_ingreso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha_ingreso),
    INDEX idx_evento (tipo_evento),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB;

-- 4. TABLA MÉTRICAS (Contador de visitas en tiempo real)
CREATE TABLE visitas_tiempo_real (
    fecha DATE PRIMARY KEY,
    total_visitas INT DEFAULT 1,
    ultima_visita TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. USUARIO DE PRUEBA (Contraseña: Seguridad123)
INSERT INTO usuarios (nombre, email, password) 
VALUES ('Administrador', 'admin@secureauth.com', '$2y$10$Ew9bE4.K3x6Vz.Bq8hL.1.3Qz9bE4.K3x6Vz.Bq8hL.1.3Qz9bE4');