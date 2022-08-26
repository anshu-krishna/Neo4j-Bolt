<?php
namespace Krishna\Neo4j\Protocol;

class Bolt4_1 extends A_Bolt {
	const VERSION = 4.1;
	public function beginTransaction(
		array $bookmarks = [],
		int $tx_timeout = 15,
		?array $tx_metadata = null,
		string $mode = 'w',
		?string $db = null
	) {
		$extra = [];
		$extra['bookmarks'] = $bookmarks;
		$extra['tx_timeout'] = $tx_timeout;
		$extra['tx_metadata'] = ($tx_metadata === null) ? null : (object) $tx_metadata;
		$extra['mode'] = $mode;
		$extra['db'] = $db;
		return $this->write('Begin', 0x11, [$extra]);
	}
}