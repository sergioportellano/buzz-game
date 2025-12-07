<?php

class ScoreService {
    private $scores = [];

    public function updateScore($playerId, $points) {
        if (!isset($this->scores[$playerId])) {
            $this->scores[$playerId] = 0;
        }
        $this->scores[$playerId] += $points;
    }

    public function getScore($playerId) {
        return $this->scores[$playerId] ?? 0;
    }

    public function getAllScores() {
        return $this->scores;
    }

    public function resetScores() {
        $this->scores = [];
    }
}