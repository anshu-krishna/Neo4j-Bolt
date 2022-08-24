<?php
require_once "vendor/autoload.php";

use Krishna\Neo4j\AuthToken;
use Krishna\Neo4j\Bolt;
use Krishna\Neo4j\Buffer;
use Krishna\Neo4j\Conn\Socket;
use Krishna\Neo4j\E_Version;
use Krishna\Neo4j\Logger;
use Krishna\Neo4j\Protocol\Helper;

set_time_limit(5);

// $l = new Logger(rowSize: 40);
// $s = new Socket();

// $s->connect('127.0.0.1', 7687, 2);

// $s->writeIterable([
// 	hex2bin('6060b017'),
// 	hex2bin('00000104'),
// 	hex2bin('00000000'),
// 	hex2bin('00000000'),
// 	hex2bin('00000000')
// ]);
// $l->logRead(Buffer::Readable($s->read(4)), 'Handshake');
// $packet = Helper::packSendable($p, 0x01, AuthToken::basic('neo4j', 'open')->token);
// $s->writeIterable($packet);
// $l->logWrite($p, 'Hello');

// $l->logRead(Buffer::Readable($s->read(20)), 'Hello');

$bolt = new Bolt(
	auth: AuthToken::basic('neo4j', 'open'),
	logger: new Logger(rowSize: 40)
);
$bolt->useVersion(E_Version::V4_1)->getConnection();