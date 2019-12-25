<?php
// Loading the library.
require_once('../../config.php');
require_once('locallib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
require_once($CFG->libdir . '/adminlib.php');

$blockid = required_param('blockid', PARAM_INT);
$block = $DB->get_record_sql("SELECT * FROM {block_instances} WHERE id = $blockid");
$context = context::instance_by_id($block->parentcontextid);

//set page variables
$PAGE->set_url(new moodle_url('/blocks/carrousel/index.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);

$PAGE->set_heading(get_string('pluginname','block_carrousel'));
$PAGE->set_title(get_string('pluginname','block_carrousel'));

$PAGE->requires->js('/blocks/carrousel/carrousel.js');
$PAGE->requires->css('/blocks/carrousel/styles.css');

$PAGE->navbar->add($context->get_context_name(), $context->get_url());
$PAGE->navbar->add(get_string('pluginname','block_carrousel'));

//admin_externalpage_setup('blocksettingcarrousel');


//require login and capability to view page
require_login();

require_capability('block/carrousel:manage', $context);

$editForm = block_carrousel_edit_form($block);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'block_carrousel'));

$editForm->display();

echo block_carrousel_create_slide_button();
echo block_carrousel_manage_table($blockid);

echo $OUTPUT->footer();