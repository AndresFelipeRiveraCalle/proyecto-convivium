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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zona_comun`
--

LOCK TABLES `zona_comun` WRITE;
/*!40000 ALTER TABLE `zona_comun` DISABLE KEYS */;
INSERT INTO `zona_comun` VALUES (1,'Salón Social','Espacio para eventos privados, incluye mesas, sillas y cocineta.',50),(2,'Gimnasio','Equipado con m??quinas de cardio y pesas. Uso exclusivo residentes.',15),(3,'Piscina de Adultos','Piscina climatizada. Se requiere traje de ba??o adecuado.',30),(4,'Piscina de Ni??os','Piscina de baja profundidad con supervisi??n requerida.',12),(5,'Cancha Sint??tica','Cancha de f??tbol 5 con iluminaci??n nocturna.',10),(6,'Zona BBQ','Kiosko con parrilla a carb??n y lavaplatos.',8),(7,'Sala de Cine','Proyector HD y sonido envolvente. Reservar con antelaci??n.',12),(8,'Parque Infantil','Juegos de madera y arenero para menores de 12 a??os.',25),(9,'Coworking','Sala con Wi-Fi de alta velocidad y conexiones el??ctricas.',10),(10,'Sauna','Zona h??meda para relajaci??n. Uso m??ximo 30 min por persona.',6);
/*!40000 ALTER TABLE `zona_comun` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-14 21:55:25
