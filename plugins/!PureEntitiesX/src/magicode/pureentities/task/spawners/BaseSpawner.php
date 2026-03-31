<?php

namespace magicode\pureentities\task\spawners;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use magicode\pureentities\entity\animal\flying\Bat;
use magicode\pureentities\entity\animal\swimming\Squid;
use magicode\pureentities\entity\animal\walking\Chicken;
use magicode\pureentities\entity\animal\walking\Cow;
use magicode\pureentities\entity\animal\walking\Donkey;
use magicode\pureentities\entity\animal\walking\Horse;
use magicode\pureentities\entity\animal\walking\Mooshroom;
use magicode\pureentities\entity\animal\walking\Mule;
use magicode\pureentities\entity\animal\walking\Ocelot;
use magicode\pureentities\entity\animal\walking\Pig;
use magicode\pureentities\entity\animal\walking\Rabbit;
use magicode\pureentities\entity\animal\walking\Sheep;
use magicode\pureentities\entity\animal\walking\Villager;
use magicode\pureentities\entity\monster\flying\Blaze;
use magicode\pureentities\entity\monster\flying\Ghast;
use magicode\pureentities\entity\monster\jumping\MagmaCube;
use magicode\pureentities\entity\monster\jumping\Slime;
use magicode\pureentities\entity\monster\walking\CaveSpider;
use magicode\pureentities\entity\monster\walking\Creeper;
use magicode\pureentities\entity\monster\walking\Enderman;
use magicode\pureentities\entity\monster\walking\Husk;
use magicode\pureentities\entity\monster\walking\IronGolem;
use magicode\pureentities\entity\monster\walking\PigZombie;
use magicode\pureentities\entity\monster\walking\Silverfish;
use magicode\pureentities\entity\monster\walking\Skeleton;
use magicode\pureentities\entity\monster\walking\SnowGolem;
use magicode\pureentities\entity\monster\walking\Spider;
use magicode\pureentities\entity\monster\walking\Stray;
use magicode\pureentities\entity\monster\walking\WitherSkeleton;
use magicode\pureentities\entity\monster\walking\Wolf;
use magicode\pureentities\entity\monster\walking\Zombie;
use magicode\pureentities\entity\monster\walking\ZombieVillager;
use magicode\pureentities\PureEntities;
use magicode\pureentities\task\spawners\animal\RabbitSpawner;

/**
 * Class BaseSpawner
 *
 * A base spawner class which all spawner classes extend from
 *
 * @package magicode\pureentities\task\spawners
 */
abstract class BaseSpawner {

    // stores all heights of mobs for spwaning reasons
    const HEIGHTS = array (
        //Bat::NETWORK_ID     => 0.3,
        //Squid::NETWORK_ID => 0.95,
        Chicken::NETWORK_ID => 0.7,
        Cow::NETWORK_ID     => 1.3,
        //Donkey::NETWORK_ID => 1.6,
        //Horse::NETWORK_ID   => 1.6,
        Mooshroom::NETWORK_ID => 1.12,
        //Mule::NETWORK_ID => 1.4,
        Ocelot::NETWORK_ID => 0.9,
        Pig::NETWORK_ID => 1.12,
        Rabbit::NETWORK_ID => 0.5,
        Sheep::NETWORK_ID => 1.8,
        Villager::NETWORK_ID => 1.8,
        Blaze::NETWORK_ID => 1.8,
        Ghast::NETWORK_ID => 4,
        MagmaCube::NETWORK_ID => 1.2,
        Slime::NETWORK_ID => 1.2,
        CaveSpider::NETWORK_ID => 0.8,
        Creeper::NETWORK_ID => 1.8,
        Enderman::NETWORK_ID => 2.8,
        Husk::NETWORK_ID => 2,
        IronGolem::NETWORK_ID => 2.1,
        PigZombie::NETWORK_ID => 1.8,
        Silverfish::NETWORK_ID => 0.2,
        Skeleton::NETWORK_ID => 1.8,
        SnowGolem::NETWORK_ID => 1.8,
        Spider::NETWORK_ID => 1.12,
        Stray::NETWORK_ID => 2,
        WitherSkeleton::NETWORK_ID => 1.8,
        Wolf::NETWORK_ID => 0.9,
        Zombie::NETWORK_ID  => 1.8,
        ZombieVillager::NETWORK_ID => 1.8
    );

    const MIN_DISTANCE_TO_PLAYER = 8; // in blocks

    /** @var  PureEntities $plugin */
    protected $plugin;

    /** @var int $maxSpawn */
    protected $maxSpawn = -1;

    /** @var int $probability */
    private $probability = 1; // 1 percent chance by default

    /**
     * BaseSpawner constructor.
     */
    public function __construct() {
        $this->maxSpawn = PureEntities::getInstance()->getConfig()->getNested("max-spawn." . strtolower($this->getEntityName()), 0);
        $this->probability = PureEntities::getInstance()->getConfig()->getNested("probability." . strtolower($this->getEntityName()), 0);
        PureEntities::logOutput("BaseSpawner: got " . $this->probability . "% spawn probability for " . $this->getEntityName() . " spawns with a maximum number of " . $this->maxSpawn . " living entities per level", PureEntities::DEBUG);
    }


    /**
     * Checks with the help of given level, if entity spawn is allowed by configuration or if entity spawn
     * may exhaust max spawn for the entity
     *
     * @param Level $level
     * @return bool
     */
    protected function spawnAllowedByEntityCount (Level $level) : bool {
        if ($this->maxSpawn <= 0) {
            return false;
        }
        $count = 0;
        foreach ($level->getEntities() as $entity) { // check all entities in given level
            if ($entity->isAlive() and !$entity->closed and $entity::NETWORK_ID == $this->getEntityNetworkId()) { // count only alive, not closed and desired entities
                $count ++;
            }
        }

        PureEntities::logOutput("BaseSpawner: got count of $count entities living for " . $this->getEntityName(), PureEntities::DEBUG);

        if ($count < $this->maxSpawn) {
            return true;
        }
        return false;
    }

    /**
     * Returns true when the spawn probability matches
     *
     * @return bool
     */
    protected function spawnAllowedByProbability () : bool {
        return $this->probability > 0 ? (mt_rand(0, 100) <= $this->probability) : false;
    }

    /**
     * Checks and returns true if the spawn point distance relative to the player is at least
     * 8 fields. If not, this method return false. Do not spawn when this function returns false.
     *
     * @param Player $player
     * @param Position $pos
     * @return bool
     */
    protected function checkPlayerDistance (Player $player, Position $pos) {
        return $player->distance($pos) > self::MIN_DISTANCE_TO_PLAYER;
    }

    /**
     * Checks with the help of the time in the level, if it is night or day.
     *
     * @param Level $level
     * @return bool
     */
    protected function isDay (Level $level) {
        $time = $level->getTime() % Level::TIME_FULL;
        return ($time <= Level::TIME_SUNSET || $time >= Level::TIME_SUNRISE);
    }

    /**
     * @return string
     */
    protected function getClassNameShort () : string {
        $classNameWithNamespace = get_class($this);
        return substr($classNameWithNamespace, strrpos($classNameWithNamespace, '\\')+1);
    }

    /**
     * Use THIS method for spawning mobs! This adds the proper height to the spawn position. Otherwise
     * the entity may get stuck in the ground or suffers suffocation
     *
     * @param Position $pos
     * @param int $entityid
     * @param Level $level
     * @param string $type
     * @return bool
     */
    protected function spawnEntityToLevel (Position $pos, int $entityid, Level $level, string $type) : bool {
        $pos->y += self::HEIGHTS[$entityid];
        return PureEntities::getInstance()->scheduleCreatureSpawn($pos, $entityid, $level, $type);
    }


    // ---- abstract functions declaration ----
    protected abstract function getEntityNetworkId () : int;
    protected abstract function getEntityName () : string;
    public abstract function spawn (Position $pos, Player $player) : bool;

}