<?php

namespace AntiVoid;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {
	
	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function onDisable() {
	}
	
	public function onMove(PlayerMoveEvent $event) {
		if($event->getPlayer()->getY() < -5) {
			$event->getPlayer()->teleport($event->getPlayer()->getLevel()->getSafeSpawn());
		}
	}
	
	public function onDamage(EntityDamageEvent $event) {
		if($event->getEntity() instanceof Player && $event->getEntity()->getY() < 0) {
			$event->setCancelled();
		}
	}
}