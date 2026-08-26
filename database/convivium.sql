-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-08-2026 a las 01:01:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `convivium`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agrupaciones`
--

CREATE TABLE `agrupaciones` (
  `id_agrupacion` int(11) NOT NULL,
  `id_tipo_agrupacion` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `agrupaciones`
--

INSERT INTO `agrupaciones` (`id_agrupacion`, `id_tipo_agrupacion`, `nombre`, `descripcion`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 'Torre 1', 'Prueba', 1, '2026-08-10 22:56:14', '2026-08-10 22:56:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agrupacion_tipos_unidad`
--

CREATE TABLE `agrupacion_tipos_unidad` (
  `id_agrupacion_tipo` int(11) NOT NULL,
  `id_agrupacion` int(11) NOT NULL,
  `id_tipo_config` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aplicaciones_pagos`
--

CREATE TABLE `aplicaciones_pagos` (
  `id_aplicacion` int(11) NOT NULL,
  `id_pago` int(11) NOT NULL,
  `id_cartera` int(11) NOT NULL,
  `valor_aplicado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_aplicacion` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_aplicacion` enum('AUTOMATICA','MANUAL') NOT NULL DEFAULT 'AUTOMATICA',
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulos`
--

CREATE TABLE `articulos` (
  `id_articulo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `unidad_medida` varchar(20) DEFAULT NULL,
  `stock_actual` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `articulos`
--

INSERT INTO `articulos` (`id_articulo`, `nombre`, `categoria`, `unidad_medida`, `stock_actual`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Mesa', 'Mobiliarios', 'Unidad', 0, 1, '2026-08-25 02:03:07', '2026-08-25 02:03:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calendario_financiero`
--

CREATE TABLE `calendario_financiero` (
  `id_calendario` int(11) NOT NULL,
  `periodo` date NOT NULL,
  `fecha_inicio_cierre` date NOT NULL,
  `fecha_fin_cierre` date NOT NULL,
  `fecha_facturacion` date NOT NULL,
  `fecha_generacion_intereses` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('ABIERTO','EN_CIERRE','CERRADO') NOT NULL DEFAULT 'ABIERTO',
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calendario_financiero`
--

INSERT INTO `calendario_financiero` (`id_calendario`, `periodo`, `fecha_inicio_cierre`, `fecha_fin_cierre`, `fecha_facturacion`, `fecha_generacion_intereses`, `fecha_vencimiento`, `estado`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, '2026-08-01', '2026-08-01', '2026-08-07', '2026-08-15', '2026-08-14', '2026-08-30', 'ABIERTO', NULL, '2026-08-22 20:36:59', '2026-08-22 20:40:34'),
(2, '2026-09-01', '2026-09-01', '2026-09-07', '2026-09-15', '2026-09-01', '2026-09-30', 'ABIERTO', NULL, '2026-08-22 20:45:45', '2026-08-22 22:28:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cartera`
--

CREATE TABLE `cartera` (
  `id_cartera` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `id_tipo_obligacion` int(11) NOT NULL,
  `periodo` date NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `valor_original` decimal(15,2) NOT NULL DEFAULT 0.00,
  `valor_pagado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `saldo` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('PENDIENTE','PAGADA','ANULADA') NOT NULL DEFAULT 'PENDIENTE',
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cartera`
--

INSERT INTO `cartera` (`id_cartera`, `id_unidad`, `id_tipo_obligacion`, `periodo`, `descripcion`, `valor_original`, `valor_pagado`, `saldo`, `fecha_vencimiento`, `estado`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 3, 2, '2026-01-01', 'Administración ordinaria enero 2026', 300000.00, 0.00, 300000.00, '2026-01-10', 'PENDIENTE', 'DATOS DE PRUEBA', '2026-08-22 18:38:10', '2026-08-22 18:38:10'),
(2, 3, 4, '2026-01-01', 'Parqueadero enero 2026', 100000.00, 0.00, 100000.00, '2026-01-10', 'PENDIENTE', 'DATOS DE PRUEBA', '2026-08-22 18:38:10', '2026-08-22 18:38:10'),
(3, 3, 2, '2026-02-01', 'Administración ordinaria febrero 2026', 300000.00, 0.00, 300000.00, '2026-02-10', 'PENDIENTE', 'DATOS DE PRUEBA', '2026-08-22 18:38:10', '2026-08-22 18:38:10'),
(4, 3, 6, '2026-02-01', 'Multa de prueba', 150000.00, 0.00, 150000.00, '2026-02-15', 'PENDIENTE', 'DATOS DE PRUEBA', '2026-08-22 18:38:10', '2026-08-22 18:38:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciudades`
--

CREATE TABLE `ciudades` (
  `id_ciudad` int(11) NOT NULL,
  `id_departamento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo_dane` varchar(10) DEFAULT NULL,
  `Activo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ciudades`
--

INSERT INTO `ciudades` (`id_ciudad`, `id_departamento`, `nombre`, `codigo_dane`, `Activo`) VALUES
(15, 8, 'Medellín', '05001', NULL),
(16, 8, 'Envigado', '05266', NULL),
(18, 8, 'Bello', '05088', NULL),
(19, 8, 'Caldas', '05129', NULL),
(20, 8, 'Copacabana', '05212', NULL),
(21, 8, 'Girardota', '05308', NULL),
(22, 8, 'Itagüí', '05360', NULL),
(23, 8, 'La Estrella', '05380', NULL),
(24, 8, 'Sabaneta', '05631', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicacion`
--

CREATE TABLE `comunicacion` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `emisor_id` int(11) NOT NULL,
  `tipo_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunicacion_receptores`
--

CREATE TABLE `comunicacion_receptores` (
  `comunicacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conceptos_facturacion`
--

CREATE TABLE `conceptos_facturacion` (
  `id_concepto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo_calculo` enum('FIJO','METRO_CUADRADO','COEFICIENTE','PORCENTAJE') NOT NULL DEFAULT 'FIJO',
  `id_tipo_obligacion` int(11) DEFAULT NULL,
  `id_cuenta_contable` int(11) DEFAULT NULL,
  `obligatorio` tinyint(1) NOT NULL DEFAULT 0,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `conceptos_facturacion`
--

INSERT INTO `conceptos_facturacion` (`id_concepto`, `nombre`, `descripcion`, `tipo_calculo`, `id_tipo_obligacion`, `id_cuenta_contable`, `obligatorio`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Administración 1', 'Cuota ordinaria de administración', 'FIJO', 2, NULL, 1, 1, '2026-08-19 02:50:51', '2026-08-23 03:48:27'),
(2, 'Parqueadero', 'Cobro por parqueadero', 'FIJO', 4, 2, 0, 1, '2026-08-19 02:50:51', '2026-08-23 03:48:33'),
(3, 'Cuota extraordinaria', 'Cobro extraordinario aprobado por la copropiedad', 'FIJO', 3, NULL, 0, 1, '2026-08-19 02:50:51', '2026-08-23 03:48:40'),
(4, 'Intereses de mora', 'Intereses generados por pagos vencidos', 'PORCENTAJE', 1, NULL, 0, 1, '2026-08-19 02:50:51', '2026-08-23 03:48:46'),
(5, 'Seguro de zonas comunes', 'Cobro correspondiente al seguro de la copropiedad', 'FIJO', 5, 1, 0, 1, '2026-08-19 03:17:58', '2026-08-23 03:48:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_cartera`
--

CREATE TABLE `configuracion_cartera` (
  `id_configuracion` int(11) NOT NULL,
  `dia_vencimiento` tinyint(2) NOT NULL DEFAULT 10,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_financiera`
--

CREATE TABLE `configuracion_financiera` (
  `id_configuracion` int(11) NOT NULL,
  `dia_inicio_cierre` tinyint(4) NOT NULL DEFAULT 1,
  `dia_fin_cierre` tinyint(4) NOT NULL DEFAULT 7,
  `dia_facturacion` tinyint(4) NOT NULL DEFAULT 15,
  `dia_vencimiento` tinyint(4) NOT NULL DEFAULT 30,
  `generar_intereses` tinyint(1) NOT NULL DEFAULT 1,
  `periodo_interes` enum('MENSUAL') NOT NULL DEFAULT 'MENSUAL',
  `id_tasa_interes` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion_financiera`
--

INSERT INTO `configuracion_financiera` (`id_configuracion`, `dia_inicio_cierre`, `dia_fin_cierre`, `dia_facturacion`, `dia_vencimiento`, `generar_intereses`, `periodo_interes`, `id_tasa_interes`, `activo`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 7, 15, 30, 1, 'MENSUAL', NULL, 1, 'Configuración financiera inicial', '2026-08-22 20:21:40', '2026-08-22 20:21:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_mora`
--

CREATE TABLE `configuracion_mora` (
  `id_configuracion_mora` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo_tasa` enum('PORCENTAJE','VALOR_FIJO') NOT NULL DEFAULT 'PORCENTAJE',
  `tasa` decimal(15,6) NOT NULL DEFAULT 0.000000,
  `periodicidad` enum('DIARIA','MENSUAL') NOT NULL DEFAULT 'MENSUAL',
  `dias_gracia` int(11) NOT NULL DEFAULT 0,
  `aplicar_desde` enum('DIA_SIGUIENTE_VENCIMIENTO','DESPUES_DIAS_GRACIA') NOT NULL DEFAULT 'DIA_SIGUIENTE_VENCIMIENTO',
  `id_concepto` int(11) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_pagos`
--

CREATE TABLE `configuracion_pagos` (
  `id_configuracion` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `id_tipo_obligacion` int(11) NOT NULL,
  `prioridad` int(11) NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion_pagos`
--

INSERT INTO `configuracion_pagos` (`id_configuracion`, `id_unidad`, `id_tipo_obligacion`, `prioridad`, `activo`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 3, 2, 1, 1, 'PRUEBA - Administración primero', '2026-08-22 18:39:05', '2026-08-22 18:39:05'),
(2, 3, 4, 2, 1, 'PRUEBA - Parqueadero segundo', '2026-08-22 18:39:05', '2026-08-22 18:39:05'),
(3, 3, 6, 3, 1, 'PRUEBA - Multa tercero', '2026-08-22 18:39:05', '2026-08-22 18:39:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_bancarias`
--

CREATE TABLE `cuentas_bancarias` (
  `id_cuenta_bancaria` int(11) NOT NULL,
  `banco` varchar(100) NOT NULL,
  `tipo_cuenta` enum('AHORROS','CORRIENTE') NOT NULL,
  `numero_cuenta` varchar(50) NOT NULL,
  `titular` varchar(150) DEFAULT NULL,
  `nit_titular` varchar(30) DEFAULT NULL,
  `moneda` varchar(10) NOT NULL DEFAULT 'COP',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cuentas_bancarias`
--

INSERT INTO `cuentas_bancarias` (`id_cuenta_bancaria`, `banco`, `tipo_cuenta`, `numero_cuenta`, `titular`, `nit_titular`, `moneda`, `estado`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Bancolombia', 'AHORROS', '00012345678', 'Conjunto Residencial Los Pinos', '900123456-7', 'COP', 1, 'Cuenta principal para recaudo de administración', '2026-08-20 01:08:53', '2026-08-20 01:08:53'),
(2, 'Davivienda', 'CORRIENTE', '000987654321', 'Conjunto Residencial Los Pinos', '900123456-7', 'COP', 1, 'Cuenta corriente para pagos y gastos administrativos', '2026-08-20 01:08:53', '2026-08-20 01:08:53'),
(3, 'Banco de Bogotá', 'AHORROS', '001234567890', 'Conjunto Residencial Los Pinos', '900123456-7', 'COP', 1, 'Cuenta destinada a reservas', '2026-08-20 01:08:53', '2026-08-20 01:08:53'),
(4, 'BBVA Colombia', 'CORRIENTE', '002345678901', 'Conjunto Residencial Los Pinos', '900123456-7', 'COP', 1, 'Cuenta para operaciones administrativas', '2026-08-20 01:08:53', '2026-08-20 01:08:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_contables`
--

CREATE TABLE `cuentas_contables` (
  `id_cuenta_contable` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo` enum('INGRESO','ACTIVO','PASIVO','GASTO','PATRIMONIO','OTRO') NOT NULL DEFAULT 'INGRESO',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cuentas_contables`
--

INSERT INTO `cuentas_contables` (`id_cuenta_contable`, `codigo`, `nombre`, `descripcion`, `tipo`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, '4135', 'Ingresos por cuotas de administración', 'Ingresos generados por cuotas ordinarias de administración', 'INGRESO', 1, '2026-08-19 02:53:04', '2026-08-19 02:53:04'),
(2, '4140', 'Ingresos por parqueaderos', 'Ingresos generados por cobro de parqueaderos', 'INGRESO', 1, '2026-08-19 02:53:04', '2026-08-19 02:53:04'),
(3, '4150', 'Ingresos por cuotas extraordinarias', 'Ingresos generados por cuotas extraordinarias', 'INGRESO', 1, '2026-08-19 02:53:04', '2026-08-19 02:53:04'),
(4, '4160', 'Ingresos por intereses de mora', 'Intereses generados por obligaciones vencidas', 'INGRESO', 1, '2026-08-19 02:53:04', '2026-08-19 02:53:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datos_unidad`
--

CREATE TABLE `datos_unidad` (
  `id` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `es_actual` tinyint(1) NOT NULL DEFAULT 1,
  `nombre` varchar(150) NOT NULL,
  `nit` varchar(20) DEFAULT NULL,
  `representante_legal` varchar(120) DEFAULT NULL,
  `correo` varchar(120) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `id_pais` int(11) DEFAULT NULL,
  `id_departamento` int(11) DEFAULT NULL,
  `id_ciudad` int(11) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `sector` varchar(150) DEFAULT NULL,
  `id_tipo_copropiedad` int(11) DEFAULT NULL,
  `cantidad_unidades` int(11) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `reglamento` varchar(255) DEFAULT NULL,
  `manual` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `datos_unidad`
--

INSERT INTO `datos_unidad` (`id`, `version`, `es_actual`, `nombre`, `nit`, `representante_legal`, `correo`, `telefono`, `id_pais`, `id_departamento`, `id_ciudad`, `direccion`, `sector`, `id_tipo_copropiedad`, `cantidad_unidades`, `logo`, `reglamento`, `manual`, `fecha_creacion`, `fecha_actualizacion`, `activo`) VALUES
(12, 1, 0, 'Sierra Campestre P.H.', '987654231-8', 'David Upegui', 'correo@sierra.com', '1234657890', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'logo_1786290150_6a789fe6cc93a.jpeg', 'reglamento_1786292100_6a78a7846196f.pdf', 'manual_1786292124_6a78a79c2b8db.pdf', '2026-08-09 16:15:24', '2026-08-09 16:51:42', 1),
(13, 2, 0, 'Sierra Campestre P.H.', '987654231-8', 'David Upegui', 'correo@sierra.com', '1234657890', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'logo_1786290150_6a789fe6cc93a.jpeg', 'reglamento_1786292100_6a78a7846196f.pdf', 'manual_1786292124_6a78a79c2b8db.pdf', '2026-08-09 16:51:42', '2026-08-09 16:54:01', 1),
(14, 3, 0, 'Sierra Campestre P.H.', '987654231-8', 'David Upegui', 'correo@sierra.com', '1234657890', 1, 8, 19, 'Calle 54a sur N 54 e 03', 'Prado', NULL, NULL, 'logo_1786290150_6a789fe6cc93a.jpeg', 'reglamento_1786292100_6a78a7846196f.pdf', 'manual_1786292124_6a78a79c2b8db.pdf', '2026-08-09 16:54:01', '2026-08-09 21:12:52', 1),
(15, 4, 0, 'Sierra Campestre P.H.', '987654231-8', 'David Upegui', 'correo@sierra.com', '1234657890', 1, 11, 20, 'Calle 54a sur N 54 e 03', 'Prado', NULL, NULL, 'logo_1786290150_6a789fe6cc93a.jpeg', 'reglamento_1786292100_6a78a7846196f.pdf', 'manual_1786292124_6a78a79c2b8db.pdf', '2026-08-09 21:12:52', '2026-08-09 21:13:03', 1),
(16, 5, 0, 'Sierra Campestre P.H.', '987654231-8', 'David Upegui', 'correo@sierra.com', '1234657890', 1, 41, 18, 'Calle 54a sur N 54 e 03', 'Prado', NULL, NULL, 'logo_1786290150_6a789fe6cc93a.jpeg', 'reglamento_1786292100_6a78a7846196f.pdf', 'manual_1786292124_6a78a79c2b8db.pdf', '2026-08-09 21:13:03', '2026-08-09 21:13:22', 1),
(17, 6, 0, 'Sierra Campestre P.H.', '987654231-8', 'David Upegui', 'correo@sierra.com', '1234657890', 1, 41, 18, 'Calle 54a sur N 54 e 03', 'San Antonio de Prado', NULL, NULL, 'logo_1786290150_6a789fe6cc93a.jpeg', 'reglamento_1786292100_6a78a7846196f.pdf', 'manual_1786292124_6a78a79c2b8db.pdf', '2026-08-09 21:13:22', '2026-08-14 23:23:05', 1),
(18, 7, 0, 'Sierra Campestre P.H.', '987654231', 'David Upegui', 'correo@sierra.com', '1234657890', 1, 41, 18, 'Calle 54a sur N 54 e 03', 'San Antonio de Prado', NULL, NULL, 'logo_1786290150_6a789fe6cc93a.jpeg', 'reglamento_1786292100_6a78a7846196f.pdf', 'manual_1786292124_6a78a79c2b8db.pdf', '2026-08-14 23:23:05', '2026-08-18 01:52:28', 1),
(19, 8, 0, 'Sierra Campestre P.H.', '987654231', 'David Upegui', 'correo@sierra.com', '1234657890', 1, 41, 18, 'Calle 54a sur N 54 e 03', 'San Antonio de Prado', 5, 200, 'logo_1786290150_6a789fe6cc93a.jpeg', 'reglamento_1786292100_6a78a7846196f.pdf', 'manual_1786292124_6a78a79c2b8db.pdf', '2026-08-18 01:52:28', '2026-08-18 03:06:44', 1),
(20, 9, 1, 'Sierra Campestre P.H.', '987654231', 'David Upegui', 'correo@sierra.com', '1234657890', 1, 8, 18, 'Calle 54a sur N 54 e 03', 'San Antonio de Prado', 5, 200, 'logo_1786290150_6a789fe6cc93a.jpeg', 'reglamento_1786292100_6a78a7846196f.pdf', 'manual_1786292124_6a78a79c2b8db.pdf', '2026-08-18 03:06:44', '2026-08-18 03:06:44', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id_departamento` int(11) NOT NULL,
  `id_pais` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  `Activo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id_departamento`, `id_pais`, `nombre`, `codigo`, `Activo`) VALUES
(8, 1, 'Antioquia', '05', 1),
(10, 1, 'Amazonas', '91', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_tipos_unidad`
--

CREATE TABLE `detalle_tipos_unidad` (
  `id_tipo_config` int(11) NOT NULL,
  `id_tipo_vivienda` int(11) NOT NULL,
  `nombre_grupo` varchar(120) NOT NULL,
  `cantidad_unidades` int(11) NOT NULL DEFAULT 0,
  `area_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `coeficiente_total` decimal(8,5) NOT NULL DEFAULT 0.00000,
  `activo` tinyint(1) DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_tipos_unidad`
--

INSERT INTO `detalle_tipos_unidad` (`id_tipo_config`, `id_tipo_vivienda`, `nombre_grupo`, `cantidad_unidades`, `area_total`, `coeficiente_total`, `activo`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 'Torre A', 10, 200.00, 30.00000, 1, '', '2026-07-03 06:09:09', '2026-07-03 06:09:09'),
(6, 11, 'Torre B', 100, 70.00, 3.00000, 1, '', '2026-08-09 23:32:03', '2026-08-09 23:32:03'),
(7, 2, 'Torre C', 40, 65.00, 0.50000, 1, '', '2026-08-12 03:36:15', '2026-08-18 02:13:16'),
(8, 5, 'Oficinas comerciales', 70, 20.00, 0.03000, 1, '', '2026-08-13 01:16:58', '2026-08-13 01:16:58'),
(12, 10, 'Penhouse', 5, 100.00, 0.50000, 1, '', '2026-08-18 00:11:39', '2026-08-18 00:11:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_bancarios`
--

CREATE TABLE `documentos_bancarios` (
  `id_documento` int(11) NOT NULL,
  `id_cuenta_bancaria` int(11) DEFAULT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `nombre_original` varchar(255) DEFAULT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tipo_archivo` varchar(50) DEFAULT NULL,
  `hash_archivo` varchar(128) DEFAULT NULL,
  `metodo_extraccion` enum('TEXTO','OCR','MANUAL') NOT NULL DEFAULT 'MANUAL',
  `estado_procesamiento` enum('PENDIENTE','PROCESANDO','PROCESADO','ERROR','REVISADO') NOT NULL DEFAULT 'PENDIENTE',
  `texto_extraido` longtext DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_procesamiento` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `documentos_bancarios`
--

INSERT INTO `documentos_bancarios` (`id_documento`, `id_cuenta_bancaria`, `nombre_archivo`, `nombre_original`, `ruta_archivo`, `tipo_archivo`, `hash_archivo`, `metodo_extraccion`, `estado_procesamiento`, `texto_extraido`, `observaciones`, `fecha_procesamiento`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 2, '20260820_030911_469565acaea666f9_1.pdf', 'Extracto (5).pdf', 'uploads/documentos_bancarios/20260820_030911_469565acaea666f9_1.pdf', 'application/pdf', '3c5f168989ec1739b353365ae110d720c66959f5a1657c5bc4840eb00c2c6345', '', 'PROCESADO', NULL, 'Movimientos confirmados y guardados correctamente: 21', '2026-08-21 22:32:05', '2026-08-20 01:09:11', '2026-08-22 03:32:05'),
(2, 1, '20260820_034507_af7d41359d2ab8e8.pdf', 'Extracto (6).pdf', 'uploads/documentos_bancarios/20260820_034507_af7d41359d2ab8e8.pdf', 'application/pdf', 'a645c136f02e13a4b3c740caa5c6e441a342dfdf8f2d1d05ce36f29383946c6b', '', 'PROCESADO', NULL, 'Movimientos confirmados y guardados correctamente: 17', '2026-08-21 22:21:28', '2026-08-20 01:45:07', '2026-08-22 03:21:28'),
(3, 2, '20260820_062941_662485c64312e343.pdf', '004 SST ADSO - Jerarquía de controles Mi 6pm.pdf', 'uploads/documentos_bancarios/20260820_062941_662485c64312e343.pdf', 'application/pdf', '12399cc54f86c18c141608565325c5226db216a30f8583df71288b283e25a153', 'MANUAL', 'ERROR', NULL, 'El OCR fue realizado correctamente, pero no se encontraron movimientos bancarios con el formato esperado.', '2026-08-21 22:40:29', '2026-08-20 04:29:41', '2026-08-22 03:40:29'),
(4, 1, '20260820_065549_d2f743712d320586.pdf', 'Extracto (7).pdf', 'uploads/documentos_bancarios/20260820_065549_d2f743712d320586.pdf', 'application/pdf', '992dc9a611a249610295de2200f285bc93f6dbecdcb06e24acbb2e15fc0060ce', '', 'PROCESADO', NULL, 'Movimientos confirmados y guardados correctamente: 26', '2026-08-21 22:41:28', '2026-08-20 04:55:49', '2026-08-22 03:41:28'),
(5, 2, '20260822_034052_86e4fc2f81437c46.pdf', 'Extracto (8).pdf', 'uploads/documentos_bancarios/20260822_034052_86e4fc2f81437c46.pdf', 'application/pdf', '399a754588ac1962deba45e516db235f5bf46e1443434d51c4aee5bd65d83947', '', 'PROCESADO', NULL, 'OCR realizado correctamente. Páginas: 1. Palabras detectadas: 275.', '2026-08-21 20:41:11', '2026-08-22 01:40:52', '2026-08-22 01:41:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `espacios_unidad`
--

CREATE TABLE `espacios_unidad` (
  `id` int(11) NOT NULL,
  `conjunto_id` int(11) NOT NULL,
  `torre` varchar(10) DEFAULT NULL,
  `numero` varchar(20) NOT NULL,
  `metros_cuadrados` decimal(10,2) DEFAULT NULL,
  `propietario_id` int(11) DEFAULT NULL,
  `residente_id` int(11) DEFAULT NULL,
  `estado` enum('ocupado','desocupado') DEFAULT 'ocupado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_civiles`
--

CREATE TABLE `estados_civiles` (
  `id_estado_civil` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estados_civiles`
--

INSERT INTO `estados_civiles` (`id_estado_civil`, `nombre`, `estado`) VALUES
(1, 'Soltero', 1),
(2, 'Casado', 1),
(3, 'Unión Libre', 1),
(4, 'Divorciado', 1),
(5, 'Viudo', 1),
(8, 'Otro', 1),
(9, 'Solitario', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `extractos_bancarios`
--

CREATE TABLE `extractos_bancarios` (
  `id_extracto` int(11) NOT NULL,
  `id_documento` int(11) DEFAULT NULL,
  `id_cuenta_bancaria` int(11) DEFAULT NULL,
  `fecha_movimiento` date NOT NULL,
  `fecha_valor` date DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `referencia` varchar(150) DEFAULT NULL,
  `numero_documento` varchar(100) DEFAULT NULL,
  `valor` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tipo_movimiento` enum('INGRESO','EGRESO') NOT NULL DEFAULT 'INGRESO',
  `estado_conciliacion` enum('PENDIENTE','CONCILIADO','RECHAZADO','CON_DIFERENCIA') NOT NULL DEFAULT 'PENDIENTE',
  `archivo_origen` varchar(255) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `extractos_bancarios`
--

INSERT INTO `extractos_bancarios` (`id_extracto`, `id_documento`, `id_cuenta_bancaria`, `fecha_movimiento`, `fecha_valor`, `descripcion`, `referencia`, `numero_documento`, `valor`, `tipo_movimiento`, `estado_conciliacion`, `archivo_origen`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 2, 1, '2026-06-22', '2026-06-22', 'Transferencia BANCOLOMBIA 890924789 Pago A556140 ACRECER SAS                                       PROCESOS ACH', '0485', '0485', 740234.00, 'INGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(2, 2, 1, '2026-06-22', '2026-06-22', 'Transferencia BANCOLOMBIA 890924789 Pago A556139 ACRECER SAS                                       PROCESOS ACH', '7019', '7019', 740234.00, 'INGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(3, 2, 1, '2026-06-22', '2026-06-22', 'Compra NU Companía de Financia                                                                     Compras y Pagos PSE', '1705', '1705', 1013690.05, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(4, 2, 1, '2026-06-22', '2026-06-22', 'Transferencia A Llave Otra Entidad                                                                 Redeban BreB', '3604', '3604', 36000.00, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(5, 2, 1, '2026-06-22', '2026-06-22', 'Transferencia A Llave Otra Entidad                                                                 Redeban BreB', '8725', '8725', 56450.00, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(6, 2, 1, '2026-06-23', '2026-06-23', 'Compra WOMPI S.A.S                                                                                 Compras y Pagos PSE', '0293', '0293', 83000.00, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(7, 2, 1, '2026-06-24', '2026-06-24', 'Transferencia A Llave Otra Entidad                                                                 Redeban BreB', '1854', '1854', 30000.00, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(8, 2, 1, '2026-06-24', '2026-06-24', 'Transferencia A Llave Otra Entidad                                                                 Redeban BreB', '8915', '8915', 20000.00, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(9, 2, 1, '2026-06-25', '2026-06-25', 'Transferencia A Llave Otra Entidad                                                                 Redeban BreB', '0814', '0814', 30000.00, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(10, 2, 1, '2026-06-26', '2026-06-26', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '8236', '8236', 17400.00, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(11, 2, 1, '2026-06-27', '2026-06-27', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '3467', '3467', 51000.00, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(12, 2, 1, '2026-06-27', '2026-06-27', 'Compra TIENDA D1 BODEGA ITAGU                                                                      FRANQUICIA MASTER CARD', '4501', '4501', 15300.00, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(13, 2, 1, '2026-06-29', '2026-06-29', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '9065', '9065', 38000.00, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(14, 2, 1, '2026-06-29', '2026-06-29', 'Abono por avance tarjeta de credito           App Davivienda', '4275', '4275', 500000.00, 'INGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(15, 2, 1, '2026-06-29', '2026-06-29', 'Compra URBANIZACION SIERRA CAM                Compras y Pagos PSE', '5583', '5583', 580000.00, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(16, 2, 1, '2026-06-30', '2026-06-30', 'Rendimientos Financieros.                     0000', '0000', '0000', 4.20, 'INGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(17, 2, 1, '2026-06-30', '2026-06-30', 'Gravamen a los Movimientos Financieros        0000', '0000', '0000', 7883.36, 'EGRESO', 'PENDIENTE', 'Extracto (6).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:21:28', '2026-08-22 03:21:28'),
(18, 4, 1, '2026-01-09', '2026-01-09', 'Pago Tarj. Credito N0032060144423171                                                               App Davivienda', '3350', '3350', 186.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(19, 4, 1, '2026-01-10', '2026-01-10', 'Compra FRISBY Q58                                                                                  FRANQUICIA MASTER CARD', '2230', '2230', 47100.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(20, 4, 1, '2026-01-10', '2026-01-10', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '3235', '3235', 19900.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(21, 4, 1, '2026-01-15', '2026-01-15', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '4123', '4123', 9000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(22, 4, 1, '2026-01-16', '2026-01-16', 'Compra UNE - EPM Telecomunicac                                                                     Compras y Pagos PSE', '9398', '9398', 55088.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(23, 4, 1, '2026-01-16', '2026-01-16', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '8212', '8212', 9000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(24, 4, 1, '2026-01-17', '2026-01-17', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '3507', '3507', 11600.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(25, 4, 1, '2026-01-17', '2026-01-17', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '2621', '2621', 52900.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(26, 4, 1, '2026-01-17', '2026-01-17', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '0860', '0860', 21000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(27, 4, 1, '2026-01-18', '2026-01-18', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '6060', '6060', 71000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(28, 4, 1, '2026-01-21', '2026-01-21', 'Transferencia BANCOLOMBIA 890924789 Pago A504830 ACRECER SAS                                       PROCESOS ACH', '4985', '4985', 711903.00, 'INGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(29, 4, 1, '2026-01-21', '2026-01-21', 'Transferencia BANCOLOMBIA 890924789 Pago A504829 ACRECER SAS                                       PROCESOS ACH', '4986', '4986', 711903.00, 'INGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(30, 4, 1, '2026-01-21', '2026-01-21', 'Compra NU Companía de Financia                                                                     Compras y Pagos PSE', '1310', '1310', 1068760.47, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(31, 4, 1, '2026-01-22', '2026-01-22', 'Compra A Comercio Llave Otra Entidad          Redeban BreB', '1943', '1943', 9000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(32, 4, 1, '2026-01-22', '2026-01-22', 'Transferencia A Llave Otra Entidad            Redeban BreB', '2215', '2215', 50000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(33, 4, 1, '2026-01-22', '2026-01-22', 'Transferencia A Llave Otra Entidad            Redeban BreB', '9401', '9401', 100000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(34, 4, 1, '2026-01-23', '2026-01-23', 'Compra A Comercio Llave Otra Entidad          Redeban BreB', '2913', '2913', 30000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(35, 4, 1, '2026-01-23', '2026-01-23', 'Compra A Comercio Llave Otra Entidad          Redeban BreB', '3327', '3327', 31900.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(36, 4, 1, '2026-01-23', '2026-01-23', 'Compra PIZZA HUT                              FRANQUICIA MASTER CARD', '9722', '9722', 36900.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(37, 4, 1, '2026-01-24', '2026-01-24', 'Retiro en Cajero Automatico.                  ENVIGADO', '2749', '2749', 120000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(38, 4, 1, '2026-01-24', '2026-01-24', 'Compra TECNIPAGOS S A                         Compras y Pagos PSE', '0091', '0091', 70000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(39, 4, 1, '2026-01-27', '2026-01-27', 'Transferencia A Llave Otra Entidad            Redeban BreB', '9461', '9461', 7000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(40, 4, 1, '2026-01-30', '2026-01-30', 'Abono Uso Adelanto De Nomina                  App Davivienda', '1055', '1055', 100000.00, 'INGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(41, 4, 1, '2026-01-30', '2026-01-30', 'Transferencia A Llave Otra Entidad            Redeban BreB', '7568', '7568', 7000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(42, 4, 1, '2026-01-31', '2026-01-31', 'Rendimientos Financieros.                     0000', '0000', '0000', 20.73, 'INGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(43, 4, 1, '2026-01-31', '2026-01-31', 'Gravamen a los Movimientos Financieros        0000', '0000', '0000', 7309.33, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:29:25', '2026-08-22 03:29:25'),
(44, 1, 2, '2026-07-12', '2026-07-12', 'Abono Entidades Financieras Desde Pse                                                              App Transaccional', '4812', '4812', 1500000.00, 'INGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(45, 1, 2, '2026-07-12', '2026-07-12', 'Pago Credito Nro. 5703391900061914                                                                 www.davivienda.com', '2435', '2435', 1495800.00, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(46, 1, 2, '2026-07-16', '2026-07-16', 'Abono por avance tarjeta de credito                                                                App Davivienda', '5924', '5924', 800000.00, 'INGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(47, 1, 2, '2026-07-16', '2026-07-16', 'Pago Credito Nro. 6600323018849882                                                                 App Davivienda', '3642', '3642', 796000.00, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(48, 1, 2, '2026-07-22', '2026-07-22', 'Transferencia BANCOLOMBIA 890924789 Pago A566382 ACRECER SAS                                       PROCESOS ACH', '0070', '0070', 738734.00, 'INGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(49, 1, 2, '2026-07-22', '2026-07-22', 'Transferencia BANCOLOMBIA 890924789 Pago A566381 ACRECER SAS                                       PROCESOS ACH', '0152', '0152', 738734.00, 'INGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(50, 1, 2, '2026-07-22', '2026-07-22', 'Pago Credito Nro. 6600323018849882                                                                 App Davivienda', '0100', '0100', 7796.00, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(51, 1, 2, '2026-07-22', '2026-07-22', 'Pago Tarj. Credito N0032060144423171                                                               App Davivienda', '0349', '0349', 100000.00, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(52, 1, 2, '2026-07-22', '2026-07-22', 'Compra NU Companía de Financia                                                                     Compras y Pagos PSE', '9317', '9317', 1094468.19, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(53, 1, 2, '2026-07-22', '2026-07-22', 'Transferencia De Otra Entidad A Llave                                                              Redeban BreB', '8369', '8369', 40000.00, 'INGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(54, 1, 2, '2026-07-23', '2026-07-23', 'Transferencia A Llave Otra Entidad                                                                 Redeban BreB', '7815', '7815', 20000.00, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(55, 1, 2, '2026-07-24', '2026-07-24', 'Compra BANCOLOMBIA                                                                                 Compras y Pagos PSE', '1598', '1598', 20000.00, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(56, 1, 2, '2026-07-25', '2026-07-25', 'Transferencia A Llave Otra Entidad                                                                 Redeban BreB', '1190', '1190', 178600.00, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(57, 1, 2, '2026-07-26', '2026-07-26', 'Compra LA MIGUERIA                            FRANQUICIA MASTER CARD', '0245', '0245', 38700.00, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(58, 1, 2, '2026-07-27', '2026-07-27', 'Compra A Comercio Llave Otra Entidad          Redeban BreB', '4984', '4984', 13500.00, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(59, 1, 2, '2026-07-27', '2026-07-27', 'Transferencia De Otra Entidad A Llave         Redeban BreB', '2049', '2049', 15000.00, 'INGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(60, 1, 2, '2026-07-27', '2026-07-27', 'Transferencia De Otra Entidad A Llave         Redeban BreB', '8617', '8617', 30000.00, 'INGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(61, 1, 2, '2026-07-27', '2026-07-27', 'Compra WOMPI S.A.S                            Compras y Pagos PSE', '1964', '1964', 80000.00, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(62, 1, 2, '2026-07-29', '2026-07-29', 'Transferencia A Llave Otra Entidad            Redeban BreB', '1935', '1935', 3900.00, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(63, 1, 2, '2026-07-31', '2026-07-31', 'Rendimientos Financieros.                     0000', '0000', '0000', 2.88, 'INGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(64, 1, 2, '2026-07-31', '2026-07-31', 'Gravamen a los Movimientos Financieros        0000', '0000', '0000', 15395.05, 'EGRESO', 'PENDIENTE', 'Extracto (5).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:32:05', '2026-08-22 03:32:05'),
(65, 4, 1, '2026-01-09', '2026-01-09', 'Pago Tarj. Credito N0032060144423171                                                               App Davivienda', '3350', '3350', 186.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(66, 4, 1, '2026-01-10', '2026-01-10', 'Compra FRISBY Q58                                                                                  FRANQUICIA MASTER CARD', '2230', '2230', 47100.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(67, 4, 1, '2026-01-10', '2026-01-10', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '3235', '3235', 19900.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(68, 4, 1, '2026-01-15', '2026-01-15', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '4123', '4123', 9000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(69, 4, 1, '2026-01-16', '2026-01-16', 'Compra UNE - EPM Telecomunicac                                                                     Compras y Pagos PSE', '9398', '9398', 55088.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(70, 4, 1, '2026-01-16', '2026-01-16', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '8212', '8212', 9000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(71, 4, 1, '2026-01-17', '2026-01-17', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '3507', '3507', 11600.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(72, 4, 1, '2026-01-17', '2026-01-17', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '2621', '2621', 52900.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(73, 4, 1, '2026-01-17', '2026-01-17', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '0860', '0860', 21000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(74, 4, 1, '2026-01-18', '2026-01-18', 'Compra A Comercio Llave Otra Entidad                                                               Redeban BreB', '6060', '6060', 71000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(75, 4, 1, '2026-01-21', '2026-01-21', 'Transferencia BANCOLOMBIA 890924789 Pago A504830 ACRECER SAS                                       PROCESOS ACH', '4985', '4985', 711903.00, 'INGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(76, 4, 1, '2026-01-21', '2026-01-21', 'Transferencia BANCOLOMBIA 890924789 Pago A504829 ACRECER SAS                                       PROCESOS ACH', '4986', '4986', 711903.00, 'INGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(77, 4, 1, '2026-01-21', '2026-01-21', 'Compra NU Companía de Financia                                                                     Compras y Pagos PSE', '1310', '1310', 1068760.47, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(78, 4, 1, '2026-01-22', '2026-01-22', 'Compra A Comercio Llave Otra Entidad          Redeban BreB', '1943', '1943', 9000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(79, 4, 1, '2026-01-22', '2026-01-22', 'Transferencia A Llave Otra Entidad            Redeban BreB', '2215', '2215', 50000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(80, 4, 1, '2026-01-22', '2026-01-22', 'Transferencia A Llave Otra Entidad            Redeban BreB', '9401', '9401', 100000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(81, 4, 1, '2026-01-23', '2026-01-23', 'Compra A Comercio Llave Otra Entidad          Redeban BreB', '2913', '2913', 30000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(82, 4, 1, '2026-01-23', '2026-01-23', 'Compra A Comercio Llave Otra Entidad          Redeban BreB', '3327', '3327', 31900.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(83, 4, 1, '2026-01-23', '2026-01-23', 'Compra PIZZA HUT                              FRANQUICIA MASTER CARD', '9722', '9722', 36900.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(84, 4, 1, '2026-01-24', '2026-01-24', 'Retiro en Cajero Automatico.                  ENVIGADO', '2749', '2749', 120000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(85, 4, 1, '2026-01-24', '2026-01-24', 'Compra TECNIPAGOS S A                         Compras y Pagos PSE', '0091', '0091', 70000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(86, 4, 1, '2026-01-27', '2026-01-27', 'Transferencia A Llave Otra Entidad            Redeban BreB', '9461', '9461', 7000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(87, 4, 1, '2026-01-30', '2026-01-30', 'Abono Uso Adelanto De Nomina                  App Davivienda', '1055', '1055', 100000.00, 'INGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(88, 4, 1, '2026-01-30', '2026-01-30', 'Transferencia A Llave Otra Entidad            Redeban BreB', '7568', '7568', 7000.00, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(89, 4, 1, '2026-01-31', '2026-01-31', 'Rendimientos Financieros.                     0000', '0000', '0000', 20.73, 'INGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28'),
(90, 4, 1, '2026-01-31', '2026-01-31', 'Gravamen a los Movimientos Financieros        0000', '0000', '0000', 7309.33, 'EGRESO', 'PENDIENTE', 'Extracto (7).pdf', 'Movimiento importado desde extracto bancario.', '2026-08-22 03:41:28', '2026-08-22 03:41:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id_factura` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `numero_factura` varchar(50) DEFAULT NULL,
  `periodo` year(4) NOT NULL,
  `mes` tinyint(2) NOT NULL,
  `fecha_generacion` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `intereses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `saldos_anteriores` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estado` enum('BORRADOR','GENERADA','PARCIAL','PAGADA','VENCIDA','ANULADA') NOT NULL DEFAULT 'GENERADA',
  `observaciones` varchar(500) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas_detalle`
--

CREATE TABLE `facturas_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_factura` int(11) NOT NULL,
  `id_concepto` int(11) NOT NULL,
  `id_tarifa` int(11) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(15,4) NOT NULL DEFAULT 1.0000,
  `valor_unitario` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tipo_calculo` enum('FIJO','METRO_CUADRADO','COEFICIENTE','PORCENTAJE') NOT NULL,
  `base_calculo` decimal(15,4) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas_saldos`
--

CREATE TABLE `facturas_saldos` (
  `id_saldo` int(11) NOT NULL,
  `id_factura` int(11) NOT NULL,
  `id_factura_origen` int(11) NOT NULL,
  `saldo_inicial` decimal(15,2) NOT NULL DEFAULT 0.00,
  `intereses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `valor_aplicado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `saldo_pendiente` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estado` enum('PENDIENTE','PARCIAL','PAGADO') NOT NULL DEFAULT 'PENDIENTE',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `generos`
--

CREATE TABLE `generos` (
  `id_genero` int(11) NOT NULL,
  `codigo` varchar(5) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `generos`
--

INSERT INTO `generos` (`id_genero`, `codigo`, `nombre`, `estado`) VALUES
(1, 'M', 'Masculino', 1),
(2, 'F', 'Femenino', 1),
(3, 'O', 'Otro', 1),
(4, 'N', 'Prefiero no informar', 1),
(5, '', 'Binario', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intereses_cartera`
--

CREATE TABLE `intereses_cartera` (
  `id_interes` int(11) NOT NULL,
  `id_cartera` int(11) NOT NULL,
  `id_tasa_interes` int(11) DEFAULT NULL,
  `periodo_interes` date NOT NULL,
  `fecha_calculo` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `dias_mora` int(11) NOT NULL DEFAULT 0,
  `tasa_interes` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `valor_base` decimal(15,2) NOT NULL DEFAULT 0.00,
  `valor_interes` decimal(15,2) NOT NULL DEFAULT 0.00,
  `valor_pagado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `saldo` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estado` enum('PENDIENTE','PAGADO','ANULADO') NOT NULL DEFAULT 'PENDIENTE',
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento`
--

CREATE TABLE `mantenimiento` (
  `id` int(11) NOT NULL,
  `zona_id` int(11) NOT NULL,
  `usuario_reporta_id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `prioridad` enum('baja','media','alta','critica') DEFAULT 'media',
  `responsable` varchar(150) DEFAULT NULL,
  `fecha_reporte` datetime DEFAULT current_timestamp(),
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_solucion` datetime DEFAULT NULL,
  `estado` enum('pendiente','en_proceso','solucionado') DEFAULT 'pendiente',
  `costo` decimal(12,2) DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `evidencia` varchar(255) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mantenimiento`
--

INSERT INTO `mantenimiento` (`id`, `zona_id`, `usuario_reporta_id`, `descripcion`, `prioridad`, `responsable`, `fecha_reporte`, `fecha_inicio`, `fecha_solucion`, `estado`, `costo`, `comentarios`, `evidencia`, `fecha_actualizacion`) VALUES
(1, 1, 2, 'Fuga de agua en el baño principal del segundo piso.', 'alta', 'Juan Pérez (Plomero)', '2026-05-31 19:08:03', '2026-06-01 08:00:00', '2026-06-15 18:31:00', 'pendiente', 150.00, 'Se requiere cambiar la tubería principal de PVC.', 'foto_evidencia_01.jpg', '2026-06-15 18:31:12'),
(2, 1, 2, 'Prueba', 'baja', NULL, '2026-06-17 19:31:54', NULL, '2026-06-17 19:31:00', 'solucionado', NULL, NULL, 'uploads/1781742714_user.png', '2026-06-18 18:50:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id_movimiento` int(11) NOT NULL,
  `id_articulo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_movimiento` enum('entrada','salida') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `fecha_movimiento` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id_movimiento`, `id_articulo`, `id_usuario`, `tipo_movimiento`, `cantidad`, `nota`, `fecha_movimiento`) VALUES
(1, 1, 7, 'entrada', 10, 'compra', '2026-08-24 21:03:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `obligaciones`
--

CREATE TABLE `obligaciones` (
  `id_obligacion` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `id_tipo_obligacion` int(11) NOT NULL,
  `periodo` date NOT NULL,
  `fecha_generacion` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `valor` decimal(15,2) NOT NULL DEFAULT 0.00,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('GENERADA','PAGADA','ANULADA') NOT NULL DEFAULT 'GENERADA',
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ocupaciones`
--

CREATE TABLE `ocupaciones` (
  `id_ocupacion` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ocupaciones`
--

INSERT INTO `ocupaciones` (`id_ocupacion`, `nombre`, `estado`) VALUES
(1, 'Empleado', 1),
(2, 'Independiente', 1),
(3, 'Estudiante', 1),
(4, 'Jubilado', 1),
(5, 'Ama de casa', 1),
(6, 'Ingeniero', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `id_extracto` int(11) DEFAULT NULL,
  `fecha_pago` date NOT NULL,
  `valor` decimal(15,2) NOT NULL DEFAULT 0.00,
  `medio_pago` enum('EFECTIVO','TRANSFERENCIA','CONSIGNACION','PSE','TARJETA','OTRO') NOT NULL DEFAULT 'TRANSFERENCIA',
  `origen_pago` enum('MANUAL','BANCO','PASARELA') NOT NULL DEFAULT 'MANUAL',
  `estado_conciliacion` enum('PENDIENTE','CONCILIADO','RECHAZADO','CON_DIFERENCIA') NOT NULL DEFAULT 'PENDIENTE',
  `fecha_conciliacion` datetime DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `referencia_externa` varchar(150) DEFAULT NULL,
  `id_externo` varchar(150) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `estado` enum('REGISTRADO','ANULADO') NOT NULL DEFAULT 'REGISTRADO',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_unidad`, `id_extracto`, `fecha_pago`, `valor`, `medio_pago`, `origen_pago`, `estado_conciliacion`, `fecha_conciliacion`, `referencia`, `referencia_externa`, `id_externo`, `observaciones`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, NULL, '2026-08-25', 200000.01, 'TRANSFERENCIA', 'MANUAL', 'PENDIENTE', NULL, 'asd22', NULL, NULL, NULL, 'REGISTRADO', '2026-08-25 02:40:26', '2026-08-25 02:40:26'),
(2, 1, NULL, '2026-08-25', 250000.01, 'TRANSFERENCIA', 'MANUAL', 'PENDIENTE', NULL, 'asd221', NULL, NULL, NULL, 'REGISTRADO', '2026-08-25 02:41:54', '2026-08-25 03:06:04'),
(3, 2, NULL, '2026-08-25', 300000.00, 'TRANSFERENCIA', 'MANUAL', 'PENDIENTE', NULL, 'XABS231231', NULL, NULL, NULL, 'REGISTRADO', '2026-08-25 02:42:15', '2026-08-25 02:42:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paises`
--

CREATE TABLE `paises` (
  `id_pais` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `Activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paises`
--

INSERT INTO `paises` (`id_pais`, `nombre`, `Activo`) VALUES
(1, 'Colombia', 1),
(21, 'Ecuador', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parentescos`
--

CREATE TABLE `parentescos` (
  `id_parentesco` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `parentescos`
--

INSERT INTO `parentescos` (`id_parentesco`, `nombre`, `estado`) VALUES
(1, 'Esposo(a)', 1),
(2, 'Hijo(a)', 1),
(3, 'Padre', 1),
(4, 'Madre', 1),
(5, 'Hermanoo', 1),
(6, 'Visitante', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pqrs`
--

CREATE TABLE `pqrs` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('peticion','queja','reclamo','sugerencia') NOT NULL,
  `asunto` varchar(100) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `estado` enum('pendiente','en_proceso','cerrado') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva_zona`
--

CREATE TABLE `reserva_zona` (
  `id` int(11) NOT NULL,
  `zona_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_reserva` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` enum('pendiente','aprobada','cancelada') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `residente`
--

CREATE TABLE `residente` (
  `id` int(11) NOT NULL,
  `unidad_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('propietario','inquilino','residente') NOT NULL,
  `recibe_factura` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_desde` datetime DEFAULT current_timestamp(),
  `fecha_hasta` datetime DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `residente`
--

INSERT INTO `residente` (`id`, `unidad_id`, `usuario_id`, `tipo`, `recibe_factura`, `fecha_desde`, `fecha_hasta`, `activo`) VALUES
(1, 1, 3, 'residente', 1, '2026-08-17 00:00:00', NULL, 1),
(2, 1, 4, 'residente', 0, '2026-08-18 00:00:00', NULL, 1),
(3, 1, 5, 'residente', 0, '2026-08-16 00:00:00', NULL, 1),
(4, 1, 6, 'propietario', 0, '2026-08-17 00:00:00', NULL, 1),
(5, 1, 7, 'propietario', 1, '2026-08-17 00:00:00', NULL, 1),
(6, 4, 28, 'propietario', 0, '2026-06-23 22:56:59', NULL, 1),
(7, 4, 33, 'inquilino', 0, '2026-06-23 22:56:59', NULL, 1),
(8, 5, 29, 'propietario', 0, '2026-06-23 22:56:59', NULL, 1),
(9, 5, 34, 'inquilino', 0, '2026-06-23 22:56:59', NULL, 1),
(10, 6, 30, 'propietario', 0, '2026-06-23 22:56:59', NULL, 1),
(11, 6, 35, 'inquilino', 0, '2026-06-23 22:56:59', NULL, 1),
(12, 9, 31, 'propietario', 0, '2026-06-23 22:56:59', NULL, 1),
(13, 1, 8, 'inquilino', 1, '2026-08-17 00:00:00', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id`, `nombre`) VALUES
(1, 'Administrador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `saldo_favor`
--

CREATE TABLE `saldo_favor` (
  `id_saldo_favor` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `id_pago` int(11) NOT NULL,
  `valor_original` decimal(15,2) NOT NULL DEFAULT 0.00,
  `valor_utilizado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `saldo_disponible` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estado` enum('DISPONIBLE','UTILIZADO','ANULADO') NOT NULL DEFAULT 'DISPONIBLE',
  `fecha_generacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_ultimo_uso` datetime DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarifas_facturacion`
--

CREATE TABLE `tarifas_facturacion` (
  `id_tarifa` int(11) NOT NULL,
  `id_concepto` int(11) NOT NULL,
  `id_tipo_config` int(11) NOT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `valor` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tarifas_facturacion`
--

INSERT INTO `tarifas_facturacion` (`id_tarifa`, `id_concepto`, `id_tipo_config`, `nombre`, `valor`, `fecha_inicio`, `fecha_fin`, `estado`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 1, 'Admin', 500000.00, '2026-08-18', '2026-08-31', 1, 'Por que si', '2026-08-19 04:11:42', '2026-08-19 23:11:05'),
(2, 3, 6, 'Arreglo Fachada', 300000.00, '2026-08-19', '2026-09-05', 1, 'Se cobra en una sola cuota', '2026-08-19 23:10:17', '2026-08-19 23:10:17'),
(3, 1, 1, 'Administración Septiembre 2026', 500000.00, '2026-09-01', NULL, 1, 'Tarifa mensual de administración', '2026-08-23 03:30:51', '2026-08-23 03:30:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasas_interes`
--

CREATE TABLE `tasas_interes` (
  `id_tasa_interes` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tasa_anual` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `tasa_mensual` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fuente` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tasas_interes`
--

INSERT INTO `tasas_interes` (`id_tasa_interes`, `nombre`, `tasa_anual`, `tasa_mensual`, `fecha_inicio`, `fecha_fin`, `fuente`, `activo`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Tasa de prueba febrero 2026', 24.000000, 2.000000, '2026-02-01', '2026-02-28', 'PRUEBA', 1, 'Registro temporal para pruebas del módulo de cartera', '2026-08-22 18:53:38', '2026-08-22 18:53:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_agrupacion`
--

CREATE TABLE `tipos_agrupacion` (
  `id_tipo_agrupacion` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_agrupacion`
--

INSERT INTO `tipos_agrupacion` (`id_tipo_agrupacion`, `nombre`, `descripcion`, `activo`, `fecha_creacion`) VALUES
(1, 'Torre', 'Agrupación vertical de unidades.', 1, '2026-08-10 22:39:27'),
(2, 'Bloque', 'Agrupación de unidades por bloques.', 1, '2026-08-10 22:39:27'),
(3, 'Manzana', 'Agrupación de viviendas por manzanas.', 1, '2026-08-10 22:39:27'),
(4, 'Etapa', 'Agrupación correspondiente a una etapa del proyecto.', 1, '2026-08-10 22:39:27'),
(5, 'Sector', 'Agrupación por sectores de la copropiedad.', 1, '2026-08-10 22:39:27'),
(6, 'Edificio', 'Agrupación de unidades dentro de un edificio.', 1, '2026-08-10 22:39:27'),
(7, 'Zona', 'Agrupación por zonas específicas.', 1, '2026-08-10 22:39:27'),
(8, 'Conjunto', 'Agrupación de unidades dentro de un conjunto.', 1, '2026-08-10 22:39:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_copropiedad`
--

CREATE TABLE `tipos_copropiedad` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `observacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipos_copropiedad`
--

INSERT INTO `tipos_copropiedad` (`id`, `nombre`, `observacion`) VALUES
(1, 'Residencial (Torres)', 'Edificios o torres de apartamentos multifamiliares.'),
(2, 'Residencial (Casas)', 'Conjuntos cerrados, urbanizaciones o condominios de casas.'),
(3, 'Comercial', 'Centros comerciales, pasajes comerciales o edificios de oficinas y consultorios.'),
(4, 'Industrial', 'Parques industriales, centros de logística y bodegas.'),
(5, 'Mixto', 'Proyectos que combinan áreas comerciales en niveles inferiores y residencial en superiores.'),
(6, 'Vacacional / Turístico', 'Condominios de playa o campo, aparthoteles y edificios de rentas cortas.'),
(7, 'Macrocopropiedad', 'Ciudadelas o macro-proyectos que agrupan múltiples conjuntos independientes.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_documento`
--

CREATE TABLE `tipos_documento` (
  `id_tipo_documento` int(11) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `estado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_documento`
--

INSERT INTO `tipos_documento` (`id_tipo_documento`, `codigo`, `nombre`, `estado`) VALUES
(1, 'CC', 'Cédula de ciudadanía', 1),
(2, 'TI', 'Tarjeta de identidad', 1),
(3, 'CE', 'Cédula de extranjería', 1),
(4, 'PA', 'Pasaporte', 1),
(5, 'NIT', 'Número de Identificación Tributaria', 1),
(6, 'RUT', 'Registro Unico', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_obligacion`
--

CREATE TABLE `tipos_obligacion` (
  `id_tipo_obligacion` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `orden_defecto` int(11) NOT NULL DEFAULT 0,
  `genera_intereses` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_obligacion`
--

INSERT INTO `tipos_obligacion` (`id_tipo_obligacion`, `nombre`, `descripcion`, `orden_defecto`, `genera_intereses`, `activo`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Intereses', 'Intereses de mora generados por obligaciones vencidas', 1, 0, 1, '2026-08-21 23:15:56', '2026-08-21 23:15:56'),
(2, 'Administración ordinaria', 'Cuota ordinaria de administración', 2, 1, 1, '2026-08-21 23:15:56', '2026-08-21 23:15:56'),
(3, 'Administración extraordinaria', 'Cuota extraordinaria aprobada por la copropiedad', 3, 1, 1, '2026-08-21 23:15:56', '2026-08-21 23:15:56'),
(4, 'Parqueadero', 'Cobro asociado al uso o asignación de parqueadero', 4, 0, 1, '2026-08-21 23:15:56', '2026-08-21 23:15:56'),
(5, 'Zona común', 'Cobros asociados a zonas o servicios comunes', 5, 0, 1, '2026-08-21 23:15:56', '2026-08-21 23:15:56'),
(6, 'Multa', 'Sanciones económicas aplicadas a la unidad', 6, 0, 1, '2026-08-21 23:15:56', '2026-08-21 23:15:56'),
(7, 'Otro', 'Otros conceptos cobrables', 7, 0, 1, '2026-08-21 23:15:56', '2026-08-21 23:15:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_persona`
--

CREATE TABLE `tipos_persona` (
  `id_tipo_persona` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_persona`
--

INSERT INTO `tipos_persona` (`id_tipo_persona`, `nombre`, `estado`) VALUES
(1, 'Propietario', 1),
(2, 'Residente', 1),
(3, 'Arrendatario', 1),
(4, 'Empleado', 1),
(5, 'Proveedor', 1),
(6, 'Visitante', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_vivienda`
--

CREATE TABLE `tipos_vivienda` (
  `id_tipo_vivienda` int(11) NOT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_vivienda`
--

INSERT INTO `tipos_vivienda` (`id_tipo_vivienda`, `codigo`, `nombre`, `descripcion`, `activo`, `orden`, `fecha_creacion`) VALUES
(1, NULL, 'Apartamento', 'Unidad residencial en edificio', 1, 1, '2026-07-01 19:20:04'),
(2, NULL, 'Casa', 'Casa independiente', 1, 2, '2026-07-01 19:20:04'),
(3, NULL, 'Casa Campestre', 'Vivienda ubicada en zona rural', 1, 5, '2026-07-01 19:20:04'),
(4, NULL, 'Local Comercial', 'Local para actividad comercial', 1, 5, '2026-07-01 19:20:04'),
(5, NULL, 'Oficina', 'Espacio destinado a oficinas', 1, 5, '2026-07-01 19:20:04'),
(6, NULL, 'Consultorio', 'Consultorio médico o profesional', 1, 5, '2026-07-01 19:20:04'),
(7, NULL, 'Bodega', 'Bodega o depósito', 1, 5, '2026-07-01 19:20:04'),
(8, NULL, 'Parqueadero', 'Parqueadero independiente', 1, 3, '2026-07-01 19:20:04'),
(9, NULL, 'Cuarto Útil', 'Depósito o cuarto útil', 1, 5, '2026-07-01 19:20:04'),
(10, NULL, 'Penthouse', 'Apartamento en el último piso', 1, 5, '2026-07-01 19:20:04'),
(11, NULL, 'Apartaestudio', 'Vivienda de un solo ambiente', 1, 5, '2026-07-01 19:20:04'),
(12, NULL, 'Lote', 'Terreno sin construir', 1, 5, '2026-07-01 19:20:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_comunicacion`
--

CREATE TABLE `tipo_comunicacion` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades`
--

CREATE TABLE `unidades` (
  `id_unidad` int(11) NOT NULL,
  `id_tipo_config` int(11) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `piso` varchar(10) DEFAULT NULL,
  `area` decimal(10,2) DEFAULT NULL,
  `coeficiente` decimal(12,8) DEFAULT NULL,
  `estado` enum('Disponible','Habitada','Desocupada','En mantenimiento') DEFAULT 'Disponible',
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `unidades`
--

INSERT INTO `unidades` (`id_unidad`, `id_tipo_config`, `codigo`, `nombre`, `piso`, `area`, `coeficiente`, `estado`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`, `activo`) VALUES
(1, 1, '101', 'Familia', '1', 30.00, 20.00000000, '', '', '2026-07-25 17:57:09', '2026-07-25 17:57:09', 1),
(2, 1, '102', 'Familia', '1', 10.00, 0.00180000, '', '', '2026-07-27 17:33:19', '2026-07-27 17:33:19', 1),
(3, 6, '202', 'Familia', '3', 30.00, 0.40000000, '', '', '2026-08-14 18:27:08', '2026-08-14 18:27:08', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usos_vivienda`
--

CREATE TABLE `usos_vivienda` (
  `id_uso` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usos_vivienda`
--

INSERT INTO `usos_vivienda` (`id_uso`, `nombre`) VALUES
(1, 'Residencial'),
(2, 'Comercial'),
(3, 'Mixto'),
(4, 'Industrial'),
(5, 'Institucional');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `id_tipo_documento` int(11) DEFAULT NULL,
  `numero_documento` varchar(30) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `id_genero` int(11) DEFAULT NULL,
  `id_estado_civil` int(11) DEFAULT NULL,
  `id_ocupacion` int(11) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `id_pais` int(11) DEFAULT NULL,
  `id_departamento` int(11) DEFAULT NULL,
  `id_ciudad` int(11) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `nombres`, `apellidos`, `id_tipo_documento`, `numero_documento`, `correo`, `telefono`, `celular`, `fecha_nacimiento`, `id_genero`, `id_estado_civil`, `id_ocupacion`, `direccion`, `id_pais`, `id_departamento`, `id_ciudad`, `foto`, `rol_id`, `estado`, `ultimo_login`, `fecha_creacion`) VALUES
(2, 'Carlos', 'Gómez', NULL, '712345678', 'carlos.gomez@ejemplo.com', '3001234567', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, '2026-08-02 18:45:01'),
(3, 'Leidy', 'Gallo', 1, '72356897', 'leidyudea23@gmail.com', '3164910858', '3215646987', '2025-12-05', 1, NULL, NULL, 'Calle 54a sur N 54 e 03', 1, NULL, 15, NULL, NULL, 1, NULL, '2026-08-02 18:45:01'),
(4, 'Sara', 'Lopez', 1, '654987321', 'sara@gmail.com', '3164910858', '32654987', '2021-12-20', 2, NULL, NULL, 'Calle 54a sur N 54 e', 1, 8, 15, 'uploads/personas/654987321.png', NULL, 1, NULL, '2026-08-02 18:45:01'),
(5, 'Andres', 'Perez', 1, '72356899', 'andres@gmail.com', '98756431', '321654987', '1985-09-30', 1, NULL, NULL, '2132as1da21d', NULL, 8, 19, 'uploads/personas/72356899.png', NULL, 1, NULL, '2026-08-02 18:45:01'),
(6, 'Nicolle', 'Velez', 2, '9876543122', 'Nicolle@gmail.com', '12346578', '31654987', '2021-06-21', 2, NULL, NULL, '32165asd', 1, 8, 19, 'uploads/personas/9876543122.png', NULL, 1, NULL, '2026-08-02 18:45:01'),
(7, 'Cristian', 'Castrillo', 1, '23456798', 'cristian@correo.com', '987654123', '465132798', '2002-08-20', 1, 2, 1, 'as32d2sa31d', 1, 8, 19, 'uploads/personas/23456798.png', NULL, 1, NULL, '2026-08-02 19:10:03'),
(8, 'Juan Gabriel', 'Henao', 1, '9765431456', 'vago@correo.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/personas/', NULL, 1, NULL, '2026-08-12 20:11:55'),
(9, 'Laura', 'Garcia', 1, '152030', 'laura@correo.com', '321654987', '321654987', '2005-07-01', 2, 1, 0, 'Calle 54a sur N 55 e 65', NULL, NULL, NULL, 'uploads/personas/152030.jpg', NULL, 1, NULL, '2026-08-18 21:18:58'),
(10, 'Catalina', 'Velasquez', 1, '784523', 'cata@correo.com', '1112346579', NULL, '2000-05-16', 2, 2, NULL, NULL, NULL, NULL, NULL, 'uploads/personas/784523.jpeg', NULL, 1, NULL, '2026-08-18 21:33:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `zona_comun`
--

CREATE TABLE zona_comun (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    capacidad INT
);

--
-- Volcado de datos para la tabla `zona_comun`
--

INSERT INTO zona_comun (nombre, descripcion, capacidad) VALUES 
('Salón Social', 'Espacio para eventos privados, incluye mesas, sillas y cocineta.', 50),
('Gimnasio', 'Equipado con máquinas de cardio y pesas. Uso exclusivo residentes.', 15),
('Piscina de Adultos', 'Piscina climatizada. Se requiere traje de baño adecuado.', 30),
('Piscina de Niños', 'Piscina de baja profundidad con supervisión requerida.', 12),
('Cancha Sintética', 'Cancha de fútbol 5 con iluminación nocturna.', 10),
('Zona BBQ', 'Kiosko con parrilla a carbón y lavaplatos.', 8),
('Sala de Cine', 'Proyector HD y sonido envolvente. Reservar con antelación.', 12),
('Parque Infantil', 'Juegos de madera y arenero para menores de 12 años.', 25),
('Coworking', 'Sala con Wi-Fi de alta velocidad y conexiones eléctricas.', 10),
('Sauna', 'Zona húmeda para relajación. Uso máximo 30 min por persona.', 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario_zona`
--

CREATE TABLE horario_zona (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_zona INT NOT NULL,
    dia_semana TINYINT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    CONSTRAINT fk_horario_zona FOREIGN KEY (id_zona) REFERENCES zona_comun(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_dia_semana CHECK (dia_semana BETWEEN 1 AND 7),
    CONSTRAINT chk_hora CHECK (hora_fin > hora_inicio)
);

--
-- Volcado de datos para la tabla `horario_zona`
--

INSERT INTO horario_zona (id_zona, dia_semana, hora_inicio, hora_fin) VALUES

-- 1. Salón Social
(1, 1, '08:00:00', '22:00:00'),
(1, 2, '08:00:00', '22:00:00'),
(1, 3, '08:00:00', '22:00:00'),
(1, 4, '08:00:00', '22:00:00'),
(1, 5, '08:00:00', '23:00:00'),
(1, 6, '09:00:00', '23:00:00'),
(1, 7, '09:00:00', '18:00:00'),

-- 2. Gimnasio
(2, 1, '05:00:00', '22:00:00'),
(2, 2, '05:00:00', '22:00:00'),
(2, 3, '05:00:00', '22:00:00'),
(2, 4, '05:00:00', '22:00:00'),
(2, 5, '05:00:00', '22:00:00'),
(2, 6, '07:00:00', '20:00:00'),
(2, 7, '07:00:00', '14:00:00'),

-- 3. Piscina de Adultos
(3, 1, '06:00:00', '21:00:00'),
(3, 2, '06:00:00', '21:00:00'),
(3, 3, '06:00:00', '21:00:00'),
(3, 4, '06:00:00', '21:00:00'),
(3, 5, '06:00:00', '21:00:00'),
(3, 6, '08:00:00', '20:00:00'),
(3, 7, '08:00:00', '18:00:00'),

-- 4. Piscina de Niños
(4, 1, '08:00:00', '18:00:00'),
(4, 2, '08:00:00', '18:00:00'),
(4, 3, '08:00:00', '18:00:00'),
(4, 4, '08:00:00', '18:00:00'),
(4, 5, '08:00:00', '18:00:00'),
(4, 6, '09:00:00', '18:00:00'),
(4, 7, '09:00:00', '17:00:00'),

-- 5. Cancha Sintética
(5, 1, '16:00:00', '22:00:00'),
(5, 2, '16:00:00', '22:00:00'),
(5, 3, '16:00:00', '22:00:00'),
(5, 4, '16:00:00', '22:00:00'),
(5, 5, '16:00:00', '23:00:00'),
(5, 6, '08:00:00', '23:00:00'),
(5, 7, '08:00:00', '20:00:00'),

-- 6. Zona BBQ
(6, 1, '10:00:00', '20:00:00'),
(6, 2, '10:00:00', '20:00:00'),
(6, 3, '10:00:00', '20:00:00'),
(6, 4, '10:00:00', '20:00:00'),
(6, 5, '10:00:00', '22:00:00'),
(6, 6, '09:00:00', '22:00:00'),
(6, 7, '09:00:00', '18:00:00'),

-- 7. Sala de Cine
(7, 1, '14:00:00', '22:00:00'),
(7, 2, '14:00:00', '22:00:00'),
(7, 3, '14:00:00', '22:00:00'),
(7, 4, '14:00:00', '22:00:00'),
(7, 5, '14:00:00', '23:00:00'),
(7, 6, '10:00:00', '23:00:00'),
(7, 7, '10:00:00', '20:00:00'),

-- 8. Parque Infantil
(8, 1, '08:00:00', '19:00:00'),
(8, 2, '08:00:00', '19:00:00'),
(8, 3, '08:00:00', '19:00:00'),
(8, 4, '08:00:00', '19:00:00'),
(8, 5, '08:00:00', '19:00:00'),
(8, 6, '08:00:00', '20:00:00'),
(8, 7, '08:00:00', '18:00:00'),

-- 9. Coworking
(9, 1, '07:00:00', '20:00:00'),
(9, 2, '07:00:00', '20:00:00'),
(9, 3, '07:00:00', '20:00:00'),
(9, 4, '07:00:00', '20:00:00'),
(9, 5, '07:00:00', '20:00:00'),
(9, 6, '08:00:00', '14:00:00'),

-- 10. Sauna
(10, 1, '10:00:00', '20:00:00'),
(10, 2, '10:00:00', '20:00:00'),
(10, 3, '10:00:00', '20:00:00'),
(10, 4, '10:00:00', '20:00:00'),
(10, 5, '10:00:00', '22:00:00'),
(10, 6, '09:00:00', '18:00:00');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `agrupaciones`
--
ALTER TABLE `agrupaciones`
  ADD PRIMARY KEY (`id_agrupacion`),
  ADD KEY `id_tipo_agrupacion` (`id_tipo_agrupacion`);

--
-- Indices de la tabla `agrupacion_tipos_unidad`
--
ALTER TABLE `agrupacion_tipos_unidad`
  ADD PRIMARY KEY (`id_agrupacion_tipo`),
  ADD UNIQUE KEY `uk_agrupacion_tipo` (`id_agrupacion`,`id_tipo_config`),
  ADD KEY `fk_agrupacion_tipo_config` (`id_tipo_config`);

--
-- Indices de la tabla `aplicaciones_pagos`
--
ALTER TABLE `aplicaciones_pagos`
  ADD PRIMARY KEY (`id_aplicacion`),
  ADD KEY `idx_aplicaciones_pago` (`id_pago`),
  ADD KEY `idx_aplicaciones_cartera` (`id_cartera`),
  ADD KEY `idx_aplicaciones_fecha` (`fecha_aplicacion`);

--
-- Indices de la tabla `articulos`
--
ALTER TABLE `articulos`
  ADD PRIMARY KEY (`id_articulo`);

--
-- Indices de la tabla `calendario_financiero`
--
ALTER TABLE `calendario_financiero`
  ADD PRIMARY KEY (`id_calendario`),
  ADD UNIQUE KEY `uk_calendario_periodo` (`periodo`),
  ADD KEY `idx_calendario_estado` (`estado`),
  ADD KEY `idx_calendario_facturacion` (`fecha_facturacion`),
  ADD KEY `idx_calendario_intereses` (`fecha_generacion_intereses`);

--
-- Indices de la tabla `cartera`
--
ALTER TABLE `cartera`
  ADD PRIMARY KEY (`id_cartera`),
  ADD KEY `idx_cartera_unidad` (`id_unidad`),
  ADD KEY `idx_cartera_tipo` (`id_tipo_obligacion`),
  ADD KEY `idx_cartera_periodo` (`periodo`),
  ADD KEY `idx_cartera_vencimiento` (`fecha_vencimiento`),
  ADD KEY `idx_cartera_estado` (`estado`);

--
-- Indices de la tabla `ciudades`
--
ALTER TABLE `ciudades`
  ADD PRIMARY KEY (`id_ciudad`),
  ADD UNIQUE KEY `uq_ciudad` (`id_departamento`,`nombre`);

--
-- Indices de la tabla `comunicacion`
--
ALTER TABLE `comunicacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emisor_id` (`emisor_id`),
  ADD KEY `tipo_id` (`tipo_id`);

--
-- Indices de la tabla `comunicacion_receptores`
--
ALTER TABLE `comunicacion_receptores`
  ADD PRIMARY KEY (`comunicacion_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `conceptos_facturacion`
--
ALTER TABLE `conceptos_facturacion`
  ADD PRIMARY KEY (`id_concepto`),
  ADD KEY `fk_concepto_cuenta_contable` (`id_cuenta_contable`),
  ADD KEY `fk_concepto_tipo_obligacion` (`id_tipo_obligacion`);

--
-- Indices de la tabla `configuracion_cartera`
--
ALTER TABLE `configuracion_cartera`
  ADD PRIMARY KEY (`id_configuracion`);

--
-- Indices de la tabla `configuracion_financiera`
--
ALTER TABLE `configuracion_financiera`
  ADD PRIMARY KEY (`id_configuracion`),
  ADD KEY `idx_configuracion_activo` (`activo`),
  ADD KEY `fk_configuracion_tasa` (`id_tasa_interes`);

--
-- Indices de la tabla `configuracion_mora`
--
ALTER TABLE `configuracion_mora`
  ADD PRIMARY KEY (`id_configuracion_mora`),
  ADD KEY `idx_mora_concepto` (`id_concepto`),
  ADD KEY `idx_mora_vigencia` (`fecha_inicio`,`fecha_fin`),
  ADD KEY `idx_mora_estado` (`estado`);

--
-- Indices de la tabla `configuracion_pagos`
--
ALTER TABLE `configuracion_pagos`
  ADD PRIMARY KEY (`id_configuracion`),
  ADD UNIQUE KEY `uk_unidad_obligacion` (`id_unidad`,`id_tipo_obligacion`),
  ADD KEY `idx_configuracion_unidad` (`id_unidad`),
  ADD KEY `idx_configuracion_obligacion` (`id_tipo_obligacion`);

--
-- Indices de la tabla `cuentas_bancarias`
--
ALTER TABLE `cuentas_bancarias`
  ADD PRIMARY KEY (`id_cuenta_bancaria`),
  ADD KEY `idx_cuenta_bancaria_estado` (`estado`);

--
-- Indices de la tabla `cuentas_contables`
--
ALTER TABLE `cuentas_contables`
  ADD PRIMARY KEY (`id_cuenta_contable`),
  ADD UNIQUE KEY `uk_cuenta_codigo` (`codigo`);

--
-- Indices de la tabla `datos_unidad`
--
ALTER TABLE `datos_unidad`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id_departamento`),
  ADD UNIQUE KEY `uq_departamento` (`id_pais`,`nombre`);

--
-- Indices de la tabla `detalle_tipos_unidad`
--
ALTER TABLE `detalle_tipos_unidad`
  ADD PRIMARY KEY (`id_tipo_config`),
  ADD UNIQUE KEY `uq_tipo_vivienda` (`id_tipo_vivienda`);

--
-- Indices de la tabla `documentos_bancarios`
--
ALTER TABLE `documentos_bancarios`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `idx_documento_estado` (`estado_procesamiento`),
  ADD KEY `idx_documento_hash` (`hash_archivo`),
  ADD KEY `idx_documento_cuenta` (`id_cuenta_bancaria`);

--
-- Indices de la tabla `espacios_unidad`
--
ALTER TABLE `espacios_unidad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conjunto_id` (`conjunto_id`),
  ADD KEY `propietario_id` (`propietario_id`),
  ADD KEY `residente_id` (`residente_id`);

--
-- Indices de la tabla `estados_civiles`
--
ALTER TABLE `estados_civiles`
  ADD PRIMARY KEY (`id_estado_civil`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `extractos_bancarios`
--
ALTER TABLE `extractos_bancarios`
  ADD PRIMARY KEY (`id_extracto`),
  ADD KEY `idx_extracto_fecha` (`fecha_movimiento`),
  ADD KEY `idx_extracto_referencia` (`referencia`),
  ADD KEY `idx_extracto_estado` (`estado_conciliacion`),
  ADD KEY `idx_extracto_documento` (`id_documento`),
  ADD KEY `fk_extracto_cuenta_bancaria` (`id_cuenta_bancaria`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id_factura`),
  ADD KEY `idx_factura_unidad` (`id_unidad`),
  ADD KEY `idx_factura_periodo` (`periodo`,`mes`),
  ADD KEY `idx_factura_estado` (`estado`);

--
-- Indices de la tabla `facturas_detalle`
--
ALTER TABLE `facturas_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `idx_detalle_factura` (`id_factura`),
  ADD KEY `idx_detalle_concepto` (`id_concepto`),
  ADD KEY `idx_detalle_tarifa` (`id_tarifa`);

--
-- Indices de la tabla `facturas_saldos`
--
ALTER TABLE `facturas_saldos`
  ADD PRIMARY KEY (`id_saldo`),
  ADD KEY `idx_saldo_factura` (`id_factura`),
  ADD KEY `idx_saldo_origen` (`id_factura_origen`);

--
-- Indices de la tabla `generos`
--
ALTER TABLE `generos`
  ADD PRIMARY KEY (`id_genero`),
  ADD UNIQUE KEY `uq_genero_codigo` (`codigo`),
  ADD UNIQUE KEY `uq_genero_nombre` (`nombre`);

--
-- Indices de la tabla `intereses_cartera`
--
ALTER TABLE `intereses_cartera`
  ADD PRIMARY KEY (`id_interes`),
  ADD KEY `idx_intereses_cartera` (`id_cartera`),
  ADD KEY `idx_intereses_fecha` (`fecha_calculo`),
  ADD KEY `idx_intereses_estado` (`estado`),
  ADD KEY `idx_intereses_tasa` (`id_tasa_interes`);

--
-- Indices de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zona_id` (`zona_id`),
  ADD KEY `usuario_reporta_id` (`usuario_reporta_id`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `fk_movimiento_articulo` (`id_articulo`),
  ADD KEY `fk_movimiento_usuario` (`id_usuario`);

--
-- Indices de la tabla `obligaciones`
--
ALTER TABLE `obligaciones`
  ADD PRIMARY KEY (`id_obligacion`),
  ADD UNIQUE KEY `uk_obligacion_unidad_tipo_periodo` (`id_unidad`,`id_tipo_obligacion`,`periodo`),
  ADD KEY `idx_obligacion_unidad` (`id_unidad`),
  ADD KEY `idx_obligacion_tipo` (`id_tipo_obligacion`),
  ADD KEY `idx_obligacion_periodo` (`periodo`),
  ADD KEY `idx_obligacion_vencimiento` (`fecha_vencimiento`),
  ADD KEY `idx_obligacion_estado` (`estado`);

--
-- Indices de la tabla `ocupaciones`
--
ALTER TABLE `ocupaciones`
  ADD PRIMARY KEY (`id_ocupacion`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `idx_pago_unidad` (`id_unidad`),
  ADD KEY `idx_pago_fecha` (`fecha_pago`),
  ADD KEY `idx_pago_estado` (`estado`),
  ADD KEY `idx_pagos_extracto` (`id_extracto`);

--
-- Indices de la tabla `paises`
--
ALTER TABLE `paises`
  ADD PRIMARY KEY (`id_pais`),
  ADD UNIQUE KEY `uq_pais_nombre` (`nombre`);

--
-- Indices de la tabla `parentescos`
--
ALTER TABLE `parentescos`
  ADD PRIMARY KEY (`id_parentesco`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `pqrs`
--
ALTER TABLE `pqrs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `reserva_zona`
--
ALTER TABLE `reserva_zona`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zona_id` (`zona_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `residente`
--
ALTER TABLE `residente`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `saldo_favor`
--
ALTER TABLE `saldo_favor`
  ADD PRIMARY KEY (`id_saldo_favor`),
  ADD KEY `idx_saldo_favor_unidad` (`id_unidad`),
  ADD KEY `idx_saldo_favor_pago` (`id_pago`),
  ADD KEY `idx_saldo_favor_estado` (`estado`);

--
-- Indices de la tabla `tarifas_facturacion`
--
ALTER TABLE `tarifas_facturacion`
  ADD PRIMARY KEY (`id_tarifa`),
  ADD KEY `fk_tarifa_concepto` (`id_concepto`),
  ADD KEY `idx_tarifa_tipo_config` (`id_tipo_config`);

--
-- Indices de la tabla `tasas_interes`
--
ALTER TABLE `tasas_interes`
  ADD PRIMARY KEY (`id_tasa_interes`),
  ADD KEY `idx_fecha_inicio` (`fecha_inicio`),
  ADD KEY `idx_fecha_fin` (`fecha_fin`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `tipos_agrupacion`
--
ALTER TABLE `tipos_agrupacion`
  ADD PRIMARY KEY (`id_tipo_agrupacion`);

--
-- Indices de la tabla `tipos_copropiedad`
--
ALTER TABLE `tipos_copropiedad`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tipos_documento`
--
ALTER TABLE `tipos_documento`
  ADD PRIMARY KEY (`id_tipo_documento`);

--
-- Indices de la tabla `tipos_obligacion`
--
ALTER TABLE `tipos_obligacion`
  ADD PRIMARY KEY (`id_tipo_obligacion`),
  ADD UNIQUE KEY `uk_tipos_obligacion_nombre` (`nombre`);

--
-- Indices de la tabla `tipos_persona`
--
ALTER TABLE `tipos_persona`
  ADD PRIMARY KEY (`id_tipo_persona`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `tipos_vivienda`
--
ALTER TABLE `tipos_vivienda`
  ADD PRIMARY KEY (`id_tipo_vivienda`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `tipo_comunicacion`
--
ALTER TABLE `tipo_comunicacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id_unidad`),
  ADD KEY `id_tipo_config` (`id_tipo_config`);

--
-- Indices de la tabla `usos_vivienda`
--
ALTER TABLE `usos_vivienda`
  ADD PRIMARY KEY (`id_uso`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `uq_usuario_documento` (`numero_documento`),
  ADD KEY `rol_id` (`rol_id`),
  ADD KEY `fk_usuario_pais` (`id_pais`),
  ADD KEY `fk_usuario_departamento` (`id_departamento`),
  ADD KEY `fk_usuario_ciudad` (`id_ciudad`),
  ADD KEY `fk_usuario_genero` (`id_genero`),
  ADD KEY `fk_usuario_tipo_documento` (`id_tipo_documento`);

--
-- Indices de la tabla `zona_comun`
--
ALTER TABLE `zona_comun`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `agrupaciones`
--
ALTER TABLE `agrupaciones`
  MODIFY `id_agrupacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `agrupacion_tipos_unidad`
--
ALTER TABLE `agrupacion_tipos_unidad`
  MODIFY `id_agrupacion_tipo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `aplicaciones_pagos`
--
ALTER TABLE `aplicaciones_pagos`
  MODIFY `id_aplicacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `articulos`
--
ALTER TABLE `articulos`
  MODIFY `id_articulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `calendario_financiero`
--
ALTER TABLE `calendario_financiero`
  MODIFY `id_calendario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cartera`
--
ALTER TABLE `cartera`
  MODIFY `id_cartera` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ciudades`
--
ALTER TABLE `ciudades`
  MODIFY `id_ciudad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `comunicacion`
--
ALTER TABLE `comunicacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `conceptos_facturacion`
--
ALTER TABLE `conceptos_facturacion`
  MODIFY `id_concepto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `configuracion_cartera`
--
ALTER TABLE `configuracion_cartera`
  MODIFY `id_configuracion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_financiera`
--
ALTER TABLE `configuracion_financiera`
  MODIFY `id_configuracion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `configuracion_mora`
--
ALTER TABLE `configuracion_mora`
  MODIFY `id_configuracion_mora` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_pagos`
--
ALTER TABLE `configuracion_pagos`
  MODIFY `id_configuracion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cuentas_bancarias`
--
ALTER TABLE `cuentas_bancarias`
  MODIFY `id_cuenta_bancaria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cuentas_contables`
--
ALTER TABLE `cuentas_contables`
  MODIFY `id_cuenta_contable` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `datos_unidad`
--
ALTER TABLE `datos_unidad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id_departamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `detalle_tipos_unidad`
--
ALTER TABLE `detalle_tipos_unidad`
  MODIFY `id_tipo_config` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `documentos_bancarios`
--
ALTER TABLE `documentos_bancarios`
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `espacios_unidad`
--
ALTER TABLE `espacios_unidad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estados_civiles`
--
ALTER TABLE `estados_civiles`
  MODIFY `id_estado_civil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `extractos_bancarios`
--
ALTER TABLE `extractos_bancarios`
  MODIFY `id_extracto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id_factura` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facturas_detalle`
--
ALTER TABLE `facturas_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facturas_saldos`
--
ALTER TABLE `facturas_saldos`
  MODIFY `id_saldo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `generos`
--
ALTER TABLE `generos`
  MODIFY `id_genero` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `intereses_cartera`
--
ALTER TABLE `intereses_cartera`
  MODIFY `id_interes` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `obligaciones`
--
ALTER TABLE `obligaciones`
  MODIFY `id_obligacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ocupaciones`
--
ALTER TABLE `ocupaciones`
  MODIFY `id_ocupacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `paises`
--
ALTER TABLE `paises`
  MODIFY `id_pais` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `parentescos`
--
ALTER TABLE `parentescos`
  MODIFY `id_parentesco` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pqrs`
--
ALTER TABLE `pqrs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reserva_zona`
--
ALTER TABLE `reserva_zona`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `residente`
--
ALTER TABLE `residente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `saldo_favor`
--
ALTER TABLE `saldo_favor`
  MODIFY `id_saldo_favor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tarifas_facturacion`
--
ALTER TABLE `tarifas_facturacion`
  MODIFY `id_tarifa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tasas_interes`
--
ALTER TABLE `tasas_interes`
  MODIFY `id_tasa_interes` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tipos_agrupacion`
--
ALTER TABLE `tipos_agrupacion`
  MODIFY `id_tipo_agrupacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `tipos_copropiedad`
--
ALTER TABLE `tipos_copropiedad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tipos_documento`
--
ALTER TABLE `tipos_documento`
  MODIFY `id_tipo_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tipos_obligacion`
--
ALTER TABLE `tipos_obligacion`
  MODIFY `id_tipo_obligacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tipos_persona`
--
ALTER TABLE `tipos_persona`
  MODIFY `id_tipo_persona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tipos_vivienda`
--
ALTER TABLE `tipos_vivienda`
  MODIFY `id_tipo_vivienda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `tipo_comunicacion`
--
ALTER TABLE `tipo_comunicacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id_unidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usos_vivienda`
--
ALTER TABLE `usos_vivienda`
  MODIFY `id_uso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `zona_comun`
--
ALTER TABLE `zona_comun`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `agrupaciones`
--
ALTER TABLE `agrupaciones`
  ADD CONSTRAINT `agrupaciones_ibfk_1` FOREIGN KEY (`id_tipo_agrupacion`) REFERENCES `tipos_agrupacion` (`id_tipo_agrupacion`);

--
-- Filtros para la tabla `agrupacion_tipos_unidad`
--
ALTER TABLE `agrupacion_tipos_unidad`
  ADD CONSTRAINT `fk_agrupacion_tipo_agrupacion` FOREIGN KEY (`id_agrupacion`) REFERENCES `agrupaciones` (`id_agrupacion`),
  ADD CONSTRAINT `fk_agrupacion_tipo_config` FOREIGN KEY (`id_tipo_config`) REFERENCES `detalle_tipos_unidad` (`id_tipo_config`);

--
-- Filtros para la tabla `aplicaciones_pagos`
--
ALTER TABLE `aplicaciones_pagos`
  ADD CONSTRAINT `fk_aplicaciones_cartera` FOREIGN KEY (`id_cartera`) REFERENCES `cartera` (`id_cartera`),
  ADD CONSTRAINT `fk_aplicaciones_pago` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`);

--
-- Filtros para la tabla `cartera`
--
ALTER TABLE `cartera`
  ADD CONSTRAINT `fk_cartera_tipo_obligacion` FOREIGN KEY (`id_tipo_obligacion`) REFERENCES `tipos_obligacion` (`id_tipo_obligacion`),
  ADD CONSTRAINT `fk_cartera_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`);

--
-- Filtros para la tabla `ciudades`
--
ALTER TABLE `ciudades`
  ADD CONSTRAINT `fk_ciudad_departamento` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`);

--
-- Filtros para la tabla `comunicacion`
--
ALTER TABLE `comunicacion`
  ADD CONSTRAINT `comunicacion_ibfk_1` FOREIGN KEY (`emisor_id`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `comunicacion_ibfk_2` FOREIGN KEY (`tipo_id`) REFERENCES `tipo_comunicacion` (`id`);

--
-- Filtros para la tabla `comunicacion_receptores`
--
ALTER TABLE `comunicacion_receptores`
  ADD CONSTRAINT `comunicacion_receptores_ibfk_1` FOREIGN KEY (`comunicacion_id`) REFERENCES `comunicacion` (`id`),
  ADD CONSTRAINT `comunicacion_receptores_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `conceptos_facturacion`
--
ALTER TABLE `conceptos_facturacion`
  ADD CONSTRAINT `fk_concepto_cuenta` FOREIGN KEY (`id_cuenta_contable`) REFERENCES `cuentas_contables` (`id_cuenta_contable`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_concepto_tipo_obligacion` FOREIGN KEY (`id_tipo_obligacion`) REFERENCES `tipos_obligacion` (`id_tipo_obligacion`);

--
-- Filtros para la tabla `configuracion_financiera`
--
ALTER TABLE `configuracion_financiera`
  ADD CONSTRAINT `fk_configuracion_tasa` FOREIGN KEY (`id_tasa_interes`) REFERENCES `tasas_interes` (`id_tasa_interes`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `configuracion_mora`
--
ALTER TABLE `configuracion_mora`
  ADD CONSTRAINT `fk_mora_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos_facturacion` (`id_concepto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `configuracion_pagos`
--
ALTER TABLE `configuracion_pagos`
  ADD CONSTRAINT `fk_configuracion_pagos_tipo_obligacion` FOREIGN KEY (`id_tipo_obligacion`) REFERENCES `tipos_obligacion` (`id_tipo_obligacion`),
  ADD CONSTRAINT `fk_configuracion_pagos_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`);

--
-- Filtros para la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD CONSTRAINT `fk_departamento_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`);

--
-- Filtros para la tabla `detalle_tipos_unidad`
--
ALTER TABLE `detalle_tipos_unidad`
  ADD CONSTRAINT `fk_detalle_tipo` FOREIGN KEY (`id_tipo_vivienda`) REFERENCES `tipos_vivienda` (`id_tipo_vivienda`),
  ADD CONSTRAINT `fk_detalle_tipo_vivienda` FOREIGN KEY (`id_tipo_vivienda`) REFERENCES `tipos_vivienda` (`id_tipo_vivienda`);

--
-- Filtros para la tabla `documentos_bancarios`
--
ALTER TABLE `documentos_bancarios`
  ADD CONSTRAINT `fk_documento_cuenta` FOREIGN KEY (`id_cuenta_bancaria`) REFERENCES `cuentas_bancarias` (`id_cuenta_bancaria`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `espacios_unidad`
--
ALTER TABLE `espacios_unidad`
  ADD CONSTRAINT `espacios_unidad_ibfk_1` FOREIGN KEY (`conjunto_id`) REFERENCES `datos_unidad` (`id`),
  ADD CONSTRAINT `espacios_unidad_ibfk_2` FOREIGN KEY (`propietario_id`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `espacios_unidad_ibfk_3` FOREIGN KEY (`residente_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `extractos_bancarios`
--
ALTER TABLE `extractos_bancarios`
  ADD CONSTRAINT `fk_extracto_cuenta_bancaria` FOREIGN KEY (`id_cuenta_bancaria`) REFERENCES `cuentas_bancarias` (`id_cuenta_bancaria`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_extracto_documento` FOREIGN KEY (`id_documento`) REFERENCES `documentos_bancarios` (`id_documento`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `fk_factura_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `facturas_detalle`
--
ALTER TABLE `facturas_detalle`
  ADD CONSTRAINT `fk_detalle_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos_facturacion` (`id_concepto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_factura` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id_factura`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_tarifa` FOREIGN KEY (`id_tarifa`) REFERENCES `tarifas_facturacion` (`id_tarifa`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `facturas_saldos`
--
ALTER TABLE `facturas_saldos`
  ADD CONSTRAINT `fk_saldo_factura` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id_factura`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_saldo_factura_origen` FOREIGN KEY (`id_factura_origen`) REFERENCES `facturas` (`id_factura`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `intereses_cartera`
--
ALTER TABLE `intereses_cartera`
  ADD CONSTRAINT `fk_intereses_cartera` FOREIGN KEY (`id_cartera`) REFERENCES `cartera` (`id_cartera`),
  ADD CONSTRAINT `fk_intereses_tasa` FOREIGN KEY (`id_tasa_interes`) REFERENCES `tasas_interes` (`id_tasa_interes`);

--
-- Filtros para la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  ADD CONSTRAINT `mantenimiento_ibfk_1` FOREIGN KEY (`zona_id`) REFERENCES `zona_comun` (`id`),
  ADD CONSTRAINT `mantenimiento_ibfk_2` FOREIGN KEY (`usuario_reporta_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `fk_movimiento_articulo` FOREIGN KEY (`id_articulo`) REFERENCES `articulos` (`id_articulo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimiento_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pago_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pagos_extracto` FOREIGN KEY (`id_extracto`) REFERENCES `extractos_bancarios` (`id_extracto`);

--
-- Filtros para la tabla `pqrs`
--
ALTER TABLE `pqrs`
  ADD CONSTRAINT `pqrs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `reserva_zona`
--
ALTER TABLE `reserva_zona`
  ADD CONSTRAINT `reserva_zona_ibfk_1` FOREIGN KEY (`zona_id`) REFERENCES `zona_comun` (`id`),
  ADD CONSTRAINT `reserva_zona_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `saldo_favor`
--
ALTER TABLE `saldo_favor`
  ADD CONSTRAINT `fk_saldo_favor_pago` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`),
  ADD CONSTRAINT `fk_saldo_favor_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`);

--
-- Filtros para la tabla `tarifas_facturacion`
--
ALTER TABLE `tarifas_facturacion`
  ADD CONSTRAINT `fk_tarifa_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos_facturacion` (`id_concepto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tarifa_tipo_config` FOREIGN KEY (`id_tipo_config`) REFERENCES `detalle_tipos_unidad` (`id_tipo_config`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD CONSTRAINT `unidades_ibfk_1` FOREIGN KEY (`id_tipo_config`) REFERENCES `detalle_tipos_unidad` (`id_tipo_config`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_ciudad` FOREIGN KEY (`id_ciudad`) REFERENCES `ciudades` (`id_ciudad`),
  ADD CONSTRAINT `fk_usuario_departamento` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`),
  ADD CONSTRAINT `fk_usuario_genero` FOREIGN KEY (`id_genero`) REFERENCES `generos` (`id_genero`),
  ADD CONSTRAINT `fk_usuario_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`),
  ADD CONSTRAINT `fk_usuario_tipo_documento` FOREIGN KEY (`id_tipo_documento`) REFERENCES `tipos_documento` (`id_tipo_documento`),
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
