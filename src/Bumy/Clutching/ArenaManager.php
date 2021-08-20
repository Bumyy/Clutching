<?php

declare(strict_types=1);

namespace Bumy\Clutching;

use Bumy\Clutching\Main;
use Bumy\Clutching\PlayerData;
use Bumy\Clutching\task\HitTask;
use Bumy\Clutching\task\WaitBetweenTask;

use pocketmine\level\Position;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\Player;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

class ArenaManager{

	public $plugin;

    public $maps;

	public function __construct(Main $plugin){
        $this->plugin = $plugin;
        $this->maps = $this->plugin->config["maps"];
        if(count($this->maps) == 1){
            array_push($this->maps, $this->maps[0]);
        }
    }

    public function createGame($player){
        //getting a random map from the maps array
    	$map = $this->maps[mt_rand(0, count($this->maps) - 1)];
        $this->getPlayerData($player->getName())->setMap($map);

    	$this->getPlugin()->createMap($player, $map);

    	$pos = new Position(100, 52, 100, $this->getPlugin()->getServer()->getLevelByName($map."-".$player->getName()));
    	$player->teleport($pos);
        $this->getPlayerData($player->getName())->npc = Entity::createEntity("CustomNPC", $player->level, Entity::createBaseNBT(new Vector3(100.5, 51, 100.5)));
        $this->getPlayerData($player->getName())->npc->setPlayer($player);
    }

    public function deleteGame($player){
    	//TODO: Change the map name
    	$this->getPlugin()->deleteMap($player, "mapName");
    }

    public function giveItems($player, string $phase){
    	$inv = $player->getInventory();
        $inv->clearAll();
    	switch($phase){
    		case "stopped":
                $inv->setItem(0, Item::get(267, 0, 1)->setCustomName("§r§7Start the Game"));
                if($this->plugin->config["canSpectate"]){
                    $inv->setItem(3, Item::get(345, 0, 1)->setCustomName("§r§7Spectate"));
                }
                $inv->setItem(4, Item::get(347, 0, 1)->setCustomName("§r§7Settings"));
                if($this->plugin->config["backToHub"]["disabled"] == false){
                    $inv->setItem(5, Item::get(351, 1, 1)->setCustomName("§r§7Go back to hub"));
                }
                
    			break;

    		case "game":
                $inv->setItem(0, Item::get(24, 0, 64));
                $inv->setItem(8, Item::get(351, 1, 1)->setCustomName("§r§7Stop the Game"));
                $inv->setItem(4, Item::get(351, 5, 1)->setCustomName("§r§7Reset Map"));
    			break;

            case "spectating":
                $inv->setItem(3, Item::get(345, 0, 1)->setCustomName("§r§7Spectate Somebody Else"));
                $inv->setItem(4, Item::get(267, 0, 1)->setCustomName("§r§7Go back to your island"));
                if($this->plugin->config["backToHub"]["disabled"] == false){
                    $inv->setItem(5, Item::get(351, 1, 1)->setCustomName("§r§7Go back to hub"));
                }
                break;
    	}
    }

    public function startHits($player){
        if($this->getPlayerData($player->getName())->getInGame() == false){
            return;
        }
        //first hit
        $this->hitPlayer($player);
        $hits = 1;
        while($hits < $this->getPlayerData($player->getName())->getSettings("hitamount")){
            $hits++;

            if($hits == (int)$this->getPlayerData($player->getName())->getSettings("hitamount")){
                $type = "last";
            } else {
                $type = "normal";
            }
            $this->plugin->getScheduler()->scheduleDelayedTask(new task\HitTask($this->plugin, $player, $type), (int)$this->getPlayerData($player->getName())->getSettings("hitdelay") * ($hits - 1));
            
        }
    }

    public function doSecondHit($player, $type){
        if($this->getPlayerData($player->getName())->getInGame() == false){
            return;
        }
        //second hit
        $this->hitPlayer($player);
        //little trick so when player is stopping and starting game hits will not multiple
        $this->getPlayerData($player->getName())->hitSession++;
        if($type == "last"){
            $this->plugin->getScheduler()->scheduleDelayedTask(new task\WaitBetweenTask($this->plugin, $player, $this->getPlayerData($player->getName())->hitSession), mt_rand(2 * 20, 3 * 20));
        }
    }

    //Hit the player by the npc
    public function hitPlayer($player){
        $npc = $this->getPlayerData($player->getName())->getNPC();
        //Default kb from pocketmine
        $kb = $this->getPlayerData($player->getName())->getSettings("kb")/10;
        //attacker, target, cause, dmg, modifiers, kb
        $ev = new EntityDamageByEntityEvent($npc, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 0, [], $kb);
        $player->attack($ev);
    }

    public function resetMap($player){
        $level = $player->level;
        for($x = 50; $x <= 150; $x++){
            for($y = 30; $y <= 80; $y++){
                for($z = 50; $z <= 150; $z++){
                    if($level->getBlockAt($x, $y, $z)->getId() == 24){
                        $level->setBlock(new Vector3($x, $y, $z), Block::get(Block::AIR), true, false);
                    }
                }
            }
        }
    }

    public function openSettings($player){
        //$api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        //$form = $api->createCustomForm(function (Player $player, ?array $data){
        $form = new CustomForm(function (Player $player, $data) {    
            if ($data === null) {
                return;
            }

            $this->getPlayerData($player->getName())->setSettings("kb", $data[0]);
            $this->getPlayerData($player->getName())->setSettings("hitdelay", $data[1]);
            $this->getPlayerData($player->getName())->setSettings("hitamount", $data[2]);
                
            $player->sendMessage("§aSuccessfully saved your settings for the training!");       
                    
        });
        $form->setTitle("§8SETTINGS");
        //$form->addContent("§7Feel free to edit your training!\n\n§8Default Settings:\n§8Knockback 4\n§8Hit Delay 5");
        $form->addSlider("Knockback", $this->plugin->config["gameSettingsValues"]["knockback"]["min"], $this->plugin->config["gameSettingsValues"]["knockback"]["max"], $this->plugin->config["gameSettingsValues"]["knockback"]["valuePerStep"], (int)$this->getPlayerData($player->getName())->getSettings("kb"));
        $form->addSlider("Hit Delay (ticks)", $this->plugin->config["gameSettingsValues"]["hitDelay"]["min"], $this->plugin->config["gameSettingsValues"]["hitDelay"]["max"], $this->plugin->config["gameSettingsValues"]["hitDelay"]["valuePerStep"], (int)$this->getPlayerData($player->getName())->getSettings("hitdelay"));
        $form->addSlider("Hit Amount", $this->plugin->config["gameSettingsValues"]["hitAmount"]["min"], $this->plugin->config["gameSettingsValues"]["hitAmount"]["max"], $this->plugin->config["gameSettingsValues"]["hitAmount"]["valuePerStep"], (int)$this->getPlayerData($player->getName())->getSettings("hitamount"));

        $form->sendToPlayer($player);
    }

    public function openSpectatingList($player){
        //$api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        //$form = $api->createSimpleForm(function (Player $player, $data){
        $form = new SimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null) {
                return;
            }
            foreach($this->plugin->getServer()->getLevelByName($this->getPlayerData($player->getName())->getMap()."-".$player->getName())->getPlayers() as $p){
                if($player->getName() !== $p->getName()){
                    $pos = new Position(100, 52, 100, $this->plugin->getServer()->getLevelByName($this->getPlayerData($p->getName())->getMap()."-".$p->getName()));
                    $p->teleport($pos);
                    $p->setGamemode(0);
                    $this->getPlayerData($p->getName())->setSpectating(false);
                    $p->sendMessage("§cTeleporting back to your island, because the player you were spectating has started spectating somebody as well!");
                    $this->giveItems($p, "stopped");
                }
            }
            foreach($this->getPlayerData($player->getName())->canSpectatePlayers as $p){
                if($result == $p){
                    if($this->plugin->getServer()->getPlayer($p) !== null){
                        $map = $this->getPlayerData($p)->getMap();
                        $pos = new Position(100, 52, 100, $this->getPlugin()->getServer()->getLevelByName($map."-".$p));
                        $player->teleport($pos);
                        $player->setGamemode(3);
                        $this->getPlayerData($player->getName())->setSpectating(true);
                        $this->giveItems($player, "spectating");
                    } else {
                        $player->sendMessage("§cThe player is offline");
                    }
                    
                }
            }
            $player->canSpectatePlayers = [];

        });
        $form->setTitle("§8SPECTATING");
        $players = [];
        foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
            if($this->getPlayerData($p->getName())->getSpectating() == false and $player->getName() !== $p->getName()){
                $form->addButton("§8".$p->getName()."\n§7Click to visit", 0, "", $p->getName());
                array_push($players, $p->getName());
            } 
        }
        $this->getPlayerData($player->getName())->canSpectatePlayers = $players;
        
        $form->sendToPlayer($player);
    }

    public function getPlayerData(string $playerName){
        return $this->plugin->getPlayerData($playerName);
    }

    public function getPlugin(){
    	return $this->plugin;
    }

}

