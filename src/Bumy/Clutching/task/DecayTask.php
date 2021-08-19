<?php

namespace Bumy\Clutching\task;

use Bumy\Clutching\Main;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\math\Vector3;
use pocketmine\block\Air;

class DecayTask extends Task{

    public $x;
    public $y;
    public $z;
    public $player;


    public function __construct($player, $block, $x, $y, $z)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
        $this->player = $player;

    }


    public function onRun(int $tick)
    {
        $level = $this->player->level;
        if (!$level == null) {
            if($this->x !== null && $this->y !== null && $this->z !== null) {
                $level->setBlock(new Vector3($this->x, $this->y, $this->z), new Air(), false);
            }
        }
    }
}