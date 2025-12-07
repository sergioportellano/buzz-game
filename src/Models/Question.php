<?php

class Question {
    private $id;
    private $questionText;
    private $audioFile;
    private $duration;
    private $metadata;
    private $category;
    private $difficulty;

    public function __construct($id, $questionText, $audioFile, $duration, $metadata, $category, $difficulty) {
        $this->id = $id;
        $this->questionText = $questionText;
        $this->audioFile = $audioFile;
        $this->duration = $duration;
        $this->metadata = $metadata;
        $this->category = $category;
        $this->difficulty = $difficulty;
    }

    public function getId() {
        return $this->id;
    }

    public function getQuestionText() {
        return $this->questionText;
    }

    public function getAudioFile() {
        return $this->audioFile;
    }

    public function getDuration() {
        return $this->duration;
    }

    public function getMetadata() {
        return $this->metadata;
    }

    public function getCategory() {
        return $this->category;
    }

    public function getDifficulty() {
        return $this->difficulty;
    }
}