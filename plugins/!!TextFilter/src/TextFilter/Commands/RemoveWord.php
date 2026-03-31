<?php

/*
 * ChatCensor v2.3 by EvolSoft
 * Developer: Flavius12
 * Website: https://www.evolsoft.tk
 * Copyright (C) 2014-2018 EvolSoft
 * Licensed under MIT (https://github.com/EvolSoft/ChatCensor/blob/master/LICENSE)
 */

namespace TextFilter\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\TextFormat;

use TextFilter\TextFilter;

class RemoveWord extends PluginCommand implements CommandExecutor {
    
    /** @var TextFilter*/
    private $plugin;

    public function __construct(TextFilter $plugin){
        $this->plugin = $plugin;
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) : bool {
		if($sender->hasPermission("textfilter.commands.removeword")){
			if(isset($args[0])){
				$args[0] = strtolower($args[0]);
				if($this->plugin->wordExists($args[0])){
					$this->plugin->removeWord($args[0]);
					$sender->sendMessage("§aWord removed!");
				}else{
				    $sender->sendMessage("§cWord not found.");
				}
			}else{
			    $sender->sendMessage("Usage: /removeword <word>");
			}
		}else{
		    $sender->sendMessage("&cYou don't have permissions to use this command");
		}
		return true;
    }
}