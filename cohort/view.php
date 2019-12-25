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
 * @author Jake Salmon <jake.salmon@kineo.com>
 * @package totara
 * @subpackage cohort
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/cohort/lib.php');

$id        = required_param('id', PARAM_INT);
$delete    = optional_param('delete', false, PARAM_BOOL);
$confirm   = optional_param('confirm', false, PARAM_BOOL);
$clone     = optional_param('clone', false, PARAM_BOOL);
$cancelurl = optional_param('cancelurl', false, PARAM_LOCALURL);


$cohort = $DB->get_record('cohort', array('id' => $id));
if (!$cohort) {
    print_error('error:doesnotexist', 'cohort');
}

$context = context::instance_by_id($cohort->contextid, MUST_EXIST);
$PAGE->set_context($context);

$url = new moodle_url('/cohort/view.php', array('id' => $id, 'delete' => $delete,
    'confirm' => $confirm, 'clone' => $clone, 'cancelurl' => $cancelurl));
if ($context->contextlevel == CONTEXT_SYSTEM) {
    admin_externalpage_setup('cohorts', '', null, $url, array('pagelayout' => 'report'));
} else {
    $PAGE->set_url($url);
    $PAGE->set_heading($COURSE->fullname);
}

require_capability('moodle/cohort:view', $context);
$canedit = has_capability('moodle/cohort:manage', $context);

if ($cohort->cohorttype == cohort::TYPE_DYNAMIC) {
    $cohort->rulesetoperator = $DB->get_field('cohort_rule_collections', 'rulesetoperator', array('id' => $cohort->draftcollectionid));
}
$membercount = $DB->count_records('cohort_members', array('cohortid' => $cohort->id));

$returnurl = new moodle_url('/cohort/index.php');

if (!$cancelurl) {
    $nourl = new moodle_url("$CFG->wwwroot/cohort/view.php", array('id'=>$cohort->id));
} else {
    $nourl = new moodle_url($cancelurl);
}

if ($delete && $cohort->id && $canedit) {
    if ($confirm and confirm_sesskey()) {
        // Get current roles assigned to this cohort.
        $roles = totara_get_cohort_roles($cohort->id);
        // Get members of the cohort.
        $members = totara_get_members_cohort($cohort->id);
        $memberids = array_keys($members);
        // Unassign members from roles.
        totara_unset_role_assignments_cohort($roles, $cohort->id, $memberids);

        $result = cohort_delete_cohort($cohort);
        totara_set_notification(get_string('successfullydeleted', 'totara_cohort'), $returnurl->out(), array('class' => 'notifysuccess'));
    }

    $yesurl = new moodle_url('/cohort/view.php', array('id'=>$cohort->id, 'delete'=>1, 'confirm'=>1,'sesskey'=>sesskey()));

    $strheading = get_string('delcohort', 'totara_cohort');
    totara_cohort_navlinks($cohort->id, format_string($cohort->name), $strheading);
    echo $OUTPUT->header();

    $buttoncontinue = new single_button($yesurl, get_string('yes'), 'post');
    $buttoncancel   = new single_button($nourl, get_string('no'), 'post');
    echo $OUTPUT->confirm(get_string('delconfirm', 'totara_cohort', format_string($cohort->name)), $buttoncontinue, $buttoncancel);

    echo $OUTPUT->footer();
    die();
}

if ($clone && $cohort->id && $canedit) {
    if ($confirm && confirm_sesskey()) {
        $result = totara_cohort_clone_cohort($cohort->id);
        if ($result) {
            $successurl = new moodle_url($CFG->wwwroot.'/cohort/view.php', array('id'=>$result));
            totara_set_notification(
                get_string('successfullycloned', 'totara_cohort'),
                $successurl->out(),
                array('class' => 'notifysuccess')
            );
        } else {
            totara_set_notification(get_string('failedtoclone', 'totara_cohort'), $returnurl->out());
        }
    }
    $yesurl = new moodle_url($CFG->wwwroot.'/cohort/view.php', array('id'=>$cohort->id, 'clone'=>1, 'confirm'=>1, 'sesskey'=>sesskey()));

    $strheading = get_string('clonecohort', 'totara_cohort');
    totara_cohort_navlinks($cohort->id, format_string($cohort->name), $strheading);
    echo $OUTPUT->header();

    $buttoncontinue = new single_button($yesurl, get_string('yes'), 'post');
    $buttoncancel   = new single_button($nourl, get_string('no'), 'post');
    echo $OUTPUT->confirm(get_string('cloneconfirm', 'totara_cohort', format_string($cohort->name)), $buttoncontinue, $buttoncancel);
    echo $OUTPUT->footer();
    die();
}

$strheading = get_string('overview', 'totara_cohort');
if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id' => $context->instanceid), '*', MUST_EXIST);
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array('contextid' => $cohort->contextid)));
} else {
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array()));
}
totara_cohort_navlinks($cohort->id, format_string($cohort->name), $strheading);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($cohort->name));

echo cohort_print_tabs('view', $cohort->id, $cohort->cohorttype, $cohort);

// Verify if the cohort has a broken rule.
$trace = new null_progress_trace();
$cohortbrokenrules = totara_cohort_broken_rules(null, $cohort->id, $trace);
if (!empty($cohortbrokenrules)) {
    totara_display_broken_rules_box();
}

$out = '';
$out .= html_writer::start_tag('div', array('class' => 'mform'));
$out .= html_writer::start_tag('fieldset');

$item = html_writer::tag('div', get_string('type', 'totara_cohort'), array('class' => 'fitemtitle'));
$item .= html_writer::tag('div', ($cohort->cohorttype == cohort::TYPE_DYNAMIC) ? get_string('dynamic', 'totara_cohort') : get_string('set', 'totara_cohort'), array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required alternate');

$item = html_writer::tag('div', get_string('idnumber', 'totara_cohort'), array('class' => 'fitemtitle'));
$item .= html_writer::tag('div', s($cohort->idnumber), array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required ');

$cohort->description = file_rewrite_pluginfile_urls($cohort->description, 'pluginfile.php', $cohort->contextid, 'cohort', 'description', $cohort->id);
$item = html_writer::tag('div', get_string('description'), array('class' => 'fitemtitle'));
$item .= html_writer::tag('div', format_text($cohort->description, $cohort->descriptionformat), array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required alternate');

$item = html_writer::tag('div', get_string('startdate', 'totara_cohort'), array('class' => 'fitemtitle'));
$ud = ($cohort->startdate) ? userdate($cohort->startdate, get_string('strftimedate')) : '';
$item .= html_writer::tag('div', $ud, array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required ');

$item = html_writer::tag('div', get_string('enddate', 'totara_cohort'), array('class' => 'fitemtitle'));
$ud = ($cohort->enddate) ? userdate($cohort->enddate, get_string('strftimedate')) : '';
$item .= html_writer::tag('div', $ud, array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required alternate');

$item = html_writer::tag('div', get_string('alertmembers', 'totara_cohort'), array('class' => 'fitemtitle'));
$item .= html_writer::tag('div', $COHORT_ALERT[$cohort->alertmembers], array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required');


$item = html_writer::tag('div', get_string('members', 'totara_cohort'), array('class' => 'fitemtitle'));
$item .= html_writer::tag('div', $membercount, array('class' => 'felement ftext'));
$out .= $OUTPUT->container($item, 'fitem required alternate');

$out .= html_writer::end_tag('fieldset') . html_writer::end_tag('div');

if ($cohort->cohorttype == cohort::TYPE_DYNAMIC) {
    require_once($CFG->dirroot.'/totara/cohort/rules/lib.php');
    $rulesets = $DB->get_records('cohort_rulesets', array('rulecollectionid' => $cohort->activecollectionid), 'sortorder');

    $out .= $OUTPUT->heading(get_string('dynamiccohortcriterialower', 'totara_cohort'));

    $out .= html_writer::start_tag('div', array('class' => 'mform'));
    $out .= html_writer::start_tag('fieldset');

    $item = html_writer::tag('div', get_string('rulestitle', 'totara_cohort'), array('class' => 'fitemtitle'));
    if (empty($rulesets)) {
        $item .= html_writer::tag('div', get_string('norules', 'totara_cohort'), array('class' => 'felement ftext'));
    } else {
        $item .= html_writer::start_tag('div', array('class' => 'felement ftext'));
        $item .= html_writer::start_tag('ul');
        $cohortoperator = get_string($COHORT_RULES_OP[$cohort->rulesetoperator], 'totara_cohort');
        $i = 0;
        foreach ($rulesets as $ruleset) {
            $item .= html_writer::start_tag('li');
            if ($i > 0) {
                $item .= $cohortoperator . ' ';
            }
            $item .= $ruleset->name;
            $rulesetoperator = get_string($COHORT_RULES_OP[$ruleset->operator], 'totara_cohort');
            $rules = $DB->get_records('cohort_rules', array('rulesetid' => $ruleset->id));
            $j = 0;
            if (!empty($rules)) { // Print its rules
                $item .= html_writer::start_tag('ul');
                foreach ($rules as $rulerec) {
                    $item .= html_writer::start_tag('li');
                    if ($j) {
                        $item .= $rulesetoperator . ' ';
                    }
                    $rule = cohort_rules_get_rule_definition($rulerec->ruletype, $rulerec->name);
                    if ($rule) {
                        $rule->sqlhandler->fetch($rulerec->id);
                        $rule->ui->setParamValues($rule->sqlhandler->paramvalues);
                        $item .= $rule->ui->getRuleDescription($rulerec->id);
                    } else { // Broken rule.
                        $a = new stdClass();
                        $a->type = $rulerec->ruletype;
                        $a->name = $rulerec->name;
                        $content = get_string('cohortbrokenrule', 'totara_cohort', $a);
                        $item .= html_writer::tag('b', $content, array('class' => 'error'));
                    }
                    $item .= html_writer::end_tag('li');
                    $j++;
                }
                $item .= html_writer::end_tag('ul');
            }
            $item .= html_writer::end_tag('li');
        }
        $item .= html_writer::end_tag('ul');
        $item .= html_writer::end_tag('div');
    }

    $out .= $OUTPUT->container($item, 'fitem required alternate');


    $out .= html_writer::end_tag('fieldset') . html_writer::end_tag('div');

} // End if cohort type is dynamic.

echo $out;
if ($canedit) {
    $cloneurl = new moodle_url("/cohort/view.php", array('id' => $cohort->id, 'clone' => 1));
    $delurl = new moodle_url("/cohort/view.php", array('id' => $cohort->id, 'delete' => 1));
    echo $OUTPUT->single_button($cloneurl, get_string('clonethiscohort', 'totara_cohort'));
    echo $OUTPUT->single_button($delurl, get_string('deletethiscohort', 'totara_cohort'));
}
echo $OUTPUT->footer();
