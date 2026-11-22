<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\command\defaults;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;

class CountryCommand extends VanillaCommand
{

    public function __construct($name)
    {
        parent::__construct(
            $name,
            "%pocketmine.command.country.description",
            "%pocketmine.command.country.usage"
        );
        $this->owner = $name;
    }

    public function execute(CommandSender $sender, $currentAlias, array $args)
    {

        if (count($args) == 0) {
            $ip = $sender->getAddress();
            $location = json_decode(file_get_contents('http://ip-api.com/json/' . $ip));
            if (!isset($location->country)) {
                $sender->sendMessage(new TranslationContainer(TextFormat::RED . "%pocketmine.command.country.notFound"));
                return;
            }
            $country = $location->country;
            if ($sender instanceof Player) {
                $sender->sendMessage(new TranslationContainer("commands.generic.player.success", [$country]));
            } else {
                $sender->sendMessage("§cThis command can only be used within the game!");
            }
        }

        if (count($args) == 1) {
            if ($player = $sender->getServer()->getPlayer($args[0])) {
                $player = $sender->getServer()->getPlayer($args[0]);
                $playerip = $player->getAddress();
                $playerlocation = json_decode(file_get_contents('http://ip-api.com/json/' . $playerip));
                $playercountry = $playerlocation->country;
                $sender->sendMessage(new TranslationContainer("pocketmine.command.country.player", [$player->getName(), $playercountry]));
            } else {
                $sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.player.notFound"));
            }
        }

        return true;
    }
}
