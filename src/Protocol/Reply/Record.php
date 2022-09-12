<?php
namespace Krishna\Neo4j\Protocol\Reply;

use ArrayObject;
use Krishna\PackStream\{I_Structable, Structure};

class Record extends ArrayObject implements I_Structable, I_Reply {
	const SIG = 0x71;
	
	public function __construct(array $records) {
		parent::__construct($records, ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS);
	}
	public static function fromStructure(Structure $struct): ?static {
		if($struct->sig !== static::SIG) { return null; }
		return new static($struct->fields[0] ?? []);
	}
	public function toStructure() : Structure {
		return new Structure(static::SIG, $this->getArrayCopy());
	}
	public function copyToArray(): array {
		return ['type' => 'Record', 'value' => $this->getArrayCopy()];
	}
}