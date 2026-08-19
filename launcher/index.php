<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once "../app/Core/Loader.php";

$orbit = LauncherService::orbit();

$categories = LauncherService::categories();

?>
<!doctype html>

<html>

<head>

<meta charset="utf-8">

<title>Launcher</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>

body{

background:#06162F;

font-family:Arial;

color:white;

padding:40px;

}

.orbit{

display:flex;

justify-content:center;

gap:30px;

margin-bottom:50px;

}

.icon{

width:90px;

height:90px;

border-radius:50%;

display:flex;

justify-content:center;

align-items:center;

font-size:35px;

background:#0E5CAD;

cursor:pointer;

transition:.3s;

}

.icon:hover{

transform:translateY(-10px);

}

.grid{

display:grid;

grid-template-columns:repeat(auto-fill,minmax(220px,1fr));

gap:15px;

}

.card{

background:white;

color:#333;

border-radius:12px;

padding:18px;

}

.card i{

font-size:26px;

margin-right:10px;

}

h2{

color:#4FC3F7;

}

</style>

</head>

<body>

<h1 align="center">

PORTAL DKK

</h1>

<div class="orbit">

<?php foreach($orbit as $app): ?>

<div class="icon"

style="background:<?= $app['color']?>">

<i class="<?= $app['icon']?>"></i>

</div>

<?php endforeach;?>

</div>

<?php

foreach($categories as $kategori=>$apps):

?>

<h2>

<?= strtoupper($kategori)?>

</h2>

<div class="grid">

<?php

foreach($apps as $app):

?>

<div class="card">

<i class="<?= $app['icon']?>"

style="color:<?= $app['color']?>">

</i>

<b><?= $app['name']?></b>

<br><br>

<?= $app['description']?>

</div>

<?php

endforeach;

?>

</div>

<br><br>

<?php

endforeach;

?>

</body>

</html>