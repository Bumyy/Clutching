<?php

namespace Bumy\Clutching\task;

use Bumy\Clutching\Main;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class HitTask extends Task{

    public $plugin;
    public $player;
    public $type;

    public function __construct(Main $plugin, Player $player, $type){
        $this->plugin = $plugin;
        $this->player = $player;
        $this->type = $type;
    }

    public function onRun(int $currentTick){
        $this->plugin->getArenaManager()->doSecondHit($this->player, $this->type);
    }
}