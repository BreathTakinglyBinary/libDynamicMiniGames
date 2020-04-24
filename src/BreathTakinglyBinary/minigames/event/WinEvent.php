<?php

namespace BreathTakinglyBinary\minigames\event;

use BreathTakinglyBinary\minigames\Arena;
use BreathTakinglyBinary\minigames\Game;
use BreathTakinglyBinary\minigames\Team;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class WinEvent extends GameEvent{

    /** @var Arena */
    private $arena;
    private $winner;

    /**
     * TeamWinEvent constructor.
     *
     * @param Game        $plugin
     * @param Arena       $arena
     * @param Team|Player $winner
     */
    public function __construct(Game $plugin, Arena $arena, $winner){
        parent::__construct($plugin);
        $this->arena = $arena;
        $this->winner = $winner;
    }

    public function announce(){
        $prefix = $this->winner instanceof Player ? "Player " : "Team ";
        Server::getInstance()->broadcastTitle(TextFormat::GREEN . $prefix . $this->winner->getName(), TextFormat::GREEN . ' has won the game ' . $this->arena->getOwningGame()->getPrefix() . '!', -1, -1, -1, $this->getInitialPlayers());
        Server::getInstance()->broadcastMessage(TextFormat::GREEN . $prefix . $this->winner->getName() . TextFormat::GREEN . ' has won the game ' . $this->arena->getOwningGame()->getPrefix() . '!', $this->getInitialPlayers());
    }

    /**
     * @return Player[]
     */
    public function getWinningPlayers(){
        if($this->winner instanceof Player) return [$this->winner];
        else return $this->winner->getInitialPlayers();
    }

    /**
     * @return Player[]
     */
    public function getInitialPlayers(){
        $arena = $this->arena;
        $teams = $arena->getTeams();
        $originals = [];
        foreach($teams as $team){
            $originals = array_merge($originals, $team->getInitialPlayers());
        }

        return array_filter($originals, function($player) : bool{
            return $player instanceof Player && $player->isOnline();
        });
    }
}