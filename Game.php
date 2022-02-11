<?php

class Game
{
    public function createGame()
    {   
        return [
            "Game" => $this->generateUniqueGameID(),
            "X" => null,
            "O" => null
        ];
    }

    public function generateUniqueGameID()
    {
        return uniqid();
    }

}