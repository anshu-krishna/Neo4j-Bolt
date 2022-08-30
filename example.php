<?php

require_once "vendor/autoload.php";

use Krishna\Neo4j\{AuthToken, BoltMaker, Logger, E_Version as Ver};
use Krishna\Neo4j\Conn\E_ConnType;

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


// $bolt->logger = new Logger(rowSize: 40);

$bolt->query('match (p:Person)-[a:ACTED_IN]->(m:Movie)<-[d:DIRECTED]-(p) return p.name as person, a.roles as role, collect(m.title) as movie limit 5');

var_dump($bolt->pull(2));
// var_dump($bolt->discard(1));
var_dump($bolt->pull());
var_dump($bolt->getQueryMeta());
$bolt->rollback();