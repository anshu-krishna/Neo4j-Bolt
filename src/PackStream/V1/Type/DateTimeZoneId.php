<?php
namespace Krishna\Neo4j\PackStream\V1\Type;

use Krishna\Neo4j\PackStream\V1\{GenericStruct, I_PackStruct, T_CachedToString, T_GenericConverter};

class DateTimeZoneId implements I_PackStruct {
	use T_GenericConverter, T_CachedToString;
	const SIG = 0x66;

	public readonly int $seconds, $nanoseconds;
	
	public function __construct(
		int $seconds,
		int $nanoseconds,
		public readonly string $tz_id
	) {
		$this->seconds = $seconds + intdiv($nanoseconds, 1e9);
		$this->nanoseconds = $nanoseconds % 1e9;
	}
	public function _stringify_(): string {
		return sprintf(
			"%s[%s]",
			\DateTime::createFromFormat(
				'U.u',
				\Krishna\Neo4j\Helper\SecNanoSec::stringify(
					$this->seconds, $this->nanoseconds
				),
				new \DateTimeZone($this->tz_id)
			)->format('Y-m-d\TH:i:s.u'),
			$this->tz_id
		);
	}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(static::SIG, $this->seconds, $this->nanoseconds, $this->tz_id);
	}
}