<?php

define("ROOT_PATH",__DIR__);

require "app/Services/MapService.php";

echo "<pre>";

print_r(

MapService::all()

);