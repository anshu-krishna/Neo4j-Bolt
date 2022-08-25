<?php
namespace Krishna\Neo4j\Protocol\Reply;

use ArrayObject;
use Krishna\Neo4j\PackStream\V1\{GenericStruct, I_PackStruct};

class Record extends ArrayObject implements I_PackStruct {
	const SIG = 0x71;
	
	public function __construct(array $records) {
		parent::__construct($records, ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS);
	}
	public static function fromGenericStruct(GenericStruct $struct): ?static {
		if($struct->sig !== static::SIG) { return null; }
		return new static($struct->fields[0] ?? []);
	}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(static::SIG, $this->getArrayCopy());
	}
}