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

class Mute extends PluginCommand implements CommandExecutor {

    /** @var TextFilter */
    private $plugin;
    
    public function __construct(TextFilter $plugin){
        $this->plugin = $plugin;
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) : bool {
		if($sender->hasPermission("textfilter.commands.mute")){
			if(isset($args[0])){
				$args[0] = strtolower($args[0]);
				if($this->plugin->getServer()->getPlayer($args[0]) != null){
					$player = $args[0];
					if($this->plugin->isMuted($player)){
					    $sender->sendMessage("&cPlayer " . $player . " is already muted!");
					    return true;
					}
					$time = $args;
					unset($time[0]);
					$time = implode($time);
					if($time == null){
					    $time = $this->plugin->cfg["mute"]["time"];
					}
					$time = strtr($time, array("s" => "second", "m" => "minute", "h" => "hour", "d" => "day", "mth" => "month", "y" => "year"));
					$time = strtotime($time);
					if($time === false){
					    $sender->sendMessage("§cInvalid duration specified.");
					    return true;
					}else if($this->plugin->mutePlayer($player, $time)){
					    $sender->sendMessage("§aYou muted &b" . $player . "&a for &b" . $this->plugin->formatInterval($time) . "&a.");
					   if($this->plugin->cfg["mute"]["log-mute"]){
					       $this->plugin->getServer()->getPlayer($player)->sendMessage(TextFormat::colorize($this->plugin->replaceVars($this->plugin->getMessage("muted"), array("PLAYER" => $sender->getName(), "DURATION" => $this->plugin->formatInterval($time)))));
					   }
					}
				}else{
				    $sender->sendMessage("§cPlayer not found!");
				}
			}else{
			    $sender->sendMessage("Usage: /mute <player> [duration]");
			}
		}else{
		    $sender->sendMessage("§cYou don't have permissions to use this command");
		}
		return true;
    }
}