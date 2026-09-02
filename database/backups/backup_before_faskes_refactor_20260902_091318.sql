-- Database Backup for Portal DKK
-- Generated on: 2026-09-02 09:13:18

DROP TABLE IF EXISTS `tbl_kecamatan`;
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

INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('1', 'MJL', 'Mojolaban', '85420', '0', '15', '2', '3', '85', '0', '35.54', '0', 'Y', '2026-07-21 11:51:03', '1', '0');
INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('2', 'BKI', 'Baki', '10000', '0', '14', '100', '8', '780', '1', '21.97', '0', 'Y', '2026-07-21 11:51:03', '10', '10');
INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('3', 'GTK', 'Gatak', '54230', '0', '14', '1', '2', '65', '0', '19.47', '0', 'Y', '2026-07-21 11:51:03', '1', '0');
INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('4', 'BD', 'Bendosari', '2', '0', '1', '2', '4', '72', '0', '52.99', '0', 'Y', '2026-07-21 11:51:03', '1', '0');
INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('5', 'PLK', 'Polokarto', '1', '0', '17', '2', '3', '90', '0', '62.18', '0', 'Y', '2026-07-21 11:51:03', '1', '0');
INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('6', 'GRG', 'Grogol', '125400', '0', '14', '3', '3', '120', '2', '30.00', '0', 'Y', '2026-07-21 11:51:03', '2', '1');
INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('7', 'KTR', 'Kartasura', '132450', '0', '12', '2', '2', '98', '3', '19.23', '0', 'Y', '2026-07-21 11:51:03', '2', '1');
INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('8', 'SKH', 'Sukoharjo', '95200', '0', '14', '2', '3', '90', '1', '44.58', '0', 'Y', '2026-07-21 11:51:03', '2', '1');
INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('9', 'TWS', 'Tawangsari', '55800', '0', '12', '1', '2', '60', '0', '39.99', '0', 'Y', '2026-07-21 11:51:03', '1', '0');
INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('10', 'BLU', 'Bulu', '52600', '0', '12', '1', '2', '58', '0', '43.86', '0', 'Y', '2026-07-21 11:51:03', '1', '0');
INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('11', 'WRU', 'Weru', '6666', '0', '20', '1000', '100', '70', '0', '41.98', '0', 'Y', '2026-07-21 11:51:03', '100', '99');
INSERT INTO `tbl_kecamatan` (`id_kecamatan`, `kode_kecamatan`, `nama_kecamatan`, `jumlah_penduduk`, `jumlah_kk`, `jumlah_desa`, `jumlah_puskesmas`, `jumlah_pustu`, `jumlah_posyandu`, `jumlah_rs`, `luas_wilayah`, `kepadatan`, `aktif`, `created_at`, `jumlah_klinik`, `jumlah_rumah_sakit`) VALUES ('12', 'NGT', 'Nguter', '68700', '0', '16', '2', '3', '75', '0', '54.88', '0', 'Y', '2026-07-21 11:51:03', '1', '0');

DROP TABLE IF EXISTS `tbl_faskes`;
CREATE TABLE `tbl_faskes` (
  `id_faskes` int NOT NULL AUTO_INCREMENT,
  `kode_faskes` varchar(20) DEFAULT NULL,
  `nama_faskes` varchar(150) NOT NULL,
  `jenis` enum('Rumah Sakit','Puskesmas','Pustu','Poskesdes','Klinik','Apotek','Laboratorium') NOT NULL,
  `kecamatan` varchar(50) NOT NULL,
  `alamat` text,
  `telepon` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `x_svg` decimal(10,2) DEFAULT NULL,
  `y_svg` decimal(10,2) DEFAULT NULL,
  `aktif` enum('Y','N') DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_faskes`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `tbl_faskes` (`id_faskes`, `kode_faskes`, `nama_faskes`, `jenis`, `kecamatan`, `alamat`, `telepon`, `email`, `latitude`, `longitude`, `x_svg`, `y_svg`, `aktif`, `created_at`) VALUES ('1', 'PKM001', 'Puskesmas Kartasura', 'Puskesmas', 'kartasura', NULL, NULL, NULL, NULL, NULL, '1035.00', '910.00', 'Y', '2026-07-21 12:22:28');
INSERT INTO `tbl_faskes` (`id_faskes`, `kode_faskes`, `nama_faskes`, `jenis`, `kecamatan`, `alamat`, `telepon`, `email`, `latitude`, `longitude`, `x_svg`, `y_svg`, `aktif`, `created_at`) VALUES ('2', 'PKM002', 'Puskesmas Grogol', 'Puskesmas', 'grogol', NULL, NULL, NULL, NULL, NULL, '1640.00', '950.00', 'Y', '2026-07-21 12:22:28');
INSERT INTO `tbl_faskes` (`id_faskes`, `kode_faskes`, `nama_faskes`, `jenis`, `kecamatan`, `alamat`, `telepon`, `email`, `latitude`, `longitude`, `x_svg`, `y_svg`, `aktif`, `created_at`) VALUES ('3', 'PKM003', 'Puskesmas Sukoharjo', 'Puskesmas', 'sukoharjo', NULL, NULL, NULL, NULL, NULL, '1835.00', '1185.00', 'Y', '2026-07-21 12:22:28');
INSERT INTO `tbl_faskes` (`id_faskes`, `kode_faskes`, `nama_faskes`, `jenis`, `kecamatan`, `alamat`, `telepon`, `email`, `latitude`, `longitude`, `x_svg`, `y_svg`, `aktif`, `created_at`) VALUES ('4', 'PKM004', 'Puskesmas Bendosari', 'Puskesmas', 'bendosari', NULL, NULL, NULL, NULL, NULL, '2145.00', '1385.00', 'Y', '2026-07-21 12:22:28');

DROP TABLE IF EXISTS `tbl_fasyankes_items`;
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

INSERT INTO `tbl_fasyankes_items` (`id`, `nama_item`, `nilai`, `urutan`, `aktif`, `created_at`) VALUES ('1', 'Puskesmas', '0', '1', 'Y', '2026-08-26 10:52:47');
INSERT INTO `tbl_fasyankes_items` (`id`, `nama_item`, `nilai`, `urutan`, `aktif`, `created_at`) VALUES ('2', 'Pustu', '0', '2', 'Y', '2026-08-26 10:52:47');
INSERT INTO `tbl_fasyankes_items` (`id`, `nama_item`, `nilai`, `urutan`, `aktif`, `created_at`) VALUES ('3', 'Klinik', '0', '3', 'Y', '2026-08-26 10:52:47');
INSERT INTO `tbl_fasyankes_items` (`id`, `nama_item`, `nilai`, `urutan`, `aktif`, `created_at`) VALUES ('4', 'Rumah Sakit', '0', '4', 'Y', '2026-08-26 10:52:47');
INSERT INTO `tbl_fasyankes_items` (`id`, `nama_item`, `nilai`, `urutan`, `aktif`, `created_at`) VALUES ('6', 'HAA', '1', '5', 'N', '2026-08-26 11:13:25');
INSERT INTO `tbl_fasyankes_items` (`id`, `nama_item`, `nilai`, `urutan`, `aktif`, `created_at`) VALUES ('7', 'Apotek', '5', '5', 'N', '2026-08-26 14:15:16');
INSERT INTO `tbl_fasyankes_items` (`id`, `nama_item`, `nilai`, `urutan`, `aktif`, `created_at`) VALUES ('8', 'Posyandu', '0', '5', 'N', '2026-08-27 09:22:23');

DROP TABLE IF EXISTS `tbl_fasyankes`;
CREATE TABLE `tbl_fasyankes` (
  `id` int NOT NULL DEFAULT '1',
  `puskesmas` int DEFAULT '0',
  `pustu` int DEFAULT '0',
  `klinik` int DEFAULT '0',
  `rumah_sakit` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `tbl_fasyankes` (`id`, `puskesmas`, `pustu`, `klinik`, `rumah_sakit`) VALUES ('1', '9', '10', '10', '8');

