-- =============================================================
-- Ketersediaan Kasur Rumah Sakit — P0 database foundation
-- Tanggal: 2026-09-09
-- Aman diulang (idempotent): CREATE memakai IF NOT EXISTS,
-- tidak menghapus/mengubah data existing, tidak melakukan seed.
-- Tabel dibuat dalam kondisi KOSONG; pengisian data (CRUD) tahap P1.
--
-- Relasi: tbl_faskes_bed.id_faskes -> tbl_faskes.id_faskes
--   ON UPDATE CASCADE, ON DELETE RESTRICT (RS yang masih punya
--   data kasur tidak bisa dihapus).
-- Business key: UNIQUE (id_faskes, kategori).
-- Kategori VARCHAR fleksibel (daftar resmi belum ada — tanpa ENUM).
-- Validasi total/tersedia via CHECK (MySQL 8.0.16+ menegakkan CHECK;
-- project memakai MySQL 8.0.30).
-- Pola mengikuti migration project: aktif ENUM Y/N (soft delete),
-- created_at/updated_at TIMESTAMP, FK bernama, UNIQUE business key.
-- =============================================================

CREATE TABLE IF NOT EXISTS `tbl_faskes_bed` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_faskes` int NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `total` int NOT NULL DEFAULT 0,
  `tersedia` int NOT NULL DEFAULT 0,
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_faskes_bed` (`id_faskes`, `kategori`),
  KEY `idx_faskes_bed_faskes` (`id_faskes`),
  CONSTRAINT `fk_faskes_bed_faskes` FOREIGN KEY (`id_faskes`)
    REFERENCES `tbl_faskes` (`id_faskes`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_faskes_bed_total` CHECK (`total` >= 0),
  CONSTRAINT `chk_faskes_bed_tersedia` CHECK (`tersedia` >= 0),
  CONSTRAINT `chk_faskes_bed_tersedia_le_total` CHECK (`tersedia` <= `total`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
