<?php
namespace Krishna\Neo4j\Protocol;

use Krishna\Neo4j\AuthToken;
use Krishna\Neo4j\Buffer;
use Krishna\Neo4j\Conn\I_Conn;
use Krishna\Neo4j\Ex\PackEx;
use Krishna\Neo4j\Logger;
use Krishna\Neo4j\PackStream\V1\GenericStruct;
use Krishna\Neo4j\PackStream\V1\I_PackStruct;
use Krishna\Neo4j\PackStream\V1\Packer;
use Krishna\Neo4j\PackStream\V1\Unpacker;

abstract class A_Bolt {
	protected static function packSendable(?Buffer &$forLog, int $sig, array $fields): iterable {
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
	public function __construct(protected readonly I_Conn $CONN, AuthToken $auth, ?array $routing, public ?Logger $logger) {
		$token = $auth->token;
		if($routing !== null) {
			$token['routing'] = (object) $routing;
		}
		$reply = $this->write('Hello', 0x01, [$token]);
	}
	protected function write(string $logTitle, int $sig, array $fields = [], bool $autoRetry = true, bool $noReply = false) {
		$packet = Buffer::Writable();
		$packet->writeIterable(static::packSendable($send, $sig, $fields));
		$packet->makeReadable();
		$this->CONN->write($packet);
		$this->logger?->logWrite($send, $logTitle);
		if($noReply) { return; }
		$reply = $this->read($logTitle);
		if($autoRetry && $reply instanceof Reply\Ignored) {
			$this->reset();
			$this->CONN->write($packet);
			$this->logger?->logWrite($send, $logTitle);
			$reply = $this->read($logTitle);
		}
		return $reply;
	}
	protected function read(string $logTitle) {
		$end = hex2bin('0000');
		$buffer = Buffer::Writable();
		do { // NOOP
			$tag = $this->CONN->read(2);
		} while($tag === $end);
		while(true) {
			if($tag === $end) { break; }
			$len = unpack('n', $tag)[1] ?? 0;
			$buffer->write($this->CONN->read($len));
			$tag = $this->CONN->read(2);
		}
		$buffer->makeReadable();
		$value = Unpacker::unpack($buffer);
		
		// Convert to Reply Struct
		if($value instanceof GenericStruct) {
			$reply = match($value->sig) {
				0x70 => Reply\Success::fromGenericStruct($value),
				0x7E => Reply\Ignored::fromGenericStruct($value),
				0x7F => Reply\Failure::fromGenericStruct($value),
				0x71 => Reply\Record::fromGenericStruct($value),
				default => null
			};
			if($reply !== null) {
				$value = $reply;
			}
		}
		$this->logger?->logRead(
			$buffer,
			$logTitle . ' = ' . static::getTypeName($value)
		);
		return $value;
	}
	public function reset() {
		return $this->write('Reset', 0x02, autoRetry: false);
	}
}