<?php

class Player {
    private $id;
    private $username;
    private $avatar;
    private $preferences;
    private $score;
    private $lives;

    public function __construct($id, $username, $avatar = null, $preferences = [], $lives = 3) {
        $this->id = $id;
        $this->username = $username;
        $this->avatar = $avatar;
        $this->preferences = $preferences;
        $this->score = 0;
        $this->lives = $lives;
    }

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getAvatar() {
        return $this->avatar;
    }

    public function getPreferences() {
        return $this->preferences;
    }

    public function getScore() {
        return $this->score;
    }

    public function getLives() {
        return $this->lives;
    }

    public function setAvatar($avatar) {
        $this->avatar = $avatar;
    }

    public function updateScore($points) {
        $this->score += $points;
    }

    public function loseLife() {
        if ($this->lives > 0) {
            $this->lives--;
        }
    }

    public function reset() {
        $this->score = 0;
        $this->lives = 3;
    }
}