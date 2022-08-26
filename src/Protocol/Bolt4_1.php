<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\Ex\BoltEx;
use Krishna\Neo4j\Helper\ListType;
use Krishna\Neo4j\Protocol\Reply\{I_Reply, Record, Success};

class Bolt4_1 extends A_Bolt {
	const VERSION = 4.1;
	protected array $qstate = [
		'transact' => false,
		'qid' => -1,
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
	public function getQueryMeta(): ?I_Reply {
		return $this->qstate['meta'];
	}
	public function queryValid(): bool {
		return $this->qstate['meta'] instanceof Success;
	}
	public function moreResults(): bool {
		return $this->qstate['closed'];
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
		$reply = $this->write('Run', 0x10, [
			$query,
			(object) $parameters,
			static::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db)
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
	public function discard(int $count = -1, int $qid = -1): ?I_Reply {
		if($this->qstate['meta'] === null || $this->qstate['closed']) { return null; }
		if($qid === -1) {
			$qid = $this->qstate['qid'];
		}
		$reply = $this->write('Discard', 0x2F, [(object) ['n' => $count, 'qid' => $qid]]);
		if($reply instanceof Success) {
			$this->qstate['closed'] = !($reply->has_more ?? false);
		}
		return $reply;
	}
	protected function puller(int $count = -1, int $qid = -1): \Generator {
		$reply = $this->write('Pull', 0x3F, [(object) ['n' => $count, 'qid' => $qid]]);
		while(true) {
			if($reply instanceof Record) {
				yield $reply;
				$reply = $this->read('Pull');
			} else {
				return $reply;
			}
		}
	}
	public function pull(int $count = -1, int $qid = -1) {
		if($this->qstate['meta'] === null || $this->qstate['closed']) { return null; }
		if($qid === -1) {
			$qid = $this->qstate['qid'];
		}
		$fields = $this->qstate['meta']['fields'] ?? [];
		$result = [];
		$iter = $this->puller($count, $qid);
		foreach($iter as $item) {
			if($item instanceof Record) {
				foreach($fields as $i => $key) {
					$item[$key] = &$item[$i];
					unset($item[$i]);
				}
			}
			$result[] = $item;
		}
		$ret = $iter->getReturn();
		if(!($ret->has_more ?? false)) {
			$this->qstate['closed'] = true;
			$this->qstate['meta'] = $ret;
		}
		return ($count === 1) ? ($result[0] ?? null) : $result;
	}
}