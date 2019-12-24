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

namespace totara_completioneditor\output;

use \core_completion\helper;
use \totara_completioneditor\course_editor;

/**
* Standard HTML output renderer for totara_core module
*/
class course_renderer extends \plugin_renderer_base {

    /**
     * Generates HTML to display course completions which have problems, including summary info.
     *
     * @param \stdClass $data with a bunch of stuff.
     * @return string HTML fragment
     */
    public function checker_results($data) {
        $out = "";

        $count = 0;
        $problemcount = 0;

        $aggregatedata = array();

        // Create the table with individual errors first, but display it last.
        ob_start();
        $errortable = new \totara_table('checkall');
        $errortable->define_columns(array('userid', 'courseid', 'errors'));
        $errortable->define_headers(array(get_string('user'), get_string('course'),
            get_string('problem', 'totara_completioneditor')));
        $errortable->define_baseurl($data->url);
        $errortable->sortable(false);
        $errortable->setup();

        foreach ($data->rs as $record) {
            $errors = helper::get_course_completion_errors($record);

            if (!empty($errors)) {
                // Aggregate this combination of errors.
                $problemkey = helper::convert_errors_to_problemkey($errors);
                // If the problem key doesn't exist in the aggregate array already then create it.
                if (!isset($aggregatedata[$problemkey])) {
                    $aggregatedata[$problemkey] = new \stdClass();
                    $aggregatedata[$problemkey]->count = 0;

                    $errorstrings = array();
                    foreach ($errors as $errorkey => $errorfield) {
                        $errorstrings[] = get_string($errorkey, 'completion');
                    }

                    $aggregatedata[$problemkey]->problem = implode('<br/>', $errorstrings);
                    $aggregatedata[$problemkey]->solution =
                        course_editor::get_error_solution($problemkey, $record->course, $data->userid);
                }
                $aggregatedata[$problemkey]->count++;

                $userurl = new \moodle_url('/totara/completioneditor/edit_course_completion.php',
                    array('courseid' => $record->course, 'userid' => $record->userid));
                $username = fullname($record);
                $coursename = format_string($record->fullname);
                $errortable->add_data(array(\html_writer::link($userurl, $username),
                    $coursename, $aggregatedata[$problemkey]->problem));
                $problemcount++;
            }
            $count++;
        }
        $errortable->finish_html();
        $errorhtml = ob_get_clean();

        // Display the summary of results.
        if (!empty($data->coursename)) {
            $out .= \html_writer::tag('p', get_string('completionfilterbycourse', 'totara_completioneditor', $data->coursename));
        }
        if (!empty($data->username)) {
            $out .= \html_writer::tag('p', get_string('completionfilterbyuser', 'totara_completioneditor', $data->username));
        }
        $out .= \html_writer::tag('p', get_string('completionrecordcounttotal', 'totara_completioneditor', $count));
        $out .= \html_writer::tag('p', get_string('completionrecordcountproblem', 'totara_completioneditor', $problemcount));

        // Display the aggregated problems and solution, including link to activate any fixes that are available.
        ob_start();
        if (!empty($aggregatedata)) {
            $aggregatetable = new \totara_table('checkall_aggregate');
            $aggregatetable->define_columns(array('problems', 'count', 'explanation'));
            $aggregatetable->define_headers(array(get_string('problem', 'totara_completioneditor'), get_string('count', 'totara_completioneditor'),
                get_string('completionprobleminformation', 'totara_completioneditor')));
            $aggregatetable->define_baseurl($data->url);
            $errortable->sortable(false);
            $aggregatetable->setup();

            foreach ($aggregatedata as $key => $value) {
                // Dev note: Change $value->solution to $key to see error keys in summary table.
                $aggregatetable->add_data(array($value->problem, $value->count, $value->solution), 'problemaggregation');
            }

            $aggregatetable->finish_html();
        }
        $out .= ob_get_clean();

        // Finally, add the individual errors table to the output.
        $out .= $errorhtml;

        return $out;
    }

    /**
     * Generates HTML to display the completion criteria relating to a course completion record.
     *
     * @param array $criteria containing pre-processed data
     * @param int $overallaggregation
     * @return string HTML fragment
     */
    public function criteria($criteria, $overallaggregation) {
        if (empty($criteria)) {
            return $this->output->box(get_string('nocriteriaset', 'completion'), 'noticebox');
        }

        $out = '';

        if ($overallaggregation == COMPLETION_AGGREGATION_ALL) {
            $out .= get_string('criteriarequiredall', 'completion');
        } else {
            $out .= get_string('criteriarequiredany', 'completion');
        }

        ob_start();
        $table = new \totara_table('completioncriteria');
        $table->define_baseurl($this->page->url);

        $table->define_columns(array(
            'ccccid',
            'cmcid',
            'criteria',
            'criteriadetails',
            'status',
            'timecompleted',
            'edit',
            'hasproblem',
        ));
        $table->define_headers(array(
            get_string('coursecompletioncritcomplid', 'totara_completioneditor'),
            get_string('coursecompletionmodulescompletionid', 'totara_completioneditor'),
            get_string('criteria', 'completion'),
            '',
            get_string('status'),
            get_string('completiondate', 'report_completion'),
            get_string('edit'),
            get_string('hasproblem', 'totara_completioneditor'),
        ));

        $table->setup();

        $stredit = get_string('edit');

        $lasttype = '';
        foreach ($criteria as $key => $crit) {
            // Just use the data we want to display, formatted how we want, and ignore the rest.
            switch ($crit->criteriatype) {
                case COMPLETION_CRITERIA_TYPE_ACTIVITY:
                case COMPLETION_CRITERIA_TYPE_COURSE:
                case COMPLETION_CRITERIA_TYPE_ROLE:
                    if ($lasttype !== $crit->criteriatype) {
                        $lasttype = $crit->criteriatype;
                        $type = $crit->details['type'];
                        $nextcrit = $this->find_next_array_item_by_key($criteria, $key);
                        if (!empty($nextcrit) && $nextcrit->criteriatype == $crit->criteriatype) {
                            $type .= \html_writer::empty_tag('br') . \html_writer::tag('i', '(' . $crit->aggregation . ')');
                        }
                    } else {
                        $type = "";
                    }

                $typedetails = $crit->criteriadetails;
                    break;
                default:
                    $type = $crit->details['type'];
                    $typedetails = $crit->criteriadetails;
                    break;
            }

            if (empty($crit->timecompleted)) {
                $status = get_string('notcompleted', 'completion');
            } else if (!empty($crit->rpl)) {
                $status = get_string('completeviarpl', 'completion');
            } else {
                $status = get_string('completed', 'completion');
            }

            $timecompleted = empty($crit->timecompleted) ? '-' :
                userdate($crit->timecompleted, get_string('strftimedatetime', 'langconfig'));

            $editlink = \html_writer::link($crit->editurl, $this->output->pix_icon('/t/edit', $stredit),
                array('title' => $stredit, 'class' => 'editmodulecompletionbutton'));

            $ccid = empty($crit->ccid) ? get_string('none') : $crit->ccid;
            if ($crit->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                $cmcid = empty($crit->cmcid) ? get_string('none') : $crit->cmcid;
            } else {
                $cmcid = get_string('notapplicableshort', 'totara_completioneditor');
            }

            $hasproblem = empty($crit->hasproblem) ? get_string('no') : get_string('yes');

            $tablerow = array(
                $ccid,
                $cmcid,
                $type,
                $typedetails,
                $status,
                $timecompleted,
                $editlink,
                $hasproblem,
            );

            $table->add_data($tablerow);
        }

        $table->finish_html();
        $out .= ob_get_clean();

        return $out;
    }

    private function find_next_array_item_by_key($array, $key) {
        $currentKey = key($array);
        while ($currentKey !== null && $currentKey != $key) {
            next($array);
            $currentKey = key($array);
        }
        return next($array);
    }

    /**
     * Generates HTML to display the orphaned criteria relating to a course completion record.
     *
     * @param array $orphans
     * @return string HTML fragment
     */
    public function orphaned_criteria($orphans) {
        if (empty($orphans)) {
            return '';
        }

        $out = '';

        ob_start();
        $table = new \totara_table('orphanedcritcompls');
        $table->define_baseurl($this->page->url);

        $table->define_columns(array(
            'ccccid',
            'criteria',
            'status',
            'rpl',
            'timecompleted',
            'edit',
        ));
        $table->define_headers(array(
            get_string('coursecompletioncritcomplid', 'totara_completioneditor'),
            get_string('criteria', 'completion'),
            get_string('status'),
            get_string('coursecompletionrpl', 'totara_completioneditor'),
            get_string('completiondate', 'report_completion'),
            get_string('delete'),
        ));

        $table->setup();

        $strdelete = get_string('delete');

        foreach ($orphans as $orphan) {
            if (empty($orphan->timecompleted)) {
                $status = get_string('notcompleted', 'completion');
            } else if (!empty($orphan->rpl)) {
                $status = get_string('completeviarpl', 'completion');
            } else {
                $status = get_string('completed', 'completion');
            }

            $timecompleted = empty($orphan->timecompleted) ? '-' :
                userdate($orphan->timecompleted, get_string('strftimedatetime', 'langconfig'));

            $deletelink = \html_writer::link($orphan->deleteurl, $this->output->pix_icon('/t/delete', $strdelete),
                array('title' => $strdelete, 'class' => 'deleteorphanedcritcomplbutton'));

            $tablerow = array(
                $orphan->ccid,
                get_string('coursecompletionorphanedcritcomplunknown', 'totara_completioneditor'),
                $status,
                $orphan->rpl,
                $timecompleted,
                $deletelink,
            );

            $table->add_data($tablerow);
        }

        $table->finish_html();
        $out .= ob_get_clean();

        return $out;
    }

    /**
     * Generates HTML to display the modules relating to a course completion record.
     *
     * @param array $modules
     * @return string HTML fragment
     */
    public function modules($modules) {
        if (empty($modules)) {
            return $this->output->box(get_string('coursecompletionmodulesnonecompletable', 'totara_completioneditor'), 'noticebox');
        }

        $out = '';

        if (!empty($modules)) {
            ob_start();
            $table = new \totara_table('modulecompletion');
            $table->define_baseurl($this->page->url);

            $table->define_columns(array(
                'cmcid',
                'ccid',
                'name',
                'status',
                'timecompleted',
                'edit',
                'hasproblem',
            ));
            $table->define_headers(array(
                get_string('coursecompletionmodulescompletionid', 'totara_completioneditor'),
                get_string('coursecompletioncritcomplid', 'totara_completioneditor'),
                get_string('name'),
                get_string('status'),
                get_string('completiondate', 'report_completion'),
                get_string('edit'),
                get_string('hasproblem', 'totara_completioneditor'),
            ));

            $table->setup();

            $stredit = get_string('edit');

            foreach ($modules as $module) {
                switch ($module->status) {
                    case COMPLETION_INCOMPLETE:
                        $status = get_string('notcompleted', 'completion');
                        break;
                    case COMPLETION_COMPLETE:
                        $status = get_string('completed', 'completion');
                        break;
                    case COMPLETION_COMPLETE_PASS:
                        $status = get_string('completion-pass', 'completion');
                        break;
                    case COMPLETION_COMPLETE_FAIL:
                        $status = get_string('completion-fail', 'completion');
                        break;
                    default:
                        $status = get_string('invalidstatus', 'totara_completioneditor');
                        break;
                }

                $timecompleted = empty($module->timecompleted) ? '-' : userdate($module->timecompleted);

                $editlink = \html_writer::link($module->editurl, $this->output->pix_icon('/t/edit', $stredit),
                    array('title' => $stredit, 'class' => 'editmodulecompletionbutton'));

                $cmcid = empty($module->cmcid) ? get_string('none') : $module->cmcid;
                if ($module->criteriaid) {
                    $ccid = empty($module->ccid) ? get_string('none') : $module->ccid;
                } else {
                    $ccid = get_string('notapplicableshort', 'totara_completioneditor');
                }

                $hasproblem = empty($module->hasproblem) ? get_string('no') : get_string('yes');

                $tablerow = array(
                    $cmcid,
                    $ccid,
                    $module->name,
                    $status,
                    $timecompleted,
                    $editlink,
                    $hasproblem,
                );

                $table->add_data($tablerow);
            }

            $table->finish_html();
            $out .= ob_get_clean();
        }

        return $out;
    }

    /**
     * Generates HTML to display the history relating to a course completion record.
     *
     * @param array $history
     * @return string HTML fragment
     */
    public function history($history) {
        $out = '';

        ob_start();
        $table = new \totara_table('coursecompletionhistory');
        $table->define_baseurl($this->page->url);

        $table->define_columns(array(
            'cchid',
            'timecompleted',
            'grade',
            'edit',
            'delete',
        ));
        $table->define_headers(array(
            get_string('coursecompletionhistoryid', 'totara_completioneditor'),
            get_string('coursecompletiontimecompleted', 'totara_completioneditor'),
            get_string('coursecompletiongrade', 'totara_completioneditor'),
            get_string('edit'),
            get_string('delete'),
        ));

        $table->setup();

        $stredit = get_string('edit');
        $strdelete = get_string('delete');

        foreach ($history as $hist) {
            $timecompleted = empty($hist->timecompleted)
                ? get_string('none')
                : userdate($hist->timecompleted, get_string('strftimedatetime', 'langconfig'));

            $editlink = \html_writer::link($hist->editurl, $this->output->pix_icon('/t/edit', $stredit),
                array('title' => $stredit, 'class' => 'editcompletionhistorybutton'));

            $deletelink = \html_writer::link($hist->deleteurl, $this->output->pix_icon('/t/delete', $strdelete),
                array('title' => $strdelete, 'class' => 'deletecompletionhistorybutton'));

            $tablerow = array(
                $hist->chid,
                $timecompleted,
                is_numeric($hist->grade) ? (float)$hist->grade : '',
                $editlink,
                $deletelink,
            );

            $table->add_data($tablerow);
        }

        $table->finish_html();
        $out .= ob_get_clean();

        return $out;
    }

    /**
     * Generates HTML to display the transactions relating to a course completion record.
     *
     * @param array $transactions
     * @return string HTML fragment
     */
    public function transactions($transactions) {
        $out = '';

        ob_start();
        $table = new \totara_table('coursecompletiontransactions');
        $table->define_baseurl($this->page->url);

        $table->define_columns(array(
            'datetime',
            'user',
            'description',
        ));
        $table->define_headers(array(
            get_string('transactiondatetime', 'totara_completioneditor'),
            get_string('transactionuser', 'totara_completioneditor'),
            get_string('description'),
        ));

        $table->setup();

        foreach ($transactions as $transaction) {
            $timemodified = userdate($transaction->timemodified,get_string('strftimedateseconds', 'langconfig')) .
                " ({$transaction->timemodified})";

            if ($transaction->changeuserid) {
                $changeby = fullname($transaction);
            } else {
                $changeby = get_string('cronautomatic', 'totara_completioneditor');
            }

            $tablerow = array(
                $timemodified,
                $changeby,
                $transaction->description,
            );

            $table->add_data($tablerow);
        }

        $table->finish_html();
        $out .= ob_get_clean();

        return $out;
    }

    /**
     * Generates HTML to display the programs and certifications relating to a course completion record.
     *
     * @param array $progs
     * @param array $certs
     * @return string HTML fragment
     */
    public function related_progs_and_certs($progs, $certs) {
        global $CFG;

        $out = '';

        if (empty($progs) && empty($certs)) {
            return $out;
        }

        $progsenabled = $CFG->enableprogramcompletioneditor && !totara_feature_disabled('programs');
        $certsenabled = $CFG->enableprogramcompletioneditor && !totara_feature_disabled('certifications');
        $haseditcolumn = !empty($progs) && $progsenabled || !empty($certs) && $certsenabled;

        ob_start();
        $table = new \totara_table('coursecompletionprogsandcerts');
        $table->define_baseurl($this->page->url);

        $columns = array(
            'name',
            'status',
            'timecompleted',
        );
        $headers = array(
            get_string('name'),
            get_string('status'),
            get_string('progorcerttimecompleted', 'totara_completioneditor'),
        );
        if ($haseditcolumn) {
            $columns[] = 'edit';
            $headers[] = get_string('edit');
        }
        $table->define_columns($columns);
        $table->define_headers($headers);

        $streditprog = get_string('programcompletionedit', 'totara_completioneditor');
        $streditcert = get_string('certificationcompletionedit', 'totara_completioneditor');
        $strnoeditprog = get_string('programcompletionnoedit', 'totara_completioneditor');
        $strnoeditcert = get_string('certificationcompletionnoedit', 'totara_completioneditor');

        $table->setup();

        foreach ($progs as $prog) {
            if (empty($prog->status)) {
                $status = 'Not currently assigned';
            } else if ($prog->status == STATUS_PROGRAM_COMPLETE) {
                $status = get_string('statusprogramcomplete', 'totara_program');
            } else {
                $status = get_string('statusprogramincomplete', 'totara_program');
            }

            if (empty($prog->timecompleted)) {
                $timecompleted = '-';
            } else {
                $timecompleted = userdate($prog->timecompleted,get_string('strftimedatetime', 'langconfig'));
            }

            $tablerow = array(
                $prog->name,
                $status,
                $timecompleted,
            );

            if ($haseditcolumn) {
                if ($prog->editurl) {
                    $editlink = \html_writer::link($prog->editurl, $this->output->pix_icon('t/edit', $streditprog),
                        array('title' => $streditprog, 'class' => 'editprogorcertcompletionbutton'));
                } else {
                    $editlink = $this->output->pix_icon('t/edit', $strnoeditprog);
                }
                $tablerow[] = $editlink;
            }

            $table->add_data($tablerow);
        }

        foreach ($certs as $cert) {
            $certcompletionstate = certif_get_completion_state($cert);
            switch ($certcompletionstate) {
                case CERTIFCOMPLETIONSTATE_ASSIGNED:
                    $status = get_string('stateassigned', 'totara_certification');
                    break;
                case CERTIFCOMPLETIONSTATE_CERTIFIED:
                    $status = get_string('statecertified', 'totara_certification');
                    break;
                case CERTIFCOMPLETIONSTATE_WINDOWOPEN:
                    $status = get_string('statewindowopen', 'totara_certification');
                    break;
                case CERTIFCOMPLETIONSTATE_EXPIRED:
                    $status = get_string('stateexpired', 'totara_certification');
                    break;
                default:
                    $status = get_string('stateinvalid', 'totara_certification');
            }

            if (empty($cert->timecompleted)) {
                $timecompleted = '-';
            } else {
                $timecompleted = userdate($cert->timecompleted,get_string('strftimedatetime', 'langconfig'));
            }

            $tablerow = array(
                $cert->name,
                $status,
                $timecompleted,
            );

            if ($haseditcolumn) {
                if ($cert->editurl) {
                    $editlink = \html_writer::link($cert->editurl, $this->output->pix_icon('t/edit', $streditcert),
                        array('title' => $streditcert, 'class' => 'editprogorcertcompletionbutton'));
                } else {
                    $editlink = $this->output->pix_icon('t/edit', $strnoeditcert);
                }
                $tablerow[] = $editlink;
            }

            $table->add_data($tablerow);
        }

        $table->finish_html();
        $out .= ob_get_clean();

        return $out;
    }

    /**
     * @param string $section the "section" that the user is currently on
     * @param int $courseid
     * @param int $userid
     * @return string
     */
    public function editor_tabs($section, $courseid, $userid) {
        $tabs = array();

        $params = array('courseid' => $courseid, 'userid' => $userid);
        $url = new \moodle_url('/totara/completioneditor/edit_course_completion.php', $params);

        $tabs[] = new \tabobject('overview',
            $url,
            get_string('overview', 'totara_completioneditor'));

        $urlcurrent = clone($url);
        $urlcurrent->param('section', 'current');
        $tabs[] = new \tabobject('current',
            $urlcurrent,
            get_string('coursecompletioncurrent', 'totara_completioneditor'));

        $urlcriteria = clone($url);
        $urlcriteria->param('section', 'criteria');
        $tabs[] = new \tabobject('criteria',
            $urlcriteria,
            get_string('coursecompletioncriteriaandmodules', 'totara_completioneditor'));
        if ($section == 'editcriteria' || $section == 'editmodule') {
            $section = 'criteria';
        }

        $urlhistory = clone($url);
        $urlhistory->param('section', 'history');
        $tabs[] = new \tabobject('history',
            $urlhistory,
            get_string('history', 'totara_completioneditor'));
        if ($section == 'edithistory') {
            $section = 'history';
        }

        $urltransactions = clone($url);
        $urltransactions->param('section', 'transactions');
        $tabs[] = new \tabobject('transactions',
            $urltransactions,
            get_string('transactions', 'totara_completioneditor'));

        // Ensure the current tab is selected and activated.
        foreach ($tabs as $tab) {
            if ($tab->id === $section) {
                $tab->activated = true;
                $tab->selected = true;
            }
        }

        return $this->output->tabtree($tabs);
    }

    /**
     * Print checker link
     *
     * @param int $courseid
     * @return string HTML
     */
    public function checker_link($courseid = null) {
        $params = array();

        if (!empty($courseid)) {
            $params['courseid'] = $courseid;
        }

        $checkallurl = new \moodle_url('/totara/completioneditor/check_course_completion.php', $params);
        return \html_writer::tag('ul', \html_writer::tag('li', \html_writer::link($checkallurl,
            get_string('checkcoursecompletions', 'totara_completioneditor'))));
    }

    /**
     * Return HTML to show a notification stating that the user is not enrolled, and include a link to delete the
     * current course completion record if it exists.
     *
     * @param bool $hascoursecompletion true if the user has a current course completion record
     * @param int $courseid
     * @param int $userid
     * @return string HTML
     */
    public function not_enrolled_notification($hascoursecompletion, $courseid, $userid) {
        $out = get_string('notenrolled', 'totara_completioneditor');

        if ($hascoursecompletion) {
            $deletecoursecompltionurl = new \moodle_url(
                '/totara/completioneditor/edit_course_completion.php',
                array(
                    'deletecoursecompletion' => 1,
                    'courseid' => $courseid,
                    'userid' => $userid,
                    'sesskey' => sesskey()
                ));

            $out .= \html_writer::empty_tag('br');
            $out .= \html_writer::link(
                $deletecoursecompltionurl,
                get_string('deletecoursecompletion', 'totara_completioneditor'),
                array('class' => 'deletecompletionlink')
            );
        }

        return $this->output->notification($out, 'notifymessage');
    }
}
