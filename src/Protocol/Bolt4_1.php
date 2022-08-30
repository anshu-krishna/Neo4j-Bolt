<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\E_State;
use Krishna\Neo4j\Ex\BoltEx;
use Krishna\Neo4j\Helper\ListType;
use Krishna\Neo4j\Protocol\Reply\{I_Reply, Record, Success};

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
		if($this->state !== E_State::READY) {
			throw new BoltEx('Bolt is not in Ready state');
		}
		$reply = $this->write('Begin', 0x11, [static::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db)]);
		if($reply instanceof Success) {
			$this->state = E_State::TX_READY;
			$this->transaction = true;
		} else {
			$this->state = E_State::FAILED;
		}
		return $reply;
	}
	public function commit(): I_Reply {
		if($this->state !== E_State::TX_READY) {
			throw new BoltEx('Bolt is not in TX_Ready state');
		}
		$reply = $this->write('Commit', 0x12);
		if($reply instanceof Success) {
			$this->state = E_State::READY;
			$this->transaction = false;
		} else {
			$this->state = E_State::FAILED;
		}
		return $reply;
	}
	public function rollback(): I_Reply {
		if($this->state !== E_State::TX_READY) {
			throw new BoltEx('Bolt not in TX_Ready state');
		}
		$reply = $this->write('Rollback', 0x13);
		if($reply instanceof Success) {
			$this->state = E_State::READY;
			$this->transaction = false;
		} else {
			$this->state = E_State::FAILED;
		}
		return $reply;
	}
	public function getQueryMeta(): ?I_Reply {
		return $this->qmeta;
	}
	public function queryValid(): bool {
		return $this->qmeta instanceof Success;
	}
	public function moreResults(): bool {
		return match($this->state) {
			E_State::STREAMING => true,
			E_State::TX_STREAMING => true,
			default => false
		};
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
		if($this->moreResults()) {
			throw new BoltEx('Previous query is active. Please \'pull all\' or \'discard\' previous query.');
		}
		$reply = $this->write('Run', 0x10, [
			$query,
			(object) $parameters,
			static::makeExtra($bookmarks, $tx_timeout, $tx_metadata, $readMode, $db)
		]);
		$this->qmeta = $reply;
		if($reply instanceof Success) {
			$this->state = $this->transaction ? E_State::TX_STREAMING : E_State::STREAMING;
		} else {
			$this->state = E_State::FAILED;
			if($autoResetOnFaiure) {
				$this->reset();
			}
		}
		return $reply;
	}
	public function discard(int $count = -1, int $qid = -1): ?I_Reply {
		if(match($this->state) {
			E_State::STREAMING => false,
			E_State::TX_STREAMING => false,
			default => true
		}) { return null; }
		if($qid === -1) {
			$qid = $this->qmeta?->qid ?? -1;
		}
		$reply = $this->write('Discard', 0x2F, [(object) ['n' => $count, 'qid' => $qid]]);
		if($reply instanceof Success) {
			if(!($reply->has_more ?? false)) {
				$this->state = $this->transaction ? E_State::TX_READY : E_State::READY;
			}
		} else {
			$this->state = E_State::FAILED;
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
		if(!$this->moreResults()) { return null; }
		if($qid === -1) {
			$qid = $this->qmeta?->qid ?? -1;
		}
		$fields = $this->qmeta['fields'] ?? [];
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
			$this->state = $this->transaction ? E_State::TX_READY : E_State::READY;
			$this->qmeta = $ret;
		}
		return ($count === 1) ? ($result[0] ?? null) : $result;
	}
}