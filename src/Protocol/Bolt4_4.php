<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\Ex\BoltEx;
use Krishna\Neo4j\Protocol\Reply\{I_Reply, Success};

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
		$extra = parent::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db);
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
		if($this->qstate['transact']) {
			throw new BoltEx('Previous transaction has not been closed');
		}
		$reply = $this->write('Begin', 0x11, [static::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db, $imp_user)]);
		if($reply instanceof Success) {
			$this->qstate['transact'] = true;
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
		?string $db = null,
		?string $imp_user = null
	): I_Reply {
		$reply = $this->write('Run', 0x10, [
			$query,
			(object) $parameters,
			static::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db, $imp_user)
		]);
		$this->qstate['meta'] = $reply;
		if($reply instanceof Success) {
			$this->qstate['qid'] = $reply->qid ?? -1;
		} else {
			$this->qstate['qid'] = -1;
			$this->qstate['closed'] = true;
			if($autoResetOnFaiure) {
				$this->reset();
			}
		}
		return $reply;
	}
}