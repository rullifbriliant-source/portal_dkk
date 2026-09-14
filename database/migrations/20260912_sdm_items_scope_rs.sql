-- Migration: Pisah tampil item SDMK per jenis faskes (RS / Puskesmas / lainnya)
-- Tanggal: 2026-09-11 (revisi; menggantikan 20260911 yang di-rollback)
-- Deskripsi:
--   Tambah kolom `scope` di tbl_sdm_items. '' (kosong) = semua jenis faskes.
--   Selain itu CSV subset dari {rs, puskesmas, lainnya}.
--   Aturan tampil (lihat sdmk.php scopeForJenis/itemVisible):
--     RS -> scope '' ATAU memuat 'rs' ... ATAU faskes sudah punya data di item itu
--     Puskesmas -> scope '' ATAU memuat 'puskesmas' ... ATAU sudah punya data
--     lainnya -> scope '' ATAU memuat 'lainnya' ... ATAU sudah punya data
--   TIDAK ada baris yang dihapus; hanya penandaan + 2 reaktivasi.
-- AMAN diulang (idempotent bila dijalankan via runner / manual sekali jalan).

-- 1. Kolom scope (jalankan sekali; abaikan error bila sudah ada)
ALTER TABLE `tbl_sdm_items`
  ADD COLUMN `scope` VARCHAR(50) NOT NULL DEFAULT '' AFTER `kategori`;

-- 2. Item khusus RS (seed Tabel 2.18, id 32..59) -> hanya RS
UPDATE `tbl_sdm_items` SET `scope`='rs' WHERE `id` >= 32 AND (`scope`='' OR `scope` IS NULL);

-- 3. Item yang tidak ada di struktur foto RS -> sembunyikan dari RS
--    (4=Nakes Lainnya, 12=Okupasi Terapi, 28=Gizi-B, 29=TGM-B).
--    Terverifikasi 2026-09-11: tidak ada data faskes RS pada keempatnya.
UPDATE `tbl_sdm_items` SET `scope`='puskesmas,lainnya'
  WHERE `id` IN (4,12,28,29) AND (`scope`='' OR `scope` IS NULL);

-- 4. Reaktivasi parent Dokter Spesialis (sempat nonaktif saat uji hapus;
--    19 sub yatim + penomoran huruf rusak bila tetap nonaktif).
--    Sp.A (30) tetap item utama global (UNIQUE nama+kategori melarang duplikat
--    sebagai sub RS).
UPDATE `tbl_sdm_items` SET `aktif`='Y' WHERE `id` IN (30,31) AND `aktif`='N';
