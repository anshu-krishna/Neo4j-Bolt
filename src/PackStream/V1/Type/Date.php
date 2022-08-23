<?php
namespace Krishna\Neo4j\PackStream\V1\Type;

use Krishna\Neo4j\PackStream\V1\{GenericStruct, I_PackStruct, T_GenericConverter};

class Date implements I_PackStruct {
	use T_GenericConverter;
	const SIG = 0x44;
	
	public function __construct(public readonly int $days) {}
	
	public function __toString(): string {
		return $this->cache = gmdate('Y-m-d', strtotime(sprintf("%+d days +0000", $this->days), 0));
	}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(static::SIG, $this->days);
	}
}