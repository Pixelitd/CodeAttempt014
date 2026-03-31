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

class AddWord extends PluginCommand implements CommandExecutor {
    
    /** @var TextFilter */
    private $plugin;

	public function __construct(TextFilter $plugin){
        $this->plugin = $plugin;
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) : bool {
		if($sender->hasPermission("textfilter.commands.addword")){
			if(isset($args[0])){
				$args[0] = strtolower($args[0]);
				if($this->plugin->wordExists($args[0])){
				    $sender->sendMessage("Word already added.");
				}else{
					$this->plugin->addWord($args[0]);
					$sender->sendMessage("Word added!");
				}
			}else{
			    $sender->sendMessage("Usage: /addword <word>");
			}
		}else{
		    $sender->sendMessage("§cYou don't have permissions to use this command");
		}
		return true;
    }
}