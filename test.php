<?php

require_once "vendor/autoload.php";

use Krishna\Neo4j\{AuthToken, BoltMaker, Logger, E_Version as Ver};

set_time_limit(5);

$neo = new BoltMaker(
	auth: AuthToken::basic('neo4j', 'open'),
	logger: new Logger(rowSize: 40)
);
$bolt = $neo->useVersion(Ver::V4_2, Ver::V4_1)->makeBolt();

var_dump($bolt->beginTransaction());