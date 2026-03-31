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

use pocketmine\block\Block;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\generator\normal\biome\DesertBiome as SandyBiome;

class RiverBiome extends SandyBiome{

	public function __construct(){
		$this->clearPopulators();
		
		$this->setGroundCover([ 
				Block::get(Block::SAND, 0),
				Block::get(Block::SAND, 0),
				Block::get(Block::SAND, 0),
				Block::get(Block::SANDSTONE, 0),
				Block::get(Block::SANDSTONE, 0),
				Block::get(Block::SANDSTONE, 0),
				Block::get(Block::SANDSTONE, 0),
				Block::get(Block::SANDSTONE, 0),
				Block::get(Block::SANDSTONE, 0),
				Block::get(Block::SANDSTONE, 0),
				Block::get(Block::SANDSTONE, 0) 
		]);
		
		$this->setElevation(60, 60);
		
		$this->temperature = 0.5;
		$this->rainfall = 0.7;
	}

	public function getId(): Int{
		return Biome::RIVER;
	}
    
	public function getName() : String{
		return "RiverBiome - Alpha Ville";
	}
}