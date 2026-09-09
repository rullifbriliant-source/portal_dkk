-- =============================================================
-- SPM — tambah kolom bkey + UNIQUE uq_spm_bkey
-- Tanggal: 2026-09-09 | Setelah: 20260908_spm_feature.sql
--
-- Latar: SpmLib::importSheet() (App/Services/SpmLib.php) melakukan
-- SELECT/INSERT/UPDATE memakai kolom tbl_spm.bkey sebagai business key.
-- Migration 20260908 belum membuat kolom tersebut sehingga import gagal
-- dengan error: Unknown column 'bkey' in 'where clause'.
-- Perbaikan kolom yang dulu dilakukan langsung di database didokumentasikan
-- di 20260909_spm_upsert_sync.sql (doc-only) — file ini menjadikannya
-- migration resmi yang bisa diulang agar schema konsisten antar environment.
--
-- Prasyarat : migration 20260908_spm_feature.sql sudah dijalankan
--             (tabel tbl_spm ada). MySQL 8.0+ (REGEXP_REPLACE).
-- Sifat     : idempotent & non-destruktif. Aman dijalankan ulang.
--             Tidak menghapus/mengubah data selain mengisi bkey yang NULL.
--
-- Business key (WAJIB sama dengan SpmLib::bkey, satu aturan saja):
--   bkey = md5(periode | tahun | layanan | sub_no | indikator | satuan | sasaran)
--   tiap komponen: trim + collapse whitespace + lowercase.
--   Kecamatan SENGAJA tidak masuk key (1 baris SPM -> N target kecamatan).
--   Detail pemetaan PHP-vs-SQL ada di komentar langkah 3 di bawah.
-- =============================================================

-- -------------------------------------------------------------
-- 1. Tambah kolom bkey CHAR(32) NULL AFTER sasaran (jika belum ada).
--    Mengikuti snippet fresh-install di 20260909_spm_upsert_sync.sql.
-- -------------------------------------------------------------
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_spm' AND COLUMN_NAME = 'bkey');
SET @sql_add := IF(@col_exists = 0,
  'ALTER TABLE tbl_spm ADD COLUMN bkey CHAR(32) NULL AFTER sasaran',
  'SELECT ''kolom bkey sudah ada'' AS info');
PREPARE stmt_add FROM @sql_add; EXECUTE stmt_add; DEALLOCATE PREPARE stmt_add;

-- -------------------------------------------------------------
-- 2. Backfill bkey untuk seluruh baris yang masih NULL.
--    Rumus SETARA SpmLib::bkey() (App/Services/SpmLib.php):
--      PHP : md5(implode("\x1F", [normKey(periode), (string)(int)tahun,
--            normKey(layanan), normKey(subNo), normKey(indikator),
--            normKey(satuan), normKey(sasaran)]))
--      SQL : MD5(CONCAT(..., CHAR(31), ...)) dengan tiap komponen
--            dinormalisasi di bawah. Pemetaan per komponen:
--      - normKey trim+collapse+lower  -> tiga tahap berurutan di bawah.
--            URUTAN TAHAP PENTING (meniru PHP persis):
--            1) pangkas tepi dengan himpunan trim PHP saja
--               (spasi, \t, \n, \r, \f, \v) via @trim_edge.
--               Ditulis via CONCAT+CHAR() karena literal backslash seperti
--               \f dan \x0B TIDAK dikenal MySQL dan akan menjadi huruf
--               biasa ('f','x',...) di dalam pola. TRIM() MySQL hanya
--               memangkas spasi; kelas [[:space:]] perilakunya beda di
--               tepi — keduanya tidak dipakai di tahap ini.
--            2) LOWER (setara mb_strtolower untuk teks Indonesia/ASCII).
--            3) NBSP U+00A0 -> spasi via @nbsp, SETELAH tahap 1, karena
--               PHP trim() TIDAK membuang NBSP di tepi: NBSP tepi harus
--               bertahan sebagai satu spasi (lihat collapse), bukan hilang.
--               Ditulis CONVERT(UNHEX('C2A0') USING utf8mb4) karena
--               CHAR(160 ...) mengembalikan NULL di dalam REPLACE.
--            4) collapse SEMUA whitespace (termasuk NBSP yang kini spasi)
--               menjadi satu spasi via '[[:space:]]+' -> setara /\s+/u
--               (collapse PHP juga tidak menghapus spasi tepi, hanya
--               menyusutkannya menjadi satu — persis perilaku tahap 4).
--      - NULL (sub_no/satuan/sasaran bisa NULL) -> COALESCE(..., '')
--            persis seperti normKey((string)null) = ''
--      - (string)(int)tahun            -> CAST(tahun AS CHAR)
--      - md5() PHP = MD5() MySQL       -> heksadesimal 32 char lowercase
--    WHERE bkey IS NULL membuat backfill idempotent: baris yang sudah
--    punya bkey tidak disentuh (nilai deterministik -> sama bila diulang).
-- -------------------------------------------------------------
SET @nbsp := CONVERT(UNHEX('C2A0') USING utf8mb4);
SET @ws := CONCAT(' ', CONVERT(CHAR(9) USING utf8mb4), CONVERT(CHAR(10) USING utf8mb4),
  CONVERT(CHAR(13) USING utf8mb4), CONVERT(CHAR(12) USING utf8mb4), CONVERT(CHAR(11) USING utf8mb4));
SET @trim_edge := CONCAT('^[', @ws, ']+|[', @ws, ']+$');
UPDATE tbl_spm SET bkey = MD5(CONCAT(
  REGEXP_REPLACE(REPLACE(LOWER(REGEXP_REPLACE(COALESCE(periode, ''), @trim_edge, '')), @nbsp, ' '), '[[:space:]]+', ' '), CHAR(31),
  CAST(tahun AS CHAR), CHAR(31),
  REGEXP_REPLACE(REPLACE(LOWER(REGEXP_REPLACE(COALESCE(jenis_layanan, ''), @trim_edge, '')), @nbsp, ' '), '[[:space:]]+', ' '), CHAR(31),
  REGEXP_REPLACE(REPLACE(LOWER(REGEXP_REPLACE(COALESCE(sub_no, ''), @trim_edge, '')), @nbsp, ' '), '[[:space:]]+', ' '), CHAR(31),
  REGEXP_REPLACE(REPLACE(LOWER(REGEXP_REPLACE(COALESCE(indikator, ''), @trim_edge, '')), @nbsp, ' '), '[[:space:]]+', ' '), CHAR(31),
  REGEXP_REPLACE(REPLACE(LOWER(REGEXP_REPLACE(COALESCE(satuan, ''), @trim_edge, '')), @nbsp, ' '), '[[:space:]]+', ' '), CHAR(31),
  REGEXP_REPLACE(REPLACE(LOWER(REGEXP_REPLACE(COALESCE(sasaran, ''), @trim_edge, '')), @nbsp, ' '), '[[:space:]]+', ' ')
)) WHERE bkey IS NULL;

-- -------------------------------------------------------------
-- 3. LAPORAN DUPLIKAT (wajib dibaca SEBELUM langkah 4).
--    Hasil kosong ("Empty set") = bersih, lanjut aman.
--    Jika ada baris: migration BERHENTI di langkah 4 (error 1062),
--    data TIDAK diubah/dihapus — rapikan duplikatnya dulu secara manual
--    (putuskan baris mana yang dipertahankan), lalu jalankan ulang file ini.
-- -------------------------------------------------------------
SELECT 'DUPLIKAT_BKEY' AS status, bkey, COUNT(*) AS jml,
  GROUP_CONCAT(id ORDER BY id) AS ids,
  GROUP_CONCAT(CONCAT('[', id, '] ', LEFT(indikator, 60)) SEPARATOR ' | ') AS contoh_record
FROM tbl_spm WHERE bkey IS NOT NULL GROUP BY bkey HAVING COUNT(*) > 1;

-- -------------------------------------------------------------
-- 4. Buat UNIQUE constraint uq_spm_bkey (jika belum ada).
--    Jika duplikat masih ada, statement ini GAGAL dengan error 1062
--    (Duplicate entry '...' for key 'uq_spm_bkey') dan TIDAK mengubah
--    apa pun — inilah mekanisme FAIL yang diminta. Baca laporan
--    langkah 3 untuk daftar record yang bertabrakan.
-- -------------------------------------------------------------
SET @uq_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_spm' AND CONSTRAINT_NAME = 'uq_spm_bkey');
SET @sql_uq := IF(@uq_exists = 0,
  'ALTER TABLE tbl_spm ADD CONSTRAINT uq_spm_bkey UNIQUE (bkey)',
  'SELECT ''uq_spm_bkey sudah ada'' AS info');
PREPARE stmt_uq FROM @sql_uq; EXECUTE stmt_uq; DEALLOCATE PREPARE stmt_uq;

-- -------------------------------------------------------------
-- 5. Verifikasi akhir (ekspektasi: bkey_null = 0, uq_ada = 1).
-- -------------------------------------------------------------
SELECT COUNT(*) AS total_baris,
  SUM(bkey IS NULL) AS bkey_null,
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_spm'
    AND CONSTRAINT_NAME = 'uq_spm_bkey') AS uq_ada
FROM tbl_spm;
