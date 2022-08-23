<?php
namespace Krishna\Neo4j\PackStream\V1;

class GenericStruct {
	public readonly array $fields;
	public function __construct(public readonly int $sig, mixed ...$fields) {
		$this->fields = $fields;
	}
}