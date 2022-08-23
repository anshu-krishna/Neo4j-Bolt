<?php
namespace Krishna\Neo4j\Conn;

use Krishna\Neo4j\Buffer;

interface I_Conn {
	public function connect(string $host, int $port, float $timeout);
	public function disconnect();
	public function read(int $length): ?string;
	public function write(string|Buffer $buffer);
}