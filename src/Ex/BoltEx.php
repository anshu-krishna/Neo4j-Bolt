<?php
namespace Krishna\Neo4j\Ex;

use Throwable;

/*
	ALL EXCEPTIONS THROWN FROM THIS DRIVER ARE A SUB-CLASS OF BoltEx.
*/
class BoltEx extends \Exception {
	public readonly int|string $errcode;
	public function __construct(string $message = "", int|string $code = 0, ?Throwable $previous = null) {
		parent::__construct($message, intval($code), $previous);
		$this->errcode = $code;
	}
}