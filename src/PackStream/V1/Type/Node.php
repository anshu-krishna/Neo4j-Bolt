<?php
namespace Krishna\Neo4j\PackStream\V1\Type;

use Krishna\Neo4j\Ex\PackEx;
use Krishna\Neo4j\Helper\JSON;
use Krishna\Neo4j\PackStream\V1\{GenericStruct, I_PackStruct, T_CachedToString, T_GenericConverter, ArrayItemChecker as AIC};

class Node implements I_PackStruct {
	use T_GenericConverter, T_CachedToString;
	const SIG = 0x4E;

	public function __construct(
		public readonly int $id,
		public readonly array $labels,
		public readonly array $properties
	) {
		if(AIC::notStringList($this->labels)) {
			throw new PackEx('Field labels must only contain string items');
		}
	}
	public function _stringify_(): string {
		return JSON::encode([
			'id' => $this->id,
			'labels' => $this->labels,
			'properties' => $this->properties
		]);
	}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(static::SIG, $this->id, $this->labels, (object)$this->properties);
	}
}