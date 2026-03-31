<?php

/*
 * ChatCensor v2.3 by EvolSoft
 * Developer: Flavius12
 * Website: https://www.evolsoft.tk
 * Copyright (C) 2014-2018 EvolSoft
 * Licensed under MIT (https://github.com/EvolSoft/ChatCensor/blob/master/LICENSE)
 */

namespace TextFilter;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class EventListener implements Listener {
    
    /** @var TextFilter */
    private $plugin;
    
    private $lastmessage;
    
    public function __construct(TextFilter $plugin){
        $this->plugin = $plugin;
    }
	
	/**
	 * @param PlayerCommandPreprocessEvent $event
	 */
	public function onChat(PlayerCommandPreprocessEvent $event){
		$message = $event->getMessage();
		$player = $event->getPlayer();
		$cfg = $this->plugin->cfg;
		//Check if this is a command and skip if check-commands is disabled
		if($message == "/" && !$cfg["censor"]["check-commands"]){
		    return;
		}
		//Check if player is muted
		if($this->plugin->isMuted(strtolower($player->getName())) && $message[0] != "/"){
			if($cfg["mute"]["log-to-player"]){
			    $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("muted-error"), array("PREFIX" => TextFilter::PREFIX)));
			}
			$event->setCancelled(true);
			return;
		}
		//Check if Anti-Caps is enabled
		if($cfg["anti-caps"]["enabled"]){
		    if($cfg["anti-caps"]["allow-bypassing"] && $player->hasPermission("textfilter.bypass.anti-caps")){
		        goto spamcheck;
		    }
		    if(preg_match_all('/\b[A-Z]+\b/', $message, $matches)){
                $word_count = count($matches[0]);
                if($word_count > $cfg["anti-caps"]["word-limit"]){
                    if($cfg["anti-caps"]["block-message"]){
                        if($cfg["anti-caps"]["log-to-player"]){
                            $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("no-caps"), array("PREFIX" => TextFilter::PREFIX)));
                        }
                        $event->setCancelled(true);
                        return;
                    }
                    $message = strtolower($message);
                }  
            }
        }
		spamcheck:
    		//Check if Anti-Spam is enabled
    		if($cfg["anti-spam"]["enabled"]){
    		    if($cfg["anti-spam"]["allow-bypassing"] && $player->hasPermission("textfilter.bypass.anti-spam")){
    		        goto charcheck;
    		    }
    		    if(isset($this->lastmessage[$player->getName()])){
    		        if($cfg["anti-spam"]["mode"] == 0 || $cfg["anti-spam"]["mode"] == 2){
	                    if(strcasecmp($message, $this->lastmessage[$player->getName()]["message"]) == 0){
	                        if($cfg["anti-spam"]["log-to-player"]){
	                            $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("no-spam"), array("PREFIX" => ChatCensor::PREFIX)));
	                        }
	                        $event->setCancelled(true);
	                        return;
	                    }
    		        }
    		        if($cfg["anti-spam"]["mode"] == 1 || $cfg["anti-spam"]["mode"] == 2){
    		            $t = time() - $this->lastmessage[$player->getName()]["time"];
		                if($t < $cfg["anti-spam"]["delay"]){
		                    if($cfg["anti-spam"]["log-to-player"]){
		                        $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("spam-delay"), array("PREFIX" => TextFilter::PREFIX, "DELAY" => $cfg["anti-spam"]["delay"] - $t)));
		                    }
		                    $event->setCancelled(true);
		                    return;
		                }
    		        }
    		    }
    		}
		charcheck:
    		//Check if CharCheck is enabled
    		if($cfg["char-check"]["enabled"]){
    			//Checking if bypass is allowed
    		    if($cfg["char-check"]["allow-bypassing"] && $player->hasPermission("textfilter.bypass.char-check")){
    				goto censor;
    			}
    			//Check message length
    			if($cfg["char-check"]["max-length"] > 0 && strlen($message) > $cfg["char-check"]["max-length"]){
    			    if($cfg["char-check"]["log-to-player"]){
    			        $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("too-long"), array("PREFIX" => TextFilter::PREFIX)));
    			    }
    			    $event->setCancelled(true);
    			    return;
    			}
    			//Check backslash
    			if(!$cfg["char-check"]["allow-backslash"]){
    			    if((bool) strpbrk($message, "\\")){
    			        if($cfg["char-check"]["log-to-player"]){
    			            $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("invalid"), array("PREFIX" => TextFilter::PREFIX)));
    			        }
    			        $event->setCancelled(true);
    			        return;
    			    }
    			}
    			//Unallowed characters checker
    			$unallowed = $this->plugin->getUnallowedChars();
    			if($unallowed != ""){
    			    if((bool) strpbrk($message, $unallowed)){
    			        if($cfg["char-check"]["log-to-player"]){
    			            $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("invalid"), array("PREFIX" => TextFilter::PREFIX)));
    			        }
    			        $event->setCancelled(true);
    			        return;
    			    }
    			}
    			//Allowed characters checker
    			$allowed = $this->plugin->getAllowedChars();
    			if($allowed != null){
    			    $allowed .= " ";
    			    $allowedchr = str_split($allowed);
    			    $messagearray = str_split($message);
    			    foreach($messagearray as $word){
    			        if(!in_array($word, $allowedchr)){
    			            if($cfg["char-check"]["log-to-player"]){
    			                $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("invalid"), array("PREFIX" => TextFilter::PREFIX)));
    			            }
    			            $event->setCancelled(true);
    			            return;
    			        }
    			    }
    			}
    		}
		censor:
			//Check if Censor is enabled
			if($cfg["censor"]["enabled"]){
				//Checking if bypass is allowed
				if($cfg["censor"]["allow-bypassing"] && $player->hasPermission("textfilter.bypass.censor")){
				    goto next;
				}
				$tempmessage = $message;
				$words = explode(" ", $message);
				foreach($words as $word){
				//Check if websites are blocked
                    if($cfg["censor"]["block-urls"]){
                        $domain = preg_replace('/(:\d+)?(\/.*)?$/', '', $word);
                        if(!empty($domain)){
                        	if((checkdnsrr($domain, 'A')) || checkdnsrr($domain, 'AAAA') || checkdnsrr($domain, 'CNAME')){
                            	if($cfg["censor"]["log-to-player"]){
                                	$player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("no-urls"), array("PREFIX" => TextFilter::PREFIX)));
                            	}
                            	$event->setCancelled(true);
                            	return;
                            }
                        }
                        if(preg_match($regex = '/^(?:[a-z0-9-]+\.)+(?:com|net|org|io|gov|edu|co|info|biz|tv|me|xyz|gg|club|shop|store|live|online|site|link|app|dev|ai)(?::(\d{1,5}))?(\/[^\s]*)?$/i', $word)){
                            if($cfg["censor"]["log-to-player"]){
                                $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("no-urls"), array("PREFIX" => TextFilter::PREFIX)));
                            }
                            $event->setCancelled(true);
                            return;
                        }
                    }
                    //Check if IP addresses are blocked
                    if($cfg["censor"]["block-ips"]&& preg_match($regex = '/^(?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(?::(\d{1,5}))?$/', $word)){
				        if($cfg["censor"]["log-to-player"]){
				            $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("no-ips"), array("PREFIX" => TextFilter::PREFIX)));
				        }
				        $event->setCancelled(true);
				        return;
				    }
				    $key = null;
				    if($this->plugin->wordExists($word, $key)){
						//Check Word Config
				        $tmp = $this->plugin->getWord($word);
						if($tmp["delete-message"]){
							$event->setCancelled(true);
						}
						if($tmp["enable-replace"]){
							$replace = $tmp["replace-word"];
							if(is_array($replace)){
							$replace = implode("", $replace);
							}
							$tempmessage = str_replace($key, $replace, $tempmessage);
						}
						if($cfg["censor"]["log-to-player"]){
						    $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("no-swearing"), array("PREFIX" => TextFilter::PREFIX)));
						}
					    foreach($tmp["commands"] as $cmd){
					        $cmd = $this->plugin->replaceVars($cmd, array("PLAYER" => $player->getName()));
					        Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $cmd);
					    }
					}
		      }
		      next:
                if(isset($tempmessage)){
                    $event->setMessage($tempmessage);
                }else{
                    $event->setMessage($message);
                }
		}
		$this->lastmessage[$player->getName()]["message"] = $message;
		$this->lastmessage[$player->getName()]["time"] = time();
	}
    
    public function onSign(SignChangeEvent $event){
    $player = $event->getPlayer();
    $lines = [
        $event->getLine(0),
        $event->getLine(1),
        $event->getLine(2),
        $event->getLine(3)
    ];
    $cfg = $this->plugin->cfg;
    
    //Check if Censor is enabled
    if($cfg["censor"]["enabled"]){
        //Checking if bypass is allowed
        if($cfg["censor"]["allow-bypassing"] && $player->hasPermission("textfilter.bypass.censor")){
            goto next;
        }

        foreach($lines as $message){
        $words = explode(" ", $message);
        foreach($words as $word){
            //Check if websites are blocked
            if($cfg["censor"]["block-urls"] && preg_match('/^(?:https?:\/\/(?:www\.)?)?(?![\d.]+$)([\w.-]+(?:\.[\w.-]+)+)(?::\d{1,5})?((?:\/[\w.-]*)*\/?)$/i', $word)){
                if($cfg["censor"]["log-to-player"]){
                    $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("no-urls"), array("PREFIX" => TextFilter::PREFIX)));
                }
                $event->setCancelled(true);
                return;
            }
            //Check if IP addresses are blocked
            if($cfg["censor"]["block-ips"] && preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:\.(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $word)){
                if($cfg["censor"]["log-to-player"]){
                    $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("no-ips"), array("PREFIX" => TextFilter::PREFIX)));
                }
                $event->setCancelled(true);
                return;
            }
        }

        $processedLines = $lines;

        foreach($processedLines as $index => $lineContent){
            $words = explode(" ", $lineContent);
            $key = null;
                if($this->plugin->wordExists($word, $key)){
                    //Check Word Config
                    $tmp = $this->plugin->getWord($key);
                    if($tmp["delete-message"]){
                        $event->setCancelled(true);
                        if($cfg["censor"]["log-to-player"]){
						    $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("no-swearing"), array("PREFIX" => TextFilter::PREFIX)));
						}
                        return;
                    }
                    if($tmp["enable-replace"]){
                        $replace = $tmp["replace-word"];
                        if(is_array($replace)){
                            $replace = implode("", $replace);
                        }
                        $processedLines[$index] = str_replace($key, $replace, $processedLines[$index]);
                    }
                    if($cfg["censor"]["log-to-player"]){
                        $player->sendMessage($this->plugin->replaceVars($this->plugin->getMessage("no-swearing"), array("PREFIX" => TextFilter::PREFIX)));
                    }
                }
            }
        }
        
        next:
        $finalLines = isset($processedLines) ? $processedLines : $lines;
        $event->setLine(0, $finalLines[0]);
        $event->setLine(1, $finalLines[1]);
        $event->setLine(2, $finalLines[2]);
        $event->setLine(3, $finalLines[3]);
    }
}
}