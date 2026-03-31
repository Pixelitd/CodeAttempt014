<?php

namespace magicode\pureentities\entity\animal\walking;

use magicode\pureentities\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Mooshroom extends WalkingAnimal{
    const NETWORK_ID = 16;

    public $width = 1.45;
    public $height = 1.12;

    public function getName(){
        return "Mooshroom";
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(10);
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        if($creature instanceof Player){
            return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::WHEAT && $distance <= 49;
        }
        return false;
    }

    public function getDrops(){
        $drops = [];
              $drops[] = Item::get(Item::LEATHER, 0, 2);
              if ($this->isOnFire()) {
                $drops[] = Item::get(Item::COOKED_BEEF, 0, mt_rand(1, 3));
            } else {
              $drops[] = Item::get(Item::RAW_BEEF, 0, 1); }
        }
    
}