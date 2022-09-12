<?php
namespace Krishna\Neo4j\Protocol\Reply;

use Krishna\PackStream\{I_Structable, Structure};
use Krishna\PackStream\Helper\T_MakeStructable;

class Ignored implements I_Structable, I_Reply {
	use T_MakeStructable;
	const SIG = 0x7E;

	public function toStructure(): Structure {
		return new Structure(static::SIG);
	}
	public function copyToArray(): array {
		return ['type' => 'Ignored', 'value' => null];
	}
}