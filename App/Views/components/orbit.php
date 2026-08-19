<div class="orbit">

<div class="logo">

<img src="assets/img/logo.png" alt="Logo">

</div>

<?php foreach($orbit as $app): ?>

<div
class="orbit-item"

data-color="<?= htmlspecialchars($app['color']) ?>"

data-url="<?= htmlspecialchars($app['url']) ?>">

<i class="<?= htmlspecialchars($app['icon']) ?>"></i>

</div>

<?php endforeach; ?>

</div>