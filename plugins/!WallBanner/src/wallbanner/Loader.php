<?php
namespace wallbanner;
use pocketmine\plugin\PluginBase;
use wallbanner\command\WallBannerCommand;
use wallbanner\protocol\ClientboundMapItemDataPacket;
use wallbanner\protocol\MapInfoRequestPacket;
use wallbanner\storage\MapImageStorage;
use wallbanner\storage\MapStorage;
class Loader extends PluginBase {
	const SUPPORTED_IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg'];
	private static $instance = null;
	public static function get() {
		return self::$instance;
	}
	public $creates = [];
	public function onEnable() {
		if(!extension_loaded('gd')) {
			$this->getLogger()->info('This plugin requires the GD php extension to work!');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
		$this->registerCommands();
		$this->registerPackets();
		@mkdir(($folder = $this->getDataFolder()).'images/', 0755, true);
		MapStorage::init($folder.'maps/');
		self::$instance = $this;
	}
	public function onDisable() {
		self::$instance = null;
	}
	public function registerCommands() {
		$this->getServer()->getCommandMap()->register('wallbanner', new WallBannerCommand());
	}
	public function registerPackets() {
		$network = $this->getServer()->getNetwork();
		$network->registerPacket(ClientboundMapItemDataPacket::NETWORK_ID, ClientboundMapItemDataPacket::class);
		$network->registerPacket(MapInfoRequestPacket::NETWORK_ID, MapInfoRequestPacket::class);
        $network->registerPacket(0xc6, ClientboundMapItemDataPacket::class);
		$network->registerPacket(0xc7, MapInfoRequestPacket::class);
	}
	public function getLoadedImageList() : array {
		return array_filter(
			scandir($this->getDataFolder().'images/', SCANDIR_SORT_ASCENDING),
			function(string $directory) : bool {
				return in_array(pathinfo($directory, PATHINFO_EXTENSION), self::SUPPORTED_IMAGE_EXTENSIONS);
			}
		);
	}
}