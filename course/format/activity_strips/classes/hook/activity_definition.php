<?php

namespace format_activity_strips\hook;

class activity_definition extends \totara_core\hook\base
{
    public $mform;
  
    public function __construct(&$mform) {
        $this->mform = $mform;
    }
}
