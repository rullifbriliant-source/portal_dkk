"use strict";

/* ==========================================================
   SPM (Standar Pelayanan Minimal) — public popup engine
   READ-ONLY: hanya fetch + render. Tanpa tombol CRUD.
   ========================================================== */

const SpmModal = {
    current: null,
    periods: [],
    counts: {},
    cache: {},

    init: function () {
        const self = this;

        // Buka dari card SPM (stat box) + link SPM di modal aplikasi + tombol lain bertanda data-spm-open
        document.querySelectorAll("#statBoxSpm, [data-spm-open]").forEach(function (el) {
            el.addEventListener("click", function (e) {
                e.preventDefault();
                self.open();
            });
            el.addEventListener("keydown", function (e) {
                if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    self.open();
                }
            });
        });

        const modal = document.getElementById("spmModal");
        if (modal) {
            modal.addEventListener("click", function (e) {
                if (e.target === modal) self.close();
            });
        }
        const btn = document.getElementById("closeSpm");
        if (btn) btn.addEventListener("click", function () { self.close(); });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") self.close();
        });

        this.loadCount();
    },

    loadCount: function () {
        const el = document.getElementById("statProgram");
        if (!el) return;
        fetch("api/get_spm.php?action=count&ts=" + Date.now(), { cache: "no-store" })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json && json.status && typeof Counter !== "undefined") {
                    Counter.start("statProgram", json.total || 0);
                } else if (json && json.status) {
                    el.textContent = Number(json.total || 0).toLocaleString("id-ID");
                }
            })
            .catch(function () { /* biarkan angka dashboard existing */ });
    },

    open: function () {
        const modal = document.getElementById("spmModal");
        if (!modal) return;
        modal.classList.add("open");
        document.body.style.overflow = "hidden";
        if (!this.periods.length) {
            this.loadPeriods();
        } else if (!this.current) {
            this.select(this.periods[0]);
        }
    },

    close: function () {
        const modal = document.getElementById("spmModal");
        if (!modal) return;
        modal.classList.remove("open");
        document.body.style.overflow = "";
    },

    loadPeriods: function () {
        const self = this;
        self.tabsLoading();
        fetch("api/get_spm.php?action=periods&ts=" + Date.now(), { cache: "no-store" })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json || !json.status || !json.periods || !json.periods.length) {
                    self.tabsEmpty();
                    return;
                }
                self.periods = json.periods;
                self.counts = json.counts || {};
                self.renderTabs();
                self.select(self.periods[0]);
            })
            .catch(function () { self.tabsEmpty(); });
    },

    tabsLoading: function () {
        const box = document.getElementById("spmTabs");
        if (box) box.innerHTML = '<span style="font-size:12px;color:rgba(255,255,255,.5)">Memuat periode...</span>';
        const body = document.getElementById("spmBody");
        if (body) body.innerHTML = '<div class="spm-loading"><i class="fas fa-spinner fa-spin"></i>Memuat data SPM...</div>';
    },

    tabsEmpty: function () {
        const box = document.getElementById("spmTabs");
        if (box) box.innerHTML = '';
        const body = document.getElementById("spmBody");
        if (body) body.innerHTML = '<div class="spm-empty"><i class="fas fa-database"></i><br>Data SPM belum tersedia.</div>';
    },

    renderTabs: function () {
        const self = this;
        const box = document.getElementById("spmTabs");
        if (!box) return;
        box.innerHTML = "";
        this.periods.forEach(function (p) {
            const b = document.createElement("button");
            b.type = "button";
            b.className = "spm-tab" + (p === self.current ? " active" : "");
            const n = self.counts[p] || 0;
            b.textContent = p + (n ? " (" + n + ")" : "");
            b.addEventListener("click", function () { self.select(p); });
            box.appendChild(b);
        });
        const meta = document.createElement("span");
        meta.className = "spm-meta";
        meta.id = "spmMeta";
        box.appendChild(meta);
    },

    select: function (periode) {
        this.current = periode;
        const self = this;
        document.querySelectorAll("#spmTabs .spm-tab").forEach(function (t, i) {
            t.classList.toggle("active", self.periods[i] === periode);
        });
        if (this.cache[periode]) {
            this.render(this.cache[periode]);
            return;
        }
        const body = document.getElementById("spmBody");
        if (body) body.innerHTML = '<div class="spm-loading"><i class="fas fa-spinner fa-spin"></i>Memuat data ' + this.esc(periode) + '...</div>';
        fetch("api/get_spm.php?periode=" + encodeURIComponent(periode) + "&ts=" + Date.now(), { cache: "no-store" })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json || !json.status) throw new Error("bad response");
                self.periods = json.periods || self.periods;
                self.counts = json.counts || self.counts;
                self.renderTabs();
                self.cache[periode] = json;
                self.render(json);
            })
            .catch(function () {
                if (body) body.innerHTML = '<div class="spm-empty">Gagal memuat data. Silakan tutup dan coba lagi.</div>';
            });
    },

    esc: function (s) {
        return String(s == null ? "" : s)
            .replace(/&/g, "&amp;").replace(/</g, "&lt;")
            .replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    },

    fmt: function (v) {
        const n = Number(v || 0);
        if (Math.abs(n - Math.round(n)) < 0.0001) {
            return Math.round(n).toLocaleString("id-ID");
        }
        return n.toLocaleString("id-ID", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    render: function (json) {
        const body = document.getElementById("spmBody");
        if (!body) return;
        const meta = document.getElementById("spmMeta");
        const groups = json.groups || {};
        const kec = json.kecamatan || [];
        const names = Object.keys(groups);

        if (meta) meta.textContent = json.total_rows + " baris • " + names.length + " jenis layanan • " + kec.length + " kecamatan";
        const foot = document.getElementById("spmFootNote");
        if (foot) foot.textContent = "Periode: " + json.periode + " • Sumber: Database portal_dkk (otomatis terbarui dari Admin → Kelola SPM)";

        if (!names.length) {
            body.innerHTML = '<div class="spm-empty">Belum ada data pada periode ini.</div>';
            return;
        }

        // Header meniru Excel: No | Jenis Layanan SPM | [Indikator Kinerja / Jenis Layanan SPM (C+D)] |
        // SATUAN | [Indikator Pencapaian / Output Kecamatan (12 kec)] | TOTAL. Tanpa kolom Sasaran.
        // FIX: colgroup 18 kolom (No 44 + Layanan 260 + Sub 50 + Indikator 320 + Satuan 110 + 12*Kec 80-110 + Total 90) agar browser tidak salah letak header baris kedua.
        let html = '<table class="spm-table"><colgroup>';
        html += '<col style="width:44px"><col style="width:260px"><col style="width:50px"><col style="width:320px"><col style="width:110px">';
        for (let i = 0; i < 12; i++) html += '<col style="width:90px">';
        html += '<col style="width:90px">';
        html += '</colgroup><thead><tr>';
        html += '<th rowspan="2" class="h-no">No</th>';
        html += '<th rowspan="2" class="h-layanan">Jenis Layanan SPM</th>';
        html += '<th colspan="2" rowspan="2" class="h-indikator">Indikator Kinerja / Jenis Layanan SPM</th>';
        html += '<th rowspan="2" class="h-satuan">SATUAN</th>';
        html += '<th colspan="12" class="h-kec-group">Indikator Pencapaian / Output Kecamatan</th>';
        html += '<th rowspan="2" class="h-total">TOTAL</th>';
        html += '</tr><tr>';
        kec.forEach(function (k) {
            html += '<th class="h-kec">' + SpmModal.esc(k) + '</th>';
        });
        html += '</tr></thead><tbody>';

        const self = this;
        // Label struktural baris utama (template Excel). BUKAN data: nilai
        // target/satuan diisi dari database bila baris induk tersedia.
        const SASARAN_LABEL = "\u2022 Jumlah yang Harus Dilayani :";
        names.forEach(function (layanan, li) {
            const rows = groups[layanan];
            const layNo = li + 1;
            // Baris utama SETIAP jenis layanan (selalu dirender, biru penuh).
            // Bila database memuat baris induknya, pakai nilai aslinya;
            // bila tidak, cell nilai dibiarkan kosong (tanpa mengarang data).
            let startIdx = 0;
            let p = null;
            if (rows.length && /jumlah yang harus dilayani/i.test(rows[0].indikator || "")) {
                p = rows[0];
                startIdx = 1;
            }
            const pTargets = (p && p.targets) || {};
            html += '<tr class="row-layanan">';
            html += '<td class="c-no">' + layNo + '</td>';
            html += '<td class="c-layanan">' + self.esc(layanan) + '</td>';
            html += '<td class="c-sub" colspan="2">' + self.esc(p ? p.indikator : SASARAN_LABEL) + '</td>';
            html += '<td class="c-satuan">' + self.esc((p && p.satuan) || "") + '</td>';
            kec.forEach(function (k) {
                const tv = pTargets[k];
                html += '<td class="c-angka">' + ((tv === null || tv === undefined || tv === "") ? "" : self.fmt(tv)) + '</td>';
            });
            html += '<td class="c-total">' + ((p && p.total !== null && p.total !== undefined && p.total !== "") ? self.fmt(p.total) : "") + '</td>';
            html += '</tr>';
            let num = 1;
            for (let i = startIdx; i < rows.length; i++) {
                const row = rows[i];
                // Kuning (seperti Excel) hanya untuk baris total kabupaten:
                // total_manual terisi DAN tidak ada rincian target kecamatan.
                let kecSum = 0;
                kec.forEach(function (k) { kecSum += Number((row.targets || {})[k]) || 0; });
                const isManual = (row.total_manual !== null && row.total_manual !== "" && kecSum === 0);
                html += '<tr' + (isManual ? ' class="row-manual"' : '') + '>';
                // Kolom A/B selalu kosong di baris indikator: No + nama layanan
                // sudah tampil pada baris utama (biru) di atas grupnya.
                html += '<td class="c-no"></td>';
                html += '<td class="c-layanan"></td>';
                // Kolom C: sub_no bila ada, jika tidak pakai nomor urut dalam grup.
                const subNo = (row.sub_no !== null && row.sub_no !== undefined && String(row.sub_no).trim() !== "")
                    ? String(row.sub_no).trim() : String(num);
                html += '<td class="c-sub">' + self.esc(subNo) + '</td>';
                html += '<td class="c-indikator">' + self.esc(row.indikator) + '</td>';
                html += '<td class="c-satuan">' + self.esc(row.satuan || "") + '</td>';
                kec.forEach(function (k) {
                    const tv = (row.targets || {})[k];
                    html += '<td class="c-angka">' + ((tv === null || tv === undefined || tv === "") ? "" : self.fmt(tv)) + '</td>';
                });
                html += '<td class="c-total">' + self.fmt(row.total) + '</td>';
                html += '</tr>';
                num++;
            }
        });
        html += '</tbody></table>';
        body.innerHTML = html;
        body.scrollTop = 0;
    }
};

document.addEventListener("DOMContentLoaded", function () {
    SpmModal.init();
});
