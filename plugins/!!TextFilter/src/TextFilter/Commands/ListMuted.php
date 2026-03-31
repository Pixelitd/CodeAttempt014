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

class ListMuted extends PluginCommand implements CommandExecutor {
    
    /** @var TextFilter */
    private $plugin;
    
    public function __construct(TextFilter $plugin){
        $this->plugin = $plugin;
    }
    
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) : bool {
        if($sender->hasPermission("textfilter.commands.listmuted")){
            $mlist = $this->plugin->muted->getAll();
            $sender->sendMessage("§bMuted players:");
            foreach($mlist as $muted => $time){
                if($this->plugin->isMuted($muted)){
                    $sender->sendMessage("§a" . $muted . "§e (expires after " . $this->plugin->formatInterval($time) . ")");
                }
            }
            if(($mlist = $this->plugin->muted->getAll()) == null){
                $sender->sendMessage("§aNo players are muted.");
            }
        }else{
            $sender->sendMessage("§cYou don't have permissions to use this command");
        }
        return true;
    }
}