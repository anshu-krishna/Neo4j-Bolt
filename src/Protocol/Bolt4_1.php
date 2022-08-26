<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\Ex\BoltEx;
use Krishna\Neo4j\Helper\ListType;
use Krishna\Neo4j\Protocol\Reply\{I_Reply, Success};

class Bolt4_1 extends A_Bolt {
	const VERSION = 4.1;
	protected array $qstate = [
		'transact' => false,
		'meta' => null,
		'closed' => false
	];
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
		if($this->qstate['transact']) {
			throw new BoltEx('Previous transaction has not been closed');
		}
		$reply = $this->write('Begin', 0x11, [static::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db)]);
		if($reply instanceof Success) {
			$this->qstate['transact'] = true;
		}
		return $reply;
	}
	public function commit(): I_Reply {
		if(!$this->qstate['transact']) {
			throw new BoltEx('Transaction has not been started');
		}
		$reply = $this->write('Commit', 0x12);
		if($reply instanceof Success) {
			$this->qstate['transact'] = false;
		}
		return $reply;
	}
	public function rollback(): I_Reply {
		if(!$this->qstate['transact']) {
			throw new BoltEx('Transaction has not been started');
		}
		$reply = $this->write('Rollback', 0x13);
		if($reply instanceof Success) {
			$this->qstate['transact'] = false;
		}
		return $reply;
	}
	public function query(
		string $query,
		array $parameters = [],
		bool $autoResetOnFaiure = true,
		array $bookmarks = [],
		int $tx_timeout = -1,
		?array $tx_metadata = null,
		bool $readMode = false,
		?string $db = null
	): I_Reply {
		return $this->write('Run', 0x10, [
			$query,
			(object) $parameters,
			static::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db)
		]);
	}
}