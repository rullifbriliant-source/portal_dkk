-- =============================================================
-- SPM (Standar Pelayanan Minimal) — feature migration
-- Tanggal: 2026-09-08
-- Aman diulang (idempotent): semua CREATE memakai IF NOT EXISTS,
-- tidak menghapus data existing, tidak menyentuh tabel lain.
-- Struktur inti tbl_spm + tbl_spm_target SUDAH ADA di database;
-- bagian CREATE di bawah hanya pengaman bila dijalankan di server baru.
-- =============================================================

CREATE TABLE IF NOT EXISTS `tbl_spm` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis_layanan` varchar(255) NOT NULL,
  `indikator` varchar(500) NOT NULL,
  `satuan` varchar(50) NOT NULL DEFAULT '',
  `sasaran` varchar(255) NOT NULL DEFAULT '',
  `tahun` smallint NOT NULL DEFAULT 2026,
  `periode` varchar(60) NOT NULL DEFAULT '2026',
  `parent_id` int DEFAULT NULL,
  `sub_no` varchar(10) DEFAULT NULL,
  `urutan` int NOT NULL DEFAULT 0,
  `total_manual` decimal(15,2) DEFAULT NULL,
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_spm_tahun_aktif` (`tahun`,`aktif`),
  KEY `idx_spm_urutan` (`urutan`),
  KEY `idx_spm_periode_aktif` (`periode`,`aktif`),
  KEY `idx_spm_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbl_spm_target` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_spm` int NOT NULL,
  `id_kecamatan` int NOT NULL,
  `target` decimal(15,2) NOT NULL DEFAULT 0.00,
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_spm_kecamatan` (`id_spm`,`id_kecamatan`),
  KEY `idx_target_spm` (`id_spm`),
  KEY `idx_target_kecamatan` (`id_kecamatan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Master sasaran (dikelola via Admin → Kelola SPM → Kelola Sasaran).
-- Kolom tbl_spm.sasaran tetap jadi penyimpanan utama (string) agar
-- kompatibel dengan data existing; tabel ini adalah daftar pilihan + CRUD.
CREATE TABLE IF NOT EXISTS `tbl_spm_sasaran` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `nama` varchar(255) NOT NULL,
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_spm_sasaran_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed sasaran master dari nilai distinct yang sudah ada di tbl_spm.
INSERT IGNORE INTO tbl_spm_sasaran (nama, aktif)
SELECT DISTINCT sasaran, 'Y' FROM tbl_spm WHERE sasaran <> '';
