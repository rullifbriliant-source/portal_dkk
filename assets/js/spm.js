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

        let html = '<table class="spm-table"><thead><tr>';
        html += '<th rowspan="2" style="min-width:44px">No</th>';
        html += '<th rowspan="2" style="min-width:220px">Jenis Layanan</th>';
        html += '<th rowspan="2" style="min-width:260px">Indikator / Sub-Indikator</th>';
        html += '<th rowspan="2" style="min-width:90px">Satuan</th>';
        html += '<th rowspan="2" style="min-width:110px">Sasaran</th>';
        html += '<th colspan="' + kec.length + '">Target Per Kecamatan</th>';
        html += '<th rowspan="2" style="min-width:90px">TOTAL</th>';
        html += '</tr><tr>';
        kec.forEach(function (k) {
            html += '<th style="min-width:78px">' + SpmModal.esc(k.charAt(0) + k.slice(1).toLowerCase()) + '</th>';
        });
        html += '</tr></thead><tbody>';

        let no = 1;
        const self = this;
        names.forEach(function (layanan) {
            const rows = groups[layanan];
            rows.forEach(function (row, idx) {
                html += '<tr>';
                html += '<td class="col-no">' + (no++) + '</td>';
                if (idx === 0) {
                    html += '<td class="col-layanan" rowspan="' + rows.length + '">' + self.esc(layanan) + '</td>';
                }
                html += '<td class="col-indikator">' + (row.sub_no ? self.esc(row.sub_no) + '. ' : '') + self.esc(row.indikator) + '</td>';
                html += '<td class="col-satuan">' + self.esc(row.satuan || "-") + '</td>';
                html += '<td class="col-sasaran">' + self.esc(row.sasaran || "-") + '</td>';
                kec.forEach(function (k) {
                    html += '<td class="col-angka">' + self.fmt((row.targets || {})[k]) + '</td>';
                });
                html += '<td class="col-total">' + self.fmt(row.total) + '</td>';
                html += '</tr>';
            });
        });
        html += '</tbody></table>';
        body.innerHTML = html;
        body.scrollTop = 0;
    }
};

document.addEventListener("DOMContentLoaded", function () {
    SpmModal.init();
});
