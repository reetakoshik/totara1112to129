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
* @author David Curry <david.curry@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Class asset_list represents seminar assets
 */
final class asset_list implements \Iterator {

    use traits\seminar_iterator;

    /**
     * Add signup to item list
     * @param signup $item
     */
    public function add(asset $item) {
        $this->items[$item->get_id()] = $item;
    }

    public function contains(int $assetid) : bool {
        return array_key_exists($assetid, $this->items);
    }

    /**
     * Get available assets for the specified time slot, or all assets if $timestart and $timefinish are empty.
     *
     * NOTE: performance is not critical here because this function should be used only when assigning assets to sessions.
     *
     * @param int $timestart start of requested slot
     * @param int $timefinish end of requested slot
     * @param int $sessionid current session id, 0 if session is being created, all current session assets are always included
     * @param int $facetofaceid facetofaceid custom assets can be used in all dates of one seminar activity
     * @return asset[] assets
     *
     */
    public static function get_available($timestart, $timefinish, seminar_event $seminarevent) {
        global $DB, $USER;

        $eventid = $seminarevent->get_id();
        $seminarid = $seminarevent->get_facetoface();
        $list = new asset_list(); // Create an empty list.

        $params = array();
        $params['timestart'] = (int)$timestart;
        $params['timefinish'] = (int)$timefinish;
        $params['sessionid'] = (int)$eventid;
        $params['facetofaceid'] = (int)$seminarid;
        $params['userid'] = $USER->id;

        $bookedassets = array();
        if ($timestart and $timefinish) {
            if ($timestart > $timefinish) {
                debugging('Invalid slot specified, start cannot be later than finish', DEBUG_DEVELOPER);
            }

            $sql = "SELECT DISTINCT fa.*
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
        if (!empty($eventid)) {
            $sql = "SELECT DISTINCT fa.*
                      FROM {facetoface_asset} fa
                 LEFT JOIN {facetoface_asset_dates} fad ON fad.assetid = fa.id
                 LEFT JOIN {facetoface_sessions_dates} fsd ON fsd.id = fad.sessionsdateid
                     WHERE fa.custom = 0 AND (fa.hidden = 0 OR fsd.sessionid = :sessionid)";
        } else {
            $sql = "SELECT fa.*
                      FROM {facetoface_asset} fa
                     WHERE fa.custom = 0 AND fa.hidden = 0
                  ORDER BY fa.name ASC, fa.id ASC";
        }
        $assets = $DB->get_records_sql($sql, $params);
        foreach ($bookedassets as $rid => $unused) {
            unset($assets[$rid]);
        }

        // Custom assets in the current facetoface activity.
        if (!empty($seminarid)) {
            $sql = "SELECT DISTINCT fa.*
                      FROM {facetoface_asset} fa
                      JOIN {facetoface_asset_dates} fad ON fad.assetid = fa.id
                      JOIN {facetoface_sessions_dates} fsd ON fsd.id = fad.sessionsdateid
                      JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid
                     WHERE fa.custom = 1 AND fs.facetoface = :facetofaceid";
            $customassets = $DB->get_records_sql($sql, $params);
            foreach ($customassets as $asset) {
                if (!isset($bookedassets[$asset->id])) {
                    $assets[$asset->id] = $asset;
                }
            }
            unset($customassets);
        }

        // Add custom assets of the current user that are not assigned yet or any more.
        $sql = "SELECT fa.*
                  FROM {facetoface_asset} fa
             LEFT JOIN {facetoface_asset_dates} fad ON fad.assetid = fa.id
             LEFT JOIN {facetoface_sessions_dates} fsd ON fsd.id = fad.sessionsdateid
                 WHERE fsd.id IS NULL AND fa.custom = 1 AND fa.usercreated = :userid
              ORDER BY fa.name ASC, fa.id ASC";
        $userassets = $DB->get_records_sql($sql, $params);
        foreach ($userassets as $asset) {
            $assets[$asset->id] = $asset;
        }

        // Construct all the assets and add them to the iterator list.
        foreach ($assets as $assetdata) {
            $asset = new asset();
            $asset->from_record($assetdata);
            $list->add($asset);
        }
        return $list;
    }
}
