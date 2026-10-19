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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
-- Table structure for table `aplicaciones_saldo_favor`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aplicaciones_saldo_favor`
--

LOCK TABLES `aplicaciones_saldo_favor` WRITE;
/*!40000 ALTER TABLE `aplicaciones_saldo_favor` DISABLE KEYS */;
/*!40000 ALTER TABLE `aplicaciones_saldo_favor` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
-- Table structure for table `cartera`
--

DROP TABLE IF EXISTS `cartera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cartera` (
  `id_cartera` int(11) NOT NULL AUTO_INCREMENT,
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
  KEY `idx_cartera_unidad` (`id_unidad`),
  KEY `idx_cartera_tipo` (`id_tipo_obligacion`),
  KEY `idx_cartera_periodo` (`periodo`),
  KEY `idx_cartera_vencimiento` (`fecha_vencimiento`),
  KEY `idx_cartera_estado` (`estado`),
  CONSTRAINT `fk_cartera_tipo_obligacion` FOREIGN KEY (`id_tipo_obligacion`) REFERENCES `tipos_obligacion` (`id_tipo_obligacion`),
  CONSTRAINT `fk_cartera_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cartera`
--

LOCK TABLES `cartera` WRITE;
/*!40000 ALTER TABLE `cartera` DISABLE KEYS */;
INSERT INTO `cartera` VALUES (1,3,2,'2026-01-01','Administración ordinaria enero 2026',300000.00,0.00,300000.00,'2026-01-10','PENDIENTE','DATOS DE PRUEBA','2026-08-22 18:38:10','2026-08-22 18:38:10'),(2,3,4,'2026-01-01','Parqueadero enero 2026',100000.00,0.00,100000.00,'2026-01-10','PENDIENTE','DATOS DE PRUEBA','2026-08-22 18:38:10','2026-08-22 18:38:10'),(3,3,2,'2026-02-01','Administración ordinaria febrero 2026',300000.00,0.00,300000.00,'2026-02-10','PENDIENTE','DATOS DE PRUEBA','2026-08-22 18:38:10','2026-08-22 18:38:10'),(4,3,6,'2026-02-01','Multa de prueba',150000.00,0.00,150000.00,'2026-02-15','PENDIENTE','DATOS DE PRUEBA','2026-08-22 18:38:10','2026-08-22 18:38:10');
/*!40000 ALTER TABLE `cartera` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departamentos`
--

LOCK TABLES `departamentos` WRITE;
/*!40000 ALTER TABLE `departamentos` DISABLE KEYS */;
INSERT INTO `departamentos` VALUES (8,1,'Antioquia','05',1),(10,1,'Amazonas','91',1);
/*!40000 ALTER TABLE `departamentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_tipos_unidad`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_tipos_unidad`
--

LOCK TABLES `detalle_tipos_unidad` WRITE;
/*!40000 ALTER TABLE `detalle_tipos_unidad` DISABLE KEYS */;
INSERT INTO `detalle_tipos_unidad` VALUES (1,1,'Torre A',10,200.00,30.00000,1,'','2026-07-03 06:09:09','2026-07-03 06:09:09'),(6,11,'Torre B',100,70.00,3.00000,1,'','2026-08-09 23:32:03','2026-08-09 23:32:03'),(7,2,'Torre C',40,65.00,0.50000,1,'','2026-08-12 03:36:15','2026-08-18 02:13:16'),(8,5,'Oficinas comerciales',70,20.00,0.03000,1,'','2026-08-13 01:16:58','2026-08-13 01:16:58'),(12,10,'Penhouse',5,100.00,0.50000,1,'','2026-08-18 00:11:39','2026-08-18 00:11:39');
/*!40000 ALTER TABLE `detalle_tipos_unidad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos_bancarios`
--

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

--
-- Dumping data for table `documentos_bancarios`
--

LOCK TABLES `documentos_bancarios` WRITE;
/*!40000 ALTER TABLE `documentos_bancarios` DISABLE KEYS */;
INSERT INTO `documentos_bancarios` VALUES (1,2,'20260820_030911_469565acaea666f9_1.pdf','Extracto (5).pdf','uploads/documentos_bancarios/20260820_030911_469565acaea666f9_1.pdf','application/pdf','3c5f168989ec1739b353365ae110d720c66959f5a1657c5bc4840eb00c2c6345','','PROCESADO',NULL,'Movimientos confirmados y guardados correctamente: 21','2026-08-21 22:32:05','2026-08-20 01:09:11','2026-08-22 03:32:05'),(2,1,'20260820_034507_af7d41359d2ab8e8.pdf','Extracto (6).pdf','uploads/documentos_bancarios/20260820_034507_af7d41359d2ab8e8.pdf','application/pdf','a645c136f02e13a4b3c740caa5c6e441a342dfdf8f2d1d05ce36f29383946c6b','','PROCESADO',NULL,'Movimientos confirmados y guardados correctamente: 17','2026-08-21 22:21:28','2026-08-20 01:45:07','2026-08-22 03:21:28'),(3,2,'20260820_062941_662485c64312e343.pdf','004 SST ADSO - Jerarqu??a de controles Mi 6pm.pdf','uploads/documentos_bancarios/20260820_062941_662485c64312e343.pdf','application/pdf','12399cc54f86c18c141608565325c5226db216a30f8583df71288b283e25a153','MANUAL','ERROR',NULL,'El OCR fue realizado correctamente, pero no se encontraron movimientos bancarios con el formato esperado.','2026-08-21 22:40:29','2026-08-20 04:29:41','2026-08-22 03:40:29'),(4,1,'20260820_065549_d2f743712d320586.pdf','Extracto (7).pdf','uploads/documentos_bancarios/20260820_065549_d2f743712d320586.pdf','application/pdf','992dc9a611a249610295de2200f285bc93f6dbecdcb06e24acbb2e15fc0060ce','','PROCESADO',NULL,'Movimientos confirmados y guardados correctamente: 26','2026-08-21 22:41:28','2026-08-20 04:55:49','2026-08-22 03:41:28'),(5,2,'20260822_034052_86e4fc2f81437c46.pdf','Extracto (8).pdf','uploads/documentos_bancarios/20260822_034052_86e4fc2f81437c46.pdf','application/pdf','399a754588ac1962deba45e516db235f5bf46e1443434d51c4aee5bd65d83947','','PROCESADO',NULL,'OCR realizado correctamente. P??ginas: 1. Palabras detectadas: 275.','2026-08-21 20:41:11','2026-08-22 01:40:52','2026-08-22 01:41:11'),(6,2,'20260828_015742_005799730b6c7c8d.pdf','Extracto (10).pdf','uploads/documentos_bancarios/20260828_015742_005799730b6c7c8d.pdf','application/pdf','64ba500e1013956be6846c5e7bb12be8b159cad04d735e5a84378755d05d8708','','PROCESADO',NULL,'Movimientos confirmados y guardados correctamente: 21','2026-08-27 18:58:41','2026-08-27 23:57:42','2026-08-27 23:58:41');
/*!40000 ALTER TABLE `documentos_bancarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `espacios`
--

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

--
-- Dumping data for table `espacios`
--

LOCK TABLES `espacios` WRITE;
/*!40000 ALTER TABLE `espacios` DISABLE KEYS */;
/*!40000 ALTER TABLE `espacios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `espacios_unidad`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `espacios_unidad`
--

LOCK TABLES `espacios_unidad` WRITE;
/*!40000 ALTER TABLE `espacios_unidad` DISABLE KEYS */;
INSERT INTO `espacios_unidad` VALUES (1,1,NULL,'PARQUEADERO','P1',12.00,'2026-09-05','2026-09-05',1,NULL,'2026-09-05 19:31:33',NULL),(2,NULL,9,'PARQUEADERO','P1',12.00,'2026-09-06','2026-09-06',1,NULL,'2026-09-07 00:55:15',NULL),(3,1,9,'PARQUEADERO','P1',12.00,'2026-09-07','2026-09-07',1,NULL,'2026-09-07 01:10:06',NULL),(4,NULL,10,'PARQUEADERO','P1',12.00,'2026-09-08',NULL,1,NULL,'2026-09-07 23:52:16','PARQUEADERO|P1');
/*!40000 ALTER TABLE `espacios_unidad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estados_civiles`
--

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

--
-- Dumping data for table `estados_civiles`
--

LOCK TABLES `estados_civiles` WRITE;
/*!40000 ALTER TABLE `estados_civiles` DISABLE KEYS */;
INSERT INTO `estados_civiles` VALUES (1,'Soltero',1),(2,'Casado',1),(3,'Uni??n Libre',1),(4,'Divorciado',1),(5,'Viudo',1),(8,'Otro',1),(9,'Solitario',1);
/*!40000 ALTER TABLE `estados_civiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `extractos_bancarios`
--

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

--
-- Dumping data for table `extractos_bancarios`
--

LOCK TABLES `extractos_bancarios` WRITE;
/*!40000 ALTER TABLE `extractos_bancarios` DISABLE KEYS */;
INSERT INTO `extractos_bancarios` VALUES (1,2,1,'2026-06-22','2026-06-22','Transferencia BANCOLOMBIA 890924789 Pago A556140 ACRECER SAS                                       PROCESOS ACH','0485','0485',740234.00,'INGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(2,2,1,'2026-06-22','2026-06-22','Transferencia BANCOLOMBIA 890924789 Pago A556139 ACRECER SAS                                       PROCESOS ACH','7019','7019',740234.00,'INGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(3,2,1,'2026-06-22','2026-06-22','Compra NU Compan??a de Financia                                                                     Compras y Pagos PSE','1705','1705',1013690.05,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(4,2,1,'2026-06-22','2026-06-22','Transferencia A Llave Otra Entidad                                                                 Redeban BreB','3604','3604',36000.00,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(5,2,1,'2026-06-22','2026-06-22','Transferencia A Llave Otra Entidad                                                                 Redeban BreB','8725','8725',56450.00,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(6,2,1,'2026-06-23','2026-06-23','Compra WOMPI S.A.S                                                                                 Compras y Pagos PSE','0293','0293',83000.00,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(7,2,1,'2026-06-24','2026-06-24','Transferencia A Llave Otra Entidad                                                                 Redeban BreB','1854','1854',30000.00,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(8,2,1,'2026-06-24','2026-06-24','Transferencia A Llave Otra Entidad                                                                 Redeban BreB','8915','8915',20000.00,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(9,2,1,'2026-06-25','2026-06-25','Transferencia A Llave Otra Entidad                                                                 Redeban BreB','0814','0814',30000.00,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(10,2,1,'2026-06-26','2026-06-26','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','8236','8236',17400.00,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(11,2,1,'2026-06-27','2026-06-27','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','3467','3467',51000.00,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(12,2,1,'2026-06-27','2026-06-27','Compra TIENDA D1 BODEGA ITAGU                                                                      FRANQUICIA MASTER CARD','4501','4501',15300.00,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(13,2,1,'2026-06-29','2026-06-29','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','9065','9065',38000.00,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(14,2,1,'2026-06-29','2026-06-29','Abono por avance tarjeta de credito           App Davivienda','4275','4275',500000.00,'INGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(15,2,1,'2026-06-29','2026-06-29','Compra URBANIZACION SIERRA CAM                Compras y Pagos PSE','5583','5583',580000.00,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(16,2,1,'2026-06-30','2026-06-30','Rendimientos Financieros.                     0000','0000','0000',4.20,'INGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(17,2,1,'2026-06-30','2026-06-30','Gravamen a los Movimientos Financieros        0000','0000','0000',7883.36,'EGRESO','PENDIENTE','Extracto (6).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:21:28','2026-08-22 03:21:28'),(18,4,1,'2026-01-09','2026-01-09','Pago Tarj. Credito N0032060144423171                                                               App Davivienda','3350','3350',186.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(19,4,1,'2026-01-10','2026-01-10','Compra FRISBY Q58                                                                                  FRANQUICIA MASTER CARD','2230','2230',47100.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(20,4,1,'2026-01-10','2026-01-10','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','3235','3235',19900.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(21,4,1,'2026-01-15','2026-01-15','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','4123','4123',9000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(22,4,1,'2026-01-16','2026-01-16','Compra UNE - EPM Telecomunicac                                                                     Compras y Pagos PSE','9398','9398',55088.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(23,4,1,'2026-01-16','2026-01-16','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','8212','8212',9000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(24,4,1,'2026-01-17','2026-01-17','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','3507','3507',11600.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(25,4,1,'2026-01-17','2026-01-17','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','2621','2621',52900.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(26,4,1,'2026-01-17','2026-01-17','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','0860','0860',21000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(27,4,1,'2026-01-18','2026-01-18','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','6060','6060',71000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(28,4,1,'2026-01-21','2026-01-21','Transferencia BANCOLOMBIA 890924789 Pago A504830 ACRECER SAS                                       PROCESOS ACH','4985','4985',711903.00,'INGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(29,4,1,'2026-01-21','2026-01-21','Transferencia BANCOLOMBIA 890924789 Pago A504829 ACRECER SAS                                       PROCESOS ACH','4986','4986',711903.00,'INGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(30,4,1,'2026-01-21','2026-01-21','Compra NU Compan??a de Financia                                                                     Compras y Pagos PSE','1310','1310',1068760.47,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(31,4,1,'2026-01-22','2026-01-22','Compra A Comercio Llave Otra Entidad          Redeban BreB','1943','1943',9000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(32,4,1,'2026-01-22','2026-01-22','Transferencia A Llave Otra Entidad            Redeban BreB','2215','2215',50000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(33,4,1,'2026-01-22','2026-01-22','Transferencia A Llave Otra Entidad            Redeban BreB','9401','9401',100000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(34,4,1,'2026-01-23','2026-01-23','Compra A Comercio Llave Otra Entidad          Redeban BreB','2913','2913',30000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(35,4,1,'2026-01-23','2026-01-23','Compra A Comercio Llave Otra Entidad          Redeban BreB','3327','3327',31900.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(36,4,1,'2026-01-23','2026-01-23','Compra PIZZA HUT                              FRANQUICIA MASTER CARD','9722','9722',36900.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(37,4,1,'2026-01-24','2026-01-24','Retiro en Cajero Automatico.                  ENVIGADO','2749','2749',120000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(38,4,1,'2026-01-24','2026-01-24','Compra TECNIPAGOS S A                         Compras y Pagos PSE','0091','0091',70000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(39,4,1,'2026-01-27','2026-01-27','Transferencia A Llave Otra Entidad            Redeban BreB','9461','9461',7000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(40,4,1,'2026-01-30','2026-01-30','Abono Uso Adelanto De Nomina                  App Davivienda','1055','1055',100000.00,'INGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(41,4,1,'2026-01-30','2026-01-30','Transferencia A Llave Otra Entidad            Redeban BreB','7568','7568',7000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(42,4,1,'2026-01-31','2026-01-31','Rendimientos Financieros.                     0000','0000','0000',20.73,'INGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(43,4,1,'2026-01-31','2026-01-31','Gravamen a los Movimientos Financieros        0000','0000','0000',7309.33,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:29:25','2026-08-22 03:29:25'),(44,1,2,'2026-07-12','2026-07-12','Abono Entidades Financieras Desde Pse                                                              App Transaccional','4812','4812',1500000.00,'INGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(45,1,2,'2026-07-12','2026-07-12','Pago Credito Nro. 5703391900061914                                                                 www.davivienda.com','2435','2435',1495800.00,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(46,1,2,'2026-07-16','2026-07-16','Abono por avance tarjeta de credito                                                                App Davivienda','5924','5924',800000.00,'INGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(47,1,2,'2026-07-16','2026-07-16','Pago Credito Nro. 6600323018849882                                                                 App Davivienda','3642','3642',796000.00,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(48,1,2,'2026-07-22','2026-07-22','Transferencia BANCOLOMBIA 890924789 Pago A566382 ACRECER SAS                                       PROCESOS ACH','0070','0070',738734.00,'INGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(49,1,2,'2026-07-22','2026-07-22','Transferencia BANCOLOMBIA 890924789 Pago A566381 ACRECER SAS                                       PROCESOS ACH','0152','0152',738734.00,'INGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(50,1,2,'2026-07-22','2026-07-22','Pago Credito Nro. 6600323018849882                                                                 App Davivienda','0100','0100',7796.00,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(51,1,2,'2026-07-22','2026-07-22','Pago Tarj. Credito N0032060144423171                                                               App Davivienda','0349','0349',100000.00,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(52,1,2,'2026-07-22','2026-07-22','Compra NU Compan??a de Financia                                                                     Compras y Pagos PSE','9317','9317',1094468.19,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(53,1,2,'2026-07-22','2026-07-22','Transferencia De Otra Entidad A Llave                                                              Redeban BreB','8369','8369',40000.00,'INGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(54,1,2,'2026-07-23','2026-07-23','Transferencia A Llave Otra Entidad                                                                 Redeban BreB','7815','7815',20000.00,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(55,1,2,'2026-07-24','2026-07-24','Compra BANCOLOMBIA                                                                                 Compras y Pagos PSE','1598','1598',20000.00,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(56,1,2,'2026-07-25','2026-07-25','Transferencia A Llave Otra Entidad                                                                 Redeban BreB','1190','1190',178600.00,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(57,1,2,'2026-07-26','2026-07-26','Compra LA MIGUERIA                            FRANQUICIA MASTER CARD','0245','0245',38700.00,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(58,1,2,'2026-07-27','2026-07-27','Compra A Comercio Llave Otra Entidad          Redeban BreB','4984','4984',13500.00,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(59,1,2,'2026-07-27','2026-07-27','Transferencia De Otra Entidad A Llave         Redeban BreB','2049','2049',15000.00,'INGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(60,1,2,'2026-07-27','2026-07-27','Transferencia De Otra Entidad A Llave         Redeban BreB','8617','8617',30000.00,'INGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(61,1,2,'2026-07-27','2026-07-27','Compra WOMPI S.A.S                            Compras y Pagos PSE','1964','1964',80000.00,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(62,1,2,'2026-07-29','2026-07-29','Transferencia A Llave Otra Entidad            Redeban BreB','1935','1935',3900.00,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(63,1,2,'2026-07-31','2026-07-31','Rendimientos Financieros.                     0000','0000','0000',2.88,'INGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(64,1,2,'2026-07-31','2026-07-31','Gravamen a los Movimientos Financieros        0000','0000','0000',15395.05,'EGRESO','PENDIENTE','Extracto (5).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:32:05','2026-08-22 03:32:05'),(65,4,1,'2026-01-09','2026-01-09','Pago Tarj. Credito N0032060144423171                                                               App Davivienda','3350','3350',186.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(66,4,1,'2026-01-10','2026-01-10','Compra FRISBY Q58                                                                                  FRANQUICIA MASTER CARD','2230','2230',47100.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(67,4,1,'2026-01-10','2026-01-10','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','3235','3235',19900.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(68,4,1,'2026-01-15','2026-01-15','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','4123','4123',9000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(69,4,1,'2026-01-16','2026-01-16','Compra UNE - EPM Telecomunicac                                                                     Compras y Pagos PSE','9398','9398',55088.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(70,4,1,'2026-01-16','2026-01-16','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','8212','8212',9000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(71,4,1,'2026-01-17','2026-01-17','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','3507','3507',11600.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(72,4,1,'2026-01-17','2026-01-17','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','2621','2621',52900.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(73,4,1,'2026-01-17','2026-01-17','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','0860','0860',21000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(74,4,1,'2026-01-18','2026-01-18','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','6060','6060',71000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(75,4,1,'2026-01-21','2026-01-21','Transferencia BANCOLOMBIA 890924789 Pago A504830 ACRECER SAS                                       PROCESOS ACH','4985','4985',711903.00,'INGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(76,4,1,'2026-01-21','2026-01-21','Transferencia BANCOLOMBIA 890924789 Pago A504829 ACRECER SAS                                       PROCESOS ACH','4986','4986',711903.00,'INGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(77,4,1,'2026-01-21','2026-01-21','Compra NU Compan??a de Financia                                                                     Compras y Pagos PSE','1310','1310',1068760.47,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(78,4,1,'2026-01-22','2026-01-22','Compra A Comercio Llave Otra Entidad          Redeban BreB','1943','1943',9000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(79,4,1,'2026-01-22','2026-01-22','Transferencia A Llave Otra Entidad            Redeban BreB','2215','2215',50000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(80,4,1,'2026-01-22','2026-01-22','Transferencia A Llave Otra Entidad            Redeban BreB','9401','9401',100000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(81,4,1,'2026-01-23','2026-01-23','Compra A Comercio Llave Otra Entidad          Redeban BreB','2913','2913',30000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(82,4,1,'2026-01-23','2026-01-23','Compra A Comercio Llave Otra Entidad          Redeban BreB','3327','3327',31900.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(83,4,1,'2026-01-23','2026-01-23','Compra PIZZA HUT                              FRANQUICIA MASTER CARD','9722','9722',36900.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(84,4,1,'2026-01-24','2026-01-24','Retiro en Cajero Automatico.                  ENVIGADO','2749','2749',120000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(85,4,1,'2026-01-24','2026-01-24','Compra TECNIPAGOS S A                         Compras y Pagos PSE','0091','0091',70000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(86,4,1,'2026-01-27','2026-01-27','Transferencia A Llave Otra Entidad            Redeban BreB','9461','9461',7000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(87,4,1,'2026-01-30','2026-01-30','Abono Uso Adelanto De Nomina                  App Davivienda','1055','1055',100000.00,'INGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(88,4,1,'2026-01-30','2026-01-30','Transferencia A Llave Otra Entidad            Redeban BreB','7568','7568',7000.00,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(89,4,1,'2026-01-31','2026-01-31','Rendimientos Financieros.                     0000','0000','0000',20.73,'INGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(90,4,1,'2026-01-31','2026-01-31','Gravamen a los Movimientos Financieros        0000','0000','0000',7309.33,'EGRESO','PENDIENTE','Extracto (7).pdf','Movimiento importado desde extracto bancario.','2026-08-22 03:41:28','2026-08-22 03:41:28'),(91,6,2,'2026-04-03','2026-04-03','Pago Tarj. Credito N4283920012626346                                                               App Davivienda','0528','0528',464000.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(92,6,2,'2026-04-17','2026-04-17','Abono Entidades Financieras Desde Pse                                                              App Transaccional','5405','5405',100000.00,'INGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(93,6,2,'2026-04-18','2026-04-18','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','6641','6641',27000.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(94,6,2,'2026-04-20','2026-04-20','Compra EDS ITAGUI FR                                                                               FRANQUICIA MASTER CARD','2681','2681',31977.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(95,6,2,'2026-04-21','2026-04-21','Transferencia BANCOLOMBIA 890924789 Pago A535624 ACRECER SAS                                       PROCESOS ACH','1060','1060',644303.00,'INGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(96,6,2,'2026-04-21','2026-04-21','Transferencia BANCOLOMBIA 890924789 Pago A535623 ACRECER SAS                                       PROCESOS ACH','1061','1061',644303.00,'INGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(97,6,2,'2026-04-21','2026-04-21','Compra NU Companía de Financia                                                                     Compras y Pagos PSE','1114','1114',944063.78,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(98,6,2,'2026-04-21','2026-04-21','Transferencia A Llave Otra Entidad                                                                 Redeban BreB','7986','7986',100000.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(99,6,2,'2026-04-22','2026-04-22','Compra A Comercio Llave Otra Entidad                                                               Redeban BreB','5634','5634',8500.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(100,6,2,'2026-04-22','2026-04-22','Transferencia A Llave Otra Entidad                                                                 Redeban BreB','1294','1294',10500.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(101,6,2,'2026-04-22','2026-04-22','Pago A Llave De Comercio                                                                           Redeban BreB','7126','7126',8400.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(102,6,2,'2026-04-23','2026-04-23','Compra BANCOLOMBIA                                                                                 Compras y Pagos PSE','3137','3137',30000.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(103,6,2,'2026-04-25','2026-04-25','Compra BOLD SA*ALDIMARK                                                                            FRANQUICIA MASTER CARD','3112','3112',4000.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(104,6,2,'2026-04-25','2026-04-25','Compra EL LAB DE CAFE TESORO                  FRANQUICIA MASTER CARD','4835','4835',34600.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(105,6,2,'2026-04-25','2026-04-25','Compra BANCOLOMBIA                            Compras y Pagos PSE','6880','6880',30000.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(106,6,2,'2026-04-26','2026-04-26','Compra BANCOLOMBIA                            Compras y Pagos PSE','9705','9705',50000.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(107,6,2,'2026-04-27','2026-04-27','Transferencia A Llave Otra Entidad            Redeban BreB','2031','2031',35000.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(108,6,2,'2026-04-28','2026-04-28','Compra BANCOLOMBIA                            Compras y Pagos PSE','7063','7063',20000.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(109,6,2,'2026-04-28','2026-04-28','Transferencia A Llave Otra Entidad            Redeban BreB','4475','4475',20000.00,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(110,6,2,'2026-04-30','2026-04-30','Rendimientos Financieros.                     0000','0000','0000',7.17,'INGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41'),(111,6,2,'2026-04-30','2026-04-30','Gravamen a los Movimientos Financieros        0000','0000','0000',7272.15,'EGRESO','PENDIENTE','Extracto (10).pdf','Movimiento importado desde extracto bancario.','2026-08-27 23:58:41','2026-08-27 23:58:41');
/*!40000 ALTER TABLE `extractos_bancarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facturas`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facturas`
--

LOCK TABLES `facturas` WRITE;
/*!40000 ALTER TABLE `facturas` DISABLE KEYS */;
/*!40000 ALTER TABLE `facturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facturas_detalle`
--

DROP TABLE IF EXISTS `facturas_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facturas_detalle` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_factura` int(11) NOT NULL,
  `id_concepto` int(11) NOT NULL,
  `id_tarifa` int(11) DEFAULT NULL,
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
  CONSTRAINT `fk_detalle_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos_facturacion` (`id_concepto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_factura` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id_factura`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_tarifa` FOREIGN KEY (`id_tarifa`) REFERENCES `tarifas_facturacion` (`id_tarifa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facturas_detalle`
--

LOCK TABLES `facturas_detalle` WRITE;
/*!40000 ALTER TABLE `facturas_detalle` DISABLE KEYS */;
/*!40000 ALTER TABLE `facturas_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facturas_saldos`
--

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

--
-- Dumping data for table `facturas_saldos`
--

LOCK TABLES `facturas_saldos` WRITE;
/*!40000 ALTER TABLE `facturas_saldos` DISABLE KEYS */;
/*!40000 ALTER TABLE `facturas_saldos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `generos`
--

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

--
-- Dumping data for table `generos`
--

LOCK TABLES `generos` WRITE;
/*!40000 ALTER TABLE `generos` DISABLE KEYS */;
INSERT INTO `generos` VALUES (1,'M','Masculino',1),(2,'F','Femenino',1),(3,'O','Otro',1),(4,'N','Prefiero no informar',1),(5,'','Binario',1);
/*!40000 ALTER TABLE `generos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `horario_zona`
--

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

--
-- Dumping data for table `horario_zona`
--

LOCK TABLES `horario_zona` WRITE;
/*!40000 ALTER TABLE `horario_zona` DISABLE KEYS */;
INSERT INTO `horario_zona` VALUES (1,1,1,'08:00:00','22:00:00'),(2,1,2,'08:00:00','22:00:00'),(3,1,3,'08:00:00','22:00:00'),(4,1,4,'08:00:00','22:00:00'),(5,1,5,'08:00:00','23:00:00'),(6,1,6,'09:00:00','23:00:00'),(7,1,7,'09:00:00','18:00:00'),(8,2,1,'05:00:00','22:00:00'),(9,2,2,'05:00:00','22:00:00'),(10,2,3,'05:00:00','22:00:00'),(11,2,4,'05:00:00','22:00:00'),(12,2,5,'05:00:00','22:00:00'),(13,2,6,'07:00:00','20:00:00'),(14,2,7,'07:00:00','14:00:00'),(15,3,1,'06:00:00','21:00:00'),(16,3,2,'06:00:00','21:00:00'),(17,3,3,'06:00:00','21:00:00'),(18,3,4,'06:00:00','21:00:00'),(19,3,5,'06:00:00','21:00:00'),(20,3,6,'08:00:00','20:00:00'),(21,3,7,'08:00:00','18:00:00'),(22,4,1,'08:00:00','18:00:00'),(23,4,2,'08:00:00','18:00:00'),(24,4,3,'08:00:00','18:00:00'),(25,4,4,'08:00:00','18:00:00'),(26,4,5,'08:00:00','18:00:00'),(27,4,6,'09:00:00','18:00:00'),(28,4,7,'09:00:00','17:00:00'),(29,5,1,'16:00:00','22:00:00'),(30,5,2,'16:00:00','22:00:00'),(31,5,3,'16:00:00','22:00:00'),(32,5,4,'16:00:00','22:00:00'),(33,5,5,'16:00:00','23:00:00'),(34,5,6,'08:00:00','23:00:00'),(35,5,7,'08:00:00','20:00:00'),(36,6,1,'10:00:00','20:00:00'),(37,6,2,'10:00:00','20:00:00'),(38,6,3,'10:00:00','20:00:00'),(39,6,4,'10:00:00','20:00:00'),(40,6,5,'10:00:00','22:00:00'),(41,6,6,'09:00:00','22:00:00'),(42,6,7,'09:00:00','18:00:00'),(43,7,1,'14:00:00','22:00:00'),(44,7,2,'14:00:00','22:00:00'),(45,7,3,'14:00:00','22:00:00'),(46,7,4,'14:00:00','22:00:00'),(47,7,5,'14:00:00','23:00:00'),(48,7,6,'10:00:00','23:00:00'),(49,7,7,'10:00:00','20:00:00'),(50,8,1,'08:00:00','19:00:00'),(51,8,2,'08:00:00','19:00:00'),(52,8,3,'08:00:00','19:00:00'),(53,8,4,'08:00:00','19:00:00'),(54,8,5,'08:00:00','19:00:00'),(55,8,6,'08:00:00','20:00:00'),(56,8,7,'08:00:00','18:00:00'),(57,9,1,'07:00:00','20:00:00'),(58,9,2,'07:00:00','20:00:00'),(59,9,3,'07:00:00','20:00:00'),(60,9,4,'07:00:00','20:00:00'),(61,9,5,'07:00:00','20:00:00'),(62,9,6,'08:00:00','14:00:00'),(63,10,1,'10:00:00','20:00:00'),(64,10,2,'10:00:00','20:00:00'),(65,10,3,'10:00:00','20:00:00'),(66,10,4,'10:00:00','20:00:00'),(67,10,5,'10:00:00','22:00:00'),(68,10,6,'09:00:00','18:00:00');
/*!40000 ALTER TABLE `horario_zona` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intereses_cartera`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intereses_cartera`
--

LOCK TABLES `intereses_cartera` WRITE;
/*!40000 ALTER TABLE `intereses_cartera` DISABLE KEYS */;
/*!40000 ALTER TABLE `intereses_cartera` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantenimiento`
--

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

--
-- Dumping data for table `mantenimiento`
--

LOCK TABLES `mantenimiento` WRITE;
/*!40000 ALTER TABLE `mantenimiento` DISABLE KEYS */;
INSERT INTO `mantenimiento` VALUES (1,1,2,'Fuga de agua en el ba??o principal del segundo piso.','alta','Juan P??rez (Plomero)','2026-05-31 19:08:03','2026-06-01 08:00:00','2026-06-15 18:31:00','pendiente',150.00,'Se requiere cambiar la tuber??a principal de PVC.','foto_evidencia_01.jpg','2026-06-15 18:31:12'),(2,1,2,'Prueba','baja',NULL,'2026-06-17 19:31:54',NULL,'2026-06-17 19:31:00','solucionado',NULL,NULL,'uploads/1781742714_user.png','2026-06-18 18:50:48');
/*!40000 ALTER TABLE `mantenimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_inventario`
--

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

--
-- Dumping data for table `movimientos_inventario`
--

LOCK TABLES `movimientos_inventario` WRITE;
/*!40000 ALTER TABLE `movimientos_inventario` DISABLE KEYS */;
INSERT INTO `movimientos_inventario` VALUES (1,1,7,'entrada',10,'compra','2026-08-24 21:03:27');
/*!40000 ALTER TABLE `movimientos_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `obligaciones`
--

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

--
-- Dumping data for table `obligaciones`
--

LOCK TABLES `obligaciones` WRITE;
/*!40000 ALTER TABLE `obligaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `obligaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ocupaciones`
--

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

--
-- Dumping data for table `ocupaciones`
--

LOCK TABLES `ocupaciones` WRITE;
/*!40000 ALTER TABLE `ocupaciones` DISABLE KEYS */;
INSERT INTO `ocupaciones` VALUES (1,'Empleado',1),(2,'Independiente',1),(3,'Estudiante',1),(4,'Jubilado',1),(5,'Ama de casa',1),(6,'Ingeniero',1);
/*!40000 ALTER TABLE `ocupaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
INSERT INTO `pagos` VALUES (1,1,NULL,'2026-08-25',200000.01,'TRANSFERENCIA','MANUAL','PENDIENTE',NULL,'asd22',NULL,NULL,NULL,'REGISTRADO','2026-08-25 02:40:26','2026-08-25 02:40:26'),(2,1,NULL,'2026-08-25',250000.01,'TRANSFERENCIA','MANUAL','PENDIENTE',NULL,'asd221',NULL,NULL,NULL,'REGISTRADO','2026-08-25 02:41:54','2026-08-25 03:06:04'),(3,2,NULL,'2026-08-25',300000.00,'TRANSFERENCIA','MANUAL','PENDIENTE',NULL,'XABS231231',NULL,NULL,NULL,'REGISTRADO','2026-08-25 02:42:15','2026-08-25 02:42:15');
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paises`
--

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

--
-- Dumping data for table `paises`
--

LOCK TABLES `paises` WRITE;
/*!40000 ALTER TABLE `paises` DISABLE KEYS */;
INSERT INTO `paises` VALUES (1,'Colombia',1),(21,'Ecuador',1);
/*!40000 ALTER TABLE `paises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parentescos`
--

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

--
-- Dumping data for table `parentescos`
--

LOCK TABLES `parentescos` WRITE;
/*!40000 ALTER TABLE `parentescos` DISABLE KEYS */;
INSERT INTO `parentescos` VALUES (1,'Esposo(a)',1),(2,'Hijo(a)',1),(3,'Padre',1),(4,'Madre',1),(5,'Hermanoo',1),(6,'Visitante',1);
/*!40000 ALTER TABLE `parentescos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parqueaderos`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parqueaderos`
--

LOCK TABLES `parqueaderos` WRITE;
/*!40000 ALTER TABLE `parqueaderos` DISABLE KEYS */;
INSERT INTO `parqueaderos` VALUES (1,'01',1,'PRIVADO','Sotano 3','OCUPADO',NULL,1,'2026-08-28 20:35:41'),(2,'02',NULL,'VISITANTES','Sotano 2','DISPONIBLE',NULL,1,'2026-08-28 20:39:32'),(3,'03',1,'MOTOS','Sotano 1','OCUPADO','Prueba',1,'2026-08-28 20:40:26'),(4,'04',2,'PRIVADO','Sotano 2','OCUPADO',NULL,1,'2026-08-28 20:43:25'),(5,'05',NULL,'VISITANTES','Sotano 1','DISPONIBLE',NULL,1,'2026-08-28 20:53:27'),(6,'06',4,'PRIVADO','Sotano 1','OCUPADO',NULL,1,'2026-08-28 20:58:03'),(7,'07',2,'MOTOS',NULL,'DISPONIBLE',NULL,1,'2026-08-28 21:15:46'),(8,'08',1,'BICICLETAS','Sotano 3','OCUPADO',NULL,1,'2026-08-29 19:10:36');
/*!40000 ALTER TABLE `parqueaderos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pqrs`
--

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

--
-- Dumping data for table `pqrs`
--

LOCK TABLES `pqrs` WRITE;
/*!40000 ALTER TABLE `pqrs` DISABLE KEYS */;
/*!40000 ALTER TABLE `pqrs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reserva_zona`
--

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

--
-- Dumping data for table `reserva_zona`
--

LOCK TABLES `reserva_zona` WRITE;
/*!40000 ALTER TABLE `reserva_zona` DISABLE KEYS */;
/*!40000 ALTER TABLE `reserva_zona` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `residente`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `residente`
--

LOCK TABLES `residente` WRITE;
/*!40000 ALTER TABLE `residente` DISABLE KEYS */;
INSERT INTO `residente` VALUES (1,1,3,'residente',1,'2026-08-17 00:00:00',NULL,1),(2,1,4,'residente',0,'2026-08-18 00:00:00',NULL,1),(3,1,5,'residente',0,'2026-08-16 00:00:00',NULL,1),(4,1,6,'propietario',0,'2026-08-17 00:00:00','2026-09-07 23:59:59',0),(5,1,7,'propietario',1,'2026-08-17 00:00:00',NULL,1),(6,4,28,'propietario',0,'2026-06-23 22:56:59',NULL,1),(7,4,33,'inquilino',0,'2026-06-23 22:56:59',NULL,1),(8,5,29,'propietario',0,'2026-06-23 22:56:59',NULL,1),(9,5,34,'inquilino',0,'2026-06-23 22:56:59',NULL,1),(10,6,30,'propietario',0,'2026-06-23 22:56:59',NULL,1),(11,6,35,'inquilino',0,'2026-06-23 22:56:59',NULL,1),(12,9,31,'propietario',0,'2026-06-23 22:56:59',NULL,1),(13,1,8,'inquilino',1,'2026-08-17 00:00:00',NULL,1),(14,3,8,'propietario',1,'2026-09-08 00:00:00',NULL,1);
/*!40000 ALTER TABLE `residente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

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

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` VALUES (1,'Administrador');
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saldo_favor`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saldo_favor`
--

LOCK TABLES `saldo_favor` WRITE;
/*!40000 ALTER TABLE `saldo_favor` DISABLE KEYS */;
/*!40000 ALTER TABLE `saldo_favor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tarifas_facturacion`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tarifas_facturacion`
--

LOCK TABLES `tarifas_facturacion` WRITE;
/*!40000 ALTER TABLE `tarifas_facturacion` DISABLE KEYS */;
INSERT INTO `tarifas_facturacion` VALUES (1,1,1,'Admin',500000.00,'2026-08-18',NULL,0,'Por que si','2026-08-19 04:11:42','2026-09-05 00:33:50'),(2,3,6,'Arreglo Fachada',3000.00,'2026-08-19','2027-12-31',0,'Se cobra en una sola cuota','2026-08-19 23:10:17','2026-09-10 02:01:16'),(3,1,1,'Administración Septiembre 2026',500000.00,'2026-09-01',NULL,1,'Tarifa mensual de administraci??n','2026-08-23 03:30:51','2026-09-04 03:53:50'),(4,4,12,'Mora',10.00,'2026-09-01','2026-10-10',1,NULL,'2026-09-04 03:31:17','2026-09-04 03:52:38'),(5,9,1,'Admin',5000.00,'2026-01-01',NULL,1,NULL,'2026-09-10 23:32:40','2026-09-10 23:32:40'),(6,9,6,'Admin',5000.00,'2026-01-01',NULL,1,NULL,'2026-09-10 23:32:40','2026-09-10 23:32:40'),(7,9,7,'Admin',5000.00,'2026-01-01',NULL,1,NULL,'2026-09-10 23:32:40','2026-09-10 23:32:40'),(8,9,8,'Admin',5000.00,'2026-01-01',NULL,1,NULL,'2026-09-10 23:32:40','2026-09-10 23:32:40'),(9,9,12,'Admin',5000.00,'2026-01-01',NULL,1,NULL,'2026-09-10 23:32:40','2026-09-10 23:32:40'),(10,9,13,'Admin',5000.00,'2026-01-01',NULL,1,NULL,'2026-09-10 23:32:40','2026-09-10 23:32:40');
/*!40000 ALTER TABLE `tarifas_facturacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasas_interes`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasas_interes`
--

LOCK TABLES `tasas_interes` WRITE;
/*!40000 ALTER TABLE `tasas_interes` DISABLE KEYS */;
INSERT INTO `tasas_interes` VALUES (1,'Tasa de prueba febrero 2026',24.000000,2.000000,'2026-02-01','2026-02-28','PRUEBA',1,'Registro temporal para pruebas del m??dulo de cartera','2026-08-22 18:53:38','2026-08-22 18:53:38');
/*!40000 ALTER TABLE `tasas_interes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_comunicacion`
--

DROP TABLE IF EXISTS `tipo_comunicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_comunicacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_comunicacion`
--

LOCK TABLES `tipo_comunicacion` WRITE;
/*!40000 ALTER TABLE `tipo_comunicacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `tipo_comunicacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_agrupacion`
--

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

--
-- Dumping data for table `tipos_agrupacion`
--

LOCK TABLES `tipos_agrupacion` WRITE;
/*!40000 ALTER TABLE `tipos_agrupacion` DISABLE KEYS */;
INSERT INTO `tipos_agrupacion` VALUES (1,'Torre','Agrupaci??n vertical de unidades.',1,'2026-08-10 22:39:27'),(2,'Bloque','Agrupaci??n de unidades por bloques.',1,'2026-08-10 22:39:27'),(3,'Manzana','Agrupaci??n de viviendas por manzanas.',1,'2026-08-10 22:39:27'),(4,'Etapa','Agrupaci??n correspondiente a una etapa del proyecto.',1,'2026-08-10 22:39:27'),(5,'Sector','Agrupaci??n por sectores de la copropiedad.',1,'2026-08-10 22:39:27'),(6,'Edificio','Agrupaci??n de unidades dentro de un edificio.',1,'2026-08-10 22:39:27'),(7,'Zona','Agrupaci??n por zonas espec??ficas.',1,'2026-08-10 22:39:27'),(8,'Conjunto','Agrupaci??n de unidades dentro de un conjunto.',1,'2026-08-10 22:39:27');
/*!40000 ALTER TABLE `tipos_agrupacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_copropiedad`
--

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

--
-- Dumping data for table `tipos_copropiedad`
--

LOCK TABLES `tipos_copropiedad` WRITE;
/*!40000 ALTER TABLE `tipos_copropiedad` DISABLE KEYS */;
INSERT INTO `tipos_copropiedad` VALUES (1,'Residencial (Torres)','Edificios o torres de apartamentos multifamiliares.'),(2,'Residencial (Casas)','Conjuntos cerrados, urbanizaciones o condominios de casas.'),(3,'Comercial','Centros comerciales, pasajes comerciales o edificios de oficinas y consultorios.'),(4,'Industrial','Parques industriales, centros de log??stica y bodegas.'),(5,'Mixto','Proyectos que combinan ??reas comerciales en niveles inferiores y residencial en superiores.'),(6,'Vacacional / Tur??stico','Condominios de playa o campo, aparthoteles y edificios de rentas cortas.'),(7,'Macrocopropiedad','Ciudadelas o macro-proyectos que agrupan m??ltiples conjuntos independientes.');
/*!40000 ALTER TABLE `tipos_copropiedad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_documento`
--

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

--
-- Dumping data for table `tipos_documento`
--

LOCK TABLES `tipos_documento` WRITE;
/*!40000 ALTER TABLE `tipos_documento` DISABLE KEYS */;
INSERT INTO `tipos_documento` VALUES (1,'CC','C??dula de ciudadan??a',1),(2,'TI','Tarjeta de identidad',1),(3,'CE','C??dula de extranjer??a',1),(4,'PA','Pasaporte',1),(5,'NIT','N??mero de Identificaci??n Tributaria',1),(6,'RUT','Registro Unico',1);
/*!40000 ALTER TABLE `tipos_documento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_espacio`
--

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

--
-- Dumping data for table `tipos_espacio`
--

LOCK TABLES `tipos_espacio` WRITE;
/*!40000 ALTER TABLE `tipos_espacio` DISABLE KEYS */;
/*!40000 ALTER TABLE `tipos_espacio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_obligacion`
--

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

--
-- Dumping data for table `tipos_obligacion`
--

LOCK TABLES `tipos_obligacion` WRITE;
/*!40000 ALTER TABLE `tipos_obligacion` DISABLE KEYS */;
INSERT INTO `tipos_obligacion` VALUES (1,'Intereses','Intereses de mora generados por obligaciones vencidas',1,0,1,'2026-08-21 23:15:56','2026-08-21 23:15:56'),(2,'Administración ordinaria','Cuota ordinaria de administración',2,1,1,'2026-08-21 23:15:56','2026-08-21 23:15:56'),(3,'Administración extraordinaria','Cuota extraordinaria aprobada por la copropiedad',3,1,1,'2026-08-21 23:15:56','2026-08-21 23:15:56'),(4,'Parqueadero','Cobro asociado al uso o asignación de parqueadero',4,0,1,'2026-08-21 23:15:56','2026-08-21 23:15:56'),(5,'Zona común','Cobros asociados a zonas o servicios comunes',5,0,1,'2026-08-21 23:15:56','2026-08-21 23:15:56'),(6,'Multa','Sanciones económicas aplicadas a la unidad',6,0,1,'2026-08-21 23:15:56','2026-08-21 23:15:56'),(7,'Otro','Otros conceptos cobrables',7,0,1,'2026-08-21 23:15:56','2026-08-21 23:15:56');
/*!40000 ALTER TABLE `tipos_obligacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_persona`
--

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

--
-- Dumping data for table `tipos_persona`
--

LOCK TABLES `tipos_persona` WRITE;
/*!40000 ALTER TABLE `tipos_persona` DISABLE KEYS */;
INSERT INTO `tipos_persona` VALUES (1,'Propietario',1),(2,'Residente',1),(3,'Arrendatario',1),(4,'Empleado',1),(5,'Proveedor',1),(6,'Visitante',1);
/*!40000 ALTER TABLE `tipos_persona` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_vivienda`
--

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

--
-- Dumping data for table `tipos_vivienda`
--

LOCK TABLES `tipos_vivienda` WRITE;
/*!40000 ALTER TABLE `tipos_vivienda` DISABLE KEYS */;
INSERT INTO `tipos_vivienda` VALUES (1,NULL,'Apartamento','Unidad residencial en edificio',1,1,'2026-07-01 19:20:04'),(2,NULL,'Casa','Casa independiente',1,2,'2026-07-01 19:20:04'),(3,NULL,'Casa Campestre','Vivienda ubicada en zona rural',1,5,'2026-07-01 19:20:04'),(4,NULL,'Local Comercial','Local para actividad comercial',1,5,'2026-07-01 19:20:04'),(5,NULL,'Oficina','Espacio destinado a oficinas',1,5,'2026-07-01 19:20:04'),(6,NULL,'Consultorio','Consultorio médico o profesional',1,5,'2026-07-01 19:20:04'),(7,NULL,'Bodega','Bodega o depósito',1,5,'2026-07-01 19:20:04'),(8,NULL,'Parqueadero','Parqueadero independiente',1,3,'2026-07-01 19:20:04'),(9,NULL,'Cuarto Útil','Depósito o cuarto útil',1,5,'2026-07-01 19:20:04'),(10,NULL,'Penthouse','Apartamento en el último piso',1,5,'2026-07-01 19:20:04'),(11,NULL,'Apartaestudio','Vivienda de un solo ambiente',1,5,'2026-07-01 19:20:04'),(12,NULL,'Lote','Terreno sin construir',1,5,'2026-07-01 19:20:04');
/*!40000 ALTER TABLE `tipos_vivienda` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unidades`
--

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

--
-- Dumping data for table `unidades`
--

LOCK TABLES `unidades` WRITE;
/*!40000 ALTER TABLE `unidades` DISABLE KEYS */;
INSERT INTO `unidades` VALUES (1,1,'101','Familia','1',30.00,20.00000000,'','','2026-07-25 17:57:09','2026-07-25 17:57:09',1),(2,1,'102','Familia','1',10.00,0.00180000,'','','2026-07-27 17:33:19','2026-07-27 17:33:19',1),(3,6,'202','Familia','3',30.00,0.40000000,'','','2026-08-14 18:27:08','2026-08-14 18:27:08',1);
/*!40000 ALTER TABLE `unidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usos_vivienda`
--

DROP TABLE IF EXISTS `usos_vivienda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usos_vivienda` (
  `id_uso` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id_uso`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usos_vivienda`
--

LOCK TABLES `usos_vivienda` WRITE;
/*!40000 ALTER TABLE `usos_vivienda` DISABLE KEYS */;
INSERT INTO `usos_vivienda` VALUES (1,'Residencial'),(2,'Comercial'),(3,'Mixto'),(4,'Industrial'),(5,'Institucional');
/*!40000 ALTER TABLE `usos_vivienda` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `uq_usuario_documento` (`numero_documento`),
  KEY `rol_id` (`rol_id`),
  KEY `fk_usuario_pais` (`id_pais`),
  KEY `fk_usuario_departamento` (`id_departamento`),
  KEY `fk_usuario_ciudad` (`id_ciudad`),
  KEY `fk_usuario_genero` (`id_genero`),
  KEY `fk_usuario_tipo_documento` (`id_tipo_documento`),
  CONSTRAINT `fk_usuario_ciudad` FOREIGN KEY (`id_ciudad`) REFERENCES `ciudades` (`id_ciudad`),
  CONSTRAINT `fk_usuario_departamento` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`),
  CONSTRAINT `fk_usuario_genero` FOREIGN KEY (`id_genero`) REFERENCES `generos` (`id_genero`),
  CONSTRAINT `fk_usuario_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`),
  CONSTRAINT `fk_usuario_tipo_documento` FOREIGN KEY (`id_tipo_documento`) REFERENCES `tipos_documento` (`id_tipo_documento`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (2,'Carlos','G??mez',NULL,'712345678','carlos.gomez@ejemplo.com','3001234567',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-08-02 18:45:01'),(3,'Leidy','Gallo',1,'72356897','leidyudea23@gmail.com','3164910858','3215646987','2025-12-05',1,NULL,NULL,'Calle 54a sur N 54 e 03',1,NULL,15,NULL,NULL,1,NULL,'2026-08-02 18:45:01'),(4,'Sara','Lopez',1,'654987321','sara@gmail.com','3164910858','32654987','2021-12-20',2,NULL,NULL,'Calle 54a sur N 54 e',1,8,15,'uploads/personas/654987321.png',NULL,1,NULL,'2026-08-02 18:45:01'),(5,'Andres','Perez',1,'72356899','andres@gmail.com','98756431','321654987','1985-09-30',1,NULL,NULL,'2132as1da21d',NULL,8,19,'uploads/personas/72356899.png',NULL,1,NULL,'2026-08-02 18:45:01'),(6,'Nicolle','Velez',2,'9876543122','Nicolle@gmail.com','12346578','31654987','2021-06-21',2,NULL,NULL,'32165asd',1,8,19,'uploads/personas/9876543122.png',NULL,1,NULL,'2026-08-02 18:45:01'),(7,'Cristian','Castrillo',1,'23456798','cristian@correo.com','987654123','465132798','2002-08-20',1,2,1,'as32d2sa31d',1,8,19,'uploads/personas/23456798.png',NULL,1,NULL,'2026-08-02 19:10:03'),(8,'Juan Gabriel','Henao',1,'9765431456','vago@correo.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'uploads/personas/',NULL,1,NULL,'2026-08-12 20:11:55'),(9,'Laura','Garcia',1,'152030','laura@correo.com','321654987','321654987','2005-07-01',2,1,0,'Calle 54a sur N 55 e 65',NULL,NULL,NULL,'uploads/personas/152030.jpg',NULL,1,NULL,'2026-08-18 21:18:58'),(10,'Catalina','Velasquez',1,'784523','cata@correo.com','1112346579',NULL,'2000-05-16',2,2,NULL,NULL,NULL,NULL,NULL,'uploads/personas/784523.jpeg',NULL,1,NULL,'2026-08-18 21:33:58');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehiculos`
--

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

--
-- Dumping data for table `vehiculos`
--

LOCK TABLES `vehiculos` WRITE;
/*!40000 ALTER TABLE `vehiculos` DISABLE KEYS */;
INSERT INTO `vehiculos` VALUES (1,'ABC123','AUTOMOVIL','Mazda','2021','blanco',NULL,1,1,'2026-08-06 21:51:00',NULL,NULL,'2026-08-28 21:51:08','2026-08-28 21:51:08'),(2,'JOV30H4','BICICLETA','Mazda','2021','blanco',NULL,NULL,1,'2026-08-28 21:54:00',NULL,NULL,'2026-08-28 21:54:49','2026-08-30 16:10:39'),(3,'SSS2223','BICICLETA','Mazda','31312','asda',NULL,2,1,'2026-07-30 21:57:00',NULL,NULL,'2026-08-28 21:57:41','2026-08-30 16:09:38');
/*!40000 ALTER TABLE `vehiculos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zona_comun`
--

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

--
-- Dumping data for table `zona_comun`
--

LOCK TABLES `zona_comun` WRITE;
/*!40000 ALTER TABLE `zona_comun` DISABLE KEYS */;
INSERT INTO `zona_comun` VALUES (1,'Salón Social','Espacio para eventos privados, incluye mesas, sillas y cocineta.',50),(2,'Gimnasio','Equipado con m??quinas de cardio y pesas. Uso exclusivo residentes.',15),(3,'Piscina de Adultos','Piscina climatizada. Se requiere traje de ba??o adecuado.',30),(4,'Piscina de Ni??os','Piscina de baja profundidad con supervisi??n requerida.',12),(5,'Cancha Sint??tica','Cancha de f??tbol 5 con iluminaci??n nocturna.',10),(6,'Zona BBQ','Kiosko con parrilla a carb??n y lavaplatos.',8),(7,'Sala de Cine','Proyector HD y sonido envolvente. Reservar con antelaci??n.',12),(8,'Parque Infantil','Juegos de madera y arenero para menores de 12 a??os.',25),(9,'Coworking','Sala con Wi-Fi de alta velocidad y conexiones el??ctricas.',10),(10,'Sauna','Zona h??meda para relajaci??n. Uso m??ximo 30 min por persona.',6);
/*!40000 ALTER TABLE `zona_comun` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'convivium'
--

--
-- Dumping routines for database 'convivium'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-14 22:30:43
