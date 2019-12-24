<?php

namespace GoLearningZone\Pages;

abstract class Base
{
    private $renderer;

    public function __construct($renderer)
    {
        global $PAGE;
        $this->renderer = $renderer;
        $PAGE->requires->strings_for_js([
            'block_last_course_accessed_resume'
        ], 'theme_golearningzone');
    }

    public function __get($name) 
    {
        return $this->$name;
    }

    abstract public function render();
}