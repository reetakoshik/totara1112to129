<?php

namespace format_activity_strips\hook;

class data_preprocessing extends \totara_core\hook\base
{
	public $default_values;

	public function __construct(&$default_values)
	{	
        $this->default_values = &$default_values;
    }
}