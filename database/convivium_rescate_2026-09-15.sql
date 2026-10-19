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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-14 21:27:36
