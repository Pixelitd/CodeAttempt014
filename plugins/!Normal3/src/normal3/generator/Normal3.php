<?php

/*
Generator made by Pixelited. Based from Genisys generator
 * @format
 */

namespace normal3\generator;

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
use pocketmine\level\generator\biome\BiomeSelector;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\noise\Simplex;
use pocketmine\level\generator\object\OreType;
use pocketmine\level\generator\populator\Cave;
use pocketmine\level\generator\populator\GroundCover;
use pocketmine\level\generator\populator\Ore;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\level\generator\normal\Normal;

// These are custom classes that must be provided by your plugin
use normal3\generator\biome\ForestBiome;
use normal3\generator\biome\OceanBiome;
use normal3\generator\biome\PlainBiome;
use normal3\generator\biome\SwampBiome;
use normal3\populator\CavePopulator;
use normal3\populator\RavinePopulator;

class Normal3 extends Normal
{
  /** @var Simplex */
  private $noiseSeaFloor;
  /** @var Simplex */
  private $noiseLand;
  /** @var Simplex */
  private $noiseMountains;
  /** @var Simplex */
  private $noiseBaseGround;
  /** @var Simplex */
  private $noiseRiver;

  private $heightOffset;

  private $seaHeight = 62;
  private $seaFloorHeight = 48;
  private $beathStartHeight = 60;
  private $beathStopHeight = 64;
  protected $bedrockDepth = 5;
  private $seaFloorGenerateRange = 5;
  private $landHeightRange = 18; // 36 / 2
  private $mountainHeight = 13; // 26 / 2
  private $basegroundHeight = 3;

  public function __construct(array $options = [])
  {
    parent::__construct($options);
  }

  public function getName()
  {
    return "Normal3";
  }

  public function pickBiome($x, $z): Biome
  {
    $hash = ($x * 2345803) ^ ($z * 9236449) ^ $this->level->getSeed();
    $hash *= $hash + 223;
    $xNoise = ($hash >> 20) & 3;
    $zNoise = ($hash >> 22) & 3;
    if ($xNoise == 3) {
      $xNoise = 1;
    }
    if ($zNoise == 3) {
      $zNoise = 1;
    }
    return $this->selector->pickBiome($x + $xNoise - 1, $z + $zNoise - 1);
  }

  public function init(ChunkManager $level, Random $random)
  {
    parent::init($level, $random); // Call parent init first

    $this->noiseSeaFloor = new Simplex($this->random, 1, 1 / 8, 1 / 64);
    $this->noiseLand = new Simplex($this->random, 2, 1 / 8, 1 / 128);
    $this->noiseMountains = new Simplex($this->random, 3, 1, 1 / 150);
    $this->noiseBaseGround = new Simplex($this->random, 4, 5 / 5, 1 / 64);
    $this->noiseRiver = new Simplex($this->random, 2, 1, 1 / 512);

    $this->selector = new BiomeSelector(
      $this->random,
      function ($temperature, $rainfall) {
        if ($rainfall < 0.25) {
          if ($temperature < 0.7) {
            return Biome::OCEAN;
          } elseif ($temperature < 0.85) {
            return Biome::RIVER;
          } else {
            return Biome::SWAMP;
          }
        } elseif ($rainfall < 0.6) {
          if ($temperature < 0.25) {
            return Biome::ICE_PLAINS;
          } elseif ($temperature < 0.75) {
            return Biome::PLAINS;
          } else {
            return Biome::DESERT;
          }
        } elseif ($rainfall < 0.8) {
          if ($temperature < 0.25) {
            return Biome::TAIGA;
          } elseif ($temperature < 0.75) {
            return Biome::FOREST;
          } else {
            return Biome::BIRCH_FOREST;
          }
        } else {
          if ($temperature < 0.25) {
            return Biome::MOUNTAINS;
          } elseif ($temperature < 0.7) {
            return Biome::SMALL_MOUNTAINS;
          } else {
            return Biome::RIVER;
          }
        }
      },
      Biome::getBiome(Biome::OCEAN)
    );

    $this->heightOffset = $random->nextRange(-5, 3);

    // Add all biomes to the selector
    $this->selector->addBiome(new OceanBiome());
    $this->selector->addBiome(new PlainBiome());
    $this->selector->addBiome(Biome::getBiome(Biome::DESERT));
    $this->selector->addBiome(Biome::getBiome(Biome::MOUNTAINS));
    $this->selector->addBiome(new ForestBiome());
    $this->selector->addBiome(Biome::getBiome(Biome::TAIGA));
    $this->selector->addBiome(new SwampBiome());
    $this->selector->addBiome(Biome::getBiome(Biome::RIVER));
    $this->selector->addBiome(Biome::getBiome(Biome::ICE_PLAINS));
    $this->selector->addBiome(Biome::getBiome(Biome::SMALL_MOUNTAINS));
    $this->selector->addBiome(Biome::getBiome(Biome::BIRCH_FOREST));
    $this->selector->recalculate();

    // Clear parent populators and add our own
    $this->populators = [];
    $this->generationPopulators = [];

    $this->generationPopulators[] = new GroundCover();

    //From BetterGen ravine populator
    $ravine = new RavinePopulator();
    $ravine->setBaseAmount(0);
    $ravine->setRandomAmount(51);
    $this->generationPopulators[] = $ravine;

    //From BetterGen cave populator
    $cavePopulator = new CavePopulator();
    $cavePopulator->setBaseAmount(0);
    $cavePopulator->setRandomAmount(2);
    $this->generationPopulators[] = $cavePopulator;

    $this->populators[] = new Cave(); // Standard PM caves

    $ores = new Ore();
    $ores->setOreTypes([
      new OreType(new CoalOre(), 20, 16, 0, 128),
      new OreType(new IronOre(), 20, 8, 0, 64),
      new OreType(new RedstoneOre(), 8, 7, 0, 16),
      new OreType(new LapisOre(), 1, 6, 0, 32),
      new OreType(new GoldOre(), 2, 8, 0, 32),
      new OreType(new DiamondOre(), 1, 7, 0, 16),
      new OreType(new Dirt(), 20, 32, 0, 128),
      new OreType(new Gravel(), 10, 16, 0, 128),
    ]);
    $this->populators[] = $ores;
  }

  public function generateChunk($chunkX, $chunkZ)
  {
    $this->random->setSeed(
      0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed()
    );

    $seaFloorNoise = Generator::getFastNoise2D(
      $this->noiseSeaFloor,
      16,
      16,
      4,
      $chunkX * 16,
      0,
      $chunkZ * 16
    );
    $landNoise = Generator::getFastNoise2D(
      $this->noiseLand,
      16,
      16,
      4,
      $chunkX * 16,
      0,
      $chunkZ * 16
    );
    $mountainNoise = Generator::getFastNoise2D(
      $this->noiseMountains,
      16,
      16,
      4,
      $chunkX * 16,
      0,
      $chunkZ * 16
    );
    $baseNoise = Generator::getFastNoise2D(
      $this->noiseBaseGround,
      16,
      16,
      4,
      $chunkX * 16,
      0,
      $chunkZ * 16
    );
    $riverNoise = Generator::getFastNoise2D(
      $this->noiseRiver,
      16,
      16,
      4,
      $chunkX * 16,
      0,
      $chunkZ * 16
    );

    $chunk = $this->level->getChunk($chunkX, $chunkZ);

    for ($genx = 0; $genx < 16; $genx++) {
      for ($genz = 0; $genz < 16; $genz++) {
        $landHeightNoise = $landNoise[$genx][$genz] + 1;
        $landHeightNoise = ($landHeightNoise * 2.956) ** 2 - 0.6;
        $landHeightNoise = max(0, $landHeightNoise);

        $mountainHeightGenerate = $mountainNoise[$genx][$genz] - 0.2;
        $mountainGenerate =
          (int) ($this->mountainHeight * max(0, $mountainHeightGenerate));

        $landHeightGenerate = (int) ($this->landHeightRange * $landHeightNoise);
        $canBaseGround = $landHeightGenerate > $this->landHeightRange;
        if ($canBaseGround) {
          $landHeightGenerate = $this->landHeightRange;
        }

        $genyHeight =
          $this->seaFloorHeight + $landHeightGenerate + $mountainGenerate;

        if ($genyHeight < $this->beathStartHeight) {
          $genyHeight +=
            (int) ($this->seaFloorGenerateRange * $seaFloorNoise[$genx][$genz]);
          $biome = Biome::getBiome(Biome::OCEAN);
        } elseif ($genyHeight <= $this->beathStopHeight) {
          $biome = Biome::getBiome(Biome::DESERT);
        } else {
          $biome = $this->pickBiome($chunkX * 16 + $genx, $chunkZ * 16 + $genz);
        }

        $riverGenerate = $riverNoise[$genx][$genz];
        if ($riverGenerate > -0.25 && $riverGenerate < 0.25) {
          $riverValue = (0.25 - abs($riverGenerate)) ** 2 * 4 - 0.0000001;
          $genyHeight -= max(0, $riverValue) * 64;
          if ($genyHeight < $this->seaHeight) {
            $biome = Biome::getBiome(Biome::RIVER);
          }
        }

        $chunk->setBiomeId($genx, $genz, $biome->getId());
        $biomeColor = $biome->getColor();
        $chunk->setBiomeColor(
          $genx,
          $genz,
          $biomeColor >> 16,
          ($biomeColor >> 8) & 0xff,
          $biomeColor & 0xff
        );

        $generateHeight = max($genyHeight, $this->seaHeight);
        for ($geny = 0; $geny <= $generateHeight; $geny++) {
          if (
            $geny <= $this->bedrockDepth &&
            ($geny == 0 || $this->random->nextRange(1, 5) == 1)
          ) {
            $chunk->setBlockId($genx, $geny, $genz, Block::BEDROCK);
          } elseif ($geny > $genyHeight) {
            $chunk->setBlockId($genx, $geny, $genz, Block::WATER);
          } else {
            $chunk->setBlockId($genx, $geny, $genz, Block::STONE);
          }
        }
      }
    }

    foreach ($this->generationPopulators as $populator) {
      $populator->populate($this->level, $chunkX, $chunkZ, $this->random);
    }
  }

  public function populateChunk($chunkX, $chunkZ)
  {
    $this->random->setSeed(
      0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed()
    );
    foreach ($this->populators as $populator) {
      $populator->populate($this->level, $chunkX, $chunkZ, $this->random);
    }

    $chunk = $this->level->getChunk($chunkX, $chunkZ);
    $biome = Biome::getBiome($chunk->getBiomeId(7, 7));
    $biome->populateChunk($this->level, $chunkX, $chunkZ, $this->random);
  }

  public function getSpawn()
  {
    return new Vector3(127.5, 128, 127.5);
  }
}
