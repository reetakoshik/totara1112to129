<?php

namespace local_lz_extension\hook;

class prog_messages_manager_construct extends \totara_core\hook\base
{
    public $manager_message_classnames;
  
    public function __construct(&$manager_message_classnames) {
        $this->manager_message_classnames = &$manager_message_classnames;
    }
}
