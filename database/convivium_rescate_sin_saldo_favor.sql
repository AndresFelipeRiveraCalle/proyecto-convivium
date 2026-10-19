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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-14 21:31:23
