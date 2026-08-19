<div class="orbit-scene">

    <!-- Ring -->
    <div class="orbit-ring"></div>

    <!-- Logo Tengah -->
    <div class="logo-center" id="portalLogo">
        <img src="../assets/img/logo.png" alt="Logo DKK">
    </div>

    <!-- Ikon aplikasi -->
    <?php foreach($orbit as $app): ?>

        <a
            href="<?= htmlspecialchars($app['url']) ?>"
            class="orbit-item"
            style="background:<?= htmlspecialchars($app['color']) ?>"
            data-name="<?= htmlspecialchars($app['name']) ?>">

            <i class="<?= htmlspecialchars($app['icon']) ?>"></i>

        </a>

    <?php endforeach; ?>

</div>