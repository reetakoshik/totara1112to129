<?php
function videomessage_summary_field_options() {
        global $CFG;
        require_once($CFG->libdir.'/formslib.php');

        return [
            'subdirs' => false,
            'maxfiles' => -1,
            'context' => context_system::instance(),
        ];
    }
    ?>