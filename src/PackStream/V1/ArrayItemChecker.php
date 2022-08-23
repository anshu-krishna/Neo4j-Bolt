<?php
namespace Krishna\Neo4j\PackStream\V1;

use Krishna\Neo4j\Helper\T_StaticOnly;
use Krishna\Neo4j\PackStream\V1\Type\{Node, Relationship};
use Throwable;

class ArrayItemChecker {
	use T_StaticOnly;

	protected static function nodeListTester(Node ...$items) {}
	public static function notNodeList(array $values): bool {
		try {
			static::nodeListTester(...$values);
			return false;
		} catch (Throwable $th) {
			return true;
		}
	}
	protected static function relListTester(Relationship ...$items) {}
	public static function notRelList(array $values): bool {
		try {
			static::relListTester(...$values);
			return false;
		} catch (Throwable $th) {
			return true;
		}
	}
	protected static function intListTester(int ...$items) {}
	public static function notIntList(array $values): bool {
		try {
			static::intListTester(...$values);
			return false;
		} catch (Throwable $th) {
			return true;
		}
	}
	protected static function stringListTester(string ...$items) {}
	public static function notStringList(array $values): bool {
		try {
			static::stringListTester(...$values);
			return false;
		} catch (Throwable $th) {
			return true;
		}
	}
}