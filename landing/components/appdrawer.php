<div id="appDrawer" class="app-drawer">

    <div class="drawer-header">

        <input
            type="text"
            id="searchApp"
            placeholder="🔍 Cari aplikasi...">

        <button id="closeDrawer">&times;</button>

    </div>

    <?php foreach($categories as $category => $apps): ?>

        <div class="drawer-category">

            <h3><?= htmlspecialchars($category) ?></h3>

            <div class="drawer-grid">

                <?php foreach($apps as $app): ?>

                    <a href="<?= htmlspecialchars($app['url']) ?>" class="drawer-card">

                        <i class="<?= htmlspecialchars($app['icon']) ?>"
                           style="color:<?= htmlspecialchars($app['color']) ?>"></i>

                        <span><?= htmlspecialchars($app['name']) ?></span>

                    </a>

                <?php endforeach; ?>

            </div>

        </div>

    <?php endforeach; ?>

</div>