<?php
/**
 ** OVERVIEW:Basic Usage
 **
 ** COMMANDS
 **
 ** * generators : List available world generators
 **   usage: /mw **generators**
 **
 **   List registered world generators.
 **/
declare(strict_types=1);
namespace aliuly\manyworlds;

use aliuly\manyworlds\common\BasicCli;
use aliuly\manyworlds\common\BasicPlugin;
use aliuly\manyworlds\common\mc;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\generator\Generator;

final class MwGenLst extends BasicCli{
	public function __construct(BasicPlugin $owner){
		parent::__construct($owner);
		$this->enableSCmd("generators", ["usage" => "",
			"help" => mc::_("List world generators"),
			"permission" => "mw.cmd.world.create",
			"aliases" => ["gen", "genlst"]]);
	}

	public function onSCommand(CommandSender $c, Command $cc, string $scmd, $data, array $args): bool {
		if(count($args) != 0){
			return false;
		}

		$c->sendMessage(implode(", ", Generator::getGeneratorList()));
		return true;
	}
}
