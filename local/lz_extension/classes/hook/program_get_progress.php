<?php

namespace local_lz_extension\hook;

class program_get_progress extends \totara_core\hook\base
{
	public $userid;
    public $courseset_group;
    public $courseset_group_complete_count;
  
    public function __construct($userid, $courseset_group, &$courseset_group_complete_count)
    {
    	$this->userid = $userid;
        $this->courseset_group = $courseset_group;
        $this->courseset_group_complete_count = &$courseset_group_complete_count;
    }
}
