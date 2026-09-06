# Implementation Plan — SDM → Fasyankes → Kecamatan

> **Status: READ-ONLY AUDIT SELESAI — MENUNGGU APPROVAL**  
> Tidak ada `ALTER TABLE` / `INSERT` / `DELETE` / perubahan kode yang dieksekusi. Dokumen ini adalah rencana. Eksekusi Tahap 4–10 hanya setelah persetujuan eksplisit.

---

## 1. HASIL AUDIT (Jawaban 7 Pertanyaan Wajib)

### 1.1 Tabel SDM saat ini apa namanya?

**3 tabel aktif:**

| Tabel | Peran | DDL singkat |
|---|---|---|
| `tbl_sdm` | Data individu SDM (1 baris = 1 orang) — **941 baris dummy** | `id_sdm PK, nama, id_profesi, spesialisasi, id_kecamatan NOT NULL, id_faskes NULL, foto, aktif Y/N` — `App/Core` tidak generate, struktur manual `SHOW CREATE TABLE tbl_sdm` |
| `tbl_sdm_items` | Master jenis/profesi — **7 baris** | `id PK + UNIQUE(nama_item), nilai, urutan, aktif` |
| `tbl_sdm_kecamatan` | Agregat jumlah per kecamatan per profesi — **84 baris (12 kec × 7 profesi)** | `id PK, UNIQUE(id_kecamatan,id_item), jumlah, aktif` |

Semua ditemukan via `SHOW TABLES LIKE '%sdm%'` — tidak ada `tbl_dokter`, `tbl_perawat`, `tbl_pegawai`, `mst_profesi` lain.

### 1.2 Kolom-kolomnya apa saja?

**`tbl_sdm`** (`DESCRIBE tbl_sdm`):
- `id_sdm int PK auto_inc`
- `nama varchar(150) NOT NULL` — isi sekarang `SDM Baki #1 (Dokter)` dst
- `id_profesi int NOT NULL MUL` → `tbl_sdm_items.id`
- `spesialisasi varchar(120) NULL` — kosong 100%
- `id_kecamatan int NOT NULL MUL` → `tbl_kecamatan.id_kecamatan`
- `id_faskes int NULL MUL` → `tbl_faskes.id_faskes` (**semua 941 = NULL**)
- `foto varchar(255) NULL`
- `aktif enum Y/N DEFAULT Y`
- `created_at timestamp DEFAULT CURRENT_TIMESTAMP`

**`tbl_sdm_items`**: `id, nama_item (UNIQUE), nilai, urutan, aktif` — 7 profesi: Dokter(1), Perawat(2), Bidan(3), Nakes Lainnya(4), Dokter Gigi(9), Farmasi(10), Gizi(11).

**`tbl_sdm_kecamatan`**: `id, id_kecamatan, id_item, jumlah, aktif` — contoh Baki: Dokter 5, Perawat 3, Bidan 5, Nakes Lainnya 9, Dokter Gigi 2, Farmasi 4, Gizi 2 → total 30.

### 1.3 Apakah saat ini SDM hanya terhubung ke kecamatan?

**YA — 100%**. Bukti:
- `SELECT COUNT(*) FROM tbl_sdm WHERE id_faskes IS NULL` = **941**
- `SELECT COUNT(*) FROM tbl_sdm WHERE id_faskes IS NOT NULL` = **0**
- `api/get_sdm.php:31` join `tbl_sdm_items LEFT JOIN tbl_sdm_kecamatan ON id_kecamatan` — tidak pernah baca `tbl_sdm.id_faskes`
- `admin/crud/sdm_kecamatan.php:30` UPSERT ` (id_kecamatan,id_item,jumlah)` — murni kecamatan.

Relasi `Kecamatan → Fasyankes` sudah ada via `tbl_faskes.id_kecamatan`, tapi **SDM → Fasyankes terputus**.

### 1.4 Apakah sudah ada id_faskes?

**Struktur SUDAH ADA, data BELUM TERISI.** Kolom `tbl_sdm.id_faskes INT NULL` + index `idx_sdm_faskes` ada. **Tidak ada FK constraint** (`INFORMATION_SCHEMA.KEY_COLUMN_USAGE` untuk `tbl_sdm` = 0). Jadi aman untuk diaktifkan tanpa migrasi berat — cukup isi nilai + tambah FK opsional. Tidak perlu `ALTER ADD COLUMN` untuk MVP; tapi direkomendasikan `ADD CONSTRAINT FK`, `ADD INDEX`, dan pertimbangkan `SET NULL` on delete.

### 1.5 Bagaimana dashboard saat ini menghitung total SDM?

- **Sumber tunggal**: `tbl_sdm_kecamatan.jumlah` (agregat), bukan `COUNT(tbl_sdm)`.
- `api/get_sdm.php:16-47` jika `?kecamatan=Baki` → `SELECT COALESCE(sk.jumlah,0) FROM tbl_sdm_items LEFT JOIN tbl_sdm_kecamatan WHERE id_kecamatan=?`. Jika tanpa param → baca `tbl_sdm_items.nilai` (total kabupaten legacy, sekarang divergen).
- `assets/js/app_v2.js:1538 PortalAPI.loadSdm(kecamatan)` + `Dashboard.renderData:1822 PortalAPI.loadSdm(namaKec)` — dipanggil setiap `MapEngine.onDistrictClick → Dashboard.loadDistrict → Dashboard.render → loadSdm`.
- `Dashboard.render` update `#sdmContainer` via `renderSdm` → `<table><tr><td>Dokter</td><td>5</td></tr>`.

**Implikasi**: angka panel kanan dan modal nanti **harus** baca sumber yang sama. Saat ini konsisten karena 1 sumber. Setelah ada `tbl_sdm.id_faskes`, harus sinkronkan: `tbl_sdm_kecamatan` jadi **materialized view** dari `tbl_sdm` atau update trigger, bukan input manual terpisah.

### 1.6 Bagaimana CRUD SDM saat ini bekerja?

Dua file, **tidak ada CRUD individu**:

- `admin/crud/sdm.php:1` — CRUD **master profesi** (`tbl_sdm_items`): tambah/edit/hapus `nama_item, nilai, urutan`. Tabel bawah list `kecamatan + total_sdmk` (SUM `tbl_sdm_kecamatan`) dengan link ke `sdm_kecamatan.php?id=`.
- `admin/crud/sdm_kecamatan.php:1` — Form per kecamatan: `SELECT * FROM tbl_sdm_items` + `SELECT jumlah FROM tbl_sdm_kecamatan WHERE id_kecamatan=?` → input `jumlah[id_item]` → `INSERT ... ON DUPLICATE KEY UPDATE jumlah`.

**Tidak ada**: `admin/crud/sdm.php` yang edit `tbl_sdm` individu, tidak ada pilih fasyankes, tidak ada pilih jumlah per fasyankes. Jadi perubahan ke model per-fasyankes adalah fitur baru, bukan modifikasi existing yang berisiko break.

### 1.7 Apakah perubahan relasi dapat dilakukan tanpa merusak data SDM lama?

**YA — dengan strategi NULL-preserve.**

- Data lama 941 baris aman karena `id_faskes` nullable. Migrasi: biarkan NULL sebagai `"Belum ditentukan"` (sesuai instruksi). Jangan `UPDATE` ke fasyankes fiktif.
- `tbl_sdm_kecamatan` 84 baris jangan dihapus; jadikan **baseline** untuk validasi konsistensi (`SUM(tbl_sdm per faskes di kec Baki) == tbl_sdm_kecamatan.total Baki`).
- Soft-delete `aktif='Y'` konsisten — jika fasyankes di-nonaktifkan, SDM histori tetap (`ON DELETE SET NULL` atau `RESTRICT` + filter `f.aktif='Y'` di API).
- Risiko divergen: jika `tbl_sdm` nanti jadi source of truth, `tbl_sdm_kecamatan.jumlah` bisa stale. Mitigasi: buat `tbl_sdm_kecamatan` jadi **view/recalc** atau cron sync, bukan input manual dobel.

---

## 2. AUDIT LENGKAP TERKAIT

### 2.1 Struktur `tbl_faskes`

- `SHOW CREATE TABLE tbl_faskes` — 99 auto_inc, 95 aktif, `ENUM 7 jenis`, `id_kecamatan INT NULL + idx`, `kecamatan varchar(50) NOT NULL` (lowercase duplikat, legacy), `foto/alamat/telepon/email/lat/lng` nullable, `aktif Y/N`.
- 12 kecamatan masing-masing punya ≥7 faskes (Baki: 7 → 2 RS + 2 Puskesmas + 2 Pustu + 1 Klinik). Seed `database/seed_faskes_awal.php:31` idempotent.
- API `api/faskes.php:1` sudah support `?kecamatan=Baki|2|kec_...` → `WHERE f.aktif='Y'` + `ORDER BY jenis,nama`.

### 2.2 Struktur `tbl_kecamatan`

- 12 baris, PK `id_kecamatan`, `kode_kecamatan` (MJL,BKI,GTK,BD,PLK,GRG,KTR,SKH,TWS,BLU,WRU,NGT), `nama_kecamatan`, kolom `jumlah_puskesmas/pustu/posyandu` stale (tidak dipakai; API hitung dari `tbl_faskes`).
- `admin/crud/kecamatan.php:29` nama terkunci (readonly) agar sinkron SVG `assets/svg/peta_sukoharjo_satelit_interaktif.svg`.

### 2.3 API SDM Existing

- `api/get_sdm.php` — 2 mode (kecamatan vs kabupaten), JSON `{status, kecamatan, data:[{id,nama,nilai}]}`. Validasi `LOWER(nama_kecamatan)=LOWER(?)`.
- Tidak ada `?faskes=` support, tidak ada hierarki `kecamatan→faskes→sdm`. Konsisten dengan `api/get_penyakit_populer.php` pattern.

### 2.4 CRUD & Auth Pattern

- `admin/config.php:13 requireLogin()` + `Session`, semua CRUD pakai `mysqli_real_escape_string + (int)cast`, `header(Location: ...?msg=)` PRG.
- `admin/crud/fasyankes.php:14 handleUploadFoto()` — referensi pola upload (ext jpg/png/webp, 3 MB, `random_bytes`).

### 2.5 `assets/js/app_v2.js`

- `PortalAPI.loadSdm(kecamatan)` (1538) + `renderSdm` (1563) → `#sdmContainer`.
- `Dashboard.loadDistrict(id)` → `fetch api/kecamatan.php?id=` → `renderData` → `PortalAPI.loadSdm(namaKec)` + `loadPenyakit(namaKec)`.
- `MapEngine.current` + `district-click` event sudah stabil. **Jangan sentuh MapEngine/tooltip/FasyankesModal**.
- `index.php:444 #sdmContainer`, `254 #appFasyankes` existing.

---

## 3. TARGET DESAIN (Sesuai Request)

```
Baki (id_kecamatan=2)
 ├── Puskesmas Baki (id_faskes=7)
 │    ├─ Dokter:  3
 │    ├─ Perawat: 5
 │    └─ Bidan:   2
 ├── Puskesmas Baki 01 (18)
 ├── Pustu Baki (8)
 ├── Klinik Baki 02 (21)
 └── Rumah Sakit Baki (9)
     TOTAL Baki = SUM semua = 30 (harus = #sdmContainer = modal total)
```

CRUD form:

```
Kecamatan [ Baki ▼ ]          → SELECT id_kecamatan FROM tbl_kecamatan WHERE aktif='Y'
Fasyankes [ Puskesmas Baki ▼ ] → SELECT id_faskes,nama_faskes,jenis FROM tbl_faskes WHERE id_kecamatan=? AND aktif='Y' ORDER BY jenis,nama (via api/faskes.php)
Jenis     [ Dokter ▼ ]        → SELECT id,nama_item FROM tbl_sdm_items WHERE aktif='Y'
Jumlah    [ 5 ]               → int >=0
```

Behavior: ganti kecamatan → fetch `api/faskes.php?kecamatan=` → repopulate dropdown fasyankes (disabled sampai kecamatan dipilih).

---

## 4. IMPLEMENTATION PLAN (10 Tahap — Eksekusi Setelah Approval)

### TAHAP 1 — Audit ✅ SELESAI

### TAHAP 2 — Rencana (dokumen ini)

### TAHAP 3 — Approval Gate

- Owner review `implementation_plan.md`
- Keputusan: (a) pakai `tbl_sdm` existing vs buat tabel baru, (b) NIP/NIK perlu?, (c) dummy 941 data keep/truncate.

### TAHAP 4 — Backup (READ-ONLY → lalu DDL aman)

```sql
-- via mysqldump CLI, bukan via app
mysqldump -u root portal_dkk tbl_sdm tbl_sdm_items tbl_sdm_kecamatan tbl_faskes tbl_kecamatan > database/backups/backup_sdm_faskes_pre_relasi_YYYYMMDD.sql
CREATE TABLE tbl_sdm_backup_pre_faskes AS SELECT * FROM tbl_sdm;
```

### TAHAP 5 — Implementasi Relasi SDM → Fasyankes (DB)

**Opsi A — Minimal (disarankan, tanpa ALTER ADD COLUMN):**
- Validasi `tbl_sdm.id_faskes` INT NULL sudah ada → cukup tambah constraint (jika belum ada column, skip):
```sql
-- cek dulu: SHOW COLUMNS FROM tbl_sdm LIKE 'id_faskes'
ALTER TABLE tbl_sdm ADD CONSTRAINT fk_sdm_faskes FOREIGN KEY (id_faskes) REFERENCES tbl_faskes(id_faskes) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE tbl_sdm ADD CONSTRAINT fk_sdm_kecamatan FOREIGN KEY (id_kecamatan) REFERENCES tbl_kecamatan(id_kecamatan);
ALTER TABLE tbl_sdm ADD CONSTRAINT fk_sdm_profesi FOREIGN KEY (id_profesi) REFERENCES tbl_sdm_items(id);
-- index sudah ada: idx_sdm_faskes, idx_sdm_kecamatan, idx_sdm_profesi
```
- Jika ingin id_kecamatan auto-derivasi, **jangan DROP column** (breaking). Alternatif: `TRIGGER before_insert` validasi `NEW.id_kecamatan = (SELECT id_kecamatan FROM tbl_faskes WHERE id_faskes=NEW.id_faskes)` bila `NEW.id_faskes IS NOT NULL`.

**Opsi B — Jika column belum ada (fallback):**
```sql
ALTER TABLE tbl_sdm ADD COLUMN id_faskes INT NULL AFTER id_kecamatan,
  ADD KEY idx_sdm_faskes (id_faskes),
  ADD CONSTRAINT fk_sdm_faskes FOREIGN KEY (id_faskes) REFERENCES tbl_faskes(id_faskes) ON DELETE SET NULL;
```

**Kebijakan NULL**: biarkan 941 lama NULL = "Belum ditentukan". Di API/CRUD tampilkan badge `Belum ditentukan (941)`.

### TAHAP 6 — CRUD SDM Baru

**File baru/ubah (tanpa sentuh map/SVG/API Fasyankes):**

- **Modifikasi `admin/crud/sdm.php`** → jadi **list individu `tbl_sdm`** dengan filter `?kecamatan=&faskes=&profesi=&search=&aktif=` + pagination. Tabel: No | Nama | Profesi | Faskes | Kecamatan | Spesialisasi | Aktif | Aksi (Edit/Hapus soft). Toolbar filter cascading: kecamatan → faskes (AJAX `api/faskes.php?kecamatan=`). Simpan pola `admin/crud/fasyankes.php:152` filter.

- **Form tambah/edit SDM** (modal atau halaman `admin/crud/sdm_form.php` reuse `fasyankes.php:383` layout):
  - `kecamatan` select → onChange fetch faskes
  - `id_faskes` select (disabled until kecamatan, required jika ingin strict; nullable jika allow "Belum ditentukan")
  - `id_profesi` select dari `tbl_sdm_items`
  - `nama, spesialisasi, jumlah?` — **Keputusan**: `tbl_sdm` saat ini 1 baris = 1 orang (nama unik). Request `Jumlah: 5` berarti **agregat per faskes per profesi**, bukan 1 baris per orang. Maka ada 2 model:
    - Model 1 (saat ini): tambah 5 baris `SDM Baki #1..5 (Dokter)` — tidak efisien.
    - Model 2 (disarankan): ubah `tbl_sdm` jadi **header per orang** atau buat **tabel agregat baru `tbl_sdm_faskes` (id_faskes, id_profesi, jumlah)**. Karena `tbl_sdm` sudah 941 baris individu, **pertahankan individu** dan `Jumlah` di form = **loop insert N baris** atau **simpan 1 baris agregat di view terpisah**.
  - **Rekomendasi**: untuk MVP, **form CRUD SDM individu (1 orang = 1 baris)** tanpa field `jumlah`. Jika butuh `Jumlah:5` cepat, gunakan **bulk insert 5 baris dummy** atau **tambah tabel `tbl_sdm_faskes`** (lihat alternatif di Risiko).

- **Validasi server** (`POST`):
  ```php
  $id_kec = (int)$_POST['id_kecamatan'];
  $id_faskes = $_POST['id_faskes'] ? (int)$_POST['id_faskes'] : null;
  if ($id_faskes) {
    $cek = $db->prepare("SELECT id_kecamatan FROM tbl_faskes WHERE id_faskes=? AND aktif='Y'");
    // bind, execute
    if ($cek->id_kecamatan != $id_kec) reject "Fasyankes tidak berada di kecamatan yang dipilih";
  }
  // prepared statement untuk insert tbl_sdm
  ```

- **Soft delete**: `UPDATE tbl_sdm SET aktif='N' WHERE id_sdm=?` — jangan `DELETE`. Jika faskes dihapus, SDM tetap dengan `id_faskes` SET NULL (FK).

### TAHAP 7 — API SDM

**Perluas `api/get_sdm.php`** (backward compatible):

- Tetap support `?kecamatan=Baki` → return agregat lama (dari `tbl_sdm_kecamatan` atau `COUNT(tbl_sdm)` — pilih satu, konsisten).
- Tambah `?kecamatan=Baki&faskes=7` atau `?faskes=7` → detail per faskes.
- **Response baru (extend, bukan break):**
```json
{
  "status": true,
  "kecamatan": "Baki",
  "id_kecamatan": 2,
  "total": 30,
  "total_per_jenis": [{"id":1,"nama":"Dokter","nilai":5}, ...],
  "faskes": [
    {"id_faskes":7,"nama_faskes":"Puskesmas Baki","jenis":"Puskesmas","total":8,"per_jenis":[{"nama":"Dokter","nilai":3}]},
    {"id_faskes":8,"nama_faskes":"Pustu Baki","jenis":"Pustu","total":4}
  ],
  "belum_ditentukan": 2,
  "data": [{"id":1,"nama":"Dokter","nilai":5}] // legacy key untuk kompat renderSdm lama
}
```
- Implementasi: `SELECT COUNT(*) FROM tbl_sdm WHERE id_kecamatan=? AND aktif='Y'` vs `SUM(tbl_sdm_kecamatan.jumlah)` — **pilih `tbl_sdm` sebagai source of truth** setelah migrasi, dan sync `tbl_sdm_kecamatan` via `TRIGGER` atau `VIEW`.
- Tambah endpoint baru `api/sdm_faskes.php?kecamatan=Baki` (alternatif jika tidak ingin ubah get_sdm) — pertimbangkan reuse.

### TAHAP 8 — Hubungkan Card SDM Dashboard

- `index.php:254` div SDM saat ini tanpa `id` — beri `id="appSdm"` (mirip `appFasyankes`).
- `assets/js/app_v2.js:1822` sudah load SDM per kecamatan. Tambah **click handler** mirip `FasyankesModal`:
```js
const SdmModal = {
  open: function(){
    const kec = Dashboard.lastData?.nama || Dashboard.currentDistrict;
    if(!kec) { alert("Pilih kecamatan di peta dulu"); return; }
    fetch("api/get_sdm.php?kecamatan="+encodeURIComponent(kec))
      .then(r=>r.json()).then(renderSdmModal);
  }
};
DOM.id("appSdm")?.addEventListener("click", SdmModal.open);
```
- **Jangan ubah** `MapEngine`, `FasyankesModal`, `Dashboard.loadDistrict`.

### TAHAP 9 — Modal/Detail SDM per Fasyankes

- Duplikat pattern `index.php:672 #fasyankesModal` → buat `#sdmModal` (`apps-modal`, `apps-window`, `faskes-body`, `faskes-filters`, `faskes-list`).
- Isi: header `SDM — Baki (Total 30)` + filter `Semua | Puskesmas | Klinik | RS` + list accordion per faskes → tabel profesi.
- CSS reuse `assets/css/style_v2.css` `.faskes-*` class — tidak perlu CSS baru besar.

### TAHAP 10 — Testing

- Manual: klik Baki → cek `#sdmContainer` update (sudah ada) → klik card SDM → modal muncul → total konsisten panel kanan vs modal.
- CRUD: tambah SDM Baki → Puskesmas Baki → Dokter 1 → cek `api/get_sdm.php?kecamatan=Baki` total +1, cek `api/faskes.php?kecamatan=Baki` tidak berubah.
- Negative: coba POST `id_faskes` dari Kartasura saat kecamatan Baki → harus 400.
- Regression: peta klik, Fasyankes modal, Kecamatan CRUD tetap jalan.

---

## 5. KEPUTUSAN YANG BUTUH APPROVAL

1. **Model `Jumlah`**: 1 baris `tbl_sdm` = 1 orang (nama unik) atau 1 baris = agregat (faskes+profesi+jumlah)? Saat ini `tbl_sdm` = individu, `tbl_sdm_kecamatan` = agregat. Jika form minta `Jumlah:5`, apakah insert 5 baris atau 1 baris agregat di tabel baru?
2. **Dummy 941**: keep sebagai histori NULL atau truncate sebelum go-live?
3. **Field identitas**: perlu `nip/nik/str` sebagai natural key anti-duplikat?
4. **FK strict**: `id_faskes` nullable vs required? Rekomendasi nullable MVP lalu NOT NULL setelah data bersih.

---

## 6. RISIKO & MITIGASI

| Risiko | Mitigasi |
|---|---|
| `id_faskes` salah kecamatan via manipulasi POST | Validasi server `SELECT id_kecamatan FROM tbl_faskes WHERE id_faskes=?` |
| Drift `tbl_sdm` vs `tbl_sdm_kecamatan` | Jadikan `tbl_sdm` source of truth, `tbl_sdm_kecamatan` view/cron `SELECT COUNT(*) GROUP BY` |
| 941 dummy tanpa faskes membingungkan total | Badge "Belum ditentukan" terpisah, tidak masuk faskes manapun |
| `tbl_faskes.kecamatan` varchar duplikat | Selalu JOIN `k.nama_kecamatan`, jangan pakai varchar untuk logic baru |
| Performa `COUNT` 941 → ok, tapi scale → tambah index sudah ada |

---

## 7. FILE YANG AKAN DIUBAH (Setelah Approval — Jangan Sentuh Sekarang)

- `admin/crud/sdm.php` (major)
- `admin/crud/sdm_form.php` (baru, atau modal di sdm.php)
- `api/get_sdm.php` (extend, backward compat)
- `assets/js/app_v2.js` (tambah `SdmModal`, jangan ubah MapEngine)
- `index.php` (tambah `id="appSdm"` + `#sdmModal` HTML)
- `database/backups/*` (backup)
- (opsional) `api/sdm_faskes.php` (baru jika split API)

**JANGAN SENTUH**: `assets/svg/*`, `assets/js/map.js`, `MapEngine`, `FasyankesModal` existing, `admin/crud/kecamatan.php`, `api/faskes.php`, `api/kecamatan.php`, `App/Core/*`.

---

## 8. ESTIMASI & URUTAN EKSEKUSI AKTUAL

1. Backup (5 menit)
2. DB constraint (10 menit, cek DDL dulu)
3. CRUD SDM (2–3 jam, cascading select + prepared stmt)
4. API extend (30 menit)
5. JS + modal (1 jam)
6. Testing manual (1 jam)

**Total MVP ~5 jam kerja setelah approval.**

---

> **TUNGGU PERSETUJUAN** — Balas `APPROVE` atau revisi poin 5.1–5.4. Tidak ada perubahan DB/kode sampai approval.
