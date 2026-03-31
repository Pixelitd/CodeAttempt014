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

namespace alphaville\generator;

use pocketmine\level\generator\biome\Biome;
use pocketmine\level\generator\biome\BiomeSelector;
use pocketmine\level\generator\noise\Simplex;

use pocketmine\utils\Random;

class BiomeSelector extends BiomeSelector{
	
	protected $fallback;
	protected $temperature;
	protected $rainfall;
	protected $biomes = [];
	protected $lookup;

	public function __construct(Random $random, callable $lookup, Biome $fallback){
		parent::__construct($random, $lookup, $fallback);
		$this->fallback = $fallback;
		$this->lookup = $lookup;
		$this->temperature = new Simplex($random, 2, 1 / 16, 1 / 512);
		$this->rainfall = new Simplex($random, 2, 1 / 16, 1 / 512);
	}

    public function recalculate(){
		#$this->map = new \SplFixedArray(64 * 64);
		#for($i = 0; $i < 64; ++$i){
			#for($j = 0; $j < 64; ++$j){
				#$this->map[$i + ($j << 6)] = call_user_func($this->lookup, $i / 63, $j / 63);
			#}
		#}
	}
    
	public function addBiome(Biome $biome){
		$this->biomes[$biome->getId()] = $biome;
	}
    
	public function getTemperature($x, $z){
		return ($this->temperature->noise2D($x, $z, true) + 1) / 2;
	}
    
	public function getRainfall($x, $z){
		return ($this->rainfall->noise2D($x, $z, true) + 1) / 2;
	}

	public function pickBiome($x, $z) {
		$temperature = ($this->getTemperature($x, $z));
		$rainfall = ($this->getRainfall($x, $z));
		$biomeId = AlphaVille::getBiome($temperature, $rainfall);
		$b = (($biomeId instanceof Biome) ? $biomeId : ($this->biomes[$biomeId] ?? $this->fallback));
		return $b;
	}
}