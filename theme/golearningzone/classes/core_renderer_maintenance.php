<?php

defined('MOODLE_INTERNAL') || die();

require_once "{$CFG->dirroot}/totara/core/renderer.php";
require_once __DIR__.'/../lib/Traits.php';

use GoLearningZone\Traits\Renderer as Renderer;

class theme_golearningzone_core_renderer_maintenance extends core_renderer_maintenance
{
    use Renderer;
}