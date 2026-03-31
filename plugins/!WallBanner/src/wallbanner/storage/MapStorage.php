<?php
namespace wallbanner\storage;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use wallbanner\Loader;
final class MapStorage {
	private static $instance = null;
	public static function get() {
		return self::$instance;
	}
	public static function init(string $path) {
		if(self::$instance === null) {
			self::$instance = new MapStorage($path);
		}
	}
	private $path = '';
	private $loadedMaps = [];
	private function __construct(string $path) {
		@mkdir($path, 0755, true);
		$this->path = $path;
	}
	public function getNextMapId() : int {
		for($i = 10; true; ++$i) {
			if(!$this->existsMap($i)) return $i;
		}
	}
	public function getMapData(int $mapId) {
		if($this->loadMapData($mapId)) {
			return $this->loadedMaps[$mapId];
		}
		return null;
	}
	public function loadMapData(int $mapId) : bool {
		if(isset($this->loadedMaps[$mapId])) {
			return true;
		}
		$path = $this->path."map_{$mapId}.dat";
		if(file_exists($path)) {
			$stream = (new NBT(NBT::BIG_ENDIAN));
			$stream->readCompressed(file_get_contents($path));
			$this->loadedMaps[$mapId] = $stream->getData();
			return true;
		}
		return false;
	}
	public function existsMap(int $mapId) : bool {
		return (isset($this->loadedMaps[$mapId]) || file_exists($this->path."map_{$mapId}.dat"));
	}
	public function saveMapData(int $mapId, CompoundTag $nbt) {
		$stream = (new NBT(NBT::BIG_ENDIAN));
		$stream->setData($nbt);
		if(($buffer = $stream->writeCompressed()) !== null) {
			file_put_contents($this->path."map_{$mapId}.dat", $buffer);
		}
	}
}