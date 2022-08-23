<?php
namespace Krishna\Neo4j\PackStream\V1\Type;

use Krishna\Neo4j\PackStream\V1\{GenericStruct, I_PackStruct, T_CachedToString, T_GenericConverter};

class LocalTime implements I_PackStruct {
	use T_GenericConverter, T_CachedToString;
	const SIG = 0x74;

	public function __construct(public readonly int $nanoseconds) {}
	public function _stringify_(): string {
		return \DateTime::createFromFormat(
			'U.u',
			sprintf("%0.6f", round($this->nanoseconds / 1e9, 6))
		)->format('H:i:s.u');
	}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(static::SIG, $this->nanoseconds);
	}
}