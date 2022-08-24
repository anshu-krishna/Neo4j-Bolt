<?php
namespace Krishna\Neo4j\PackStream\V1\Type;

use Krishna\Neo4j\PackStream\V1\{GenericStruct, I_PackStruct, T_CachedToString, T_GenericConverter};

class LocalDateTime implements I_PackStruct {
	use T_GenericConverter, T_CachedToString;
	const SIG = 0x64;

	public readonly int $seconds, $nanoseconds;
	
	public function __construct(
		int $seconds,
		int $nanoseconds
	) {
		$this->seconds = $seconds + intdiv($nanoseconds, 1e9);
		$this->nanoseconds = $nanoseconds % 1e9;
	}
	public function _stringify_(): string {
		return \DateTime::createFromFormat(
			'U.u',
			\Krishna\Neo4j\Helper\SecNanoSec::stringify($this->seconds, $this->nanoseconds)
		)->format('Y-m-d\TH:i:s.u');
	}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(static::SIG, $this->seconds, $this->nanoseconds);
	}
}