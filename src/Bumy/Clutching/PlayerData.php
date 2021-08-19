<?php

declare(strict_types=1);

namespace Bumy\Clutching;

use Bumy\Clutching\Main;

class PlayerData{

    public $plugin;

    public $playerName;

    //Declares if the hits are coming or the game is stopped
    public $inGame = false;
    public $npc;

    public $hitSession = 0;
    public $map;

    public $settings = ["kb" => 1, "hitdelay" => 1, "hitamount" => 1];

    public $spectating = false;

    public $canSpectatePlayers;

    public function __construct(Main $plugin, $playerName){
        $this->plugin = $plugin;
        $this->playerName = $playerName;

        $this->settings["kb"] = $this->plugin->config["gameSettingsValues"]["knockback"]["default"];

        $this->settings["hitdelay"] = $this->plugin->config["gameSettingsValues"]["hitDelay"]["default"];

        $this->settings["hitamount"] = $this->plugin->config["gameSettingsValues"]["hitAmount"]["default"];
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
