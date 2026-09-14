-- ==========================================================
-- PORTAL DKK — database/schema.sql
-- Struktur tabel SAJA (tanpa data). Dibuat dari DB development.
-- Tabel backup (*_backup_*) sengaja dikecualikan.
-- Cara pakai: mysql -u USER -p DB_BARU < database/schema.sql
-- Lihat SETUP.md untuk panduan lengkap.
-- ==========================================================

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
-- Table structure for table `mst_marker`
--

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
-- Table structure for table `portal_activity`
--

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
-- Table structure for table `portal_apps`
--

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
-- Table structure for table `portal_categories`
--

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
-- Table structure for table `portal_district`
--

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
-- Table structure for table `portal_favorites`
--

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
-- Table structure for table `portal_notifications`
--

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
-- Table structure for table `portal_permissions`
--

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
-- Table structure for table `portal_recent_apps`
--

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
-- Table structure for table `portal_roles`
--

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
-- Table structure for table `portal_sessions`
--

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
-- Table structure for table `portal_settings`
--

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
-- Table structure for table `portal_users`
--

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
-- Table structure for table `portal_widgets`
--

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
-- Table structure for table `tbl_admin`
--

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
-- Table structure for table `tbl_faskes`
--

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
-- Table structure for table `tbl_fasyankes`
--

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
-- Table structure for table `tbl_fasyankes_items`
--

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
-- Table structure for table `tbl_kasus_penyakit`
--

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
-- Table structure for table `tbl_kecamatan`
--

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
-- Table structure for table `tbl_penyakit`
--

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
-- Table structure for table `tbl_penyakit_items`
--

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
-- Table structure for table `tbl_penyakit_kecamatan`
--

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
-- Table structure for table `tbl_portal_info`
--

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
-- Table structure for table `tbl_sdm`
--

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
-- Table structure for table `tbl_sdm_faskes`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=270 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_sdm_items`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_sdm_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_item` varchar(100) NOT NULL,
  `kategori` enum('Tenaga Kesehatan','Asisten Tenaga Kesehatan','Tenaga Penunjang') NOT NULL DEFAULT 'Tenaga Kesehatan',
  `scope` varchar(50) NOT NULL DEFAULT '' COMMENT '''=semua jenis faskes; CSV subset {rs,puskesmas,lainnya}',
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
-- Table structure for table `tbl_sdm_kecamatan`
--

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
-- Table structure for table `tbl_spesialis`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_spesialis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_spesialis` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `urutan` int DEFAULT '0',
  `aktif` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_spesialis_nama` (`nama_spesialis`),
  KEY `idx_spesialis_aktif` (`aktif`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tbl_statistik`
--

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

-- Dump completed on 2026-09-09  8:02:17
