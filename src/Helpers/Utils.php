<?php
class Utils {
    public static function generateUniqueCode($length = 10) {
        return substr(str_shuffle(str_repeat($x = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }

    public static function validateJson($json) {
        json_decode($json);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    public static function formatTime($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public static function arrayToJson($array) {
        return json_encode($array, JSON_PRETTY_PRINT);
    }

    public static function jsonToArray($json) {
        return json_decode($json, true);
    }
}
?>