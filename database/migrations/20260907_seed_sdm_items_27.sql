-- Seed 27 Jenis SDM sesuai laporan Dinkes Puskesmas
-- Idempotent: safe to run multiple times (ON DUPLICATE KEY)
-- Prerequisite: ALTER tbl_sdm_items ADD kategori, id_parent sudah ada

-- 1. Rename legacy items agar match spec (avoid duplicate nama_item)
UPDATE `tbl_sdm_items` SET `nama_item`='Dokter Umum' WHERE `nama_item`='Dokter' LIMIT 1;
UPDATE `tbl_sdm_items` SET `nama_item`='Keperawatan' WHERE `nama_item`='Perawat' LIMIT 1;
UPDATE `tbl_sdm_items` SET `nama_item`='Kebidanan' WHERE `nama_item`='Bidan' LIMIT 1;
UPDATE `tbl_sdm_items` SET `nama_item`='Kefarmasian' WHERE `nama_item`='Farmasi' LIMIT 1;

-- 2. Ensure kategori & aktif untuk legacy yang sudah ada
UPDATE `tbl_sdm_items` SET `kategori`='Tenaga Kesehatan', `urutan`=1, `aktif`='Y' WHERE `nama_item`='Dokter Umum';
UPDATE `tbl_sdm_items` SET `kategori`='Tenaga Kesehatan', `urutan`=2, `aktif`='Y' WHERE `nama_item`='Dokter Gigi';
UPDATE `tbl_sdm_items` SET `kategori`='Tenaga Kesehatan', `urutan`=4, `aktif`='Y' WHERE `nama_item`='Keperawatan';
UPDATE `tbl_sdm_items` SET `kategori`='Tenaga Kesehatan', `urutan`=5, `aktif`='Y' WHERE `nama_item`='Kebidanan';
UPDATE `tbl_sdm_items` SET `kategori`='Tenaga Kesehatan', `urutan`=6, `aktif`='Y' WHERE `nama_item`='Kefarmasian';
UPDATE `tbl_sdm_items` SET `kategori`='Tenaga Kesehatan', `urutan`=11, `aktif`='Y' WHERE `nama_item`='Gizi';
UPDATE `tbl_sdm_items` SET `kategori`='Tenaga Kesehatan', `urutan`=24, `aktif`='Y' WHERE `nama_item`='Nakes Lainnya';

-- 3. Insert 20 item baru (ON DUPLICATE KEY UPDATE)
INSERT INTO `tbl_sdm_items` (`nama_item`, `kategori`, `id_parent`, `urutan`, `aktif`) VALUES
('Psikologi Klinis', 'Tenaga Kesehatan', NULL, 3, 'Y'),
('Apoteker', 'Tenaga Kesehatan', NULL, 7, 'Y'),
('Tenaga Teknis Kefarmasian', 'Tenaga Kesehatan', NULL, 8, 'Y'),
('Kesehatan Masyarakat', 'Tenaga Kesehatan', NULL, 9, 'Y'),
('Kesehatan Lingkungan', 'Tenaga Kesehatan', NULL, 10, 'Y'),
('Keterapian Fisik', 'Tenaga Kesehatan', NULL, 12, 'Y'),
('Fisioterapis', 'Tenaga Kesehatan', NULL, 13, 'Y'),
('Okupasi Terapi', 'Tenaga Kesehatan', NULL, 14, 'Y'),
('Terapis Wicara', 'Tenaga Kesehatan', NULL, 15, 'Y'),
('Keteknisian Medis', 'Tenaga Kesehatan', NULL, 16, 'Y'),
('Perekam Medis dan Informasi Kesehatan', 'Tenaga Kesehatan', NULL, 17, 'Y'),
('Teknisi Gigi', 'Tenaga Kesehatan', NULL, 18, 'Y'),
('Terapis Gigi dan Mulut', 'Tenaga Kesehatan', NULL, 19, 'Y'),
('Teknik Biomedika', 'Tenaga Kesehatan', NULL, 20, 'Y'),
('Radiografer', 'Tenaga Kesehatan', NULL, 21, 'Y'),
('Ahli Teknologi Laboratorium Medik', 'Tenaga Kesehatan', NULL, 22, 'Y'),
('Radioterapis', 'Tenaga Kesehatan', NULL, 23, 'Y'),
('Asisten Keperawatan', 'Asisten Tenaga Kesehatan', NULL, 25, 'Y'),
('Struktural', 'Tenaga Penunjang', NULL, 26, 'Y'),
('Dukungan Manajemen', 'Tenaga Penunjang', NULL, 27, 'Y')
ON DUPLICATE KEY UPDATE kategori=VALUES(kategori), urutan=VALUES(urutan), aktif='Y';

-- 4. Set parent untuk sub-items
UPDATE `tbl_sdm_items` SET `id_parent` = (SELECT id FROM (SELECT id FROM `tbl_sdm_items` WHERE `nama_item`='Kefarmasian' LIMIT 1) x) WHERE `nama_item` IN ('Apoteker','Tenaga Teknis Kefarmasian');
UPDATE `tbl_sdm_items` SET `id_parent` = (SELECT id FROM (SELECT id FROM `tbl_sdm_items` WHERE `nama_item`='Keterapian Fisik' LIMIT 1) x) WHERE `nama_item` IN ('Fisioterapis','Okupasi Terapi','Terapis Wicara');
UPDATE `tbl_sdm_items` SET `id_parent` = (SELECT id FROM (SELECT id FROM `tbl_sdm_items` WHERE `nama_item`='Keteknisian Medis' LIMIT 1) x) WHERE `nama_item` IN ('Perekam Medis dan Informasi Kesehatan','Teknisi Gigi','Terapis Gigi dan Mulut');
UPDATE `tbl_sdm_items` SET `id_parent` = (SELECT id FROM (SELECT id FROM `tbl_sdm_items` WHERE `nama_item`='Teknik Biomedika' LIMIT 1) x) WHERE `nama_item` IN ('Radiografer','Ahli Teknologi Laboratorium Medik','Radioterapis');

-- 5. Ensure parents have no parent
UPDATE `tbl_sdm_items` SET `id_parent`=NULL WHERE `nama_item` IN ('Kefarmasian','Keterapian Fisik','Keteknisian Medis','Teknik Biomedika');

-- 6. Nilai legacy set 0 (tidak dipakai lagi, jumlah via tbl_sdm_faskes)
UPDATE `tbl_sdm_items` SET `nilai`=0 WHERE 1;
