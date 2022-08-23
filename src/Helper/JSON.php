<?php
namespace Krishna\Neo4j\Helper;

class JSON {
	use \Krishna\Neo4j\Helper\T_StaticOnly;
	
	public static function encode(mixed $object, bool $pretty = false) : string {
		$options = JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE;
		if($pretty) {
			$options |= JSON_PRETTY_PRINT;
		}
		$out = json_encode($object, $options);
		if($out === false) { return 'null'; }
		
		return $out;
	}

	public static function decode(string $json) : mixed { // Returns null on error
		return json_decode($json, true, flags: JSON_INVALID_UTF8_SUBSTITUTE);
	}
}