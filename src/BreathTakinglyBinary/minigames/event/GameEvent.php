<?php
declare(strict_types=1);

namespace BreathTakinglyBinary\minigames\event;


use BreathTakinglyBinary\minigames\Game;
use pocketmine\event\Event;

class GameEvent extends Event{

    public static $handlerList = null;

    /** @var Game */
    private $game;

    public function __construct(Game $game){
        $this->game = $game;
    }

    /**
     * @return Game
     */
    public function getGame() : Game{
        return $this->game;
    }

    public function getName() : string{
        return $this->game->getName();
    }

}