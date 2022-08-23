<?php
namespace Krishna\Neo4j\PackStream\V1\Type;

use Krishna\Neo4j\PackStream\V1\{GenericStruct, I_PackStruct, T_CachedToString, T_GenericConverter};

class Time implements I_PackStruct {
	use T_GenericConverter, T_CachedToString;
	const SIG = 0x54;

	public function __construct(
		public readonly int $nanoseconds,
		public readonly int $tz_offset_seconds
	) {}
	public function _stringify_(): string {
		return \DateTime::createFromFormat(
			'U.u',
			\Krishna\Neo4j\Helper\SecNanoSec::stringify(
				intdiv($this->nanoseconds, 1e9) - $this->tz_offset_seconds,
				$this->nanoseconds % 1e9
			),
			new \DateTimeZone('UTC')
		)->setTimezone(
			new \DateTimeZone(sprintf("%+05d", intdiv($this->tz_offset_seconds, 3600) * 100))
		)->format('H:i:s.uP');
	}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(static::SIG, $this->nanoseconds, $this->tz_offset_seconds);
	}
}