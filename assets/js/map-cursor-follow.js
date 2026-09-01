/* ==========================================================
   EFEK: PETA MENGIKUTI KURSOR
   ----------------------------------------------------------
   Dibuat dari awal, khusus untuk elemen #mapStage saja.
   Tidak menyentuh elemen lain (header, card kiri, card kanan).

   CARA KERJA:
   1. Kita simpan posisi kursor relatif terhadap tengah layar
      (angka -1 sampai 1, di mana 0 = tengah layar).
   2. Posisi itu dikalikan dengan jarak maksimum pergeseran
      (maxMove) untuk jadi "target" posisi peta.
   3. Posisi peta saat ini "mengejar" target itu sedikit demi
      sedikit tiap frame (easing), supaya gerakannya halus,
      bukan langsung meloncat ke posisi kursor.
========================================================== */

(function () {
    "use strict";

    // 1. Ambil elemen peta. Kalau tidak ketemu, hentikan script.
    var mapStage = document.getElementById("mapStage");
    if (!mapStage) {
        console.warn("Elemen #mapStage tidak ditemukan.");
        return;
    }

    // 2. Pengaturan efek — boleh diubah sesuai selera.
    // maxMove dibuat KECIL supaya peta terasa tetap "di tengah",
    // tidak bikin pusing, tapi masih ada sedikit kesan hidup/interaktif.
    var maxMove = 6;    // seberapa jauh peta boleh bergeser (pixel) — dulu 25, sekarang jauh lebih kecil
    var easing  = 0.05; // dibuat lebih lambat/halus supaya gerakan makin lembut, tidak terasa tersentak

    // 3. Variabel posisi.
    var targetX = 0, targetY = 0;   // posisi yang ingin dicapai (mengikuti kursor)
    var currentX = 0, currentY = 0; // posisi peta saat ini (bergerak halus menuju target)

    // 4. Saat kursor bergerak, hitung target baru.
    window.addEventListener("mousemove", function (event) {
        // Ubah posisi kursor jadi angka -1 sampai 1 (0 = tengah layar)
        var relativeX = (event.clientX / window.innerWidth - 0.5) * 2;
        var relativeY = (event.clientY / window.innerHeight - 0.5) * 2;

        targetX = relativeX * maxMove;
        targetY = relativeY * maxMove;
    });

    // 5. Saat kursor keluar dari halaman, peta kembali ke posisi tengah.
    window.addEventListener("mouseleave", function () {
        targetX = 0;
        targetY = 0;
    });

    // 6. Loop animasi — dijalankan terus menerus tiap frame.
    function updatePosition() {
        // Gerakkan posisi saat ini sedikit lebih dekat ke target (efek halus/easing)
        currentX += (targetX - currentX) * easing;
        currentY += (targetY - currentY) * easing;

        // Terapkan pergeseran hanya ke elemen peta
        mapStage.style.transform =
            "translate3d(" + currentX + "px, " + currentY + "px, 0)";

        requestAnimationFrame(updatePosition);
    }

    updatePosition();
})();