-- Esquema inicial de Convivium, exportado sin datos.
-- Se omiten pago y tipos_unidad por errores MariaDB 1932; no tienen referencias en el código ni claves foráneas.

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `agrupacion_tipos_unidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agrupacion_tipos_unidad` (
  `id_agrupacion_tipo` int(11) NOT NULL AUTO_INCREMENT,
  `id_agrupacion` int(11) NOT NULL,
  `id_tipo_config` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_agrupacion_tipo`),
  UNIQUE KEY `uk_agrupacion_tipo` (`id_agrupacion`,`id_tipo_config`),
  KEY `fk_agrupacion_tipo_config` (`id_tipo_config`),
  CONSTRAINT `fk_agrupacion_tipo_agrupacion` FOREIGN KEY (`id_agrupacion`) REFERENCES `agrupaciones` (`id_agrupacion`),
  CONSTRAINT `fk_agrupacion_tipo_config` FOREIGN KEY (`id_tipo_config`) REFERENCES `detalle_tipos_unidad` (`id_tipo_config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agrupaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agrupaciones` (
  `id_agrupacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_tipo_agrupacion` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_agrupacion`),
  KEY `id_tipo_agrupacion` (`id_tipo_agrupacion`),
  CONSTRAINT `agrupaciones_ibfk_1` FOREIGN KEY (`id_tipo_agrupacion`) REFERENCES `tipos_agrupacion` (`id_tipo_agrupacion`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aplicaciones_pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aplicaciones_pagos` (
  `id_aplicacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_pago` int(11) NOT NULL,
  `id_cartera` int(11) NOT NULL,
  `valor_aplicado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_aplicacion` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_aplicacion` enum('AUTOMATICA','MANUAL') NOT NULL DEFAULT 'AUTOMATICA',
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_aplicacion`),
  KEY `idx_aplicaciones_pago` (`id_pago`),
  KEY `idx_aplicaciones_cartera` (`id_cartera`),
  KEY `idx_aplicaciones_fecha` (`fecha_aplicacion`),
  CONSTRAINT `fk_aplicaciones_cartera` FOREIGN KEY (`id_cartera`) REFERENCES `cartera` (`id_cartera`),
  CONSTRAINT `fk_aplicaciones_pago` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `aplicaciones_saldo_favor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aplicaciones_saldo_favor` (
  `id_aplicacion_saldo` int(11) NOT NULL AUTO_INCREMENT,
  `id_saldo_favor` int(11) NOT NULL,
  `id_cartera` int(11) NOT NULL,
  `valor_aplicado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_aplicacion` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_aplicacion` enum('AUTOMATICA','MANUAL') NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_aplicacion_saldo`),
  KEY `idx_aplicacion_saldo_favor` (`id_saldo_favor`),
  KEY `idx_aplicacion_cartera` (`id_cartera`),
  KEY `idx_aplicacion_fecha` (`fecha_aplicacion`),
  CONSTRAINT `fk_aplicacion_cartera` FOREIGN KEY (`id_cartera`) REFERENCES `cartera` (`id_cartera`),
  CONSTRAINT `fk_aplicacion_saldo_favor` FOREIGN KEY (`id_saldo_favor`) REFERENCES `saldo_favor` (`id_saldo_favor`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `articulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `articulos` (
  `id_articulo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `unidad_medida` varchar(20) DEFAULT NULL,
  `stock_actual` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_articulo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asignaciones_parqueadero`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asignaciones_parqueadero` (
  `id_asignacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_parqueadero` int(11) NOT NULL,
  `id_vehiculo` int(11) DEFAULT NULL,
  `id_unidad` int(11) NOT NULL,
  `id_residente` int(11) DEFAULT NULL,
  `fecha_desde` date NOT NULL,
  `fecha_hasta` date DEFAULT NULL,
  `estado` enum('ACTIVA','FINALIZADA') NOT NULL DEFAULT 'ACTIVA',
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_asignacion`),
  KEY `idx_asignacion_parqueadero` (`id_parqueadero`),
  KEY `idx_asignacion_vehiculo` (`id_vehiculo`),
  KEY `idx_asignacion_unidad` (`id_unidad`),
  KEY `idx_asignacion_residente` (`id_residente`),
  CONSTRAINT `fk_asignacion_parqueadero` FOREIGN KEY (`id_parqueadero`) REFERENCES `parqueaderos` (`id_parqueadero`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asignacion_residente` FOREIGN KEY (`id_residente`) REFERENCES `residente` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_asignacion_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`) ON UPDATE CASCADE,
  CONSTRAINT `fk_asignacion_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendario_financiero`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendario_financiero` (
  `id_calendario` int(11) NOT NULL AUTO_INCREMENT,
  `periodo` date NOT NULL,
  `fecha_inicio_cierre` date NOT NULL,
  `fecha_fin_cierre` date NOT NULL,
  `fecha_facturacion` date NOT NULL,
  `fecha_generacion_intereses` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('ABIERTO','EN_CIERRE','CERRADO') NOT NULL DEFAULT 'ABIERTO',
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_calendario`),
  UNIQUE KEY `uk_calendario_periodo` (`periodo`),
  KEY `idx_calendario_estado` (`estado`),
  KEY `idx_calendario_facturacion` (`fecha_facturacion`),
  KEY `idx_calendario_intereses` (`fecha_generacion_intereses`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cargos_facturacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cargos_facturacion` (
  `id_cargo` int(11) NOT NULL AUTO_INCREMENT,
  `id_concepto` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo_aplicacion` enum('TODAS_UNIDADES','TIPO_UNIDAD','UNIDAD') NOT NULL,
  `id_tipo_config` int(11) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `tipo_distribucion` enum('VALOR_FIJO','METRO_CUADRADO','COEFICIENTE') NOT NULL DEFAULT 'VALOR_FIJO',
  `valor_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `numero_cuotas` int(11) NOT NULL DEFAULT 1,
  `periodo_inicio` date NOT NULL,
  `estado` enum('BORRADOR','ACTIVO','FINALIZADO','ANULADO') NOT NULL DEFAULT 'BORRADOR',
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_cargo`),
  KEY `fk_cargo_tipo_unidad` (`id_tipo_config`),
  KEY `fk_cargo_unidad` (`id_unidad`),
  KEY `idx_cargo_concepto` (`id_concepto`),
  CONSTRAINT `fk_cargo_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos_facturacion` (`id_concepto`),
  CONSTRAINT `fk_cargo_tipo_unidad` FOREIGN KEY (`id_tipo_config`) REFERENCES `detalle_tipos_unidad` (`id_tipo_config`),
  CONSTRAINT `fk_cargo_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cargos_facturacion_cuotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cargos_facturacion_cuotas` (
  `id_cuota` int(11) NOT NULL AUTO_INCREMENT,
  `id_cargo_unidad` int(11) NOT NULL,
  `numero_cuota` int(11) NOT NULL,
  `periodo` date NOT NULL,
  `valor` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estado` enum('PENDIENTE','FACTURADA','ANULADA') NOT NULL DEFAULT 'PENDIENTE',
  `fecha_facturacion` date DEFAULT NULL,
  `id_detalle` int(11) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_cuota`),
  UNIQUE KEY `uq_cargo_unidad_numero_cuota` (`id_cargo_unidad`,`numero_cuota`),
  KEY `idx_cuota_periodo` (`periodo`),
  KEY `idx_cuota_estado` (`estado`),
  KEY `idx_cuota_cargo_unidad` (`id_cargo_unidad`),
  KEY `idx_cuota_detalle` (`id_detalle`),
  CONSTRAINT `fk_cuota_cargo_unidad` FOREIGN KEY (`id_cargo_unidad`) REFERENCES `cargos_facturacion_unidades` (`id_cargo_unidad`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cuota_detalle` FOREIGN KEY (`id_detalle`) REFERENCES `facturas_detalle` (`id_detalle`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cargos_facturacion_unidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cargos_facturacion_unidades` (
  `id_cargo_unidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_cargo` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `valor_asignado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cantidad_cuotas` int(11) NOT NULL DEFAULT 1,
  `estado` enum('ACTIVO','ANULADO') NOT NULL DEFAULT 'ACTIVO',
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_cargo_unidad`),
  UNIQUE KEY `uq_cargo_unidad` (`id_cargo`,`id_unidad`),
  KEY `fk_cargo_unidad_unidad` (`id_unidad`),
  CONSTRAINT `fk_cargo_unidad_cargo` FOREIGN KEY (`id_cargo`) REFERENCES `cargos_facturacion` (`id_cargo`),
  CONSTRAINT `fk_cargo_unidad_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cartera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cartera` (
  `id_cartera` int(11) NOT NULL AUTO_INCREMENT,
  `id_factura` int(11) DEFAULT NULL,
  `id_detalle` int(11) DEFAULT NULL,
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
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_cartera`),
  UNIQUE KEY `uq_cartera_detalle` (`id_detalle`),
  KEY `idx_cartera_unidad` (`id_unidad`),
  KEY `idx_cartera_tipo` (`id_tipo_obligacion`),
  KEY `idx_cartera_periodo` (`periodo`),
  KEY `idx_cartera_vencimiento` (`fecha_vencimiento`),
  KEY `idx_cartera_estado` (`estado`),
  KEY `idx_cartera_factura` (`id_factura`),
  CONSTRAINT `fk_cartera_detalle` FOREIGN KEY (`id_detalle`) REFERENCES `facturas_detalle` (`id_detalle`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cartera_factura` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id_factura`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cartera_tipo_obligacion` FOREIGN KEY (`id_tipo_obligacion`) REFERENCES `tipos_obligacion` (`id_tipo_obligacion`),
  CONSTRAINT `fk_cartera_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ciudades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ciudades` (
  `id_ciudad` int(11) NOT NULL AUTO_INCREMENT,
  `id_departamento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo_dane` varchar(10) DEFAULT NULL,
  `Activo` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_ciudad`),
  UNIQUE KEY `uq_ciudad` (`id_departamento`,`nombre`),
  CONSTRAINT `fk_ciudad_departamento` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comunicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comunicacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `emisor_id` int(11) NOT NULL,
  `tipo_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `emisor_id` (`emisor_id`),
  KEY `tipo_id` (`tipo_id`),
  CONSTRAINT `comunicacion_ibfk_1` FOREIGN KEY (`emisor_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `comunicacion_ibfk_2` FOREIGN KEY (`tipo_id`) REFERENCES `tipo_comunicacion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comunicacion_receptores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comunicacion_receptores` (
  `comunicacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`comunicacion_id`,`usuario_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `comunicacion_receptores_ibfk_1` FOREIGN KEY (`comunicacion_id`) REFERENCES `comunicacion` (`id`),
  CONSTRAINT `comunicacion_receptores_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conceptos_facturacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conceptos_facturacion` (
  `id_concepto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo_calculo` enum('FIJO','METRO_CUADRADO','COEFICIENTE','PORCENTAJE') NOT NULL DEFAULT 'FIJO',
  `origen_cobro` enum('GENERAL','CARTERA','ESPACIO','PARTICULAR') NOT NULL DEFAULT 'PARTICULAR',
  `id_tipo_obligacion` int(11) DEFAULT NULL,
  `id_cuenta_contable` int(11) DEFAULT NULL,
  `obligatorio` tinyint(1) NOT NULL DEFAULT 0,
  `aplica_interes_mora` tinyint(1) NOT NULL DEFAULT 0,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_concepto`),
  KEY `fk_concepto_cuenta_contable` (`id_cuenta_contable`),
  KEY `fk_concepto_tipo_obligacion` (`id_tipo_obligacion`),
  CONSTRAINT `fk_concepto_cuenta` FOREIGN KEY (`id_cuenta_contable`) REFERENCES `cuentas_contables` (`id_cuenta_contable`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_concepto_tipo_obligacion` FOREIGN KEY (`id_tipo_obligacion`) REFERENCES `tipos_obligacion` (`id_tipo_obligacion`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuracion_cartera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion_cartera` (
  `id_configuracion` int(11) NOT NULL AUTO_INCREMENT,
  `dia_vencimiento` tinyint(2) NOT NULL DEFAULT 10,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_configuracion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuracion_conceptos_espacio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion_conceptos_espacio` (
  `id_config` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_espacio` enum('PARQUEADERO','CUARTO_UTIL','DEPOSITO','BODEGA','OTRO') NOT NULL,
  `id_concepto` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_config`),
  UNIQUE KEY `uq_tipo_espacio` (`tipo_espacio`),
  KEY `fk_config_espacio_concepto` (`id_concepto`),
  CONSTRAINT `fk_config_espacio_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos_facturacion` (`id_concepto`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuracion_correo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion_correo` (
  `id_configuracion_correo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_configuracion` varchar(100) NOT NULL,
  `correo_remitente` varchar(150) NOT NULL,
  `nombre_remitente` varchar(150) NOT NULL,
  `correo_respuesta` varchar(150) DEFAULT NULL,
  `smtp_host` varchar(150) NOT NULL,
  `smtp_puerto` int(11) NOT NULL DEFAULT 587,
  `smtp_seguridad` enum('TLS','SSL','NINGUNA') NOT NULL DEFAULT 'TLS',
  `smtp_autenticacion` tinyint(1) NOT NULL DEFAULT 1,
  `smtp_usuario` varchar(150) DEFAULT NULL,
  `smtp_password` text DEFAULT NULL,
  `imap_host` varchar(150) DEFAULT NULL,
  `imap_puerto` int(11) DEFAULT 993,
  `imap_seguridad` enum('TLS','SSL','NINGUNA') DEFAULT 'SSL',
  `imap_usuario` varchar(150) DEFAULT NULL,
  `imap_password` text DEFAULT NULL,
  `imap_carpeta` varchar(100) DEFAULT 'INBOX',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultima_prueba_smtp` datetime DEFAULT NULL,
  `resultado_prueba_smtp` varchar(255) DEFAULT NULL,
  `ultima_prueba_imap` datetime DEFAULT NULL,
  `resultado_prueba_imap` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_configuracion_correo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuracion_financiera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion_financiera` (
  `id_configuracion` int(11) NOT NULL AUTO_INCREMENT,
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
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_configuracion`),
  KEY `idx_configuracion_activo` (`activo`),
  KEY `fk_configuracion_tasa` (`id_tasa_interes`),
  CONSTRAINT `fk_configuracion_tasa` FOREIGN KEY (`id_tasa_interes`) REFERENCES `tasas_interes` (`id_tasa_interes`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuracion_mora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion_mora` (
  `id_configuracion_mora` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `tipo_tasa` enum('PORCENTAJE','VALOR_FIJO') NOT NULL DEFAULT 'PORCENTAJE',
  `tasa` decimal(15,6) NOT NULL DEFAULT 0.000000,
  `periodicidad` enum('DIARIA','MENSUAL') NOT NULL DEFAULT 'MENSUAL',
  `dias_gracia` int(11) NOT NULL DEFAULT 0,
  `aplicar_desde` enum('DIA_SIGUIENTE_VENCIMIENTO','DESPUES_DIAS_GRACIA') NOT NULL DEFAULT 'DIA_SIGUIENTE_VENCIMIENTO',
  `id_concepto` int(11) DEFAULT NULL,
  `id_tasa_interes` int(11) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_configuracion_mora`),
  KEY `idx_mora_concepto` (`id_concepto`),
  KEY `idx_mora_vigencia` (`fecha_inicio`,`fecha_fin`),
  KEY `idx_mora_estado` (`estado`),
  KEY `idx_mora_tasa` (`id_tasa_interes`),
  CONSTRAINT `fk_mora_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos_facturacion` (`id_concepto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mora_tasa_interes` FOREIGN KEY (`id_tasa_interes`) REFERENCES `tasas_interes` (`id_tasa_interes`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuracion_pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion_pagos` (
  `id_configuracion` int(11) NOT NULL AUTO_INCREMENT,
  `id_unidad` int(11) NOT NULL,
  `id_tipo_obligacion` int(11) NOT NULL,
  `prioridad` int(11) NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_configuracion`),
  UNIQUE KEY `uk_unidad_obligacion` (`id_unidad`,`id_tipo_obligacion`),
  KEY `idx_configuracion_unidad` (`id_unidad`),
  KEY `idx_configuracion_obligacion` (`id_tipo_obligacion`),
  CONSTRAINT `fk_configuracion_pagos_tipo_obligacion` FOREIGN KEY (`id_tipo_obligacion`) REFERENCES `tipos_obligacion` (`id_tipo_obligacion`),
  CONSTRAINT `fk_configuracion_pagos_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_bancarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuentas_bancarias` (
  `id_cuenta_bancaria` int(11) NOT NULL AUTO_INCREMENT,
  `banco` varchar(100) NOT NULL,
  `tipo_cuenta` enum('AHORROS','CORRIENTE') NOT NULL,
  `numero_cuenta` varchar(50) NOT NULL,
  `titular` varchar(150) DEFAULT NULL,
  `nit_titular` varchar(30) DEFAULT NULL,
  `moneda` varchar(10) NOT NULL DEFAULT 'COP',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_cuenta_bancaria`),
  KEY `idx_cuenta_bancaria_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_contables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuentas_contables` (
  `id_cuenta_contable` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo` enum('INGRESO','ACTIVO','PASIVO','GASTO','PATRIMONIO','OTRO') NOT NULL DEFAULT 'INGRESO',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_cuenta_contable`),
  UNIQUE KEY `uk_cuenta_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `datos_unidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `datos_unidad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departamentos` (
  `id_departamento` int(11) NOT NULL AUTO_INCREMENT,
  `id_pais` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  `Activo` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_departamento`),
  UNIQUE KEY `uq_departamento` (`id_pais`,`nombre`),
  CONSTRAINT `fk_departamento_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_tipos_unidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_tipos_unidad` (
  `id_tipo_config` int(11) NOT NULL AUTO_INCREMENT,
  `id_tipo_vivienda` int(11) NOT NULL,
  `nombre_grupo` varchar(120) NOT NULL,
  `cantidad_unidades` int(11) NOT NULL DEFAULT 0,
  `area_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `coeficiente_total` decimal(8,5) NOT NULL DEFAULT 0.00000,
  `activo` tinyint(1) DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tipo_config`),
  UNIQUE KEY `uq_tipo_vivienda` (`id_tipo_vivienda`),
  CONSTRAINT `fk_detalle_tipo` FOREIGN KEY (`id_tipo_vivienda`) REFERENCES `tipos_vivienda` (`id_tipo_vivienda`),
  CONSTRAINT `fk_detalle_tipo_vivienda` FOREIGN KEY (`id_tipo_vivienda`) REFERENCES `tipos_vivienda` (`id_tipo_vivienda`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documentos_bancarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentos_bancarios` (
  `id_documento` int(11) NOT NULL AUTO_INCREMENT,
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
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_documento`),
  KEY `idx_documento_estado` (`estado_procesamiento`),
  KEY `idx_documento_hash` (`hash_archivo`),
  KEY `idx_documento_cuenta` (`id_cuenta_bancaria`),
  CONSTRAINT `fk_documento_cuenta` FOREIGN KEY (`id_cuenta_bancaria`) REFERENCES `cuentas_bancarias` (`id_cuenta_bancaria`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `envios_facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `envios_facturas` (
  `id_envio` int(11) NOT NULL AUTO_INCREMENT,
  `id_factura` int(11) NOT NULL,
  `tipo_envio` enum('INDIVIDUAL','MASIVO') NOT NULL,
  `destinatario` varchar(150) NOT NULL,
  `nombre_destinatario` varchar(150) DEFAULT NULL,
  `asunto` varchar(255) NOT NULL,
  `estado` enum('PENDIENTE','ENVIANDO','ENVIADO','ERROR') NOT NULL DEFAULT 'PENDIENTE',
  `fecha_programacion` datetime DEFAULT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `intentos` int(11) NOT NULL DEFAULT 0,
  `mensaje_error` text DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL,
  `usuario_envia_id` int(11) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_envio`),
  KEY `fk_envio_factura_usuario` (`usuario_envia_id`),
  KEY `idx_envio_factura` (`id_factura`),
  KEY `idx_envio_estado` (`estado`),
  KEY `idx_envio_destinatario` (`destinatario`),
  CONSTRAINT `fk_envio_factura` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id_factura`) ON UPDATE CASCADE,
  CONSTRAINT `fk_envio_factura_usuario` FOREIGN KEY (`usuario_envia_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `espacios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `espacios` (
  `id_espacio` int(11) NOT NULL AUTO_INCREMENT,
  `id_tipo_espacio` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `area` decimal(10,2) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_espacio`),
  UNIQUE KEY `uk_espacio_codigo` (`codigo`),
  KEY `fk_espacio_tipo` (`id_tipo_espacio`),
  CONSTRAINT `fk_espacio_tipo` FOREIGN KEY (`id_tipo_espacio`) REFERENCES `tipos_espacio` (`id_tipo_espacio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `espacios_unidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `espacios_unidad` (
  `id_espacio_unidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_unidad` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo_espacio` enum('PARQUEADERO','CUARTO_UTIL','DEPOSITO','BODEGA','OTRO') NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `area` decimal(10,2) DEFAULT NULL,
  `fecha_desde` date NOT NULL,
  `fecha_hasta` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `clave_vigente` varchar(150) GENERATED ALWAYS AS (case when `activo` = 1 and `fecha_hasta` is null then concat(`tipo_espacio`,'|',ucase(trim(`codigo`))) else NULL end) STORED,
  PRIMARY KEY (`id_espacio_unidad`),
  UNIQUE KEY `uq_espacio_vigente` (`clave_vigente`),
  KEY `idx_espacio_unidad` (`id_unidad`),
  KEY `idx_espacio_codigo` (`codigo`),
  KEY `idx_espacio_vigencia` (`codigo`,`fecha_desde`,`fecha_hasta`),
  KEY `idx_espacio_propietario` (`usuario_id`),
  CONSTRAINT `fk_espacio_propietario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `fk_espacios_unidad_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `estados_civiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estados_civiles` (
  `id_estado_civil` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_estado_civil`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `extractos_bancarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `extractos_bancarios` (
  `id_extracto` int(11) NOT NULL AUTO_INCREMENT,
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
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_extracto`),
  KEY `idx_extracto_fecha` (`fecha_movimiento`),
  KEY `idx_extracto_referencia` (`referencia`),
  KEY `idx_extracto_estado` (`estado_conciliacion`),
  KEY `idx_extracto_documento` (`id_documento`),
  KEY `fk_extracto_cuenta_bancaria` (`id_cuenta_bancaria`),
  CONSTRAINT `fk_extracto_cuenta_bancaria` FOREIGN KEY (`id_cuenta_bancaria`) REFERENCES `cuentas_bancarias` (`id_cuenta_bancaria`) ON UPDATE CASCADE,
  CONSTRAINT `fk_extracto_documento` FOREIGN KEY (`id_documento`) REFERENCES `documentos_bancarios` (`id_documento`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facturas` (
  `id_factura` int(11) NOT NULL AUTO_INCREMENT,
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
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_factura`),
  KEY `idx_factura_unidad` (`id_unidad`),
  KEY `idx_factura_periodo` (`periodo`,`mes`),
  KEY `idx_factura_estado` (`estado`),
  CONSTRAINT `fk_factura_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facturas_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facturas_detalle` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_factura` int(11) NOT NULL,
  `id_concepto` int(11) NOT NULL,
  `id_tarifa` int(11) DEFAULT NULL,
  `id_interes` int(11) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(15,4) NOT NULL DEFAULT 1.0000,
  `valor_unitario` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tipo_calculo` enum('FIJO','METRO_CUADRADO','COEFICIENTE','PORCENTAJE') NOT NULL,
  `base_calculo` decimal(15,4) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_detalle`),
  KEY `idx_detalle_factura` (`id_factura`),
  KEY `idx_detalle_concepto` (`id_concepto`),
  KEY `idx_detalle_tarifa` (`id_tarifa`),
  KEY `idx_facturas_detalle_interes` (`id_interes`),
  CONSTRAINT `fk_detalle_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos_facturacion` (`id_concepto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_factura` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id_factura`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_tarifa` FOREIGN KEY (`id_tarifa`) REFERENCES `tarifas_facturacion` (`id_tarifa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_facturas_detalle_interes` FOREIGN KEY (`id_interes`) REFERENCES `intereses_cartera` (`id_interes`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facturas_saldos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facturas_saldos` (
  `id_saldo` int(11) NOT NULL AUTO_INCREMENT,
  `id_factura` int(11) NOT NULL,
  `id_factura_origen` int(11) NOT NULL,
  `saldo_inicial` decimal(15,2) NOT NULL DEFAULT 0.00,
  `intereses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `valor_aplicado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `saldo_pendiente` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estado` enum('PENDIENTE','PARCIAL','PAGADO') NOT NULL DEFAULT 'PENDIENTE',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_saldo`),
  KEY `idx_saldo_factura` (`id_factura`),
  KEY `idx_saldo_origen` (`id_factura_origen`),
  CONSTRAINT `fk_saldo_factura` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id_factura`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_saldo_factura_origen` FOREIGN KEY (`id_factura_origen`) REFERENCES `facturas` (`id_factura`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `generos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `generos` (
  `id_genero` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(5) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_genero`),
  UNIQUE KEY `uq_genero_codigo` (`codigo`),
  UNIQUE KEY `uq_genero_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `horario_zona`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `horario_zona` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_zona` int(11) NOT NULL,
  `dia_semana` tinyint(4) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_horario_zona` (`id_zona`),
  CONSTRAINT `fk_horario_zona` FOREIGN KEY (`id_zona`) REFERENCES `zona_comun` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_dia_semana` CHECK (`dia_semana` between 1 and 7),
  CONSTRAINT `chk_hora` CHECK (`hora_fin` > `hora_inicio`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `intereses_cartera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intereses_cartera` (
  `id_interes` int(11) NOT NULL AUTO_INCREMENT,
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
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_interes`),
  UNIQUE KEY `uq_interes_cartera_periodo` (`id_cartera`,`periodo_interes`),
  KEY `idx_intereses_cartera` (`id_cartera`),
  KEY `idx_intereses_fecha` (`fecha_calculo`),
  KEY `idx_intereses_estado` (`estado`),
  KEY `idx_intereses_tasa` (`id_tasa_interes`),
  CONSTRAINT `fk_intereses_cartera` FOREIGN KEY (`id_cartera`) REFERENCES `cartera` (`id_cartera`),
  CONSTRAINT `fk_intereses_tasa` FOREIGN KEY (`id_tasa_interes`) REFERENCES `tasas_interes` (`id_tasa_interes`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mantenimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantenimiento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `zona_id` (`zona_id`),
  KEY `usuario_reporta_id` (`usuario_reporta_id`),
  CONSTRAINT `mantenimiento_ibfk_1` FOREIGN KEY (`zona_id`) REFERENCES `zona_comun` (`id`),
  CONSTRAINT `mantenimiento_ibfk_2` FOREIGN KEY (`usuario_reporta_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mascotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mascotas` (
  `id_mascota` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `especie` varchar(50) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_mascota`),
  KEY `fk_mascotas_unidades` (`id_unidad`),
  CONSTRAINT `fk_mascotas_unidades` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos_inventario` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_articulo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_movimiento` enum('entrada','salida') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `fecha_movimiento` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_movimiento`),
  KEY `fk_movimiento_articulo` (`id_articulo`),
  KEY `fk_movimiento_usuario` (`id_usuario`),
  CONSTRAINT `fk_movimiento_articulo` FOREIGN KEY (`id_articulo`) REFERENCES `articulos` (`id_articulo`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_movimiento_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `obligaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `obligaciones` (
  `id_obligacion` int(11) NOT NULL AUTO_INCREMENT,
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
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_obligacion`),
  UNIQUE KEY `uk_obligacion_unidad_tipo_periodo` (`id_unidad`,`id_tipo_obligacion`,`periodo`),
  KEY `idx_obligacion_unidad` (`id_unidad`),
  KEY `idx_obligacion_tipo` (`id_tipo_obligacion`),
  KEY `idx_obligacion_periodo` (`periodo`),
  KEY `idx_obligacion_vencimiento` (`fecha_vencimiento`),
  KEY `idx_obligacion_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ocupaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ocupaciones` (
  `id_ocupacion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_ocupacion`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
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
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pago`),
  KEY `idx_pago_unidad` (`id_unidad`),
  KEY `idx_pago_fecha` (`fecha_pago`),
  KEY `idx_pago_estado` (`estado`),
  KEY `idx_pagos_extracto` (`id_extracto`),
  CONSTRAINT `fk_pago_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pagos_extracto` FOREIGN KEY (`id_extracto`) REFERENCES `extractos_bancarios` (`id_extracto`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `paises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paises` (
  `id_pais` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `Activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_pais`),
  UNIQUE KEY `uq_pais_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parentescos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parentescos` (
  `id_parentesco` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_parentesco`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parqueaderos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parqueaderos` (
  `id_parqueadero` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `tipo` enum('PRIVADO','VISITANTES','MOTOS','BICICLETAS') NOT NULL DEFAULT 'PRIVADO',
  `ubicacion` varchar(100) DEFAULT NULL,
  `estado` enum('DISPONIBLE','OCUPADO','MANTENIMIENTO') NOT NULL DEFAULT 'DISPONIBLE',
  `observaciones` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_parqueadero`),
  UNIQUE KEY `uk_parqueadero_codigo` (`codigo`),
  KEY `idx_parqueadero_unidad` (`id_unidad`),
  CONSTRAINT `fk_parqueadero_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pqrs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pqrs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('peticion','queja','reclamo','sugerencia') NOT NULL,
  `asunto` varchar(100) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `estado` enum('pendiente','en_proceso','cerrado') DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `pqrs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reserva_zona`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reserva_zona` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zona_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_reserva` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` enum('pendiente','aprobada','cancelada') DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `zona_id` (`zona_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `reserva_zona_ibfk_1` FOREIGN KEY (`zona_id`) REFERENCES `zona_comun` (`id`),
  CONSTRAINT `reserva_zona_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `residente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `residente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unidad_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('propietario','inquilino','residente') NOT NULL,
  `recibe_factura` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_desde` datetime DEFAULT current_timestamp(),
  `fecha_hasta` datetime DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saldo_favor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saldo_favor` (
  `id_saldo_favor` int(11) NOT NULL AUTO_INCREMENT,
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
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_saldo_favor`),
  KEY `idx_saldo_favor_unidad` (`id_unidad`),
  KEY `idx_saldo_favor_pago` (`id_pago`),
  KEY `idx_saldo_favor_estado` (`estado`),
  CONSTRAINT `fk_saldo_favor_pago` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`),
  CONSTRAINT `fk_saldo_favor_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tarifas_facturacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tarifas_facturacion` (
  `id_tarifa` int(11) NOT NULL AUTO_INCREMENT,
  `id_concepto` int(11) NOT NULL,
  `id_tipo_config` int(11) NOT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `valor` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tarifa`),
  KEY `fk_tarifa_concepto` (`id_concepto`),
  KEY `idx_tarifa_tipo_config` (`id_tipo_config`),
  CONSTRAINT `fk_tarifa_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos_facturacion` (`id_concepto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tarifa_tipo_config` FOREIGN KEY (`id_tipo_config`) REFERENCES `detalle_tipos_unidad` (`id_tipo_config`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tasas_interes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasas_interes` (
  `id_tasa_interes` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `tasa_anual` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `tasa_mensual` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fuente` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tasa_interes`),
  KEY `idx_fecha_inicio` (`fecha_inicio`),
  KEY `idx_fecha_fin` (`fecha_fin`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipo_comunicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_comunicacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_agrupacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_agrupacion` (
  `id_tipo_agrupacion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_tipo_agrupacion`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_copropiedad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_copropiedad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `observacion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_documento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_documento` (
  `id_tipo_documento` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_tipo_documento`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_espacio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_espacio` (
  `id_tipo_espacio` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `id_concepto` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tipo_espacio`),
  KEY `fk_tipo_espacio_concepto` (`id_concepto`),
  CONSTRAINT `fk_tipo_espacio_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos_facturacion` (`id_concepto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_obligacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_obligacion` (
  `id_tipo_obligacion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `orden_defecto` int(11) NOT NULL DEFAULT 0,
  `genera_intereses` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tipo_obligacion`),
  UNIQUE KEY `uk_tipos_obligacion_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_persona`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_persona` (
  `id_tipo_persona` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_tipo_persona`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_vivienda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_vivienda` (
  `id_tipo_vivienda` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_tipo_vivienda`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unidades` (
  `id_unidad` int(11) NOT NULL AUTO_INCREMENT,
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
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_unidad`),
  KEY `id_tipo_config` (`id_tipo_config`),
  CONSTRAINT `unidades_ibfk_1` FOREIGN KEY (`id_tipo_config`) REFERENCES `detalle_tipos_unidad` (`id_tipo_config`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usos_vivienda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usos_vivienda` (
  `id_uso` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id_uso`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `id_tipo_documento` int(11) DEFAULT NULL,
  `numero_documento` varchar(30) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `contrasena` varchar(255) DEFAULT NULL,
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
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `token_recuperacion` char(64) DEFAULT NULL,
  `expiracion_token` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `uq_usuario_documento` (`numero_documento`),
  KEY `rol_id` (`rol_id`),
  KEY `fk_usuario_pais` (`id_pais`),
  KEY `fk_usuario_departamento` (`id_departamento`),
  KEY `fk_usuario_ciudad` (`id_ciudad`),
  KEY `fk_usuario_genero` (`id_genero`),
  KEY `fk_usuario_tipo_documento` (`id_tipo_documento`),
  KEY `idx_usuario_token_recuperacion` (`token_recuperacion`),
  CONSTRAINT `fk_usuario_ciudad` FOREIGN KEY (`id_ciudad`) REFERENCES `ciudades` (`id_ciudad`),
  CONSTRAINT `fk_usuario_departamento` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`),
  CONSTRAINT `fk_usuario_genero` FOREIGN KEY (`id_genero`) REFERENCES `generos` (`id_genero`),
  CONSTRAINT `fk_usuario_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`),
  CONSTRAINT `fk_usuario_tipo_documento` FOREIGN KEY (`id_tipo_documento`) REFERENCES `tipos_documento` (`id_tipo_documento`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vehiculos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehiculos` (
  `id_vehiculo` int(11) NOT NULL AUTO_INCREMENT,
  `placa` varchar(10) NOT NULL,
  `tipo` enum('AUTOMOVIL','MOTOCICLETA','BICICLETA','OTRO') NOT NULL DEFAULT 'AUTOMOVIL',
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `id_residente` int(11) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_desde` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_hasta` datetime DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_vehiculo`),
  UNIQUE KEY `uk_vehiculo_placa` (`placa`),
  KEY `idx_vehiculo_residente` (`id_residente`),
  KEY `idx_vehiculo_unidad` (`id_unidad`),
  CONSTRAINT `fk_vehiculo_residente` FOREIGN KEY (`id_residente`) REFERENCES `residente` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_vehiculo_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zona_comun`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zona_comun` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
