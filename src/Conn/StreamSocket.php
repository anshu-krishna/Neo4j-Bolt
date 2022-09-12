<?php
namespace Krishna\Neo4j\Conn;

use Krishna\Neo4j\Buffer;
use Krishna\Neo4j\Ex\ConnEx;
use Krishna\Neo4j\Helper\ErrorHandler;

class StreamSocket implements I_Conn {
	private $stream;

	public function __construct(private float $timeout = 15, public readonly array $sslContextOptions = []) {}
	public function connect(string $host, int $port, float $timeout) {
		$context = stream_context_create([
			'socket' => [
				'tcp_nodelay' => true,
			],
			'ssl' => $this->sslContextOptions
		]);
		ErrorHandler::pause();
		$this->stream = stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
		ErrorHandler::resume();

		if($this->stream === false) {
			throw new ConnEx($errstr, $errno);
		}
		if (!stream_set_blocking($this->stream, true)) {
			$this->stream = false;
			throw new ConnEx('Cannot set socket into blocking mode');
		}

		if (!empty($this->sslContextOptions)) {
			if (stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_ANY_CLIENT) !== true) {
				$this->stream = false;
				throw new ConnEx('Enable encryption error');
			}
		}
		$this->updateTimeout(0);
	}
	public function updateTimeout(float $timeout): bool {
		if($timeout > 1) {
			$this->timeout = $timeout;
		}
		if(is_resource($this->stream)) {
			$t = (int)floor($this->timeout);
			if (!stream_set_timeout($this->stream, $t, (int)floor(($this->timeout - $t) * 10e6))) {
				$this->stream = false;
				throw new ConnEx('Cannot set timeout on stream');
			}
			return true;
		}
		return false;
	}
	public function write(string|Buffer $buffer): void {
		if($this->stream === false) {
			throw new ConnEx('Socket not initialized');
		}
		if($buffer instanceof Buffer) {
			$size = $buffer->size();
			$buffer = $buffer->__toString();
		} else {
			$size = mb_strlen($buffer, '8bit');
		}
		$time = microtime(true);
		while (0 < $size) {
			$sent = fwrite($this->stream, $buffer);
			if($sent === false) {
				if(microtime(true) - $time >= $this->timeout) {
					throw new ConnEx('Connection timeout reached after ' . $this->timeout . ' seconds.');
				}
				else { throw new ConnEx('Write error'); }
			}
			$buffer = mb_strcut($buffer, $sent, null, '8bit');
			$size -= $sent;
		}
	}
	public function writeIterable(iterable $parts): void {
		if($this->stream === false) {
			throw new ConnEx('Socket not initialized');
		}
		$time = microtime(true);
		foreach($parts as $buffer) {
			if(is_string($buffer)) {
				$size = mb_strlen($buffer, '8bit');
			} elseif($buffer instanceof Buffer) {
				$size = $buffer->size();
				$buffer = $buffer->__toString();
			} else {
				throw new ConnEx('Only string or Buffer can be written');
			}
			while (0 < $size) {
				$sent = fwrite($this->stream, $buffer);
				if($sent === false) {
					if(microtime(true) - $time >= $this->timeout) {
						throw new ConnEx('Connection timeout reached after ' . $this->timeout . ' seconds.');
					}
					else { throw new ConnEx('Write error'); }
				}
				$buffer = mb_strcut($buffer, $sent, null, '8bit');
				$size -= $sent;
			}
		}
	}
	public function read(int $length): ?string {
		if($this->stream === false) {
			throw new ConnEx('Socket not initialized');
		}
		$output = '';
		$read = 0;
		do {
			$bin = stream_get_contents($this->stream, $length - $read);
			if (stream_get_meta_data($this->stream)['timed_out'] ?? false) {
				throw new ConnEx('Connection timeout reached after ' . $this->timeout . ' seconds.');
			}
			if ($bin === false) { throw new ConnEx('Read error'); }
			$read += mb_strlen($bin, '8bit');
			$output .= $bin;
		} while (mb_strlen($output, '8bit') < $length);
		return $output;
	}
	
	public function disconnect() {
		if(is_resource($this->stream)) {
			stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
			$this->stream = false;
		}
	}
}