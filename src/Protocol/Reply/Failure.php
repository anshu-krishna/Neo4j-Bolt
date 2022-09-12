<?php
namespace Krishna\Neo4j\Protocol\Reply;

use Krishna\PackStream\{I_Structable, Structure};

class Failure implements I_Structable, I_Reply {
	const SIG = 0x7F;

	public function __construct(
		public readonly string $code,
		public readonly string $message
	) {}
	public static function fromStructure(Structure $struct): ?static {
		if($struct->sig !== static::SIG) { return null; }
		return new static($struct->fields[0]['code'] ?? '', $struct->fields[0]['message'] ?? '');
	}
	public function toStructure() : Structure {
		return new Structure(static::SIG, [
			'code' => $this->code,
			'message' => $this->message
		]);
	}
	public function copyToArray(): array {
		return ['type' => 'Failure', 'value' => [
			'code' => $this->code,
			'message' => $this->message
		]];
	}
}