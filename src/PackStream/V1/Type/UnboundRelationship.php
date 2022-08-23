<?php
namespace Krishna\Neo4j\PackStream\V1\Type;

use Krishna\Neo4j\Helper\JSON;
use Krishna\Neo4j\PackStream\V1\{GenericStruct, I_PackStruct, T_CachedToString, T_GenericConverter};

class UnboundRelationship implements I_PackStruct {
	use T_GenericConverter, T_CachedToString;
	const SIG = 0x72;

	public function __construct(
		public readonly int $id,
		public readonly string $type,
		public readonly array $properties
	) {}
	public function _stringify_(): string {
		return JSON::encode([
			'id' => $this->id,
			'type' => $this->type,
			'properties' => $this->properties
		]);
	}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(static::SIG, $this->id, $this->type, (object)$this->properties);
	}
}