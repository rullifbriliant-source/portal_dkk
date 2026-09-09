-- =============================================================
-- Desa/Kelurahan + Penduduk — P0 database foundation
-- Tanggal: 2026-09-09
-- Aman diulang (idempotent): CREATE memakai IF NOT EXISTS,
-- tidak menghapus data existing, tidak mengubah tabel lain,
-- tidak melakukan seed/import. Tabel dibuat dalam kondisi KOSONG;
-- pengisian 167 desa/kelurahan dilakukan tahap berikutnya via import Excel.
--
-- Business key utama: kode_wilayah (kode Kemendagri apa adanya,
-- cth 33.11.01.2001). UNIQUE hanya pada kode_wilayah karena nama
-- dapat sama di kecamatan/level berbeda.
--
-- Pola mengikuti migration project yang sudah ada:
--   - aktif ENUM('Y','N') DEFAULT 'Y' untuk soft delete (semua modul)
--   - created_at / updated_at TIMESTAMP (pola 20260908_spm_feature.sql)
--   - jumlah sebagai GENERATED STORED = laki_laki + perempuan,
--     satu-satunya sumber total (pola 20260907_alter_sdm_sdm_faskes.sql);
--     kolom jumlah TIDAK boleh diisi manual saat insert/import
--   - FK bernama (pola fk_sdmitems_parent):
--     ON UPDATE CASCADE, ON DELETE RESTRICT agar baris desa tidak
--     bisa yatim dan kecamatan yang masih punya desa tidak bisa dihapus
-- =============================================================

CREATE TABLE IF NOT EXISTS `tbl_desa_kelurahan` (
  `id_desa_kelurahan` int NOT NULL AUTO_INCREMENT,
  `id_kecamatan` int NOT NULL,
  `kode_wilayah` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis` enum('Desa','Kelurahan') NOT NULL,
  `laki_laki` int NOT NULL DEFAULT 0,
  `perempuan` int NOT NULL DEFAULT 0,
  `jumlah` int GENERATED ALWAYS AS (`laki_laki` + `perempuan`) STORED,
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_desa_kelurahan`),
  UNIQUE KEY `uq_desa_kelurahan_kode` (`kode_wilayah`),
  KEY `idx_desa_kelurahan_kecamatan` (`id_kecamatan`),
  CONSTRAINT `fk_desa_kelurahan_kecamatan` FOREIGN KEY (`id_kecamatan`)
    REFERENCES `tbl_kecamatan` (`id_kecamatan`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
