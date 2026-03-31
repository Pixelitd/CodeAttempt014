<?php

namespace Unconscious;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;

use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use Unconscious\Dummy;

class Main extends PluginBase implements Listener{
    
    private $dummies = [];
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        Entity::registerEntity(Dummy::class, true);
    }
    
    /*public function onDisable(){
        foreach ($this->dummies as $dummy) {
            $dummy->close();
        }
        $this->dummies = [];
    }*/
    
    public function onEntityDeath(EntityDeathEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof Dummy){
            $event->setDrops([]);
        }
    }
    
     public function onQuit(PlayerQuitEvent $event){
         $player = $event->getPlayer();
         $nbt = $this->makeNBT($player->getSkinData(), $player->getSkinId(), $player->getName(), $player->getInventory(), $player->getYaw(), $player->getPitch(), $player->getX(), $player->getFloorY() - 1.2, $player->getZ());
         $dummy = Entity::createEntity("Dummy", $player->getLevel()->getChunk($player->getX() >> 4, $player->getZ() >> 4), $nbt);
         $inv = $dummy->getInventory();
         $inv->setHelmet($player->getInventory()->getHelmet());
         $inv->setChestplate($player->getInventory()->getChestplate());
         $inv->setLeggings($player->getInventory()->getLeggings());
         $inv->setBoots($player->getInventory()->getBoots());
         $inv->setHeldItemSlot($player->getInventory()->getHeldItemSlot());
         $inv->setItemInHand($player->getInventory()->getItemInHand());
         $dummy->spawnToAll();
         $dummy->setDataProperty(Dummy::DATA_PLAYER_BED_POSITION, Dummy::DATA_TYPE_POS, [$dummy->x, $dummy->y, $dummy->z]);
         $dummy->setDataFlag(Dummy::DATA_PLAYER_FLAGS, Dummy::DATA_PLAYER_FLAG_SLEEP, true);
         $this->dummies = [$dummy];
     }
    
    private function makeNBT($skin, $skinId, $name, $inv, $yaw, $pitch, $x, $y, $z){
        $nbt = new CompoundTag;
        $nbt->Pos = new ListTag("Pos", [
            new DoubleTag("", $x),
            new DoubleTag("", $y),
            new DoubleTag("", $z)
        ]);
        $nbt->Rotation = new ListTag("Rotation", [
            new FloatTag("", $yaw),
            new FloatTag("", $pitch)
        ]);
        $nbt->Health = new ShortTag("Health", 1);
        $nbt->CustomName = new StringTag("CustomName", $name);
        $nbt->Commands = new CompoundTag("Commands", []);
        $nbt->MenuName = new StringTag("MenuName", "");
        $nbt->CustomNameVisible = new ByteTag("CustomNameVisible", 0);
        $nbt->Inventory = new ListTag("Inventory", $inv);
        $nbt->Skin = new CompoundTag("Skin", ["Data" => new StringTag("Data", $skin), "Name" => new StringTag("Name", $skinId)]);
        $nbt->Player = new StringTag("Player", $name);
        return $nbt;
    }
    
    public function onJoin(PlayerJoinEvent $event){
        foreach(($player = $event->getPlayer())->getLevel()->getEntities() as $entity){
            if($entity instanceof Dummy){
                $entity->spawnTo($player);
                $entity->setDataProperty(Dummy::DATA_PLAYER_BED_POSITION, Dummy::DATA_TYPE_POS, [$entity->x, $entity->y, $entity->z]);
                $entity->setDataFlag(Dummy::DATA_PLAYER_FLAGS, Dummy::DATA_PLAYER_FLAG_SLEEP, true);
                $playerName = $entity->namedtag->getString("Player", "");
                if ($playerName === $player->getName()){
                    $entity->close();
                }
            }
        }
    }
    
    public function onChunkLoad(ChunkLoadEvent $event){
        $chunk = $event->getChunk();

    	foreach ($chunk->getEntities() as $entity){
        	if($entity instanceof Dummy){
            	foreach($this->getServer()->getOnlinePlayers() as $player){
                	if($player->getLevel() === $entity->getLevel()){
                    	$chunkX = $entity->getX() >> 4;
                    	$chunkZ = $entity->getZ() >> 4;
                    	$playerChunkX = $player->getX() >> 4;
                    	$playerChunkZ = $player->getZ() >> 4;
                    	if(abs($chunkX - $playerChunkX) <= 8 && abs($chunkZ - $playerChunkZ) <= 8){
                        	$entity->respawnToAll();
                        	$entity->setDataProperty(Dummy::DATA_PLAYER_BED_POSITION, Dummy::DATA_TYPE_POS, [$entity->x, $entity->y, $entity->z]);
                        	$entity->setDataFlag(Dummy::DATA_PLAYER_FLAGS, Dummy::DATA_PLAYER_FLAG_SLEEP, true);
                    	}
                    }
                }
            }
        }
    }
}