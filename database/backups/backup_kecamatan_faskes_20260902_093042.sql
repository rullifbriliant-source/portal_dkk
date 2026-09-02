-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: portal_dkk
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `tbl_kecamatan`
--

DROP TABLE IF EXISTS `tbl_kecamatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_kecamatan` (
  `id_kecamatan` int NOT NULL AUTO_INCREMENT,
  `kode_kecamatan` varchar(20) NOT NULL,
  `nama_kecamatan` varchar(100) NOT NULL,
  `jumlah_penduduk` int DEFAULT '0',
  `jumlah_kk` int DEFAULT '0',
  `jumlah_desa` int DEFAULT '0',
  `jumlah_puskesmas` int DEFAULT '0',
  `jumlah_pustu` int DEFAULT '0',
  `jumlah_posyandu` int DEFAULT '0',
  `jumlah_rs` int DEFAULT '0',
  `luas_wilayah` decimal(10,2) DEFAULT '0.00',
  `kepadatan` int DEFAULT '0',
  `aktif` enum('Y','N') DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `jumlah_klinik` int DEFAULT '0',
  `jumlah_rumah_sakit` int DEFAULT '0',
  PRIMARY KEY (`id_kecamatan`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_kecamatan`
--

LOCK TABLES `tbl_kecamatan` WRITE;
/*!40000 ALTER TABLE `tbl_kecamatan` DISABLE KEYS */;
INSERT INTO `tbl_kecamatan` VALUES (1,'MJL','Mojolaban',85420,0,15,2,3,85,0,35.54,0,'Y','2026-07-21 04:51:03',1,0),(2,'BKI','Baki',10000,0,14,100,8,780,1,21.97,0,'Y','2026-07-21 04:51:03',10,10),(3,'GTK','Gatak',54230,0,14,1,2,65,0,19.47,0,'Y','2026-07-21 04:51:03',1,0),(4,'BD','Bendosari',2,0,1,2,4,72,0,52.99,0,'Y','2026-07-21 04:51:03',1,0),(5,'PLK','Polokarto',1,0,17,2,3,90,0,62.18,0,'Y','2026-07-21 04:51:03',1,0),(6,'GRG','Grogol',125400,0,14,3,3,120,2,30.00,0,'Y','2026-07-21 04:51:03',2,1),(7,'KTR','Kartasura',132450,0,12,2,2,98,3,19.23,0,'Y','2026-07-21 04:51:03',2,1),(8,'SKH','Sukoharjo',95200,0,14,2,3,90,1,44.58,0,'Y','2026-07-21 04:51:03',2,1),(9,'TWS','Tawangsari',55800,0,12,1,2,60,0,39.99,0,'Y','2026-07-21 04:51:03',1,0),(10,'BLU','Bulu',52600,0,12,1,2,58,0,43.86,0,'Y','2026-07-21 04:51:03',1,0),(11,'WRU','Weru',6666,0,20,1000,100,70,0,41.98,0,'Y','2026-07-21 04:51:03',100,99),(12,'NGT','Nguter',68700,0,16,2,3,75,0,54.88,0,'Y','2026-07-21 04:51:03',1,0);
/*!40000 ALTER TABLE `tbl_kecamatan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_faskes`
--

DROP TABLE IF EXISTS `tbl_faskes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_faskes` (
  `id_faskes` int NOT NULL AUTO_INCREMENT,
  `kode_faskes` varchar(20) DEFAULT NULL,
  `nama_faskes` varchar(150) NOT NULL,
  `jenis` enum('Rumah Sakit','Puskesmas','Pustu','Poskesdes','Klinik','Apotek','Laboratorium') NOT NULL,
  `id_kecamatan` int DEFAULT NULL,
  `kecamatan` varchar(50) NOT NULL,
  `alamat` text,
  `telepon` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `x_svg` decimal(10,2) DEFAULT NULL,
  `y_svg` decimal(10,2) DEFAULT NULL,
  `aktif` enum('Y','N') DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_faskes`),
  KEY `idx_faskes_kecamatan` (`id_kecamatan`),
  KEY `idx_faskes_jenis` (`jenis`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_faskes`
--

LOCK TABLES `tbl_faskes` WRITE;
/*!40000 ALTER TABLE `tbl_faskes` DISABLE KEYS */;
INSERT INTO `tbl_faskes` VALUES (1,'PKM001','Puskesmas Kartasura','Puskesmas',7,'kartasura',NULL,NULL,NULL,NULL,NULL,NULL,1035.00,910.00,'Y','2026-07-21 05:22:28'),(2,'PKM002','Puskesmas Grogol','Puskesmas',6,'grogol',NULL,NULL,NULL,NULL,NULL,NULL,1640.00,950.00,'Y','2026-07-21 05:22:28'),(3,'PKM003','Puskesmas Sukoharjo','Puskesmas',8,'sukoharjo',NULL,NULL,NULL,NULL,NULL,NULL,1835.00,1185.00,'Y','2026-07-21 05:22:28'),(4,'PKM004','Puskesmas Bendosari','Puskesmas',4,'bendosari',NULL,NULL,NULL,NULL,NULL,NULL,2145.00,1385.00,'Y','2026-07-21 05:22:28');
/*!40000 ALTER TABLE `tbl_faskes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-02  9:30:42
