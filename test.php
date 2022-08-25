<?php
require_once "vendor/autoload.php";

use Krishna\Neo4j\AuthToken;
use Krishna\Neo4j\E_Version;
use Krishna\Neo4j\Logger;
use Krishna\Neo4j\Neo4j;

set_time_limit(5);

$neo = new Neo4j(
	auth: AuthToken::basic('neo4j', 'open'),
	logger: new Logger(rowSize: 40)
);
$bolt = $neo->useVersion(E_Version::V4_1)->getBolt();