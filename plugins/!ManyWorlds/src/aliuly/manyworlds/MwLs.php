<?php
/**
 ** OVERVIEW:Basic Usage
 **
 ** COMMANDS
 **
 ** * ls : Provide world information
 **   usage: /mw **ls** _[world]_
 **
 **   If _world_ is not specified, it will list available worlds.
 **   Otherwise, details for _world_ will be provided.
 **/
declare(strict_types=1);
namespace aliuly\manyworlds;

use aliuly\manyworlds\common\BasicCli;
use aliuly\manyworlds\common\BasicPlugin;
use aliuly\manyworlds\common\mc;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

final class MwLs extends BasicCli{
	public function __construct(BasicPlugin $owner){
		parent::__construct($owner);
		$this->enableSCmd("ls", ["usage" => mc::_("[world: string]"),
			"help" => mc::_("List world information"),
			"permission" => "mw.cmd.ls",
			"aliases" => ["list", "info"]]);
	}

	private function mwWorldList(CommandSender $sender): array {
		$dir = $this->owner->getServer()->getDataPath() . "worlds";
		if(!is_dir($dir)){
			$sender->sendMessage(mc::_("[MW] Missing path %1%", $dir));

			return null;
		}
		$txt = ["HDR"];

		$auto = $this->owner->getServer()->getProperty("worlds", []);
		$default = $this->owner->getServer()->getDefaultLevel();
		if($default){
			$default = $default->getFolderName();
		}

		$count = 0;
		$dh = opendir($dir);
		if(!$dh){
			return null;
		}
		while(($file = readdir($dh)) !== false){
			if($file === '.' || $file === '..'){
				continue;
			}
			if(!$this->owner->getServer()->isLevelGenerated($file)){
				continue;
			}
			$attrs = [];
			++$count;
			if(isset($auto[$file])){
				$attrs[] = mc::_("auto");
			}
			if($default == $file){
				$attrs[] = mc::_("default");
			}
			if($this->owner->getServer()->isLevelLoaded($file)){
				$attrs[] = mc::_("loaded");
				$np = count($this->owner->getServer()->getLevelByName($file)->getPlayers());
				if($np > 0){
					$attrs[] = mc::_("players:%1%", (string)$np);
				}
			}
			$ln = "- $file";
			if(count($attrs)){
				$ln .= TextFormat::AQUA . " (" . implode(",", $attrs) . ")";
			}
			$txt[] = $ln;
		}
		closedir($dh);
		$txt[0] = mc::_("Worlds: %1%", (string)$count);

		return $txt;
	}

	private function mwWorldDetails(CommandSender $sender, string $worldName): array {
		$txt = [];
		if($this->owner->getServer()->isLevelLoaded($worldName)){
			$unload = false;
		}else{
			if(!$this->owner->autoLoad($sender, $worldName)){
				$sender->sendMessage(TextFormat::RED . mc::_("Error getting %1%", $worldName));

				return null;
			}
			$unload = true;
		}
		$world = $this->owner->getServer()->getLevelByName($worldName);

		//==== provider
		$provider = $world->getProvider();
		$txt[] = mc::_("Info for %1%", $worldName);
		$txt[] = TextFormat::AQUA . mc::_("Provider: ") . TextFormat::WHITE . get_class($provider);
		$txt[] = TextFormat::AQUA . mc::_("Path: ") . TextFormat::WHITE . $provider->getPath();
		$txt[] = TextFormat::AQUA . mc::_("Name: ") . TextFormat::WHITE . $world->getFolderName();
		$txt[] = TextFormat::AQUA . mc::_("Seed: ") . TextFormat::WHITE . $world->getSeed();
		$txt[] = TextFormat::AQUA . mc::_("Generator: ") . TextFormat::WHITE . $provider->getGenerator();
		$gopts = $provider->getGeneratorOptions();
		if($gopts != ""){
			$txt[] = TextFormat::AQUA . mc::_("Generator Presets: ") . TextFormat::WHITE . $gopts;
		}
		$spawn = $world->getSpawnLocation();
		$txt[] = TextFormat::AQUA . mc::_("Spawn: ") . TextFormat::WHITE . $spawn->getX() . "," . $spawn->getY() . "," . $spawn->getZ();
		$plst = $world->getPlayers();
		$lst = "";
		foreach($plst as $p){
			$lst .= ($lst !== "" ? ", " : "") . $p->getName();
		}
		$txt[] = TextFormat::AQUA . mc::_("Players(%1%):", (string)count($plst)) .
			TextFormat::WHITE . $lst;

		// Check for warnings...
		if($world->getFolderName() !== $worldName){
			$txt[] = TextFormat::RED . mc::_("Folder Name and Level.Dat names do NOT match");
			$txt[] = TextFormat::RED . mc::_("This can cause intermitent problems");
			if($sender->hasPermission("mw.cmd.lvdat")){
				$txt[] = TextFormat::RED . mc::_("Use: ");
				$txt[] = TextFormat::GREEN . mc::_("> /mw fixname %1%", $worldName);
				$txt[] = TextFormat::RED . mc::_("to fix this issue");
			}
		}

		if($unload){
			$this->owner->getServer()->unloadLevel($world);
		}

		return $txt;
	}

	public function onSCommand(CommandSender $c, Command $cc, string $scmd, $data, array $args): bool {
		$pageNumber = $this->getPageNumber($args);
		if(count($args) === 0){
			$txt = $this->mwWorldList($c);
		}else{
			$wname = implode(" ", $args);
			$txt = $this->mwWorldDetails($c, $wname);
		}
		if($txt == null){
			return true;
		}

		return $this->paginateText($c, $pageNumber, $txt);
	}
}
