<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get the relevant session asset for a facetoface activity
 *
 * @param int $assetid
 *
 * @return mixed stdClass object or false if not found
 * @deprecated since Totara 12
 */
function facetoface_get_asset($assetid) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

    debugging('facetoface_get_asset has been deprecated. Use new asset($id) instead.', DEBUG_DEVELOPER);

    if (!$assetid) {
        return false;
    }

    $asset = $DB->get_record('facetoface_asset', array('id' => $assetid));
    if (empty($asset)) {
        return false;
    }

    customfield_load_data($asset, 'facetofaceasset', 'facetoface_asset');
    return $asset;
}

/**
 * Process asset edit form and call related handlers
 *
 * @param stdClass|false $asset
 * @param stdClass|false $facetoface non-false means we are editing session via ajax
 * @param stdClass|false $session non-false means we are editing existing session via ajax
 * @param callable $successhandler function($id) where $id is assetid
 * @param callable $cancelhandler
 * @return mod_facetoface\form\asset_edit
 *
 * @deprecated since Totara 12
 */
function facetoface_process_asset_form($asset, $facetoface, $session, callable $successhandler, callable $cancelhandler = null) {
    global $DB, $TEXTAREA_OPTIONS, $USER, $CFG;
    require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');
    require_once($CFG->dirroot . '/mod/facetoface/asset/asset_form.php');

    debugging('facetoface_process_asset_form has been deprecated, this is now handled by the form', DEBUG_DEVELOPER);

    $editoroptions = $TEXTAREA_OPTIONS;
    if ($facetoface) {
        // Do not use autosave in editor when nesting forms.
        $editoroptions['autosave'] = false;
    }

    if (!$asset) {
        $asset = new stdClass();
        $asset->id = 0;
        $asset->description = '';
        $asset->descriptionformat = FORMAT_HTML;
        $asset->allowconflicts = 0;
        if ($facetoface) {
            $asset->custom = 1;
        } else {
            $asset->custom = 0;
        }
    } else {
        $asset->descriptionformat = FORMAT_HTML;
        customfield_load_data($asset, 'facetofaceasset', 'facetoface_asset');
        $asset = file_prepare_standard_editor($asset, 'description', $editoroptions, $editoroptions['context'], 'mod_facetoface', 'asset', $asset->id);
    }

    $customdata = array();
    $customdata['asset'] = $asset;
    $customdata['facetoface'] = $facetoface;
    $customdata['session'] = $session;
    $customdata['editoroptions'] = $editoroptions;

    $form = new \mod_facetoface\form\asset_edit(null, $customdata, 'post', '', array('class' => 'dialog-nobind'), true, null, 'mform_modal');

    if ($form->is_cancelled()) {
        if (is_callable($cancelhandler)) {
            $cancelhandler();
        }
    }

    if ($data = $form->get_data()) {
        $todb = new stdClass();
        $todb->name = $data->name;
        $todb->allowconflicts = $data->allowconflicts;
        if ($facetoface) {
            if (!empty($data->notcustom)) {
                $todb->custom = 0;
            } else {
                $todb->custom = 1;
            }
        }
        // NOTE: usually the time created and updated are set to the same value when adding new items,
        //       do the same here and later compare timestamps to find out if it was not updated yet.
        if (empty($data->id)) {
            $todb->timemodified = $todb->timecreated = time();
            $todb->usercreated = $USER->id;
            $todb->usermodified = $USER->id;
            $data->id = $DB->insert_record('facetoface_asset', $todb);
            $todb->id = $data->id;
        } else {
            $todb->timemodified = time();
            $todb->usermodified = $USER->id;
            $todb->id = $data->id;
            $DB->update_record('facetoface_asset', $todb);
        }

        /**
         * Need to combine the location data here since the preprocess isn't called enough before the save and fails.
         * But first check to see if the location custom field is present.
         * $_customlocationfieldname added in @see customfield_location::edit_field_add()
         */
        if (property_exists($form->_form, '_customlocationfieldname')) {
            customfield_define_location::prepare_form_location_data_for_db($data, $form->_form->_customlocationfieldname);
        }

        customfield_save_data($data, 'facetofaceasset', 'facetoface_asset');

        // Update description.
        $descriptiondata = file_postupdate_standard_editor(
            $data,
            'description',
            $editoroptions,
            $editoroptions['context'],
            'mod_facetoface',
            'asset',
            $data->id
        );

        $DB->set_field('facetoface_asset', 'description', $descriptiondata->description, array('id' => $data->id));

        $asset = $DB->get_record('facetoface_asset', array('id' => $data->id), '*', MUST_EXIST);

        $successhandler($asset);
    }
    return $form;
}

/**
 * Delete asset and all related information.
 *
 * If any session is still using this asset, the asset is unassigned.
 *
 * @param int $id
 *
 * @deprecated since Totara 12
 */
function facetoface_delete_asset($id) {
    global $DB, $CFG;
    require_once("$CFG->dirroot/totara/customfield/fieldlib.php");

    debugging('facetoface_delete_asset has been deprecated. Use \mod_facetoface\asset->delete() instead', DEBUG_DEVELOPER);

    $asset = $DB->get_record('facetoface_asset', array('id' => $id));
    if (!$asset) {
        // Nothing to delete.
        return;
    }

    // Delete all custom fields related to asset.
    $assetfields = $DB->get_records('facetoface_asset_info_field');
    foreach($assetfields as $assetfield) {
        /** @var customfield_base $customfieldentry */
        $customfieldentry = customfield_get_field_instance($asset, $assetfield->id, 'facetoface_asset', 'facetofaceasset');
        if (!empty($customfieldentry)) {
            $customfieldentry->delete();
        }
    }

    // Delete all files embedded in the asset description.
    $fs = get_file_storage();
    $syscontext = context_system::instance();
    $fs->delete_area_files($syscontext->id, 'mod_facetoface', 'asset', $asset->id);

    // Unlink this asset from any session.
    $DB->delete_records('facetoface_asset_dates', array('assetid' => $asset->id));

    // Finally delete the asset record itself.
    $DB->delete_records('facetoface_asset', array('id' => $id));
}

/**
 * Get available assets for the specified time slot, or all assets if $timestart and $timefinish are empty.
 *
 * NOTE: performance is not critical here because this function should be used only when assigning assets to sessions.
 *
 * @param int $timestart start of requested slot
 * @param int $timefinish end of requested slot
 * @param string $fields db fields for which data should be retrieved, with mandatory 'fa.' prefix
 * @param int $sessionid current session id, 0 if session is being created, all current session assets are always included
 * @param int $facetofaceid facetofaceid custom assets can be used in all dates of one seminar activity
 * @return stdClass[] assets
 *
 * @deprecated since Totara 12
 */
function facetoface_get_available_assets($timestart, $timefinish, $fields='fa.*', $sessionid, $facetofaceid) {
    global $DB, $USER;

    debugging('facetoface_get_available_assets has been deprecated. Use \mod_facetoface\asset_list::get_available() instead.', DEBUG_DEVELOPER);

    $params = array();
    $params['timestart'] = (int)$timestart;
    $params['timefinish'] = (int)$timefinish;
    $params['sessionid'] = (int)$sessionid;
    $params['facetofaceid'] = (int)$facetofaceid;
    $params['userid'] = $USER->id;

    if ($fields !== 'fa.*' and strpos($fields, 'fa.id') !== 0) {
        throw new coding_exception('Invalid $fields parameter specified, must be fa.* or must start with fa.id');
    }

    $bookedassets = array();
    if ($timestart and $timefinish) {
        if ($timestart > $timefinish) {
            debugging('Invalid slot specified, start cannot be later than finish', DEBUG_DEVELOPER);
        }
        $sql = "SELECT DISTINCT fa.id
                  FROM {facetoface_asset} fa
                  JOIN {facetoface_asset_dates} fad ON fad.assetid = fa.id
                  JOIN {facetoface_sessions_dates} fsd ON fsd.id = fad.sessionsdateid
                 WHERE fa.allowconflicts = 0 AND fsd.sessionid <> :sessionid
                       AND (fsd.timestart < :timefinish AND fsd.timefinish > :timestart)";
        $bookedassets = $DB->get_records_sql($sql, $params);
    }

    // First get all site assets that either allow conflicts
    // or are not occupied at the given times
    // or are already used from the current event.
    // Note that hidden assets may be reused in the same session if already there,
    // but are completely hidden everywhere else.
    if ($sessionid) {
        $sql = "SELECT DISTINCT {$fields}
                  FROM {facetoface_asset} fa
             LEFT JOIN {facetoface_asset_dates} fad ON fad.assetid = fa.id
             LEFT JOIN {facetoface_sessions_dates} fsd ON fsd.id = fad.sessionsdateid
                 WHERE fa.custom = 0 AND (fa.hidden = 0 OR fsd.sessionid = :sessionid)";
        if (strpos($fields, 'fa.*') !== false or strpos($fields, 'fa.name') !== false) {
            $sql .= " ORDER BY fa.name ASC, fa.id ASC";
        }
    } else {
        $sql = "SELECT {$fields}
                  FROM {facetoface_asset} fa
                 WHERE fa.custom = 0 AND fa.hidden = 0
              ORDER BY fa.name ASC, fa.id ASC";
    }
    $assets = $DB->get_records_sql($sql, $params);
    foreach ($bookedassets as $rid => $unused) {
        unset($assets[$rid]);
    }

    // Custom assets in the current facetoface activity.
    if ($facetofaceid) {
        $sql = "SELECT DISTINCT {$fields}
                  FROM {facetoface_asset} fa
                  JOIN {facetoface_asset_dates} fad ON fad.assetid = fa.id
                  JOIN {facetoface_sessions_dates} fsd ON fsd.id = fad.sessionsdateid
                  JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid
                 WHERE fa.custom = 1 AND fs.facetoface = :facetofaceid";
        if (strpos($fields, 'fa.*') !== false or strpos($fields, 'fa.name') !== false) {
            $sql .= " ORDER BY fa.name ASC, fa.id ASC";
        }
        $customassets = $DB->get_records_sql($sql, $params);
        foreach ($customassets as $asset) {
            if (!isset($bookedassets[$asset->id])) {
                $assets[$asset->id] = $asset;
            }
        }
        unset($customassets);
    }

    // Add custom assets of the current user that are not assigned yet or any more.
    $sql = "SELECT {$fields}
              FROM {facetoface_asset} fa
         LEFT JOIN {facetoface_asset_dates} fad ON fad.assetid = fa.id
         LEFT JOIN {facetoface_sessions_dates} fsd ON fsd.id = fad.sessionsdateid
             WHERE fsd.id IS NULL AND fa.custom = 1 AND fa.usercreated = :userid
          ORDER BY fa.name ASC, fa.id ASC";
    $userassets = $DB->get_records_sql($sql, $params);
    foreach ($userassets as $asset) {
        $assets[$asset->id] = $asset;
    }

    return $assets;
}

/**
 * Check if asset is available during certain time slot.
 *
 * Available assets are assets where the start- OR end times don't fall within that of another session's asset,
 * as well as assets where the start- AND end times don't encapsulate that of another session's asset
 *
 * @param int $timestart
 * @param int $timefinish
 * @param stdClass $asset
 * @param int $sessionid current session id, 0 if adding new session
 * @param int $facetofaceid current facetoface id
 * @return boolean
 *
 * @deprecated since Totara 12
 */
function facetoface_is_asset_available($timestart, $timefinish, stdClass $asset, $sessionid, $facetofaceid) {
    global $DB, $USER;

    debugging('facetoface_is_asset_available has been deprecated. Use \mod_facetoface\asset->is_available() instead', DEBUG_DEVELOPER);

    if ($asset->hidden) {
        // Hidden assets can be assigned only if they are already used in the session.
        if (!$sessionid) {
            return false;
        }
        $sql = "SELECT 'x'
                  FROM {facetoface_asset_dates} fad
                  JOIN {facetoface_sessions_dates} fsd ON (fsd.id = fad.sessionsdateid)
                 WHERE fad.assetid = :assetid AND fsd.sessionid = :sessionid";
        if (!$DB->record_exists_sql($sql, array('assetid' => $asset->id, 'sessionid' => $sessionid))) {
            return false;
        }
    }

    if ($asset->custom) {
        // Custom assets can be used only if already used in seminar
        // or not used anywhere and created by current user.
        $sql = "SELECT 'x'
                  FROM {facetoface_asset_dates} fad
                  JOIN {facetoface_sessions_dates} fsd ON (fsd.id = fad.sessionsdateid)
                  JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid)
                 WHERE fad.assetid = :assetid AND fs.facetoface = :facetofaceid";

        if (!$DB->record_exists_sql($sql, array('assetid' => $asset->id, 'facetofaceid' => $facetofaceid))) {
            if ($asset->usercreated == $USER->id) {
                if ($DB->record_exists('facetoface_asset_dates', array('assetid' => $asset->id))) {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    if (!$timestart and !$timefinish) {
        // Time not specified, no need to verify conflicts.
        return true;
    }

    if ($asset->allowconflicts) {
        // No need to worry about time slots.
        return true;
    }

    if ($timestart > $timefinish) {
        debugging('Invalid slot specified, start cannot be later than finish', DEBUG_DEVELOPER);
    }

    // Is there any other event using this asset in this slot?
    // Note that there cannot be collisions in session dates of one event because they cannot overlap.
    $params = array('timestart' => $timestart, 'timefinish' => $timefinish, 'assetid' => $asset->id, 'sessionid' => $sessionid);

    $sql = "SELECT 'x'
              FROM {facetoface_asset_dates} fad
              JOIN {facetoface_sessions_dates} fsd ON (fsd.id = fad.sessionsdateid)
              JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid)
             WHERE fad.assetid = :assetid AND fs.id <> :sessionid
                   AND :timefinish > fsd.timestart AND :timestart < fsd.timefinish";
    return !$DB->record_exists_sql($sql, $params);
}

/**
 * Find out if asset has scheduling conflicts.
 *
 * @param int $assetid
 * @return bool
 *
 * @deprecated since Totara 12
 */
function facetoface_asset_has_conflicts($assetid) {
    global $DB;

    debugging('facetoface_asset_has_conflicts has been deprecated. Please use \mod_facetoface\asset->has_conflicts() instead.', DEBUG_DEVELOPER);

    $sql = "SELECT 'x'
              FROM {facetoface_sessions_dates} fsd
              JOIN {facetoface_asset_dates} fad ON (fad.sessionsdateid = fsd.id)
              JOIN {facetoface_asset_dates} fad2 ON (fad2.assetid = fad.assetid)
              JOIN {facetoface_sessions_dates} fsd2 ON (fsd2.id = fad2.sessionsdateid AND fsd2.id <> fsd.id)
             WHERE fad.assetid = :assetid AND
                   ((fsd.timestart >= fsd2.timestart AND fsd.timestart < fsd2.timefinish)
                    OR (fsd.timefinish > fsd2.timestart AND fsd.timefinish <= fsd2.timefinish))";
    return $DB->record_exists_sql($sql, array('assetid' => $assetid));
}
