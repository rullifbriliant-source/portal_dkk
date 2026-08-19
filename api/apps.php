<?php

header("Content-Type: application/json");

$apps=require "../config/apps.php";

echo json_encode($apps);