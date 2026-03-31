<?php
declare(strict_types=1);
namespace wallbanner\utils;
use function count;
use function intdiv;
final class Color{
	protected $a;
	protected $r;
	protected $g;
	protected $b;
	public function __construct(int $r, int $g, int $b, int $a = 0xff){
		$this->r = $r & 0xff;
		$this->g = $g & 0xff;
		$this->b = $b & 0xff;
		$this->a = $a & 0xff;
	}
	public function getA() : int{
		return $this->a;
	}
	public function getR() : int{
		return $this->r;
	}
	public function getG() : int{
		return $this->g;
	}
	public function getB() : int{
		return $this->b;
	}
	public static function mix(Color $color1, Color ...$colors) : Color{
		$colors[] = $color1;
		$count = count($colors);
		$a = $r = $g = $b = 0;
		foreach($colors as $color){
			$a += $color->a;
			$r += $color->r;
			$g += $color->g;
			$b += $color->b;
		}
		return new Color(intdiv($r, $count), intdiv($g, $count), intdiv($b, $count), intdiv($a, $count));
	}
	public static function fromRGB(int $code) : Color{
		return new Color(($code >> 16) & 0xff, ($code >> 8) & 0xff, $code & 0xff);
	}
	public static function fromARGB(int $code) : Color{
		return new Color(($code >> 16) & 0xff, ($code >> 8) & 0xff, $code & 0xff, (~($code >> 24) << 1) & 0xff);
	}
	public function toARGB() : int{
		return ($this->a << 24) | ($this->r << 16) | ($this->g << 8) | $this->b;
	}
	public static function fromRGBA(int $c) : Color{
		return new Color(($c >> 24) & 0xff, ($c >> 16) & 0xff, ($c >> 8) & 0xff, $c & 0xff);
	}
	public function toRGBA() : int{
		return ($this->r << 24) | ($this->g << 16) | ($this->b << 8) | $this->a;
	}
}