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

namespace alphaville\generator\populator;

use pocketmine\level\generator\populator\Populator;

use pocketmine\utils\Random;

abstract class AmountPopulator extends Populator{
    
	protected $baseAmount = 0;
	protected $randomAmount = 0;
	
	public function setRandomAmount(Int $amount){
		$this->randomAmount = $amount;
	}
	
	public function setBaseAmount(Int $amount){
		$this->baseAmount = $amount;
	}
	
	public function getAmount(Random $random){
		return $this->baseAmount + $random->nextRange(0, $this->randomAmount + 1);
	}
	
	public function getBaseAmount(): Int{
		return $this->baseAmount;
	}
	
	public function getRandomAmount(): Int{
		return $this->randomAmount;
	}
}