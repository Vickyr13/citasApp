-- Crear base de datos
CREATE DATABASE peluqueria_db;
USE peluqueria_db;

-- Tabla de servicios
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    duracion INT NOT NULL COMMENT 'Duración en minutos',
    precio DECIMAL(10,2) NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(150),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de citas
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    servicio_id INT NOT NULL,
    fecha_cita DATE NOT NULL,
    hora_cita TIME NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'completada', 'cancelada') DEFAULT 'pendiente',
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cita (fecha_cita, hora_cita, servicio_id)
);

-- Insertar servicios de ejemplo
INSERT INTO servicios (nombre, descripcion, duracion, precio) VALUES
('Corte de Cabello', 'Corte de cabello clásico para hombre/mujer', 30, 15.00),
('Peinado', 'Peinado para eventos especiales', 45, 25.00),
('Tinte', 'Coloración completa del cabello', 90, 40.00),
('Mechas', 'Aplicación de mechas o reflejos', 120, 55.00),
('Tratamiento Capilar', 'Hidratación y nutrición del cabello', 60, 30.00),
('Manicure', 'Cuidado completo de uñas de manos', 45, 20.00),
('Pedicure', 'Cuidado completo de uñas de pies', 60, 25.00);

-- Insertar clientes de ejemplo
INSERT INTO clientes (nombre, apellido, telefono, email) VALUES
('María', 'González', '+503-1234-5678', 'maria.gonzalez@email.com'),
('Ana', 'López', '+503-2345-6789', 'ana.lopez@email.com'),
('Carmen', 'Martínez', '+503-3456-7890', 'carmen.martinez@email.com');