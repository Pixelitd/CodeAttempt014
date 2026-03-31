<?php
/**
 ** OVERVIEW:Basic Usage
 **
 ** COMMANDS
 **
 ** * default : Sets the default world
 **   usage: /mw **default** _<world>_
 **
 **   Teleports you to another world.  If _player_ is specified, that
 **   player will be teleported.
 **/
declare(strict_types=1);
namespace aliuly\manyworlds;

use aliuly\manyworlds\common\BasicCli;
use aliuly\manyworlds\common\BasicPlugin;
use aliuly\manyworlds\common\mc;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

final class MwDefault extends BasicCli{
	public function __construct(BasicPlugin $owner){
		parent::__construct($owner);
		$this->enableSCmd("default", ["usage" => mc::_("<world: string>"),
			"help" => mc::_("Changes default world"),
			"permission" => "mw.cmd.default"]);
	}

	public function onSCommand(CommandSender $c, Command $cc, string $scmd, $data, array $args): bool {
		if(count($args) === 0){
			return false;
		}
		$wname = implode(" ", $args);
		$old = $this->owner->getServer()->getConfigString("level-name");
		if($old === $wname){
			$c->sendMessage(TextFormat::RED . mc::_("No change"));

			return true;
		}
		if(!$this->owner->autoLoad($c, $wname)){
			$c->sendMessage(TextFormat::RED . mc::_("[MW] Unable to load %1%", $wname));
			$c->sendMessage(TextFormat::RED . mc::_("Change failed!"));

			return true;
		}
		$level = $this->owner->getServer()->getLevelByName($wname);
		if($level === null){
			$c->sendMessage(TextFormat::RED . mc::_("Error GetLevelByName %1%"));

			return true;
		}
		$this->owner->getServer()->setConfigString("level-name", $wname);
		$this->owner->getServer()->setDefaultLevel($level);
		$c->sendMessage(TextFormat::BLUE . mc::_("Default world changed to %1%", $wname));

		return true;
	}
}
