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
 * @author Jonathan Newman
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

/**
 * hierarchy/prefix/position/lib.php
 *
 * Library to construct position hierarchies
 */
require_once("{$CFG->dirroot}/totara/hierarchy/lib.php");
require_once("{$CFG->dirroot}/totara/core/utils.php");
require_once("{$CFG->dirroot}/completion/data_object.php");

/**
 * Object that holds methods and attributes for position operations.
 * @abstract
 */
class position extends hierarchy {

    /**
     * The base table prefix for the class
     */
    var $prefix = 'position';
    var $shortprefix = 'pos';
    protected $extrafields = null;

    /**
     * Run any code before printing header
     * @param $page string Unique identifier for page
     * @return void
     */
    function hierarchy_page_setup($page = '', $item) {
        global $CFG, $USER, $PAGE;

        if ($page !== 'item/view') {
            return;
        }

        // Setup custom javascript
        require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');

        // Setup lightbox
        local_js(array(
            TOTARA_JS_DIALOG,
            TOTARA_JS_TREEVIEW
        ));

        $args = array('args'=>'{"id":' . $item->id . ','
                             . '"frameworkid":' . $item->frameworkid . ','
                             . '"userid":' . $USER->id . ','
                             . '"sesskey":"' . sesskey() . '",'
                             . '"can_edit": true}');

        $PAGE->requires->strings_for_js(array('assigncompetencies', 'assigncompetencytemplate', 'assigngoals'), 'totara_hierarchy');

        // Include position user js modules.
        $jsmodule = array(
                'name' => 'totara_positionitem',
                'fullpath' => '/totara/core/js/position.item.js',
                'requires' => array('json'));
        $PAGE->requires->js_init_call('M.totara_positionitem.init',
            $args, false, $jsmodule);
    }

    /**
     * Print any extra markup to display on the hierarchy view item page
     * @param $item object Position being viewed
     * @return void
     */
    function display_extra_view_info($item, $frameworkid=0) {
        global $CFG, $OUTPUT, $PAGE;

        require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

        $sitecontext = context_system::instance();
        $can_edit = has_capability('totara/hierarchy:updateposition', $sitecontext);
        $comptype = optional_param('comptype', 'competencies', PARAM_TEXT);
        $renderer = $PAGE->get_renderer('totara_hierarchy');

        if (totara_feature_visible('competencies')) {
            // Spacing.
            echo html_writer::empty_tag('br');

            echo html_writer::start_tag('div', array('class' => "list-assignedcompetencies"));
            echo $OUTPUT->heading(get_string('assignedcompetencies', 'totara_hierarchy'));

            echo $this->print_comp_framework_picker($item->id, $frameworkid);

            if ($comptype == 'competencies') {
                // Display assigned competencies
                $items = $this->get_assigned_competencies($item, $frameworkid);
                $addurl = new moodle_url('/totara/hierarchy/prefix/position/assigncompetency/find.php', array('assignto' => $item->id));
                $displaytitle = 'assignedcompetencies';
            } else if ($comptype == 'comptemplates') {
                // Display assigned competencies
                $items = $this->get_assigned_competency_templates($item, $frameworkid);
                $addurl = new moodle_url('/totara/hierarchy/prefix/position/assigncompetencytemplate/find.php', array('assignto' => $item->id));
                $displaytitle = 'assignedcompetencytemplates';
            }
            echo $renderer->print_hierarchy_items($frameworkid, $this->prefix, $this->shortprefix, $displaytitle, $addurl, $item->id, $items, $can_edit);
            echo html_writer::end_tag('div');
        }

        // Spacing.
        echo html_writer::empty_tag('br');

        // Display all goals assigned to this item.
        if (totara_feature_visible('goals') && !is_ajax_request($_SERVER)) {
            $addgoalparam = array('assignto' => $item->id, 'assigntype' => GOAL_ASSIGNMENT_POSITION, 'sesskey' => sesskey());
            $addgoalurl = new moodle_url('/totara/hierarchy/prefix/goal/assign/find.php', $addgoalparam);
            echo html_writer::start_tag('div', array('class' => 'list-assigned-goals'));
            echo $OUTPUT->heading(get_string('goalsassigned', 'totara_hierarchy'));
            echo $renderer->assigned_goals($this->prefix, $this->shortprefix, $addgoalurl, $item->id);
            echo html_writer::end_tag('div');
        }
    }

    /**
     * Returns a list of competencies that are assigned to a position
     * @param $item object|int Position being viewed
     * @param $frameworkid int If set only return competencies for this framework
     * @param $excluded_ids array an optional set of ids of competencies to exclude
     * @return array List of assigned competencies
     */
    function get_assigned_competencies($item, $frameworkid=0, $excluded_ids=false) {
        global $DB;

        if (is_object($item)) {
            $itemid = $item->id;
        } else if (is_numeric($item)) {
            $itemid = $item;
        } else {
            return false;
        }

        $params = array($itemid);

        $sql = "SELECT
                    c.*,
                    cf.id AS fid,
                    cf.fullname AS framework,
                    ct.fullname AS type,
                    pc.id AS aid,
                    pc.linktype as linktype
                FROM
                    {pos_competencies} pc
                INNER JOIN
                    {comp} c
                 ON pc.competencyid = c.id
                INNER JOIN
                    {comp_framework} cf
                 ON c.frameworkid = cf.id
                LEFT JOIN
                    {comp_type} ct
                 ON c.typeid = ct.id
                WHERE
                    pc.templateid IS NULL
                AND pc.positionid = ?";

        if (!empty($frameworkid)) {
            $sql .= " AND c.frameworkid = ?";
            $params[] = $frameworkid;
        }
        $ids = null;
        if (is_array($excluded_ids) && !empty($excluded_ids)) {
            list($excluded_sql, $excluded_params) = $DB->get_in_or_equal($excluded_ids, SQL_PARAMS_QM, 'param', false);
            $sql .= " AND c.id {$excluded_sql}";
            $params = array_merge($params, $excluded_params);
        }

        $sql .= " ORDER BY c.fullname";

        return $DB->get_records_sql($sql, $params);
    }

   /**
    * get assigne competency templates for an item
    *
    * @param int|object $item
    * @param int $frameworkid
    */
    function get_assigned_competency_templates($item, $frameworkid=0) {
        global $DB;

        if (is_object($item)) {
            $itemid = $item->id;
        } elseif (is_numeric($item)) {
            $itemid = $item;
        }

        $params = array($itemid);

        $sql = "SELECT
                    c.*,
                    cf.id AS fid,
                    cf.fullname AS framework,
                    pc.id AS aid
                FROM
                    {pos_competencies} pc
                INNER JOIN
                    {comp_template} c
                 ON pc.templateid = c.id
                INNER JOIN
                    {comp_framework} cf
                 ON c.frameworkid = cf.id
                WHERE
                    pc.competencyid IS NULL
                AND pc.positionid = ?";

        if (!empty($frameworkid)) {
            $sql .= " AND c.frameworkid = ?";
            $params[] = $frameworkid;
        }

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Returns array of positions assigned to a user,
     * indexed by job assignment id
     *
     * @param   $user   object  User object
     * @return  array
     */
    function get_user_positions($user) {
        global $DB;

        $sql = "SELECT ja.id AS jobassignmentid, p.*
                  FROM {pos} p
                  JOIN {job_assignment} ja ON p.id = ja.positionid
                 WHERE ja.userid = ?
                 ORDER BY ja.sortorder ASC";
        return $DB->get_records_sql($sql, array($user->id));
    }

    /**
     * Return markup for user's assigned positions picker
     *
     * @param   object $user User object
     * @param   int $selected Id of currently selected position
     * @return  string of html
     */
    function user_positions_picker($user, $selected) {
        // Get user's positions
        $positions = $this->get_user_positions($user);

        if (!$positions || count($positions) < 2) {
            return '';
        }

        // Format options
        $options = array();
        foreach ($positions as $jobassignid => $pos) {
            $jobassignment = \totara_job\job_assignment::get_with_id($jobassignid);
            $text = $jobassignment->fullname.': '.$pos->fullname;
            $options[$pos->id] = $text;
        }

        return display_dialog_selector($options, $selected, 'simpleframeworkpicker');
    }


    /**
     * Delete all data associated with the positions
     *
     * This method is protected because it deletes the positions, but doesn't use transactions
     *
     * Use {@link hierarchy::delete_hierarchy_item()} to recursively delete an item and
     * all its children
     *
     * @param array $items Array of IDs to be deleted
     *
     * @return boolean True if items and associated data were successfully deleted
     */
    protected function _delete_hierarchy_items($items) {
        global $DB;

        // First call the deleter for the parent class
        if (!parent::_delete_hierarchy_items($items)) {
            return false;
        }

        list($items_sql, $items_params) = $DB->get_in_or_equal($items);

        // delete all of the positions links to completencies
        $wheresql = "positionid {$items_sql}";
        if (!$DB->delete_records_select($this->shortprefix . "_competencies", $wheresql, $items_params)) {
            return false;
        }

        // delete any relevant position relations
        $wheresql = "id1 {$items_sql} OR id2 {$items_sql}";
        if (!$DB->delete_records_select($this->shortprefix . "_relations", $wheresql, array_merge($items_params, $items_params))) {
            return false;
        }

        // set position id to null in all these tables
        $db_data = array(
            hierarchy::get_short_prefix('competency').'_record' => 'positionid',
            'course_completions' => 'positionid',
        );

        foreach ($db_data as $table => $field) {
            $update_sql = "UPDATE {{$table}}
                           SET {$field} = NULL
                           WHERE {$field} {$items_sql}";

            if (!$DB->execute($update_sql, $items_params)) {
                return false;
            }
        }

        // Remove all references to these positions in job_assignment table.
        foreach ($items as $positionid) {
            \totara_job\job_assignment::update_to_empty_by_criteria('positionid', $positionid);
        }

        return true;
    }

    /**
     * prints the competency framework picker
     *
     * @param int $positionid
     * @param int $currentfw
     * @return object html for the picker
     */
    function print_comp_framework_picker($positionid, $currentfw) {
      global $CFG, $DB, $OUTPUT;

        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');

        $edit = optional_param('edit', 'off', PARAM_TEXT);

        $competency = new competency();
        $frameworks = $competency->get_frameworks();

        $assignedcounts = $DB->get_records_sql_menu("SELECT comp.frameworkid, COUNT(*)
                                                FROM {pos_competencies} poscomp
                                                INNER JOIN {comp} comp
                                                ON poscomp.competencyid=comp.id
                                                WHERE poscomp.positionid = ?
                                                GROUP BY comp.frameworkid", array($positionid));

        $out = '';

        $out .= html_writer::start_tag('div', array('class' => "frameworkpicker"));
        if (!empty($frameworks)) {
            $fwoptions = array();
            foreach ($frameworks as $fw) {
                $count = isset($assignedcounts[$fw->id]) ? $assignedcounts[$fw->id] : 0;
                $fwoptions[$fw->id] = $fw->fullname . " ({$count})";
            }
            $fwoptions = count($fwoptions) > 1 ? array(0 => get_string('all')) + $fwoptions : $fwoptions;
            $out .= html_writer::start_tag('div', array('class' => "hierarchyframeworkpicker"));

            $url = new moodle_url('/totara/hierarchy/item/view.php', array('id' => $positionid, 'edit' => $edit, 'prefix' => 'position'));
            $options = $fwoptions;
            $selected = $currentfw;
            $formid = 'switchframework';
            $out .= get_string('filterframework', 'totara_hierarchy') . $OUTPUT->single_select($url, 'framework', $options, $selected, null, $formid);

            $out .= html_writer::end_tag('div');
        } else {
            $out .= get_string('competencynoframeworks', 'totara_hierarchy');
        }
        $out .= html_writer::end_tag('div');

        return $out;
   }


    /**
     * Returns various stats about an item, used for listed what will be deleted
     *
     * @param integer $id ID of the item to get stats for
     * @return array Associative array containing stats
     */
    public function get_item_stats($id) {
        global $DB;

        if (!$data = parent::get_item_stats($id)) {
            return false;
        }

        // should always include at least one item (itself)
        if (!$children = $this->get_item_descendants($id)) {
            return false;
        }

        $ids = array_keys($children);

        list($idssql, $idsparams) = sql_sequence('positionid', $ids);
        // Number of job assignment records with matching position.
        $data['job_assignment'] = $DB->count_records_select('job_assignment', $idssql, $idsparams);

        // number of assigned competencies
        $data['assigned_comps'] = $DB->count_records_select('pos_competencies', $idssql, $idsparams);

        return $data;
    }


    /**
     * Given some stats about an item, return a formatted delete message
     *
     * @param array $stats Associative array of item stats
     * @return string Formatted delete message
     */
    public function output_delete_message($stats) {
        $message = parent::output_delete_message($stats);

        if ($stats['job_assignment'] > 0) {
            $message .= get_string('positiondeleteincludexjobassignments', 'totara_hierarchy', $stats['job_assignment']) .
                html_writer::empty_tag('br');
        }

        if ($stats['assigned_comps'] > 0) {
            $message .= get_string('positiondeleteincludexlinkedcompetencies', 'totara_hierarchy', $stats['assigned_comps']).
                html_writer::empty_tag('br');
        }

        return $message;
    }

    /**
     * Returns a string formatted:
     *
     *   "<job assignment full name> (<position name>)"
     * or, if no position is set
     *   "<job assignment full name>"
     *
     * @param \totara_job\job_assignment $jobassignment
     * @return string
     */
    public static function job_position_label(\totara_job\job_assignment $jobassignment) {
        global $DB;

        $label = $jobassignment->fullname;

        if (!empty($jobassignment->positionid)) {
            $position = $DB->get_record('pos', array('id' => $jobassignment->positionid));
            $label .= " ($position->fullname)";
        }

        return $label;
    }

    /**
     * Check if the position feature is enabled, if not Display an error.
     */
    public static function check_feature_enabled() {
        if (totara_feature_disabled('positions')) {
            print_error('error:positionsdisabled', 'totara_hierarchy');
        }
    }

}  // class
