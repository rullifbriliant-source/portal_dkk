-- Migration FINAL editability SDMK (2026-09-08)
-- Aturan: baris dengan No. (angka/huruf) = editable, header/total = read-only computed.
-- 1. Tambah kolom baru (preserve data existing)
ALTER TABLE `tbl_sdm_items`
  ADD COLUMN `parent_id` INT DEFAULT NULL AFTER `kategori`,
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD CONSTRAINT `fk_sdmitems_parent2` FOREIGN KEY (`parent_id`) REFERENCES `tbl_sdm_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD COLUMN `is_total_row` TINYINT(1) NOT NULL DEFAULT 0 AFTER `urutan`,
  ADD COLUMN `include_in_total` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_total_row`;

-- sync parent_id mirror id_parent for existing rows
UPDATE `tbl_sdm_items` SET `parent_id` = `id_parent` WHERE `parent_id` IS NULL AND `id_parent` IS NOT NULL;

-- backfill flags
UPDATE `tbl_sdm_items` SET `is_total_row`=0 WHERE 1;
UPDATE `tbl_sdm_items` SET `include_in_total` = CASE WHEN `parent_id` IS NOT NULL THEN 0 ELSE 1 END;

-- fix UNIQUE to allow duplicate nama across kategori (Gizi, Terapis Gigi dan Mulut appear twice)
ALTER TABLE `tbl_sdm_items` DROP INDEX `nama_item`;
ALTER TABLE `tbl_sdm_items` ADD UNIQUE KEY `uk_nama_kategori` (`nama_item`, `kategori`);

-- 2. Seed missing B items (Gizi & Terapis Gigi dan Mulut as Asisten)
INSERT INTO `tbl_sdm_items` (`nama_item`, `kategori`, `parent_id`, `id_parent`, `urutan`, `is_total_row`, `include_in_total`, `aktif`) VALUES
('Gizi', 'Asisten Tenaga Kesehatan', NULL, NULL, 25, 0, 1, 'Y'),
('Terapis Gigi dan Mulut', 'Asisten Tenaga Kesehatan', NULL, NULL, 26, 0, 1, 'Y')
ON DUPLICATE KEY UPDATE `aktif`='Y';

-- reorder
UPDATE `tbl_sdm_items` SET `urutan`=27 WHERE `nama_item`='Asisten Keperawatan' AND `kategori`='Asisten Tenaga Kesehatan';
UPDATE `tbl_sdm_items` SET `urutan`=28 WHERE `nama_item`='Struktural' AND `kategori`='Tenaga Penunjang';
UPDATE `tbl_sdm_items` SET `urutan`=29 WHERE `nama_item`='Dukungan Manajemen' AND `kategori`='Tenaga Penunjang';
