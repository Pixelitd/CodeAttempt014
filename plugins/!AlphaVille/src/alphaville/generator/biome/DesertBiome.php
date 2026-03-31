<?php

/**
 *     _      _           _              __     __  _   _   _        
 *    / \    | |  _ __   | |__     __ _  \ \   / / (_) | | | |   ___ 
 *   / _ \   | | | '_ \  | '_ \   / _` |  \ \ / /  | | | | | |  / _ \
 *  / ___ \  | | | |_) | | | | | | (_| |   \ V /   | | | | | | |  __/
 * /_/   \_\ |_| | .__/  |_| |_|  \__,_|    \_/    |_| |_| |_|  \___|
 *
 * This plugin is about copyright by DayKoala
 * Twitter: https://twitter.com/DayKoala
 *
 */

namespace alphaville\generator\biome;

use pocketmine\level\generator\normal\biome\DesertBiome as SandyBiome;

use alphaville\generator\populator\trees\Tree;

class DesertBiome extends SandyBiome{
    
	public $type;
    
	public function __construct($type = 0){
		parent::__construct();
        
		$this->setElevation(63, 74);
		$this->temperature = 2;
		$this->rainfall = 0;
	}
    
    public function getId() : Int{
        return 403;
    }
    
	public function getName() : String{
		return "DesertBiome - Alpha Ville";
	}
}