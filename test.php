<?php
require_once "vendor/autoload.php";

use Krishna\Neo4j\PackStream\V1\Type\Date;

$d = new Date(15);
echo $d;