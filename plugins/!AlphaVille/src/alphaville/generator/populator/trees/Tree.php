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

namespace alphaville\generator\populator\trees;

use pocketmine\block\Block;

use pocketmine\level\ChunkManager;

use pocketmine\utils\Random;

use alphaville\generator\populator\AmountPopulator;

class Tree extends AmountPopulator{

    static $types = ["alphaville\\generator\\populator\\trees\\ForestTree",
                     "alphaville\\generator\\populator\\trees\\BirchForestTree",
                     "alphaville\\generator\\populator\\trees\\TaigaForestTree",
                     "alphaville\\generator\\populator\\trees\\SwampTree"];
    
	protected $level;
	protected $type;
    
	public function __construct($type = 0){
		$this->type = $type;
        self::$types = ["alphaville\\generator\\populator\\trees\\ForestTree",
                     "alphaville\\generator\\populator\\trees\\BirchForestTree",
                     "alphaville\\generator\\populator\\trees\\TaigaForestTree",
                     "alphaville\\generator\\populator\\trees\\SwampTree"];
	}
    
	public function populate(ChunkManager $level, $chunkX, $chunkZ, Random $random){
		$this->level = $level;
		$amount = $random->nextRange(0, $this->randomAmount + 1) + $this->baseAmount;
        
		for($i = 0; $i < $amount; ++$i){
			$x = $random->nextRange($chunkX << 4, ($chunkX << 4) + 15);
			$z = $random->nextRange($chunkZ << 4, ($chunkZ << 4) + 15);
			$y = $this->getHighestWorkableBlock($x, $z);
			if($y === -1){
               continue;
			}
            $treeC = self::$types[$this->type];
			$tree = new $treeC();
			$tree->placeObject($this->level, $x, $y, $z, $random);
		}
	}
    
	private function getHighestWorkableBlock($x, $z){
		for($y = 127; $y > 0; --$y){
			$b = $this->level->getBlockIdAt($x, $y, $z);
			if($b === Block::DIRT or $b === Block::GRASS or $b === Block::PODZOL){
				break;
			}elseif($b !== 0 and $b !== Block::SNOW_LAYER){
				return -1;
			}
		}
		return ++$y;
	}
}