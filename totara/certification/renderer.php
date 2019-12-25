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
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @package totara
 * @subpackage certification
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/totaratablelib.php');

/**
 * Standard HTML output renderer for totara_core module
 */
class totara_certification_renderer extends plugin_renderer_base {

    /**
     * Print a description of a program, suitable for browsing in a list.
     * (This is the counterpart to print_course in /course/lib.php)
     *
     * @param object $data all info required by renderer
     * @return HTML fragment
     */
    public function print_certification($data) {

        if ($data->accessible) {
            if ($data->visible) {
                $linkcss = '';
            } else {
                $linkcss = 'dimmed';
            }
        } else {
            if ($data->visible) {
                $linkcss = 'inaccessible';
            } else {
                $linkcss = 'dimmed inaccessible';
            }
        }

        $out = '';
        $out .= html_writer::start_tag('div', array('class' => 'coursebox programbox clearfix'));
        $out .= html_writer::start_tag('div', array('class' => 'info'));
        $out .= html_writer::start_tag('div', array('class' => 'name'));
        $out .= html_writer::empty_tag('img', array('src' => totara_get_icon($data->pid, TOTARA_ICON_TYPE_PROGRAM),
            'class' => 'course_icon', 'alt' => ''));
        $url = new moodle_url('/totara/program/view.php', array('id' => $data->progid));
        $attributes = array('title' => get_string('viewprogram', 'totara_program'), 'class' => $linkcss);
        $linktext = highlight($data->highlightterms, format_string($data->fullname));
        $out .= html_writer::link($url, $linktext, $attributes);
        $out .= html_writer::end_tag('div'); // At /name .
        $out .= html_writer::end_tag('div'); // At /info .

        $out .= html_writer::start_tag('div', array('class' => 'learningcomptype'));
        $out .= html_writer::start_tag('div', array('class' => 'name'));
        $out .= $data->learningcomptypestr;
        $out .= html_writer::end_tag('div');
        $out .= html_writer::end_tag('div');

        $out .= html_writer::start_tag('div', array('class' => 'summary'));
        $options = new stdClass();
        $options->noclean = true;
        $options->para = false;
        $options->context = context_program::instance($data->progid);
        $out .= highlight($data->highlightterms, format_text($data->summary, FORMAT_MOODLE, $options));
        $out .= html_writer::end_tag('div');
        $out .= html_writer::end_tag('div');
        return $out;
    }


    /**
     * Generates HTML for a cancel button which is displayed on
     * management edit screens
     *
     * @param str $url
     * @return str HTML fragment
     */
    public function get_cancel_button($params=null, $url='') {
        if (empty($url)) {
            $url = "/totara/program/edit.php"; // Back to program edit.
        }
        $link = new moodle_url($url, $params);
        $output = $this->output->action_link($link, get_string('cancelcertificationmanagement', 'totara_certification'),
                         null, array('id' => 'cancelcertificationedits'));
        $output .= html_writer::empty_tag('br');
        return $output;
    }

    /**
     * Generates HTML to display the confirmation warnings when editing a current certification completion record.
     *
     * @param $data Object with a bunch of stuff.
     * @return str HTML fragment
     */
    public function get_save_completion_confirmation($data) {
        global $CERTIFCOMPLETIONSTATE;

        $out = '';

        if ($data->originalstate != $data->newstate) {
            $states = array(
                'from' => get_string($CERTIFCOMPLETIONSTATE[$data->originalstate], 'totara_certification'),
                'to' => get_string($CERTIFCOMPLETIONSTATE[$data->newstate], 'totara_certification')
            );
            $out .= html_writer::tag('p', get_string('completionchangestates', 'totara_certification', $states));
        }

        if (!empty($data->userresults)) {
            $out .= html_writer::tag('span', get_string('completionchangeuser', 'totara_certification'));
            $out .= html_writer::start_tag('ul');
            foreach ($data->userresults as $result) {
                $out .= html_writer::tag('li',
                    get_string($result, 'totara_certification'));
            }
            $out .= html_writer::end_tag('ul');
        }

        if (!empty($data->cronresults)) {
            $out .= html_writer::tag('span', get_string('completionchangecron', 'totara_certification'));
            $out .= html_writer::start_tag('ul');
            foreach ($data->cronresults as $change) {
                $out .= html_writer::tag('li',
                    get_string($change, 'totara_certification'));
            }
            $out .= html_writer::end_tag('ul');
        }

        $out .= html_writer::tag('span', get_string('completionchangeconfirm', 'totara_certification'));

        return $out;
    }

    /**
     * Generates HTML to display certifications which have problems, including summary info.
     *
     * @param $data Object with a bunch of stuff.
     * @return string HTML fragment
     */
    public function get_completion_checker_results($data) {
        if (!empty($data->rs)) {
            debugging('The $data required by get_completion_checker_results has changed', DEBUG_DEVELOPER);
            return "";
        }

        $out = "";

        $fulllist = $data->fulllist;
        $aggregatelist = $data->aggregatelist;
        $totalcount = $data->totalcount;
        $problemcount = 0;

        // Create the table with individual errors first, but display it last.
        ob_start();
        $errortable = new totara_table('checkall');
        $errortable->define_columns(array('user', 'program', 'errors'));
        $errortable->define_headers(array(get_string('user'), get_string('program', 'totara_program'),
            get_string('problem', 'totara_program')));
        $errortable->define_baseurl($data->url);
        $errortable->sortable(false);
        $errortable->setup();

        foreach ($fulllist as $affected) {
            $errortable->add_data(array(html_writer::link($affected->editcompletionurl, $affected->userfullname),
                $affected->programname, $affected->problem));
            $problemcount++;
        }
        $errortable->finish_html();
        $errorhtml = ob_get_clean();

        // Display the summary of results.
        if (!empty($data->progname)) {
            $out .= html_writer::tag('p', get_string('completionfilterbycertification', 'totara_certification', $data->progname));
        }
        if (!empty($data->username)) {
            $out .= html_writer::tag('p', get_string('completionfilterbyuser', 'totara_certification', $data->username));
        }
        $out .= html_writer::tag('p', get_string('completionrecordcounttotal', 'totara_certification', $totalcount));
        $out .= html_writer::tag('p', get_string('completionrecordcountproblem', 'totara_certification', $problemcount));

        // Display the aggregated problems and solution, including link to activate any fixes that are available.
        ob_start();
        if (!empty($aggregatelist)) {
            $aggregatetable = new totara_table('checkall_aggregate');
            $aggregatetable->define_columns(array('category', 'problems', 'count', 'explanation'));
            $aggregatetable->define_headers(array(
                get_string('problemcategory', 'totara_program') . $this->output->help_icon('problemcategory', 'totara_program', null),
                get_string('problem', 'totara_program'),
                get_string('count', 'totara_program'),
                get_string('completionprobleminformation', 'totara_program')));
            $aggregatetable->define_baseurl($data->url);
            $errortable->sortable(false);
            $aggregatetable->setup();

            foreach ($aggregatelist as $key => $value) {
                // Dev note: Change $value->solution to $key to see error keys in summary table.
                $aggregatetable->add_data(array($value->category, $value->problem, $value->count, $value->solution), 'problemaggregation');
            }

            $aggregatetable->finish_html();
        }
        $out .= ob_get_clean();

        // Finally, add the individual errors table to the output.
        $out .= $errorhtml;

        return $out;
    }
}
