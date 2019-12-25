<?php

namespace format_activity_strips\hook;

class advanced_feartures extends \totara_core\hook\base
{
    public $optionalsubsystems;
  
    public function __construct(&$optionalsubsystems) {
        $this->optionalsubsystems = $optionalsubsystems;
    }
}
