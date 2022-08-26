<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\Ex\BoltEx;
use Krishna\Neo4j\Helper\ListType;
use Krishna\Neo4j\Protocol\Reply\I_Reply;

class Bolt4_1 extends A_Bolt {
	const VERSION = 4.1;
	public function beginTransaction(
		array $bookmarks = [],
		int $tx_timeout = -1,
		?array $tx_metadata = null,
		bool $readMode = false,
		?string $db = null
	): I_Reply {
		$extra = [];
		if(ListType::isStringList($bookmarks)) {
			$extra['bookmarks'] = $bookmarks;
		} else {
			throw new BoltEx('parameter bookmarks must be a list of strings');
		}
		if($tx_metadata > 0) {
			$extra['tx_timeout'] = $tx_timeout;
		}
		if($tx_metadata !== null) {
			$extra['tx_metadata'] = (object) $tx_metadata;
		}
		if($readMode) {
			$extra['mode'] = 'r';
		}
		if($db !== null) {
			$extra['db'] = $db;
		}
		return $this->write('Begin', 0x11, [$extra]);
	}
	public function commit(): I_Reply {

	}
}