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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage totara_question
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__ . '/../lib.php');

// Check if Appraisals are enabled.
appraisal::check_feature_enabled();

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

require_sesskey();
require_login(null, false, null, false, true);

$id = required_param('id', PARAM_INT);
$roleassignmentid = required_param('answerid', PARAM_INT);

$PAGE->set_url(new moodle_url('/totara/appraisal/ajax/review.php', array('id' => $id, 'answerid' => $roleassignmentid)));

// Check that assignments exist.
$planitems = optional_param('update', null, PARAM_SEQUENCE);
$idlist = (!$planitems) ? array() : explode(',', $planitems);
if (empty($idlist)) {
    exit;
}

$defquestion = new appraisal_question($id);
$page = new appraisal_page($defquestion->appraisalstagepageid);
$stage = new appraisal_stage($page->appraisalstageid);
$appraisal = new appraisal($stage->appraisalid);
$roleassignment = new appraisal_role_assignment($roleassignmentid);

$otherassignments = $appraisal->get_all_assignments($roleassignment->subjectid);
unset($otherassignments[$roleassignment->appraisalrole]);
$question = new appraisal_question($id, $roleassignment);
$review = $question->get_element();
$rights = $question->roles[$roleassignment->appraisalrole];

if ($roleassignment->userid != $USER->id) {
    throw new appraisal_exception('Wrong assignment');
}

$newitems = array();
if ($review->cananswer) {
    $checkedids = $review->check_target_ids($idlist, $roleassignment->subjectid);
    foreach ($checkedids as $itemid) {
        $item = new stdClass();
        $item->itemid = $itemid;
        if (!$review->stub_exists(array('itemid' => $item->itemid))) {
            $review->prepare_stub($item);
            $newitems[$item->itemid] = true;
        }
    }
}
if (empty($newitems)) {
    exit;
}

if (($rights & appraisal::ACCESS_CANVIEWOTHER) == appraisal::ACCESS_CANVIEWOTHER) {
    $question->populate_roles_element($roleassignment, $otherassignments);
}
$renderer = $PAGE->get_renderer('totara_question');
$items = $review->get_grouped_items();
foreach ($items[null] as $itemkey => $item) {
    if (!isset($newitems[$itemkey])) {
        unset($items[null][$itemkey]);
    }
}
$form = new MoodleQuickForm(null, null, null);
$renderer->add_review_items($form, $items, $review);
$form->display();
