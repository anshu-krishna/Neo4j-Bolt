<?php
namespace Krishna\Neo4j\Protocol\Reply;

use ArrayObject;
use Krishna\Neo4j\PackStream\V1\{GenericStruct, I_PackStruct};

class Success extends ArrayObject implements I_PackStruct {
	const SIG = 0x7F;
	public function __construct(array $metadata) {
		parent::__construct($metadata, ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS);
	}
	public static function fromGenericStruct(GenericStruct $struct): ?static {
		if($struct->sig !== 0x70) { return null; }
		return new static($struct->fields[0] ?? []);
	}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(0x70, $this->getArrayCopy());
	}
}