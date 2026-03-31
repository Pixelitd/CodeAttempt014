<?php
namespace wallbanner\command;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use wallbanner\Loader;
class WallBannerCommand extends Command {
	public function __construct() {
		parent::__construct('wallbanner', 'Set wall banners in a block area', null, ['wlb', 'wb', 'image']);
		$this->setPermission('wallbanner.manager');
	}
	public function execute(CommandSender $sender, $commandLabel, array $args) {
		if(!($sender instanceof Player)) {
			$sender->sendMessage('§cThis command only works in game');
			return false;
		}
		if(!$this->testPermission($sender)) {
			return false;
		}
		$subcommand = strtolower(array_shift($args) ?? '');
		switch($subcommand) {
			case 'help':
				$sender->sendMessage(implode(PHP_EOL, [
					'List of arguments for using the command:',
					'/'.$commandLabel.' add - To create a new image',
					'/'.$commandLabel. ' list - To see a list of images uploaded to the plugin',
				]));
				break;
    case 'list':
		 case 'image-list':
		 case 'ls':
				$sender->sendMessage('List of uploaded images: '.implode(', ', Loader::get()->getLoadedImageList()));
				break;
    case 'add':
		 case 'make':
	 	 case 'new':
				if(isset($args[0])) {
					$imagePath = Loader::get()->getDataFolder().'images/'.$args[0];
					if(file_exists($imagePath)) {
						Loader::get()->creates[$sender->getName()] = [
							'path' => $imagePath
						];
						$sender->sendMessage('We are almost done! Now set the first position of the area where the wall banner will be');
					} else {
						$sender->sendMessage('The reported image is not registered in the plugin');
					}
				} else {
					$sender->sendMessage('Usage: /'.$commandLabel.' '.$subcommand.' [Image name (with extension: .png or .jpeg)]');
				}
				break;
			default:
				$sender->sendMessage('Usage: /'.$commandLabel.' help');
			break;
		}
		return true;
	}
}