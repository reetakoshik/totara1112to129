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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_completioneditor
 */

namespace totara_completioneditor\form;

use core_completion\helper;
use totara_completioneditor\course_editor;
use totara_completioneditor\form\element\confirm_action_button;
use totara_form\form;
use totara_form\form_controller;
use totara_form\form\clientaction\onchange_reload;
use totara_form\form\element\action_button;
use totara_form\form\element\datetime;
use totara_form\form\element\hidden;
use totara_form\form\element\select;
use totara_form\form\element\static_html;
use totara_form\form\element\text;
use totara_form\form\group\buttons;
use totara_form\form\group\section;

class course_completion extends form {
    const EDITINGMODESEPARATE = 1;
    const EDITINGMODEUSEMODULE = 2;

    const CRITERIASTATUSINVALID = -1;
    const CRITERIASTATUSINCOMPLETE = 0;
    const CRITERIASTATUSCOMPLETE = 1;

    const COMPLETIONSTATUSINVALID = -1; // Must not clash with COMPLETION_STATUS_XXX.

    protected function definition() {
        global $CFG, $COMPLETION_STATUS;

        // Form keys.
        $this->model->add(new hidden('section', PARAM_ALPHA));
        $this->model->add(new hidden('courseid', PARAM_INT));
        $this->model->add(new hidden('userid', PARAM_INT));
        $this->model->add(new hidden('chid', PARAM_INT));
        $this->model->add(new hidden('criteriaid', PARAM_INT));
        $this->model->add(new hidden('cmid', PARAM_INT));

        $section = $this->get_parameters()['section'];

        /////////////////////////////////////////////////////////////////
        /// The user has no current completion record.
        if (($section == 'overview' || $section == 'current') && !$this->get_parameters()['hascoursecompletion']) {
            // The user has no current course completion record.
            $completionsection = $this->model->add(new section('completionsection',
                get_string('coursecompletioncurrentrecord', 'totara_completioneditor')));
            $completionsection->set_expanded(true);
            $completionsection->add(new static_html('nocoursecompletion', null,
                get_string('coursecompletiondoesntexist', 'totara_completioneditor')));

        /////////////////////////////////////////////////////////////////
        /// The current completion editor, either frozen or active.
        } else if ($section == 'overview' || $section == 'current') {
            $completionsection = $this->model->add(new section('completionsection',
                get_string('coursecompletioncurrentrecord', 'totara_completioneditor')));
            $completionsection->set_expanded(true);

            $completionsection->add(
                new datetime('timeenrolled',
                    get_string('coursecompletiontimeenrolled', 'totara_completioneditor')));

            $statuses = array();
            $statuses[self::COMPLETIONSTATUSINVALID] = get_string('invalidstatus', 'totara_completioneditor');
            foreach ($COMPLETION_STATUS as $key => $code) {
                $statuses[$key] = get_string($code, 'completion');
            }
            $status = $completionsection->add(
                new select('status',
                    get_string('coursecompletionstatus', 'totara_completioneditor'),
                    $statuses));
            $currentstatus = $status->get_data()['status'];
            if ($section == 'current') {
                $this->model->add_clientaction(new onchange_reload($status));
            }

            if ($currentstatus == COMPLETION_STATUS_NOTYETSTARTED) {
                $completionsection->add(
                    new static_html('timestarted',
                        get_string('coursecompletiontimestarted', 'totara_completioneditor'),
                        get_string('notapplicable', 'totara_completioneditor')));
            } else {
                $completionsection->add(
                    new datetime('timestarted',
                        get_string('coursecompletiontimestarted', 'totara_completioneditor')));
            }

            if (in_array($currentstatus, array(COMPLETION_STATUS_NOTYETSTARTED, COMPLETION_STATUS_INPROGRESS))) {
                $completionsection->add(
                    new static_html('timecompleted',
                        get_string('coursecompletiontimecompleted', 'totara_completioneditor'),
                        get_string('notapplicable', 'totara_completioneditor')));
            } else {
                $timecompleted = $completionsection->add(
                    new datetime('timecompleted',
                        get_string('coursecompletiontimecompleted', 'totara_completioneditor')));
                if ($section == 'current' && $currentstatus != self::COMPLETIONSTATUSINVALID) {
                    $timecompleted->set_attribute('required', true);
                }
            }

            if (in_array($currentstatus, array(COMPLETION_STATUS_NOTYETSTARTED, COMPLETION_STATUS_INPROGRESS, COMPLETION_STATUS_COMPLETE))) {
                $completionsection->add(
                    new static_html('rpl',
                        get_string('coursecompletionrpl', 'totara_completioneditor'),
                        get_string('notapplicable', 'totara_completioneditor')));
                $completionsection->add(
                    new static_html('rplgrade',
                        get_string('coursecompletionrplgrade', 'totara_completioneditor'),
                        get_string('notapplicable', 'totara_completioneditor')));
            } else {
                $rpl = $completionsection->add(
                    new text('rpl',
                        get_string('coursecompletionrpl', 'totara_completioneditor'),
                        PARAM_TEXT));
                if ($section == 'current' && $currentstatus != self::COMPLETIONSTATUSINVALID) {
                    $rpl->set_attribute('required', true);
                }
                $completionsection->add(
                    new text('rplgrade',
                        get_string('coursecompletionrplgrade', 'totara_completioneditor'),
                        PARAM_FLOAT));
            }

            if ($section == 'current') {
                // We're editing the current course completion, so show the action buttons.
                $savebuttongroup = $completionsection->add(new buttons('savebuttongroup'));
                $savebuttongroup->add(new confirm_action_button('savecurrent', get_string('savechanges'),
                    action_button::TYPE_SUBMIT,
                    array(
                        'dialogtitle' => get_string('savechanges'),
                        'dialogmessage' => get_string('coursecompletionsaveconfirm', 'totara_completioneditor'),
                    )));
                $savebuttongroup->add(new action_button('cancelcurrent', get_string('cancel'),
                    action_button::TYPE_CANCEL));

            } else {
                // We're just viewing the current course completion, so freeze the form.
                $completionsection->set_frozen(true);
            }
        }

        /////////////////////////////////////////////////////////////////
        /// The history editor.
        if ($section == 'edithistory') {
            $hashistorycompletion = $this->get_parameters()['hashistorycompletion'];
            if ($hashistorycompletion) {
                $completionsection = $this->model->add(new section('completionsection',
                    get_string('coursecompletionhistoryedit', 'totara_completioneditor')));
                $completionsection->set_expanded(true);
            } else {
                $completionsection = $this->model->add(new section('completionsection',
                    get_string('coursecompletionhistoryadd', 'totara_completioneditor')));
                $completionsection->set_expanded(true);
            }

            $timecompleted = $completionsection->add(
                new datetime('timecompleted',
                    get_string('coursecompletiontimecompleted', 'totara_completioneditor')));
            $timecompleted->set_attribute('required', true);

            $completionsection->add(
                new text('grade',
                    get_string('coursecompletiongrade', 'totara_completioneditor'),
                    PARAM_FLOAT));

            if ($hashistorycompletion) {
                $savebuttonlabel = get_string('savechanges');
            } else {
                $savebuttonlabel = get_string('coursecompletionhistoryadd', 'totara_completioneditor');
            }

            $savebuttongroup = $completionsection->add(new buttons('savebuttongroup'));
            $savebuttongroup->add(new action_button('savehistory', $savebuttonlabel,
                action_button::TYPE_SUBMIT));
            $savebuttongroup->add(new action_button('cancelhistory', get_string('cancel'),
                action_button::TYPE_CANCEL));
        }

        /////////////////////////////////////////////////////////////////
        /// The criteria editor.
        $completionsection = null;
        $rplposition = 0; // Used to put the RPL field before the module completion fields, if they exist.
        if ($section == 'editcriteria') {
            $completionsection = $this->model->add(new section('completionsection', $this->get_parameters()['sectiontitle']));
            $completionsection->set_expanded(true);

            if ($this->get_parameters()['ismodule']) {
                $editingmodes = array(
                    self::EDITINGMODESEPARATE => get_string('coursecompletionmodulecriteriaeditingmodeseparate', 'totara_completioneditor'),
                    self::EDITINGMODEUSEMODULE => get_string('coursecompletionmodulecriteriaeditingmodemodule', 'totara_completioneditor'),
                );
                $editingmode = $completionsection->add(
                    new select('editingmode',
                        get_string('coursecompletionmodulecriteriaeditingmode', 'totara_completioneditor'),
                        $editingmodes));
                $currenteditingmode = $editingmode->get_data()['editingmode'];
                $this->model->add_clientaction(new onchange_reload($editingmode));
                $rplposition++;
            }

            if (!isset($currenteditingmode) || $currenteditingmode == self::EDITINGMODESEPARATE) {
                $criteriastatuses = array(
                    self::CRITERIASTATUSINVALID => get_string('invalidstatus', 'totara_completioneditor'),
                    self::CRITERIASTATUSINCOMPLETE => get_string('notcompleted', 'completion'),
                    self::CRITERIASTATUSCOMPLETE => get_string('completed', 'completion'),
                );
                $criteriastatus = $completionsection->add(
                    new select('criteriastatus',
                        get_string('coursecompletioncriteriastatus', 'totara_completioneditor'),
                        $criteriastatuses));
                $currentcriteriastatus = $criteriastatus->get_data()['criteriastatus'];
                $this->model->add_clientaction(new onchange_reload($criteriastatus));
                $rplposition++;

                if ($currentcriteriastatus == self::CRITERIASTATUSINVALID) {
                    $completionsection->add(
                        new datetime('cctimecompleted',
                            get_string('coursecompletioncriteriatimecompleted', 'totara_completioneditor')));
                } else if ($currentcriteriastatus == self::CRITERIASTATUSCOMPLETE) {
                    $timecompleted = $completionsection->add(
                        new datetime('cctimecompleted',
                            get_string('coursecompletioncriteriatimecompleted', 'totara_completioneditor')));
                    $timecompleted->set_attribute('required', true);
                } else {
                    $completionsection->add(
                        new static_html('cctimecompleted',
                            get_string('coursecompletioncriteriatimecompleted', 'totara_completioneditor'),
                            get_string('notapplicable', 'totara_completioneditor')));
                }
                $rplposition++;
            } else {
                $notfailed = !empty($CFG->completionexcludefailures) ? 'notfailed' : '';
                $completionsection->add(
                    new static_html('criteriacomplete',
                        get_string('coursecompletioncriteriastatus', 'totara_completioneditor'),
                        get_string('coursecompletioncriteriacompletecopiedfrommodule' . $notfailed, 'totara_completioneditor')));
                $rplposition++;
                $completionsection->add(
                    new static_html('cctimecompleted',
                        get_string('coursecompletioncriteriatimecompleted', 'totara_completioneditor'),
                        get_string('coursecompletioncriteriatimecompletedcopiedfrommodule' . $notfailed, 'totara_completioneditor')));
                $rplposition++;
            }
        }

        /////////////////////////////////////////////////////////////////
        /// The module editor.
        if ($section == 'editmodule' || $section == 'editcriteria' && $this->get_parameters()['ismodule']) {
            if ($section == 'editmodule') {
                $completionsection = $this->model->add(new section('completionsection', $this->get_parameters()['sectiontitle']));
                $completionsection->set_expanded(true);
            }

            $completionstates = array(
                self::COMPLETIONSTATUSINVALID => get_string('invalidstatus', 'totara_completioneditor'),
                COMPLETION_INCOMPLETE => get_string('notcompleted', 'completion'),
                COMPLETION_COMPLETE => get_string('completed', 'completion'),
            );
            $hasmodulepassfail = $this->get_parameters()['hasmodulepassfail'];
            if ($hasmodulepassfail) {
                $completionstates[COMPLETION_COMPLETE_PASS] = get_string('completion-pass', 'completion');
                $completionstates[COMPLETION_COMPLETE_FAIL] = get_string('completion-fail', 'completion');
            }

            $completionstate = $completionsection->add(
                new select('completionstate',
                    get_string('coursecompletionmodulestatus', 'totara_completioneditor'),
                    $completionstates));
            $currentmodulestatus = $completionstate->get_data()['completionstate'];

            $onstatechange = new onchange_reload($completionstate);
            if (in_array($currentmodulestatus, array(COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS, COMPLETION_COMPLETE_FAIL))) {
                $onstatechange->add_ignored_value(COMPLETION_COMPLETE);
                $onstatechange->add_ignored_value(COMPLETION_COMPLETE_PASS);
                $onstatechange->add_ignored_value(COMPLETION_COMPLETE_FAIL);
            }
            $this->model->add_clientaction($onstatechange);

            if ($currentmodulestatus == self::COMPLETIONSTATUSINVALID) {
                $completionsection->add(
                    new datetime('cmctimecompleted',
                        get_string('coursecompletionmoduletimecompleted', 'totara_completioneditor')));
            } else if ($currentmodulestatus == COMPLETION_INCOMPLETE) {
                $completionsection->add(
                    new static_html('timecompletednotapplicaple',
                        get_string('coursecompletionmoduletimecompleted', 'totara_completioneditor'),
                        get_string('notapplicable', 'totara_completioneditor')));
            } else {
                $timecompleted = $completionsection->add(
                    new datetime('cmctimecompleted',
                        get_string('coursecompletionmoduletimecompleted', 'totara_completioneditor')));
                $timecompleted->set_attribute('required', true);
            }

            $completionsection->add(
                new form\element\checkbox('viewed',
                    get_string('coursecompletionviewed', 'totara_completioneditor')));
        }

        /////////////////////////////////////////////////////////////////
        /// Criteria RPL field.
        if ($section == 'editcriteria') {
            // Now that we've calculated the module and criteria completion statuses, we can work out
            // if the RPL field should be editable or not.
            if (isset($currentcriteriastatus) && $currentcriteriastatus == self::CRITERIASTATUSINVALID ||
                $this->get_parameters()['ismodule'] && isset($currentcriteriastatus) && $currentcriteriastatus == self::CRITERIASTATUSCOMPLETE ||
                isset($currenteditingmode) && $currenteditingmode == self::EDITINGMODEUSEMODULE && isset($currentmodulestatus) && $currentmodulestatus != COMPLETION_INCOMPLETE) {
                $completionsection->add(
                    new text('rpl',
                        get_string('coursecompletionrpl', 'totara_completioneditor'),
                        PARAM_TEXT), $rplposition);
            } else {
                $completionsection->add(
                    new static_html('rpl',
                        get_string('coursecompletionrpl', 'totara_completioneditor'),
                        get_string('notapplicable', 'totara_completioneditor')), $rplposition);
            }
        }

        /////////////////////////////////////////////////////////////////
        /// The module or criteria forum submit.
        if ($section == 'editcriteria' || $section == 'editmodule') {
            $savebuttongroup = $completionsection->add(new buttons('savebuttongroup'));
            $savebuttongroup->add(new confirm_action_button(str_replace('edit', 'save', $section), get_string('savechanges'),
                action_button::TYPE_SUBMIT,
                array(
                    'dialogtitle' => get_string('savechanges'),
                    'dialogmessage' => get_string('coursecompletionsaveconfirm', 'totara_completioneditor'),
                )));
            $savebuttongroup->add(new action_button('cancelcriteria', get_string('cancel'),
                action_button::TYPE_CANCEL));
        }

        /////////////////////////////////////////////////////////////////
        /// The programs and certifications list.
        if ($section == 'overview') {
            $progsandcertstable = $this->get_parameters()['progsandcertstable'];
            if (!empty($progsandcertstable)) {
                $progsandcertssection = $this->model->add(new section('progsandcertssection',
                    get_string('coursecompletionprogsandcerts', 'totara_completioneditor')));
                $progsandcertssection->set_expanded(true);
                $progsandcertssection->add(new static_html('progsandcertstable', null, $progsandcertstable));
            }
        }

        /////////////////////////////////////////////////////////////////
        /// The course completion criteria and modules list.
        if ($section == 'overview' || $section == 'criteria') {
            // Course completion criteria.
            $criteriasection = $this->model->add(new section('criteriasection',
                get_string('coursecompletioncriteria', 'totara_completioneditor')));
            $criteriatable = $this->get_parameters()['criteriatable'];
            $criteriasection->set_expanded(true);
            $criteriasection->add(new static_html('criteriatable', null, $criteriatable));

            // Orphaned crit compl records.
            $orphanedcritcompltable = $this->get_parameters()['orphanedcritcompltable'];
            if (!empty($orphanedcritcompltable)) {
                $orphansection = $this->model->add(new section('orphanedcritcomplsection',
                    get_string('coursecompletionorphanedcritcompls', 'totara_completioneditor')));
                $orphansection->add(new static_html('orphanexplained', '',
                    get_string('coursecompletionorphanedcritcomplsexplained', 'totara_completioneditor')));
                $orphansection->set_expanded(true);
                $orphansection->add(new static_html('orphanedcritcompltable', null, $orphanedcritcompltable));
            }

            // Modules.
            $modulessection = $this->model->add(new section('modulessection',
                get_string('coursecompletionmodulecompletion', 'totara_completioneditor')));
            $modulestable = $this->get_parameters()['modulestable'];
            $modulessection->set_expanded(true);
            $modulessection->add(new static_html('modulestable', null, $modulestable));
        }

        /////////////////////////////////////////////////////////////////
        /// The history list.
        if ($section == 'overview' || $section == 'history') {
            $historytable = $this->get_parameters()['historytable'];
            $historysection = $this->model->add(new section('historysection',
                get_string('coursecompletionhistory', 'totara_completioneditor')));
            $historysection->set_expanded(true);
            $historysection->add(new static_html('historytable', null, $historytable));

            // If we're showing history then we should have an Add history button.
            $historysection->add(new action_button('coursecompletionhistoryadd',
                get_string('coursecompletionhistoryadd', 'totara_completioneditor'),
                action_button::TYPE_SUBMIT));
        }

        /////////////////////////////////////////////////////////////////
        /// The transactions list.
        if ($section == 'overview' || $section == 'transactions') {
            $transactionstable = $this->get_parameters()['transactionstable'];
            $transactionssection = $this->model->add(new section('transactionssection',
                get_string('transactions', 'totara_completioneditor')));
            $transactionssection->set_expanded(true);
            $transactionssection->add(new static_html('transactionstable', null, $transactionstable));
        }
    }

    /**
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of ("fieldname"=>stored_file[]) of submitted files
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK
     */
    protected function validation(array $data, array $files) {
        $errors = parent::validation($data, $files);

        if ($data['section'] == 'current') {

            // Construct a course completion record from the submitted data.
            $coursecompletion = new \stdClass();
            $coursecompletion->status = $data['status'];
            $coursecompletion->timecompleted = !empty($data['timecompleted']) ? $data['timecompleted'] : null;
            $coursecompletion->rpl = (isset($data['rpl']) && $data['rpl'] != '') ? $data['rpl'] : null;
            $coursecompletion->rplgrade = !empty($data['rplgrade']) ? $data['rplgrade'] : null;

            $coursecompletion = course_editor::get_current_completion_from_data((object)$data);

            // Check for problems.
            $rawerrors = helper::get_course_completion_errors($coursecompletion);
            $completionerrors = helper::convert_errors_for_form($rawerrors);

            $errors = array_merge($errors, $completionerrors);

        }

        if ($data['section'] == 'editcriteria' || $data['section'] == 'editmodule') {

            list($cmc, $cccc) = course_editor::get_module_and_criteria_from_data((object)$data);

            if (!empty($cmc)) {
                $rawerrors = helper::get_module_completion_errors($cmc);
                $cmcerrors = helper::convert_errors_for_form($rawerrors);
                $errors = array_merge($errors, $cmcerrors);
            }

            if (!empty($cccc)) {
                $rawerrors = helper::get_criteria_completion_errors($cccc);
                $ccccerrors = helper::convert_errors_for_form($rawerrors);
                $errors = array_merge($errors, $ccccerrors);
            }

            // Criteria status isn't used to produce either of the records, so we need to check it explicitly now.
            if (!empty($data['criteriastatus']) && $data['criteriastatus'] == self::CRITERIASTATUSINVALID) {
                $errors['criteriastatus'] = get_string('invalidstatus', 'totara_completioneditor');
            }

        }

        // History, module and criteria validation are ensured by form validation.
        return $errors;
    }

    /**
     * Returns true if the form should be initialised in JS.
     *
     * @return bool
     */
    public static function initialise_in_js() {
        return true;
    }

    /**
     * Returns class responsible for form handling.
     * This is intended especially for ajax processing.
     *
     * @return null|form_controller
     */
    public static function get_form_controller() {
        return new course_completion_controller;
    }
}
