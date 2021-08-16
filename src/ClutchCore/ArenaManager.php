<?php

declare(strict_types=1);

namespace ClutchCore;

use ClutchCore\Main;
use ClutchCore\CustomPlayer;
use ClutchCore\task\HitTask;
use ClutchCore\task\WaitBetweenTask;

use pocketmine\level\Position;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\Player;

use jojoe77777\FormAPI;

class ArenaManager{

	public $plugin;

    //make this from config
    public $maps = ["DefaultMap", "DefaultMap"];

	public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function createGame(CustomPlayer $player){
        //getting a random map from the maps array
    	$map = $this->maps[mt_rand(0, count($this->maps) - 1)];
        $player->setMap($map);

    	$this->getPlugin()->createMap($player, $map);

    	$pos = new Position(100, 52, 100, $this->getPlugin()->getServer()->getLevelByName($map."-".$player->getName()));
    	$player->teleport($pos);
        $player->npc = Entity::createEntity("CustomNPC", $player->level, Entity::createBaseNBT(new Vector3(100.5, 51, 100.5)));
        $player->npc->setPlayer($player);
    }

    public function deleteGame(CustomPlayer $player){
    	//TODO: Change the map name
    	$this->getPlugin()->deleteMap($player, "mapName");
    }

    public function giveItems(CustomPlayer $player, string $phase){
    	$inv = $player->getInventory();
        $inv->clearAll();
    	switch($phase){
    		case "stopped":
                $inv->setItem(0, Item::get(267, 0, 1)->setCustomName("§r§7Start the Game"));
                $inv->setItem(3, Item::get(345, 0, 1)->setCustomName("§r§7Spectate"));
                $inv->setItem(4, Item::get(347, 0, 1)->setCustomName("§r§7Settings"));
                $inv->setItem(5, Item::get(351, 1, 1)->setCustomName("§r§7Go back to hub"));
    			break;

    		case "game":
                $inv->setItem(0, Item::get(24, 0, 64));
                $inv->setItem(8, Item::get(351, 1, 1)->setCustomName("§r§7Stop the Game"));
                $inv->setItem(4, Item::get(351, 5, 1)->setCustomName("§r§7Reset Map"));
    			break;

            case "spectating":
                $inv->setItem(3, Item::get(345, 0, 1)->setCustomName("§r§7Spectate Somebody Else"));
                $inv->setItem(4, Item::get(267, 0, 1)->setCustomName("§r§7Go back to your island"));
                $inv->setItem(5, Item::get(351, 1, 1)->setCustomName("§r§7Go back to hub"));
                break;
    	}
    }

    public function startHits(CustomPlayer $player){
        if($player->getInGame() == false){
            return;
        }
        //first hit
        $this->hitPlayer($player);
        $hits = 1;
        while($hits < $player->getSettings("hitamount")){
            $hits++;

            if($hits == (int)$player->getSettings("hitamount")){
                $type = "last";
            } else {
                $type = "normal";
            }
            $this->plugin->getScheduler()->scheduleDelayedTask(new task\HitTask($this->plugin, $player, $type), (int)$player->getSettings("hitdelay") * ($hits - 1));
            
        }
    }

    public function doSecondHit(CustomPlayer $player, $type){
        if($player->getInGame() == false){
            return;
        }
        //second hit
        $this->hitPlayer($player);
        //little trick so when player is stopping and starting game hits will not multiple
        $player->hitSession++;
        if($type == "last"){
            $this->plugin->getScheduler()->scheduleDelayedTask(new task\WaitBetweenTask($this->plugin, $player, $player->hitSession), mt_rand(2 * 20, 3 * 20));
        }
    }

    //Hit the player by the npc
    public function hitPlayer(CustomPlayer $player){
        $npc = $player->getNPC();
        //Default kb from pocketmine
        $kb = $player->getSettings("kb")/10;
        //attacker, target, cause, dmg, modifiers, kb
        $ev = new EntityDamageByEntityEvent($npc, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 0, [], $kb);
        $player->attack($ev);
    }

    public function resetMap(CustomPlayer $player){
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

    public function openSettings(CustomPlayer $player){
        $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createCustomForm(function (Player $player, ?array $data){
            
        if ($data === null) {
            return;
        }

        $player->setSettings("kb", $data[0]);
        $player->setSettings("hitdelay", $data[1]);
        $player->setSettings("hitamount", $data[2]);
            
        $player->sendMessage("§aSuccessfully saved your settings for the training!");       
                
        });
        $form->setTitle("§8SETTINGS");
        //$form->addContent("§7Feel free to edit your training!\n\n§8Default Settings:\n§8Knockback 4\n§8Hit Delay 5");
        $form->addSlider("Knockback", 1, 20, 1, (int)$player->getSettings("kb"));
        $form->addSlider("Hit Delay (ticks)", 1, 10, 1, (int)$player->getSettings("hitdelay"));
        $form->addSlider("Hit Amount", 1, 10, 1, (int)$player->getSettings("hitamount"));

        $form->sendToPlayer($player);
    }

    public function openSpectatingList(CustomPlayer $player){
        $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, $data){
        $result = $data;
        if ($result === null) {
            return;
        }
        foreach($this->plugin->getServer()->getLevelByName($player->getMap()."-".$player->getName())->getPlayers() as $p){
            if($player->getName() !== $p->getName()){
                $pos = new Position(100, 52, 100, $this->plugin->getServer()->getLevelByName($p->getMap()."-".$p->getName()));
                $p->teleport($pos);
                $p->setGamemode(0);
                $p->setSpectating(false);
                $p->sendMessage("§cTeleporting back to your island, because the player you were spectating has started spectating somebody as well!");
                $this->giveItems($p, "stopped");
            }
        }
        foreach($player->canSpectatePlayers as $p){
            if($result == $p){
                if($this->plugin->getServer()->getPlayer($p) !== null){
                    $map = $this->plugin->getServer()->getPlayer($p)->getMap();
                    $pos = new Position(100, 52, 100, $this->getPlugin()->getServer()->getLevelByName($map."-".$p));
                    $player->teleport($pos);
                    $player->setGamemode(3);
                    $player->setSpectating(true);
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
            if($p->getSpectating() == false and $player->getName() !== $p->getName()){
                $form->addButton("§8".$p->getName()."\n§7Click to visit", 0, "", $p->getName());
                array_push($players, $p->getName());
            } 
        }
        $player->canSpectatePlayers = $players;
        
        $form->sendToPlayer($player);
    }

    public function getPlugin(){
    	return $this->plugin;
    }

}

