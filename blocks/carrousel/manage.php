<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_dashboard
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/blocks/carrousel/lib.php');
require_once($CFG->libdir.'/adminlib.php');

$action = optional_param('action', '', PARAM_ACTION);
$blockid = required_param('blockid', PARAM_INT);

$systemcontext = context_system::instance();

$block = $DB->get_record_sql("SELECT * FROM {block_instances} WHERE id = $blockid");
$context = context::instance_by_id($block->parentcontextid);
require_capability('block/carrousel:manage', $context);

//set page variables
$PAGE->set_url(new moodle_url('/blocks/carrousel/manage.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);

require_login();

if ($action != '') {
    $id = required_param('id', PARAM_INT);
    
    $returnurl = new moodle_url('/blocks/carrousel/index.php', array('blockid' => $blockid));
}
switch ($action) {
    case 'delete':
        $confirm = optional_param('confirm', null, PARAM_INT);
        if ($confirm) {
            require_sesskey();
            block_carrousel_delete_slide($id);
            totara_set_notification(get_string('dashboarddeletesuccess', 'block_carrousel'), $returnurl,
                    array('class' => 'notifysuccess'));
        }
        break;
    case 'up':
        require_sesskey();
        block_carrousel_slide_move_up($id);
        redirect($returnurl);
        break;
    case 'down':
        require_sesskey();
        block_carrousel_slide_move_down($id);
        redirect($returnurl);
        break;
     case 'publish':
        require_sesskey();
        block_carrousel_slide_publish($id);
        redirect($returnurl);
        break;
     case 'unpublish':
        require_sesskey();
        block_carrousel_slide_unpublish($id);
        redirect($returnurl);
        break;
}

echo $OUTPUT->header();
switch ($action) {
    case 'delete':
        
        $slide = block_carrousel_get_slide($id);
        $confirmtext = get_string('deleteslideconfirm', 'block_carrousel', $slide->title);
        
        echo $OUTPUT->box_start('notifynotice');
        echo html_writer::tag('p', $confirmtext);
        echo $OUTPUT->box_end();

        $url = new moodle_url('/blocks/carrousel/manage.php', array('action'=> $action, 'id' => $id, 'confirm' => 1, 'blockid' => $blockid));
        $continue = new single_button($url, get_string('continue'), 'post');
        $cancel = new single_button($returnurl, get_string('cancel'), 'get');
        echo html_writer::tag('div', $OUTPUT->render($continue) . $OUTPUT->render($cancel), array('class' => 'buttons'));
        break;
    default:
        break;
}
echo $OUTPUT->footer();