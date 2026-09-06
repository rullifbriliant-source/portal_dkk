-- Migration: Tambah Spesialis Dokter pada SDM
-- Tanggal: 2026-09-06
-- Deskripsi: Membuat master spesialis dokter dan menghubungkan ke SDM

-- 1. Master Spesialis Dokter
CREATE TABLE IF NOT EXISTS `tbl_spesialis` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nama_spesialis` VARCHAR(100) NOT NULL,
  `kode` VARCHAR(20) DEFAULT NULL,
  `deskripsi` VARCHAR(255) DEFAULT NULL,
  `urutan` INT DEFAULT 0,
  `aktif` ENUM('Y','N') DEFAULT 'Y',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_spesialis_nama` (`nama_spesialis`),
  KEY `idx_spesialis_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Seed data spesialis umum di Indonesia
INSERT INTO `tbl_spesialis` (`nama_spesialis`, `kode`, `urutan`) VALUES
('Dokter Umum', 'Umum', 0),
('Spesialis Anak', 'Sp.A', 1),
('Spesialis Kandungan & Kebidanan', 'Sp.OG', 2),
('Spesialis Penyakit Dalam', 'Sp.PD', 3),
('Spesialis Bedah', 'Sp.B', 4),
('Spesialis Bedah Saraf', 'Sp.BS', 5),
('Spesialis Jantung & Pembuluh Darah', 'Sp.JP', 6),
('Spesialis Paru', 'Sp.P', 7),
('Spesialis Saraf', 'Sp.S', 8),
('Spesialis THT-KL', 'Sp.THT-KL', 9),
('Spesialis Mata', 'Sp.M', 10),
('Spesialis Kulit & Kelamin', 'Sp.KK', 11),
('Spesialis Orthopedi & Traumatologi', 'Sp.OT', 12),
('Spesialis Urologi', 'Sp.U', 13),
('Spesialis Anestesi', 'Sp.An', 14),
('Spesialis Radiologi', 'Sp.Rad', 15),
('Spesialis Patologi Klinik', 'Sp.PK', 16),
('Spesialis Kedokteran Jiwa', 'Sp.KJ', 17),
('Spesialis Rehabilitasi Medik', 'Sp.KFR', 18)
ON DUPLICATE KEY UPDATE kode=VALUES(kode), urutan=VALUES(urutan), aktif='Y';

-- 3. Tambah kolom id_spesialis ke tbl_sdm (individu) jika belum ada
-- Kolom ini nullable: NULL = umum / tidak spesifik, hanya relevan jika id_profesi = Dokter (id=1)
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_sdm' AND COLUMN_NAME='id_spesialis');
SET @sql := IF(@col_exists=0, 'ALTER TABLE tbl_sdm ADD COLUMN id_spesialis INT NULL AFTER id_profesi, ADD KEY idx_sdm_spesialis (id_spesialis)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Tambah kolom id_spesialis ke tbl_sdm_faskes (agregat per faskes) jika belum ada
SET @col_exists2 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_sdm_faskes' AND COLUMN_NAME='id_spesialis');
SET @sql2 := IF(@col_exists2=0, 'ALTER TABLE tbl_sdm_faskes ADD COLUMN id_spesialis INT NULL AFTER id_profesi, ADD KEY idx_sdmfaskes_spesialis (id_spesialis)', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- 5. Tambah FK jika belum ada (ignore jika sudah ada)
SET @fk_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_sdm' AND CONSTRAINT_NAME='fk_sdm_spesialis');
SET @sql3 := IF(@fk_exists=0, 'ALTER TABLE tbl_sdm ADD CONSTRAINT fk_sdm_spesialis FOREIGN KEY (id_spesialis) REFERENCES tbl_spesialis(id) ON DELETE SET NULL ON UPDATE CASCADE', 'SELECT 1');
PREPARE stmt3 FROM @sql3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;

SET @fk_exists2 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_sdm_faskes' AND CONSTRAINT_NAME='fk_sdmfaskes_spesialis');
SET @sql4 := IF(@fk_exists2=0, 'ALTER TABLE tbl_sdm_faskes ADD CONSTRAINT fk_sdmfaskes_spesialis FOREIGN KEY (id_spesialis) REFERENCES tbl_spesialis(id) ON DELETE SET NULL ON UPDATE CASCADE', 'SELECT 1');
PREPARE stmt4 FROM @sql4; EXECUTE stmt4; DEALLOCATE PREPARE stmt4;

-- 6. Penyesuaian UNIQUE KEY tbl_sdm_faskes agar mendukung spesialis
-- UNIQUE lama: (id_faskes, id_profesi)
-- UNIQUE baru: (id_faskes, id_profesi, id_spesialis) agar bisa simpan Dokter Umum + Dokter Spesialis Anak di faskes yang sama
-- Karena MySQL UNIQUE memperlakukan NULL != NULL, kombinasi dengan NULL tetap aman (multiple Umum allowed? kita cegah via ON DUPLICATE KEY).
-- Kita drop & recreate hanya jika id_spesialis sudah ada dan constraint belum diubah
SET @uk_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_sdm_faskes' AND INDEX_NAME='uk_faskes_profesi_spesialis');
SET @sql5 := IF(@uk_exists=0, 'ALTER TABLE tbl_sdm_faskes ADD UNIQUE KEY uk_faskes_profesi_spesialis (id_faskes, id_profesi, id_spesialis)', 'SELECT 1');
PREPARE stmt5 FROM @sql5; EXECUTE stmt5; DEALLOCATE PREPARE stmt5;
-- Catatan: UNIQUE lama uk_faskes_profesi tetap dipertahankan untuk backward compat; MySQL akan ignore jika sudah ada.
-- Jika ingin strict 1-baris per kombinasi, aplikasi harus gunakan INSERT ... ON DUPLICATE KEY UPDATE dengan nilai id_spesialis yang konsisten (NULL untuk umum).

-- 7. (Opsional) Tambah kolom id_spesialis ke tbl_sdm_kecamatan untuk rekap kecamatan jika dibutuhkan detail spesialis
SET @col_exists3 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_sdm_kecamatan' AND COLUMN_NAME='id_spesialis');
SET @sql6 := IF(@col_exists3=0, 'ALTER TABLE tbl_sdm_kecamatan ADD COLUMN id_spesialis INT NULL AFTER id_item, ADD KEY idx_sdmkec_spesialis (id_spesialis)', 'SELECT 1');
PREPARE stmt6 FROM @sql6; EXECUTE stmt6; DEALLOCATE PREPARE stmt6;

SET @fk_exists3 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_sdm_kecamatan' AND CONSTRAINT_NAME='fk_sdmkec_spesialis');
SET @sql7 := IF(@fk_exists3=0, 'ALTER TABLE tbl_sdm_kecamatan ADD CONSTRAINT fk_sdmkec_spesialis FOREIGN KEY (id_spesialis) REFERENCES tbl_spesialis(id) ON DELETE SET NULL ON UPDATE CASCADE', 'SELECT 1');
PREPARE stmt7 FROM @sql7; EXECUTE stmt7; DEALLOCATE PREPARE stmt7;

-- Update UNIQUE agar include spesialis jika belum ada
SET @uk2_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tbl_sdm_kecamatan' AND INDEX_NAME='uk_sdm_kecamatan_item_spesialis');
SET @sql8 := IF(@uk2_exists=0, 'ALTER TABLE tbl_sdm_kecamatan ADD UNIQUE KEY uk_sdm_kecamatan_item_spesialis (id_kecamatan, id_item, id_spesialis)', 'SELECT 1');
PREPARE stmt8 FROM @sql8; EXECUTE stmt8; DEALLOCATE PREPARE stmt8;
