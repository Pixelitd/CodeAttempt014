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

class Unmute extends PluginCommand implements CommandExecutor {
    
    /** @var TextFilter */
    private $plugin;

    public function __construct(TextFilter $plugin){
        $this->plugin = $plugin;
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) : bool {
    	if($sender->hasPermission("textfilter.commands.unmute")){
    		if(isset($args[0])){
    			$args[0] = strtolower($args[0]);
    			if($this->plugin->getServer()->getPlayer($args[0]) != null){
    				$player = $args[0];
    				if($this->plugin->isMuted($player)){
    				    $this->plugin->unmutePlayer($player);
    				    $sender->sendMessage("§aYou unmuted &b" . $player);
    					if($this->plugin->cfg["mute"]["log-unmute"]){
    					    $this->plugin->getServer()->getPlayer($player)->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("unmuted"), array("PLAYER" => $sender->getName())));
    					}
    				}else{
    				    $sender->sendMessage("§cPlayer " . $player . " is not muted!");
    				}
    			}else{
    			    $sender->sendMessage("§cPlayer not found!");
    			}
    		}else{
    		    $sender->sendMessage("Usage: /unmute <player>");
    		}
    	}else{
    	    $sender->sendMessage("§cYou don't have permissions to use this command");
    	}
    	return true;
    }
}