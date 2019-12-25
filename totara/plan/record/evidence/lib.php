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
 * @author Russell England <russell.england@totaralms.com>
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara
 * @subpackage plan
 */

use totara_job\job_assignment;

/**
 * Display attachments to an evidence
 *
 * @deprecated since Totara 12
 *
 * @global object $CFG
 * @global type $OUTPUT
 * @param int $userid
 * @param int $evidenceid
 * @return string
 */
function evidence_display_attachment($userid, $evidenceid) {
    global $CFG, $OUTPUT, $FILEPICKER_OPTIONS;

    if (!$filecontext = context_user::instance($userid)) {
        return '';
    }

    $out = '';

    $context = $FILEPICKER_OPTIONS['context'];

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'totara_plan', 'attachment', $evidenceid, null, FALSE);

    if (!empty($files)) {
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $path = '/'.$file->get_contextid().'/totara_plan/attachment'.$file->get_filepath().$file->get_itemid().'/'.$filename;
            $fileurl = moodle_url::make_file_url('/pluginfile.php', $path);

            $mimetype = $file->get_mimetype();
            $fileicon = file_mimetype_flex_icon($mimetype, $mimetype);

            $out .= html_writer::tag('a', $fileicon . s($filename), array('href' => $fileurl));
            $out .= html_writer::empty_tag('br');
        }
    }

    return $out;
}

/**
 * Deletes a selected evidence item.
 *
 * @param int $evidenceid - dp_plan_evidence->id
 */
function evidence_delete($evidenceid) {
    global $DB;

    if (!$evidence = $DB->get_record('dp_plan_evidence', array('id' => $evidenceid))) {
        // Well we can't delete something that isn't there.
        return;
    }

    $transaction = $DB->start_delegated_transaction();

    // Delete the evidence item.
    $DB->delete_records('dp_plan_evidence', array('id' => $evidence->id));

    // Delete any evidence relations.
    $DB->delete_records('dp_plan_evidence_relation', array('evidenceid' => $evidence->id));

    $fs = get_file_storage();
    $systemcontext = \context_system::instance();

    $cfdata = totara_plan_get_custom_fields($evidenceid);
    foreach ($cfdata as $customfield) {
        // Delete any custom field files
        if ($customfield->datatype === 'file') {
            $fs->delete_area_files($systemcontext->id, 'totara_customfield', 'evidence_filemgr', $customfield->data);
        } else if ($customfield->datatype === 'textarea') {
            $fs->delete_area_files($systemcontext->id, 'totara_customfield', 'evidence', $customfield->id);
        }
    }

    // Delete custom field data.
    $DB->delete_records_select('dp_plan_evidence_info_data_param',
                               'EXISTS (SELECT 1 FROM {dp_plan_evidence_info_data} dpeid
                                WHERE dpeid.id = dataid AND dpeid.evidenceid = :evidenceid)',
                               array('evidenceid' => $evidence->id));
    $DB->delete_records('dp_plan_evidence_info_data', array('evidenceid' => $evidence->id));

    $transaction->allow_commit();

    \totara_plan\event\evidence_deleted::create_from_instance($evidence)->trigger();
}

/**
 * Get custom fields
 * @param int $itemid the evidence id
 * @return array
 */
function totara_plan_get_custom_fields($itemid) {
    global $DB;

    $sql = "SELECT c.*, f.datatype, f.hidden, f.fullname
              FROM {dp_plan_evidence_info_data} c
        INNER JOIN {dp_plan_evidence_info_field} f ON c.fieldid = f.id
             WHERE c.evidenceid = :itemid
          ORDER BY f.sortorder";

    return $DB->get_records_sql($sql, array('itemid' => $itemid));
}

/**
 * Returns markup to display an individual evidence relation
 *
 * @global object $USER
 * @global object $DB
 * @global object $OUTPUT
 * @param int $evidenceid - dp_plan_evidence->id
 * @param bool $delete - display a delete link
 * @return string html markup
 */
function display_evidence_detail($evidenceid, $delete = false) {
    global $USER, $DB, $OUTPUT, $CFG;

    $sql ="
        SELECT
            e.id,
            e.name,
            e.evidencetypeid,
            et.name as evidencetypename,
            e.userid,
            e.readonly
        FROM {dp_plan_evidence} e
        LEFT JOIN {dp_evidence_type} et on e.evidencetypeid = et.id
        WHERE e.id = ?";

    if (!$item = $DB->get_record_sql($sql, array($evidenceid))) {
        return get_string('error:evidencenotfound', 'totara_plan');
    }

    if (!empty($item->userid)) {
        $usercontext = context_user::instance($item->userid);
    } else {
        $usercontext = null;
    }

    $out = '';

    $icon = 'evidence-regular';
    $img = $OUTPUT->pix_icon('/msgicons/' . $icon,
            format_string($item->name),
            'totara_core',
            array('class' => 'evidence-state-icon'));

    $out .= $OUTPUT->heading($img . $item->name, 4);

    if (!$delete && can_create_or_edit_evidence($item->userid, !empty($evidenceid), $item->readonly)) {
        $buttonlabel = get_string('editdetails', 'totara_plan');
        $editurl = new moodle_url('/totara/plan/record/evidence/edit.php',
                array('id' => $evidenceid, 'userid' => $item->userid));
        $out .= html_writer::tag('div', $OUTPUT->single_button($editurl, $buttonlabel, null),
                array('class' => 'add-linked-competency'));
    }

    if (!empty($item->evidencetypename)) {
        $out .=  html_writer::tag('p', html_writer::tag('strong', get_string('evidencetype', 'totara_plan')) . ' : ' . $item->evidencetypename);
    }

    $cfdata = totara_plan_get_custom_fields($evidenceid);
    if ($cfdata) {
        foreach ($cfdata as $cf) {
            // Don't show hidden custom fields.
            if ($cf->hidden) {
                continue;
            }
            $cf_class = "customfield_{$cf->datatype}";
            require_once($CFG->dirroot.'/totara/customfield/field/'.$cf->datatype.'/field.class.php');
            $out .=  html_writer::tag('p', html_writer::tag('strong', $cf->fullname) . ' : ' . call_user_func(array($cf_class, 'display_item_data'), $cf->data, array('prefix' => 'evidence', 'itemid' => $cf->id)));
        }
    }
    return $out;
}

/**
 * Lists all components that are linked to the evidence id
 *
 * @param int $evidenceid Evidence ID to list items for
 *
 * @return string html output
 */
function list_evidence_in_use($evidenceid) {
    global $DB, $OUTPUT;

    $out = '';

    $sql = "
        SELECT er.id, dp.name AS planname, er.component, comp.fullname AS itemname
        FROM {dp_plan_evidence_relation} AS er
        JOIN {dp_plan} AS dp ON dp.id = er.planid
        JOIN {dp_plan_competency_assign} AS c ON c.id = er.itemid
        JOIN {comp} AS comp ON comp.id = c.competencyid
        WHERE er.component = 'competency'
        AND er.evidenceid = ?
        UNION
        SELECT er.id, dp.name AS planname, er.component, course.fullname AS itemname
        FROM {dp_plan_evidence_relation} AS er
        JOIN {dp_plan} AS dp ON dp.id = er.planid
        JOIN {dp_plan_course_assign} AS c ON c.id = er.itemid
        JOIN {course} AS course ON course.id = c.courseid
        WHERE er.component = 'course'
        AND er.evidenceid = ?
        UNION
        SELECT er.id, dp.name AS planname, er.component, c.fullname AS itemname
        FROM {dp_plan_evidence_relation} AS er
        JOIN {dp_plan} AS dp ON dp.id = er.planid
        JOIN {dp_plan_objective} AS c ON c.id = er.itemid
        WHERE er.component = 'objective'
        AND er.evidenceid = ?
        UNION
        SELECT er.id, dp.name AS planname, er.component, prog.fullname AS itemname
        FROM {dp_plan_evidence_relation} AS er
        JOIN {dp_plan} AS dp ON dp.id = er.planid
        JOIN {dp_plan_program_assign} AS c ON c.id = er.itemid
        JOIN {prog} AS prog ON prog.id = c.programid
        WHERE er.component = 'program'
        AND er.evidenceid = ?
        ORDER BY planname, component, itemname";
    if ($items = $DB->get_records_sql($sql, array($evidenceid, $evidenceid, $evidenceid, $evidenceid))) {
        $out .= $OUTPUT->heading(get_string('evidenceinuseby', 'totara_plan'), 4);

        $tableheaders = array(
            get_string('planname', 'totara_plan'),
            get_string('component', 'totara_plan'),
            get_string('name', 'totara_plan'),
        );

        $tablecolumns = array(
            'planname',
            'component',
            'itemname'
        );

        // Start output buffering to bypass echo statements in $table->add_data()
        ob_start();
        $table = new flexible_table('linkedevidencelist');
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $url = new moodle_url('/totara/plan/record/evidence/index.php');
        $table->define_baseurl($url);
        $table->set_attribute('class', 'logtable generalbox dp-plan-evidence-items');
        $table->setup();

        foreach ($items as $item) {
            $row = array();
            $row[] = $item->planname;
            $row[] = get_string($item->component, 'totara_plan');
            $row[] = $item->itemname;
            $table->add_data($row);
        }

        // return instead of outputing table contents
        $table->finish_html();
        $out .= ob_get_contents();
        ob_end_clean();
    }
    return $out;

}

/**
 * Check whether the current user has permission to create, edit or delete evidence
 *
 * @param int $userid The ID of the user the evidence is for
 * @param bool $is_editing Are we wanting to edit/delete the evidence? Defaults to creating (false)
 * @param bool $read_only Is the evidence read-only? Defaults to false
 * @return bool
 */
function can_create_or_edit_evidence(int $userid, bool $is_editing = false, bool $read_only = false): bool {
    global $USER;
    $user_context = context_user::instance($userid);

    if (has_capability('totara/plan:editsiteevidence', $user_context) ||
        has_capability('totara/plan:accessanyplan', context_system::instance())) {
        return true;
    }

    if ($read_only) {
        return false;
    }

    if ($USER->id != $userid) {
        return job_assignment::is_managing($USER->id, $userid);
    }

    if ($is_editing) {
        return has_capability('totara/plan:editownsiteevidence', $user_context);
    }

    // A user can always create evidence for themselves no matter what
    return true;
}
