<?php
namespace Krishna\Neo4j\PackStream\V1\Type;

use Krishna\Neo4j\Ex\PackEx;
use Krishna\Neo4j\Helper\JSON;
use Krishna\Neo4j\PackStream\V1\{GenericStruct, I_PackStruct, T_GenericConverter, ArrayItemChecker as AIC, T_CachedToString};

class Path implements I_PackStruct {
	use T_GenericConverter, T_CachedToString;
	const SIG = 0x50;
	
	public function __construct(
		public readonly array $nodes,
		public readonly array $rels,
		public readonly array $ids
	) {
		if(AIC::notNodeList($this->nodes)) {
			throw new PackEx('Field nodes must only contain ' . Node::class . ' objects');
		}
		if(AIC::notURelList($this->rels)) {
			throw new PackEx('Field rels must only contain ' . Relationship::class . ' objects');
		}
		if(AIC::notIntList($this->ids)) {
			throw new PackEx('Field ids must only contain int items');
		}
	}
	public function _stringify_(): string {
		$segments = [];
		for ($i = 0, $max = count($this->nodes) - 1; $i < $max; $i++) {
			$segments[] = [
				'start' => $this->nodes[$i],
				'relationship' => $this->rels[$i],
				'end' => $this->nodes[$i + 1]
			];
		}
		$obj = [
			'start' => $this->nodes[0] ?? null,
			'end' => $this->nodes[count($this->nodes) - 1] ?? null,
			'segments' => $segments,
			'length' => count($this->ids) - 1
		];
		return JSON::encode($obj);
	}
	public function toGenericStruct() : GenericStruct {
		return new GenericStruct(static::SIG, $this->nodes, $this->rels, $this->ids);
	}
}