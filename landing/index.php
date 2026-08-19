<?php

require_once "../app/Core/Loader.php";

$orbit = LauncherService::orbit();
$categories = LauncherService::categories();

?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="utf-8">

<title>Portal DKK</title>

<link rel="stylesheet"
href="../assets/css/style.css">

<link rel="stylesheet"
href="css/landing.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<div id="stars"></div>

<div class="landing">

<?php include "components/header.php"; ?>

<?php include "components/orbit.php"; ?>

<?php include "components/map.php"; ?>

<?php include "components/appdrawer.php"; ?>

<?php include "components/footer.php"; ?>

</div>

<script src="../assets/js/starfield.js"></script>
<script src="../assets/js/network.js"></script>
<script src="../assets/js/drawer.js"></script>
<script src="../assets/js/orbit.js"></script>
<link rel="stylesheet" href="assets/css/map.css">

<div id="tooltip"></div>

</body>

</html>