<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\{AuthToken, Buffer, E_State, Logger, PackStream};
use Krishna\Neo4j\Conn\I_Conn;
use Krishna\Neo4j\Ex\{BoltEx, ConnEx, PackEx};
use Krishna\Neo4j\Protocol\Reply\{I_Reply, Success};
use Krishna\PackStream\{Packer, Structure};

abstract class A_Bolt {
	public readonly array $connMeta;
	protected E_State $state = E_State::DISCONNECTED;
	protected ?I_Reply $qmeta = null;
	protected bool $transaction = false;

	protected static function packetGenerator(?Buffer &$forLog, int $sig, array $fields): iterable {
		$forLog ??= Buffer::Writable();
		$length = count($fields);
		if ($length < Packer::TINY) { //TINY_STRUCT
			yield $send = pack('n', 1) . pack('C', 0xB0 | $length);
			$forLog->write($send);
		} elseif ($length < Packer::MEDIUM) { //STRUCT_8
			yield $send = pack('n', 2) . chr(0xDC) . pack('C', $length);
			$forLog->write($send);
		} elseif ($length < Packer::LARGE) { //STRUCT_16
			yield $send = pack('n', 4) . chr(0xDD) . pack('n', $length);
			$forLog->write($send);
		} else {
			throw new PackEx('Structure too big');
		}
		yield $send = pack('n', 1) . chr($sig);
		$forLog->write($send);
	
		foreach($fields as $val) {
			$packed = Buffer::Writable();
			$packed->writeIterable(Packer::pack($val));
			$packed->makeReadable();
			yield from $packed->getChunks();
			$forLog->write($packed->__toString());
		}
		yield $send = hex2bin('0000');
		$forLog->write($send);
		$forLog->makeReadable();
	}
	protected static function getTypeName(mixed $value) {
		if(is_object($value)) {
			// return $value::class;
			// return (new \ReflectionClass($value))->getShortName();
			$name = explode('\\', $value::class);
			return end($name);
		}
		return ucwords(gettype($value));
	}
	protected static function checkConstructMeta(array $meta): void {
		(function (AuthToken $auth, ?array $routing) {})(...$meta);
	}
	public function updateTimeout(float $timeout): bool {
		return $this->CONN->updateTimeout($timeout);
	}
	public function __construct(protected readonly I_Conn $CONN, public ?Logger $logger, array $meta) {
		try {
			static::checkConstructMeta($meta);
		} catch (\Throwable $th) {
			throw new BoltEx('Invalid meta parameter in constructor; Expected ["auth" => AuthToken, "routing" => ?array]');
		}
		$this->state = E_State::CONNECTED;
		$token = $meta['auth']->token;
		if($meta['routing'] !== null) {
			$token['routing'] = (object) $meta['routing'];
		}
		$reply = $this->write('Hello', 0x01, [$token], false);
		if($reply instanceof Reply\Success) {
			$this->connMeta = $reply->getArrayCopy();
			$this->state = E_State::READY;
		} elseif($reply instanceof Reply\Failure) {
			$this->state = E_State::DEFUNCT;
			$this->CONN->disconnect();
			throw new ConnEx($reply->message, $reply->code);
		} else {
			$this->state = E_State::DEFUNCT;
			$this->CONN->disconnect();
			throw new BoltEx('Unknown Error');
		}
	}
	public function __destruct() {
		$this->disconnect();
	}
	protected function onConnErr(BoltEx $ex) {
		$this->state = E_State::DEFUNCT;
		$this->CONN->disconnect();
		throw $ex;
	}
	protected function connRead(int $length): ?string {
		try { return $this->CONN->read($length); }
		catch (ConnEx $ex) {
			$this->state = E_State::DEFUNCT;
			$this->CONN->disconnect();
			throw $ex;
		}
	}
	protected function connWrite(string|Buffer $buffer): void {
		try { $this->CONN->write($buffer); }
		catch (ConnEx $ex) {
			$this->state = E_State::DEFUNCT;
			$this->CONN->disconnect();
			throw $ex;
		}
	}
	protected function connWriteIterable(iterable $parts): void {
		try { $this->CONN->writeIterable($parts); }
		catch (ConnEx $ex) {
			$this->state = E_State::DEFUNCT;
			$this->CONN->disconnect();
			throw $ex;
		}
	}
	protected function write(string $logTitle, int $sig, array $fields = [], bool $autoRetry = true, bool $noReply = false): ?I_Reply {
		if($this->state->connOff()) {
			throw new BoltEx('Bolt is in ' . $this->state->stringify() . ' state');
		}
		$this->connWriteIterable(static::packetGenerator($packet, $sig, $fields));
		$this->logger?->logWrite($packet, $logTitle);
		if($noReply) { return null; }
		$reply = $this->read($logTitle);
		if($reply instanceof Reply\Ignored) {
			$this->state = E_State::INTERRUPTED;
			if($autoRetry) {
				$this->reset();
				$this->connWrite($packet);
				$this->logger?->logWrite($packet, $logTitle);
				$reply = $this->read($logTitle);
			}
		}
		return $reply;
	}
	protected function read(string $logTitle): I_Reply {
		if($this->state->connOff()) {
			throw new BoltEx('Bolt is in ' . $this->state->stringify() . ' state');
		}
		$end = hex2bin('0000');
		$buffer = Buffer::Writable();
		do { // NOOP
			$tag = $this->connRead(2);
		} while($tag === $end);
		while(true) {
			if($tag === $end) { break; }
			$len = unpack('n', $tag)[1] ?? 0;
			$buffer->write($this->connRead($len));
			$tag = $this->connRead(2);
		}
		$value = PackStream::unpack($buffer);
		// Convert to Reply Struct
		if($value instanceof Structure) {
			[$name, $value] = match($value->sig) {
				0x70 => ['Success', Reply\Success::fromStructure($value)],
				0x7E => ['Ignored', Reply\Ignored::fromStructure($value)],
				0x7F => ['Failure', Reply\Failure::fromStructure($value)],
				0x71 => ['Record', Reply\Record::fromStructure($value)]
			};
			$this->logger?->logRead(
				$buffer,
				"{$logTitle} = {$name}"
			);
		} else {
			$this->logger?->logRead(
				$buffer,
				"{$logTitle} = " . static::getTypeName($value)
			);
			$this->disconnect();
			throw new BoltEx('Invalid reply received');
		}
		return $value;
	}
	public function reset(): I_Reply {
		$reply = $this->write('Reset', 0x02, autoRetry: false);
		if($reply instanceof Success) {
			$this->state = $this->transaction ? E_State::TX_READY : E_State::READY;
		}
		return $reply;
	}
	public function disconnect(): void {
		try {
			$this->write('Goodbye', 0x02, autoRetry: false, noReply: true);
		} catch (ConnEx $th) {}
		$this->CONN->disconnect();
		$this->state = E_State::DEFUNCT;
	}
}