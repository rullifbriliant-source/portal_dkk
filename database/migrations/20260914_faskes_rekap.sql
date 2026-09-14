-- =============================================================
-- Rekap Sarana Pelayanan Kesehatan Kab. Sukoharjo 2021–2025
-- Tanggal: 2026-09-14 (Tahap 1 — fondasi, tanpa seed angka)
-- Aman diulang (idempotent): CREATE memakai IF NOT EXISTS,
-- tidak menghapus/mengubah data existing, tidak melakukan seed.
-- Tabel dibuat dalam kondisi KOSONG; pengisian via CRUD/import
-- Excel tahap 1 (admin/crud/faskes_rekap.php).
--
-- Grain: agregat KABUPATEN per (tahun x jenis_sarana). BUKAN per
-- kecamatan dan BUKAN fasilitas individual — sepenuhnya terpisah
-- dari tbl_faskes (data individual 2026). Tidak ada FK ke tbl_faskes.
-- Business key: UNIQUE (tahun, jenis_sarana).
-- jenis_sarana VARCHAR fleksibel (daftar rekap historis berbeda dari
-- enum tbl_faskes — tanpa ENUM).
-- Pola mengikuti migration project: aktif ENUM Y/N (soft delete),
-- created_at/updated_at TIMESTAMP, UNIQUE business key.
-- BELUM DIJALANKAN — menunggu konfirmasi.
-- =============================================================

CREATE TABLE IF NOT EXISTS `tbl_faskes_rekap` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tahun` smallint NOT NULL,
  `jenis_sarana` varchar(100) NOT NULL,
  `jumlah` int NOT NULL DEFAULT 0,
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_faskes_rekap_tahun_jenis` (`tahun`, `jenis_sarana`),
  KEY `idx_faskes_rekap_tahun` (`tahun`),
  KEY `idx_faskes_rekap_jenis` (`jenis_sarana`),
  CONSTRAINT `chk_faskes_rekap_jumlah` CHECK (`jumlah` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
