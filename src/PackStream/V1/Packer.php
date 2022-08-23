<?php
namespace Krishna\Neo4j\PackStream\V1;

use Krishna\Neo4j\Buffer;
use Krishna\Neo4j\Ex\PackEx;

class Packer {
	use \Krishna\Neo4j\Helper\T_StaticOnly;

	const TINY = 16;
	const MEDIUM = 256;
	const LARGE = 65536;
	const HUGE = 4294967295;

	protected static ?bool $LITTLE_ENDIAN = null;

	public static function LittleEndian(): bool {
		static::$LITTLE_ENDIAN ??= unpack('S', "\x01\x00")[1] === 1;
		return static::$LITTLE_ENDIAN; 
	}

	public static function isAssoc(array $array) {
		foreach(array_keys($array) as $key) {
			if(is_string($key)) { return true; }
		}
		return false;
	}

	public static function size(int $size, int $tag_tiny, int $tag_8, int $tag_16, int $tag_32, string $errmsg) : string {
		if($size < static::TINY) {
			return pack('C', $tag_tiny | $size);
		} elseif($size < static::MEDIUM) {
			return chr($tag_8) . pack('C', $size);
		} elseif($size < static::LARGE) {
			return chr($tag_16) . pack('n', $size);
		} elseif($size < static::HUGE) {
			return chr($tag_32) . pack('N', $size);
		} else {
			throw new PackEx($errmsg);
		}
	}

	public static function pack(mixed $value): iterable {
		switch (gettype($value)) {
			case 'NULL':
				yield chr(0xC0);
				break;
			case 'integer':
				yield from static::packInteger($value);
				break;
			case 'double':
				yield from static::packFloat($value);
				break;
			case 'boolean':
				yield from static::packBool($value);
				break;
			case 'string':
				yield from static::packString($value);
				break;
			case 'array':
				if (static::isAssoc($value)) {
					yield from static::packMap($value);
				} else {
					yield from static::packList($value);
				}
				break;
			case 'object':
				yield from match(true) {
					$value instanceof Type\Bytes
						=> static::packBytes($value),
					$value instanceof GenericStruct
						=> static::packStructure($value),
				// 	$value instanceof I_Structure
				// 		=> (static::BOLT->proto === null)
				// 		? static::packStructure($value->toGenericStruct())
				// 		: static::packStructure(static::BOLT->proto::Structure2GStruct($value)),
					default => static::packMap((array)$value)
				};
				break;
			default:
				throw new PackEx('Packer failed');
				break;
		}
	}
	public function packInteger(int $value): iterable {
		$LITTLE_ENDIAN = static::LittleEndian();
		if ($value >= -16 && $value <= 127) {
			//TINY_INT
			yield pack('c', $value);
		} elseif ($value >= -128 && $value <= -17) {
			//INT_8
			yield chr(0xC8) . pack('c', $value);
		} elseif (($value >= 128 && $value <= 32767) || ($value >= -32768 && $value <= -129)) {
			//INT_16
			$packed = pack('s', $value);
			yield chr(0xC9) . ($LITTLE_ENDIAN ? strrev($packed) : $packed);
		} elseif (($value >= 32768 && $value <= 2147483647) || ($value >= -2147483648 && $value <= -32769)) {
			//INT_32
			$packed = pack('l', $value);
			yield chr(0xCA) . ($LITTLE_ENDIAN ? strrev($packed) : $packed);
		} elseif (($value >= 2147483648 && $value <= 9223372036854775807) || ($value >= -9223372036854775808 && $value <= -2147483649)) {
			//INT_64
			$packed = pack('q', $value);
			yield chr(0xCB) . ($LITTLE_ENDIAN ? strrev($packed) : $packed);
		} else {
			throw new PackEx('Integer out of range');
		}
	}

	public function packFloat(float $value): iterable {
		$packed = pack('d', $value);
		yield chr(0xC1) . (static::LittleEndian() ? strrev($packed) : $packed);
	}
	public function packBool(bool $value): iterable {
		yield chr($value ? 0xC3 : 0xC2);
	}
	public function packString(string $value): iterable {
		$length = mb_strlen($value, '8bit');
		yield static::size($length, 0x80, 0xD0, 0xD1, 0xD2, 'String is too long');
		yield $value;
	}
	public function packMap(array $value): iterable {
		yield static::size(count($value), 0xA0, 0xD8, 0xD9, 0xDA, 'Map has too many items');
		foreach($value as $k => $v) {
			yield from static::packString((string) $k);
			yield from static::pack($v);
		}
	}
	public function packList(array $value): iterable {
		yield static::size(count($value), 0x90, 0xD4, 0xD5, 0xD6, 'List has too many items');
		foreach($value as $v) {
			yield from static::pack($v);
		}
	}
	public function packBytes(Type\Bytes $bytes) : iterable {
		$size = $bytes->length;

		if($size < static::MEDIUM) {
			yield chr(0xCC) . pack('C', $size);
		} elseif($size < static::LARGE) {
			yield chr(0xCD) . pack('n', $size);
		} elseif($size < 2147483648) {
			yield chr(0xCE) . pack('N', $size);
		} else {
			throw new PackEx('ByteArray too big');
		}
		yield $bytes->bytes;
	}
	public function packStructure(GenericStruct $struct): iterable {
		$length = count($struct->fields);
		if ($length < static::TINY) { //TINY_STRUCT
			yield pack('n', 1) . pack('C', 0xB0 | $length);
		} elseif ($length < static::MEDIUM) { //STRUCT_8
			yield pack('n', 2) . chr(0xDC) . pack('C', $length);
		} elseif ($length < static::LARGE) { //STRUCT_16
			yield pack('n', 4) . chr(0xDD) . pack('n', $length);
		} else {
			throw new PackEx('Structure too big');
		}
		yield pack('n', 1) . chr($struct->sig);
		foreach($struct->fields as $val) {
			$packed = Buffer::Writable();
			foreach(static::pack($val) as $bytes) {
				$packed->write($bytes);
			}
			foreach($packed->getChunks() as $chunk) {
				yield $chunk;
			}
		}
		yield hex2bin('0000');
	}
}