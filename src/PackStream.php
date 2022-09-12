<?php
namespace Krishna\Neo4j;

use Krishna\Neo4j\Ex\PackEx as NeoPackEx;
use Krishna\PackStream\{PackEx, Packer, Unpacker};

final class PackStream {
	use Helper\T_StaticOnly;

	public static function pack(mixed $value): iterable {
		try {
			yield from Packer::pack($value);
		} catch(PackEx $ex) {
			throw new NeoPackEx($ex->getMessage());
		}
	}
	public static function unpack(Buffer $source) {
		try {
			$source->makeReadable();
			return Unpacker::unpack($source);
		} catch (PackEx $ex) {
			throw new NeoPackEx($ex->getMessage());
		}
	}
}