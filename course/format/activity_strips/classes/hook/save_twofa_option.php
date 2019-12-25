<?php

namespace format_activity_strips\hook;

class save_twofa_option extends \totara_core\hook\base
{
	public $data;
	public $modulename;
  
    public function __construct($data, $modulename)
    {
    	$this->data = $data;
    	$this->modulename = $modulename;
    }
}