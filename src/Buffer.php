<?php
namespace Krishna\Neo4j;

use Krishna\Neo4j\Ex\BufferEx;

final class Buffer {
	private array|string $store;

	private ?int $length; // Null means writemode
	private int $offset = 0;

	public static function Readable(string $bytes): self {
		$obj = new self();
		$obj->store = $bytes;
		$obj->length = mb_strlen($obj->store, '8bit');
		return $obj;
	}
	public static function Writable(?string $bytes = null): self {
		$obj = new self();
		$obj->store = ($bytes === null) ? [] : [$bytes];
		$obj->length = null;
		return $obj;
	}
	private function __construct() {}
	public function write(string $bytes): void {
		if($this->length === null) { $this->store[] = $bytes; }
		else {
			throw new BufferEx('Buffer is in read mode');
		}
	}
	public function writeIterable(iterable $iter): void {
		if($this->length !== null) {
			throw new BufferEx('Buffer is in read mode');
		}
		foreach($iter as $bytes) {
			if(is_string($bytes)) {
				$this->store[] = $bytes;
			} else {
				throw new BufferEx('Only string can be written in the Buffer');
			}
		}
	}
	public function read(int $len = 0): string {
		if($this->length === null) {
			throw new BufferEx('Buffer is in write mode');
		}
		$remaning = $this->length - $this->offset;
		if($len < 1) { // Read all remaining
			$len = $remaning;
		} elseif ($len > $remaning) {
			throw new BufferEx("Cannot read {$len} bytes; {$remaning} bytes remaining");
		}
		$part = mb_strcut($this->store, $this->offset, $len, '8bit');
		$this->offset += $len;
		return $part;
	}
	public function getChunks(int $chunkSize = 65535) : iterable {
		if($this->length === null) {
			throw new BufferEx('Buffer is in write mode');
		}
		$msg = $this->store;
		$max = $this->length;
		$sent = 0;
		while($sent < $max) {
			$remaning = $max - $sent;
			$send = ($remaning > $chunkSize) ? $chunkSize : $remaning;
			yield pack('n', $send) . mb_strcut($msg, $sent, $send, '8bit'); // Chunk size + Msg chunk
			$sent += $send;
		}
	}
	public function resetReading(): void { $this->offset = 0; }
	public function getRemaining(): int {
		if($this->length === null) {
			throw new BufferEx('Buffer is in write mode');
		}
		return $this->length - $this->offset;
	}
	public function getReadingOffset(): int {
		return $this->offset;
	}
	public function getSize(): int {
		if($this->length === null) {
			$size = 0;
			foreach($this->store as $part) {
				$size += mb_strlen($part, '8bit');
			}
			return $size;
		}
		return $this->length;
	}
	public function getMode(): string {
		return ($this->length === null) ? 'write' : 'read';
	}
	public function makeReadable(): void {
		if($this->length === null) {
			$this->store = implode('', $this->store);
			$this->length = mb_strlen($this->store, '8bit');
			$this->offset = 0;
		}
	}
	public function makeWritable(): void {
		if($this->length !== null) {
			$this->store = [$this->store];
			$this->length = null;
		}
	}
	public function __toString(): string {
		if($this->length === null) {
			return implode('', $this->store);
		}
		return $this->store;
	}
}