# SETUP — Portal Terpadu Dinas Kesehatan (portal_dkk)

Panduan setup koneksi database fleksibel lokal vs production.

## 1. Arsitektur koneksi

- Satu-satunya file yang membuka koneksi: `config/database.php`.
- Kredensial dibaca dari environment variable, prioritas:
  1. System env (`getenv` / `$_ENV` / `$_SERVER`) — dipakai di hosting/VPS.
  2. File `.env` di project root — dipakai di development lokal.
  3. Fallback default lokal (`localhost` / `portal_dkk` / `root` / kosong / `3306`)
     jika tidak ada env sama sekali.
- Semua file lain (`admin/config.php`, `api/*`, `get_*.php`, `App/Core/Loader.php`,
  `tools/*`) hanya `require config/database.php` dan memakai global `$config`
  (mysqli). **Jangan** panggil `mysqli_connect` / `new PDO` di file lain.

## 2. Setup lokal (Laragon / XAMPP)

```powershell
Copy-Item .env.example .env
```

Isi `.env`:

```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=portal_dkk
DB_USER=root
DB_PASS=
```

Buat database kosong lalu import struktur:

```powershell
& "D:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS portal_dkk CHARACTER SET utf8mb4;"
& "D:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root portal_dkk -e "SOURCE database/schema.sql"
```

Atau via phpMyAdmin: pilih database → tab Import → pilih `database/schema.sql`.

Buka `http://localhost/portal_dkk` dan `http://localhost/portal_dkk/admin/crud/sdmk.php`
(sebagai admin) untuk verifikasi.

## 3. Setup production (hosting / VPS)

1. Buat database + user di panel hosting, catat host/port/nama/user/pass.
2. Upload semua file **kecuali** `.env` (sudah di-`.gitignore`).
3. Set kredensial dengan **salah satu** cara (jangan dua-duanya berbeda):
   - **Opsi A — environment variable server** (disarankan bila hosting
     mendukung, mis. via panel / vhost / `SetEnv`): set `DB_HOST`, `DB_NAME`,
     `DB_USER`, `DB_PASS`, `DB_PORT`.
   - **Opsi B — file `.env`** di project root di server:
     ```ini
     DB_HOST=db.namadomain.id
     DB_PORT=3306
     DB_NAME=u1234567_portal_dkk
     DB_USER=u1234567_portal
     DB_PASS=isi-password-kuat-di-sini
     ```
4. Import `database/schema.sql` ke database production yang masih kosong
   (via phpMyAdmin → Import, atau SSH: `mysql -u USER -p DB_BARU < database/schema.sql`).
5. Jika migrasi tambahan diperlukan, jalankan file di `database/migrations/`
   secara berurutan setelah schema.

> Catatan: `database/schema.sql` hanya berisi **struktur** (tanpa data),
> tabel `*_backup_*` sengaja dikecualikan. Data development tidak ikut terbawa.

## 4. Regenerasi schema.sql

Setelah ada perubahan struktur di DB development:

```powershell
& "D:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe" --host=localhost --port=3306 --user=root --no-data --skip-add-drop-table --set-charset --routines --events --ignore-table=portal_dkk.tbl_sdm_backup_pre_faskes --ignore-table=portal_dkk.tbl_sdm_items_backup_20260908_20260908_104547 --ignore-table=portal_dkk.tbl_sdm_items_backup_pre_fix2 --result-file="database\schema.sql" portal_dkk
```

Pastikan header komentar di baris atas file tetap ada setelah regenerasi.

## 5. Troubleshooting

| Gejala | Penyebab umum |
|---|---|
| `Database Error : ...` di browser | `.env` salah / DB belum dibuat / user tanpa akses. Cek `DB_*`. |
| Halaman admin redirect ke `login.php` | Normal — `requireLogin()`; login dulu sebagai admin. |
| Tabel tidak ditemukan setelah import | Schema belum diimport ke DB yang benar; cek `DB_NAME`. |
