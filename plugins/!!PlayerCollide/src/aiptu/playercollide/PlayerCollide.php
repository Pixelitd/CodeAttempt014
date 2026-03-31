<?php

declare(strict_types=1);

namespace aiptu\playercollide;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class PlayerCollide extends PluginBase implements Listener {

    /** @var Server */
    private $server;

    /** @var array */
    private $previousPositions = [];

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->server = $this->getServer();

        // Schedule a repeating task to check collisions
        $this->server->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            private $plugin;

            public function __construct($plugin) {
                $this->plugin = $plugin;
            }

            public function onRun($currentTick) {
                $this->plugin->checkEntityCollisions();
            }
        }, 1); // This will run every tick (you can adjust this as needed)
    }

    /**
     * Track entity movement by storing previous position and calculating speed.
     *
     * @param Entity $entity
     * @return float
     */
    public function trackEntityMovement(Entity $entity): float {
        $currentPosition = $entity->getPosition();
        $entityId = $entity->getId();

        // Get the previous position or default to current position
        if (!isset($this->previousPositions[$entityId])) {
            $this->previousPositions[$entityId] = $currentPosition;
            return 0;  // No speed if no previous position is available
        }

        $previousPosition = $this->previousPositions[$entityId];
        $this->previousPositions[$entityId] = $currentPosition;

        // Calculate the distance moved and the time difference (approximated by the tick interval)
        $distanceMoved = $currentPosition->distance($previousPosition);
        return $distanceMoved;  // This can be adjusted for more precise speed calculations
    }

    /**
     * Check for collisions between entities every tick.
     */
    public function checkEntityCollisions() {
        $collisionDistance = (float) $this->getConfig()->get('collision_distance', 0.8);
        $baseKnockbackStrength = (float) $this->getConfig()->get('knockback_strength', 0.1);
        $speedKnockbackMultiplier = (float) $this->getConfig()->get('speed_knockback_multiplier', 0.5);

        // Loop through all players on the server and check for collisions with other entities
        foreach ($this->server->getOnlinePlayers() as $player) {
            if (!$player->isAlive()) {
                continue;
            }

            foreach ($player->getLevel()->getEntities() as $entity) {
                // Skip if the entity is the player itself or non-living entities (like projectiles)
                if ($entity === $player || !$entity->isAlive()) {
                    continue;
                }

                // Calculate entity speed based on movement
                $entitySpeed = $this->trackEntityMovement($entity);

                // Handle the collision
                $this->handleEntityCollision($player, $entity, $collisionDistance, $baseKnockbackStrength, $speedKnockbackMultiplier, $entitySpeed);
            }
        }
    }

    /**
     * Handle collision between two entities (player or mob).
     *
     * @param Entity $entity1 The first entity (player or mob)
     * @param Entity $entity2 The second entity (player or mob)
     * @param float $collisionDistance The maximum distance for collision detection
     * @param float $baseKnockbackStrength The base strength of knockback
     * @param float $speedKnockbackMultiplier The multiplier to apply knockback based on speed
     * @param float $entitySpeed The calculated speed of the entity
     */
    private function handleEntityCollision(Entity $entity1, Entity $entity2, float $collisionDistance, float $baseKnockbackStrength, float $speedKnockbackMultiplier, float $entitySpeed) {
        // Get the distance between the two entities
        $distance = $entity1->getPosition()->distance($entity2->getPosition());

        // If the distance is less than the configured collision distance, apply knockback
        if ($distance <= $collisionDistance) {
            // Calculate the direction vector between the two entities
            $diff = new Vector3(
                $entity1->getX() - $entity2->getX(),
                $entity1->getY() - $entity2->getY(),
                $entity1->getZ() - $entity2->getZ()
            );

            // Normalize the direction vector
            $direction = $diff->normalize();

            // Calculate knockback strength based on the entities' movement speed
            $knockbackValue = $baseKnockbackStrength + ($entitySpeed * $speedKnockbackMultiplier);

            // Apply knockback in opposite directions to push them away from each other
            $this->applyKnockback($entity1, $direction, $knockbackValue);
            $this->applyKnockback($entity2, $direction, $knockbackValue, true);  // Reverse direction for the second entity
        }
    }

    /**
     * Apply knockback to an entity (player or mob).
     *
     * @param Entity $target The entity to apply knockback to
     * @param Vector3 $direction The direction of the knockback
     * @param float $strength The strength of the knockback
     * @param bool $reverse Whether to reverse the direction of the knockback (for the second entity)
     */
    private function applyKnockback(Entity $target, Vector3 $direction, float $strength, bool $reverse = false) {
        // Reverse the direction for the second entity (to push them away from each other)
        if ($reverse) {
            $direction = $direction->multiply(-1);
        }

        // Apply the knockback using setMotion() (works for both players and entities)
        $target->setMotion($direction->multiply($strength));
    }
}
