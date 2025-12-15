-- Base de datos para el servicio web de grupos musicales
-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE db;

-- Crear usuario administrador
CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON db.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;

-- Tabla GÉNERO
CREATE TABLE IF NOT EXISTS genero (
    identificador INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla GRUPO
CREATE TABLE IF NOT EXISTS grupo (
    identificador INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    genero INT NOT NULL,
    FOREIGN KEY (genero) REFERENCES genero(identificador) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar algunos géneros de ejemplo
INSERT INTO genero (nombre) VALUES 
('Rock'),
('Pop'),
('Jazz'),
('Clásica'),
('Electrónica'),
('Hip-Hop'),
('Reggae'),
('Metal');

