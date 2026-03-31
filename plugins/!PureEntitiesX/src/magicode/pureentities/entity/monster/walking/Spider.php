<?php

namespace magicode\pureentities\entity\monster\walking;

use magicode\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

class Spider extends WalkingMonster{
    const NETWORK_ID =35;

    public $width = 1.3;
    public $height = 1.12;

    public function getSpeed() : float{
        return 1.3;
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(16);
        $this->setDamage([0, 2, 2, 3]);
    }

    public function getName(){
        return "Spider";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.32){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        $drops = [];
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            switch(mt_rand(0, 2)){
                case 0:
                    $drops[] = Item::get(Item::STRING, 0, 1);
                    break;
                case 1:
                    $drops[] = Item::get(Item::SPIDER_EYE, 0, 1);
                    break;
            }
        }
        return $drops;
    }

}
