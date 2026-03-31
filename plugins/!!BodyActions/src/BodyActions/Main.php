<?php

namespace BodyActions;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\block\Block;
use pocketmine\network\protocol\AdventureSettingsPacket;
use pocketmine\entity\Entity;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\utils\TextFormat;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\Info;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\Server;

class Main extends PluginBase implements Listener{
    
    private $tapping = [];
    private $laying = [];
    private $sitting = [];
    private $oldGamemode = [];
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->count = 10000;
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        if($sender instanceof Player){
            if($command->getName() === "lay"){
                $this->LayDown($sender);
                return true;
            }elseif($command->getName() === "sit"){
                $this->SitDown($sender);
                return true;
            }
        }else{
            $sender->sendMessage(TextFormat::RED . "This command can only be used within the game!");
            return true;
        }
    }
    
    public function onInteract(PlayerInteractEvent $event){
        if(isset($this->tapping[($player = $event->getPlayer())->getName()])){
            if(isset($this->laying[($player = $event->getPlayer())->getName()])){
                $player->teleport(new Vector3($player->getX(), $player->getY() + 0.5, $player->getZ()));
                $player->setDataProperty(Player::DATA_PLAYER_BED_POSITION, Player::DATA_TYPE_POS, [0, 0, 0]);
                $player->setDataFlag(Player::DATA_PLAYER_FLAGS, Player::DATA_PLAYER_FLAG_SLEEP, false);
                $pk = new AdventureSettingsPacket();
                $pk->flags = ($flags = 0);
                $pk->userPermission = 2;
                $pk->globalPermission = 2;
                $player->dataPacket($pk);
                unset($this->laying[$player->getName()]);
                if(isset($this->oldGamemode[$player->getName()])){
                $player->setGamemode($this->oldGamemode[$player->getName()]);
                unset($this->oldGamemode[$player->getName()]);
                unset($this->tapping[$player->getName()]);
                }
            }
            return;
        }
    }
    
    public function onMove(PlayerMoveEvent $event){
        $from = $event->getFrom();
        $to = $event->getTo();
        if(isset($this->laying[($player = $event->getPlayer())->getName()])) {
            if($from->getFloorX() !== $to->getFloorX() || $from->getFloorY() !== $to->getFloorY() || $from->getFloorZ() !== $to->getFloorZ()){
                $player->sendPopup("Touch/Right click to get up");
                $event->setCancelled();
            }
        }
    }
    
    public function onQuit(PlayerQuitEvent $event){
        if(isset($this->tapping[($player = $event->getPlayer())->getName()])){
            unset($this->tapping[$player->getName()]);
        }
        if(isset($this->laying[($player = $event->getPlayer())->getName()])){
            unset($this->laying[$player->getName()]);
        }
        if(isset($this->oldGamemode[($player = $event->getPlayer())->getName()])){
            unset($this->oldGamemode[$player->getName()]);
        }
    }
        
    public function LayDown(Player $player){
        $this->tapping[$player->getName()] = $player->getName();
        $player->teleport(new Vector3($player->getX(), $player->getY() - 1.2, $player->getZ()));
        $player->setDataProperty(Player::DATA_PLAYER_BED_POSITION, Player::DATA_TYPE_POS, [$player->getX(), $player->getY() + 1.2, $player->getZ()]);
        $player->setDataFlag(Player::DATA_PLAYER_FLAGS, Player::DATA_PLAYER_FLAG_SLEEP, true);
        $this->laying[$player->getName()] = [$player->getName()];
        $this->oldGamemode[$player->getName()] = $player->getGamemode();
        $pk = new AdventureSettingsPacket();
        $pk->flags = ($flags = 0x100);
        $pk->userPermission = 2;
        $pk->globalPermission = 2;
        $player->dataPacket($pk);
        return;
    }
    
    public function SitDown(Player $player){
        $pk = new AddEntityPacket();
        $this->count = $this->count++;
        $pk->eid= $this->count;
        $pk->type = 84;
        $pk->x= $player->getX();
        $pk->y = $player->getY() + 0.3;
        $pk->z = $player->getZ();
        $pk->speedX = 0;
        $pk->speedY = 0;
        $pk->speedZ = 0;
        $pk->yaw = $player->yaw;
        $pk->pitch = $player->pitch;
        $pk->metadata = [0 => [0,1<<5],2 =>[Entity::DATA_TYPE_STRING,"" ], Entity::DATA_SHOW_NAMETAG =>[0,1],15=>[ 0,1 ] ];
        $ps = Server::getInstance()->getOnlinePlayers();
        Server::broadcastPacket($ps,$pk);
        
        $ppk = new SetEntityLinkPacket();
        $ppk->from = $this->count;
        $ppk->to = 0;
        $ppk->type = 2;
        $player->dataPacket($ppk);
        
        $pkc = new SetEntityLinkPacket();
        $pkc->from = $this->count;
        $pkc->to = $player->getId();
        $pkc->type = 2;
        $ps = Server::getInstance()->getOnlinePlayers();
        Server::broadcastPacket($ps, $pkc);
        $this->sitting[$player->getName()] = $this->count;
    }
    
    public function onPacketReceived(DataPacketReceiveEvent $event){
        $pk = $event->getPacket();
        if(!is_object($pk)) return;
        if($pk::NETWORK_ID === Info::PLAYER_ACTION_PACKET or $pk::NETWORK_ID === 0xab){
            $player = $event->getPlayer();
            if(isset($this->sitting[$player->getName()])){
                if($pk->action === 8){
                    $ppk = new SetEntityLinkPacket();
                    $ppk->from = $this->sitting[$player->getName()];
                    $ppk->to = 0;
                    $ppk->type = 0;
                    $player->dataPacket($ppk);
                    
                    $pkc = new SetEntityLinkPacket();
                    $pkc->from = $this->sitting[$player->getName()];
                    $pkc->to = $player->getId();
                    $pkc->type = 0;
                    $ps = Server::getInstance()->getOnlinePlayers();
                    Server::broadcastPacket($ps, $pkc);
                    
                    $pk0 = new RemoveEntityPacket();
                    $pk0->eid = $this->sitting[$player->getName()];
                    $ps = Server::getInstance()->getOnlinePlayers();
                    Server::broadcastPacket($ps, $pk0);
                    unset($this->sitting[$player->getName()]);
                }
            }
        }
        if($pk::NETWORK_ID === Info::INTERACT_PACKET or $pk::NETWORK_ID === 0xa9){
            $player = $event->getPlayer();
            if(isset($this->laying[$player->getName()])){
                if($pk->action === 2){
                    if(isset($this->tapping[($player = $event->getPlayer())->getName()])){
                        $player->teleport(new Vector3($player->getX(), $player->getY() + 0.5, $player->getZ()));
                        $player->setDataProperty(Player::DATA_PLAYER_BED_POSITION, Player::DATA_TYPE_POS, [0, 0, 0]);
                        $player->setDataFlag(Player::DATA_PLAYER_FLAGS, Player::DATA_PLAYER_FLAG_SLEEP, false);
                        $pk = new AdventureSettingsPacket();
                        $pk->flags = ($flags = 0);
                        $pk->userPermission = 2;
                        $pk->globalPermission = 2;
                        $player->dataPacket($pk);
                        unset($this->laying[$player->getName()]);
                        if(isset($this->oldGamemode[$player->getName()])){
                            $player->setGamemode($this->oldGamemode[$player->getName()]);
                            unset($this->oldGamemode[$player->getName()]);
                            unset($this->tapping[$player->getName()]);
                        }
                    }
                }
            }
        }
    }
}