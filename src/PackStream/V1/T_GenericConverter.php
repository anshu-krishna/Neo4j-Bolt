<?php
namespace Krishna\Neo4j\PackStream\V1;

use Krishna\Neo4j\Ex\PackEx;

// Bench Results on PHP 8.1
// 'Method1' => float 5.0490999221802
// 'Method2' => float 15.689797401428

// METHOD 1
trait T_GenericConverter {
	public static function fromGenericStruct(GenericStruct $struct): ?static {
		if($struct->sig !== static::SIG) { return null; } // Intellsense error indicator is irrelevant
		try {
			return new static(...$struct->fields);
		} catch (\Throwable $th) {
			throw new PackEx('Invalid ' . static::class . '; ' . $th->getMessage());
		}
	}
	public function toGenericStruct() : GenericStruct {
		$refProps = (new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
		$props = [];
		foreach($refProps as $prop) {
			$props[] = $prop->getValue($this);
		}
		return new GenericStruct(static::SIG, ...$props); // Intellsense error indicator is irrelevant
	}
}

// METHOD 2
/*
trait T_GenericConverter {	
	public function clock() {
		$t1 = microtime(true);
		$a = static::SIG; // Method 1
		$t2 = microtime(true);
		$b = (new \ReflectionClassConstant(static::class, 'SIG'))->getValue();
		$t3 = microtime(true); // Method 2
		return [$t2 - $t1, $t3 - $t2];
	}
	public function bench(int $run = 1000000) {
		$t1 = 0;
		$t2 = 0;
		for ($i = 0; $i < $run; $i++) { 
			[$a, $b] = $this->clock();
			$t1 += $a;
			$t2 += $b;
		}
		var_dump(['Method1' => ($t1 / $run) * 10e6, 'Method2' => ($t2 / $run) * 10e6]);
	}
	public static function fromGenericStruct(GenericStruct $struct): ?static {
		$SIG = (new \ReflectionClassConstant(static::class, 'SIG'))->getValue();
		if($struct->sig !== $SIG) { return null; }
		try {
			return new static(...$struct->fields);
		} catch (\Throwable $th) {
			throw new PackEx('Invalid ' . static::class . '; ' . $px->getMessage());
		}
	}
	public function toGenericStruct() : GenericStruct {
		$refProps = (new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
		$props = [];
		foreach($refProps as $prop) {
			$props[$prop->getName()] = $prop->getValue($this);
		}
		$SIG = (new \ReflectionClassConstant(static::class, 'SIG'))->getValue();
		return new GenericStruct($SIG, ...$props);
	}
}
*/