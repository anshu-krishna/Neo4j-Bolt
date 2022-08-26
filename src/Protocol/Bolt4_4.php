<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\Ex\BoltEx;
use Krishna\Neo4j\Helper\ListType;
use Krishna\Neo4j\Protocol\Reply\I_Reply;

class Bolt4_4 extends Bolt4_3 {
	const VERSION = 4.4;
	protected static function makeExtra(
		array $bookmarks,
		int $tx_timeout,
		?array $tx_metadata,
		bool $readMode,
		?string $db,
		?string $imp_user = null
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
		if($imp_user !== null) {
			$extra['imp_user'] = $imp_user;
		}
		return $extra;
	}
	public function beginTransaction(
		array $bookmarks = [],
		int $tx_timeout = -1,
		?array $tx_metadata = null,
		bool $readMode = false,
		?string $db = null,
		?string $imp_user = null
	): I_Reply {
		return $this->write('Begin', 0x11, [static::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db, $imp_user)]);
	}
	public function query(
		string $query,
		array $parameters = [],
		array $bookmarks = [],
		int $tx_timeout = -1,
		?array $tx_metadata = null,
		bool $readMode = false,
		?string $db = null,
		?string $imp_user = null
	): I_Reply {
		return $this->write('Run', 0x10, [
			$query,
			(object) $parameters,
			static::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db, $imp_user)
		]);
	}
}