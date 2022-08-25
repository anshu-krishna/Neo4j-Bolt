<?php
namespace Krishna\Neo4j\Protocol\Reply;

use Krishna\Neo4j\PackStream\V1\{T_GenericConverter, GenericStruct, I_PackStruct};

class Ignored implements I_PackStruct {
	use T_GenericConverter;
	const SIG = 0x7E;

	public function toGenericStruct(): GenericStruct {
		return new GenericStruct(static::SIG);
	}
}