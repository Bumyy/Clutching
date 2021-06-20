<?php

namespace ClutchCore\task;

use ClutchCore\Main;
use pocketmine\Player;
use ClutchCore\CustomPlayer;
use pocketmine\scheduler\Task;

class HitTask extends Task{

    public $plugin;
    public $player;
    public $type;

    public function __construct(Main $plugin, CustomPlayer $player, $type){
        $this->plugin = $plugin;
        $this->player = $player;
        $this->type = $type;
    }

    public function onRun(int $currentTick){
        $this->plugin->getArenaManager()->doSecondHit($this->player, $this->type);
    }
}