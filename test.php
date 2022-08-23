<?php
require_once "vendor/autoload.php";

use Krishna\Neo4j\PackStream\V1\GenericStruct;
use Krishna\Neo4j\PackStream\V1\Type\Date;

$d = new Date(10);

$d->bench();