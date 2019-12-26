<?php

namespace local_compsync\hook;

class totara_sync_get_element_files extends \totara_core\hook\base
{
    public $filepaths;
  
    public function __construct(&$filepaths)
    {
        $this->filepaths = &$filepaths;
    }
}
