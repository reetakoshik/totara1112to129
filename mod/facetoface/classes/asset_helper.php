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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;

final class asset_helper {

    /**
     * Asset data
     *
     * @param object $data to be saved includes:
     *      @var int {facetoface_asset}.id
     *      @var string {facetoface_asset}.name
     *      @var int {facetoface_asset}.allowconflicts
     *      @var string {facetoface_asset}.description
     *      @var int {facetoface_asset}.custom
     *      @var int {facetoface_asset}.hidden
     */
    public static function save($data) {
        global $TEXTAREA_OPTIONS;

        if ($data->id) {
            $asset = new asset($data->id);
        } else {
            if (isset($data->custom) && $data->custom == 1) {
                $asset = asset::create_custom_asset();
            } else {
                $asset = new asset();
            }
        }
        $asset->set_name($data->name);
        $asset->set_allowconflicts($data->allowconflicts);
        if (empty($data->custom)) {
            $asset->publish();
        }

        // We need to make sure the asset exists before formatting the customfields and description.
        if (!$asset->exists()) {
            $asset->save();
        }

        // Export data to store in customfields and description.
        $data->id = $asset->get_id();
        customfield_save_data($data, 'facetofaceasset', 'facetoface_asset');

        // Update description.
        $data = file_postupdate_standard_editor(
            $data,
            'description',
            $TEXTAREA_OPTIONS,
            $TEXTAREA_OPTIONS['context'],
            'mod_facetoface',
            'asset',
            $asset->get_id()
        );
        $asset->set_description($data->description);
        $asset->save();
        // Return new/updated asset.
        return $asset;
    }
}
