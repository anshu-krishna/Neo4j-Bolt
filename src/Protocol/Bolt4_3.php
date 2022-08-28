<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\Buffer;
use Krishna\Neo4j\E_State;
use Krishna\Neo4j\Ex\BoltEx;
use Krishna\Neo4j\Helper\ListType;
use Krishna\Neo4j\Protocol\Reply\I_Reply;
use Krishna\Neo4j\Protocol\Reply\Success;

class Bolt4_3 extends Bolt4_1 {
	const VERSION = 4.3;

	public function noop(): void {
		$noop = Buffer::Readable(hex2bin('0000'));
		$this->connWrite($noop);
	}
	public function route(array $routing, array $bookmarks, ?string $db = null): I_Reply {
		if(ListType::isStringList($bookmarks)) {
			$bookmarks = array_values($bookmarks);
		} else {
			throw new BoltEx('parameter bookmarks must be a list of strings');
		}
		if($this->transaction) {
			throw new BoltEx('Cannot send Route message while transaction is active');
		}
		$reply = $this->write('Route', 0x66, [(object) $routing, $bookmarks, $db]);
		if(!$reply instanceof Success) {
			$this->state = E_State::FAILED;
		}
		return $reply;
	}
}