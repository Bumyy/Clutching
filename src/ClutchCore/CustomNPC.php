<?php

declare(strict_types=1);

namespace ClutchCore;

use pocketmine\Player;
use pocketmine\entity\Living;
use ClutchCore\CustomPlayer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\DataPropertyManager;
use pocketmine\entity\AttributeMap;
use pocketmine\entity\Attribute;
use pocketmine\level\Level;

class CustomNPC extends Living {

    public $plugin;
    public $player;

    public const NETWORK_ID = 91;

    /**
     * @param Main $main
     * @param CustomPlayer $player
     * @return void
     */

    public function __construct(Level $level, CompoundTag $nbt){
        $this->width = 0.01;
        $this->height = 0.01;
        parent::__construct($level, $nbt);
        //$this->namedtag = new CompoundTag();
        $this->setNametag("§r§8Clutch Trainer");
        $this->setMaxHealth(20);
        $this->setHealth(20);
        parent::initEntity();
        
        //What is this? - Bumy
        //$this->generateRandomPosition();
        //Idk tbh, its in the PRAC core for bots - Jack
    }

    public function getName() : string{
        return "CustomNPC";
    }

    public function getPlayer(){
        return $this->player;
    }

    public function getNameTag():string{
        return "CustomNPC";
    }

    public function setPlayer(CustomPlayer $player) : void{
        $this->player = $player;
    }
}
