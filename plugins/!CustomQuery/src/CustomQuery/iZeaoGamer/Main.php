<?php
namespace CustomQuery\iZeaoGamer;

use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use CustomQuery\iZeaoGamer\commands\CustomQueryCommand;


class Main extends PluginBase implements Listener{
    
    /*
     * This function is when plugins are enabling.
     * @return void
     */
    public function onEnable(){
            $this->saveDefaultConfig();
            $config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
            $this->getServer()->getPluginManager()->registerEvents($this, $this);
        }
    //}
    /*
    * An event function, allowing you to edit parts of the Query system for your servers.
    */
    public function onQuery(QueryRegenerateEvent $event){
        if($this->getConfig()->get("show-plugins") === true){
            $event->setListPlugins(true);
        }
        if($this->getConfig()->get("fake-plugins") === true){
            $this->plugins = $this->getConfig()->get("set-plugins");
            foreach($this->plugins as $plugins){
            $event->setPlugins([$plugins]);
    }
}
    if($this->getConfig()->get("infinity-slots") === true){
        $event->setMaxPlayerCount(($event->getPlayerCount() + 1));
}
        if($this->getConfig()->get("fake-slots") === true){
            $minPlayerCount = $this->getConfig()->get("min-slots");
            $maxPlayerCount = $this->getConfig()->get("max-slots");
            $event->setPlayerCount((mt_rand($minPlayerCount, $maxPlayerCount)));
        }
    }
}
