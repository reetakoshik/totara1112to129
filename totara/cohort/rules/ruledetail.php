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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara
 * @subpackage cohort/rules
 */
/**
 * This class displays details about a rule. It is meant to provide content to a pop-up ajax dialog
 */
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/totara/cohort/rules/lib.php');

require_login();

$type = required_param('type', PARAM_ALPHA);
$id = required_param('id', PARAM_INT);

// Get the cohort contextid so we can determin permissions.
if ($type == 'rule' || $type == 'ruleset') {
    if ($type == 'rule') {
        $rulesetid = $DB->get_field('cohort_rules', 'rulesetid', array('id' => $id));
    } else {
        $rulesetid = $id;
    }

    $cohort = $DB->get_record_sql(
        "SELECT c.id, c.contextid
        FROM {cohort} c
        INNER JOIN {cohort_rule_collections} crc
        ON c.id = crc.cohortid
        INNER JOIN {cohort_rulesets} crs
        ON crc.id = crs.rulecollectionid
        WHERE crs.id = ?", array($rulesetid)
    );
} else {
    $cohort = $DB->get_record('cohort', array('id' => $id));
}

$context = context::instance_by_id($cohort->contextid);
require_capability('totara/cohort:managerules', $context);
$PAGE->set_context($context);

if ($type == 'rule') {
    $ruleobj = $DB->get_record('cohort_rules', array('id' => $id));
    $ruleinstanceid = $id;
    $rulegroupname = $ruleobj->ruletype . '-' . $ruleobj->name;
} else {
    $ruleinstanceid = false;
    $rulegroupname = required_param('rule',PARAM_RAW);
}

if (!preg_match('/^([a-z]+)-([a-z0-9_]+)$/', $rulegroupname, $matches)) {
    print_error('error:rulemissing', 'totara_cohort');
} else {
    $rulegroup = $matches[1];
    $rulename = $matches[2];
    unset($matches);
}

$rule = cohort_rules_get_rule_definition($rulegroup, $rulename);
if (!$rule) {
    print_error('error:badrule', 'totara_cohort', '', "{$rulegroup}-{$rulename}");
}

/* @var $ui cohort_rule_ui_form */
$ui = $rule->ui;
/* @var $sqlhandler cohort_rule_sqlhandler */
$sqlhandler = $rule->sqlhandler;

$update = optional_param('update', false, PARAM_BOOL);

if ($update) {
    require_sesskey();

    if ($ui->validateResponse()) {

        // See what kind of new record (or existing record update) we need to do
        switch($type) {
            case 'cohort':
                // Given only the cohort, this indicates we should create the first
                // rule in a new ruleset
                if (!$cohort = $DB->get_record('cohort', array('id' => $id))) {
                    print_error(get_string('error:badcohortid', 'totara_cohort'));
                }
                if (!$cohort->cohorttype == cohort::TYPE_DYNAMIC) {
                    print_error(get_string('error:notdynamiccohort', 'totara_cohort'));
                }

                $rulesetid = cohort_rule_create_ruleset($cohort->draftcollectionid);
                $cohortid = $id;
                $ruleinstanceid = cohort_rule_create_rule($rulesetid, $rulegroup, $rulename);
                break;
            case 'ruleset':
                // Given only the ruleset, this indicates we should create a new
                // rule in the ruleset
                $ruleinstanceid = cohort_rule_create_rule($id, $rulegroup, $rulename);
                $rulesetid = $id;
                break;
            case 'rule':
                // Given the ruleid, indicates we're updating an existing one
                // ... we don't actually have to do anything here.
                $ruleinstanceid = $id;
                $rulesetid = $DB->get_field('cohort_rules', 'rulesetid', array('id' => $id));

                $cohort = $DB->get_record_sql(
                    "SELECT c.id, c.contextid
                    FROM {cohort} c
                    INNER JOIN {cohort_rule_collections} crc
                    ON c.id = crc.cohortid
                    INNER JOIN {cohort_rulesets} crs
                    ON crc.id = crs.rulecollectionid
                    WHERE crs.id = ?", array($rulesetid));

                // Trigger rule updated event.
                $log = array(SITEID, 'cohort', 'edit rule', 'totara/cohort/rules.php?id='. $cohort->id, "ruleid={$id}");
                $event = \totara_cohort\event\rule_updated::create_from_instance($ruleobj, $cohort);
                $event->set_legacy_logdata($log);
                $event->trigger();
                break;
            default:
                // error!
        }

        $sqlhandler->fetch($ruleinstanceid);
        $ui->handleDialogUpdate($sqlhandler);

        echo "DONE";

        $ruleset = $DB->get_record('cohort_rulesets', array('id' => $rulesetid));
        $ruleset->rules = $DB->get_records('cohort_rules', array('rulesetid' => $ruleset->id), 'sortorder');

        // Generate the response
        switch($type) {
            case 'cohort':

                //todo: need to figure out proper formslib way to print a form snippet without <form> tags et al
                // This is kind of a hacky way to print only the snippet I need from formslib
                require_once($CFG->dirroot . '/lib/formslib.php');
                class empty_form extends moodleform {
                   function definition(){}
                }

                $snippetform = new empty_form();
                $mform =& $snippetform->_form;

                if ($ruleset->sortorder > 1) {
                    $operator = cohort_collection_get_rulesetoperator($cohortid);
                    $opstr = html_writer::start_tag('div',
                        array('class' => 'cohort-oplabel', 'id' => 'oplabel'.$rulesetid));
                    switch ($operator) {
                        case COHORT_RULES_OP_AND:
                            $opstr .= get_string('andcohort', 'totara_cohort');
                            break;
                        case COHORT_RULES_OP_OR:
                            $opstr .= get_string('orcohort', 'totara_cohort');
                            break;
                        default:
                            $opstr .= $operator;
                    }
                    $opstr .= html_writer::end_tag('div');
                    $mform->addElement('static', "operator{$rulesetid}", $opstr, '');
                }

                $mform->addElement('header', "cohort-ruleset-header{$rulesetid}", $ruleset->name);

                // The menu for the operator in this ruleset
                $radiogroup = array();
                $radioname = "rulesetoperator[{$rulesetid}]";
                $radiogroup[] =& $mform->createElement('radio', $radioname, '', get_string('cohortoperatorandlabel', 'totara_cohort'), COHORT_RULES_OP_AND);
                $radiogroup[] =& $mform->createElement('radio', $radioname, '', get_string('cohortoperatororlabel', 'totara_cohort'), COHORT_RULES_OP_OR);
                $mform->addGroup($radiogroup, $radioname, get_string('rulesetoperatorlabel', 'totara_cohort'), '<br />', false);

                $ruledata = cohort_ruleset_form_template_object($ruleset);
                $mform->addElement('html', $OUTPUT->render_from_template('totara_cohort/editing_ruleset', $ruledata));

                $mform->addElement(
                    'selectgroups',
                    "addrulemenu{$id}",
                    get_string('addrule', 'totara_cohort'),
                    cohort_rules_get_menu_options(),
                    array(
                        'class' => 'rule_selector new_rule_selector',
                        'data-idtype' => 'ruleset',
                        'data-id' => $ruleset->id,
                    )
                );

                ob_start();
                $snippetform->display();
                $c = ob_get_contents();
                ob_end_clean();
                $snippet = preg_replace('/.*?(<fieldset.*<\/fieldset>).*/ms', '$1', $c);

                echo $snippet;
                break;

            case 'ruleset':
            case 'rule':

                $ruledata = cohort_ruleset_form_template_object($ruleset);
                echo $OUTPUT->render_from_template('totara_cohort/editing_ruleset', $ruledata);
                break;
        }
        exit();
    } else { // if ($ui->validateResponse)
        // nothin'! We'll print the errors via $ui->printDialogContent, below
    }
}

$cohortid = false;
$rulesetid = false;

if ($ruleinstanceid) {
    $sqlhandler->fetch($ruleinstanceid);
    $ui->setParamValues($sqlhandler->paramvalues);
}

$ui->printDialogContent(
    array(
        'type' => $type,
        'id' => $id,
        'rule' => $rulegroupname
    ),
    $ruleinstanceid
);
