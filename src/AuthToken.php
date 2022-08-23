<?php
namespace Krishna\Neo4j;

class AuthToken {
	public static string $defaultUserAgent = 'KBolt/1.0';

	private function __construct(public readonly array $token) {}

	public static function none(?string $userAgent = null): static {
		return new static([
			'user_agent' => $userAgent ?? static::$defaultUserAgent,
			'scheme' => 'none'
		]);
	}
	public static function basic(string $username, string $password, ?string $userAgent = null): static {
		return new static([
			'user_agent' => $userAgent ?? self::$defaultUserAgent,
			'scheme' => 'basic',
			'principal' => $username,
			'credentials' => $password
		]);
	}
	public static function bearer(string $token, ?string $userAgent = null): static {
		return new static([
			'user_agent' => $userAgent ?? self::$defaultUserAgent,
			'scheme' => 'bearer',
			'credentials' => $token
		]);
	}
	public static function kerberos(string $token, ?string $userAgent = null): static {
		return new static([
			'user_agent' => $userAgent ?? self::$defaultUserAgent,
			'scheme' => 'kerberos',
			'principal' => '',
			'credentials' => $token
		]);
	}
}
