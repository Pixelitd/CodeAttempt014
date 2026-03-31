<?php

namespace normal3;

use pocketmine\plugin\PluginBase;

use pocketmine\level\generator\Generator;

use normal3\generator\Normal3;

class Main extends PluginBase{

	public function onEnable(){  
        Generator::addGenerator(Normal3::class, "normal3");
    }
}