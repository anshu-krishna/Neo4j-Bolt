<?php
namespace Krishna\Neo4j\PackStream\V1;

use Krishna\Neo4j\Buffer;
use Krishna\Neo4j\Ex\PackEx;
use Krishna\Neo4j\Helper\T_StaticOnly;

class Unpacker {
	use T_StaticOnly;
	protected static ?bool $LITTLE_ENDIAN = null;

	public static function LittleEndian(): bool {
		static::$LITTLE_ENDIAN ??= unpack('S', "\x01\x00")[1] === 1;
		return static::$LITTLE_ENDIAN; 
	}
	public static function fetch(Buffer $buffer, string $format, int $len) {
		return unpack($format, $buffer->read($len))[1];
	}
	public static function fetchEndian(Buffer $buffer, string $format, int $len) {
		return unpack(
			$format,
			static::LittleEndian()? strrev($buffer->read($len)) : $buffer->read($len)
		)[1];
	}
	public static function fetchSize(
		Buffer $buffer,
		int $tag, int $high,
		int|bool $tag_tiny,	// Size_Tiny
		int $tag_8,			// Size_8
		int $tag_16,		// Size_16
		int|bool $tag_32	// Size_32
	): ?int {
		if($high === $tag_tiny) {
			return $tag_tiny ^ $tag; // Size_Tiny
		}
		return match($tag) {
			$tag_8 => (int)static::fetch($buffer, 'C', 1), // Size_8
			$tag_16 => (int)static::fetch($buffer, 'n', 2), // Size_16
			$tag_32 => (int)static::fetch($buffer, 'N', 4), // Size_32
			default => null
		};
	}
	protected static function checkList(Buffer $buffer, int $tag): iterable {
		$high = $tag & 0xF0;
		// yield static::unpackBool($buffer, $tag); // Short-circuited
		yield static::unpackInteger($buffer, $tag, $high);
		// yield static::unpackFloat($buffer, $tag); // Short-circuited
		yield static::unpackString($buffer, $tag, $high);
		yield static::unpackList($buffer, $tag, $high);
		yield static::unpackMap($buffer, $tag, $high);
		yield static::unpackBytes($buffer, $tag, $high);
		yield static::unpackStruct($buffer, $tag, $high);
	}
	public static function unpack(Buffer $buffer) {
		if($buffer->getRemaining() === 0) {
			return null;
		}
		$tag = ord($buffer->read(1));
		switch($tag) {
			case 0xC0: return null;
			case 0xC2: return false; // Short-circuit for False
			case 0xC3: return true; // Short-circuit for True
			case 0xC1: return (float)static::fetchEndian($buffer, 'd', 8); // Short-circuit for Float
		}

		foreach(static::checkList($buffer, $tag) as $v) {
			if($v !== null) { return $v; }
		}
		throw new PackEx('Invalid binary');
	}
	public static function unpackInteger(Buffer $buffer, int $tag, int $high): ?int {
		if($high <= 0x70 || 0xF0 <= $high) {
			return (int)unpack('c', chr($tag))[1];
		}
		return match($tag) {
			0xC8 => (int)static::fetchEndian($buffer, 'c', 1), // INT_8
			0xC9 => (int)static::fetchEndian($buffer, 's', 2), // INT_16
			0xCA => (int)static::fetchEndian($buffer, 'l', 4), // INT_32
			0xCB => (int)static::fetchEndian($buffer, 'q', 8), // INT_64
			default => null
		};
	}
	public static function unpackFloat(Buffer $buffer, int $tag): ?float {
		return match($tag) {
			0xC1 => (float)static::fetchEndian($buffer, 'd', 8),
			default => null
		};
	}
	public static function unpackBool(Buffer $buffer, int $tag): ?bool {
		return match($tag) {
			0xC2 => false,
			0xC3 => true,
			default => null
		};
	}
	public static function unpackString(Buffer $buffer, int $tag, int $high): ?string {
		$size = static::fetchSize($buffer, $tag, $high, 0x80, 0xD0, 0xD1, 0xD2);
		if($size === null) { return null; }
		return $buffer->read($size);
	}
	public static function unpackMap(Buffer $buffer, int $tag, int $high): ?array {
		$size = static::fetchSize($buffer, $tag, $high, 0xA0, 0xD8, 0xD9, 0xDA);
		if($size === null) { return null; }
		$ret = [];
		for($i = 0; $i < $size; $i++) {
			$nxt = ord($buffer->read(1));
			$key = static::unpackString($buffer, $nxt, $nxt & 0xF0);
			if($key === null) {
				throw new PackEx('Invalid map key');
			}
			$ret[$key] = static::unpack($buffer);
		}
		return $ret;
	}
	public static function unpackList(Buffer $buffer, int $tag, int $high): ?array {
		$size = static::fetchSize($buffer, $tag, $high, 0x90, 0xD4, 0xD5, 0xD6);
		if($size === null) { return null; }
		$ret = [];
		for($i = 0; $i < $size; $i++) {
			$ret[] = static::unpack($buffer);
		}
		return $ret;
	}
	public static function unpackBytes(Buffer $buffer, int $tag, int $high): ?Type\Bytes {
		$size = static::fetchSize($buffer, $tag, $high, false, 0xCC, 0xCD, 0xCE);
		if($size === null) { return null; }
		return new Type\Bytes($buffer->read($size));
	}
	public static function unpackStruct(Buffer $buffer, int $tag, int $high): GenericStruct|I_PackStruct|null {
		$size = static::fetchSize($buffer, $tag, $high, 0xB0, 0xDC, 0xDD, false);
		if($size === null) { return null; }
		$sig = ord($buffer->read(1));
		$fields = [];
		for($i = 0; $i < $size; $i++) {
			$fields[] = static::unpack($buffer);
		}
		$gstruct = new GenericStruct($sig, ...$fields);
		// Convert to I_PackStruct
		$pstruct = match($gstruct->sig) {
			0x70 => null, // Short-circuit for Protocol\Reply\Success,
			0x7E => null, // Short-circuit for Protocol\Reply\Ignored,
			0x7F => null, // Short-circuit for Protocol\Reply\Failure,
			0x71 => null, // Short-circuit for Protocol\Reply\Record,
			0x4E => Type\Node::fromGenericStruct($gstruct),
			0x52 => Type\Relationship::fromGenericStruct($gstruct),
			0x72 => Type\UnboundRelationship::fromGenericStruct($gstruct),
			0x50 => Type\Path::fromGenericStruct($gstruct),
			0x44 => Type\Date::fromGenericStruct($gstruct),
			0x54 => Type\Time::fromGenericStruct($gstruct),
			0x74 => Type\LocalTime::fromGenericStruct($gstruct),
			0x46 => Type\DateTime::fromGenericStruct($gstruct),
			0x66 => Type\DateTimeZoneId::fromGenericStruct($gstruct),
			0x64 => Type\LocalDateTime::fromGenericStruct($gstruct),
			0x45 => Type\Duration::fromGenericStruct($gstruct),
			0x58 => Type\Point2D::fromGenericStruct($gstruct),
			0x59 => Type\Point3D::fromGenericStruct($gstruct),
			default => null
		};
		return ($pstruct === null) ? $gstruct : $pstruct;
	}
}