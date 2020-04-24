<?php

namespace BreathTakinglyBinary\minigames;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\format\io\LevelProvider;
use pocketmine\level\format\io\LevelProviderManager;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Color;
use pocketmine\utils\TextFormat;
use RuntimeException;
use BreathTakinglyBinary\minigames\event\DefaultSettingsListener;
use BreathTakinglyBinary\minigames\event\StopGameEvent;
use BreathTakinglyBinary\minigames\task\ArenaAsyncCopyTask;

class API{
    /** @var Game */
    private static $game;
    /** @var GeneratorGameVoid */
    public static $generator;

    /**
     * Returns all world names (!NOT FOLDER NAMES, level.dat entries) of valid levels in "/worlds"
     * @return string[]
     * @throws RuntimeException
     */
    public static function getAllWorlds() : array{
        $worldNames = [];
        $glob = glob(Server::getInstance()->getDataPath() . "worlds/*", GLOB_ONLYDIR);
        if($glob === false) return [];
        /*//hack to fix "File in use" with leveldb. TODO find proper replacement
        return array_map(function ($path) {
            return basename($path);
        }, $glob);*/
        foreach($glob as $path){
            $path .= DIRECTORY_SEPARATOR;
            if(Server::getInstance()->isLevelLoaded(basename($path))){
                $worldNames[] = Server::getInstance()->getLevelByName(basename($path))->getName();
                continue;
            }
            $provider = LevelProviderManager::getProvider($path);
            if($provider !== null){
                /** @var LevelProvider $c */
                $c = (new $provider($path));
                $worldNames[] = $c->getName();
                unset($provider);
            }
        }
        sort($worldNames);

        return $worldNames;
    }

    /**
     * Stops the registered game using the API
     *
     * @param Game|string $plugin a plugin or plugin name
     *
     * @return bool
     * @throws \ReflectionException
     */
    public static function stop(){
        if(!self::$game instanceof Game){
            throw new RuntimeException("API::stop called when no game was registered.");
        }
        $server = Server::getInstance();
        $ev = new StopGameEvent(self::$game);
        $ev->call();
        foreach(self::$game->getArenas() as $arena){
            $arena->stopArena();
        }
        if(DefaultSettingsListener::getRegistrant() === self::$game){
            DefaultSettingsListener::unregister();
        }
        $server->broadcastMessage(TextFormat::GREEN . "Stopped " . ($ev->getName() ?? "nameless game"));

        return true;
    }

    public static function resetArena(Arena $arena){
        $level = $arena->getLevel();
        $levelname = $arena->getLevelName();
        $server = Server::getInstance();

        if($arena->getState() !== Arena::STOP) $arena->stopArena();

        if($server->isLevelLoaded($levelname)){
            if(method_exists($arena->getOwningGame(), "removeEntityOnReset"))
                foreach(array_filter($level->getEntities(), function(Entity $entity) use ($arena){
                    return $arena->getOwningGame()->removeEntityOnArenaReset($entity);
                }) as $entity){
                    $level->removeEntity($entity);
                }
            $server->getLogger()->notice('Level ' . $levelname . ($server->unloadLevel($server->getLevelByName($levelname)) ? ' successfully' : ' NOT') . ' unloaded!');
            $path1 = $arena->getOwningGame()->getDataFolder();

            $server->getAsyncPool()->submitTask(new ArenaAsyncCopyTask($path1, $server->getDataPath(), $levelname, $arena->getOwningGame()->getName()));
        }
    }

    public static function copyr($source, $dest){
        // Check for symlinks
        if(is_link($source)){
            return symlink(readlink($source), $dest);
        }

        // Simple copy for a file
        if(is_file($source)){
            return copy($source, $dest);
        }

        // Make destination directory
        if(!is_dir($dest)){
            @mkdir($dest, 0777, true);
        }

        // Loop through the folder
        $dir = dir($source);
        while(false !== $entry = $dir->read()){
            // Skip pointers
            if($entry == '.' || $entry == '..'){
                continue;
            }

            // Deep copy directories
            self::copyr("$source/$entry", "$dest/$entry");
        }

        // Clean up
        $dir->close();

        return true;
    }

    /** Gets the team a player is in
     *
     * @param Player $player
     *
     * @return null|Team
     */
    public static function getTeamOfPlayer(Player $player) : ?Team{
        $arena = self::getArenaOfPlayer($player);
        if(is_null($arena)) return null;

        return $arena->getTeamByPlayer($player);
    }

    /** Gets the team a player is in
     *
     * @param Game   $game
     * @param Level  $level
     * @param string $color
     *
     * @return null|Team
     */
    public static function getTeamByColor(Game $game, Level $level, string $color) : ?Team{
        $arena = self::getArenaByLevel($level);
        if(is_null($arena)) return null;

        return $arena->getTeamByColor($color);
    }

    /** Gets the arena a player is in
     *
     * @param Player $player
     *
     * @return Arena | null
     */
    public static function getArenaOfPlayer(Player $player) : ?Arena{
        foreach(self::$game->getArenas() as $arena){
            if($arena->inArena($player)) return $arena;
        }

        return null;
    }

    /**
     * @param Player      $gamer
     * @param null|Plugin $game if null, it will check for any game
     *
     * @return bool
     */
    public static function isPlaying(Player $gamer, ?Plugin $game = null){
        return /*!is_null(self::getTeamOfPlayer($gamer)) && */
            !is_null($arena = self::getArenaByLevel($gamer->getLevel())) && $arena->inArena($gamer);
    }

    /**
     * Register a plugin as a game
     *
     * @param Plugin|Game $game
     *
     * @throws \ReflectionException
     */
    public static function registerGame(Game $game){
        if(self::$game instanceof Game){
            throw new RuntimeException("Tried to register " . $game->getName() . " while " . self::$game->getName() . " was still registered!");
        }
        //Generic handler for the DefaultSettings
        if(!DefaultSettingsListener::isRegistered())
            DefaultSettingsListener::register($game);
        try{
            self::$generator = new GeneratorGameVoid();
        }catch(\InvalidArgumentException $e){
        };
        self::$game = $game;
    }

    public static function getGame(){
        return self::$game;
    }

    /**
     * @param null|Level $level
     *
     * @return bool
     */
    public static function isArena(?Level $level){
        if(is_null($level)) return false;

        return self::getArenaByLevel($level) instanceof Arena;
    }

    /**
     * @param Level       $level
     *
     * @return Arena|null
     */
    public static function getArenaByLevel(Level $level) : ?Arena{
        foreach(self::$game->getArenas() as $arena){
            if($arena->getLevel()->getName() === $level->getName()){
                return $arena;
            }
        }

        return null;
    }

    /**
     * @param Plugin|Game $game
     * @param string      $levelname
     *
     * @return Arena|null
     */
    public static function getArenaByLevelName(?Plugin $game, string $levelname) : ?Arena{
        $level = Server::getInstance()->getLevelByName($levelname);
        if(is_null($level)) return null;

        return self::getArenaByLevel($level);
    }

    /**
     * @param string $color a TextFormat color constant
     *
     * @return Color
     */
    public static function colorFromTextFormat($color) : Color{
        [$r, $g, $b] = str_split(ltrim(str_replace('>', '', str_replace('<span style=color:#', '', TextFormat::toHTML($color))), '#'));

        return new Color(...array_map('hexdec', [$r . $r, $g . $g, $b . $b]));
    }

    /**
     * Returns a matching meta value for a TextFormat color constant
     *
     * @param string $color a TextFormat constant
     *
     * @return int Meta value, returns -1 if failed
     */
    public static function getMetaByColor(string $color){
        switch($color){
            case TextFormat::BLACK:
                return 15;
            case TextFormat::DARK_BLUE:
                return 11;
            case TextFormat::DARK_GREEN :
                return 13;
            case TextFormat::DARK_AQUA :
            case TextFormat::AQUA :
                return 9;
            case TextFormat::DARK_RED :
            case TextFormat::RED :
                return 14;
            case TextFormat::DARK_PURPLE :
                return 10;
            case TextFormat::GOLD :
                return 1;
            case TextFormat::GRAY :
                return 8;
            case TextFormat::DARK_GRAY :
                return 7;
            case TextFormat::BLUE :
                return 3;
            case TextFormat::GREEN :
                return 5;
            case TextFormat::LIGHT_PURPLE :
                return 2;
            case TextFormat::YELLOW :
                return 4;
            case TextFormat::WHITE :
                return 0;
            default:
                return -1;
        }
    }

    /**
     * Returns a matching TextFormat color constant from meta values
     *
     * @param int $meta
     *
     * @return string $color a TextFormat constant
     */
    public static function getColorByMeta(int $meta){
        switch($meta){
            case 0:
            default:
                return TextFormat::WHITE;
            case 1:
                return TextFormat::GOLD;
            case 2:
                return TextFormat::LIGHT_PURPLE;
            case 3:
                return TextFormat::BLUE;
            case 4:
                return TextFormat::YELLOW;
            case 5:
                return TextFormat::GREEN;
            case 7:
                return TextFormat::DARK_GRAY;
            case 8:
                return TextFormat::GRAY;
            case 9:
                return TextFormat::AQUA;
            case 10:
                return TextFormat::DARK_PURPLE;
            case 11:
                return TextFormat::DARK_BLUE;
            case 13:
                return TextFormat::DARK_GREEN;
            case 14:
                return TextFormat::RED;
            case 15:
                return TextFormat::BLACK;
        }
    }

    public static function setCustomColor(Item $item, Color $color){
        if(($hasTag = $item->hasCompoundTag())){
            $tag = $item->getNamedTag();
        }else{
            $tag = new CompoundTag("", []);
        }
        $tag->setInt("customColor", self::toRGB($color));
        $item->setCompoundTag($tag);

        return $item;
    }

    /**
     * Returns an RGB 32-bit colour value.
     *
     * @param Color $color
     *
     * @return int
     */
    public static function toRGB(Color $color) : int{
        return ($color->getR() << 16) | ($color->getG() << 8) | $color->getB() & 0xffffff;
    }
}