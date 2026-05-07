CREATE DATABASE convivium;
USE convivium;

-- ======================
-- TABLA ROLES
-- ======================
CREATE TABLE rol (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- ======================
-- TABLA USUARIO
-- ======================
CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    contrasena VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    estado BOOLEAN DEFAULT TRUE,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES rol(id)
);

-- ======================
-- TABLA CONJUNTO
-- ======================
CREATE TABLE conjunto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(150) NOT NULL,
    numero_bloques INT,
    numero_unidades INT
);

-- ======================
-- TABLA UNIDAD
-- ======================
CREATE TABLE unidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conjunto_id INT NOT NULL,
    torre VARCHAR(10),
    numero VARCHAR(20) NOT NULL,
    metros_cuadrados DECIMAL(10,2),
    propietario_id INT,
    residente_id INT,
    estado ENUM('ocupado','desocupado') DEFAULT 'ocupado',
    FOREIGN KEY (conjunto_id) REFERENCES conjunto(id),
    FOREIGN KEY (propietario_id) REFERENCES usuario(id),
    FOREIGN KEY (residente_id) REFERENCES usuario(id)
);

-- ======================
-- TABLA TIPO_COMUNICACION
-- ======================
CREATE TABLE tipo_comunicacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
);

-- ======================
-- TABLA COMUNICACION
-- ======================
CREATE TABLE comunicacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    emisor_id INT NOT NULL,
    tipo_id INT NOT NULL,
    FOREIGN KEY (emisor_id) REFERENCES usuario(id),
    FOREIGN KEY (tipo_id) REFERENCES tipo_comunicacion(id)
);

-- ======================
-- TABLA COMUNICACION_RECEPTORES
-- ======================
CREATE TABLE comunicacion_receptores (
    comunicacion_id INT,
    usuario_id INT,
    PRIMARY KEY (comunicacion_id, usuario_id),
    FOREIGN KEY (comunicacion_id) REFERENCES comunicacion(id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id)
);

-- ======================
-- TABLA PAGO
-- ======================
CREATE TABLE pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unidad_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATETIME,
    fecha_pago DATETIME,
    metodo_pago VARCHAR(50),
    estado ENUM('pendiente','pagado','vencido') DEFAULT 'pendiente',
    comprobante VARCHAR(255),
    FOREIGN KEY (unidad_id) REFERENCES unidad(id)
);

-- ======================
-- TABLA ZONA_COMUN
-- ======================
CREATE TABLE zona_comun (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    capacidad INT,
    horario_disponible VARCHAR(100)
);

-- ======================
-- TABLA RESERVA_ZONA
-- ======================
CREATE TABLE reserva_zona (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zona_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_reserva DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    estado ENUM('pendiente','aprobada','cancelada') DEFAULT 'pendiente',
    FOREIGN KEY (zona_id) REFERENCES zona_comun(id),
    FOREIGN KEY (usuario_id) REFERENCES usuario(id)
);

-- ======================
-- TABLA MANTENIMIENTO
-- ======================
CREATE TABLE mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zona_id INT NOT NULL,
    usuario_reporta_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    prioridad ENUM('baja','media','alta') DEFAULT 'media',
    fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_solucion DATETIME,
    estado ENUM('pendiente','en_proceso','solucionado') DEFAULT 'pendiente',
    evidencia VARCHAR(255),
    FOREIGN KEY (zona_id) REFERENCES zona_comun(id),
    FOREIGN KEY (usuario_reporta_id) REFERENCES usuario(id)
);

-- ======================
-- TABLA PQRS
-- ======================
CREATE TABLE pqrs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('peticion','queja','reclamo','sugerencia') NOT NULL,
    asunto VARCHAR(100),
    descripcion TEXT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente','en_proceso','cerrado') DEFAULT 'pendiente',
    FOREIGN KEY (usuario_id) REFERENCES usuario(id)
);
