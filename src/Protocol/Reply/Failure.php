<?php
namespace Krishna\Neo4j\Protocol\Reply;

use Krishna\Neo4j\PackStream\V1\{T_GenericConverter, GenericStruct, I_PackStruct};

class Failure implements I_PackStruct {
	use T_GenericConverter;
	const SIG = 0x7F;

	public function __construct(
		public readonly string $code,
		public readonly string $message
	) {}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(static::SIG, [
			'code' => $this->code,
			'message' => $this->message
		]);
	}
}