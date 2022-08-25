<?php
namespace Krishna\Neo4j;

use Krishna\Neo4j\Conn\E_ConnType;
use Krishna\Neo4j\Ex\ConnEx;
use Krishna\Neo4j\Protocol\A_Bolt;

class Neo4j {
	private static ?array $vmeta = null;
	public static function getVersionMeta(): array {
		self::$vmeta ??= [
			['bin' => hex2bin('00000000'), 'class' => null],
			['bin' => hex2bin('00000104'), 'class' => __NAMESPACE__ . '\Protocol\V4_1\Bolt_4_1'],
			['bin' => hex2bin('00000204'), 'class' => __NAMESPACE__ . '\Protocol\V4_2\Bolt_4_2'],
			['bin' => hex2bin('00000304'), 'class' => __NAMESPACE__ . '\Protocol\V4_3\Bolt_4_3'],
			['bin' => hex2bin('00000404'), 'class' => __NAMESPACE__ . '\Protocol\V4_4\Bolt_4_4'],
		];
		return self::$vmeta;
	}

	private array $protocols;
	private readonly string $host;
	public function __construct(
		private readonly AuthToken $auth,
		string $host = '127.0.0.1',
		private readonly int $port = 7687,
		private readonly E_ConnType $connType = E_ConnType::Socket,
		private readonly ?array $routing = null,
		private readonly float $timeout = 15,
		private readonly ?Logger $logger = null,
	) {
		$this->protocols = [
			E_Version::V4_4->toBin(),
			E_Version::V4_3->toBin(),
			E_Version::V4_2->toBin(),
			E_Version::V4_1->toBin(),
		];
		if(filter_var($host, FILTER_VALIDATE_URL)) {
			$scheme = parse_url($host, PHP_URL_SCHEME);
			if(!empty($scheme)) {
				$host = str_replace("{$scheme}://", '', $host);
			}
		}
		$this->host = $host;
	}
	public function useVersion(
		E_Version $first,
		?E_Version $second = null,
		?E_Version $third = null,
		?E_Version $fourth = null
	): static {
		$p = [];
		foreach(func_get_args() as $v) {
			if($v !== null) { $p[] = $v->toBin(); }
		}
		$none = hex2bin('00000000');
		while(count($p) < 4) {
			$p[] = $none;
		}
		$this->protocols = $p;
		return $this;
	}
	public function getBolt(): A_Bolt {
		$socket = new ($this->connType->value);
		$socket->connect($this->host, $this->port, $this->timeout);
		$pkt = Buffer::Writable(hex2bin('6060b017'));
		$pkt->writeIterable($this->protocols);
		$pkt->makeReadable();
		$this->logger?->logWrite($pkt, 'Handshake');
		$socket->write($pkt);
		$pkt = $socket->read(4);
		$this->logger?->logRead($pkt, 'Handshake');
		if($pkt === 'HTTP') {
			$socket->disconnect();
			throw new ConnEx("Cannot to connect to Bolt service on {$this->host}:{$this->port}; Looks like HTTP;");
		}
		$class = (E_Version::fromBin($pkt))?->toClass();
		if($class === null) {
			$socket->disconnect();
			throw new ConnEx("Cannot to connect to Bolt service on {$this->host}:{$this->port}; Unsupported protocol version(s);");
		}
		return new ($class)($socket, $this->auth, $this->routing, $this->logger);
	}
}