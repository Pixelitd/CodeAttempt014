<?php
namespace wallbanner\utils;
use pocketmine\utils\BinaryStream;
use InvalidArgumentException;
use pocketmine\level\Level;
use RuntimeException;
use function imagecreatefromjpeg;
use function imagecreatefrompng;
class Image {
	public static function getBytesFromPng(string $path) : string {
		if(!(($gave = pathinfo($path, PATHINFO_EXTENSION)) === 'png')) {
			throw new InvalidArgumentException('File extension must be a PNG Extension');
		}
		list($width, $height) = getimagesize($path);
		$image = imagecreatefrompng($path);
		$steam = new BinaryStream();
		for($y = 0; $y < $height; $y++) {
			for($x = 0; $x < $width; $x++) {
				$argb = \imagecolorat($image, $x, $y);
				$steam->putByte(($argb >> 16) & 0xff); // R
				$steam->putByte(($argb >>  8) & 0xff); // G
				$steam->putByte($argb & 0xff);         // B
				$steam->putByte((~($argb >> 24) << 1) & 0xff); // A
			}
		}
		imagedestroy($image);
		return $steam->getBuffer();
	}
	public static function getPiecesOfImagePerBlockArea(
		string $imagePath,
		int $blockMaxY = 1,
		int $blockMaxX = 1,
		int $pixelsPerBlock = 128,
		bool $useSuppliedARGB = false
	) : array {
		$imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
		switch($imageType) {
			case 'png':
				$sourceImage = imagecreatefrompng($imagePath);
				break;
			case 'jpeg':
			case 'jpg':
				$sourceImage = imagecreatefromjpeg($imagePath);
				break;
			default:
				throw new RuntimeException('Unsupported image type: '.$imageType);
		}
		$pixelsPerBlock = max(1, min(128, $pixelsPerBlock));
		$blockMaxY = max(1, $blockMaxY);
		$blockMaxX = max(1, $blockMaxX);
		$height = $blockMaxY * $pixelsPerBlock;
		$width  = $blockMaxX  * $pixelsPerBlock;
		$image = imagecreatetruecolor($width, $height);
		imagecopyresampled($image, $sourceImage, 0, 0, 0, 0, $width, $height, imagesx($sourceImage), imagesy($sourceImage));
		$pieces = [];
		for($y = 0; $y < $height; ++$y) {
			$blockY = (int) floor($y / $pixelsPerBlock);
			for($x = 0; $x < $width; ++$x) {
				$color = Color::fromARGB(imagecolorat($image, $x, $y));
				$blockX = (int) floor($x / $pixelsPerBlock);
				$hash = Level::chunkHash($blockX, $blockY);
				if(!isset($pieces[$hash])) $pieces[$hash] = "";
				if($useSuppliedARGB) {
					$pieces[$hash] .= self::writeUnsignedVarInt($color->toARGB());
				} else {
					$pieces[$hash] .=
						chr($color->getR()) .
						chr($color->getG()) .
						chr($color->getB()) .
						chr($color->getA());
				}
			}
		}
		imagedestroy($sourceImage);
		imagedestroy($image);
		return $pieces;
	}
	private static function writeUnsignedVarInt(int $value) : string {
		$buf = "";
		$value &= 0xffffffff;
		for($i = 0; $i < 5; ++$i) {
			if(($value >> 7) !== 0){
				$buf .= chr($value | 0x80);
			}else{
				$buf .= chr($value & 0x7f);
				return $buf;
			}
			$value = (($value >> 7) & (PHP_INT_MAX >> 6));
		}
		throw new InvalidArgumentException("Value too large to be encoded as a VarInt");
	}
}