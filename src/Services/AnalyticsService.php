<?php

class AnalyticsService {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function trackPlayerAction($playerId, $action, $details = []) {
        $query = "INSERT INTO player_actions (player_id, action, details, timestamp) VALUES (?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$playerId, $action, json_encode($details)]);
    }

    public function getGameStatistics($gameId) {
        $query = "SELECT * FROM game_statistics WHERE game_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$gameId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recordGameResult($gameId, $results) {
        $query = "INSERT INTO game_results (game_id, results, timestamp) VALUES (?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$gameId, json_encode($results)]);
    }
}