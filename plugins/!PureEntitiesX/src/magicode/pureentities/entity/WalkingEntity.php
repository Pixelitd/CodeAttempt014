<?php

namespace magicode\pureentities\entity;

use pocketmine\block\Block;
use magicode\pureentities\data\BlockSides;
use magicode\pureentities\entity\animal\Animal;
use magicode\pureentities\entity\monster\walking\PigZombie;
use pocketmine\block\Liquid;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\entity\Creature;

abstract class WalkingEntity extends BaseEntity{

    protected function checkTarget(){
        if($this->isKnockback()){
            return;
        }

        $target = $this->baseTarget;
        if(!$target instanceof Creature or !$this->targetOption($target, $this->distanceSquared($target))){
            $near = PHP_INT_MAX;
            foreach ($this->getLevel()->getEntities() as $creature){
                if($creature === $this || !($creature instanceof Creature) || $creature instanceof Animal){
                    continue;
                }

                if($creature instanceof BaseEntity && $creature->isFriendly() == $this->isFriendly()){
                    continue;
                }

                $distance = $this->distanceSquared($creature);
                if(
                    $distance <= 100
                    && $this instanceof PigZombie && $this->isAngry()
                    && $creature instanceof PigZombie && !$creature->isAngry()
                ){
                    $creature->setAngry(1000);
                }

                if($distance > $near or !$this->targetOption($creature, $distance)){
                    continue;
                }
                $near = $distance;

                $this->moveTime = 0;
                $this->baseTarget = $creature;
            }
        }

        if($this->baseTarget instanceof Creature && $this->baseTarget->isAlive()){
            return;
        }

        if($this->moveTime <= 0 or !($this->baseTarget instanceof Vector3)){
            $x = mt_rand(20, 100);
            $z = mt_rand(20, 100);
            $this->moveTime = mt_rand(300, 1200);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
        }
    }

    /**
     * @param int $dx
     * @param int $dz
     *
     * @return bool
     */
    protected function checkJump($dx, $dz){
        if ($this->motionY == $this->gravity * 2) { // swimming
            return $this->getLevel()->getBlock(new Vector3(Math::floorFloat($this->x), (int)$this->y, Math::floorFloat($this->z))) instanceof Liquid;
        } else { // dive up?
            if ($this->getLevel()->getBlock(new Vector3(Math::floorFloat($this->x), (int)($this->y + 0.8), Math::floorFloat($this->z))) instanceof Liquid) {
                $this->motionY = $this->gravity * 2; // set swimming (rather walking on water ;))
                return true;
            }
        }

        if (!$this->isOnGround() or $this->stayTime > 0) {
            return false;
        }

        if ($this->getDirection() === null) {
            return false;
        }

         $that = $this->getLevel()->getBlock(new Vector3(Math::floorFloat($this->x + $dx), (int)$this->y, Math::floorFloat($this->z + $dz)));
        $block = $that->getSide(BlockSides::getSides()[$this->getDirection()]);
        // we cannot pass through the block that is directly in front of us
        if (!$block->canPassThrough() and $this->getMaxJumpHeight() > 0) { // it's possible that an entity can't jump?! better check!
            // check if we can get through the upper of the block directly in front of the entity
            if ($block->getSide(Block::SIDE_UP)->canPassThrough() && $that->getSide(Block::SIDE_UP, 2)->canPassThrough()) {
                if ($block instanceof Fence || $block instanceof FenceGate) { // cannot pass fence or fence gate ...
                    $this->motionY = $this->gravity;
                } else if ($this->motionY <= ($this->gravity * 5)) {
                    $this->motionY = $this->gravity * 5;
                } else {
                    $this->motionY += $this->gravity * 0.25;
                }
                return true;
            }
        }
        return false;
        
         }

    /**
     * This function checks if upper blocks of the given block can be passed through. This
     * method brings in jumpHeight of the entitiy (normal entities can only jump one block, horses
     * e.g. jump 2 blocks ...)
     *
     * @param Block $block  the block the check starts with
     * @return bool true if upper blocks can be passed through
     */
    private function canPassThroughUpperBlocks (Block $block) : bool {
        for ($y=0.8; $y <= $this->getMaxJumpHeight(); $y++) {
            if (!$block->getSide(Block::SIDE_UP, ($y + 1))->canPassThrough()) {
                return false;
            }
            return true;
        }
    }

    /**
     * @param int $tickDiff
     *
     * @return null|Vector3
     */
    public function updateMove($tickDiff){
        if(!$this->isMovement()){
            return null;
        }

        if($this->isKnockback()){
            $this->move($this->motionX * $tickDiff, $this->motionY, $this->motionZ * $tickDiff);
            $this->motionY -= 0.2 * $tickDiff;
            $this->updateMovement();
            return null;
        }
        
        $before = $this->baseTarget;
        $this->checkTarget();
        if($this->baseTarget instanceof Creature or $before !== $this->baseTarget){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;

            $diff = abs($x) + abs($z);
            if($x ** 2 + $z ** 2 < 0.7){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
            }
            $this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }

        $dx = $this->motionX * $tickDiff;
        $dz = $this->motionZ * $tickDiff;
        $isJump = false;
        if ($this->isCollidedHorizontally or $this->isInsideOfWater()) {
            $isJump = $this->checkJump($dx, $dz);
        }
        if($this->stayTime > 0){
            $this->stayTime -= $tickDiff;
            $this->move(0, $this->motionY * $tickDiff, 0);
        }else{
            $be = new Vector2($this->x + $dx, $this->z + $dz);
            $this->move($dx, $this->motionY * $tickDiff, $dz);
            $af = new Vector2($this->x, $this->z);

            if(($be->x != $af->x || $be->y != $af->y) && !$isJump){
                $this->moveTime -= 90 * $tickDiff;
            }
        }

        if(!$isJump){
             if ($this->isOnGround()) {
                $this->motionY = 0;
            }else if($this->motionY > -$this->gravity * 4){
                 if (!($this->getLevel()->getBlock(new Vector3(Math::floorFloat($this->x), (int)($this->y + 0.8), Math::floorFloat($this->z))) instanceof Liquid)) {	$this->motionY -= $this->gravity * 1;	}
            }else{
                $this->motionY -= $this->gravity* $tickDiff;
             }
        }
        $this->updateMovement();
        return $this->baseTarget;
    }
}