<?php

declare(strict_types = 1);

namespace ClutchCore;

use pocketmine\plugin\PluginBase;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\entity\EntityDespawnEvent;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\level\Position;

use ClutchCore\CustomPlayer;
use ClutchCore\ArenaManager;
use ClutchCore\CustomNPC;
use ClutchCore\task\WaitBetweenTask;
use ClutchCore\task\DecayTask;


class Main extends PluginBase implements Listener {

    public $arenaManager;

    public function onEnable() {

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->arenaManager = new ArenaManager($this);
        Entity::registerEntity(CustomNPC::class, true);

        //create task or not

        #loading all worlds
        foreach(glob($this->getServer()->getDataPath() . "worlds/*") as $world) {
            $world = str_replace($this->getServer()->getDataPath() . "worlds/", "", $world);
            if($this->getServer()->isLevelLoaded($world)){
                continue;
            }
            $this->getServer()->loadLevel($world);
        }

        $this->getServer()->getCommandMap()->registerAll("astral", [
            #new commands\Test("test", $this),
        ]);
    }

    /**
     * @param PlayerCreationEvent $event
     * @return void
     */
    public function onPlayerCreation(PlayerCreationEvent $event) : void{
        $event->setPlayerClass(CustomPlayer::class);

    }

    /**
     * @priority HIGHEST
     */
    public function onChangeSkin(PlayerChangeSkinEvent $event){
        $player = $event->getPlayer();
        $player->sendMessage("Unfortunately, you cannot change your skin here.");
        $event->setCancelled();

    }

    /**
     * @param PlayerJoinEvent $event
     * @return void
     */
    public function onPlayerJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();// ready
        $player->load($this);
        $this->getArenaManager()->createGame($player);
        $event->setJoinMessage("");
        $player->sendMessage("§7Welcome to Clutch Practice!\nJoin our discord: https://astralclient.net/discord/");
        $this->getArenaManager()->giveItems($player, "stopped");
        $player->setGamemode(0);


        $p = $event->getPlayer();
        $n = $p->getName();
        $event->setJoinMessage("§r§d+§r§a $n");
    }

    public function onExhaust(PlayerExhaustEvent $event){
        $event->setCancelled();
    }

    /**
     * @param PlayerQuitEvent $event
     * @return void
     */
    public function onPlayerLeave(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        foreach($this->getServer()->getLevelByName($player->getMap()."-".$player->getName())->getPlayers() as $p){
            $pos = new Position(100, 52, 100, $this->getServer()->getLevelByName($p->getMap()."-".$p->getName()));
            $p->teleport($pos);
            $p->setGamemode(0);
            $p->setSpectating(false);
            $p->sendMessage("§cTeleporting back to your island, because the player you were spectating has left the game!");
            $this->getArenaManager()->giveItems($p, "stopped");
        }
        //delete game and map
        $player->setInGame(false);

        $p = $event->getPlayer();
        $n = $p->getName();
        $event->setQuitMessage("§r§c-§r§c $n");
        $this->deleteMap($player, $player->getMap());

    }

    public function onEntityDamageEvent(EntityDamageEvent $event){
        //cancelling customnpc hit
        if($event instanceof EntityDamageByEntityEvent){
            if($event->getEntity() instanceof CustomNPC){
                $event->setCancelled();
            }
        }

        if($event->getCause() == EntityDamageEvent::CAUSE_FALL){
            $event->setCancelled();
        }
    }

    public function onInteractEvent(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $name = $event->getItem()->getCustomName();
        switch($name){
            case "§r§7Start the Game":
                $player->setInGame(true);
                $player->hitSession++;
                $this->getScheduler()->scheduleDelayedTask(new task\WaitBetweenTask($this, $player, $player->hitSession), mt_rand(2 * 20, 3 * 20));
                $player->sendMessage("§aYou started the game!");
                $player->sendPopup("§aPrepare for the hits!");
                $this->getArenaManager()->giveItems($player, "game");
                break;

            case "§r§7Settings":
                //$player->sendMessage("§7Coming soon...");
                $this->getArenaManager()->openSettings($player);
                break;

            case "§r§7Stop the Game":
                $player->setInGame(false);
                $player->sendMessage("§cYou stopped the game!");
                $this->getArenaManager()->giveItems($player, "stopped");
                $pos = new Position(100, 52, 100, $player->getLevel());
                $player->teleport($pos);
                $this->getArenaManager()->resetMap($player);
                break;

            case "§r§7Reset Map":
                $this->getArenaManager()->resetMap($player);
                $player->sendMessage("§cMap resetted!");
                break;

            case "§r§7Go back to hub":
                //$player->transfer("pvp.astralclient.net", 19132);
                break;

            case "§r§7Spectate Somebody Else":
            case "§r§7Spectate":
                $this->getArenaManager()->openSpectatingList($player);
                break;

            case "§r§7Go back to your island":
                $map = $player->getMap();
                $pos = new Position(100, 52, 100, $this->getServer()->getLevelByName($map."-".$player->getName()));
                $player->teleport($pos);
                $player->setGamemode(0);
                $player->setSpectating(false);
                $this->getArenaManager()->giveItems($player, "stopped");
                break;
        }
    }

    public function onPlayerMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        if($player->getY() < 40) {
            $pos = new Position(100, 52, 100, $player->getLevel());
            $player->teleport($pos);
            if ($player->getIngame()) {
                $this->getArenaManager()->resetMap($player);
                $this->getArenaManager()->giveItems($player, "game");
            }elseif($player->getIngame(false)){
                $this->getArenaManager()->giveItems($player, "stopped");
            }elseif($player->getSpectating(true)){
                $this->getArenaManager()->giveItems($player, "spectating");
            }
        }
    }

    public function decayTask(BlockPlaceEvent $e)
    {
        $block = $e->getBlock();
        $player = $e->getPlayer();
        $x = $block->getX();
        $y = $block->getY();
        $z = $block->getZ();

        if ($player->getIngame(true)) {
            $this->getArenaManager()->getPlugin()->getScheduler()->scheduleDelayedTask(new DecayTask($player, $block, $x, $y, $z), 200);
        }
    }

    public function onBreak(BlockBreakEvent $event){
        $event->setCancelled();
    }

    /**
     * @param CustomPlayer $player
     * @param $folderName
     * @return void
     */
    public function createMap(CustomPlayer $player, $folderName){
        $mapname = $folderName."-".$player->getName();
      
        $zipPath = $this->getServer()->getDataPath() . "plugin_data/ClutchCore/" .  $folderName . ".zip";

        if(file_exists($this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $mapname)){
            $this->deleteMap($player, $folderName);
        }
      
        $zipArchive = new \ZipArchive();
        if($zipArchive->open($zipPath) == true){
            $zipArchive->extractTo($this->getServer()->getDataPath() . "worlds");
            $zipArchive->close();
          $this->getLogger()->notice("Zip Object created!");
        } else {
          $this->getLogger()->notice("Couldn't create Zip Object!");
        }
        
        rename($this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $folderName, $this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $mapname);
        $this->getServer()->loadLevel($mapname);
        return $this->getServer()->getLevelByName($mapname);
    }
    
    /**
     * @param CustomPlayer $player
     * @param $folderName
     * @return void
     */            
    public function deleteMap(CustomPlayer $player, $folderName) : void{
        $mapName = $folderName."-".$player->getName();
        if(!$this->getServer()->isLevelGenerated($mapName)) {
            
            return;
        }

        if(!$this->getServer()->isLevelLoaded($mapName)) {
            
            return;
        }

        $this->getServer()->unloadLevel($this->getServer()->getLevelByName($mapName));
        $folderName = $this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $mapName;
        $this->removeDirectory($folderName);
        
         $this->getLogger()->notice("World has been deleted for player called ".$player->getName());
      
    }

    public function removeDirectory($path) {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
        return;
    }

    public function getArenaManager(){
        return $this->arenaManager;
    }
               
}
