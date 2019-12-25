<?php

namespace local_lz_extension\hook;

class xlsx_export_writer extends \totara_core\hook\base
{
	public $export;
  
    public function __construct(&$export)
    {
    	$this->export = &$export;
    }
}
