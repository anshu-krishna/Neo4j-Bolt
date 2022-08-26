<?php

require_once "vendor/autoload.php";

use Krishna\Neo4j\{AuthToken, BoltMaker, Logger, E_Version as Ver};

set_time_limit(5);

$neo = new BoltMaker(
	auth: AuthToken::basic('neo4j', 'open'),
	// logger: new Logger(rowSize: 40)
);
// $bolt = $neo->useVersion(Ver::V4_1)->makeBolt();
$bolt = $neo->makeBolt();
var_dump($bolt::VERSION);

$bolt->beginTransaction();
// $bolt->commit();
$bolt->rollback();
$bolt->logger = new Logger(rowSize: 40);
var_dump($bolt->query('match (p:Person)-[a:ACTED_IN]->(m:Movie)<-[d:DIRECTED]-(p) return p.name as person, a.roles as role, collect(m.title) as movie'));