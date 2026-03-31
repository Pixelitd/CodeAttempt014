<?php
    
namespace normal3\generator\biome;

use pocketmine\level\generator\populator\TallGrass;
use pocketmine\level\generator\normal\biome\GrassyBiome;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\generator\populator\Tree;

class PlainBiome extends GrassyBiome{
            
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
        return Biome::PLAINS;
    }
    
	public function getName() : String{
		return "Plains";
	}
    
    	public function getColor(){
		return 0x85bc56;
	}
}