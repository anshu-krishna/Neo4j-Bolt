<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\Ex\BoltEx;
use Krishna\Neo4j\Helper\ListType;

class Bolt4_4 extends Bolt4_3 {
	const VERSION = 4.4;
	public function beginTransaction(
		array $bookmarks = [],
		int $tx_timeout = -1,
		?array $tx_metadata = null,
		bool $readMode = false,
		?string $db = null,
		?string $imp_user = null
	) {
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
		if($imp_user !== null) {
			$extra['imp_user'] = $imp_user;
		}
		return $this->write('Begin', 0x11, [$extra]);
	}
}