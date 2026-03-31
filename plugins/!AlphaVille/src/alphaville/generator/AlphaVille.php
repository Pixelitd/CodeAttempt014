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

use pocketmine\block\Block;
use pocketmine\block\CoalOre;
use pocketmine\block\DiamondOre;
use pocketmine\block\Dirt;
use pocketmine\block\GoldOre;
use pocketmine\block\Gravel;
use pocketmine\block\IronOre;
use pocketmine\block\LapisOre;
use pocketmine\block\RedstoneOre;

use pocketmine\level\ChunkManager;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\noise\Simplex;
use pocketmine\level\generator\object\OreType;
use pocketmine\level\generator\populator\Cave;
use pocketmine\level\generator\populator\GroundCover;
use pocketmine\level\generator\populator\Ore;
use pocketmine\level\Level;

use pocketmine\math\Vector3;

use pocketmine\utils\Random;

use alphaville\generator\BiomeSelector;

use alphaville\generator\biome\ForestBiome;
use alphaville\generator\biome\BirchForestBiome;
use alphaville\generator\biome\MountainsBiome;
use alphaville\generator\biome\SmallMountainsBiome;
use alphaville\generator\biome\TaigaBiome;
use alphaville\generator\biome\RiverBiome;
use alphaville\generator\biome\SwampBiome;

class AlphaVille extends Generator{
   
    
    protected $selector;
	protected $level;
	protected $random;
	protected $populators = [];
	protected $generationPopulators = [];
  
    public static $biomes = [];
	public static $biomeById = [];
	public static $levels = [];
    
    private $noiseBase;
    private $noiseSeaFloor;
    private $noiseLand;
    private $noiseMountains;
    private $noiseBaseGround;
    private $noiseRiver;
    
	private $heightOffset;
	private $seaHeight = 62;
	private $seaFloorHeight = 48;
	private $beathStartHeight = 60;
	private $beathStopHeight = 64;
	private $seaFloorGenerateRange = 5;
	private $landHeightRange = 18;
	private $mountainHeight = 13; 
	private $basegroundHeight = 3;
    
    private static $GAUSSIAN_KERNEL = null;
    private static $SMOOTH_SIZE = 2;
    
    protected $bedrockDepth = 5;
    
    public function pickBiome($x, $z){
		$hash = $x * 2345803 ^ $z * 9236449 ^ $this->level->getSeed();
		$hash *= $hash + 223;
        
		$xNoise = $hash >> 20 & 3;
		$zNoise = $hash >> 22 & 3;
        
		if($xNoise == 3){
           $xNoise = 1;
		}
		if($zNoise == 3){
           $zNoise = 1;
		}
		return $this->selector->pickBiome($x + $xNoise - 1, $z + $zNoise - 1);
	}
    
    public function init(ChunkManager $level, Random $random){
		$this->level = $level;
		$this->random = $random;
        
        self::$levels[] = $level;
        
		$this->random->setSeed($this->level->getSeed());
        
		$this->noiseSeaFloor = new Simplex($this->random, 1, 1 / 8, 1 / 64);
		$this->noiseLand = new Simplex($this->random, 2, 1 / 8, 1 / 512);
		$this->noiseMountains = new Simplex($this->random, 4, 1, 1 / 500);
		$this->noiseBaseGround = new Simplex($this->random, 4, 1 / 4, 1 / 64);
		$this->noiseRiver = new Simplex($this->random, 2, 1, 1 / 512);
        
		$this->random->setSeed($this->level->getSeed());

		$this->heightOffset = $random->nextRange(-5, 3);

		$this->registerBiome(Biome::getBiome(Biome::OCEAN));
		$this->registerBiome(Biome::getBiome(Biome::PLAINS));
		$this->registerBiome(Biome::getBiome(Biome::DESERT));
		$this->registerBiome(new MountainsBiome());
		$this->registerBiome(new ForestBiome());
		$this->registerBiome(new BirchForestBiome());
		$this->registerBiome(new SwampBiome());
		$this->registerBiome(new RiverBiome());
		$this->registerBiome(Biome::getBiome(Biome::ICE_PLAINS));
		$this->registerBiome(new SmallMountainsBiome());
		$this->registerBiome(new TaigaBiome());  

		$this->selector = new BiomeSelector($random, [self::class, "getBiome"], Biome::getBiome(Biome::OCEAN));
        
		foreach(self::$biomes as $rain){
			    foreach($rain as $biome){
				        $this->selector->addBiome($biome);
			    }
		}
		
		$this->selector->recalculate();

		$cover = new GroundCover();
		$this->generationPopulators[] = $cover;

		$cave = new Cave();
		$this->generationPopulators[] = $cave;

		$ores = new Ore();
		$ores->setOreTypes([
			#new OreType(new CoalOre(), 20, 16, 0, 128),
			#new OreType(new IronOre(), 20, 8, 0, 64),
			#new OreType(new RedstoneOre(), 8, 7, 0, 16),
			#new OreType(new LapisOre(), 1, 6, 0, 32),
			#new OreType(new GoldOre(), 2, 8, 0, 32),
			#new OreType(new DiamondOre(), 1, 7, 0, 16),
			new OreType(new Dirt(), 20, 32, 0, 128),
			new OreType(new Gravel(), 10, 16, 0, 128)
		]);
		$this->populators[] = $ores;
	}


	public function generateChunk($chunkX, $chunkZ){
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());

		$seaFloorNoise = Generator::getFastNoise2D($this->noiseSeaFloor, 16, 16, 4, $chunkX * 16, 0, $chunkZ * 16);
		$landNoise = Generator::getFastNoise2D($this->noiseLand, 16, 16, 4, $chunkX * 16, 0, $chunkZ * 16);
		$mountainNoise = Generator::getFastNoise2D($this->noiseMountains, 16, 16, 4, $chunkX * 16, 0, $chunkZ * 16);
		$baseNoise = Generator::getFastNoise2D($this->noiseBaseGround, 16, 16, 4, $chunkX * 16, 0, $chunkZ * 16);
		$riverNoise = Generator::getFastNoise2D($this->noiseRiver, 16, 16, 4, $chunkX * 16, 0, $chunkZ * 16);

		$chunk = $this->level->getChunk($chunkX, $chunkZ);

		for($genx = 0; $genx < 16; $genx++){
			for($genz = 0; $genz < 16; $genz++){
                
                $biome = $this->pickBiome($chunkX * 16 + $genx, $chunkZ * 16 + $genz);
				$chunk->setBiomeId($genx, $genz, $biome->getId());
                
                for($sx = - self::$SMOOTH_SIZE; $sx <= self::$SMOOTH_SIZE; $sx++){
					for($sz = - self::$SMOOTH_SIZE; $sz <= self::$SMOOTH_SIZE; $sz++){
						
						$weight = self::$GAUSSIAN_KERNEL[$sx + self::$SMOOTH_SIZE] [$sz + self::$SMOOTH_SIZE];
						
						if($sx === 0 and $sz === 0){
				           $adjacent = $biome;
						}else{
				           $index = Level::chunkHash($chunkX * 16 + $genx + $sx, $chunkZ * 16 + $genz + $sz);
				           if(isset($biomeCache[$index])){
				              $adjacent = $biomeCache[$index];
				           }else{
				              $biomeCache[$index] = $adjacent = $this->pickBiome($chunkX * 16 + $genx + $sx, $chunkZ * 16 + $genz + $sz);
                           }
                        }
                    }
                }
                
				$canBaseGround = false;
				$canRiver = true;

				$landHeightNoise = $landNoise[$genx][$genz] + 1;
				$landHeightNoise *= 2.956;
				$landHeightNoise = $landHeightNoise * $landHeightNoise;
				$landHeightNoise = $landHeightNoise - 0.6;
				$landHeightNoise = $landHeightNoise > 0 ? $landHeightNoise : 0;

				$mountainHeightGenerate = $mountainNoise[$genx][$genz] - 0.2;
				$mountainHeightGenerate = $mountainHeightGenerate > 0 ? $mountainHeightGenerate : 0;
				$mountainGenerate = (int) ($this->mountainHeight * $mountainHeightGenerate);

				$landHeightGenerate = (int) ($this->landHeightRange * $landHeightNoise);
				if($landHeightGenerate > $this->landHeightRange){
					if($landHeightGenerate > $this->landHeightRange){
						$canBaseGround = true;
					}
					$landHeightGenerate = $this->landHeightRange;
				}

				$genyHeight = $this->seaFloorHeight + $landHeightGenerate;
				$genyHeight += $mountainGenerate;

				if($genyHeight < $this->beathStartHeight){
					if($genyHeight < $this->beathStartHeight - 5){
						$genyHeight += (int) ($this->seaFloorGenerateRange * $seaFloorNoise[$genx][$genz]);
					}
					$biome = Biome::getBiome(Biome::OCEAN);
					if($genyHeight < $this->seaFloorHeight - $this->seaFloorGenerateRange){
						$genyHeight = $this->seaFloorHeight;
					}
					$canRiver = false;
				}else if($genyHeight <= $this->beathStopHeight && $genyHeight >= $this->beathStartHeight){
					$biome = Biome::getBiome(Biome::DESERT);
				}else{
					$biome = $this->pickBiome($chunkX * 16 + $genx, $chunkZ * 16 + $genz);
					if($canBaseGround){
						$baseGroundHeight = (int) ($this->landHeightRange * $landHeightNoise) - $this->landHeightRange;
						$baseGroundHeight2 = (int) ($this->basegroundHeight * ($baseNoise[$genx][$genz] + 1));
						if($baseGroundHeight2 > $baseGroundHeight) $baseGroundHeight2 = $baseGroundHeight;
						if($baseGroundHeight2 > $mountainGenerate)
							$baseGroundHeight2 = $baseGroundHeight2 - $mountainGenerate;
						else $baseGroundHeight2 = 0;
						$genyHeight += $baseGroundHeight2;
					}
				}
				if($canRiver && $genyHeight <= $this->seaHeight - 5){
					$canRiver = false;
				}
				if($canRiver){
					$riverGenerate = $riverNoise[$genx][$genz];
					if($riverGenerate > -0.25 && $riverGenerate < 0.25){
						$riverGenerate = $riverGenerate > 0 ? $riverGenerate : -$riverGenerate;
						$riverGenerate = 0.25 - $riverGenerate;
						$riverGenerate = $riverGenerate * $riverGenerate * 4;
						$riverGenerate = $riverGenerate - 0.0000001;
						$riverGenerate = $riverGenerate > 0 ? $riverGenerate : 0;
						$genyHeight -= $riverGenerate * 64;
						if($genyHeight < $this->seaHeight){
							$biome = new RiverBiome();
							if($genyHeight <= $this->seaHeight - 8){
								$genyHeight1 = $this->seaHeight - 9 + (int) ($this->basegroundHeight * ($baseNoise[$genx][$genz] + 1));
								$genyHeight2 = $genyHeight < $this->seaHeight - 7 ? $this->seaHeight - 7 : $genyHeight;
								$genyHeight = $genyHeight1 > $genyHeight2 ? $genyHeight1 : $genyHeight2;
							}
						}
					}
				}
				$chunk->setBiomeId($genx, $genz, $biome->getId());
                if(!version_compare(\pocketmine\API_VERSION, "3.0.0-ALPHA10") >= 0){
                   if($biome instanceof SmallMountainsBiome){ #Biome Color RGB
                      $chunk->setBiomeColor($genx, $genz, 177, 210, 132);
                   }elseif($biome instanceof ForestBiome){
                      $chunk->setBiomeColor($genx, $genz, 146, 188, 89);
                   }elseif($biome instanceof BirchForestBiome){
                      $chunk->setBiomeColor($genx, $genz, 246, 246, 246); 
                   }elseif($biome instanceof RiverBiome){
                      $chunk->setBiomeColor($genx, $genz, 149, 249, 255);
                   }else{
				     $biomeColor = $biome->getColor();             
				     $chunk->setBiomeColor($genx, $genz, ($biomeColor >> 16), ($biomeColor >> 8) & 0xff, ($biomeColor & 0xff));
                   }
                }
				$generateHeight = $genyHeight > $this->seaHeight ? $genyHeight : $this->seaHeight;
				for($geny = 0; $geny <= $generateHeight; $geny++){
					if($geny <= $this->bedrockDepth && ($geny == 0 or $this->random->nextRange(1, 5) == 1)){
						$chunk->setBlockId($genx, $geny, $genz, Block::BEDROCK);
					}elseif($geny > $genyHeight){
						if(($biome->getId() == Biome::ICE_PLAINS or $biome->getId() == Biome::TAIGA) and $geny == $this->seaHeight){
							$chunk->setBlockId($genx, $geny, $genz, Block::ICE);
						}else{
							$chunk->setBlockId($genx, $geny, $genz, Block::STILL_WATER);
						}
					}else{
						$chunk->setBlockId($genx, $geny, $genz, Block::STONE);
					}
				}
			}
		}
		foreach($this->generationPopulators as $populator){
			    $populator->populate($this->level, $chunkX, $chunkZ, $this->random);
        }
	}
    
    public function getSpawn(): Vector3{
		return new Vector3(127.5, 128, 127.5);
	}
    
    public function getSafeSpawn(){
        return new Vector3(127.5, $this->getHighestWorkableBlock(127, 127), 127.5);
    }

	public function populateChunk($chunkX, $chunkZ){
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
		foreach($this->populators as $populator){
			    $populator->populate($this->level, $chunkX, $chunkZ, $this->random);
		}	
		$chunk = $this->level->getChunk($chunkX, $chunkZ);
		$biome = self::getBiomeById($chunk->getBiomeId(7, 7));
		$biome->populateChunk($this->level, $chunkX, $chunkZ, $this->random);
	}
    
    public static function registerBiome(Biome $biome): Bool{
		foreach(self::$levels as $level) 
                if(isset($level->selector)) 
                   $level->selector->addBiome($biome);
		           if(!isset(self::$biomes[(string) $biome->getRainfall()])) 
                      self::$biomes[(string) $biome->getRainfall()] = [];
		              self::$biomes[(string) $biome->getRainfall()] [(string) $biome->getTemperature()] = $biome;
		              ksort(self::$biomes[(string) $biome->getRainfall()]);
		              ksort(self::$biomes);
		              self::$biomeById[$biome->getId()] = $biome;
		              return true;
	}
    
    public static function getBiome($temperature, $rainfall){
		$ret = null;
		if(!isset(self::$biomes[(string) round($rainfall, 1)])){
           while(!isset(self::$biomes[(string) round($rainfall, 1)])){
				 if(abs($rainfall - round($rainfall, 1)) >= 0.05)
					$rainfall += 0.1;
				 if(abs($rainfall - round($rainfall, 1)) < 0.05)
					$rainfall -= 0.1;
				 if(round($rainfall, 1) < 0)
					$rainfall = 0;
				 if(round($rainfall, 1) >= 0.9)
					$rainfall = 0.9;
           }
        }
		$b = self::$biomes[(string) round($rainfall, 1)];
		foreach($b as $t => $biome){
			if($temperature <= (float) $t){
               $ret = $biome;
               break;
			}
		}
		if(is_string($ret)) {
           $ret = new $ret();
		}
		return $ret;
	}
    
    public function getBiomeById(int $id): Biome{
		return self::$biomeById[$id] ?? self::$biomeById[Biome::OCEAN];
	}
    
    protected function getHighestWorkableBlock($x, $z){
		for($y = Level::Y_MAX - 1; $y > 0; -- $y){
			$b = $this->level->getBlockIdAt($x, $y, $z);
			if($b === Block::DIRT or $b === Block::GRASS or $b === Block::PODZOL){
               break;
			}elseif($b !== 0 and $b !== Block::SNOW_LAYER){
				    return - 1;
			}
		}
		return ++$y;
	}
    
    public function __construct(array $options = []) {
        if (self::$GAUSSIAN_KERNEL === null) {
            self::generateKernel();
        }
    }
    
    private static function generateKernel() {
        self::$GAUSSIAN_KERNEL = [];
        $bellSize = 1 / self::$SMOOTH_SIZE;
        $bellHeight = 2 * self::$SMOOTH_SIZE;
        for ($sx = -self::$SMOOTH_SIZE; $sx <= self::$SMOOTH_SIZE; ++$sx) {
            self::$GAUSSIAN_KERNEL[$sx + self::$SMOOTH_SIZE] = [];
            for ($sz = -self::$SMOOTH_SIZE; $sz <= self::$SMOOTH_SIZE; ++$sz) {
                $bx = $bellSize * $sx;
                $bz = $bellSize * $sz;
                self::$GAUSSIAN_KERNEL[$sx + self::$SMOOTH_SIZE][$sz + self::$SMOOTH_SIZE] = $bellHeight * exp(-($bx * $bx + $bz * $bz) / 2);
            }
        }
    }
    
    public function getName(): string{
        return "AlphaVille";
    }
    
    public function getSettings(): array{
        return [];
    }
}