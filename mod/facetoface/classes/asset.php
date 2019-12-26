<?php
/*
 * This file is part of Totara LMS
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
 * @author  Carl Anderson <carl.anderson@totaralearning.com>
 * @author  David Curry <david.curry@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Class Asset represents Seminar Asset
 */
final class asset {

    use traits\crud_mapper;

    /**
     * @var int {facetoface_asset}.id
     */
    private $id = 0;

    /**
     * @var string {facetoface_asset}.name
     */
    private $name = '';

    /**
     * @var int {facetoface_asset}.allowconflicts
     */
    private $allowconflicts = 0;

    /**
     * @var string {facetoface_asset}.description
     */
    private $description = '';

    /**
     * @var int {facetoface_asset}.custom
     */
    private $custom = 0;

    /**
     * @var int {facetoface_asset}.hidden
     */
    private $hidden = 0;

    /**
     * @var int {facetoface_asset}.usercreated
     */
    private $usercreated = 0;

    /**
     * @var int {facetoface_asset}.usermodified
     */
    private $usermodified = 0;

    /**
     * @var int {facetoface_asset}.timecreated
     */
    private $timecreated = 0;

    /**
     * @var int {facetoface_asset}.timemodified
     */
    private $timemodified = 0;

    /**
     * @var string facetoface assets table name
     */
    const DBTABLE = 'facetoface_asset';

    /**
     * Seminar Asset constructor
     * @param int $id {facetoface_session}.id If 0 - new Seminar Asset will be created
     */
    public function __construct(int $id = 0) {
        $this->id = $id;

        if ($id) {
            $this->load();
        }
    }

    /**
     * Create a new asset with the custom flag set.
     *
     * @return asset
     */
    public static function create_custom_asset() : asset {
        $asset = new asset();
        $asset->custom = 1;
        return $asset;
    }

    /**
     * Load asset data from DB
     *
     * @return asset this
     */
    public function load() : asset {
        return $this->crud_load();
    }

    /**
     * Map data object to class instance.
     *
     * @param \stdClass $object
     * @return asset this
     */
    public function from_record(\stdClass $object) : asset {
        $this->map_object($object);
        return $this;
    }

    /**
     * Map class instance to data object.
     *
     * @return \stdClass
     */
    public function to_record() : \stdClass {
        return $this->unmap_object();
    }

    /**
     * Store asset into database
     */
    public function save() {
        global $USER;

        $this->usermodified = $USER->id;
        $this->timemodified = time();

        if (!$this->id) {
            $this->usercreated = $USER->id;
            $this->timecreated = time();
        }

        $this->crud_save();
    }

    /**
     * Remove asset from database
     *
     * @return asset $this
     */
    public function delete() : asset {
        global $DB;

        $this->delete_customfields();
        $this->delete_files();

        // Unlink this asset from any session, then delete the asset
        $DB->delete_records('facetoface_asset_dates', array('assetid' => $this->id));
        $DB->delete_records('facetoface_asset', array('id' => $this->id));

        return $this;
    }

    /**
     * Delete customfields associated with this asset
     * @return asset $this
     */
    public function delete_customfields() : asset {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/customfield/fieldlib.php");

        // Delete all custom fields related to asset.
        $assetfields = $DB->get_records('facetoface_asset_info_field');
        foreach ($assetfields as $assetfield) {
            /** @var customfield_base $customfieldentry */
            $customfieldentry = customfield_get_field_instance($this->unmap_object(), $assetfield->id, 'facetoface_asset', 'facetofaceasset');
            if (!empty($customfieldentry)) {
                $customfieldentry->delete();
            }
        }

        return $this;
    }

    /**
     * Deletes files associated with this asset
     * @return asset $this
     */
    public function delete_files() : asset {
        // Delete all files embedded in the asset description.
        $fs = get_file_storage();
        $syscontext = \context_system::instance();
        $fs->delete_area_files($syscontext->id, 'mod_facetoface', 'asset', $this->id);

        return $this;
    }

    /**
     * Check whether the room exists yet or not.
     * If the asset has been saved into the database the $id field should be non-zero.
     *
     * @return bool - true if the asset has an $id, false if it hasn't
     */
    public function exists() : bool {
        return !empty($this->id);
    }

    /**
     * Checks if the asset is in use anywhere
     *
     * @return bool
     */
    public function is_used() : bool {
        global $DB;

        $count = $DB->count_records('facetoface_asset_dates', array('assetid' => $this->get_id()));
        return $count > 0;
    }

    /**
     * Switch an asset from a single use custom asset to a site wide reusable asset.
     * Note: that this function is instead of the set_custom() function, and it enforces
     *       the behaviour that an asset can not be republished if it is currently published.
     *
     * @return asset this
     */
    public function publish() : asset {
        // Utilising identical check to prevent false positives when custom not yet set.
        if ($this->custom === false) {
            debugging(get_string('error:cannotrepublishasset', 'facetoface'), DEBUG_DEVELOPER);
            return $this;
        }

        $this->custom = (int)false;

        return $this;
    }

    /**
     * Check if asset is available during certain time slot.
     *
     * Available assets are assets where the start- OR end times don't fall within that of another session's asset,
     * as well as assets where the start- AND end times don't encapsulate that of another session's asset
     *
     * @param int $timestart
     * @param int $timefinish
     * @param seminar_event $sessionid
     * @return bool
     */
    public function is_available(int $timestart, int $timefinish, seminar_event $seminarevent) : bool {
        global $DB, $USER;

        // Hidden assets can be assigned only if they are already used in the session.
        if ($this->get_hidden()) {
            if (!$seminarevent->exists()) {
                return false;
            }
            $sql = "SELECT 'x'
                      FROM {facetoface_asset_dates} fad
                      JOIN {facetoface_sessions_dates} fsd
                        ON (fsd.id = fad.sessionsdateid)
                     WHERE fad.assetid = :assetid
                       AND fsd.sessionid = :sessionid";
            if (!$DB->record_exists_sql($sql, ['assetid' => $this->id, 'sessionid' => $seminarevent->get_id()])) {
                return false;
            }
        }

        // Custom assets can be used only if already used in seminar, or not used anywhere and created by current user.
        if ($this->get_custom()) {
            $seminarid = $seminarevent->get_facetoface();

            $sql = "SELECT 'x'
                      FROM {facetoface_asset_dates} fad
                      JOIN {facetoface_sessions_dates} fsd
                        ON (fsd.id = fad.sessionsdateid)
                      JOIN {facetoface_sessions} fs
                        ON (fs.id = fsd.sessionid)
                     WHERE fad.assetid = :assetid
                       AND fs.facetoface = :facetofaceid";

            if (!$DB->record_exists_sql($sql, array('assetid' => $this->id, 'facetofaceid' => $seminarid))) {
                if ($this->usercreated == $USER->id) {
                    if ($DB->record_exists('facetoface_asset_dates', array('assetid' => $this->id))) {
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

        if ($this->get_allowconflicts()) {
            // No need to worry about time slots.
            return true;
        }

        if ($timestart > $timefinish) {
            debugging('Invalid slot specified, start cannot be later than finish', DEBUG_DEVELOPER);
            return false;
        }

        // Is there any other event using this asset in this slot?
        // Note that there cannot be collisions in session dates of one event because they cannot overlap.
        $params = array('timestart' => $timestart, 'timefinish' => $timefinish, 'assetid' => $this->id, 'sessionid' => $seminarevent->get_id());

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
     * @return bool
     */
    public function has_conflicts() : bool {
        global $DB;

        $sql = "SELECT 'x'
              FROM {facetoface_sessions_dates} fsd
              JOIN {facetoface_asset_dates} fad ON (fad.sessionsdateid = fsd.id)
              JOIN {facetoface_asset_dates} fad2 ON (fad2.assetid = fad.assetid)
              JOIN {facetoface_sessions_dates} fsd2 ON (fsd2.id = fad2.sessionsdateid AND fsd2.id <> fsd.id)
             WHERE fad.assetid = :assetid AND
                   ((fsd.timestart >= fsd2.timestart AND fsd.timestart < fsd2.timefinish)
                    OR (fsd.timefinish > fsd2.timestart AND fsd.timefinish <= fsd2.timefinish))";

        return $DB->record_exists_sql($sql, array('assetid' => $this->id));
    }

    /**
     * Get Asset ID
     * @return int
     */
    public function get_id() : int {
        return (int)$this->id;
    }

    /**
     * Get name for asset
     * @return string
     */
    public function get_name() : string {
        return (string)$this->name;
    }

    /**
     * Get name for asset
     * @param string $name Name to give the asset
     */
    public function set_name(string $name) : asset {
        $this->name = $name;
        return $this;
    }

    /**
     * Gets the asset description
     * @return string asset description
     */
    public function get_description() : string {
        return (string)$this->description;
    }

    /**
     * Sets the asset description
     * @param string $description asset description
     * @return asset $this
     */
    public function set_description(string $description) : asset {
        $this->description = $description;

        return $this;
    }

    /**
     * Get whether this asset allows conflicts
     * @return bool
     */
    public function get_allowconflicts() : bool {
        return (bool)$this->allowconflicts;
    }

    /**
     * Set whether this asset allows conflicts
     * @param bool $allowconflicts
     *
     * @return asset $this
     */
    public function set_allowconflicts(int $allowconflicts) : asset {
        $this->allowconflicts = (int)$allowconflicts;

        return $this;
    }

    /**
     * Get the id of the user who created this asset
     * @return int
     */
    public function get_usercreated() : int {
        return (int)$this->usercreated;
    }

    /**
     * Get the id of the user who last modified this asset
     * @return int
     */
    public function get_usermodified() : int {
        return (int)$this->usermodified;
    }

    /**
     * Set the user who last modified this asset
     * @param int $usermodified
     *
     * @return asset $this
     */
    public function set_usermodified(int $usermodified) : asset {
        $this->usermodified = $usermodified;

        return $this;
    }

    /**
     * Get the time this asset was created
     */
    public function get_timecreated() : int {
        return (int)$this->timecreated;
    }

    /**
     * Get the time this asset was last modified
     */
    public function get_timemodified() : int {
        return (int)$this->timemodified;
    }

    /**
     * Set the time this asset was modified
     * @param int $timemodified
     *
     * @return asset $this
     */
    public function set_timemodified(int $timemodified) : asset {
        $this->timemodified = $timemodified;

        return $this;
    }

    /**
     * Get whether this asset is custom
     * Note: There is no setter for this field as it can only move in
     *       one direction, this is controlled by the publish function.
     *
     * @return bool
     */
    public function get_custom() : bool {
        return (bool)$this->custom;
    }

    /**
     * Get whether this asset is hidden
     * Note: There is no setter for this field, please use the hide()
     *       and show() functions instead.
     *
     * @return bool
     */
    public function get_hidden() : bool {
        return (bool)$this->hidden;
    }

    /**
     * Hides this asset
     * Note: This is the equivalent of set_hidden(true);
     *
     * @return asset $this
     */
    public function hide() : asset {
        $this->hidden = (int)true;

        return $this;
    }

    /**
     * Shows the asset
     * Note: This is the equivalent of set_hidden(false);
     *
     * @return asset $this
     */
    public function show() : asset {
        $this->hidden = (int)false;

        return $this;
    }
}
