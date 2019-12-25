<?php 
use tool_policy\api;
use tool_policy\policy_version;

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
global $DB, $USER , $OUTPUT;

echo $OUTPUT->header();
 
$policy = api::list_policies(null,false);

foreach($policy as $p) {
        if(!empty($p->currentversion)) {
        	print_r($p->currentversion);

        $policyarr[] = array('pid' => $p->id, 'pname' => $p->currentversion->name);
        }
        }

echo $OUTPUT->footer();

?>