<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_plan
 */

namespace totara_plan\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\export;

require_once($CFG->dirroot . '/totara/plan/lib.php');
require_once($CFG->dirroot . '/totara/plan/record/evidence/lib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * User data item for evidence.
 */
class evidence extends item {

    /**
     * Is the data for this item exportable
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export user data for this item
     *
     * @param target_user $user
     * @param \context $context
     *
     * @return export
     */
    public static function export(target_user $user, \context $context) {
        global $DB, $FILEPICKER_OPTIONS;

        $evidencerecords = $DB->get_records('dp_plan_evidence', ['userid' => $user->id]);
        $evidencetypes = $DB->get_records('dp_evidence_type', [], 'sortorder');

        $exportdata = array();

        $fs = get_file_storage();
        $filecontext = $FILEPICKER_OPTIONS['context'];

        $export = new export();

        foreach ($evidencerecords as $record) {

            $evidenceitemexport = new \stdClass();
            $evidenceitemexport->id = $record->id;
            $evidenceitemexport->name = $record->name;

            $evidenceitemexport->files = array();

            $type = new \stdClass();
            $type->name = $evidencetypes[$record->evidencetypeid]->name;
            $type->description = $evidencetypes[$record->evidencetypeid]->description;

            // Deal with any files that could be in the description.
            $files = $fs->get_area_files($filecontext->id, 'totara_plan', 'dp_evidence_type', $record->evidencetypeid, 'timemodified', false);
            foreach ($files as $file) {
                $filedetails = $export->add_file($file);
                $evidenceitemexport->files[] = $filedetails;
            }

            $evidenceitemexport->type = $type;

            // Get data from custom fields
            $cfdata = totara_plan_get_custom_fields($record->id);

            foreach ($cfdata as $cf) {
                switch ($cf->datatype) {
                    case 'file':
                        // Do something with uploaded file.
                        $files = $fs->get_area_files($filecontext->id, 'totara_customfield', 'evidence_filemgr', $cf->data, 'timemodified', false);
                        foreach ($files as $file) {
                            $filedetails = $export->add_file($file);
                            $evidenceitemexport->files[] = $filedetails;
                        }
                        break;
                    case 'textarea':
                        $files = $fs->get_area_files($filecontext->id, 'totara_customfield', 'evidence', $cf->id, 'timemodified', false);
                        foreach ($files as $file) {
                            $filedetails = $export->add_file($file);
                            $evidenceitemexport->files[] = $filedetails;
                        }
                        $evidenceitemexport->{$cf->fullname} = $cf->data;
                        break;
                    default:
                        $evidenceitemexport->{$cf->fullname} = $cf->data;
                        break;
                }
            }

            $exportdata[] = $evidenceitemexport;
        }

        $export->data = $exportdata;

        return $export;
    }


    /**
     * Is data for this item purgeable
     *
     * @param int $userstatus
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Purge evidence records for evidence.
     *
     * @param target_user $user
     * @param \context $context
     *
     * @return int RESULT_STATUS_SUCCESS
     */
    public static function purge(target_user $user, \context $context) {
        global $DB;

        $evidencerecords = $DB->get_fieldset_select('dp_plan_evidence', 'id', 'userid = :userid', ['userid' => $user->id]);

        // Delete the evidence items.
        foreach ($evidencerecords as $evidenceid) {
            \evidence_delete($evidenceid);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Is this data item countable?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count user data for this item
     *
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    public static function count(target_user $user, \context $context) {
        global $DB;

        return $DB->count_records('dp_plan_evidence', ['userid' => $user->id]);
    }
}
