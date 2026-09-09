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
-- Table structure for table `mst_kecamatan`
--

DROP TABLE IF EXISTS `mst_kecamatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_kecamatan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `penduduk` int DEFAULT NULL,
  `desa` int DEFAULT NULL,
  `puskesmas` int DEFAULT NULL,
  `posyandu` int DEFAULT NULL,
  `luas` decimal(10,2) DEFAULT NULL,
  `warna` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_kecamatan`
--

LOCK TABLES `mst_kecamatan` WRITE;
/*!40000 ALTER TABLE `mst_kecamatan` DISABLE KEYS */;
/*!40000 ALTER TABLE `mst_kecamatan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_marker`
--

DROP TABLE IF EXISTS `mst_marker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_marker` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) DEFAULT NULL,
  `jenis` varchar(30) DEFAULT NULL,
  `kecamatan` varchar(50) DEFAULT NULL,
  `svg_x` decimal(10,2) DEFAULT NULL,
  `svg_y` decimal(10,2) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `status` enum('Y','N') DEFAULT 'Y',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_marker`
--

LOCK TABLES `mst_marker` WRITE;
/*!40000 ALTER TABLE `mst_marker` DISABLE KEYS */;
/*!40000 ALTER TABLE `mst_marker` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_activity`
--

DROP TABLE IF EXISTS `portal_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_activity` (
  `id_activity` bigint NOT NULL AUTO_INCREMENT,
  `tanggal` datetime DEFAULT NULL,
  `id_user` int DEFAULT NULL,
  `app` varchar(50) DEFAULT NULL,
  `aktivitas` text,
  `ip_address` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_activity`
--

LOCK TABLES `portal_activity` WRITE;
/*!40000 ALTER TABLE `portal_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_apps`
--

DROP TABLE IF EXISTS `portal_apps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_apps` (
  `id_app` int NOT NULL AUTO_INCREMENT,
  `nama_app` varchar(100) DEFAULT NULL,
  `slug` varchar(50) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `warna` varchar(20) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `deskripsi` text,
  `versi` varchar(20) DEFAULT NULL,
  `urutan` int DEFAULT NULL,
  `aktif` enum('Y','N') DEFAULT NULL,
  PRIMARY KEY (`id_app`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_apps`
--

LOCK TABLES `portal_apps` WRITE;
/*!40000 ALTER TABLE `portal_apps` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_apps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_categories`
--

DROP TABLE IF EXISTS `portal_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_categories` (
  `id_kategori` int NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `warna` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_categories`
--

LOCK TABLES `portal_categories` WRITE;
/*!40000 ALTER TABLE `portal_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_district`
--

DROP TABLE IF EXISTS `portal_district`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_district` (
  `id` int NOT NULL AUTO_INCREMENT,
  `district_id` varchar(50) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `warna` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `penduduk` int DEFAULT NULL,
  `puskesmas` int DEFAULT NULL,
  `desa` int DEFAULT NULL,
  `stunting` int DEFAULT NULL,
  `dbd` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `district_id` (`district_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_district`
--

LOCK TABLES `portal_district` WRITE;
/*!40000 ALTER TABLE `portal_district` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_district` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_favorites`
--

DROP TABLE IF EXISTS `portal_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_favorites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_user` int DEFAULT NULL,
  `id_app` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_favorites`
--

LOCK TABLES `portal_favorites` WRITE;
/*!40000 ALTER TABLE `portal_favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_notifications`
--

DROP TABLE IF EXISTS `portal_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_notifications` (
  `id_notif` bigint NOT NULL AUTO_INCREMENT,
  `tanggal` datetime DEFAULT NULL,
  `judul` varchar(200) DEFAULT NULL,
  `pesan` text,
  `tujuan` varchar(50) DEFAULT NULL,
  `dibaca` enum('Y','N') DEFAULT NULL,
  PRIMARY KEY (`id_notif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_notifications`
--

LOCK TABLES `portal_notifications` WRITE;
/*!40000 ALTER TABLE `portal_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_permissions`
--

DROP TABLE IF EXISTS `portal_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_permissions` (
  `id_permission` int NOT NULL AUTO_INCREMENT,
  `id_role` int DEFAULT NULL,
  `id_app` int DEFAULT NULL,
  `can_view` tinyint(1) DEFAULT NULL,
  `can_add` tinyint(1) DEFAULT NULL,
  `can_edit` tinyint(1) DEFAULT NULL,
  `can_delete` tinyint(1) DEFAULT NULL,
  `can_export` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_permissions`
--

LOCK TABLES `portal_permissions` WRITE;
/*!40000 ALTER TABLE `portal_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_recent_apps`
--

DROP TABLE IF EXISTS `portal_recent_apps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_recent_apps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_user` int DEFAULT NULL,
  `id_app` int DEFAULT NULL,
  `dibuka` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_recent_apps`
--

LOCK TABLES `portal_recent_apps` WRITE;
/*!40000 ALTER TABLE `portal_recent_apps` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_recent_apps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_roles`
--

DROP TABLE IF EXISTS `portal_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_roles` (
  `id_role` int NOT NULL AUTO_INCREMENT,
  `nama_role` varchar(50) DEFAULT NULL,
  `deskripsi` varchar(200) DEFAULT NULL,
  `aktif` enum('Y','N') DEFAULT 'Y',
  PRIMARY KEY (`id_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_roles`
--

LOCK TABLES `portal_roles` WRITE;
/*!40000 ALTER TABLE `portal_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_sessions`
--

DROP TABLE IF EXISTS `portal_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_sessions` (
  `id_session` bigint NOT NULL AUTO_INCREMENT,
  `id_user` int DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `expired_time` datetime DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_session`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_sessions`
--

LOCK TABLES `portal_sessions` WRITE;
/*!40000 ALTER TABLE `portal_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_settings`
--

DROP TABLE IF EXISTS `portal_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_settings` (
  `id_setting` int NOT NULL AUTO_INCREMENT,
  `nama_instansi` varchar(200) DEFAULT NULL,
  `logo` varchar(150) DEFAULT NULL,
  `tema` varchar(30) DEFAULT NULL,
  `warna_primer` varchar(20) DEFAULT NULL,
  `background` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_setting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_settings`
--

LOCK TABLES `portal_settings` WRITE;
/*!40000 ALTER TABLE `portal_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_users`
--

DROP TABLE IF EXISTS `portal_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_users` (
  `id_portal` int NOT NULL AUTO_INCREMENT,
  `id_user` int DEFAULT NULL,
  `database_asal` varchar(30) DEFAULT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `foto` varchar(150) DEFAULT NULL,
  `id_role` int DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') DEFAULT NULL,
  PRIMARY KEY (`id_portal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_users`
--

LOCK TABLES `portal_users` WRITE;
/*!40000 ALTER TABLE `portal_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_widgets`
--

DROP TABLE IF EXISTS `portal_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_widgets` (
  `id_widget` int NOT NULL AUTO_INCREMENT,
  `nama_widget` varchar(100) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `aktif` enum('Y','N') DEFAULT NULL,
  `urutan` int DEFAULT NULL,
  PRIMARY KEY (`id_widget`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_widgets`
--

LOCK TABLES `portal_widgets` WRITE;
/*!40000 ALTER TABLE `portal_widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_widgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_admin`
--

DROP TABLE IF EXISTS `tbl_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `aktif` enum('Y','N') DEFAULT 'Y',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_admin`
--

LOCK TABLES `tbl_admin` WRITE;
/*!40000 ALTER TABLE `tbl_admin` DISABLE KEYS */;
INSERT INTO `tbl_admin` VALUES (1,'admin','0192023a7bbd73250516f069df18b500','Y');
/*!40000 ALTER TABLE `tbl_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_desa_kelurahan`
--

DROP TABLE IF EXISTS `tbl_desa_kelurahan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_desa_kelurahan` (
  `id_desa_kelurahan` int NOT NULL AUTO_INCREMENT,
  `id_kecamatan` int NOT NULL,
  `kode_wilayah` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis` enum('Desa','Kelurahan') NOT NULL,
  `laki_laki` int NOT NULL DEFAULT '0',
  `perempuan` int NOT NULL DEFAULT '0',
  `jumlah` int GENERATED ALWAYS AS ((`laki_laki` + `perempuan`)) STORED,
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_desa_kelurahan`),
  UNIQUE KEY `uq_desa_kelurahan_kode` (`kode_wilayah`),
  KEY `idx_desa_kelurahan_kecamatan` (`id_kecamatan`),
  CONSTRAINT `fk_desa_kelurahan_kecamatan` FOREIGN KEY (`id_kecamatan`) REFERENCES `tbl_kecamatan` (`id_kecamatan`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_desa_kelurahan`
--

LOCK TABLES `tbl_desa_kelurahan` WRITE;
/*!40000 ALTER TABLE `tbl_desa_kelurahan` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_desa_kelurahan` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_faskes`
--

LOCK TABLES `tbl_faskes` WRITE;
/*!40000 ALTER TABLE `tbl_faskes` DISABLE KEYS */;
INSERT INTO `tbl_faskes` VALUES (1,'PKM001','Puskesmas Kartasura','Puskesmas',7,'kartasura',NULL,NULL,NULL,NULL,NULL,NULL,1035.00,910.00,'Y','2026-07-21 05:22:28'),(2,'PKM002','Puskesmas Grogol','Puskesmas',6,'grogol',NULL,NULL,NULL,NULL,NULL,NULL,1640.00,950.00,'Y','2026-07-21 05:22:28'),(3,'PKM003','Puskesmas Sukoharjo','Puskesmas',8,'sukoharjo',NULL,NULL,NULL,NULL,NULL,NULL,1835.00,1185.00,'Y','2026-07-21 05:22:28'),(4,'PKM004','Puskesmas Bendosari','Puskesmas',4,'bendosari',NULL,NULL,NULL,NULL,NULL,NULL,2145.00,1385.00,'Y','2026-07-21 05:22:28'),(6,NULL,'Rumah Sakit','Rumah Sakit',2,'baki','','','',NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 02:40:57'),(7,NULL,'Puskesmas Baki','Puskesmas',2,'baki','Jl.ADISUMARMO NO.164','088220023544','metodiubudiono@gmail.com','faskes_20260902_103054_026fa853.png',NULL,NULL,NULL,NULL,'Y','2026-09-02 02:41:25'),(8,NULL,'Pustu Baki','Pustu',2,'baki','','','',NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 02:56:53'),(9,NULL,'Rumah Sakit Baki','Rumah Sakit',2,'baki','','','',NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:08:16'),(10,'PKM-MJL-01','Puskesmas Mojolaban','Puskesmas',1,'mojolaban','','','',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-02 03:16:43'),(11,'PKM-MJL-02','Puskesmas Mojolaban 02','Puskesmas',1,'mojolaban',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(12,'PST-MJL-01','Pustu Mojolaban 01','Pustu',1,'mojolaban',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(13,'PST-MJL-02','Pustu Mojolaban 02','Pustu',1,'mojolaban',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(14,'KLK-MJL-01','Klinik Mojolaban 01','Klinik',1,'mojolaban',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(15,'KLK-MJL-02','Klinik Mojolaban 02','Klinik',1,'mojolaban',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(16,'RST-MJL-01','Rumah Sakit Mojolaban 01','Rumah Sakit',1,'mojolaban',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(17,'RST-MJL-02','Rumah Sakit Mojolaban 02','Rumah Sakit',1,'mojolaban',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(18,'PKM-BKI-01','Puskesmas Baki 01','Puskesmas',2,'baki',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(19,'PST-BKI-01','Pustu Baki 01','Pustu',2,'baki',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(20,'KLK-BKI-01','Klinik Baki 01','Klinik',2,'baki','Jl.ADISUMARMO NO.164','088220023544','metodiubudiono@gmail.com','faskes_20260907_084128_f8db6e31.png',NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(21,'KLK-BKI-02','Klinik Baki 02','Klinik',2,'baki','jalan ahmad','4324343423','iann@gmail.com','faskes_20260902_104733_161de9e2.png',NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(22,'PKM-GTK-01','Puskesmas Gatak','Puskesmas',3,'gatak','','','',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-02 03:16:43'),(23,'PKM-GTK-02','Puskesmas Gatak 02','Puskesmas',3,'gatak',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(24,'PST-GTK-01','Pustu Gatak 01','Pustu',3,'gatak',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(25,'PST-GTK-02','Pustu Gatak 02','Pustu',3,'gatak',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(26,'KLK-GTK-01','Klinik Gatak 01','Klinik',3,'gatak',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(27,'KLK-GTK-02','Klinik Gatak 02','Klinik',3,'gatak',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(28,'RST-GTK-01','Rumah Sakit Gatak 01','Rumah Sakit',3,'gatak',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(29,'RST-GTK-02','Rumah Sakit Gatak 02','Rumah Sakit',3,'gatak',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(30,'PKM-BD-01','Puskesmas Bendosari 01','Puskesmas',4,'bendosari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(31,'PST-BD-01','Pustu Bendosari 01','Pustu',4,'bendosari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(32,'PST-BD-02','Pustu Bendosari 02','Pustu',4,'bendosari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(33,'KLK-BD-01','Klinik Bendosari 01','Klinik',4,'bendosari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(34,'KLK-BD-02','Klinik Bendosari 02','Klinik',4,'bendosari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(35,'RST-BD-01','Rumah Sakit Bendosari 01','Rumah Sakit',4,'bendosari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(36,'RST-BD-02','Rumah Sakit Bendosari 02','Rumah Sakit',4,'bendosari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(37,'PKM-PLK-01','Puskesmas Polokarto','Puskesmas',5,'polokarto','','','',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-02 03:16:43'),(38,'PKM-PLK-02','Puskesmas Polokarto 02','Puskesmas',5,'polokarto',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(39,'PST-PLK-01','Pustu Polokarto 01','Pustu',5,'polokarto',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(40,'PST-PLK-02','Pustu Polokarto 02','Pustu',5,'polokarto',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(41,'KLK-PLK-01','Klinik Polokarto 01','Klinik',5,'polokarto',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(42,'KLK-PLK-02','Klinik Polokarto 02','Klinik',5,'polokarto',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(43,'RST-PLK-01','Rumah Sakit Polokarto 01','Rumah Sakit',5,'polokarto',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(44,'RST-PLK-02','Rumah Sakit Polokarto 02','Rumah Sakit',5,'polokarto',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(45,'PKM-GRG-01','Puskesmas Grogol 01','Puskesmas',6,'grogol',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(46,'PST-GRG-01','Pustu Grogol 01','Pustu',6,'grogol',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(47,'PST-GRG-02','Pustu Grogol 02','Pustu',6,'grogol',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(48,'KLK-GRG-01','Klinik Grogol 01','Klinik',6,'grogol',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(49,'KLK-GRG-02','Klinik Grogol 02','Klinik',6,'grogol',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(50,'RST-GRG-01','Rumah Sakit Grogol 01','Rumah Sakit',6,'grogol','reress','355454','msakms@gmailrr','faskes_20260902_140556_38d26b09.png',NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(51,'RST-GRG-02','Rumah Sakit Grogol 02','Rumah Sakit',6,'grogol',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(52,'PKM-KTR-01','Puskesmas Kartasura 01','Puskesmas',7,'kartasura',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(53,'PST-KTR-01','Pustu Kartasura 01','Pustu',7,'kartasura',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(54,'PST-KTR-02','Pustu Kartasura 02','Pustu',7,'kartasura',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(55,'KLK-KTR-01','Klinik Kartasura 01','Klinik',7,'kartasura',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(56,'KLK-KTR-02','Klinik Kartasura 02','Klinik',7,'kartasura',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(57,'RST-KTR-01','Rumah Sakit Kartasura 01','Rumah Sakit',7,'kartasura',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(58,'RST-KTR-02','Rumah Sakit Kartasura 02','Rumah Sakit',7,'kartasura',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(59,'PKM-SKH-01','Puskesmas Sukoharjo 01','Puskesmas',8,'sukoharjo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(60,'PST-SKH-01','Pustu Sukoharjo 01','Pustu',8,'sukoharjo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(61,'PST-SKH-02','Pustu Sukoharjo 02','Pustu',8,'sukoharjo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(62,'KLK-SKH-01','Klinik Sukoharjo 01','Klinik',8,'sukoharjo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(63,'KLK-SKH-02','Klinik Sukoharjo 02','Klinik',8,'sukoharjo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(64,'RST-SKH-01','Rumah Sakit Sukoharjo 01','Rumah Sakit',8,'sukoharjo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(65,'RST-SKH-02','Rumah Sakit Sukoharjo 02','Rumah Sakit',8,'sukoharjo',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(66,'PKM-TWS-01','Puskesmas Tawangsari','Puskesmas',9,'tawangsari','','','',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-02 03:16:43'),(67,'PKM-TWS-02','Puskesmas Tawangsari 02','Puskesmas',9,'tawangsari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(68,'PST-TWS-01','Pustu Tawangsari 01','Pustu',9,'tawangsari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(69,'PST-TWS-02','Pustu Tawangsari 02','Pustu',9,'tawangsari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(70,'KLK-TWS-01','Klinik Tawangsari 01','Klinik',9,'tawangsari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(71,'KLK-TWS-02','Klinik Tawangsari 02','Klinik',9,'tawangsari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(72,'RST-TWS-01','Rumah Sakit Tawangsari 01','Rumah Sakit',9,'tawangsari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(73,'RST-TWS-02','Rumah Sakit Tawangsari 02','Rumah Sakit',9,'tawangsari',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(74,'PKM-BLU-01','Puskesmas Bulu','Puskesmas',10,'bulu','','','',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-02 03:16:43'),(75,'PKM-BLU-02','Puskesmas Bulu 02','Puskesmas',10,'bulu',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(76,'PST-BLU-01','Pustu Bulu 01','Pustu',10,'bulu',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(77,'PST-BLU-02','Pustu Bulu 02','Pustu',10,'bulu',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(78,'KLK-BLU-01','Klinik Bulu 01','Klinik',10,'bulu',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(79,'KLK-BLU-02','Klinik Bulu 02','Klinik',10,'bulu',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(80,'RST-BLU-01','Rumah Sakit Bulu 01','Rumah Sakit',10,'bulu',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(81,'RST-BLU-02','Rumah Sakit Bulu 02','Rumah Sakit',10,'bulu',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(82,'PKM-WRU-01','Puskesmas Weru','Puskesmas',11,'weru','','','',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-02 03:16:43'),(83,'PKM-WRU-02','Puskesmas Weru 02','Puskesmas',11,'weru',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(84,'PST-WRU-01','Pustu Weru 01','Pustu',11,'weru',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(85,'PST-WRU-02','Pustu Weru 02','Pustu',11,'weru',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(86,'KLK-WRU-01','Klinik Weru 01','Klinik',11,'weru',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(87,'KLK-WRU-02','Klinik Weru 02','Klinik',11,'weru',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(88,'RST-WRU-01','Rumah Sakit Weru 01','Rumah Sakit',11,'weru',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(89,'RST-WRU-02','Rumah Sakit Weru 02','Rumah Sakit',11,'weru',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(90,'PKM-NGT-01','Puskesmas Nguter','Puskesmas',12,'nguter','','','',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-02 03:16:43'),(91,'PKM-NGT-02','Puskesmas Nguter 02','Puskesmas',12,'nguter',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(92,'PST-NGT-01','Pustu Nguter 01','Pustu',12,'nguter',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(93,'PST-NGT-02','Pustu Nguter 02','Pustu',12,'nguter',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(94,'KLK-NGT-01','Klinik Nguter 01','Klinik',12,'nguter',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(95,'KLK-NGT-02','Klinik Nguter 02','Klinik',12,'nguter',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(96,'RST-NGT-01','Rumah Sakit Nguter 01','Rumah Sakit',12,'nguter',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(97,'RST-NGT-02','Rumah Sakit Nguter 02','Rumah Sakit',12,'nguter',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'N','2026-09-02 03:16:43'),(99,NULL,'Puskesmas Odi','Puskesmas',11,'weru','Jl.ADISUMARMO NO.164','088220023544','metodiubudiono@gmail.com','faskes_20260902_140719_8aab3c6e.png',NULL,NULL,NULL,NULL,'N','2026-09-02 07:07:19'),(100,'RS-RESMI-01','RS Umum Daerah Ir. Soekarno Kabupaten Sukoharjo','Rumah Sakit',8,'sukoharjo','Jl. Dr. Muwardi No. 71, Sukoharjo, Jawa Tengah 57514','(0271) 593118','rsud@sukoharjokab.go.id',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-07 04:59:06'),(101,'RS-RESMI-02','RS Khusus Orthopedi Karima Utama','Rumah Sakit',7,'kartasura','Jl. Amarta No. 8-10, Ngabeyan, Kartasura, Sukoharjo','0271-783399','rs karimautama.solo@yahoo.com',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-07 04:59:06'),(102,'RS-RESMI-03','RS Umum Nirmala Suri','Rumah Sakit',4,'bendosari','Jl. Raya Solo-Sukoharjo KM 09, Sukoharjo','','rs.nirmalasuri@yahoo.com','faskes_20260908_115952_ac441e79.jpg',NULL,NULL,NULL,NULL,'Y','2026-09-07 04:59:06'),(103,'RS-RESMI-04','RS Umum Dr. Oen Solo Baru','Rumah Sakit',6,'grogol','Jl. Raya Solo Baru, Grogol, Sukoharjo','0271-620220','rumah_sakit@droensolobaru.com',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-07 04:59:06'),(104,'RS-RESMI-05','RS Umum PKU Muhammadiyah Sukoharjo','Rumah Sakit',8,'sukoharjo','Jl. Mayor Sunaryo No. 37, Sukoharjo 57512','0271-593979','pku.sukoharjo@gmail.com',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-07 04:59:06'),(105,'RS-RESMI-06','RS Umum Universitas Sebelas Maret','Rumah Sakit',7,'kartasura','Jl. A. Yani No. 200, Makamhaji, Kartasura, Sukoharjo 57161','0271-6775000','rsuns@mail.uns.ac.id',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-07 04:59:06'),(106,'RS-RESMI-07','RS Umum Indriati','Rumah Sakit',6,'grogol','Jl. Palem Raya, Langenharjo, Solo Baru, Sukoharjo','0271-5722000','rsind@rsindriati.com',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-07 04:59:06'),(107,'RS-RESMI-08','RS PKU Muhammadiyah Kartasura','Rumah Sakit',7,'kartasura','Jl. Slamet Riyadi No. 6, Kartasura, Sukoharjo','0271-780156','pkukartasura@gmail.com',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-07 04:59:06'),(108,'RS-RESMI-09','RS Islam Surakarta YARSIS','Rumah Sakit',7,'kartasura','Jl. Ahmad Yani, Pabelan, Kartasura, Sukoharjo','(0271) 710571','yarsishumas@gmail.com',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-07 04:59:06'),(109,'RS-RESMI-10','Rumah Sakit ASA BUNDA','Rumah Sakit',2,'baki','Jl. Ovensari Raya No. 30, Kadilangu, Baki, Sukoharjo','81367317000','rsasabunda@gmail.com','faskes_20260908_093050_cbfbae11.jpeg',NULL,NULL,NULL,NULL,'Y','2026-09-07 04:59:06'),(110,'RS-RESMI-11','RS Orthopedi Prof. Dr. R. Soeharso','Rumah Sakit',7,'kartasura','Jl. Jenderal Ahmad Yani, Pabelan, Kartasura, Sukoharjo 57162','0271-714458','rso_solo@rso.go.id',NULL,NULL,NULL,NULL,NULL,'Y','2026-09-07 04:59:06');
/*!40000 ALTER TABLE `tbl_faskes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_fasyankes`
--

DROP TABLE IF EXISTS `tbl_fasyankes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_fasyankes` (
  `id` int NOT NULL DEFAULT '1',
  `puskesmas` int DEFAULT '0',
  `pustu` int DEFAULT '0',
  `klinik` int DEFAULT '0',
  `rumah_sakit` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_fasyankes`
--

LOCK TABLES `tbl_fasyankes` WRITE;
/*!40000 ALTER TABLE `tbl_fasyankes` DISABLE KEYS */;
INSERT INTO `tbl_fasyankes` VALUES (1,9,10,10,8);
/*!40000 ALTER TABLE `tbl_fasyankes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_fasyankes_items`
--

DROP TABLE IF EXISTS `tbl_fasyankes_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_fasyankes_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_item` varchar(100) NOT NULL,
  `nilai` int DEFAULT '0',
  `urutan` int DEFAULT '0',
  `aktif` enum('Y','N') DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_item` (`nama_item`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_fasyankes_items`
--

LOCK TABLES `tbl_fasyankes_items` WRITE;
/*!40000 ALTER TABLE `tbl_fasyankes_items` DISABLE KEYS */;
INSERT INTO `tbl_fasyankes_items` VALUES (1,'Puskesmas',0,1,'Y','2026-08-26 03:52:47'),(2,'Pustu',0,2,'Y','2026-08-26 03:52:47'),(3,'Klinik',0,3,'Y','2026-08-26 03:52:47'),(4,'Rumah Sakit',0,4,'Y','2026-08-26 03:52:47'),(6,'HAA',1,5,'N','2026-08-26 04:13:25'),(7,'Apotek',5,5,'N','2026-08-26 07:15:16'),(8,'Posyandu',0,5,'N','2026-08-27 02:22:23');
/*!40000 ALTER TABLE `tbl_fasyankes_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_kasus_penyakit`
--

DROP TABLE IF EXISTS `tbl_kasus_penyakit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_kasus_penyakit` (
  `id_kasus` int NOT NULL AUTO_INCREMENT,
  `id_kecamatan` int NOT NULL,
  `id_penyakit` int NOT NULL,
  `jumlah_kasus` int DEFAULT '0',
  `bulan` tinyint NOT NULL,
  `tahun` smallint NOT NULL,
  `sumber_data` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kasus`),
  KEY `id_penyakit` (`id_penyakit`),
  KEY `idx_periode` (`tahun`,`bulan`),
  KEY `idx_kecamatan` (`id_kecamatan`),
  CONSTRAINT `tbl_kasus_penyakit_ibfk_1` FOREIGN KEY (`id_kecamatan`) REFERENCES `tbl_kecamatan` (`id_kecamatan`),
  CONSTRAINT `tbl_kasus_penyakit_ibfk_2` FOREIGN KEY (`id_penyakit`) REFERENCES `tbl_penyakit` (`id_penyakit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_kasus_penyakit`
--

LOCK TABLES `tbl_kasus_penyakit` WRITE;
/*!40000 ALTER TABLE `tbl_kasus_penyakit` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_kasus_penyakit` ENABLE KEYS */;
UNLOCK TABLES;

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
INSERT INTO `tbl_kecamatan` VALUES (1,'MJL','Mojolaban',85420,0,15,2,3,85,0,35.54,0,'Y','2026-07-21 04:51:03',1,0),(2,'BKI','Baki',1,0,14,100,8,780,1,21.97,0,'Y','2026-07-21 04:51:03',10,10),(3,'GTK','Gatak',54230,0,14,1,2,65,0,19.47,0,'Y','2026-07-21 04:51:03',1,0),(4,'BD','Bendosari',1,0,1,2,4,72,0,52.99,0,'Y','2026-07-21 04:51:03',1,0),(5,'PLK','Polokarto',1,0,17,2,3,90,0,62.18,0,'Y','2026-07-21 04:51:03',1,0),(6,'GRG','Grogol',125400,0,14,3,3,120,2,30.00,0,'Y','2026-07-21 04:51:03',2,1),(7,'KTR','Kartasura',132450,0,12,2,2,98,3,19.23,0,'Y','2026-07-21 04:51:03',2,1),(8,'SKH','Sukoharjo',95200,0,14,2,3,90,1,44.58,0,'Y','2026-07-21 04:51:03',2,1),(9,'TWS','Tawangsari',55800,0,12,1,2,60,0,39.99,0,'Y','2026-07-21 04:51:03',1,0),(10,'BLU','Bulu',52600,0,12,1,2,58,0,43.86,0,'Y','2026-07-21 04:51:03',1,0),(11,'WRU','Weru',6666,0,20,1000,100,70,0,41.98,0,'Y','2026-07-21 04:51:03',100,99),(12,'NGT','Nguter',68700,0,16,2,3,75,0,54.88,0,'Y','2026-07-21 04:51:03',1,0);
/*!40000 ALTER TABLE `tbl_kecamatan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_penyakit`
--

DROP TABLE IF EXISTS `tbl_penyakit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_penyakit` (
  `id_penyakit` int NOT NULL AUTO_INCREMENT,
  `nama_penyakit` varchar(100) NOT NULL,
  `kategori` enum('menular','tidak_menular','ispa','lainnya') DEFAULT 'lainnya',
  `icon` varchar(50) DEFAULT NULL,
  `aktif` char(1) DEFAULT 'Y',
  `total_kasus` int DEFAULT '0',
  PRIMARY KEY (`id_penyakit`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_penyakit`
--

LOCK TABLES `tbl_penyakit` WRITE;
/*!40000 ALTER TABLE `tbl_penyakit` DISABLE KEYS */;
INSERT INTO `tbl_penyakit` VALUES (1,'ISPA','lainnya',NULL,'Y',1540),(2,'Hipertensi','lainnya',NULL,'Y',1230),(3,'COVID-19','lainnya',NULL,'Y',1100),(4,'Diare','lainnya',NULL,'Y',890),(5,'Gastritis','lainnya',NULL,'Y',760),(6,'TBC','lainnya',NULL,'Y',640),(7,'Diabetes','lainnya',NULL,'Y',510),(8,'Asma','lainnya',NULL,'Y',430),(9,'Pneumonia','lainnya',NULL,'Y',380),(10,'Demam Berdarah','lainnya',NULL,'Y',320);
/*!40000 ALTER TABLE `tbl_penyakit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_penyakit_items`
--

DROP TABLE IF EXISTS `tbl_penyakit_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_penyakit_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_item` varchar(100) NOT NULL,
  `nilai` int DEFAULT '0',
  `urutan` int DEFAULT '0',
  `aktif` enum('Y','N') DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_item` (`nama_item`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_penyakit_items`
--

LOCK TABLES `tbl_penyakit_items` WRITE;
/*!40000 ALTER TABLE `tbl_penyakit_items` DISABLE KEYS */;
INSERT INTO `tbl_penyakit_items` VALUES (1,'Jantung',1321,1,'Y','2026-08-27 03:26:08'),(2,'Hipertensi',1230,2,'Y','2026-08-27 03:26:08'),(3,'COVID-19',1100,3,'Y','2026-08-27 03:26:08'),(4,'Diare',890,4,'Y','2026-08-27 03:26:08'),(5,'Gastritis',760,5,'Y','2026-08-27 03:26:08'),(6,'TBC',640,6,'Y','2026-08-27 03:26:08'),(7,'Diabetes',510,7,'Y','2026-08-27 03:26:08'),(8,'Asma',430,8,'Y','2026-08-27 03:26:08'),(9,'Pneumonia',380,9,'Y','2026-08-27 03:26:08'),(10,'Demam Berdarah',320,10,'Y','2026-08-27 03:26:08');
/*!40000 ALTER TABLE `tbl_penyakit_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_penyakit_kecamatan`
--

DROP TABLE IF EXISTS `tbl_penyakit_kecamatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_penyakit_kecamatan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_kecamatan` varchar(50) NOT NULL,
  `nama_item` varchar(100) NOT NULL,
  `nilai` int DEFAULT '0',
  `urutan` int DEFAULT '0',
  `aktif` enum('Y','N') DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_penyakit_kecamatan`
--

LOCK TABLES `tbl_penyakit_kecamatan` WRITE;
/*!40000 ALTER TABLE `tbl_penyakit_kecamatan` DISABLE KEYS */;
INSERT INTO `tbl_penyakit_kecamatan` VALUES (1,'Baki','hee',9873,1,'Y','2026-08-28 02:06:16');
/*!40000 ALTER TABLE `tbl_penyakit_kecamatan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_portal_info`
--

DROP TABLE IF EXISTS `tbl_portal_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_portal_info` (
  `id` int NOT NULL DEFAULT '1',
  `judul` varchar(255) DEFAULT 'Portal Terpadu',
  `deskripsi` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_portal_info`
--

LOCK TABLES `tbl_portal_info` WRITE;
/*!40000 ALTER TABLE `tbl_portal_info` DISABLE KEYS */;
INSERT INTO `tbl_portal_info` VALUES (1,'Portal Terpadu','Portal Terpadu Dinas Kesehatan Kabupaten Sukoharjo\r\n    merupakan pusat informasi digital yang mengintegrasikan\r\n    seluruh layanan, data kesehatan, dashboard,\r\n    aplikasi internal serta layanan publik\r\n    dalam satu tampilan interaktif.');
/*!40000 ALTER TABLE `tbl_portal_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_sdm`
--

DROP TABLE IF EXISTS `tbl_sdm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_sdm` (
  `id_sdm` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_profesi` int NOT NULL,
  `id_spesialis` int DEFAULT NULL,
  `spesialisasi` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_kecamatan` int NOT NULL,
  `id_faskes` int DEFAULT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aktif` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_sdm`),
  KEY `idx_sdm_kecamatan` (`id_kecamatan`),
  KEY `idx_sdm_faskes` (`id_faskes`),
  KEY `idx_sdm_profesi` (`id_profesi`),
  KEY `idx_sdm_aktif` (`aktif`),
  KEY `idx_sdm_spesialis` (`id_spesialis`),
  CONSTRAINT `fk_sdm_faskes` FOREIGN KEY (`id_faskes`) REFERENCES `tbl_faskes` (`id_faskes`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sdm_spesialis` FOREIGN KEY (`id_spesialis`) REFERENCES `tbl_spesialis` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_sdm`
--

LOCK TABLES `tbl_sdm` WRITE;
/*!40000 ALTER TABLE `tbl_sdm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_sdm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_sdm_backup_pre_faskes`
--

DROP TABLE IF EXISTS `tbl_sdm_backup_pre_faskes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_sdm_backup_pre_faskes` (
  `id_sdm` int NOT NULL DEFAULT '0',
  `nama` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_profesi` int NOT NULL,
  `spesialisasi` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_kecamatan` int NOT NULL,
  `id_faskes` int DEFAULT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aktif` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_sdm_backup_pre_faskes`
--

LOCK TABLES `tbl_sdm_backup_pre_faskes` WRITE;
/*!40000 ALTER TABLE `tbl_sdm_backup_pre_faskes` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_sdm_backup_pre_faskes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_sdm_faskes`
--

DROP TABLE IF EXISTS `tbl_sdm_faskes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_sdm_faskes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_kecamatan` int NOT NULL,
  `id_faskes` int NOT NULL,
  `id_profesi` int NOT NULL,
  `id_spesialis` int DEFAULT NULL,
  `jumlah` int GENERATED ALWAYS AS ((((`asn_l` + `asn_p`) + `nonasn_l`) + `nonasn_p`)) STORED,
  `asn_l` int NOT NULL DEFAULT '0',
  `asn_p` int NOT NULL DEFAULT '0',
  `nonasn_l` int NOT NULL DEFAULT '0',
  `nonasn_p` int NOT NULL DEFAULT '0',
  `aktif` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_faskes_profesi_spesialis` (`id_faskes`,`id_profesi`,`id_spesialis`),
  KEY `idx_sdm_faskes_kecamatan` (`id_kecamatan`),
  KEY `idx_sdm_faskes_faskes` (`id_faskes`),
  KEY `idx_sdm_faskes_profesi` (`id_profesi`),
  KEY `idx_sdmfaskes_spesialis` (`id_spesialis`),
  CONSTRAINT `fk_sdmfaskes_faskes` FOREIGN KEY (`id_faskes`) REFERENCES `tbl_faskes` (`id_faskes`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sdmfaskes_kecamatan` FOREIGN KEY (`id_kecamatan`) REFERENCES `tbl_kecamatan` (`id_kecamatan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sdmfaskes_profesi` FOREIGN KEY (`id_profesi`) REFERENCES `tbl_sdm_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sdmfaskes_spesialis` FOREIGN KEY (`id_spesialis`) REFERENCES `tbl_spesialis` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_sdm_faskes`
--

LOCK TABLES `tbl_sdm_faskes` WRITE;
/*!40000 ALTER TABLE `tbl_sdm_faskes` DISABLE KEYS */;
INSERT INTO `tbl_sdm_faskes` (`id`, `id_kecamatan`, `id_faskes`, `id_profesi`, `id_spesialis`, `asn_l`, `asn_p`, `nonasn_l`, `nonasn_p`, `aktif`, `created_at`, `updated_at`) VALUES (1,2,7,1,NULL,4,3,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(2,2,7,5,NULL,0,0,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(3,2,7,2,NULL,6,19,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(4,2,7,3,NULL,0,37,0,1,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(5,2,7,6,NULL,0,1,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(6,2,7,7,NULL,0,1,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(7,2,7,8,NULL,0,1,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(8,2,7,9,NULL,0,2,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(9,2,7,11,NULL,0,3,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(10,2,7,12,NULL,0,0,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(11,2,7,13,NULL,0,0,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(12,2,7,15,NULL,0,3,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(13,2,7,16,NULL,0,0,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(14,2,7,17,NULL,0,1,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(15,2,7,19,NULL,0,0,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(16,2,7,20,NULL,2,2,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(17,2,7,21,NULL,0,0,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(18,2,7,4,NULL,0,0,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(19,2,7,22,NULL,0,0,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(20,2,7,23,NULL,0,0,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(21,2,7,24,NULL,0,0,0,0,'Y','2026-09-07 04:49:33','2026-09-07 04:53:15'),(22,2,7,25,NULL,0,1,0,0,'Y','2026-09-07 04:53:15',NULL),(23,2,7,26,NULL,0,0,0,0,'Y','2026-09-07 04:53:15',NULL),(24,2,7,27,NULL,0,2,0,0,'Y','2026-09-07 04:53:15',NULL),(25,7,108,1,NULL,10,0,0,0,'Y','2026-09-07 07:14:51','2026-09-07 07:15:26'),(26,7,108,11,NULL,11,0,0,0,'Y','2026-09-07 07:15:26',NULL),(27,6,103,1,NULL,3,0,0,0,'Y','2026-09-07 07:46:39',NULL),(28,2,109,1,NULL,2,0,0,0,'Y','2026-09-08 02:28:32',NULL);
/*!40000 ALTER TABLE `tbl_sdm_faskes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_sdm_items`
--

DROP TABLE IF EXISTS `tbl_sdm_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_sdm_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_item` varchar(100) NOT NULL,
  `kategori` enum('Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang') NOT NULL DEFAULT 'Tenaga Kesehatan',
  `parent_id` int DEFAULT NULL,
  `id_parent` int DEFAULT NULL,
  `nilai` int DEFAULT '0',
  `urutan` int DEFAULT '0',
  `is_total_row` tinyint(1) NOT NULL DEFAULT '0',
  `include_in_total` tinyint(1) NOT NULL DEFAULT '1',
  `aktif` enum('Y','N') DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nama_kategori` (`nama_item`,`kategori`),
  KEY `fk_sdmitems_parent` (`id_parent`),
  KEY `idx_parent_id` (`parent_id`),
  CONSTRAINT `fk_sdmitems_parent` FOREIGN KEY (`id_parent`) REFERENCES `tbl_sdm_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sdmitems_parent2` FOREIGN KEY (`parent_id`) REFERENCES `tbl_sdm_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_sdm_items`
--

LOCK TABLES `tbl_sdm_items` WRITE;
/*!40000 ALTER TABLE `tbl_sdm_items` DISABLE KEYS */;
INSERT INTO `tbl_sdm_items` VALUES (1,'Dokter Umum','Tenaga Kesehatan',NULL,NULL,0,1,0,1,'Y','2026-08-26 07:42:40'),(2,'Keperawatan','Tenaga Kesehatan',NULL,NULL,0,4,0,1,'Y','2026-08-26 07:42:40'),(3,'Kebidanan','Tenaga Kesehatan',NULL,NULL,0,5,0,1,'Y','2026-08-26 07:42:40'),(4,'Nakes Lainnya','Tenaga Kesehatan',NULL,NULL,0,24,0,1,'Y','2026-08-26 07:42:40'),(5,'Psikologi Klinis','Tenaga Kesehatan',NULL,NULL,0,3,0,1,'Y','2026-09-07 04:47:08'),(6,'Apoteker','Tenaga Kesehatan',26,26,0,7,0,0,'Y','2026-09-07 04:47:08'),(7,'Tenaga Teknis Kefarmasian','Tenaga Kesehatan',26,26,0,8,0,0,'Y','2026-09-07 04:47:08'),(8,'Kesehatan Masyarakat','Tenaga Kesehatan',NULL,NULL,0,9,0,1,'Y','2026-09-07 04:47:08'),(9,'Kesehatan Lingkungan','Tenaga Kesehatan',NULL,NULL,0,10,0,1,'Y','2026-09-07 04:47:08'),(10,'Keterapian Fisik','Tenaga Kesehatan',NULL,NULL,0,12,0,1,'Y','2026-09-07 04:47:08'),(11,'Fisioterapis','Tenaga Kesehatan',10,10,0,13,0,0,'Y','2026-09-07 04:47:08'),(12,'Okupasi Terapi','Tenaga Kesehatan',10,10,0,14,0,0,'Y','2026-09-07 04:47:08'),(13,'Terapis Wicara','Tenaga Kesehatan',10,10,0,15,0,0,'Y','2026-09-07 04:47:08'),(14,'Keteknisian Medis','Tenaga Kesehatan',NULL,NULL,0,16,0,1,'Y','2026-09-07 04:47:08'),(15,'Perekam Medis dan Informasi Kesehatan','Tenaga Kesehatan',14,14,0,17,0,0,'Y','2026-09-07 04:47:08'),(16,'Teknisi Gigi','Tenaga Kesehatan',14,14,0,18,0,0,'Y','2026-09-07 04:47:08'),(17,'Terapis Gigi dan Mulut','Tenaga Kesehatan',14,14,0,19,0,0,'Y','2026-09-07 04:47:08'),(18,'Teknik Biomedika','Tenaga Kesehatan',NULL,NULL,0,20,0,1,'Y','2026-09-07 04:47:08'),(19,'Radiografer','Tenaga Kesehatan',18,18,0,21,0,0,'Y','2026-09-07 04:47:08'),(20,'Ahli Teknologi Laboratorium Medik','Tenaga Kesehatan',18,18,0,22,0,0,'Y','2026-09-07 04:47:08'),(21,'Radioterapis','Tenaga Kesehatan',18,18,0,23,0,0,'Y','2026-09-07 04:47:08'),(22,'Asisten Keperawatan','Asisten Tenaga Kesehatan',NULL,NULL,0,27,0,1,'N','2026-09-07 04:47:08'),(23,'Struktural','Tenaga Penunjang',NULL,NULL,0,28,0,1,'Y','2026-09-07 04:47:08'),(24,'Dukungan Manajemen','Tenaga Penunjang',NULL,NULL,0,29,0,1,'Y','2026-09-07 04:47:08'),(25,'Dokter Gigi','Tenaga Kesehatan',NULL,NULL,0,2,0,1,'Y','2026-09-07 04:52:44'),(26,'Kefarmasian','Tenaga Kesehatan',NULL,NULL,0,6,0,1,'Y','2026-09-07 04:52:44'),(27,'Gizi','Tenaga Kesehatan',NULL,NULL,0,11,0,1,'Y','2026-09-07 04:52:44'),(28,'Gizi','Asisten Tenaga Kesehatan',NULL,NULL,0,25,0,1,'Y','2026-09-08 03:45:58'),(29,'Terapis Gigi dan Mulut','Asisten Tenaga Kesehatan',NULL,NULL,0,26,0,1,'Y','2026-09-08 03:45:58');
/*!40000 ALTER TABLE `tbl_sdm_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_sdm_items_backup_20260908_20260908_104547`
--

DROP TABLE IF EXISTS `tbl_sdm_items_backup_20260908_20260908_104547`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_sdm_items_backup_20260908_20260908_104547` (
  `id` int NOT NULL DEFAULT '0',
  `nama_item` varchar(100) NOT NULL,
  `kategori` enum('Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang') NOT NULL DEFAULT 'Tenaga Kesehatan',
  `id_parent` int DEFAULT NULL,
  `nilai` int DEFAULT '0',
  `urutan` int DEFAULT '0',
  `aktif` enum('Y','N') DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_sdm_items_backup_20260908_20260908_104547`
--

LOCK TABLES `tbl_sdm_items_backup_20260908_20260908_104547` WRITE;
/*!40000 ALTER TABLE `tbl_sdm_items_backup_20260908_20260908_104547` DISABLE KEYS */;
INSERT INTO `tbl_sdm_items_backup_20260908_20260908_104547` VALUES (1,'Dokter Umum','Tenaga Kesehatan',NULL,0,1,'Y','2026-08-26 07:42:40'),(2,'Keperawatan','Tenaga Kesehatan',NULL,0,4,'Y','2026-08-26 07:42:40'),(3,'Kebidanan','Tenaga Kesehatan',NULL,0,5,'Y','2026-08-26 07:42:40'),(4,'Nakes Lainnya','Tenaga Kesehatan',NULL,0,24,'Y','2026-08-26 07:42:40'),(5,'Psikologi Klinis','Tenaga Kesehatan',NULL,0,3,'Y','2026-09-07 04:47:08'),(6,'Apoteker','Tenaga Kesehatan',NULL,0,7,'Y','2026-09-07 04:47:08'),(7,'Tenaga Teknis Kefarmasian','Tenaga Kesehatan',NULL,0,8,'Y','2026-09-07 04:47:08'),(8,'Kesehatan Masyarakat','Tenaga Kesehatan',NULL,0,9,'Y','2026-09-07 04:47:08'),(9,'Kesehatan Lingkungan','Tenaga Kesehatan',NULL,0,10,'Y','2026-09-07 04:47:08'),(10,'Keterapian Fisik','Tenaga Kesehatan',NULL,0,12,'Y','2026-09-07 04:47:08'),(11,'Fisioterapis','Tenaga Kesehatan',10,0,13,'Y','2026-09-07 04:47:08'),(12,'Okupasi Terapi','Tenaga Kesehatan',10,0,14,'Y','2026-09-07 04:47:08'),(13,'Terapis Wicara','Tenaga Kesehatan',10,0,15,'Y','2026-09-07 04:47:08'),(14,'Keteknisian Medis','Tenaga Kesehatan',NULL,0,16,'Y','2026-09-07 04:47:08'),(15,'Perekam Medis dan Informasi Kesehatan','Tenaga Kesehatan',14,0,17,'Y','2026-09-07 04:47:08'),(16,'Teknisi Gigi','Tenaga Kesehatan',14,0,18,'Y','2026-09-07 04:47:08'),(17,'Terapis Gigi dan Mulut','Tenaga Kesehatan',14,0,19,'Y','2026-09-07 04:47:08'),(18,'Teknik Biomedika','Tenaga Kesehatan',NULL,0,20,'Y','2026-09-07 04:47:08'),(19,'Radiografer','Tenaga Kesehatan',18,0,21,'Y','2026-09-07 04:47:08'),(20,'Ahli Teknologi Laboratorium Medik','Tenaga Kesehatan',18,0,22,'Y','2026-09-07 04:47:08'),(21,'Radioterapis','Tenaga Kesehatan',18,0,23,'Y','2026-09-07 04:47:08'),(22,'Asisten Keperawatan','Asisten Tenaga Kesehatan',NULL,0,25,'Y','2026-09-07 04:47:08'),(23,'Struktural','Tenaga Penunjang',NULL,0,26,'Y','2026-09-07 04:47:08'),(24,'Dukungan Manajemen','Tenaga Penunjang',NULL,0,27,'Y','2026-09-07 04:47:08'),(25,'Dokter Gigi','Tenaga Kesehatan',NULL,0,2,'Y','2026-09-07 04:52:44'),(26,'Kefarmasian','Tenaga Kesehatan',NULL,0,6,'Y','2026-09-07 04:52:44'),(27,'Gizi','Tenaga Kesehatan',NULL,0,11,'Y','2026-09-07 04:52:44');
/*!40000 ALTER TABLE `tbl_sdm_items_backup_20260908_20260908_104547` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_sdm_items_backup_pre_fix2`
--

DROP TABLE IF EXISTS `tbl_sdm_items_backup_pre_fix2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_sdm_items_backup_pre_fix2` (
  `id` int NOT NULL DEFAULT '0',
  `nama_item` varchar(100) NOT NULL,
  `kategori` enum('Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang') NOT NULL DEFAULT 'Tenaga Kesehatan',
  `parent_id` int DEFAULT NULL,
  `id_parent` int DEFAULT NULL,
  `nilai` int DEFAULT '0',
  `urutan` int DEFAULT '0',
  `is_total_row` tinyint(1) NOT NULL DEFAULT '0',
  `include_in_total` tinyint(1) NOT NULL DEFAULT '1',
  `aktif` enum('Y','N') DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_sdm_items_backup_pre_fix2`
--

LOCK TABLES `tbl_sdm_items_backup_pre_fix2` WRITE;
/*!40000 ALTER TABLE `tbl_sdm_items_backup_pre_fix2` DISABLE KEYS */;
INSERT INTO `tbl_sdm_items_backup_pre_fix2` VALUES (6,'Apoteker','Tenaga Kesehatan',NULL,NULL,0,7,0,1,'Y','2026-09-07 04:47:08'),(22,'Asisten Keperawatan','Asisten Tenaga Kesehatan',NULL,NULL,0,27,0,1,'Y','2026-09-07 04:47:08'),(7,'Tenaga Teknis Kefarmasian','Tenaga Kesehatan',NULL,NULL,0,8,0,1,'Y','2026-09-07 04:47:08');
/*!40000 ALTER TABLE `tbl_sdm_items_backup_pre_fix2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_sdm_kecamatan`
--

DROP TABLE IF EXISTS `tbl_sdm_kecamatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_sdm_kecamatan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_kecamatan` int NOT NULL,
  `id_item` int NOT NULL,
  `id_spesialis` int DEFAULT NULL,
  `jumlah` int DEFAULT '0',
  `aktif` enum('Y','N') DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sdmkec_spesialis` (`id_spesialis`),
  CONSTRAINT `fk_sdmkec_spesialis` FOREIGN KEY (`id_spesialis`) REFERENCES `tbl_spesialis` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_sdm_kecamatan`
--

LOCK TABLES `tbl_sdm_kecamatan` WRITE;
/*!40000 ALTER TABLE `tbl_sdm_kecamatan` DISABLE KEYS */;
INSERT INTO `tbl_sdm_kecamatan` VALUES (1,2,1,NULL,10,'Y','2026-08-27 07:58:47'),(2,2,2,NULL,3,'Y','2026-08-27 07:58:47'),(3,2,3,NULL,5,'Y','2026-08-27 07:58:47'),(4,2,4,NULL,9,'Y','2026-08-27 07:58:47'),(5,6,1,NULL,3,'Y','2026-08-27 08:00:50'),(6,6,2,NULL,6,'Y','2026-08-27 08:00:50'),(7,6,3,NULL,6,'Y','2026-08-27 08:00:50'),(8,6,4,NULL,6,'Y','2026-08-27 08:00:50'),(9,2,1,NULL,0,'Y','2026-08-28 01:05:13'),(10,2,2,NULL,3,'Y','2026-08-28 01:05:13'),(11,2,3,NULL,5,'Y','2026-08-28 01:05:13'),(12,2,4,NULL,9,'Y','2026-08-28 01:05:13');
/*!40000 ALTER TABLE `tbl_sdm_kecamatan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_spesialis`
--

DROP TABLE IF EXISTS `tbl_spesialis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_spesialis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_spesialis` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `kode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `urutan` int DEFAULT '0',
  `aktif` enum('Y','N') COLLATE utf8mb4_general_ci DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_spesialis_nama` (`nama_spesialis`),
  KEY `idx_spesialis_aktif` (`aktif`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_spesialis`
--

LOCK TABLES `tbl_spesialis` WRITE;
/*!40000 ALTER TABLE `tbl_spesialis` DISABLE KEYS */;
INSERT INTO `tbl_spesialis` VALUES (1,'Dokter Umum','Umum',NULL,0,'Y','2026-09-07 00:35:19'),(2,'Spesialis Anak','Sp.A',NULL,1,'Y','2026-09-07 00:35:19'),(3,'Spesialis Kandungan & Kebidanan','Sp.OG',NULL,2,'Y','2026-09-07 00:35:19'),(4,'Spesialis Penyakit Dalam','Sp.PD',NULL,3,'Y','2026-09-07 00:35:19'),(5,'Spesialis Bedah','Sp.B',NULL,4,'Y','2026-09-07 00:35:19'),(6,'Spesialis Bedah Saraf','Sp.BS',NULL,5,'Y','2026-09-07 00:35:19'),(7,'Spesialis Jantung & Pembuluh Darah','Sp.JP',NULL,6,'Y','2026-09-07 00:35:19'),(8,'Spesialis Paru','Sp.P',NULL,7,'Y','2026-09-07 00:35:19'),(9,'Spesialis Saraf','Sp.S',NULL,8,'Y','2026-09-07 00:35:19'),(10,'Spesialis THT-KL','Sp.THT-KL',NULL,9,'Y','2026-09-07 00:35:19'),(11,'Spesialis Mata','Sp.M',NULL,10,'Y','2026-09-07 00:35:19'),(12,'Spesialis Kulit & Kelamin','Sp.KK',NULL,11,'Y','2026-09-07 00:35:19'),(13,'Spesialis Orthopedi & Traumatologi','Sp.OT',NULL,12,'Y','2026-09-07 00:35:19'),(14,'Spesialis Urologi','Sp.U',NULL,13,'Y','2026-09-07 00:35:19'),(15,'Spesialis Anestesi','Sp.An',NULL,14,'Y','2026-09-07 00:35:19'),(16,'Spesialis Radiologi','Sp.Rad',NULL,15,'Y','2026-09-07 00:35:19'),(17,'Spesialis Patologi Klinik','Sp.PK',NULL,16,'Y','2026-09-07 00:35:19'),(18,'Spesialis Kedokteran Jiwa','Sp.KJ',NULL,17,'Y','2026-09-07 00:35:19'),(19,'Spesialis Rehabilitasi Medik','Sp.KFR',NULL,18,'Y','2026-09-07 00:35:19');
/*!40000 ALTER TABLE `tbl_spesialis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_spm`
--

DROP TABLE IF EXISTS `tbl_spm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_spm` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis_layanan` varchar(255) NOT NULL,
  `indikator` varchar(500) NOT NULL,
  `satuan` varchar(50) NOT NULL DEFAULT '',
  `sasaran` varchar(255) NOT NULL DEFAULT '',
  `tahun` smallint NOT NULL DEFAULT '2026',
  `periode` varchar(60) NOT NULL DEFAULT '2026',
  `parent_id` int DEFAULT NULL,
  `sub_no` varchar(10) DEFAULT NULL,
  `urutan` int NOT NULL DEFAULT '0',
  `total_manual` decimal(15,2) DEFAULT NULL,
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_spm_tahun_aktif` (`tahun`,`aktif`),
  KEY `idx_spm_urutan` (`urutan`),
  KEY `idx_spm_periode_aktif` (`periode`,`aktif`),
  KEY `idx_spm_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_spm`
--

LOCK TABLES `tbl_spm` WRITE;
/*!40000 ALTER TABLE `tbl_spm` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_spm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_spm_sasaran`
--

DROP TABLE IF EXISTS `tbl_spm_sasaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_spm_sasaran` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_spm_sasaran_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_spm_sasaran`
--

LOCK TABLES `tbl_spm_sasaran` WRITE;
/*!40000 ALTER TABLE `tbl_spm_sasaran` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_spm_sasaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_spm_target`
--

DROP TABLE IF EXISTS `tbl_spm_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_spm_target` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_spm` int NOT NULL,
  `id_kecamatan` int NOT NULL,
  `target` decimal(15,2) NOT NULL DEFAULT '0.00',
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_spm_kecamatan` (`id_spm`,`id_kecamatan`),
  KEY `idx_target_spm` (`id_spm`),
  KEY `idx_target_kecamatan` (`id_kecamatan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_spm_target`
--

LOCK TABLES `tbl_spm_target` WRITE;
/*!40000 ALTER TABLE `tbl_spm_target` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_spm_target` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_statistik`
--

DROP TABLE IF EXISTS `tbl_statistik`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_statistik` (
  `id` int NOT NULL DEFAULT '1',
  `puskesmas` int DEFAULT '0',
  `pustu` int DEFAULT '0',
  `posyandu` int DEFAULT '0',
  `pegawai` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_statistik`
--

LOCK TABLES `tbl_statistik` WRITE;
/*!40000 ALTER TABLE `tbl_statistik` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_statistik` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'portal_dkk'
--

--
-- Dumping routines for database 'portal_dkk'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-09  9:59:07
