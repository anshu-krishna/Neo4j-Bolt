<?php
namespace Krishna\Neo4j\PackStream\V1\Type;

use Krishna\Neo4j\PackStream\V1\{GenericStruct, I_PackStruct, T_CachedToString, T_GenericConverter};

class Point3D implements I_PackStruct {
	use T_GenericConverter, T_CachedToString;
	const SIG = 0x59;

	public function __construct(
		public readonly int $srid,
		public readonly float $x,
		public readonly float $y,
		public readonly float $z
	) {}
	public function _stringify_() : string {
		return "point({srid: {$this->srid}, x: {$this->x}, y: {$this->y}, z: {$this->z})";
	}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(static::SIG, $this->srid, $this->x, $this->y, $this->z);
	}
}