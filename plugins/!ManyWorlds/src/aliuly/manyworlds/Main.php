<?php
/**
 **
 **/
declare(strict_types=1);
namespace aliuly\manyworlds;

use aliuly\manyworlds\common\BasicHelp;
use aliuly\manyworlds\common\BasicPlugin;
use aliuly\manyworlds\common\mc;
use aliuly\manyworlds\common\MPMU;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

final class Main extends BasicPlugin {

	public function onEnable() {
		// We don't really need this...
		//if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		mc::plugin_init($this, $this->getFile());

		$this->modules = [];
		foreach([
			"MwTp",
			"MwLs",
			"MwCreate",
			"MwGenLst",
			"MwLoader",
			"MwLvDat",
			"MwDefault",
		] as $mod){
			$mod = __NAMESPACE__ . "\\" . $mod;
			$this->modules[] = new $mod($this);
		}
		$this->modules[] = new BasicHelp($this);
	}

	public function autoLoad(CommandSender $c, string $world): bool {
		if($this->getServer()->isLevelLoaded($world)){
			return true;
		}
		if(!MPMU::access($c, "mw.cmd.world.load")){
			return false;
		}
		if(!$this->getServer()->isLevelGenerated($world)){
			$c->sendMessage(mc::_("[MW] No world with the name %1% exists!", $world));

			return false;
		}
		$this->getServer()->loadLevel($world);

		return $this->getServer()->isLevelLoaded($world);
	}

	//////////////////////////////////////////////////////////////////////
	//
	// Command dispatcher
	//
	//////////////////////////////////////////////////////////////////////
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		return $this->dispatchSCmd($sender, $cmd, $args);
	}
}
