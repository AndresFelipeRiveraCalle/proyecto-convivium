-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: convivium
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Current Database: `convivium`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `convivium` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `convivium`;

--
-- Table structure for table `agrupacion_tipos_unidad`
--

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

--
-- Dumping data for table `agrupacion_tipos_unidad`
--

LOCK TABLES `agrupacion_tipos_unidad` WRITE;
/*!40000 ALTER TABLE `agrupacion_tipos_unidad` DISABLE KEYS */;
/*!40000 ALTER TABLE `agrupacion_tipos_unidad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agrupaciones`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agrupaciones`
--

LOCK TABLES `agrupaciones` WRITE;
/*!40000 ALTER TABLE `agrupaciones` DISABLE KEYS */;
INSERT INTO `agrupaciones` VALUES (1,1,'Torre 1','Prueba',1,'2026-08-10 22:56:14','2026-08-10 22:56:14');
/*!40000 ALTER TABLE `agrupaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `aplicaciones_pagos`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aplicaciones_pagos`
--

LOCK TABLES `aplicaciones_pagos` WRITE;
/*!40000 ALTER TABLE `aplicaciones_pagos` DISABLE KEYS */;
/*!40000 ALTER TABLE `aplicaciones_pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `articulos`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `articulos`
--

LOCK TABLES `articulos` WRITE;
/*!40000 ALTER TABLE `articulos` DISABLE KEYS */;
INSERT INTO `articulos` VALUES (1,'Mesa','Mobiliarios','Unidad',0,0,'2026-08-25 02:03:07','2026-08-28 00:01:49');
/*!40000 ALTER TABLE `articulos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignaciones_parqueadero`
--

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

--
-- Dumping data for table `asignaciones_parqueadero`
--

LOCK TABLES `asignaciones_parqueadero` WRITE;
/*!40000 ALTER TABLE `asignaciones_parqueadero` DISABLE KEYS */;
/*!40000 ALTER TABLE `asignaciones_parqueadero` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendario_financiero`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendario_financiero`
--

LOCK TABLES `calendario_financiero` WRITE;
/*!40000 ALTER TABLE `calendario_financiero` DISABLE KEYS */;
INSERT INTO `calendario_financiero` VALUES (1,'2026-08-01','2026-08-01','2026-08-07','2026-08-15','2026-08-14','2026-08-30','ABIERTO',NULL,'2026-08-22 20:36:59','2026-08-22 20:40:34'),(2,'2026-09-01','2026-09-01','2026-09-07','2026-09-15','2026-09-01','2026-09-30','ABIERTO',NULL,'2026-08-22 20:45:45','2026-09-03 21:45:03'),(3,'2026-10-01','2026-10-10','2026-10-31','2026-10-10','2026-10-10','2026-10-31','ABIERTO',NULL,'2026-09-09 22:44:17','2026-09-09 22:44:17');
/*!40000 ALTER TABLE `calendario_financiero` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cargos_facturacion`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cargos_facturacion`
--

LOCK TABLES `cargos_facturacion` WRITE;
/*!40000 ALTER TABLE `cargos_facturacion` DISABLE KEYS */;
INSERT INTO `cargos_facturacion` VALUES (1,3,'Multa',NULL,'UNIDAD',NULL,1,'VALOR_FIJO',100000.00,1,'2026-09-01','ACTIVO',NULL,'2026-09-10 02:54:47','2026-09-09 21:54:47'),(2,3,'Multa por Perros',NULL,'UNIDAD',NULL,3,'VALOR_FIJO',60000.00,1,'2026-09-01','ACTIVO','Por cochino','2026-09-10 23:36:32','2026-09-10 18:36:32');
/*!40000 ALTER TABLE `cargos_facturacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cargos_facturacion_cuotas`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cargos_facturacion_cuotas`
--

LOCK TABLES `cargos_facturacion_cuotas` WRITE;
/*!40000 ALTER TABLE `cargos_facturacion_cuotas` DISABLE KEYS */;
INSERT INTO `cargos_facturacion_cuotas` VALUES (1,1,1,'2026-09-01',100000.00,'FACTURADA','2026-09-15',17,NULL,'2026-09-10 02:54:47','2026-09-10 03:56:42'),(2,2,1,'2026-09-01',60000.00,'FACTURADA','2026-09-15',19,NULL,'2026-09-10 23:36:32','2026-09-10 23:37:40');
/*!40000 ALTER TABLE `cargos_facturacion_cuotas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cargos_facturacion_unidades`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cargos_facturacion_unidades`
--

LOCK TABLES `cargos_facturacion_unidades` WRITE;
/*!40000 ALTER TABLE `cargos_facturacion_unidades` DISABLE KEYS */;
INSERT INTO `cargos_facturacion_unidades` VALUES (1,1,1,100000.00,1,'ACTIVO',NULL,'2026-09-10 02:54:47','2026-09-09 21:54:47'),(2,2,3,60000.00,1,'ACTIVO',NULL,'2026-09-10 23:36:32','2026-09-10 18:36:32');
/*!40000 ALTER TABLE `cargos_facturacion_unidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ciudades`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ciudades`
--

LOCK TABLES `ciudades` WRITE;
/*!40000 ALTER TABLE `ciudades` DISABLE KEYS */;
INSERT INTO `ciudades` VALUES (15,8,'Medell??n','05001',NULL),(16,8,'Envigado','05266',NULL),(18,8,'Bello','05088',NULL),(19,8,'Caldas','05129',NULL),(20,8,'Copacabana','05212',NULL),(21,8,'Girardota','05308',NULL),(22,8,'Itag????','05360',NULL),(23,8,'La Estrella','05380',NULL),(24,8,'Sabaneta','05631',NULL);
/*!40000 ALTER TABLE `ciudades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comunicacion`
--

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

--
-- Dumping data for table `comunicacion`
--

LOCK TABLES `comunicacion` WRITE;
/*!40000 ALTER TABLE `comunicacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `comunicacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comunicacion_receptores`
--

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

--
-- Dumping data for table `comunicacion_receptores`
--

LOCK TABLES `comunicacion_receptores` WRITE;
/*!40000 ALTER TABLE `comunicacion_receptores` DISABLE KEYS */;
/*!40000 ALTER TABLE `comunicacion_receptores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conceptos_facturacion`
--

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
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_concepto`),
  KEY `fk_concepto_cuenta_contable` (`id_cuenta_contable`),
  KEY `fk_concepto_tipo_obligacion` (`id_tipo_obligacion`),
  CONSTRAINT `fk_concepto_cuenta` FOREIGN KEY (`id_cuenta_contable`) REFERENCES `cuentas_contables` (`id_cuenta_contable`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_concepto_tipo_obligacion` FOREIGN KEY (`id_tipo_obligacion`) REFERENCES `tipos_obligacion` (`id_tipo_obligacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conceptos_facturacion`
--

LOCK TABLES `conceptos_facturacion` WRITE;
/*!40000 ALTER TABLE `conceptos_facturacion` DISABLE KEYS */;
INSERT INTO `conceptos_facturacion` VALUES (1,'Administración 1','Cuota ordinaria de administración','FIJO','GENERAL',2,NULL,1,1,'2026-08-19 02:50:51','2026-09-10 02:18:17'),(2,'Parqueadero','Cobro por parqueadero','FIJO','ESPACIO',4,2,0,1,'2026-08-19 02:50:51','2026-09-10 02:18:18'),(3,'Cuota extraordinaria','Cobro extraordinario aprobado por la copropiedad','METRO_CUADRADO','GENERAL',1,NULL,0,1,'2026-08-19 02:50:51','2026-09-10 02:18:17'),(4,'Intereses de mora','Intereses generados por pagos vencidos','PORCENTAJE','CARTERA',1,NULL,0,1,'2026-08-19 02:50:51','2026-09-10 02:18:18'),(5,'Seguro de zonas comunes','Cobro correspondiente al seguro de la copropiedad','COEFICIENTE','GENERAL',6,1,0,1,'2026-08-19 03:17:58','2026-09-10 02:18:17'),(6,'Cuarto útil','Cobro asociado a cuarto útil','FIJO','ESPACIO',NULL,NULL,0,1,'2026-09-08 00:10:47','2026-09-10 02:18:18'),(7,'Depósito','Cobro asociado a depósito','FIJO','ESPACIO',NULL,NULL,0,1,'2026-09-08 00:10:47','2026-09-10 02:18:18'),(8,'Bodega','Cobro asociado a bodega','FIJO','ESPACIO',NULL,NULL,0,1,'2026-09-08 00:10:47','2026-09-10 02:18:18'),(9,'Local comercial','Local comercial','METRO_CUADRADO','PARTICULAR',2,1,1,1,'2026-09-10 23:30:25','2026-09-10 23:30:25');
/*!40000 ALTER TABLE `conceptos_facturacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion_cartera`
--

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

--
-- Dumping data for table `configuracion_cartera`
--

LOCK TABLES `configuracion_cartera` WRITE;
/*!40000 ALTER TABLE `configuracion_cartera` DISABLE KEYS */;
/*!40000 ALTER TABLE `configuracion_cartera` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion_conceptos_espacio`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion_conceptos_espacio`
--

LOCK TABLES `configuracion_conceptos_espacio` WRITE;
/*!40000 ALTER TABLE `configuracion_conceptos_espacio` DISABLE KEYS */;
INSERT INTO `configuracion_conceptos_espacio` VALUES (1,'PARQUEADERO',2,1),(3,'CUARTO_UTIL',6,1),(4,'DEPOSITO',7,1),(5,'BODEGA',8,1);
/*!40000 ALTER TABLE `configuracion_conceptos_espacio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion_financiera`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion_financiera`
--

LOCK TABLES `configuracion_financiera` WRITE;
/*!40000 ALTER TABLE `configuracion_financiera` DISABLE KEYS */;
INSERT INTO `configuracion_financiera` VALUES (1,1,7,15,30,1,'MENSUAL',NULL,1,'Configuraci??n financiera inicial','2026-08-22 20:21:40','2026-08-22 20:21:40');
/*!40000 ALTER TABLE `configuracion_financiera` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion_mora`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion_mora`
--

LOCK TABLES `configuracion_mora` WRITE;
/*!40000 ALTER TABLE `configuracion_mora` DISABLE KEYS */;
/*!40000 ALTER TABLE `configuracion_mora` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion_pagos`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion_pagos`
--

LOCK TABLES `configuracion_pagos` WRITE;
/*!40000 ALTER TABLE `configuracion_pagos` DISABLE KEYS */;
INSERT INTO `configuracion_pagos` VALUES (1,3,2,1,1,'PRUEBA - Administraci??n primero','2026-08-22 18:39:05','2026-08-22 18:39:05'),(2,3,4,2,1,'PRUEBA - Parqueadero segundo','2026-08-22 18:39:05','2026-08-22 18:39:05'),(3,3,6,3,1,'PRUEBA - Multa tercero','2026-08-22 18:39:05','2026-08-22 18:39:05');
/*!40000 ALTER TABLE `configuracion_pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuentas_bancarias`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas_bancarias`
--

LOCK TABLES `cuentas_bancarias` WRITE;
/*!40000 ALTER TABLE `cuentas_bancarias` DISABLE KEYS */;
INSERT INTO `cuentas_bancarias` VALUES (1,'Bancolombia','AHORROS','00012345678','Conjunto Residencial Los Pinos','900123456-7','COP',1,'Cuenta principal para recaudo de administraci??n','2026-08-20 01:08:53','2026-08-20 01:08:53'),(2,'Davivienda','CORRIENTE','000987654321','Conjunto Residencial Los Pinos','900123456-7','COP',1,'Cuenta corriente para pagos y gastos administrativos','2026-08-20 01:08:53','2026-08-20 01:08:53'),(3,'Banco de Bogot??','AHORROS','001234567890','Conjunto Residencial Los Pinos','900123456-7','COP',1,'Cuenta destinada a reservas','2026-08-20 01:08:53','2026-08-20 01:08:53'),(4,'BBVA Colombia','CORRIENTE','002345678901','Conjunto Residencial Los Pinos','900123456-7','COP',1,'Cuenta para operaciones administrativas','2026-08-20 01:08:53','2026-08-20 01:08:53');
/*!40000 ALTER TABLE `cuentas_bancarias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuentas_contables`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas_contables`
--

LOCK TABLES `cuentas_contables` WRITE;
/*!40000 ALTER TABLE `cuentas_contables` DISABLE KEYS */;
INSERT INTO `cuentas_contables` VALUES (1,'4135','Ingresos por cuotas de administraci??n','Ingresos generados por cuotas ordinarias de administraci??n','INGRESO',1,'2026-08-19 02:53:04','2026-08-19 02:53:04'),(2,'4140','Ingresos por parqueaderos','Ingresos generados por cobro de parqueaderos','INGRESO',1,'2026-08-19 02:53:04','2026-08-19 02:53:04'),(3,'4150','Ingresos por cuotas extraordinarias','Ingresos generados por cuotas extraordinarias','INGRESO',1,'2026-08-19 02:53:04','2026-08-19 02:53:04'),(4,'4160','Ingresos por intereses de mora','Intereses generados por obligaciones vencidas','INGRESO',1,'2026-08-19 02:53:04','2026-08-19 02:53:04');
/*!40000 ALTER TABLE `cuentas_contables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `datos_unidad`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `datos_unidad`
--

LOCK TABLES `datos_unidad` WRITE;
/*!40000 ALTER TABLE `datos_unidad` DISABLE KEYS */;
INSERT INTO `datos_unidad` VALUES (12,1,0,'Sierra Campestre P.H.','987654231-8','David Upegui','correo@sierra.com','1234657890',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'logo_1786290150_6a789fe6cc93a.jpeg','reglamento_1786292100_6a78a7846196f.pdf','manual_1786292124_6a78a79c2b8db.pdf','2026-08-09 16:15:24','2026-08-09 16:51:42',1),(13,2,0,'Sierra Campestre P.H.','987654231-8','David Upegui','correo@sierra.com','1234657890',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'logo_1786290150_6a789fe6cc93a.jpeg','reglamento_1786292100_6a78a7846196f.pdf','manual_1786292124_6a78a79c2b8db.pdf','2026-08-09 16:51:42','2026-08-09 16:54:01',1),(14,3,0,'Sierra Campestre P.H.','987654231-8','David Upegui','correo@sierra.com','1234657890',1,8,19,'Calle 54a sur N 54 e 03','Prado',NULL,NULL,'logo_1786290150_6a789fe6cc93a.jpeg','reglamento_1786292100_6a78a7846196f.pdf','manual_1786292124_6a78a79c2b8db.pdf','2026-08-09 16:54:01','2026-08-09 21:12:52',1),(15,4,0,'Sierra Campestre P.H.','987654231-8','David Upegui','correo@sierra.com','1234657890',1,11,20,'Calle 54a sur N 54 e 03','Prado',NULL,NULL,'logo_1786290150_6a789fe6cc93a.jpeg','reglamento_1786292100_6a78a7846196f.pdf','manual_1786292124_6a78a79c2b8db.pdf','2026-08-09 21:12:52','2026-08-09 21:13:03',1),(16,5,0,'Sierra Campestre P.H.','987654231-8','David Upegui','correo@sierra.com','1234657890',1,41,18,'Calle 54a sur N 54 e 03','Prado',NULL,NULL,'logo_1786290150_6a789fe6cc93a.jpeg','reglamento_1786292100_6a78a7846196f.pdf','manual_1786292124_6a78a79c2b8db.pdf','2026-08-09 21:13:03','2026-08-09 21:13:22',1),(17,6,0,'Sierra Campestre P.H.','987654231-8','David Upegui','correo@sierra.com','1234657890',1,41,18,'Calle 54a sur N 54 e 03','San Antonio de Prado',NULL,NULL,'logo_1786290150_6a789fe6cc93a.jpeg','reglamento_1786292100_6a78a7846196f.pdf','manual_1786292124_6a78a79c2b8db.pdf','2026-08-09 21:13:22','2026-08-14 23:23:05',1),(18,7,0,'Sierra Campestre P.H.','987654231','David Upegui','correo@sierra.com','1234657890',1,41,18,'Calle 54a sur N 54 e 03','San Antonio de Prado',NULL,NULL,'logo_1786290150_6a789fe6cc93a.jpeg','reglamento_1786292100_6a78a7846196f.pdf','manual_1786292124_6a78a79c2b8db.pdf','2026-08-14 23:23:05','2026-08-18 01:52:28',1),(19,8,0,'Sierra Campestre P.H.','987654231','David Upegui','correo@sierra.com','1234657890',1,41,18,'Calle 54a sur N 54 e 03','San Antonio de Prado',5,200,'logo_1786290150_6a789fe6cc93a.jpeg','reglamento_1786292100_6a78a7846196f.pdf','manual_1786292124_6a78a79c2b8db.pdf','2026-08-18 01:52:28','2026-08-18 03:06:44',1),(20,9,0,'Sierra Campestre P.H.','987654231','David Upegui','correo@sierra.com','1234657890',1,8,18,'Calle 54a sur N 54 e 03','San Antonio de Prado',5,200,'logo_1786290150_6a789fe6cc93a.jpeg','reglamento_1786292100_6a78a7846196f.pdf','manual_1786292124_6a78a79c2b8db.pdf','2026-08-18 03:06:44','2026-09-05 00:57:00',1),(21,10,1,'Sierra Campestre P.H.','9876542312','David Upegui','correo@sierra.com','1234657890',1,8,18,'Calle 54a sur N 54 e 03','San Antonio de Prado',5,225,'logo_1786290150_6a789fe6cc93a.jpeg','reglamento_1786292100_6a78a7846196f.pdf','manual_1786292124_6a78a79c2b8db.pdf','2026-09-05 00:57:00','2026-09-05 00:57:00',1);
/*!40000 ALTER TABLE `datos_unidad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departamentos`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departamentos`
--

LOCK TABLES `departamentos` WRITE;
/*!40000 ALTER TABLE `departamentos` DISABLE KEYS */;
INSERT INTO `departamentos` VALUES (8,1,'Antioquia','05',1),(10,1,'Amazonas','91',1);
/*!40000 ALTER TABLE `departamentos` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-14 21:36:16
