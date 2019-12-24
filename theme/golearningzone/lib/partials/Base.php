<?php

namespace GoLearningZone\Partials;

abstract class Base
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    public function __get($name) 
    {
        return $this->$name;
    }

    abstract public function render();
}