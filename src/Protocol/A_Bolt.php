<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\Buffer;
use Krishna\Neo4j\Ex\PackEx;
use Krishna\Neo4j\PackStream\V1\Packer;

abstract class A_Bolt {
	protected static function packSendable(?Buffer &$forLog, int $sig, mixed ...$fields): iterable {
		$forLog ??= Buffer::Writable();
		$length = count($fields);
		if ($length < Packer::TINY) { //TINY_STRUCT
			yield $send = pack('n', 1) . pack('C', 0xB0 | $length);
			$forLog->write($send);
		} elseif ($length < Packer::MEDIUM) { //STRUCT_8
			yield $send = pack('n', 2) . chr(0xDC) . pack('C', $length);
			$forLog->write($send);
		} elseif ($length < Packer::LARGE) { //STRUCT_16
			yield $send = pack('n', 4) . chr(0xDD) . pack('n', $length);
			$forLog->write($send);
		} else {
			throw new PackEx('Structure too big');
		}
		yield $send = pack('n', 1) . chr($sig);
		$forLog->write($send);
	
		foreach($fields as $val) {
			$packed = Buffer::Writable();
			$packed->writeIterable(Packer::pack($val));
			$packed->makeReadable();
			yield from $packed->getChunks();
			$forLog->write($packed->__toString());
		}
		yield $send = hex2bin('0000');
		$forLog->write($send);
		$forLog->makeReadable();
	}
}