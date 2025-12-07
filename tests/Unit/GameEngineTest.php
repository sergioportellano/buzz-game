<?php

use PHPUnit\Framework\TestCase;

class GameEngineTest extends TestCase
{
    protected $gameEngine;

    protected function setUp(): void
    {
        // Initialize the GameEngine before each test
        $this->gameEngine = new GameEngine();
    }

    public function testStartGame()
    {
        $this->gameEngine->startGame();
        $this->assertTrue($this->gameEngine->isGameRunning());
    }

    public function testStopGame()
    {
        $this->gameEngine->startGame();
        $this->gameEngine->stopGame();
        $this->assertFalse($this->gameEngine->isGameRunning());
    }

    public function testAddPlayer()
    {
        $player = new Player('Player1');
        $this->gameEngine->addPlayer($player);
        $this->assertCount(1, $this->gameEngine->getPlayers());
    }

    public function testRemovePlayer()
    {
        $player = new Player('Player1');
        $this->gameEngine->addPlayer($player);
        $this->gameEngine->removePlayer($player);
        $this->assertCount(0, $this->gameEngine->getPlayers());
    }

    public function testHandlePlayerAction()
    {
        $player = new Player('Player1');
        $this->gameEngine->addPlayer($player);
        $this->gameEngine->startGame();

        $actionResult = $this->gameEngine->handlePlayerAction($player->getId(), 'buzz', []);
        $this->assertNotNull($actionResult);
    }

    public function testCalculateScores()
    {
        $player1 = new Player('Player1');
        $player2 = new Player('Player2');
        $this->gameEngine->addPlayer($player1);
        $this->gameEngine->addPlayer($player2);
        $this->gameEngine->startGame();

        // Simulate some actions and calculate scores
        $this->gameEngine->handlePlayerAction($player1->getId(), 'buzz', []);
        $this->gameEngine->handlePlayerAction($player2->getId(), 'answer', ['correct' => true]);

        $scores = $this->gameEngine->calculateScores();
        $this->assertArrayHasKey($player1->getId(), $scores);
        $this->assertArrayHasKey($player2->getId(), $scores);
    }
}