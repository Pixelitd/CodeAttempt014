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

use pocketmine\level\generator\populator\TallGrass;
use pocketmine\level\generator\normal\biome\SmallMountainsBiome as GrassyBiome;
use pocketmine\level\generator\biome\Biome;

use alphaville\generator\populator\trees\Tree;

class SmallMountainsBiome extends GrassyBiome implements Mountainable{
            
	public function __construct(){
		parent::__construct();
        $this->clearPopulators();
        
		$trees = new Tree(0);
		$trees->setBaseAmount(1);
		$this->addPopulator($trees);
      
  
		$this->setElevation(63, 97);

	}

    public function getId() : Int{
        return Biome::SMALL_MOUNTAINS;
    }
    
	public function getName() : String{
		return "Small Moutains - Alpha Ville";
	}
}