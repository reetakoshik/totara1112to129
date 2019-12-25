<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_connect
 */

/**
 * This class implements the support for dialog selection of client cohorts.
 */
class totara_connect_cohorts {
    /** @var stdClass */
    protected $client;
    /** @var array */
    protected $headers;
    /** @var array */
    protected $data;

    public function __construct($client) {
        $this->client = $client;
    }

    public function get_cohorts($fields = 'c.*') {
        global $DB;

        if (empty($this->client->id)) {
            return array();
        }

        $sql = "SELECT {$fields}
                  FROM {cohort} c
                  JOIN {totara_connect_client_cohorts} cc ON cc.cohortid = c.id
                 WHERE cc.clientid = ?";
        $params = array($this->client->id);

        return $DB->get_records_sql($sql, $params);
    }

    public function has_data() {
        return !empty($this->data);
    }

    public function build_table() {
        $this->headers = array(
            get_string('cohortname', 'totara_cohort'),
            get_string('type', 'totara_cohort'),
            get_string('numlearners', 'totara_cohort')
        );
        $this->data = array();

        // Go to the database and gets the assignments.
        $items = $this->get_cohorts('c.id, c.name AS fullname, c.cohorttype');

        // Convert these into html.
        foreach ($items as $item) {
            $this->data[] = $this->build_row($item);
        }
    }

    public function build_row($item, $readonly = false) {
        global $OUTPUT;

        if (is_int($item)) {
            $item = $this->get_item($item);
        }

        $cohorttypes = cohort::getCohortTypes();
        $cohortstring = $cohorttypes[$item->cohorttype];

        $row = array();
        $delete = '';
        if (!$readonly) {
            $delete = html_writer::link('#', $OUTPUT->pix_icon('t/delete', get_string('delete')),
                array('title' => get_string('delete'), 'class'=>'connectcohortdeletelink'));
        }
        $row[] = html_writer::start_tag('div', array('id' => 'cohort-item-'.$item->id, 'class' => 'item')) .
            format_string($item->fullname) . $delete . html_writer::end_tag('div');

        $row[] = $cohortstring;
        $row[] = $this->user_affected_count($item);

        return $row;
    }

    public function get_item($itemid) {
        global $DB;
        return $DB->get_record('cohort', array('id' => $itemid), 'id, name AS fullname, cohorttype');
    }

    public function user_affected_count($item) {
        return $this->get_affected_users($item, 0, true);
    }

    public function get_affected_users($item, $userid = 0, $count = false) {
        global $DB;
        $select = $count ? 'COUNT(u.id)' : 'u.id';
        $params = array();
        $sql = "SELECT $select
                  FROM {cohort_members} AS cm
                  JOIN {user} AS u ON cm.userid = u.id
                 WHERE cm.cohortid = ? AND u.deleted = 0";
        $params[] = $item->id;
        if ($userid) {
            $sql .= " AND u.id = ?";
            $params[] = $userid;
        }

        if ($count) {
            $num = $DB->count_records_sql($sql, $params);
            return !$num ? 0 : $num;
        } else {
            return $DB->get_records_sql($sql);
        }
    }

    /**
     * Prints out the actual html
     *
     * @param bool $return
     * @return string html
     */
    public function display($return = false) {
        $html = '<div id="totara-connect-cohort-assignments">
            <div id="assignment_categories">
            <fieldset class="assignment_category cohorts">';

        $table = new html_table();
        $table->attributes = array('class' => 'generaltable');
        $table->id = 'totara-connect-cohorts-table';
        $table->head = $this->headers;

        if (!empty($this->data)) {
            $table->data = $this->data;
        }

        $html .= html_writer::table($table);
        $html .= '</fieldset></div></div>';

        if ($return) {
            return $html;
        }
        echo $html;
    }

    public function init_page_js() {
        global $PAGE;

        local_js(array(
            TOTARA_JS_UI,
            TOTARA_JS_ICON_PREVIEW,
            TOTARA_JS_DIALOG,
            TOTARA_JS_TREEVIEW
        ));

        $selected = $this->get_cohorts('c.id');
        $selected = !empty($selected) ? implode(',', array_keys($selected)) : '';

        $PAGE->requires->strings_for_js(array('cohorts'), 'totara_connect');
        $jsmodule = array(
            'name' => 'totara_connect_cohort',
            'fullpath' => '/totara/connect/dialog/cohort.js');
        $instanceid = empty($this->client->id) ? -1 : $this->client->id;
        $args = array('selected' => $selected, 'instanceid' => $instanceid);
        $PAGE->requires->js_init_call('M.totara_connect_cohort.init', $args, true, $jsmodule);
        unset($enrolledselected);
    }
}

