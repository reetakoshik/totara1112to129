<?php

namespace local_lz_extension\watcher;
  
class prog_messages_manager_display_form_element 
{
    public static function execute(\local_lz_extension\hook\prog_messages_manager_display_form_element $hook)
    {
        $hook->messageclassname = str_replace('local_lz_extension\\', '', $hook->messageclassname);
    }
}