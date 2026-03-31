<?php
namespace wallbanner;
use ErrorException;
use pocketmine\block\Block;
use pocketmine\block\ItemFrame as ItemFrameBlock;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\tile\ItemFrame as ItemFrameTile;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Tile;
use wallbanner\protocol\ClientboundMapItemDataPacket;
use wallbanner\protocol\MapInfoRequestPacket;
use wallbanner\storage\MapStorage;
use wallbanner\utils\Image;
class EventListener implements Listener {
	public function onReceive(DataPacketReceiveEvent $event) {
		$packet = $event->getPacket();
		if($packet instanceof MapInfoRequestPacket) {
			$nbt = MapStorage::get()->getMapData($packet->mapId);
			if($nbt instanceof CompoundTag && isset($nbt['rawImage'])) {
				$pk = new ClientboundMapItemDataPacket();
				$pk->colors = $nbt['rawImage'];
				$pk->isColorArray = false;
				$pk->mapId = $packet->mapId;
				$event->getPlayer()->dataPacket($pk);
			}
		}
	}
	public function onBreak(BlockBreakEvent $event) {
		$block = $event->getBlock();
		if($block->getId() === Block::ITEM_FRAME_BLOCK) {
			$tile = $block->getLevel()->getTile($block);
			if(!($tile instanceof ItemFrameTile) || ($item = $tile->getItem())->getId() !== Item::FILLED_MAP) {
				return;
			}
			$player = $event->getPlayer();	
			$inventory = $player->getInventory();
			$tag = $item->getNamedTag() ?? new CompoundTag();

			if(($tag['locked_image'] ?? 0) !== 0 && (!$player->isOp() || $inventory->getItemInHand()->getId() !== Item::BONE)) {
				$event->setCancelled(true);
			}
		}
	}
	public function onInteract(PlayerInteractEvent $event) {
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($block->getId() === Block::ITEM_FRAME_BLOCK) {
			$tile = $block->getLevel()->getTile($block);
			if($tile instanceof ItemFrameTile && ($item = $tile->getItem())->getId() === Item::FILLED_MAP) {
				$tag = $item->getNamedTag() ?? new CompoundTag();
				$inventory = $player->getInventory();
				if(($tag['locked_image'] ?? 0) !== 0 && !$player->isOp() || $inventory->getItemInHand()->getId() !== Item::BONE) {
					$event->setCancelled(true);
					$tile->spawnTo($player);
				}
			}
			return;
		}
		elseif($event->getAction() !== $event::RIGHT_CLICK_BLOCK) {
			return;
		}
		$data = &Loader::get()->creates[$player->getName()];
		if($data === null) {
			return;
		}
		$position = $event->getBlock()->getSide($event->getFace());
		if(!isset($data['pos1'])) {
			$player->sendMessage('First position successfully defined! Now sellect second position');
			$data['pos1'] = $position;
			return;
		}
		if(!isset($data['pos2'])) {
			$player->sendMessage('Second position successfully defined');
		}
		$pos1 = $data['pos1'];
		$pos2 = $position;
		$useXDirection = false;
		if(abs($pos1->x - $pos2->x) > 0) {
			$useXDirection = true;
		}
		if($useXDirection && abs($pos1->z - $pos2->z) > 0) {
			$player->sendMessage('The block area of the wall banner should be a 2D rectangular area and not 3D');
			unset(Loader::get()->creates[$player->getName()]);
			return;
		}
		$min = new Vector3(
			min($pos1->getX(), $pos2->getX()),
			min($pos1->getY(), $pos2->getY()),
			min($pos1->getZ(), $pos2->getZ())
		);
		$max = new Vector3(
			max($pos1->getX(), $pos2->getX()),
			max($pos1->getY(), $pos2->getY()),
			max($pos1->getZ(), $pos2->getZ())
		);
		$height = 1 + ($max->y - $min->y);
		$width  = 1 + ($useXDirection ? ($max->x - $min->x) : ($max->z - $min->z));
		$player->sendMessage("Height: $height, Width: $width");
		$pieces = Image::getPiecesOfImagePerBlockArea($data['path'], $height, $width);
		$faces = [
			2 => 3,
			3 => 2,
			4 => 1,
			5 => 0,
		];
		$face = $faces[$event->getFace()] ?? 0;
		$player->sendMessage("Face: $face");
		for($y = 0; $y < $height; ++$y) {
			for($x = 0; $x < $width; ++$x) {
				if($useXDirection) {
					$position = $min->add($x, $height - ($y + 1), 0);
				} else {
					$position = $min->add(0,  $height - ($y + 1), $x);
				}
				$player->level->setBlock($position, new ItemFrameBlock($face), true, true);
				$nbt = new CompoundTag("", [
					new StringTag("id", Tile::ITEM_FRAME),
					new IntTag("x", $position->x),
					new IntTag("y", $position->y),
					new IntTag("z", $position->z),
					new ByteTag("ItemRotation", 0),
					new FloatTag("ItemDropChance", 0.0)
				]);
				$tile = Tile::createTile(
					Tile::ITEM_FRAME,
					$player->level->getChunk(
						$position->x >> 4,
						$position->z >> 4
					),
					$nbt
				);
				$mapId = MapStorage::get()->getNextMapId();
				if($event->getFace() === Vector3::SIDE_EAST || $event->getFace() === Vector3::SIDE_NORTH) {
					$pixels = $pieces[Level::chunkHash($width - 1 - $x, $y)];
				} else {
					$pixels = $pieces[Level::chunkHash($x, $y)];
				}
				try {
					MapStorage::get()->saveMapData($mapId, new CompoundTag("", [new ByteArrayTag('rawImage', $pixels)]));
				} catch(ErrorException $exception) {
				}
				$item = new Item(Item::FILLED_MAP);
				$item->setNamedTag(new CompoundTag('', [
					new StringTag('map_uuid', (string) $mapId),
					new ByteTag('locked_image', 1)
				]));
				$tile->setItem($item, true);
			}
		}
		$player->sendMessage('Wall banner successfully set!');
		unset(Loader::get()->creates[$player->getName()]);
	}
}