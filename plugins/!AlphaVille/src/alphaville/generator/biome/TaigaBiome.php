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

use pocketmine\level\generator\populator\MossStone;
use pocketmine\level\generator\normal\biome\TaigaBiome as SnowyBiome;
use pocketmine\level\generator\biome\Biome;

use alphaville\generator\populator\trees\Tree;

class TaigaBiome extends SnowyBiome implements Mountainable{
    
	public function __construct(){
		parent::__construct();
        $this->clearPopulators();
        
        $trees = new Tree(2);
		$trees->setBaseAmount(10);
		$this->addPopulator($trees);
        
		$mossStone = new MossStone();
		$mossStone->setBaseAmount(1);
		$this->addPopulator($mossStone);
        
		$this->setElevation(63, 81);
        
		$this->temperature = 0.5;
		$this->rainfall = 0.8;
        
		$this->setGroundCover([
			Block::get(Block::PODZOL, 0),
			Block::get(Block::DIRT, 0),
			Block::get(Block::DIRT, 0),
			Block::get(Block::DIRT, 0)
		]);
	}

    public function getId(): Int{
        return Biome::TAIGA;
    }
    
	public function getName(): String{
		return "TaigaBiome - Alpha Ville";
	}
}