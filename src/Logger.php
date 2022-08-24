<?php
namespace Krishna\Neo4j;

final class Logger {
	private $callback;
	
	public function __construct(
		?callable $callback = null,
		private int $rowSize = 20,
		private int $wordSize = 4,
	) {
		$this->callback = $callback;
	}

	public static function hexify(string $data, int $rowSize = 20, int $wordSize = 4): string {
		$rowEnd = $rowSize - 1; $wordEnd = $wordSize - 1;
		$i = -1; $hex = [];
		foreach(str_split(unpack('H*', $data)[1], 2) as $byte) {
			$i++;
			$hex[] = $byte;
			if($i % $rowSize === $rowEnd) { $hex[] = "\n"; }
			elseif($i % $wordSize === $wordEnd) { $hex[] = '  '; }
			else { $hex[] = ' '; }
		}
		return trim(implode('', $hex));
	}
	public function log(string $str, ?string $title = null): void {
		$str = ($title === null ? '' : "{$title}:\n") . $str;
		if($this->callback === null) {
			echo '<pre>', htmlentities(
				$str,
				flags: ENT_SUBSTITUTE | ENT_HTML5,
				encoding: 'UTF-8'
			), '</pre>';
		} else { ($this->callback)($str); }
	}
	
	public function logRead(string|Buffer $content, ?string $title = null): void {
		$title = ($title === null) ? '' : " [{$title}]";
		if($content instanceof Buffer) { $content = $content->__toString(); }
		$this->log(self::hexify($content, $this->rowSize, $this->wordSize), 'Read' . $title);
	}
	public function logWrite(string|Buffer $content, ?string $title = null): void {
		$title = ($title === null) ? '' : " [{$title}]";
		if($content instanceof Buffer) { $content = $content->__toString(); }
		$this->log(self::hexify($content, $this->rowSize, $this->wordSize), 'Write' . $title);
	}
}