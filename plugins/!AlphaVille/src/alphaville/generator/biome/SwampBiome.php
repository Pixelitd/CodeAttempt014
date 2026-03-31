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

use pocketmine\level\generator\biome\Biome;
use pocketmine\level\generator\normal\biome\SwampBiome;
use pocketmine\level\generator\populator\Flower;
use pocketmine\level\generator\populator\LilyPad;

use pocketmine\block\Block;
use pocketmine\block\Flower as FlowerBlock;

use alphaville\generator\populator\trees\Tree;

class SwampBiome extends SwampBiome implements Mountainable{

	public function __construct(){
		parent::__construct();
        $this->clearPopulators();
        
        $trees = new Tree(3);
		$trees->setBaseAmount(1);
		$this->addPopulator($trees);
        
		$flower = new Flower();
		$flower->setBaseAmount(8);
		$flower->addType([Block::RED_FLOWER, FlowerBlock::TYPE_BLUE_ORCHID]);
		$this->addPopulator($flower);
        
		$lilypad = new LilyPad();
		$lilypad->setBaseAmount(4);
		$this->addPopulator($lilypad);
        
		$this->setElevation(62, 63);
        
		$this->temperature = 0.8;
		$this->rainfall = 0.9;
	}

	public function getId(): Int{
		return Biome::SWAMP;
	}
    
	public function getName() : String{
		return "RiverBiome - Alpha Ville";
	}
}