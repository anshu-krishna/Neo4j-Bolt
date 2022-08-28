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
			self::DISCONNECTED => 'Disconnected',
			self::CONNECTED => 'Connected',
			self::DEFUNCT => 'Defunct',
			self::READY => 'Ready',
			self::STREAMING => 'Streaming',
			self::TX_READY => 'Tx_Ready',
			self::TX_STREAMING => 'Tx_Streaming',
			self::FAILED => 'Failed',
			self::INTERRUPTED => 'Interrupted',
			default => 'Unknown'
		};
	}
}