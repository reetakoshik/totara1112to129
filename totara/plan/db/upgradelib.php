<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_certification
 */


// TL-14290 duedate in dp_plan_program_assign must not be -1, instead use 0.
function totara_plan_upgrade_fix_invalid_program_duedates() {
    global $DB;

    $sql = "UPDATE {dp_plan_program_assign} SET duedate = 0 WHERE duedate = -1";
    $DB->execute($sql);
}

/**
 * TL-16908 Evidence customfield files are not deleted when evidence is deleted.
 *
 * Cleans up any orphaned file records from the files table where evidence was
 * previously deleted but left the file related data in the table.
 */
function totara_plan_upgrade_clean_deleted_evidence_files() {
    global $DB;

    // When an evidence is deleted, records in the dp_plan_evidence_info_data table are removed,
    // but file entries in the files table still link to these records via the files.itemid column.
    // This code removes all the dangling file entries.

    $sql = "
      SELECT f.component, f.filearea, f.itemid
        FROM {files} f
      WHERE f.component = 'totara_customfield'
        AND (f.filearea = 'evidence' OR f.filearea = 'evidence_filemgr')
        AND NOT EXISTS (
          SELECT 1
            FROM {dp_plan_evidence_info_data} dp
          WHERE dp.id = f.itemid
        )
    ";

    $context = context_system::instance()->id;
    $fs = get_file_storage();
    $results = $DB->get_recordset_sql($sql);

    foreach($results as $rs) {
        $fs->delete_area_files($context, $rs->component, $rs->filearea, $rs->itemid);
    }
    $results->close();
}
