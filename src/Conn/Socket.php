<?php
namespace Krishna\Neo4j\Conn;

use Krishna\Neo4j\Buffer;
use Krishna\Neo4j\Ex\ConnEx;
use Krishna\Neo4j\Helper\ErrorHandler;

final class Socket implements I_Conn {
	const TIMEOUT_CODES = [11, 10060];
	private \Socket|false $tcp = false;
	
	public function __destruct() { $this->disconnect(); }

	public function connect(string $host, int $port, float $timeout) {
		if($this->tcp !== false) { return; }

		$error = null;
		$code = 0;

		ErrorHandler::pause();
		while(true) {
			$error = match(false) {
				extension_loaded('sockets') =>
					'PHP Extension sockets not enabled',
				($this->tcp = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) =>
					'Cannot create socket',
				socket_set_block($this->tcp) =>
					'Cannot set socket into blocking mode',
				socket_set_option($this->tcp, SOL_TCP, TCP_NODELAY, 1) =>
					'Cannot set socket options',
				socket_set_option($this->tcp, SOL_SOCKET, SO_KEEPALIVE, 1) =>
					'Cannot set socket options',
				$this->updateTimeout($timeout) =>
					'Cannot set socket timeout',
				default =>
					null
			};
			if($error !== null) { break; }
			if(socket_connect($this->tcp, $host, $port) === false) {
				$code = socket_last_error($this->tcp);
				$error = socket_strerror($code);
				break;
			}
			break;
		}
		ErrorHandler::resume();
		if($error !== null) {
			$this->tcp = false;
			throw new ConnEx($error, $code);
		}
	}
	public function updateTimeout(float $timeout): bool {
		if($this->tcp === false) { return false; }
		$sec = floor($timeout);
		$micro = floor(($timeout - $sec) * 1000000);
		$cfg = ['sec' => $sec, 'usec' => $micro];
		return
			socket_set_option($this->tcp, SOL_SOCKET, SO_RCVTIMEO, $cfg)
			&& socket_set_option($this->tcp, SOL_SOCKET, SO_SNDTIMEO, $cfg);
	}
	public function write(string|Buffer $buffer) {
		if($this->tcp === false) {
			throw new ConnEx('Socket not initialized');
		}
		if($buffer instanceof Buffer) {
			$buffer->makeReadable();
			$size = $buffer->getSize();
			$buffer = $buffer->__toString();
		} else {
			$size = mb_strlen($buffer, '8bit');
		}
		ErrorHandler::pause();
		while (0 < $size) {
			$sent = socket_write($this->tcp, $buffer, $size);
			if($sent === false) { $this->throwEx(); }
			$buffer = mb_strcut($buffer, $sent, null, '8bit');
			$size -= $sent;
		}
		ErrorHandler::resume();
	}
	public function read(int $length): ?string {
		if($this->tcp === false) {
			throw new ConnEx('Socket not initialized');
		}
		$output = '';
		$read = 0;
		ErrorHandler::pause();
		do {
			$bin = socket_read($this->tcp, $length - $read, PHP_BINARY_READ);
			if($bin === false) { $this->throwEx(); }
			$read += mb_strlen($bin, '8bit');
			$output .= $bin;
		} while ($read < $length);
		ErrorHandler::resume();
		return $output;
	}
	public function disconnect() {
		if($this->tcp !== false) {
			ErrorHandler::pause();
			socket_shutdown($this->tcp);
			socket_close($this->tcp);
			$this->tcp = false;
			ErrorHandler::resume();
		}
	}
	private function throwEx() {
		ErrorHandler::resume();
		$code = socket_last_error($this->tcp);
		$this->tcp = false;
		throw new ConnEx(socket_strerror($code), $code);
	}
}