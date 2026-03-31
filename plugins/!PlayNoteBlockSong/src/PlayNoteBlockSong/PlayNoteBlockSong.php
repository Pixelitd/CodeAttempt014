<?php
namespace PlayNoteBlockSong;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat as Color;
use pocketmine\event\TranslationContainer as Translation;
use pocketmine\block\NoteBlock;
use PlayNoteBlockSong\task\LoadSongAsyncTask;
use PlayNoteBlockSong\task\PlaySongTask;
use pocketmine\Player;
use pocketmine\network\protocol\BlockEventPacket;
use pocketmine\network\protocol\UpdateBlockPacket;

class PlayNoteBlockSong extends PluginBase{
    const SONG = 0;
    const NAME = 1;

    private $songs = [], $index = 0, $song = null, $play = false;

    public function onEnable(){
        $this->getServer()->getScheduler()->scheduleAsyncTask(new LoadSongAsyncTask());
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new PlaySongTask($this), 2);
        $this->isKorean = $this->getServer()->getLanguage()->getName() === "\"한국어\"";
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, array $sub){
        $ik = $this->isKorean;
        if(!isset($sub[0]) || $sub[0] === ""){
            return false;
        }

        switch(strtolower($sub[0])){
            case "play":
            case "p":
                if(!$sender->hasPermission("playnoteblocksong.cmd.play")){
                    $r = new Translation(Color::RED . "%commands.generic.permission");
                } elseif($this->play){
                    $r = Color::RED . "[PlayNBS] " . ($ik ? "이미 재생중입니다." : "Already playing");
                } elseif(count($this->songs) <= 0){
                    $r = Color::RED . "[PlayNBS] " . ($ik ? "당신은 음악이 하나도 없습니다." : "You don't have any song");
                } else {
                    if($this->song === null){
                        $filePath = $this->songs[$this->index][self::SONG];
                        $this->song = new SongPlayer($this, $filePath);
                    }
                    $this->play = true;
                    $r = Color::YELLOW . "[PlayNBS] " . ($ik ? "음악을 재생합니다. : " : "Play the song : ") . $this->songs[$this->index][self::NAME];
                }
            break;

            case "stop":
            case "s":
                if(!$sender->hasPermission("playnoteblocksong.cmd.stop")){
                    $r = new Translation(Color::RED . "%commands.generic.permission");
                } elseif(!$this->play){
                    $r = Color::RED . "[PlayNBS] " . ($ik ? "음악이 재생중이 아닙니다." : "Song is not playing");
                } else {
                    $this->play = false;
                    $r = Color::YELLOW . "[PlayNBS] " . ($ik ? "음악을 중지합니다." : "Stop the song");
                }
            break;

            case "next":
            case "n":
                if(!$sender->hasPermission("playnoteblocksong.cmd.next")){
                    $r = new Translation(Color::RED . "%commands.generic.permission");
                } elseif(count($this->songs) <= 0){
                    $r = Color::RED . "[PlayNBS] " . ($ik ? "당신은 음악이 하나도 없습니다." : "You don't have any song");
                } else {
                    $this->index = isset($this->songs[$this->index + 1]) ? $this->index + 1 : 0;
                    $filePath = $this->songs[$this->index][self::SONG];
                    $this->song = new SongPlayer($this, $filePath);
                    $this->getLogger()->debug(Color::AQUA . "Play next song : " . $this->songs[$this->index][self::NAME]);
                    $r = Color::YELLOW . "[PlayNBS] " . ($ik ? "다음 음악을 재생합니다. : " : "Play next song : ") . $this->songs[$this->index][self::NAME];
                }
            break;

            case "prev":
            case "pr":
                if(!$sender->hasPermission("playnoteblocksong.cmd.prev")){
                    $r = new Translation(Color::RED . "%commands.generic.permission");
                } elseif(count($this->songs) <= 0){
                    $r = Color::RED . "[PlayNBS] " . ($ik ? "당신은 음악이 하나도 없습니다." : "You don't have any song");
                } else {
                    $this->index = isset($this->songs[$this->index - 1]) ? $this->index - 1 : 0;
                    $filePath = $this->songs[$this->index][self::SONG];
                    $this->song = new SongPlayer($this, $filePath);
                    $this->getLogger()->debug(Color::AQUA . "Play prev song : " . $this->songs[$this->index][self::NAME]);
                    $r = Color::YELLOW . "[PlayNBS] " . ($ik ? "이전 음악을 재생합니다. : " : "Play prev song : ") . $this->songs[$this->index][self::NAME];
                }
            break;

            case "shuffle":
            case "sh":
                if(!$sender->hasPermission("playnoteblocksong.cmd.shuffle")){
                    $r = new Translation(Color::RED . "%commands.generic.permission");
                } elseif(count($this->songs) <= 0){
                    $r = Color::RED . "[PlayNBS] " . ($ik ? "당신은 음악이 하나도 없습니다." : "You don't have any song");
                } else {
                    shuffle($this->songs);
                    $this->index = 0;
                    $filePath = $this->songs[$this->index][self::SONG];
                    $this->song = new SongPlayer($this, $filePath);
                    $this->getLogger()->debug(Color::AQUA . "Song list is shuffled. Now song : " . $this->songs[$this->index][self::NAME]);
                    $r = Color::YELLOW . "[PlayNBS] " . ($ik ? "음악 목록이 뒤섞였습니다. 다음 음악 : " : "Song list is shuffled. Now song : ") . $this->songs[$this->index][self::NAME];
                }
            break;

            case "list":
            case "l":
                if(!$sender->hasPermission("playnoteblocksong.cmd.list")){
                    $r = new Translation(Color::RED . "%commands.generic.permission");
                } elseif(count($this->songs) <= 0){
                    $r = Color::RED . "[PlayNBS] " . ($ik ? "당신은 음악이 하나도 없습니다." : "You don't have any song");
                } else {
                    $lists = array_chunk($this->songs, 5);
                    $page = min(isset($sub[1]) && is_numeric($sub[1]) && isset($lists[$sub[1]-1]) ? $sub[1] : 1, count($lists));
                    $r = Color::YELLOW . "[PlayNBS] " . ($ik ? "음악 목록 (페이지: " : "Song list (Page: ") . "$page/" . count($lists) . ") (" . count($this->songs) . ")";
                    if(isset($lists[$page-1])){
                        foreach($lists[$page-1] as $key => $songData){
                            $r .= "\n" . Color::GOLD . "    [" . (($page-1)*5 + $key + 1) . "] " . $songData[self::NAME];
                        }
                    }
                }
            break;

            case "reload":
            case "r":
                if(!$sender->hasPermission("playnoteblocksong.cmd.reload")){
                    $r = new Translation(Color::RED . "%commands.generic.permission");
                } else {
                    $this->loadSong();
                    $r = Color::YELLOW . "[PlayNBS] " . ($ik ? "음악을 다시 로드했습니다." : "Reloaded songs.");
                }
            break;

            default:
                return false;
        }

        if(isset($r)){
            $sender->sendMessage($r);
        }
        return true;
    }

    public function loadSong(){
        $this->songs = [];
        $logger = $this->getLogger();
        @mkdir($folder = $this->getDataFolder());
        if($dir = @opendir($folder)){
            $logger->debug(Color::AQUA . "Loading songs...");
            while(($file = readdir($dir)) !== false){
                if(($pos = stripos($file, ".nbs")) !== false){
                    $name = substr($file, 0, $pos);
                    $this->songs[] = [$folder . $file, $name];
                    $logger->debug(Color::AQUA . "$name is loaded");
                }
            }
            closedir($dir);
        }

        if(count($this->songs) >= 1){
            $logger->debug(Color::AQUA . "Load complete, " . count($this->songs) . " song(s) found.");
        } else {
            $logger->debug(Color::DARK_RED . "No songs found! Please put .nbs files in $folder");
        }
    }

    public function playSong(){
        if($this->play){
            if($this->song === null || $this->song->isStop()){
                $this->index = isset($this->songs[$this->index+1]) ? $this->index+1 : 0;
                $filePath = $this->songs[$this->index][self::SONG];
                $this->song = new SongPlayer($this, $filePath);
                $this->getLogger()->debug(Color::AQUA . "Playing next song: " . $this->songs[$this->index][self::NAME]);
            }
            $this->song->onRun();
        }
    }

    public function runNoteBlockSound(Position $pos, $pitch, $type = 0, $players = null){ 
        if(!is_array($players)){ 
            if($players instanceof Player){
                $players = [$players]; 
            }elseif($players == null){
                $players = $pos->getLevel()->getChunkPlayers($pos->x >> 4, $pos->z >> 4);
            }else{
                return false;
            }
        }
        if(class_exists('\pocketmine\network\protocol\p70\BlockEventPacket') and class_exists('\pocketmine\network\protocol\p70\UpdateBlockPacket')){
            $soundPk = new \pocketmine\network\protocol\p70\BlockEventPacket;
            $soundPk->x = $pos->x;
            $soundPk->y = $pos->y;
            $soundPk->z = $pos->z;
            $soundPk->case1 = $type;
            $soundPk->case2 = $pitch;
            $setNoteBlockPk = new \pocketmine\network\protocol\p70\UpdateBlockPacket;
            $setNoteBlockPk->records[] = [$pos->x, $pos->z, $pos->y, 25, 0, \pocketmine\network\protocol\p70\UpdateBlockPacket::FLAG_NONE];
            $realBlock = $pos->getLevel()->getBlock($pos); $setRealBlockPk = new \pocketmine\network\protocol\p70\UpdateBlockPacket;
            $setRealBlockPk->records[] = [$pos->x, $pos->z, $pos->y, $realBlock->getID(), $realBlock->getDamage(), \pocketmine\network\protocol\p70\UpdateBlockPacket::FLAG_NONE];
            $this->getServer()->getInstance()->batchPackets($players, [$setNoteBlockPk, $soundPk, $setRealBlockPk], true);
            return true;
        }else{
            $soundPk = new BlockEventPacket();
            $soundPk->x = $pos->x;
            $soundPk->y = $pos->y;
            $soundPk->z = $pos->z;
            $soundPk->case1 = $type;
            $soundPk->case2 = $pitch;
            $setNoteBlockPk = new UpdateBlockPacket();
            $setNoteBlockPk->records[] = [$pos->x, $pos->z, $pos->y, 25, 0, UpdateBlockPacket::FLAG_NONE];
            $realBlock = $pos->getLevel()->getBlock($pos);
            $setRealBlockPk = new UpdateBlockPacket(); $setRealBlockPk->records[] = [$pos->x, $pos->z, $pos->y, $realBlock->getID(), $realBlock->getDamage(), UpdateBlockPacket::FLAG_NONE];
            $this->getServer()->getInstance()->batchPackets($players, [$setNoteBlockPk, $soundPk, $setRealBlockPk], true);
            return true;
        }
    }

    public function sendSound($pitch, $type = 1){
		foreach($this->getServer()->getOnlinePlayers() as $player){
            $this->runNoteBlockSound(new Position($player->x, $player->y + 1, $player->z, $player->level), $pitch, $type, $player);
            if(class_exists('\pocketmine\network\AnyVersionManager')){
                if($player->getPlayerVersion() === "0.15"){
                    $player->sendTip("§c0.15 not supported\n§b|->§6Now Playing: §a".$this->songs[$this->index][self::NAME]."§b<-|");
                }else{
            	$player->sendTip("§b|->§6Now Playing: §a".$this->songs[$this->index][self::NAME]."§b<-|");
                }
            }else{
                $player->sendTip("§b|->§6Now Playing: §a".$this->songs[$this->index][self::NAME]."§b<-|");
            }
		}
	}
}
