<?php

namespace format_activity_strips\watcher;
  
class advanced_feartures 
{
    public static function execute(\format_activity_strips\hook\advanced_feartures $hook)
    {
    	$hook->optionalsubsystems->add(
            new \admin_setting_configcheckbox(
                'totara_enable_2fa',
                // new lang_string('setting:allowmultiplejobs', 'totara_job'),
                get_string('twofa-enable', 'format_activity_strips'),
                // new lang_string('setting:allowmultiplejobs_description', 'totara_job'),
                get_string('twofa-enable', 'format_activity_strips'),
                0
            )
        );
    }
}