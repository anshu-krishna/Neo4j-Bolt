<?php
namespace Krishna\Neo4j\Helper;

class ListType {
	use T_StaticOnly;

	// Generic tester
	protected static function notTester(string $testerFuncName, array $list) : bool {
		try { ([static::class, $testerFuncName])(...$list); return false; } catch (\Throwable $th) { return true; }
	}
	protected static function isTester(string $testerFuncName, array $list) : bool {
		try { ([static::class, $testerFuncName])(...$list); return true; } catch (\Throwable $th) { return false; }
	}

	// Specific testers

	// String
	protected static function stringList(string ...$items) {}
	public static function notStringList(array $list): bool {
		return static::notTester('stringList', $list);
	}
	public static function isStringList(array $list): bool {
		return static::isTester('stringList', $list);
	}

	// Int
	protected static function intList(int ...$items) {}
	public static function notintList(array $list): bool {
		return static::notTester('intList', $list);
	}
	public static function isintList(array $list): bool {
		return static::isTester('intList', $list);
	}
}