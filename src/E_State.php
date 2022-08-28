<?php
namespace Krishna\Neo4j;

enum E_State {
	case DISCONNECTED;
	case CONNECTED;
	case DEFUNCT;
	case READY;
	case STREAMING;
	case TX_READY;
	case TX_STREAMING;
	case FAILED;
	case INTERRUPTED;

	public function connOn(): bool {
		return match($this) {
			self::DEFUNCT => false,
			self::DISCONNECTED => false,
			default => true
		};
	}
	public function connOff(): bool {
		return match($this) {
			self::DEFUNCT => true,
			self::DISCONNECTED => true,
			default => false
		};
	}
	public function stringify(): string {
		return match($this) {
			self::DISCONNECTED => 'DISCONNECTED',
			self::CONNECTED => 'CONNECTED',
			self::DEFUNCT => 'DEFUNCT',
			self::READY => 'READY',
			self::STREAMING => 'STREAMING',
			self::TX_READY => 'TX_READY',
			self::TX_STREAMING => 'TX_STREAMING',
			self::FAILED => 'FAILED',
			self::INTERRUPTED => 'INTERRUPTED',
			default => 'UNKNOWN'
		};
	}
}