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

class Commands extends PluginCommand implements CommandExecutor {
    
    /** @var TextFilter */
    private $plugin;

    public function __construct(TextFilter $plugin){
        $this->plugin = $plugin;
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) : bool {
        if(isset($args[0])){
    		$args[0] = strtolower($args[0]);
    		switch($args[0]){
    		    case "help":
			        goto help;
			    case "info":
			        if($sender->hasPermission("textfilter.commands.info")){
			            $sender->sendMessage("§eTextFilter §av" . $this->plugin->getDescription()->getVersion() . "§e developed by §aPixelited, EvolSoft");
			            $sender->sendMessage("§eWebsite §a" . $this->plugin->getDescription()->getWebsite());
			            break;
			        }
			        $sender->sendMessage("§cYou don't have permissions to use this command");
			        break;
			    case "reload":
			        if($sender->hasPermission("textfilter.commands.reload")){
			            $this->plugin->reload();
			            $sender->sendMessage("§aConfiguration Reloaded.");
			            break;
			        }
			        $sender->sendMessage("§cYou don't have permissions to use this command");
			        break;
			    default:
			        if($sender->hasPermission("textfilter")){
			            $sender->sendMessage("§cSubcommand §a" . $args[0] . " §cnot found. Use §a/cc help &cto show available commands.");
			            break;
			        }
			        $sender->sendMessage("§cYou don't have permissions to use this command");
			        break; 
			}
			return true;
		}else{
		    help:
    		if($sender->hasPermission("textfilter.commands.help")){
    		    $sender->sendMessage("§c== §eAvailable Commands §c==");
    		    $sender->sendMessage("§a/cc info §c->§e Show info about this plugin");
    		    $sender->sendMessage("§a/cc help §c->§e Show help about this plugin");
    		    $sender->sendMessage("§a/cc reload §c->§e Reload the config");
    		    $sender->sendMessage("§a/addword §c->§e Add a censored word");
    		    $sender->sendMessage("§a/removeword §c->§e Remove a censored word");
    		    $sender->sendMessage("§a/mute §c->§e Mute a player");
    		    $sender->sendMessage("§a/unmute §c->§e Unmute a player");
    		    $sender->sendMessage("§a/listmuted §c->§e Get the list of muted players");
    			return true;
    		}
    		$sender->sendMessage("§cYou don't have permissions to use this command");
    		return true;
    	}
    }
}