<?php

class Room {
    private $id;
    private $code;
    private $creatorId;
    private $state;
    private $configuration;
    private $maxPlayers;
    private $currentRound;
    private $createdAt;
    private $startedAt;
    private $finishedAt;

    public function __construct($code, $creatorId, $maxPlayers = 8, $configuration = []) {
        $this->code = $code;
        $this->creatorId = $creatorId;
        $this->state = 'configuracion';
        $this->configuration = $configuration;
        $this->maxPlayers = $maxPlayers;
        $this->currentRound = 1;
        $this->createdAt = new DateTime();
    }

    public function getId() {
        return $this->id;
    }

    public function getCode() {
        return $this->code;
    }

    public function getCreatorId() {
        return $this->creatorId;
    }

    public function getState() {
        return $this->state;
    }

    public function setState($state) {
        $this->state = $state;
    }

    public function getConfiguration() {
        return $this->configuration;
    }

    public function setConfiguration($configuration) {
        $this->configuration = $configuration;
    }

    public function getMaxPlayers() {
        return $this->maxPlayers;
    }

    public function getCurrentRound() {
        return $this->currentRound;
    }

    public function incrementRound() {
        $this->currentRound++;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function getStartedAt() {
        return $this->startedAt;
    }

    public function setStartedAt($startedAt) {
        $this->startedAt = $startedAt;
    }

    public function getFinishedAt() {
        return $this->finishedAt;
    }

    public function setFinishedAt($finishedAt) {
        $this->finishedAt = $finishedAt;
    }
}