/* ==========================================================
   PORTAL DKK — Admin Change Notifier (portal-notify.js)
   Mengirim event "data-updated" ke tab Portal yang terbuka
   SETELAH operasi database admin berhasil (redirect ?msg=...).
   - Mekanisme: localStorage (sinkron, andal walau halaman
     admin redirect) + BroadcastChannel (pengiriman instan).
   - Payload: {type, source, page, at} — tanpa data sensitif.
   - Daftar msg sukses didefinisikan per halaman via
     window.PORTAL_NOTIFY_OK (allowlist eksplisit).
     Msg error (desa_error, exists, invalid, ...) TIDAK memicu.
   - Query msg dibersihkan via history.replaceState agar
     refresh halaman admin tidak memancarkan ulang event.
   - Read-only terhadap database; tidak mengubah alur CRUD.
========================================================== */
(function () {
    try {
        var ok = window.PORTAL_NOTIFY_OK || [];
        if (!ok || ok.length === 0) return;

        var q;
        try {
            q = new URLSearchParams(window.location.search);
        } catch (e) {
            return;
        }
        var msg = q.get("msg") || q.get("msg_sp") || "";
        if (!msg || ok.indexOf(msg) === -1) return;

        var page = "";
        try {
            page = window.location.pathname.split("/").pop();
        } catch (e) {}

        var payload = JSON.stringify({
            type: "data-updated",
            source: "admin",
            page: page,
            msg: msg,
            at: Date.now()
        });

        try {
            window.localStorage.setItem("portal_dkk_data_updated", payload);
        } catch (e) {}

        try {
            if (typeof window.BroadcastChannel !== "undefined") {
                new window.BroadcastChannel("portal_dkk_data_updated")
                    .postMessage(JSON.parse(payload));
            }
        } catch (e) {}

        // Bersihkan ?msg agar reload halaman admin tidak re-notify.
        try {
            q.delete("msg");
            q.delete("msg_sp");
            var rest = q.toString();
            var clean = window.location.pathname +
                (rest ? "?" + rest : "") +
                window.location.hash;
            window.history.replaceState(null, "", clean);
        } catch (e) {}
    } catch (e) {}
})();
