<?php
declare(strict_types=1);

namespace BreathTakinglyBinary\minigames\task;


use BreathTakinglyBinary\minigames\API;
use BreathTakinglyBinary\minigames\Arena;
use pocketmine\scheduler\Task;

class ArenaResetTask extends Task{
    /** @var Arena */
    private $arena;

    /**
     * @param Arena $arena
     */
    public function __construct(Arena $arena){
        $this->arena = $arena;
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun(int $currentTick){
        API::resetArena($this->arena);
    }
}