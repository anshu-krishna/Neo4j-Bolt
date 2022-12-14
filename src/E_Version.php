<?php
namespace Krishna\Neo4j;

enum E_Version : int {
	case V4_1 = 1;
	case V4_2 = 2;
	case V4_3 = 3;
	case V4_4 = 4;
	
	public function toBin(): string {
		return BoltMaker::getVersionMeta()[$this->value]['bin'];
	}
	public function toClass(): string {
		return BoltMaker::getVersionMeta()[$this->value]['class'];
	}
	public function toVersionNumber(): string {
		return (BoltMaker::getVersionMeta()[$this->value]['class'])::VERSION;
	}
	public static function fromBin(string $ver): ?static {
		$meta = BoltMaker::getVersionMeta();
		return match($ver) {
			$meta[1]['bin'] => self::V4_1,
			$meta[2]['bin'] => self::V4_2,
			$meta[3]['bin'] => self::V4_3,
			$meta[4]['bin'] => self::V4_4,
			default => null
		};
	}
}