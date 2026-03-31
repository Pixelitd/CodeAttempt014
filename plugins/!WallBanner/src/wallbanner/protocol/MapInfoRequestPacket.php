<?php
namespace wallbanner\protocol;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info;
class MapInfoRequestPacket extends DataPacket {
	const NETWORK_ID = Info::MAP_INFO_REQUEST_PACKET;
	public $mapId;
	public function decode() {
		$this->mapId = $this->getLong();
	}
	public function encode(){
		$this->reset();
		$this->putLong($this->mapId);
	}
}