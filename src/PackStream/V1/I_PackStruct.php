<?php
namespace Krishna\Neo4j\PackStream\V1;

interface I_PackStruct {
	public static function fromGenericStruct(GenericStruct $struct): ?static;
	public function toGenericStruct() : GenericStruct;
}