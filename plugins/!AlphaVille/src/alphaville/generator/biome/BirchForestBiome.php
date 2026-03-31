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
use pocketmine\level\generator\normal\biome\ForestBiome as GrassyBiome;
use pocketmine\level\generator\biome\Biome;

use alphaville\generator\populator\trees\Tree;

class BirchForestBiome extends GrassyBiome implements Mountainable{
            
	public function __construct(){
		parent::__construct();
        $this->clearPopulators();
        
		$trees = new Tree(1);
		$trees->setBaseAmount(5);
		$this->addPopulator($trees);
        
		$tallGrass = new TallGrass();
		$tallGrass->setBaseAmount(3);
		$this->addPopulator($tallGrass);
        
		$this->setElevation(63, 81);

		$this->temperature = 0.6;
		$this->rainfall = 0.5;

	}

    public function getId() : Int{
        return Biome::BIRCH_FOREST;
    }
    
	public function getName() : String{
		return "BirchForest - Alpha Ville";
	}
}