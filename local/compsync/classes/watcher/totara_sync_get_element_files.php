<?php

namespace local_compsync\watcher;
  
class totara_sync_get_element_files 
{
    public static function execute(\local_compsync\hook\totara_sync_get_element_files $hook)
    {
    	global $CFG;
        $hook->filepaths[] = $CFG->dirroot.'/local/compsync/lib/elements/comp.php';
    }
}