<?php

namespace ClutchCore\task;

use ClutchCore\Main;
use pocketmine\Player;
use ClutchCore\CustomPlayer;
use pocketmine\scheduler\Task;

class WaitBetweenTask extends Task{

    public $plugin;
    public $player;
    public $hitSession;

    public function __construct(Main $plugin, CustomPlayer $player, $hitSession){
        $this->plugin = $plugin;
        $this->player = $player;
        $this->hitSession = $hitSession;
    }

    public function onRun(int $currentTick){
    	if($this->hitSession == $this->player->hitSession){
    		$this->plugin->getArenaManager()->startHits($this->player);
    	}
    }
}