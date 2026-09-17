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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (2,'Carlos','G??mez',NULL,'712345678','carlos.gomez@ejemplo.com','3001234567',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-08-02 18:45:01'),(3,'Leidy','Gallo',1,'72356897','leidyudea23@gmail.com','3164910858','3215646987','2025-12-05',1,NULL,NULL,'Calle 54a sur N 54 e 03',1,NULL,15,NULL,NULL,1,NULL,'2026-08-02 18:45:01'),(4,'Sara','Lopez',1,'654987321','sara@gmail.com','3164910858','32654987','2021-12-20',2,NULL,NULL,'Calle 54a sur N 54 e',1,8,15,'uploads/personas/654987321.png',NULL,1,NULL,'2026-08-02 18:45:01'),(5,'Andres','Perez',1,'72356899','andres@gmail.com','98756431','321654987','1985-09-30',1,NULL,NULL,'2132as1da21d',NULL,8,19,'uploads/personas/72356899.png',NULL,1,NULL,'2026-08-02 18:45:01'),(6,'Nicolle','Velez',2,'9876543122','Nicolle@gmail.com','12346578','31654987','2021-06-21',2,NULL,NULL,'32165asd',1,8,19,'uploads/personas/9876543122.png',NULL,1,NULL,'2026-08-02 18:45:01'),(7,'Cristian','Castrillo',1,'23456798','cristian@correo.com','987654123','465132798','2002-08-20',1,2,1,'as32d2sa31d',1,8,19,'uploads/personas/23456798.png',NULL,1,NULL,'2026-08-02 19:10:03'),(8,'Juan Gabriel','Henao',1,'9765431456','vago@correo.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'uploads/personas/',NULL,1,NULL,'2026-08-12 20:11:55'),(9,'Laura','Garcia',1,'152030','laura@correo.com','321654987','321654987','2005-07-01',2,1,0,'Calle 54a sur N 55 e 65',NULL,NULL,NULL,'uploads/personas/152030.jpg',NULL,1,NULL,'2026-08-18 21:18:58'),(10,'Catalina','Velasquez',1,'784523','cata@correo.com','1112346579',NULL,'2000-05-16',2,2,NULL,NULL,NULL,NULL,NULL,'uploads/personas/784523.jpeg',NULL,1,NULL,'2026-08-18 21:33:58');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-14 21:54:27
