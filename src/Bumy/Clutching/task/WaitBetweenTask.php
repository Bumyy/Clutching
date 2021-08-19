<?php

namespace Bumy\Clutching\task;

use Bumy\Clutching\Main;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class WaitBetweenTask extends Task{

    public $plugin;
    public $player;
    public $hitSession;

    public function __construct(Main $plugin, Player $player, $hitSession){
        $this->plugin = $plugin;
        $this->player = $player;
        $this->hitSession = $hitSession;
    }

    public function onRun(int $currentTick){
    	if($this->hitSession == $this->plugin->getPlayerData($this->player->getName())->hitSession){
    		$this->plugin->getArenaManager()->startHits($this->player);
    	}
    }
}