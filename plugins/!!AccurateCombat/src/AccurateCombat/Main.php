<?php

namespace AccurateCombat;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Main extends PluginBase implements Listener {

    private $config;

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        //$this->getLogger()->info("§aAccurateCombat enabled with custom knockback and damage!");
        return $this;
    }

    /**
     * Modify damage and knockback to mimic Bedrock PvP physics
     */
    public function onDamage(\pocketmine\event\entity\EntityDamageEvent $event) {
    if($event->isCancelled()) return;

    $entity = $event->getEntity();
    $cause = $event->getCause();

    // Only process PvP
    if($cause === \pocketmine\event\entity\EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
        $damager = $event->getDamager();
        if(!($damager instanceof \pocketmine\Player) || !($entity instanceof \pocketmine\Player)) {
            return;
        }

        // ===== DAMAGE ADJUSTMENT =====
        $baseMultiplier = (float)$this->config->getNested("damage.base_multiplier", 1.0);
        $criticalMultiplier = (float)$this->config->getNested("damage.critical_multiplier", 1.5);

        $damage = $event->getOriginalDamage() * $baseMultiplier;

        // Optional: simulate critical hits (jumping attacks)
        if (!$damager->isOnGround()) {
            $damage *= $criticalMultiplier;
        }

        $event->setDamage($damage);

        // ===== KNOCKBACK ADJUSTMENT =====
        $kbHorizontal = (float)$this->config->getNested("knockback.horizontal", 0.35);
        $kbVertical = (float)$this->config->getNested("knockback.vertical", 0.35);
        $friction = (float)$this->config->getNested("knockback.friction", 0.6);

        $deltaX = $entity->getX() - $damager->getX();
        $deltaZ = $entity->getZ() - $damager->getZ();
        $f = sqrt($deltaX * $deltaX + $deltaZ * $deltaZ);

        if ($f > 0) {
            $f = 1 / $f;
            $motion = new Vector3(
                $deltaX * $f * $kbHorizontal,
                $kbVertical,
                $deltaZ * $f * $kbHorizontal
            );

            // Apply friction simulation
            $entity->setMotion($entity->getMotion()->multiply($friction)->add($motion));
            }
        }
    }
}