<?php

namespace local_lz_extension\hook;

class prog_messages_manager_display_form_element extends \totara_core\hook\base
{
	public $messageclassname;

	public function __construct(&$messageclassname)
	{	
        $this->messageclassname = &$messageclassname;
    }
}