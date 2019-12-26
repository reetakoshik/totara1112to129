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
 * @author Russell England <russell.england@catalyst-eu.net>
 * @package totara
 * @subpackage block_totara_certifications
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Certifications block
 *
 * Displays upcoming certifications
 */
class block_totara_certifications extends block_base {

    public function init() {
        $this->title   = get_string('pluginname', 'block_totara_certifications');
    }

    public function get_content() {
        global $USER, $DB;

        if (!totara_feature_visible('certifications')) {
            return '';
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $certifications = array();

        $params = array('uid' => $USER->id,
            'cert' => CERTIFPATH_CERT,
            'recert' => CERTIFPATH_RECERT,
            'due' => CERTIFRENEWALSTATUS_DUE,
            'raised' => PROGRAM_EXCEPTION_RAISED,
            'dismissed' => PROGRAM_EXCEPTION_DISMISSED,
            'contextlevel' => CONTEXT_PROGRAM
        );

        // Take into account the visibility of the certification.
        list($visibilitysql, $visibilityparams) = totara_visibility_where($USER->id, 'p.id', 'p.visible',
            'p.audiencevisible', 'p', 'certification');

        $params = array_merge($params, $visibilityparams);

        $sql = "SELECT p.id as pid, p.fullname, cfc.timewindowopens, cfc.certifpath
                  FROM {prog} p
            INNER JOIN {certif_completion} cfc
                    ON (cfc.certifid = p.certifid AND cfc.userid = :uid)
             LEFT JOIN {context} ctx
                    ON (ctx.instanceid = p.id AND ctx.contextlevel = :contextlevel)
                 WHERE {$visibilitysql}
                   AND (cfc.certifpath = :cert OR (cfc.certifpath = :recert AND cfc.renewalstatus = :due))
                   AND EXISTS (SELECT id
                                 FROM {prog_user_assignment} pua
                                WHERE pua.userid = cfc.userid
                                  AND pua.programid = p.id
                                  AND pua.exceptionstatus <> :raised
                                  AND pua.exceptionstatus <> :dismissed
                       )
              ORDER BY cfc.timewindowopens DESC";

        // As timewindowopens is 0 for CERTs they will come at top, in any order.
        $renewals = $DB->get_records_sql($sql, $params);

        foreach ($renewals as $renewal) {
            $certification = new stdClass();
            $url = new moodle_url('/totara/program/required.php', array('id' => $renewal->pid));
            $link = html_writer::link($url, $renewal->fullname, array('title' => $renewal->fullname));
            $certification->description = $link;

            $prog_completion = $DB->get_record('prog_completion',
                            array('programid' => $renewal->pid, 'userid' => $USER->id, 'coursesetid' => 0));
            if ($prog_completion) {
                $duedatestr = (empty($prog_completion->timedue) || $prog_completion->timedue == COMPLETION_TIME_NOT_SET)
                    ? get_string('duedatenotset', 'totara_program')
                    : userdate($prog_completion->timedue, get_string('strftimedate', 'langconfig'));
            } else {
                $duedatestr =  get_string('duedatenotset', 'totara_program');
            }
            $certification->date = $duedatestr;
            $certifications[] = $certification;
        }
        // Display 'required' list, certifications only.
        $url = new moodle_url('/totara/program/required.php', array('userid' => $USER->id, 'filter' => 'certification'));
        if (count($certifications) > 0) {
            $this->content->footer = html_writer::link($url, get_string('allmycertifications', 'block_totara_certifications'));
        }

        $renderer = $this->page->get_renderer('block_totara_certifications');
        $this->content->text = $renderer->display_certifications($certifications);
        return $this->content;
    }
}
