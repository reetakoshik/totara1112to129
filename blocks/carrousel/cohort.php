<?php

/*
    Cohorts dialog for carrousel
    Based on totara dashboard dialog but don't require totara/dashboard:manage permission
*/

require_once __DIR__.'/../../config.php';
require_once $CFG->dirroot .'/cohort/lib.php';

$selected   = optional_param('selected', array(), PARAM_SEQUENCE);
$blockid    = optional_param('blockid', 0, PARAM_INT);

require_login();

try {
    require_sesskey();
} catch (moodle_exception $e) {
    echo html_writer::tag('div', $e->getMessage(), array('class' => 'notifyproblem'));
    die();
}

// Check user capabilities.
$contextsystem = context_system::instance();

$capable = false;
if ($blockid) {
    $block = $DB->get_record_sql("SELECT * FROM {block_instances} WHERE id = $blockid");
    $context = context::instance_by_id($block->parentcontextid);
    $capable = has_capability('block/carrousel:manage', $context);
} 

$PAGE->set_context($contextsystem);
$PAGE->set_url('/blocks/carrousel/cohort.php');

if (!$capable) {
    echo html_writer::tag(
        'div',
        get_string('error:capabilitycohortview', 'totara_cohort'),
        ['class' => 'notifyproblem']
    );
    die();
}

if (!empty($selected)) {
    $selectedlist = explode(',', $selected);
    list($placeholders, $params) = $DB->get_in_or_equal($selectedlist);
    $records = $DB->get_records_select('cohort', "id {$placeholders}", $params, '', 'id, name as fullname');
} else {
    $records = array();
}

$items = $DB->get_records('cohort');

// Don't let them remove the currently selected ones.
$unremovable = $records;

// Setup dialog.
// Load dialog content generator; skip access, since it's checked above.
$dialog = new totara_dialog_content();
$dialog->search_code = '/blocks/carrousel/cohort_search.php';
$dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
$dialog->items = $items;
$dialog->selected_items = $records;
$dialog->unremovable_items = $unremovable;
$dialog->selected_title = 'itemstoadd';
$dialog->searchtype = 'cohort';
$dialog->customdata['instanceid'] = $blockid;

echo $dialog->generate_markup();
