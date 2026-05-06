-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-04-2026 a las 03:19:59
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `desarrollo_agil`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Desarrollo Web', 'Aprende HTML, CSS, JavaScript y PHP desde cero'),
(2, 'Base de Datos', 'Fundamentos de MySQL, consultas SQL y modelado de datos'),
(3, 'Programación en Python', 'Introducción a Python, estructuras de datos y algoritmos'),
(4, 'Diseño UI/UX', 'Principios de diseño, prototipado y experiencia de usuario'),
(5, 'Redes y Seguridad', 'Fundamentos de redes, protocolos y seguridad informática'),
(6, 'PHP Avanzado', 'Programación orientada a objetos en PHP y frameworks'),
(7, 'JavaScript Moderno', 'ES6+, React y desarrollo frontend avanzado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `ciudad` varchar(100) NOT NULL DEFAULT '',
  `fecha_nacimiento` date DEFAULT NULL,
  `fecha_registro` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id`, `nombre`, `apellido`, `correo`, `ciudad`, `fecha_nacimiento`, `fecha_registro`) VALUES
(1, 'Juan', 'García', 'juan.garcia@email.com', 'Bogotá', '2000-03-15', '2026-03-29'),
(2, 'María', 'López', 'maria.lopez@email.com', 'Medellín', '1999-07-22', '2026-03-29'),
(3, 'Carlos', 'Martínez', 'carlos.martinez@email.com', 'Cali', '2001-01-10', '2026-03-29'),
(5, 'Luis', 'Hernández', 'luis.hernandez@email.com', 'Barranquilla', '1998-04-18', '2026-03-29'),
(6, 'Sofía', 'Torres', 'sofia.torres@email.com', 'Medellín', '2001-09-30', '2026-03-29'),
(7, 'Diego', 'Ramírez', 'diego.ramirez@email.com', 'Cali', '1999-12-12', '2026-03-29'),
(8, 'Valentina', 'Flores', 'valentina.flores@email.com', 'Bogotá', '2002-06-25', '2026-03-29'),
(9, 'Andrés', 'Vargas', 'andres.vargas@email.com', 'Cartagena', '2000-08-14', '2026-03-29'),
(10, 'Camila', 'Castro', 'camila.castro@email.com', 'Medellín', '1999-02-28', '2026-03-29'),
(11, 'Sebastián', 'Moreno', 'sebastian.moreno@email.com', 'Bogotá', '2001-05-17', '2026-03-29'),
(12, 'Isabella', 'Jiménez', 'isabella.jimenez@email.com', 'Bucaramanga', '2000-10-09', '2026-03-29'),
(13, 'Felipe', 'Ruiz', 'felipe.ruiz@email.com', 'Cali', '1998-03-21', '2026-03-29'),
(14, 'Daniela', 'Díaz', 'daniela.diaz@email.com', 'Barranquilla', '2002-01-14', '2026-03-29'),
(15, 'Mateo', 'Sánchez', 'mateo.sanchez@email.com', 'Medellín', '1999-11-03', '2026-03-29'),
(16, 'Luciana', 'Reyes', 'luciana.reyes@email.com', 'Bogotá', '2001-07-19', '2026-03-29'),
(17, 'Santiago', 'Gómez', 'santiago.gomez@email.com', 'Cartagena', '2000-04-27', '2026-03-29'),
(18, 'Valeria', 'Mendoza', 'valeria.mendoza@email.com', 'Cali', '1998-09-08', '2026-03-29'),
(19, 'Nicolás', 'Pérez', 'nicolas.perez@email.com', 'Bucaramanga', '2002-02-11', '2026-03-29'),
(21, 'cristian', 'castrillon', 'ccastrillon@gmail.com', 'Itagüi', '2026-03-29', '2026-03-29'),
(22, 'melanie', 'gonzales', 'melanie.55@hotmail.com', 'medellin', '2026-04-03', '2026-04-03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) DEFAULT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `fecha_inscripcion` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`id`, `estudiante_id`, `curso_id`, `fecha_inscripcion`) VALUES
(1, 1, 1, '2025-01-10'),
(2, 1, 2, '2025-01-15'),
(3, 2, 1, '2025-01-12'),
(4, 2, 3, '2025-02-01'),
(5, 3, 2, '2025-01-20'),
(6, 3, 4, '2025-02-10'),
(9, 5, 3, '2025-02-14'),
(10, 5, 6, '2025-03-10'),
(11, 6, 1, '2025-01-25'),
(12, 6, 2, '2025-02-20'),
(13, 7, 4, '2025-02-08'),
(14, 7, 7, '2025-03-15'),
(15, 8, 1, '2025-01-30'),
(16, 8, 3, '2025-02-25'),
(17, 9, 5, '2025-03-01'),
(18, 9, 6, '2025-03-20'),
(19, 10, 2, '2025-01-22'),
(20, 10, 7, '2025-04-01'),
(21, 11, 1, '2025-02-05'),
(22, 11, 4, '2025-03-12'),
(23, 12, 3, '2025-02-18'),
(24, 12, 5, '2025-04-05'),
(25, 13, 6, '2025-03-08'),
(26, 13, 7, '2025-04-10'),
(27, 14, 1, '2025-02-22'),
(28, 14, 2, '2025-03-25'),
(29, 15, 3, '2025-04-02'),
(30, 15, 4, '2025-04-15'),
(31, 16, 5, '2025-03-18'),
(32, 16, 6, '2025-04-20'),
(33, 17, 7, '2025-04-08'),
(34, 17, 1, '2025-05-01'),
(35, 18, 2, '2025-04-25'),
(36, 18, 3, '2025-05-05'),
(37, 19, 4, '2025-05-10'),
(38, 19, 5, '2025-05-15'),
(42, 13, 4, '2026-03-29'),
(43, 13, 3, '2026-03-29'),
(44, 7, 1, '2026-03-29'),
(49, 6, 7, '2026-03-18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instructores`
--

CREATE TABLE `instructores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `instructores`
--

INSERT INTO `instructores` (`id`, `nombre`, `apellido`, `correo`, `especialidad`) VALUES
(1, 'Roberto', 'Gómez', 'roberto.gomez@academia.com', 'Desarrollo Web'),
(2, 'Patricia', 'Silva', 'patricia.silva@academia.com', 'Base de Datos'),
(3, 'Hernando', 'Castillo', 'hernando.castillo@academia.com', 'Programación'),
(4, 'Luz', 'Bermúdez', 'luz.bermudez@academia.com', 'Diseño UI/UX'),
(5, 'Jorge', 'Pineda', 'jorge.pineda@academia.com', 'Redes y Seguridad'),
(6, 'emilio', 'castillo', 'emilio.23@hotmail.com', 'desarrollo web'),
(7, 'Mariana', 'Torres', 'm.torres@academia.com', 'Analisis de Datos'),
(8, 'Carlos', 'Mendoza', 'carlos.m@academia.com', 'Ciberseguridad'),
(9, 'Isabel', 'Valencia', 'i.valencia@academia.com', 'Gestion de Proyectos'),
(10, 'Mateo', 'Holguín', 'm.holguin@academia.com', 'Desarrollo Backend'),
(11, 'Valeria', 'Quintero', 'v.quintero@academia.com', 'Testing / QA'),
(12, 'Alejandro', 'Ruiz', 'a.ruiz@academia.com', 'Arquitectura de Software'),
(13, 'Gabriela', 'Cano', 'g.cano@academia.com', 'Big Data'),
(14, 'Samuel', 'Mejía', 's.mejia@academia.com', 'Devops'),
(15, 'Paulina', 'Herrera', 'p.herrera@academia.com', 'Diseño Grafico'),
(16, 'Daniel', 'Ossa', 'd.ossa@academia.com', 'Soporte Tecnico'),
(17, 'sandra', 'Méndez', 'l.mendez@academia.com', 'Analista Funcional');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `apellido` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','instructor','estudiante','usuario') NOT NULL DEFAULT 'admin',
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `nombre`, `apellido`, `password_hash`, `rol`, `fecha_creacion`) VALUES
(1, 'ccastrillon33@gmail.com', 'Cristian', 'Castrillon', '$2y$10$axhprpM8VxKFbDrymBm9we9BAJySU8KcWdcPKvCr1Cv2NqUZ4PAry', 'admin', '2026-04-03 20:20:26'),
(2, 'cccastrillon33@gmail.com', 'eduardo', 'aragon', '$2y$10$vRLlf0YNx1hTuMAxzpagNuBSVcOJ3wv07uCZuGCKCrzaNyKEL7E/C', 'usuario', '2026-04-05 13:53:15'),
(3, 'melanie@gmail.com', 'melanie', 'gonzalez', '$2y$10$NINHdeLnhS5Qj1GUyS4nw.K95GbiVG.sZhxRUamyXftrXB7geRaoy', 'usuario', '2026-04-05 16:35:24');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `curso_id` (`curso_id`);

--
-- Indices de la tabla `instructores`
--
ALTER TABLE `instructores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `instructores`
--
ALTER TABLE `instructores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
