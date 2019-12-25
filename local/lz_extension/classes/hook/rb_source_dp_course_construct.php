<?php

namespace local_lz_extension\hook;

class rb_source_dp_course_construct extends \totara_core\hook\base
{
	public $source;
  
    public function __construct(&$source)
    {
    	$this->source = $source;
    }
}