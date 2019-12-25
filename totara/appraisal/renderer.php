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
 * @package totara
 * @subpackage totara_appraisal
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once('lib.php');
require_once($CFG->dirroot.'/totara/appraisal/constants.php');
require_once($CFG->dirroot.'/totara/job/classes/job_assignment.php');

use totara_job\job_assignment;

/**
 * Output renderer for totara_appraisals module
 */
class totara_appraisal_renderer extends plugin_renderer_base {
    /**
     * Page header that is aware of dompdf limitations.
     * @return string
     */
    public function snapshot_header() {
        global $CFG;
        $this->page->set_state(moodle_page::STATE_PRINTING_HEADER);
        $output = '';
        $output .=  $this->doctype();
        $output .=  html_writer::start_tag('html');

        $headhtml = html_writer::tag('title', $this->page_title());
        $headhtml .= html_writer::empty_tag('meta', array('http-equiv' => "Content-Type",
            'content' => "text/html; charset=utf-8"));
        $headhtml .= html_writer::empty_tag('meta', array('name' => 'viewport',
            'content' => 'width=device-width, initial-scale=1.'));

        $styles = file_get_contents($CFG->dirroot . '/totara/appraisal/dompdf.css');
        $headhtml .= html_writer::tag('style', $styles);

        $headhtml .= $this->page->requires->get_head_code($this->page, $this->output);

        $output .= html_writer::tag('head', $headhtml);
        $output .= html_writer::start_tag('body', array('id' => $this->body_id(),
            'class' => $this->body_css_classes(array('bodynoheader'))));

        $this->page->set_state(moodle_page::STATE_IN_BODY);
        return $output;
    }

    /**
     * Page footer that is aware of pdf renderer limitations.
     * @return string
     */
    public function snapshot_footer() {
        $output = '';
        $output .= $this->page->requires->get_end_code();
        $output .= html_writer::end_tag('body');
        $output .= html_writer::end_tag('html');
        $output .= $this->container_end_all(false);

        $this->page->set_state(moodle_page::STATE_DONE);
        return $output;
    }

    /**
     * Renders a table containing appraisals list for manager
     *
     * @param array $appraisals array of appraisal object
     * @param int $userid User id to show actions according their rights
     * @return string HTML table
     */
    public function appraisal_manage_table($appraisals = array(), $userid = null) {
        global $CFG, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        if (empty($appraisals)) {
            return get_string('noappraisals', 'totara_appraisal');
        }

        $tableheader = array(get_string('name', 'totara_appraisal'),
                             get_string('start', 'totara_appraisal'),
                             get_string('assigned', 'totara_appraisal'),
                             get_string('status', 'totara_appraisal'),
                             get_string('options', 'totara_appraisal'));

        $appraisalstable = new html_table();
        $appraisalstable->summary = '';
        $appraisalstable->head = $tableheader;
        $appraisalstable->data = array();
        $appraisalstable->attributes = array('class' => 'generaltable fullwidth appraisallist');

        $stractivate = get_string('activate', 'totara_appraisal');
        $strclose = get_string('close', 'totara_appraisal');
        $strsettings = get_string('settings', 'totara_appraisal');
        $strdelete = get_string('delete', 'totara_appraisal');
        $strcannotdelete = get_string('error:appraisalisactive', 'totara_appraisal');
        $strnoteditable = get_string('error:appraisalnoteditable', 'totara_appraisal');
        $strclone = get_string('copy', 'moodle');

        $systemcontext = context_system::instance();

        $data = array();
        foreach ($appraisals as $appraisal) {
            $name = format_string($appraisal->name);
            $activateurl = new moodle_url('/totara/appraisal/activation.php',
                    array('id' => $appraisal->id, 'action' => 'activate'));
            $closeurl = new moodle_url('/totara/appraisal/activation.php',
                    array('id' => $appraisal->id, 'action' => 'close'));
            $editurl = new moodle_url('/totara/appraisal/general.php',
                    array('id' => $appraisal->id));
            $assignurl = new moodle_url('/totara/appraisal/learners.php',
                    array('appraisalid' => $appraisal->id));
            $deleteurl = new moodle_url('/totara/appraisal/manage.php',
                    array('id' => $appraisal->id, 'action' => 'delete'));
            $cloneurl = new moodle_url('/totara/appraisal/manage.php',
                    array('id' => $appraisal->id, 'action' => 'copy', 'sesskey' => sesskey()));

            $row = array();
            if (has_capability('totara/appraisal:manageappraisals', $systemcontext, $userid)) {
                $row[] = html_writer::link($editurl, $name);
            } else {
                $row[] = $name;
            }
            if ($appraisal->timestarted) {
                $row[] = userdate($appraisal->timestarted, get_string('strfdateshortmonth', 'langconfig'));
            } else {
                $row[] = get_string('none', 'totara_appraisal');
            }

            $a = new stdClass();
            $a->total = $appraisal->lnum;
            $a->completed = $appraisal->cnum;
            $row[] = get_string('assignnumcompleted', 'totara_appraisal', $a);

            $row[] = appraisal::display_status($appraisal->status);

            $options = '';
            if (has_capability('totara/appraisal:manageappraisals', $systemcontext, $userid)) {
                if ($appraisal->status == appraisal::STATUS_ACTIVE) {
                    if (empty($CFG->dynamicappraisals)) {
                        $options .= $this->output->pix_icon('/t/edit_gray', $strnoteditable, 'moodle', array('class' => 'disabled iconsmall'));
                    } else {
                        $options .= $this->output->action_icon($assignurl, new pix_icon('/t/edit', $strsettings, 'moodle'));
                    }
                    $options .= $this->output->action_icon($cloneurl, new pix_icon('/t/copy', $strclone, 'moodle'));
                    $options .= $this->output->pix_icon('/t/delete_gray', $strcannotdelete, 'moodle', array('class' => 'disabled iconsmall'));
                } else {
                    $options .= $this->output->action_icon($editurl, new pix_icon('/t/edit', $strsettings, 'moodle'));
                    $options .= $this->output->action_icon($cloneurl, new pix_icon('/t/copy', $strclone, 'moodle'));
                    $options .= $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'));
                }
            }

            $activate = '';
            if (has_capability('totara/appraisal:manageactivation', $systemcontext, $userid)) {
                if ($appraisal->status == appraisal::STATUS_ACTIVE) {
                    $activate = $this->output->action_link($closeurl, $strclose);
                } else if ($appraisal->status == appraisal::STATUS_DRAFT) {
                    $activate = $this->output->action_link($activateurl, $stractivate);
                }
            }
            $row[] = $options . ' ' . $activate;

            $data[] = $row;
        }
        $appraisalstable->data = $data;

        return html_writer::table($appraisalstable);
    }


    public function appraisal_management_tabs($appraisalid, $currenttab = 'general') {
        global $CFG;

        $tabs = array();
        $row = array();
        $activated = array();
        $inactive = array();

        if ($appraisalid < 1) {
            $inactive = array('content', 'learners', 'messages');
        }

        $systemcontext = context_system::instance();
        if (has_capability('totara/appraisal:manageappraisals', $systemcontext)) {
            $row[] = new tabobject('general', $CFG->wwwroot . '/totara/appraisal/general.php?id=' .
                    $appraisalid, get_string('general'));
        }
        if (has_capability('totara/appraisal:managepageelements', $systemcontext)) {
            $row[] = new tabobject('content', $CFG->wwwroot . '/totara/appraisal/stage.php?appraisalid=' .
                    $appraisalid, get_string('content', 'totara_appraisal'));
        }
        $capabilities = array('totara/appraisal:viewassignedusers', 'totara/appraisal:assignappraisaltogroup');
        if (has_any_capability($capabilities, $systemcontext)) {
            $row[] = new tabobject('learners', $CFG->wwwroot . '/totara/appraisal/learners.php?appraisalid=' .
                    $appraisalid, get_string('assignments', 'totara_appraisal'));
        }
        if (has_capability('totara/appraisal:managenotifications', $systemcontext)) {
            $row[] = new tabobject('messages', $CFG->wwwroot . '/totara/appraisal/message.php?id=' .
                    $appraisalid, get_string('messages', 'totara_appraisal'));
        }

        $tabs[] = $row;
        $activated[] = $currenttab;

        return print_tabs($tabs, $currenttab, $inactive, $activated, true);
    }


    /**
     * Renders a table containing appraisals list for messages
     *
     * @param array $events array of events messages
     * @return string HTML table
     */
    public function appraisal_message_table(array $events) {
        if (empty($events)) {
            return get_string('nomessages', 'totara_appraisal');
        }

        $tableheader = array(get_string('messagetitle', 'totara_appraisal'),
                             get_string('messageevent', 'totara_appraisal'),
                             get_string('messagetiming', 'totara_appraisal'),
                             get_string('messagerecipients', 'totara_appraisal'),
                             get_string('options', 'totara_appraisal'));

        $messagetable = new html_table();
        $messagetable->summary = '';
        $messagetable->head = $tableheader;
        $messagetable->data = array();
        $messagetable->attributes = array('class' => 'generaltable fullwidth');

        $strsettings = get_string('settings', 'totara_appraisal');
        $strdelete = get_string('delete', 'totara_appraisal');

        $data = array();
        foreach ($events as $event) {
            $messages = $event->messages;
            $name = format_string(current($messages)->name);

            $editurl = new moodle_url('/totara/appraisal/message.php',
                    array('id' => $event->appraisalid, 'messageid' => $event->id, 'action' => 'edit'));
            $deleteurl = new moodle_url('/totara/appraisal/message.php',
                    array('id' => $event->appraisalid, 'messageid' => $event->id, 'action' => 'delete'));

            $row = array();
            $row[] = html_writer::link($editurl, $name);
            $row[] = $this->display_event($event->type);
            $row[] = $this->display_timing($event->delta, $event->deltaperiod);
            $row[] = $this->display_roles(array_flip($event->roles));

            $options = $this->output->action_icon($editurl, new pix_icon('/t/edit', $strsettings, 'moodle'));
            $options .= $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'));

            $row[] = $options;
            $data[] = $row;
        }
        $messagetable->data = $data;

        return html_writer::table($messagetable);
    }


    /**
     * Render event type
     *
     * @param string $type
     * @return string
     */
    public function display_event($type) {
        // Events: activation, stage_due, stage_completion.
        $strevent = get_string($type, 'totara_appraisal');
        return $strevent;
    }


    /**
     * Display time when message should be sent
     *
     * @param int $delta
     * @param int $period
     * @return string
     */
    public function display_timing($delta, $period) {
        if ($delta == 0) {
            return get_string('immediatecron', 'totara_appraisal');
        }
        $str = new stdClass();
        $str->delta = abs($delta);
        switch ($period) {
            case appraisal_message::PERIOD_DAY:
                $str->period = get_string('perioddays', 'totara_appraisal');
                break;
            case appraisal_message::PERIOD_WEEK;
                $str->period = get_string('periodweeks', 'totara_appraisal');
                break;
            case appraisal_message::PERIOD_MONTH:
                $str->period = get_string('periodmonths', 'totara_appraisal');
                break;
        }

        if ($delta < 0) {
            return get_string('eventbefore', 'totara_appraisal', $str);
        } else {
            return get_string('eventafter', 'totara_appraisal', $str);
        }
    }


    /**
     * Renders a table containing active appraisals list for the management report
     *
     * @param array $appraisals array of appraisal object
     * @return string HTML table
     */
    public function report_active_table($appraisals = array()) {
        if (empty($appraisals)) {
            return get_string('noappraisalsactive', 'totara_appraisal');
        }

        $tableheader = array(get_string('name', 'totara_appraisal'),
                             get_string('overdue', 'totara_appraisal'),
                             get_string('ontarget', 'totara_appraisal'),
                             get_string('cancelled', 'totara_appraisal'),
                             get_string('complete', 'totara_appraisal'),
                             get_string('reports', 'totara_appraisal'));

        $appraisalstable = new html_table();
        $appraisalstable->summary = '';
        $appraisalstable->head = $tableheader;
        $appraisalstable->data = array();
        $appraisalstable->attributes = array('class' => 'generaltable');

        $data = array();
        foreach ($appraisals as $appraisal) {
            $row = array();

            $appraisalurl = new moodle_url('/totara/appraisal/general.php',
                    array('id' => $appraisal->id));
            $statusreporturl = new moodle_url('/totara/appraisal/statusreport.php',
                    array('appraisalid' => $appraisal->id, 'clearfilters' => 1));
            $detailreporturl = new moodle_url('/totara/appraisal/detailreport.php',
                    array('appraisalid' => $appraisal->id, 'clearfilters' => 1));

            $row[] = html_writer::link($appraisalurl, format_string($appraisal->name));

            if (!($appraisal->userscomplete > 0)) {
                $appraisal->userscomplete = 0;
            }
            if (!($appraisal->usersoverdue > 0)) {
                $appraisal->usersoverdue = 0;
            }
            if ($appraisal->usersoverdue > 0) {
                $statusreporturl->param('filterstatus', 'statusoverdue');
                $row[] = html_writer::link($statusreporturl, $appraisal->usersoverdue);
            } else {
                $row[] = '0';
            }

            if ($appraisal->usersontarget > 0) {
                $statusreporturl->param('filterstatus', 'statusontarget');
                $row[] = html_writer::link($statusreporturl, $appraisal->usersontarget);
            } else {
                $row[] = '0';
            }

            if ($appraisal->userscancelled > 0) {
                $statusreporturl->param('filterstatus', 'statuscancelled');
                $row[] = html_writer::link($statusreporturl, $appraisal->userscancelled);
            } else {
                $row[] = '0';
            }

            if ($appraisal->userscomplete > 0) {
                $statusreporturl->param('filterstatus', 'statuscomplete');
                $row[] = html_writer::link($statusreporturl, $appraisal->userscomplete);
            } else {
                $row[] = '0';
            }

            $statusreporturl->remove_params('filterstatus');
            $row[] = html_writer::link($statusreporturl, get_string('statusreport', 'totara_appraisal')) . ' ' .
                     html_writer::link($detailreporturl, get_string('detailreport', 'totara_appraisal'));

            $data[] = $row;
        }
        $appraisalstable->data = $data;

        return html_writer::table($appraisalstable);
    }


    /**
     * Renders a table containing inactive appraisals list for the management report
     *
     * @param array $appraisals array of appraisal object
     * @return string HTML table
     */
    public function report_inactive_table($appraisals = array()) {
        if (empty($appraisals)) {
            return get_string('noappraisalsinactive', 'totara_appraisal');
        }

        $tableheader = array(get_string('name', 'totara_appraisal'),
                             get_string('status', 'totara_appraisal'),
                             get_string('finished', 'totara_appraisal'),
                             get_string('complete', 'totara_appraisal'),
                             get_string('incomplete', 'totara_appraisal'),
                             get_string('reports', 'totara_appraisal'));

        $appraisalstable = new html_table();
        $appraisalstable->summary = '';
        $appraisalstable->head = $tableheader;
        $appraisalstable->data = array();
        $appraisalstable->attributes = array('class' => 'generaltable');

        $data = array();
        foreach ($appraisals as $appraisal) {
            $row = array();

            $appraisalurl = new moodle_url('/totara/appraisal/general.php',
                    array('id' => $appraisal->id));
            $statusreporturl = new moodle_url('/totara/appraisal/statusreport.php',
                    array('appraisalid' => $appraisal->id, 'clearfilters' => 1));
            $detailreporturl = new moodle_url('/totara/appraisal/detailreport.php',
                    array('appraisalid' => $appraisal->id, 'clearfilters' => 1));

            $row[] = html_writer::link($appraisalurl, format_string($appraisal->name));

            $row[] = appraisal::display_status($appraisal->status);

            $row[] = userdate($appraisal->timefinished, get_string('strftimedate', 'langconfig'));

            if ($appraisal->userscomplete > 0) {
                $statusreporturl->param('filterstatus', 'statuscomplete');
                $row[] = html_writer::link($statusreporturl, $appraisal->userscomplete);
            } else {
                $row[] = '0';
            }

            if ($appraisal->userstotal - $appraisal->userscomplete > 0) {
                $statusreporturl->param('filterstatus', 'statusincomplete');
                $row[] = html_writer::link($statusreporturl, $appraisal->userstotal - $appraisal->userscomplete);
            } else {
                $row[] = '0';
            }

            $statusreporturl->remove_params('filterstatus');
            $row[] = html_writer::link($statusreporturl, get_string('statusreport', 'totara_appraisal')) . ' ' .
                     html_writer::link($detailreporturl, get_string('detailreport', 'totara_appraisal'));

            $data[] = $row;
        }
        $appraisalstable->data = $data;

        return html_writer::table($appraisalstable);
    }


    /**
     * Renders a table containing appraisals for the detail report
     *
     * @param int $detailreportid id of the report
     * @param array $appraisals array of appraisal object
     * @return string HTML table
     */
    public function detail_report_table($detailreportid, $appraisals = array()) {
        if (empty($appraisals)) {
            return get_string('noappraisals', 'totara_appraisal');
        }

        $tableheader = array(get_string('name', 'totara_appraisal'),
                             get_string('start', 'totara_appraisal'),
                             get_string('learners', 'totara_appraisal'),
                             get_string('status', 'totara_appraisal'));

        $table = new html_table();
        $table->summary = '';
        $table->head = $tableheader;
        $table->data = array();
        $table->attributes = array('class' => 'generaltable');

        $data = array();
        foreach ($appraisals as $appraisal) {
            $row = array();

            $detailreporturl = new moodle_url('/totara/reportbuilder/report.php',
                    array('id' => $detailreportid, 'appraisalid' => $appraisal->id, 'clearfilters' => 1));

            $row[] = html_writer::link($detailreporturl, format_string($appraisal->name));

            if ($appraisal->timestarted) {
                $row[] = userdate($appraisal->timestarted, get_string('strfdateshortmonth', 'langconfig'));
            } else {
                $row[] = get_string('none', 'totara_appraisal');
            }

            $row[] = $appraisal->lnum;

            $row[] = appraisal::display_status($appraisal->status);

            $data[] = $row;
        }
        $table->data = $data;

        return html_writer::table($table);
    }


    /**
     * Renders a table containing appraisals list for user
     * It is assumed that user allowed access to listed appraisals
     *
     * @param array $appraisals array of stdClass appraisal data
     * @param int $role
     * @return string HTML table
     */
    public function display_user_appraisals($appraisals, $role) {
        if (empty($appraisals)) {
            return get_string('noappraisals', 'totara_appraisal');
        }

        $tableheader = array();
        if ($role != appraisal::ROLE_LEARNER) {
            $tableheader[] = get_string('name', 'totara_appraisal');
        }
        $tableheader[] = get_string('appraisal', 'totara_appraisal');
        $tableheader[] = get_string('datestart', 'totara_appraisal');
        $tableheader[] = get_string('dateend', 'totara_appraisal');
        $tableheader[] = get_string('status', 'totara_appraisal');

        $appraisalstable = new html_table();
        $appraisalstable->summary = '';
        $appraisalstable->head = $tableheader;
        $appraisalstable->data = array();
        $appraisalstable->attributes = array('class' => 'generaltable fullwidth');

        $systemcontext = context_system::instance();

        $data = array();
        foreach ($appraisals as $appraisal) {
            $name = format_string($appraisal->name);

            $params = array('role' => $role, 'subjectid' => $appraisal->userid, 'appraisalid' => $appraisal->id,
                'action' => 'stages');
            $editurl = new moodle_url('/totara/appraisal/myappraisal.php', $params);

            $row = array();

            if ($role != appraisal::ROLE_LEARNER) {
                $row[] = fullname($appraisal->user);
            }

            if (has_capability('totara/appraisal:viewownappraisals', $systemcontext)) {
                $namelink = html_writer::link($editurl, $name);
                $snapshots = appraisal::list_snapshots($appraisal->id, $appraisal->roleassignmentid);
                $snapstr = '';
                foreach ($snapshots as $snapshot) {
                    $pic = $this->output->pix_icon("f/".mimeinfo("icon", $snapshot->filename), $snapshot->filename);
                    $link = $this->output->action_link($snapshot->url, get_string('snapshotname', 'totara_appraisal', $snapshot));
                    $snapstr .= html_writer::tag('div', $pic.$link, array('class' => 'snapshot'));
                }
                $row[] = $namelink.$snapstr;
            } else {
                $row[] = $name;
            }

            if ($appraisal->timestarted) {
                $row[] = userdate($appraisal->timestarted, get_string('strfdateshortmonth', 'langconfig'));
            } else {
                $row[] = get_string('none', 'totara_appraisal');
            }

            if ($appraisal->timedue) {
                $row[] = userdate($appraisal->timedue, get_string('strfdateshortmonth', 'langconfig'));
            } else {
                $row[] = get_string('none', 'totara_appraisal');
            }

            if (!empty($appraisal->timecompleted)) {
                $row[] = appraisal::display_status(appraisal::STATUS_COMPLETED);
            } else {
                $row[] = appraisal::display_status($appraisal->status);
            }

            $data[] = $row;
        }
        $appraisalstable->data = $data;

        return html_writer::table($appraisalstable);
    }


    /**
     * Renders table with appraisal stages
     *
     * @param array $stages array of appraisal stage object
     * @param int $userid User id to show actions according their rights
     * @param int $stageid Active stage
     * @return string HTML table
     */
    public function appraisal_stages_table($stages = array(), $userid = 0, $stageid = 0) {
        global $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        if (empty($stages)) {
            return get_string('nostages', 'totara_appraisal');
        }

        $tableheader = array(get_string('stageheader', 'totara_appraisal'),
                             get_string('completeby', 'totara_appraisal'),
                             get_string('rolescananswer', 'totara_appraisal') .
                                    $this->output->help_icon('rolescananswer', 'totara_appraisal', null),
                             get_string('rolescanview', 'totara_appraisal') .
                                    $this->output->help_icon('rolescanview', 'totara_appraisal', null),
                             get_string('options', 'totara_appraisal'));

        $stagestable = new html_table();
        $stagestable->summary = '';
        $stagestable->head = $tableheader;
        $stagestable->data = array();
        $stagestable->attributes['class'] = 'generaltable appraisal-stages fullwidth';

        $strsettings = get_string('settings', 'totara_appraisal');
        $strdelete = get_string('delete', 'totara_appraisal');
        $strview = get_string('view');

        $data = array();
        foreach ($stages as $stage) {
            $contenturl = new moodle_url('/totara/appraisal/stage.php',
                    array('appraisalid' => $stage->appraisalid, 'id' => $stage->id));
            $editurl = new moodle_url('/totara/appraisal/stage.php',
                    array('appraisalid' => $stage->appraisalid, 'action' => 'edit', 'id' => $stage->id));
            $deleteurl = new moodle_url('/totara/appraisal/stage.php',
                    array('appraisalid' => $stage->appraisalid, 'action' => 'delete', 'id' => $stage->id));
            $settings = $this->output->action_icon($editurl, new pix_icon('/t/edit', $strsettings, 'moodle'));
            $viewsettings = $this->output->action_icon($editurl, new pix_icon('/t/preview', $strview, 'moodle'));
            $delete = $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'), null,
                    array('class' => 'action-icon delete'));

            $row = new html_table_row();
            if ($stageid == $stage->id) {
                $row->attributes['class'] = 'selected';
            }
            $row->cells[] = $this->output->action_link($contenturl, format_string($stage->name), null,
                    array('data-id' => $stage->id, 'data-type' => 'stage', 'class' => 'appraisal-stagelink'));
            if ($stage->timedue) {
                $row->cells[] = userdate($stage->timedue, get_string('strfdateshortmonth', 'langconfig'));
            } else {
                $row->cells[] = get_string('none', 'totara_appraisal');
            }

            if ($stage->roles) {
                $row->cells[] = '<span class="appraisal-rolescananswer">' . $this->display_roles($stage->roles) .'</span>';
            } else {
                $row->cells[] = '<span class="appraisal-rolescananswer">' . get_string('none', 'totara_appraisal') .'</span>';
            }

            $stageobj = new appraisal_stage($stage->id);
            $allviewers = $stageobj->get_roles_involved(appraisal::ACCESS_CANVIEWOTHER);
            $viewers = array_diff($allviewers, array_keys($stage->roles));

            if (!empty($viewers)) {
                $row->cells[] = '<span class="appraisal-rolescanview">' . $this->display_roles(array_flip($viewers)) .'</span>';
            } else {
                $row->cells[] = '<span class="appraisal-rolescanview">' . get_string('none', 'totara_appraisal') .'</span>';
            }

            if (appraisal::is_draft($stage->appraisalid)) {
                $row->cells[] = "{$settings}{$delete}";
            } else {
                $row->cells[] = $viewsettings;
            }

            $data[] = $row;
        }
        $stagestable->data = $data;

        return html_writer::table($stagestable);
    }


    /**
     * Display 1..n roles by their code
     *
     * @param mixed $roles array of int or int
     * @return string Roles concatenated by comma
     */
    public function display_roles($roles) {
        if (is_string($roles)) {
            $roles = array($roles);
        }
        $strroles = array();
        $appraisalroles = appraisal::get_roles();
        foreach ($roles as $role => $rolename) {
            if (isset($appraisalroles[$role])) {
                $strroles[] = get_string($appraisalroles[$role], 'totara_appraisal');
            } else {
                switch($role) {
                    case appraisal::ROLE_ADMINISTRATOR:
                        $strroles[] = get_string('roleadministrator', 'totara_appraisal');
                        break;
                }
            }
        }
        return implode(', ', $strroles);
    }


    /**
     * Get status name and call to action
     *
     * @param int $status
     * @param int $id
     * @return string
     */
    public function appraisal_additional_actions($status, $id) {
        $activateurl = new moodle_url('/totara/appraisal/activation.php', array('id' => $id, 'action' => 'activate'));
        $closeurl = new moodle_url('/totara/appraisal/activation.php', array('id' => $id, 'action' => 'close'));
        $previewurl = new moodle_url('/totara/appraisal/myappraisal.php',
                array('appraisalid' => $id, 'preview' => 1));

        $strstatusnow = appraisal::display_status($status);
        $strstatusat = get_string('statusat', 'totara_appraisal');
        $strpreviewappraisal = get_string('previewappraisal', 'totara_appraisal');
        $appraisal = new appraisal($id);

        // Render preview button, opening in a popup window.
        $button = new single_button($previewurl, $strpreviewappraisal);
        $button->class .= ' appraisal-previewer';
        $popupaction = new popup_action('click', $previewurl, 'previewappraisal',
            array('toolbar' => false, 'height' => 800, 'width' => 1000));
        $button->actions[] = $popupaction;
        $previewbutton = $this->output->render($button);

        if ($appraisal->status == appraisal::STATUS_ACTIVE) {
            $activate = $this->output->action_link($closeurl, get_string('close', 'totara_appraisal'));
        } else if ($appraisal->status == appraisal::STATUS_DRAFT) {
            $activate = $this->output->action_link($activateurl,  get_string('activatenow', 'totara_appraisal'));
        } else {
            $activate = '';
        }
        return $strstatusat.' '.$strstatusnow.' '.$activate.' '.$previewbutton;
    }


    /**
     * Return a button that when clicked, takes the user to appraisal creation form
     *
     * @return string HTML to display the button
     */
    public function create_appraisal_button() {
        return $this->output->single_button(new moodle_url('/totara/appraisal/general.php'),
                get_string('createappraisal', 'totara_appraisal'), 'get');
    }

    /**
     * Return a button that when clicked, takes the user to appraisal message creation form
     *
     * @return string HTML to display the button
     */
    public function create_message_button($appraisalid) {
        return $this->output->single_button(new moodle_url('/totara/appraisal/message.php',
                array('id' => $appraisalid, 'action' => 'edit')), get_string('messagecreate', 'totara_appraisal'), 'get');
    }


    /**
     * Return a button that takes user to appraisal stage createion form
     *
     * @param object $appraisal
     * @return string HTML to display the button
     */
    public function create_stage_button($appraisal) {
        if (appraisal::is_draft($appraisal)) {
            return $this->output->single_button(new moodle_url('/totara/appraisal/stage.php',
                    array('appraisalid' => $appraisal->id, 'action' => 'edit')),
                    get_string('addstage', 'totara_appraisal'), 'get');
        }
        return '';
    }


    /**
     * Return link to new page form for current stage for draft appraisal
     *
     * @param appraisal_stage $stage
     * @return string HTML
     */
    public function create_new_page_link(appraisal_stage $stage) {
        $action = '';
        if (appraisal::is_draft($stage->appraisalid)) {
            $stageid = $stage->id;
            $newpageurl = new moodle_url('/totara/appraisal/ajax/page.php',
                    array('appraisalstageid' => $stageid));
            $strnewpage = get_string('addpage', 'totara_appraisal');
            $action = $this->output->action_link($newpageurl, $strnewpage, null,
                    array('id' => 'appraisal-add-page', 'class' => 'btn btn-primary'));
        }
        return $action;
    }


    /**
     * Returns list of pages of particular stage
     *
     * @param array $pages of stdClass
     * @param int $pageid Current page id
     * @param appraisal_stage $stage
     * @return string
     */
    public function list_pages($pages, $pageid, appraisal_stage $stage) {
        $list = array();
        if ($pages) {
            $stredit = get_string('settings', 'totara_question');
            $strdelete = get_string('delete', 'totara_question');
            $strup =  get_string('moveup', 'totara_question');
            $strdown =  get_string('movedown', 'totara_question');
            $last = end($pages);
            $first = reset($pages);
            foreach ($pages as $page) {
                $posuplink = $posdownlink = '';
                $attrs = ($pageid == $page->id) ? array('class' => 'selected') : array('class' => '');
                $attrs['data-pageid'] = $page->id;
                $attrs['data-type'] = 'page';
                if ($page->id != $first->id) {
                    $posupurl = new moodle_url('/totara/appraisal/ajax/page.php', array('action' => 'posup',
                        'id' => $page->id, 'sesskey' => sesskey()));
                    $posuplink = $this->output->action_icon($posupurl, new pix_icon('/t/up', $strup, 'moodle'), null,
                            array('class' => 'action-icon js-hide'));
                } else {
                    $attrs['class'] .= ' first';
                    $posuplink = $this->output->spacer(array('width' => '21', 'height' => '15'));
                }
                if ($page->id != $last->id) {
                    $posdownurl = new moodle_url('/totara/appraisal/ajax/page.php', array('action' => 'posdown',
                            'id' => $page->id, 'sesskey' => sesskey()));
                    $posdownlink = $this->output->action_icon($posdownurl, new pix_icon('/t/down', $strdown, 'moodle'), null,
                            array('class' => 'action-icon js-hide'));
                } else {
                    $attrs['class'] .= ' last';
                    $posdownlink = $this->output->spacer(array('width' => '21', 'height' => '15'));
                }
                $pageurl = new moodle_url('/totara/appraisal/ajax/question.php', array('appraisalstagepageid' => $page->id));
                $editurl = new moodle_url('/totara/appraisal/ajax/page.php', array('action' => 'edit', 'id' => $page->id));
                $deleteurl = new moodle_url('/totara/appraisal/ajax/page.php', array('action' => 'delete', 'id' => $page->id));

                if ($page->hasredisplay) {
                    $deleteurl->param('hasredisplay', 'true');
                }

                $dragdrop = $this->pix_icon('/i/dragdrop', '', 'moodle', array('class' => 'iconsmall js-show-inline move'));
                $editlink = $this->output->action_icon($editurl, new pix_icon('/t/edit', $stredit, 'moodle'), null,
                        array('class' => 'action-icon edit'));
                $deletelink = $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'), null,
                        array('class' => 'action-icon delete'));
                $pagelink = $this->output->action_link($pageurl, format_string($page->name), null,
                        array('class' => 'appraisal-page-list-name', 'data-pageid' => $page->id));

                $actions = '';
                if (appraisal::is_draft($stage->appraisalid)) {
                    $actions = html_writer::tag('span', $posuplink . $posdownlink . $dragdrop . $editlink . $deletelink,
                            array('class'=>'appraisal-page-actions'));
                }

                $list[] = html_writer::tag('li', $pagelink.$actions, $attrs);
            }
            $nav = html_writer::tag('ul', implode($list), array('id'=>'appraisal-page-list',
                'class' => 'appraisal-page-list yui-nav'));
            return html_writer::tag('div', $nav, array('class' => 'yui-u first'));
        }
        return '';
    }


    /**
     * Retruns list of questions of particular page
     *
     * @param array $quests of stdClass
     * @param appraisal_stage $stage
     * @return string
     */
    public function list_questions($quests, appraisal_stage $stage) {
        $list = array();
        if ($quests) {
            $stredit = get_string('settings', 'totara_question');
            $strduplicate = get_string('copy');
            $strdelete = get_string('delete', 'totara_question');
            $strup =  get_string('moveup', 'totara_question');
            $strdown =  get_string('movedown', 'totara_question');
            $strview = get_string('view');
            $last = end($quests);
            $first = reset($quests);
            // Get the list of question types that are supported. We'll ignore if
            // they're disabled in case the feature was enabled when it was added.
            $questtypes = question_manager::get_registered_elements(false);

            foreach ($quests as $quest) {
                $question = new appraisal_question($quest->id);
                $posuplink = $posdownlink = '';
                $attrs['data-questid'] = $quest->id;
                $attrs['data-type'] = 'question';
                if ($quest->id != $first->id) {
                    $posupurl = new moodle_url('/totara/appraisal/ajax/question.php', array('action' => 'posup',
                        'id' => $quest->id, 'sesskey' => sesskey()));
                    $posuplink = $this->output->action_icon($posupurl, new pix_icon('/t/up', $strup, 'moodle'), null,
                            array('class' => 'action-icon js-hide'));
                } else {
                    $attrs['class'] = ' first';
                    $posuplink = $this->output->spacer(array('width' => '21', 'height' => '15'));
                }
                if ($quest->id != $last->id) {
                    $posdownurl = new moodle_url('/totara/appraisal/ajax/question.php', array('action' => 'posdown',
                            'id' => $quest->id, 'sesskey' => sesskey()));
                    $posdownlink = $this->output->action_icon($posdownurl, new pix_icon('/t/down', $strdown, 'moodle'), null,
                            array('class' => 'action-icon js-hide'));
                } else {
                    $attrs['class'] .= ' last';
                    $posdownlink = $this->output->spacer(array('width' => '21', 'height' => '15'));
                }
                $editurl = new moodle_url('/totara/appraisal/ajax/question.php', array('action' => 'edit',
                    'id' => $quest->id, 'sesskey' => sesskey()));
                $duplicateurl = new moodle_url('/totara/appraisal/ajax/question.php', array('action' => 'duplicate',
                    'id' => $quest->id, 'sesskey' => sesskey()));
                $deleteurl = new moodle_url('/totara/appraisal/ajax/question.php', array('action' => 'delete',
                    'id' => $quest->id, 'sesskey' => sesskey()));

                if ($quest->hasredisplay) {
                    $deleteurl->param('hasredisplay', 'true');
                    $redisplayed = $this->output->pix_icon('link', get_string('hasredisplayitems', 'totara_appraisal'),
                            'totara_appraisal', array('class' => 'action-icon iconsmall redirect'));
                } else {
                    $redisplayed = '';
                }

                $dragdrop = $this->pix_icon('i/dragdrop', '', 'moodle', array('class' => 'iconsmall js-show-inline move'));
                $viewlink = $this->output->action_icon($editurl, new pix_icon('t/preview', $strview, 'moodle'), null,
                    array('class' => 'action-icon view'));
                $editlink = $this->output->action_icon($editurl, new pix_icon('t/edit', $stredit, 'moodle'), null,
                        array('class' => 'action-icon edit'));
                $duplicatelink = $this->output->action_icon($duplicateurl, new pix_icon('t/copy', $strduplicate, 'moodle'), null,
                        array('class' => 'action-icon copy'));
                $deletelink = $this->output->action_icon($deleteurl, new pix_icon('t/delete', $strdelete, 'moodle'), null,
                        array('class' => 'action-icon delete'));

                $questtext = html_writer::tag('strong', format_string($question->get_name())) .
                             html_writer::empty_tag('br') .
                             html_writer::tag('label', $questtypes[$quest->datatype]['type']);
                if ($question->is_invalid_redisplay()) {
                    $questtext .= html_writer::tag('div',
                        $this->output->notification(get_string('error:redisplayoutoforder', 'totara_appraisal'), 'notifyproblem'),
                        array('class' => 'nobox redisplay-notification-nomargin'));
                }
                $strquest = html_writer::tag('span', $questtext, array('class' => 'appraisal-quest-list-name'));

                $actions = '';
                if (appraisal::is_draft($stage->appraisalid)) {
                    $actions = html_writer::tag('span',
                            $redisplayed . $posuplink . $posdownlink . $dragdrop . $editlink . $duplicatelink . $deletelink,
                            array('class'=>'appraisal-quest-actions'));
                } else {
                    $actions = html_writer::tag('span', $viewlink, array('class'=>'appraisal-quest-actions'));
                }
                $list[] = html_writer::tag('li', $actions.$strquest, $attrs);
            }
            $nav = html_writer::tag('ul', implode($list), array('id'=>'appraisal-quest-list',
                'class' => 'appraisal-quest-list yui-nav'));
            return html_writer::tag('div', $nav, array('class' => 'yui-u first'));
        }
        return '';
    }


    /**
     * Return stage content container (pages)
     *
     * @param appraisal_stage $stage
     * @param array $pages of stdClass
     * @param int $pageid Currently selected page
     * @return string HTML
     */
    public function stage_page_container($stage = null, $pages = null, $pageid = 0) {
        if (!$stage) {
            return '';
        }
        $header = $this->heading(get_string('managestage', 'totara_appraisal', format_string($stage->name)), 3);
        $contentpane = html_writer::tag('div', '', array('id' => 'appraisal-questions',
            'class' => 'appraisal-content'));
        $create_page = $this->create_new_page_link($stage);
        $pagepane = html_writer::tag('div', $this->list_pages($pages, $pageid, $stage).$create_page,
                    array('class' => 'appraisal-page-pane'));
        $pagecontainer = html_writer::tag('div', $pagepane.$contentpane, array('class' => 'appraisal-page-container'));
        return html_writer::tag('div', $header.$pagecontainer,
                array('id' => 'appraisalstagecontainer'));
    }


    /**
     * Confirm appraisal activation
     *
     * @param appraisal $appraisal
     * @param array $errors
     * @return string HTML
     */
    public function confirm_appraisal_activation($appraisal, $errors, $warnings) {
        global $CFG;

        $out = '';
        if (!empty($errors)) {
            $out .= $this->heading(get_string('appraisalinvalid', 'totara_appraisal'));
            // Output the errors to the screen.
            $out .= html_writer::tag('p', get_string('appraisalfixerrors', 'totara_appraisal'));
            $errordesc = array();
            foreach ($errors as $error) {
                $errordesc[] = html_writer::tag('li', $error);
            }
            $out .= html_writer::tag('ul', implode('', $errordesc), array('class' => 'appraisalerrorlist'));
            // Output the warnings to the screen.
            if (!empty($warnings)) {
                $out .= html_writer::tag('p', get_string('appraisalfixwarnings', 'totara_appraisal'));
                $warndesc = array();
                foreach ($warnings as $warn) {
                    $warndesc[] = html_writer::tag('li', $warn);
                }
                $out .= html_writer::tag('ul', implode('', $warndesc), array('class' => 'appraisalerrorlist'));
            }
            $buttons = array();
            $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/stage.php',
                    array('appraisalid' => $appraisal->id)), get_string('backtoappraisal', 'totara_appraisal',
                    format_string($appraisal->name)), 'get');
            $out .= html_writer::tag('div', implode(' ', $buttons), array('class' => 'buttons'));
        } else {
            $warnstr = empty($CFG->dynamicappraisals) ? 'appraisalstaticlastwarning' : 'appraisaldynamiclastwarning';
            $out .= html_writer::tag('p', get_string($warnstr, 'totara_appraisal'));
            // Output the warnings to the screen.
            if (!empty($warnings)) {
                $out .= html_writer::tag('p', get_string('confirmactivatewarning', 'totara_appraisal'));
                $warndesc = array();
                foreach ($warnings as $warn) {
                    $warndesc[] = html_writer::tag('li', $warn);
                }
                $out .= html_writer::tag('ul', implode('', $warndesc), array('class' => 'appraisalerrorlist'));
            } else {
                $out .= html_writer::tag('p', get_string('confirmactivateappraisal', 'totara_appraisal'));
            }
            $buttons = array();
            $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/activation.php',
                    array('action' => 'activate', 'confirm' => 1, 'id' => $appraisal->id)),
                    get_string('activate', 'totara_appraisal'), 'post');
            $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/stage.php',
                    array('appraisalid' => $appraisal->id)), get_string('backtoappraisal', 'totara_appraisal',
                    format_string($appraisal->name)), 'get');
            $out .= html_writer::tag('div', implode(' ', $buttons), array('class' => 'buttons'));
        }
        return $out;
    }


    /**
     * Confirm appraisal close
     *
     * @param appraisal $appraisal
     * @return string HTML
     */
    public function confirm_appraisal_close($incompleteusers) {
        return html_writer::tag('p', get_string('closeusersincomplete', 'totara_appraisal', $incompleteusers));
    }


    /**
     * Confirm user delete appraisal
     *
     * @param int $appraisalid
     * @param array $content
     * @return string HTML
     */
    public function confirm_delete_appraisal($appraisalid, array $content) {
        $out = '';
        if (count($content['stages']) > 0) {
            $out .= get_string('appraisalhasstages', 'totara_appraisal');
            $stages = '';
            foreach ($content['stages'] as $stage) {
                $stages .= html_writer::tag('li', format_string($stage->name));
            }
            $out .= html_writer::tag('ul', $stages);
        }
        $out .= html_writer::tag('p', get_string('confirmdeleteappraisal', 'totara_appraisal'));

        $buttons = array();
        $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/manage.php',
                array('action' => 'delete', 'confirm' => 1, 'id' => $appraisalid)),
                get_string('delete', 'totara_appraisal'), 'post');
        $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/manage.php'),
                get_string('cancel', 'moodle'), 'get');
        $out .= html_writer::tag('div', implode(' ', $buttons), array('class' => 'buttons'));
        return $out;
    }


    /**
     * Confirm user delete stage
     *
     * @param int $stageid
     * @return string HTML
     */
    public function confirm_delete_stage($stageid) {
        if (appraisal_stage::has_redisplayed_items($stageid)) {
            $out = html_writer::tag('p', get_string('confirmdeletestagewithredisplay', 'totara_appraisal'));
        } else {
            $out = html_writer::tag('p', get_string('confirmdeletestage', 'totara_appraisal'));
        }

        $buttons = array();
        $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/stage.php',
                array('action' => 'delete', 'confirm' => 1, 'id' => $stageid)),
                get_string('delete', 'totara_appraisal'), 'post');
        $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/stage.php',
                array('id' => $stageid)),
                get_string('cancel', 'moodle'), 'get');
        $out .= html_writer::tag('div', implode(' ', $buttons), array('class' => 'buttons'));
        return $out;
    }


    /**
     * Confirm user delete page
     *
     * @param int $pageid
     * @param int $stageid
     * @return string
     */
    public function confirm_delete_page($pageid, $stageid) {
        $out = '';
        $out .= html_writer::tag('p', get_string('confirmdeletepage', 'totara_appraisal'));

        $buttons = array();
        $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/ajax/page.php',
                array('action' => 'delete', 'confirm' => 1, 'id' => $pageid)),
                get_string('delete', 'totara_appraisal'), 'post');
        $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/stage.php',
                array('id' => $stageid)),
                get_string('cancel', 'moodle'), 'get');
        $out .= html_writer::tag('div', implode(' ', $buttons), array('class' => 'buttons'));
        return $out;
    }


    /**
     * Confirm user delete qustion
     *
     * @param int $questionid
     * @param int $pageid
     * @return string HTML
     */
    public function confirm_delete_question($questionid, $pageid) {
        $out = '';
        $out .= html_writer::tag('p', get_string('confirmdeletequestion', 'totara_appraisal'));

        $buttons = array();
        $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/ajax/question.php',
                array('action' => 'delete', 'confirm' => 1, 'id' => $questionid)),
                get_string('delete', 'totara_appraisal'), 'post');
        $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/ajax/question.php',
                array('appraisalstagepageid' => $pageid)),
                get_string('cancel', 'moodle'), 'get');
        $out .= html_writer::tag('div', implode(' ', $buttons), array('class' => 'buttons'));

        return $out;
    }


    /**
     * Confirm user delete message
     *
     * @param int $messageid
     * @param int $appraisalid
     * @return string HTML
     */
    public function confirm_delete_message($messageid, $appraisalid) {
        $out = '';
        $out .= html_writer::tag('p', get_string('confirmdeletemessage', 'totara_appraisal'));

        $buttons = array();
        $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/message.php',
                array('action' => 'delete', 'confirm' => 1, 'id' => $appraisalid, 'messageid' => $messageid)),
                get_string('delete', 'totara_appraisal'), 'post');
        $buttons[] = $this->output->single_button(new moodle_url('/totara/appraisal/message.php',
                array('id' => $appraisalid)),
                get_string('cancel', 'moodle'), 'get');
        $out .= html_writer::tag('div', implode(' ', $buttons), array('class' => 'buttons'));

        return $out;
    }


    /**
     * Returns a table showing the currently assigned groups of users
     *
     * @param array $assignments group assignment info
     * @param int $itemid the id of the appraisal object users are assigned to
     * @return string HTML
     */
    public function display_assigned_groups($assignments, $itemid) {
        global $CFG, $OUTPUT;

        $tableheader = array(get_string('assigngrouptypename', 'totara_appraisal'),
                             get_string('assignsourcename', 'totara_appraisal'),
                             get_string('assignincludechildren', 'totara_appraisal'),
                             get_string('assignnumusers', 'totara_appraisal') . ' ' .
                                        $OUTPUT->help_icon('assignnumusers', 'totara_appraisal'),
                             get_string('actions'));

        $appraisal = new appraisal($itemid);

        $table = new html_table();
        $table->attributes['class'] = 'generaltable fullwidth dataTable';
        $table->summary = '';
        $table->head = $tableheader;
        $table->data = array();
        if (empty($assignments)) {
            $emptycell = new html_table_cell(get_string('noassignments', 'totara_appraisal'));
            $emptycell->colspan = count($tableheader);
            $emptycell->attributes['class'] .= 'dataTables_empty';
            $emptyrow = new html_table_row(array($emptycell));
            $emptyrow->attributes['class'] .= 'odd';
            $table->data[] = $emptyrow;
        } else {
            foreach ($assignments as $assign) {
                $includechildren = ($assign->includechildren == 1) ? get_string('yes') : get_string('no');
                $row = array();
                $row[] = new html_table_cell($assign->grouptypename);
                $row[] = new html_table_cell($assign->sourcefullname);
                $row[] = new html_table_cell($includechildren);
                $row[] = new html_table_cell($assign->groupusers);
                // Only show delete if appraisal is draft status, or active with dynamic appraisals enabled.
                if ((!empty($CFG->dynamicappraisals) && appraisal::is_active($itemid)) || appraisal::is_draft($itemid)) {
                    $delete = $this->output->action_icon(
                            new moodle_url('/totara/appraisal/learners.php',
                            array('appraisalid' => $itemid, 'deleteid' => $assign->id, 'sesskey' => sesskey())),
                            new pix_icon('t/delete', get_string('delete')));
                    $row[] = new html_table_cell($delete);
                } else {
                    $row[] = '';
                }
                $table->data[] = $row;
            }
        }
        $out = $this->output->container(html_writer::table($table), 'clearfix', 'assignedgroups');
        return $out;
    }


    /**
     * Returns the base markup for a paginated user table widget
     *
     * @param bool $show_assignedvia Show the "Assigned Via" column?
     * @param bool $show_stage_edit True if the edit stage action icons should be shown
     * @return string HTML
     */
    public function display_user_datatable($show_assignedvia = true, $show_stage_edit = false) {
        $table = new html_table();
        $table->id = 'datatable';
        $table->attributes['class'] = 'generaltable clearfix';
        $table->head = array(get_string('learner'));
        if ($show_assignedvia) {
            $table->head[] = get_string('assignedvia', 'totara_core');
        }
        if ($show_stage_edit) {
            $table->head[] = get_string('currentstageinprogress', 'totara_appraisal');
        }
        $out = $this->output->container(html_writer::table($table), 'clearfix', 'assignedusers');
        return $out;
    }

    /**
     * Returns the markup for the learner assignments not live notification.
     *
     * @param   int         The id of the appraisal we are display warnings for.
     * @param   boolean     Flag whether the user should see the update assignments button.
     * @return  string      HTML
     */
    public function display_notlive_notice($appraisalid, $canassign = false) {

        // Not live notification text.
        $out = get_string('changesnotlive', 'totara_appraisal');

        if ($canassign) {
            // Update learner assignments button.
            $updatestr = get_string('updatenow', 'totara_appraisal');
            $updateparams = array('appraisalid' => $appraisalid, 'update' => true, 'sesskey' => sesskey());
            $updateurl = new moodle_url('/totara/appraisal/learners.php', $updateparams);
            $updatebutton = new single_button($updateurl, $updatestr, 'get');
            $updatebutton->class .= ' update_assignment_records';
            $out .= $this->render($updatebutton);
        }

        return $this->container($out, 'notifynotice');
    }

    /**
     * Returns the markup for the learner assignments error warning.
     *
     * @param   int         The id of the appraisal we are display warnings for.
     * @param   array       An array of the warnings we are displaying.
     * @param   boolean     Flag whether the user should see the link to more information.
     * @return  string      HTML
     */
    public function display_learner_warnings($appraisalid, $warnings, $canview = false) {

        if (empty($warnings)) {
            return '';
        }

        if (!empty($warnings['learners'])) {
            // Display the no learners assigned warning.
            $out = get_string('appraisalinvalid:learners', 'totara_appraisal');
        } else if (!empty($warnings)) {
            // The only other warnings are role warnings.
            if ($canview) {
                // Add the link to list all the role warings.
                $infostr = get_string('missingroleslink', 'totara_appraisal');
                $infourl = new moodle_url('/totara/appraisal/missing.php', array('appraisalid' => $appraisalid));
                $infolink = html_writer::link($infourl->out(), $infostr, array('class' => 'missingroleslink'));

                $out = get_string('missingrolesinfo', 'totara_appraisal', $infolink);
            } else {
                // Just the plain warning without a link.
                $out = get_string('missingroles', 'totara_appraisal');
            }
        }

        return $this->container($out, 'notifyproblem');
    }


    /**
     * Displays the header for the given appraisal. Includes appraisal status for the given user.
     * Note since this can update the user assignment with the job assignment id,
     * the updated assignment is also returned
     *
     * @param appraisal $appraisal
     * @param object $userassignment
     * @param null $unused (previously $roleassignments) - DEPRECATED since 9.0 but will not be removed.
     *                   The list of role assignments is now populated within this method.
     * @param boolean $preview
     * @param array $urlparams existing query parameters to use when creating a
     *        job selection control.
     * @return array a (html string, updated appraisee assignment) tuple.
     */
    public function display_appraisal_header($appraisal, $userassignment, $unused = null, $preview = false, array $urlparams = []) {
        global $CFG, $DB;

        if (isset($unused)) {
            debugging('The $roleassignments parameter in totara_appraisal_renderer::display_appraisal_header has been deprecated. The list of role assignments is now populated within this method.',
                DEBUG_DEVELOPER);
        }

        $out = html_writer::tag('h3', format_string($appraisal->name));

        list($job_assignment_html, $newuserassignment) = $this->display_job_assignment($appraisal, $userassignment, $preview, $urlparams);
        $out .= $job_assignment_html;
        $roleassignments = $appraisal->get_all_assignments($newuserassignment->userid);

        // Display the list of participants.
        $rolestringkeys = appraisal::get_roles();
        $participants = array();
        foreach ($appraisal->get_roles_involved() as $role) {
            $rolestring = get_string($rolestringkeys[$role], 'totara_appraisal');
            if (empty($roleassignments[$role]) || empty($roleassignments[$role]->userid)) {
                $participant = $rolestring . ": " . get_string('rolecurrentlyempty', 'totara_appraisal');
            } else {
                $user = $DB->get_record('user', array('id' => $roleassignments[$role]->userid));
                $participant = $rolestring . ": " . fullname($user);
            }
            $participants[] = $participant;
        }
        $out .= html_writer::tag('div', html_writer::span(get_string('participants', 'totara_appraisal')) .
                    html_writer::alist($participants), array('class' => 'appraisal-participants'));

        if (!$preview) {
            if (!empty($newuserassignment->timecompleted)) {
                $icon = $this->output->pix_icon('i/valid', get_string('completed', 'totara_appraisal'));
                $out .= html_writer::tag('p', $icon . get_string('completedon', 'totara_appraisal',
                    userdate($newuserassignment->timecompleted, get_string('strftimedate', 'langconfig'))));
            } else if ($appraisal->status == appraisal::STATUS_CLOSED) {
                $icon = $this->output->pix_icon('i/invalid', get_string('closed', 'totara_appraisal'));
                $out .= html_writer::tag('p', $icon . get_string('closedon', 'totara_appraisal',
                    userdate($appraisal->timefinished, get_string('strftimedate', 'langconfig'))));
            } else if ($newuserassignment->status == appraisal::STATUS_CLOSED) {
                $icon = $this->output->pix_icon('i/invalid', get_string('closed', 'totara_appraisal'));
                $userfullname = fullname($newuserassignment->user);
                $out .= html_writer::tag('p', $icon . get_string('appraisalclosedforuser', 'totara_appraisal', $userfullname));
            }
        }

        $html = html_writer::tag('div', $out, array('class' => 'appraisal-title'));
        return [$html, $newuserassignment];
    }

    public function display_missing_roles($userassignmentid, $appraisalid) {
        global $DB;

        // Get roles required for the appraisal.
        $appraisal = new appraisal($appraisalid);
        $requiredroles = $appraisal->get_roles_involved(appraisal::ACCESS_MUSTANSWER);

        if (empty($requiredroles)) {
            return "";
        }

        // Check required roles are not empty.
        list($insql, $inparam) = $DB->get_in_or_equal($requiredroles);
        $missingsql = "SELECT *
                         FROM {appraisal_role_assignment}
                        WHERE appraisaluserassignmentid = ?
                        AND userid = 0
                        AND appraisalrole {$insql}";
        $missingparams = array_merge(array($userassignmentid), $inparam);
        $missingroles = $DB->get_records_sql($missingsql, $missingparams);

        if (empty($missingroles)) {
            return "";
        }

        $out = html_writer::tag('p', get_string('rolesmissing', 'totara_appraisal'));

        $allroles = appraisal::get_roles();
        $out .= html_writer::start_tag('ul');
        foreach ($missingroles as $role) {
            $out .= html_writer::tag('li', get_string($allroles[$role->appraisalrole], 'totara_appraisal'));
        }
        $out .= html_writer::end_tag('ul');

        return $this->container($out, 'missingroles notifyproblem');
    }


    /**
     * Displays the actions for the given appraisal. Intended to be used beside the appraisal header.
     *
     * @param appraisal $appraisal
     * @param object $userassignment
     * @param boolean $showprint
     * @param boolean $preview
     * @return string HTML
     */
    public function display_appraisal_actions($appraisal, $userassignment, $showprint, $preview = false) {
        $out = '';
        $buttons = '';

        if ($preview || !$appraisal->is_locked($userassignment)) {
            $savepdfstr = get_string('savepdfsnapshot', 'totara_appraisal');
            $savepdfbutton = array('type' => 'button', 'value' => $savepdfstr, 'id' => 'show-savepdf-dialog');
            if ($preview) {
                $savepdfbutton['disabled'] = 'disabled';
            }
            $buttons .= html_writer::empty_tag('input', $savepdfbutton);
        }

        if ($showprint) {
            $printstr = get_string('print', 'totara_appraisal');
            $printbutton = array('type' => 'button', 'value' => $printstr, 'id' => 'show-print-dialog');
            if ($preview) {
                $printbutton['disabled'] = 'disabled';
            }
            $buttons .= html_writer::empty_tag('input', $printbutton);
        }

        $out .= html_writer::tag('div', $buttons, array('class' => 'appraisal-headerbuttons'));

        return $out;
    }

    /**
     * Displays the stage. Includes title, due date, description, user submission statuses.
     * The specified actions are inserted into a space on the right.
     *
     * @param appraisal $appraisal
     * @param appraisal_stage $stage
     * @param object $userassignment
     * @param object $roleassignment
     * @param string $actions HTML of actions to be inserted into the right column
     * @param bool $preview is this appraisal being previewed
     * @return string HTML
     */
    public function display_stage($appraisal, $stage, $userassignment, $roleassignment, $actions, $preview = false) {
        global $USER, $TEXTAREA_OPTIONS;

        $context = array();
        $context['name'] = $stage->name;

        // Initialise some variables.
        $allroles = appraisal::get_roles();
        $stageinprogress = ($stage->id == $userassignment->activestageid)
                           && empty($userassignment->timecompleted)
                           && !empty($userassignment->jobassignmentid);

        if (!empty($stage->timedue)) {
            $context['completeby'] = userdate($stage->timedue, get_string('strftimedate', 'langconfig'));
            $context['completeby_string'] = get_string('completebydate', 'totara_appraisal', s($context['completeby']));
        }

        // Info block (in middle).
        if ($preview) {
            $stagestatus = 'appraisal-stage-inprogress';
            $context['textstatus'] = get_string('incomplete', 'totara_appraisal');
            $context['status'] = 'spacer';
        } else if ((($appraisal->status == appraisal::STATUS_CLOSED && !$stage->is_completed($userassignment)) ||
                    ($userassignment->is_closed() && !$stage->is_completed($userassignment)))) {
            if ($stageinprogress) {
                $stagestatus = 'appraisal-stage-completed';
                $context['status'] = 'check-success';
                $context['textstatus'] = get_string('completed', 'totara_appraisal');
            } else {
                $stagestatus = 'appraisal-stage-locked';
                $context['status'] = 'lock';
                $context['textstatus'] = get_string('locked', 'totara_appraisal');
            }
        } else if ($stage->is_overdue() && $stageinprogress) {
            $stagestatus = 'appraisal-stage-overdue';
            $context['status'] = 'warning';
            $context['textstatus'] = get_string('overdue', 'totara_appraisal');
        } else if ($stageinprogress) {
            $stagestatus = 'appraisal-stage-inprogress';
            $context['status'] = 'spacer';
            $context['textstatus'] = get_string('incomplete', 'totara_appraisal');
        } else if ($stage->is_completed($userassignment)) {
            $stagestatus = 'appraisal-stage-completed';
            $context['status'] = 'check-success';
            $context['textstatus'] = get_string('completed', 'totara_appraisal');
        } else {
            $stagestatus = 'appraisal-stage-locked';
            $context['status'] = 'lock';
            $context['textstatus'] = get_string('locked', 'totara_appraisal');
        }

        $description = file_rewrite_pluginfile_urls($stage->description, 'pluginfile.php', $TEXTAREA_OPTIONS['context']->id,
                'totara_appraisal', 'appraisal_stage', $stage->id);
        $context['description'] = format_text($description, FORMAT_MOODLE);
        // Involved users statuses.
        if ($preview) {
            $rolesinvolvedrecords = $stage->get_roles_involved(appraisal::ACCESS_CANANSWER);
            // Reformat the results so that they are the same as those returned by get_mandatory_completion.
            $rolescompletion = array();
            foreach ($rolesinvolvedrecords as $roleinvolvedrecord) {
                $rolescompletion[] = (object) array('appraisalrole' => $roleinvolvedrecord);
            }
        } else {
            $rolescompletion = $stage->get_mandatory_completion($userassignment->userid);
        }

        $lines = array();
        foreach ($rolescompletion as $rolecompletion) {
            // Icon.
            if (isset($rolecompletion->timecompleted)) {
                $icon = $this->output->flex_icon('check-success', array('alt' => get_string('completed', 'totara_appraisal')));
                $prefix = 'rolecompleted';
            } else if ($stage->is_overdue()) {
                $icon = $this->output->flex_icon('warning', array('alt' => get_string('overdue', 'totara_appraisal')));
                $prefix = 'rolecomplete';
            } else {
                $icon = $this->output->flex_icon('spacer');
                $prefix = 'rolecomplete';
            }
            // Text.
            // If stage is completed and a role wasn't assigned at the time of completion - indicate this to user
            $showactor = true;
            if (!$preview && $stage->is_completed($userassignment)) {
                // As the learner's managers and appraiser may have changed since completion (and you may not have been the
                // one providing the answers, do not use 'you' unless you are the learner
                if (!isset($rolecompletion->timecompleted)) {
                    // No job assignment for this at time of completion
                    // Don't show icon infront of the text

                    if ($userassignment->userid == $USER->id &&
                            $roleassignment->appraisalrole == appraisal::ROLE_LEARNER) {
                        // My appraisal - use 'Your'
                        $a = get_string($allroles[$rolecompletion->appraisalrole], 'totara_appraisal');
                        $rolecomplete = html_writer::span(get_string('rolecompleteyour', 'totara_appraisal', $a), 'appraisal-disabled');
                        $rolecomplete .= html_writer::span(get_string('rolecompletenotassigned', 'totara_appraisal',
                            get_string($allroles[$rolecompletion->appraisalrole], 'totara_appraisal')), 'label label-info');
                    }
                    else {
                        $a = new stdClass();
                        $a->username = fullname($userassignment->user);
                        $a->rolename = get_string($allroles[$rolecompletion->appraisalrole], 'totara_appraisal');
                        $rolecomplete = html_writer::span(get_string('rolecompleteusers', 'totara_appraisal', $a), 'appraisal-disabled');
                        $rolecomplete .= html_writer::span(get_string('rolecompletenotassigned', 'totara_appraisal', $a->rolename), 'label label-info');
                    }
                } else {
                    if ($userassignment->userid == $USER->id) {
                        // My appraisal - use 'You' and 'Your'
                        if ($rolecompletion->appraisalrole == $roleassignment->appraisalrole) {
                            $rolecomplete = get_string($prefix . 'you', 'totara_appraisal');
                            $showactor = false; // You know who you are.
                        } else {
                            $rolecomplete = get_string($prefix . 'your', 'totara_appraisal',
                                    get_string($allroles[$rolecompletion->appraisalrole], 'totara_appraisal'));
                        }
                    } else {
                        if ($rolecompletion->appraisalrole == appraisal::ROLE_LEARNER) {
                            $rolecomplete = get_string($prefix . 'user', 'totara_appraisal', fullname($userassignment->user));
                            $showactor = false; // Everyone knows who the learner is.
                        }
                        else {
                            $a = new stdClass();
                            $a->username = fullname($userassignment->user);
                            $a->rolename = get_string($allroles[$rolecompletion->appraisalrole], 'totara_appraisal');
                            $rolecomplete = get_string($prefix . 'users', 'totara_appraisal', $a);
                        }
                    }
                }
            } else {
                if ($rolecompletion->appraisalrole == $roleassignment->appraisalrole) {
                    $rolecomplete = get_string($prefix . 'you', 'totara_appraisal');
                } else if ($rolecompletion->appraisalrole == appraisal::ROLE_LEARNER) {
                    $rolecomplete = get_string($prefix . 'user', 'totara_appraisal', fullname($userassignment->user));
                } else if ($userassignment->userid == $USER->id &&
                        (!$preview || $roleassignment->appraisalrole == appraisal::ROLE_LEARNER)) {
                    $rolecomplete = get_string($prefix . 'your', 'totara_appraisal',
                            get_string($allroles[$rolecompletion->appraisalrole], 'totara_appraisal'));
                } else {
                    $a = new stdClass();
                    $a->username = fullname($userassignment->user);
                    $a->rolename = get_string($allroles[$rolecompletion->appraisalrole], 'totara_appraisal');
                    $rolecomplete = get_string($prefix . 'users', 'totara_appraisal', $a);
                }
            }

            // Add specific user role completion information.
            if (!empty($rolecompletion->usercompleted) && ($showactor || !empty($rolecompletion->realusercompleted))) {
                $rolecomplete .= $this->get_completion_user_info($rolecompletion->usercompleted, $rolecompletion->realusercompleted);
            }

            // Add whole line in span to enable searching in behat.
            // Better ux element grouping will be better
            $lines[] = $icon . $rolecomplete;
        }
        $statusflexicon = \core\output\flex_icon::get_icon($context['status']);
        $this->statusicon = [
            'template' => $statusflexicon->get_template(),
            'context' => $statusflexicon->export_for_template($this->output)
        ];

        $context['lines'] = $lines;
        $context['actions'] = $actions;
        $context['cssclass'] = $stagestatus;
        $context['statusicon'] = $this->statusicon;

        return $this->render_from_template('totara_appraisal/stage_brief', $context);
    }


    /**
     * Returns info on the real user who completed an action as a string.
     * @param integer|null $usercompleted User id for user who completed or null.
     * @param integer|null $realusercompleted User id for real user who completed on behalf of another user, or null.
     * @return string Text string describing who completed action.
     */
    protected function get_completion_user_info($usercompletedid, $realusercompletedid) {
        global $DB;

        // Prep user data for stage completions.
        if (!empty($usercompletedid)) {
            $u = $DB->get_record('user', ['id' => $usercompletedid]);
            $usercompleted = $u ? fullname($u) : '';
        } else {
            $usercompleted = '';
        }
        if (!empty($realusercompletedid)) {
            $u = $DB->get_record('user', ['id' => $realusercompletedid]);
            $realusercompleted = $u ? fullname($u) : '';
        } else {
            $realusercompleted = '';
        }

        $out = '';
        if (!empty($usercompleted)) {
            if (!empty($realusercompleted)) {
                $a = new \stdClass();
                $a->completedby = $realusercompleted;
                $a->completedonbehalfof = $usercompleted;
                $out = get_string('completedbyxonbehalfofy', 'totara_appraisal', $a);
            } else {
                $out = get_string('completedbyx', 'totara_appraisal', $usercompleted);
            }
        }
        return $out;
    }

    /**
     * Displays the actions for the given stage when shown on the appraisal level.
     * These are intended to be inserted to the right of the stage header (display_stage_header()).
     *
     * @param appraisal $appraisal
     * @param appraisal_stage $stage
     * @param object $userassignment
     * @param object $roleassignment
     * @param array $urlparams
     * @param boolean $preview
     * @return string HTML
     */
    public function display_stage_actions_for_stages($appraisal, $stage, $userassignment, $roleassignment,
            $urlparams, $preview = false) {
        $pagesurl = new moodle_url('/totara/appraisal/myappraisal.php', $urlparams);

        $action = '';
        if ($preview) {
            $pagesurl->param('stageid', $stage->id);
            $button = new single_button($pagesurl, get_string('preview', 'totara_appraisal'), 'get');
            $action .= $this->output->render($button);

        } else if ($userassignment->is_closed()) {
            if (isset($stage->firstpage)) {
                $button = new single_button($pagesurl, get_string('view', 'totara_appraisal'), 'get');
                $action .= $this->output->render($button);
            }

        } else if ($stage->is_completed($userassignment)) {
            if (isset($stage->firstpage)) {
                $pagesurl->param('pageid', $stage->firstpage);
                $button = new single_button($pagesurl, get_string('view', 'totara_appraisal'), 'get');
                $action .= $this->output->render($button);
            }

        } else if ($stage->id == $userassignment->activestageid && !empty($userassignment->jobassignmentid) && isset($stage->firstpage)) {
            if ($appraisal->status == appraisal::STATUS_CLOSED || $stage->is_completed($roleassignment)) {
                $button = new single_button($pagesurl, get_string('view', 'totara_appraisal'), 'get');
                $action .= $this->output->render($button);
            } else if ($roleassignment->activepageid) {
                $button = new single_button($pagesurl, get_string('continue', 'totara_appraisal'), 'get');
                $action .= $this->output->render($button);
            } else {
                $button = new single_button($pagesurl, get_string('start', 'totara_appraisal'), 'get');
                $action .= $this->output->render($button);
            }
        }

        return html_writer::tag('div', $action, array('class' => 'appraisal-stageactions'));
    }


    /**
     * Displays the actions for the given stage when shown on the stage level.
     * These are intended to be inserted to the right of the stage header (display_stage_header()).
     *
     * @param boolean $showsaveprogress
     * @param boolean $showcompletestage
     * @param array $urlparams
     * @return string HTML
     */
    public function display_stage_actions_for_pages($showsaveprogress, $showcompletestage, $urlparams) {
        $actions = '';

        // Save progress button.
        $saveprogressurl = new moodle_url('/totara/appraisal/myappraisal.php', $urlparams);
        $saveprogressstr = get_string('saveprogress', 'totara_appraisal');
        $saveprogressbutton = new single_button($saveprogressurl, $saveprogressstr, 'get');
        $saveprogressbutton->formid = 'saveprogress';
        if (!$showsaveprogress) {
            $saveprogressbutton->disabled = true;
        }
        $actions .= $this->output->render($saveprogressbutton);

        // Complete stage button.
        $completestageurl = new moodle_url('/totara/appraisal/myappraisal.php', $urlparams);
        $completestagestr = get_string('completestage', 'totara_appraisal');
        $completestagebutton = new single_button($completestageurl, $completestagestr, 'get');
        $completestagebutton->formid = 'completestage';
        if (!$showcompletestage) {
            $completestagebutton->disabled = true;
        }
        $actions .= $this->output->render($completestagebutton);

        return html_writer::tag('div', $actions, array('class' => 'appraisal-stageactions'));
    }


    /**
     * Display appraisal overview for the given subject and role.
     * Do all loading of objects here and pass to child renderer functions.
     *
     * @param appraisal $appraisal
     * @param array of appraisal_stage $stages
     * @param object $roleassignment
     * @param boolean $preview
     * @return string HTML
     */
    public function display_stages($appraisal, $stages, $roleassignment, $showprint, $preview = false) {
        // Initialise some variables.
        $out = '';
        $userassignment = $roleassignment->get_user_assignment();
        $urlparams = array('role' => $roleassignment->appraisalrole, 'subjectid' => $userassignment->userid,
                'appraisalid' => $appraisal->id, 'action' => 'pages');
        if ($preview) {
            $urlparams['preview'] = 1;
        }
        $roleassignments = $appraisal->get_all_assignments($userassignment->userid);

        // Title and status.
        list($headerhtml, $newuserassignment) = $this->display_appraisal_header($appraisal, $userassignment, null, $preview, $urlparams);
        $out .= $this->display_appraisal_actions($appraisal, $newuserassignment, $showprint, $preview);
        $out .= $headerhtml;

        // Check to see if there are any stages to display.
        if (empty($stages)) {
            return $out . get_string('nostages', 'totara_appraisal');
        }

        $out .= html_writer::start_tag('div', array('class' => 'appraisal-stagelist'));

        // If appropriate display the missing roles warning above the stages.
        if ($newuserassignment->status == appraisal::STATUS_ACTIVE) {
            $out .= $this->display_missing_roles($newuserassignment->id, $newuserassignment->appraisalid);
        }

        // Stages list.
        $stagelist = array();
        foreach ($stages as $stage) {
            $stageactions = $this->display_stage_actions_for_stages($appraisal, $stage, $newuserassignment, $roleassignment,
                    $urlparams, $preview);
            $out .= $this->display_stage($appraisal, $stage, $newuserassignment, $roleassignment, $stageactions, $preview);
        }
        $out .= html_writer::end_tag('div');

        return $out;
    }


    /**
     * Display stage header and visible pages for a given subject and role.
     * Do all loading of objects here and pass to child renderer functions.
     *
     * @param array $pages of appraisal_page
     * @param appraisal_page $page The currently selected page, or null if pages is empty
     * @param appraisal_role_assignment $roleassignment
     * @param boolean $preview
     * @param boolean $includewrapper
     * @return string HTML
     */
    public function display_pages($pages, $page, appraisal_role_assignment $roleassignment, $preview = false,
            $includewrapper = false) {
        $out = '';
        // Initialise stage variables.
        $userassignment = $roleassignment->get_user_assignment();
        $activestage = new appraisal_stage($userassignment->activestageid);
        $appraisal = new appraisal($activestage->appraisalid);
        $urlparams = array('role' => $roleassignment->appraisalrole, 'subjectid' => $userassignment->userid,
                'appraisalid' => $activestage->appraisalid);
        if ($preview) {
            $urlparams['preview'] = 1;
            $urlparams['stageid'] = $userassignment->activestageid;
        }

        // Display stage header.
        $out .= $this->heading(get_string('currentstage', 'totara_appraisal'));
        $showsaveprogress = !$preview && isset($page) && ($roleassignment->activepageid == $page->id) &&
                !$appraisal->is_locked($userassignment) &&
                !$page->is_completed($roleassignment);
        $showcompletestage = $showsaveprogress && ($page->id == end($pages)->id);
        $actions = $this->display_stage_actions_for_pages($showsaveprogress, $showcompletestage, $urlparams);
        $stagesstr = get_string('backtoappraisalx', 'totara_appraisal', format_string($appraisal->name));
        $stagesurl = new moodle_url('/totara/appraisal/myappraisal.php', array_merge($urlparams, array('action' => 'stages')));
        $out .= html_writer::link($stagesurl, $stagesstr);
        $out .= $this->display_stage($appraisal, $activestage, $userassignment, $roleassignment, $actions, $preview);

        // Check to see if there are any pages to display.
        if (empty($pages)) {
            return $out . get_string('nopagestoview', 'totara_appraisal');
        }

        $out .= html_writer::empty_tag('hr');
        $out .= $this->heading(get_string('appraisalcontent', 'totara_appraisal'));

        // Display pages tabs for a given stage, with the specified page selected.
        $rows = array();
        $inactiverows = array();
        $pageiscomplete = true;
        foreach ($pages as $tabpage) {
            // After the first incomplete page, all pages are inactive (locked).
            if (!$pageiscomplete) {
                $inactiverows[] = $tabpage->id;
            }
            // If a page is not completed then all following pages will not be completed.
            // A page is not completed if it is on the active stage and it is on or after the active page.
            if ($pageiscomplete && !$preview && ($tabpage->appraisalstageid == $userassignment->activestageid) &&
                    ($tabpage->id == $roleassignment->activepageid)) {
                $pageiscomplete = $tabpage->is_completed($roleassignment);
            }
            if (!$pageiscomplete && !$preview) {
                // Show incomplete icon.
                $icon = $this->output->pix_icon('i/completion-manual-n', get_string('incomplete', 'totara_appraisal'));

            } else if ($tabpage->can_be_answered($roleassignment->appraisalrole) &&
                    !$tabpage->is_locked($roleassignment)) {
                // Show complete and editable icon.
                $icon = $this->output->pix_icon('i/completion-manual-y', get_string('completed', 'totara_appraisal'));

            } else {
                // Show complete and uneditable icon.
                $icon = $this->output->pix_icon('i/completion-auto-enabled', get_string('completed', 'totara_appraisal'));
            }
            $rows[] = new tabobject($tabpage->id, new moodle_url('/totara/appraisal/myappraisal.php',
                    array_merge($urlparams, array('pageid' => $tabpage->id, 'action' => 'pages'))),
                    $icon . ' ' . format_string($tabpage->name), format_string($tabpage->name));
        }
        $tabs[] = $rows;
        if ($includewrapper) {
            $out .= $this->container_start('verticaltabtree-wrapper row');
        }
        $out .= html_writer::tag('div', print_tabs($tabs, $page->id, $inactiverows, null, true),
                array('class' => 'verticaltabtree col-sm-4 col-md-3 col-lg-2'));

        return $out;
    }


    /**
     * Display a header indicating that the user is viewing another user's appraisal.
     *
     * @param object $subject
     * @parm string $customlangtext custom source string
     * @param int $role adjust role
     * @return string HTML
     */
    public function display_viewing_appraisal_header($subject = null, $customlangtext = '', $role = 0) {
        global $CFG;

        $langtext = ($customlangtext == '') ? 'youareviewingxsappraisal' : $customlangtext;

        $a = new stdClass();
        $a->name = fullname($subject);
        $a->userid = $subject->id;
        $a->site = $CFG->wwwroot;

        if ($role) {
            $roles = appraisal::get_roles();
            $a->rolename = get_string($roles[$role], 'totara_appraisal');
        }

        $rowdata = array();
        if (!empty($subject->picture)) {
            $rowdata[] = $this->output->user_picture($subject, array('link' => false));
        }
        $rowdata[] = get_string($langtext, 'totara_appraisal', $a);

        $t = new html_table();
        $t->attributes['class'] = 'invisiblepadded viewing-xs-appraisal';
        $t->data[] = new html_table_row($rowdata);
        return html_writer::tag('div', html_writer::table($t), array('class' => "plan_box notifymessage"));
    }

    /**
     * Display preview header.
     *
     * @param appraisal $appraisal
     * @param int $role
     * @param array $urlparams
     * @return string HTML
     */
    public function display_preview_header($appraisal, $role, $urlparams) {
        $roles = appraisal::get_roles();
        foreach ($roles as $roleid => $rolecode) {
            $roles[$roleid] = get_string($rolecode, 'totara_appraisal');
        }

        // The heading.
        $a = new stdClass();
        $a->appraisalname = format_string($appraisal->name);
        $a->rolename = $roles[$role];
        $heading = html_writer::tag('h3', get_string('previewingappraisal', 'totara_appraisal', $a));

        // The roles control.
        $previewurl = new moodle_url('/totara/appraisal/myappraisal.php', $urlparams);
        $select = new single_select($previewurl, 'role', $roles);
        $select->set_label(get_string('previewappraisalas', 'totara_appraisal'));
        $select->class .= ' appraisal-previewer';
        $select->nothing = '';
        $select->selected = $role;
        $rolecontrol = $this->render($select);

        // The message.
        $description = html_writer::tag('div', get_string('previewinfo', 'totara_appraisal', $a->rolename));

        // Combine and display.
        return html_writer::tag('div', $heading . $rolecontrol . $description, array('class' => "plan_box notifymessage", 'id' => 'preview-appraisal-notification'));
    }

    /**
     * Returns the base markup for a snapshot export.
     *
     * @param appraisal $appraisal
     * @param stdClass $subject user object
     * @param stdClass $userassignment
     * @param stdClass $roleassignment
     * @param int $spaces
     * @param bool $nouserpic true means no user picture
     * @param array $stageschecked null means all, array stageid=>tobeprinted
     * @return string HTML fragment
     */
    public function display_snapshot($appraisal, $subject, $userassignment, $roleassignment, $spaces, $nouserpic, $stageschecked) {
        global $CFG, $TEXTAREA_OPTIONS;

        require_once($CFG->dirroot . '/totara/appraisal/appraisal_forms.php');

        // Set up.
        $out = "";
        list($headerhtml, $newuserassignment) = $this->display_appraisal_header($appraisal, $userassignment);
        $out .= $headerhtml;

        $role = $roleassignment->appraisalrole;
        $assignments = $appraisal->get_all_assignments($subject->id);
        $otherassignments = $assignments;

        $appdesc = new stdClass();
        $appdesc->description = $appraisal->description;
        $appdesc->descriptionformat = FORMAT_HTML;
        $appdesc = file_prepare_standard_editor($appdesc, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                'totara_appraisal', 'appraisal', $appraisal->id);
        $out .= $appdesc->description_editor['text'];
        $out .= $this->display_viewing_appraisal_header($subject, 'youareprintingxsappraisal', $role);
        $stageslist = appraisal_stage::get_stages($appraisal->id, array($role));

        foreach ($stageslist as $stageid => $stagedata) {
            if ($stageschecked === null or !empty($stageschecked[$stageid])) {
                // Print stage.
                $stage = new appraisal_stage($stageid);
                $out .= $this->display_stage($appraisal, $stage, $newuserassignment, $roleassignment, '', false);

                $pages = appraisal_page::get_applicable_pages($stageid, $role, 0, false);
                foreach ($pages as $page) {
                    // Print page.
                    $out .= $this->heading($page->name);

                    // Print form.
                    $form = new appraisal_answer_form(null, array('appraisal' => $appraisal, 'page' => $page,
                    'userassignment' => $newuserassignment, 'roleassignment' => $roleassignment,
                    'otherassignments' => $otherassignments, 'spaces' => $spaces, 'nouserpic' => $nouserpic,
                    'action' => 'print', 'preview' => false, 'export' => true, 'islastpage' => false, 'readonly' => true),
                        'post', '', array('class' => 'totara-question-group'));

                    foreach ($assignments as $assignment) {
                        $form->set_data($appraisal->get_answers($page->id, $assignment));
                    }

                    $out .= $form->render();
                    $form->reset_form_sent();
                }
            }
        }

        return $out;
    }

    /**
     * Returns the HTML snippet to display job details for the appraisal. Note
     * this can update the user assignment with the job assignment id, which is
     * why the updated assignment is also returned.
     *
     * @param \appraisal $appraisal appraisal to render.
     * @param \appraisal_user_assignment current appraisee for which to render
     *        appraisal.
     * @param boolean $preview if the rendering is for a preview.
     * @param array $urlparams existing query parameters to use when creating a
     *        job selection control.
     *
     * @return array a (HTML string, updated user assignment) tuple.
     */
    private function display_job_assignment(
        \appraisal $appraisal,
        \appraisal_user_assignment $userassignment,
        $preview,
        array $urlparams
    ) {
        global $OUTPUT;

        $heading = html_writer::span(
            get_string('jobassignment', 'totara_appraisal') . ':', null
        );

        $renderfixedfn = function(
            $desc, \appraisal_user_assignment $assignment
        ) use ($heading) {
            $fixed = html_writer::alist([$desc]);
            return [html_writer::div("$heading $fixed"), $assignment];
        };

        if ($preview) {
            $desc = get_string('jobassignmentempty', 'totara_appraisal');
            return $renderfixedfn($desc, $userassignment);
        }

        $newassignment = $userassignment->with_auto_job_assignment(false);
        if (!empty($newassignment)) {
            list($assignment, $job) = $newassignment;
            $desc = position::job_position_label($job);
            return $renderfixedfn($desc, $assignment);
        }

        // If it gets to here, it means the appraisee has multiple jobs and the
        // UI must force the appraisee to explicitly select one.
        $urlparams['action'] = totara_appraisal_constants::ACTION_ASSIGN_JOB;
        $url = new moodle_url(
            totara_appraisal_constants::URL_MYAPPRAISAL, $urlparams
        );

        $options = [];
        $appraisee = $userassignment->userid;
        foreach (job_assignment::get_all($appraisee) as $job) {
            $options[$job->id] = position::job_position_label($job);
        }

        $select = $OUTPUT->single_select(
            $url,
            totara_appraisal_constants::PARAM_JOB_ID,
            $options,
            '',
            ['' => get_string('jobassignmentselect', 'totara_appraisal')]
        );

        return [html_writer::div("$heading $select"), $userassignment];
    }
}
