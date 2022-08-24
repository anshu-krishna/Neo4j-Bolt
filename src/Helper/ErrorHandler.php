<?php
namespace Krishna\Neo4j\Helper;
class ErrorHandler {
	use T_StaticOnly;

	private static bool $paused = false;
	public static function handler(int $errno, string $errstr, string $errfile, int $errline): bool {
		// static::resume();
		error_clear_last();
		return true;
	}
	public static function pause() {
		if(!static::$paused) {
			set_error_handler([static::class, 'handler']);
			static::$paused = true;
		}
	}
	public static function resume() {
		if(static::$paused) {
			restore_error_handler();
			static::$paused = false;
		}
	}
}