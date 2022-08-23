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

	public function hexify(string $data): string {
		$rowEnd = $this->rowSize - 1; $wordEnd = $this->wordSize - 1;
		$i = -1; $hex = [];
		foreach(str_split(unpack('H*', $data)[1], 2) as $byte) {
			$i++;
			$hex[] = $byte;
			if($i % $this->rowSize === $rowEnd) { $hex[] = "\n"; }
			elseif($i % $this->wordSize === $wordEnd) { $hex[] = '  '; }
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
	
	public function logRead(Buffer $buffer, ?string $title = null): void {
		$title = ($title === null) ? '' : " [{$title}]";
		self::log(self::hexify($buffer->__toString()), 'Read' . $title);
	}
	public function logWrite(Buffer $buffer, ?string $title = null): void {
		$title = ($title === null) ? '' : " [{$title}]";
		self::log(self::hexify($buffer->__toString()), 'Write' . $title);
	}
}