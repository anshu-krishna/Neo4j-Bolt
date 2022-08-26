<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\Ex\BoltEx;
use Krishna\Neo4j\Helper\ListType;
use Krishna\Neo4j\Protocol\Reply\I_Reply;

class Bolt4_1 extends A_Bolt {
	const VERSION = 4.1;
	protected static function makeExtra(
		array $bookmarks,
		int $tx_timeout,
		?array $tx_metadata,
		bool $readMode,
		?string $db
	): array {
		$extra = [];
		if(ListType::isStringList($bookmarks)) {
			$extra['bookmarks'] = array_values($bookmarks);
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
		return $extra;
	}
	public function beginTransaction(
		array $bookmarks = [],
		int $tx_timeout = -1,
		?array $tx_metadata = null,
		bool $readMode = false,
		?string $db = null
	): I_Reply {
		return $this->write('Begin', 0x11, [static::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db)]);
	}
	public function commit(): I_Reply {
		return $this->write('Commit', 0x12);
	}
	public function rollback(): I_Reply {
		return $this->write('Rollback', 0x13);
	}
	public function query(
		string $query,
		array $parameters = [],
		array $bookmarks = [],
		int $tx_timeout = -1,
		?array $tx_metadata = null,
		bool $readMode = false,
		?string $db = null
	): I_Reply {
		return $this->write('Run', 0x10, [
			$query,
			$parameters,
			static::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db)
		]);
	}
}