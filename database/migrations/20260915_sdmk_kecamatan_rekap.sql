-- =============================================================
-- Rekap SDMK per Kecamatan Kab. Sukoharjo (5 tahun terakhir dinamis)
-- Tanggal: 2026-09-15
-- Aman diulang (idempotent): CREATE memakai IF NOT EXISTS,
-- tidak menghapus/mengubah data existing, tidak melakukan seed.
-- Tabel dibuat dalam kondisi KOSONG; pengisian via CRUD/import
-- Excel (admin/crud/sdmk_kecamatan_rekap.php).
--
-- Grain: agregat KECAMATAN per (id_kecamatan x id_item x tahun).
-- TANPA id_spesialis (granularitas cukup level item utama).
-- Terpisah penuh dari tbl_sdm_kecamatan (legacy, cacat duplikat —
-- tabel lama TIDAK disentuh migration ini) dan dari tbl_sdm_faskes.
-- Business key: UNIQUE (id_kecamatan, id_item, tahun).
-- Pola mengikuti tbl_faskes_rekap: aktif ENUM Y/N (soft delete),
-- created_at/updated_at TIMESTAMP, CHECK jumlah >= 0.
-- =============================================================

CREATE TABLE IF NOT EXISTS `tbl_sdmk_kecamatan_rekap` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_kecamatan` INT NOT NULL,
  `id_item` INT NOT NULL,
  `tahun` SMALLINT NOT NULL,
  `jumlah` INT NOT NULL DEFAULT 0,
  `aktif` ENUM('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sdmk_rekap` (`id_kecamatan`, `id_item`, `tahun`),
  KEY `idx_sdmk_rekap_kecamatan` (`id_kecamatan`),
  KEY `idx_sdmk_rekap_tahun` (`tahun`),
  CONSTRAINT `fk_sdmk_rekap_kecamatan` FOREIGN KEY (`id_kecamatan`)
    REFERENCES `tbl_kecamatan` (`id_kecamatan`),
  CONSTRAINT `fk_sdmk_rekap_item` FOREIGN KEY (`id_item`)
    REFERENCES `tbl_sdm_items` (`id`),
  CONSTRAINT `chk_sdmk_rekap_jumlah` CHECK (`jumlah` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
