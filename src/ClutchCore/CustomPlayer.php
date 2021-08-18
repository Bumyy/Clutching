<?php

declare(strict_types=1);

namespace ClutchCore;

use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;
use pocketmine\entity\Human;
use ClutchCore\CustomNPC;

class CustomPlayer extends Player {

    public $plugin;

    //Declares if the hits are coming or the game is stopped
    public $inGame = false;
    public $npc;

    public $hitSession = 0;
    public $map;

    public $settings = ["kb" => 1, "hitdelay" => 1, "hitamount" => 1];

    public $spectating = false;

    public $canSpectatePlayers;

    /**
     * @param Main $main
     * @return void
     */
	public function load(Main $main) : void {
		$this->plugin = $main;
        //create hitting npc
        $this->settings["kb"] = $this->plugin->config["gameSettingsValues"]["knockback"]["default"];

        $this->settings["hitdelay"] = $this->plugin->config["gameSettingsValues"]["hitDelay"]["default"];

        $this->settings["hitamount"] = $this->plugin->config["gameSettingsValues"]["hitAmount"]["default"];
        
	}

    /**
     * @param EntityDamageEvent $source
     * @return void
     */
	public function attack(EntityDamageEvent $source) : void {
        parent::attack($source);
        
        if($source->isCancelled()) return;

        $this->attackTime = $this->getSettings("hitdelay");

        if($this->getGamemode() == 2 or $this->getGamemode() == 3){
            $source->setCancelled(true);
            return;
        }

        if($source instanceof EntityDamageByChildEntityEvent){
        	$damager = $source->getDamager();
        }elseif($source->getCause() === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION){
        	$damager = $source->getDamager();
        }else{
        	//$damager = $source->getDamager();
        }

        if($source->getCause() === EntityDamageEvent::CAUSE_FALL or $source->getCause() === EntityDamageEvent::CAUSE_SUFFOCATION){
            $source->setCancelled(true);
            return;
        }else{
            if($source->getCause() !== EntityDamageEvent::CAUSE_ENTITY_ATTACK and !$source instanceof EntityDamageByChildEntityEvent and $source->getCause() !== EntityDamageEvent::CAUSE_ENTITY_EXPLOSION){
                $source->setCancelled(false);
                $source->setKnockBack(0.4);
            } 
        }
    }

    public function getInGame() : bool{
        return $this->inGame;
    }

    public function setInGame(bool $bool) : void{
        $this->inGame = $bool;
    }

    public function getNPC(){
        return $this->npc;
    }

    public function setNPC($npc){
        $this->npc = $npc;
    }

    public function getMap(){
        return $this->map;
    }

    public function setMap(string $map){
        $this->map = $map;
    }

    public function getSettings($type){
        return $this->settings[$type];
    }

    public function setSettings($type, $data){
        $this->settings[$type] = $data;
    }

    public function getSpectating(){
        return $this->spectating;
    }

    public function setSpectating($data){
        $this->spectating = $data;
    }
}
