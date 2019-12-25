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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Display class intended for showing a user's profile picture, name and links to their profile.
 * When exporting, only the user's full name is displayed (without link)
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class user_icon_link extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT;

        $extrafields = self::get_extrafields_row($row, $column);

        if ($extrafields->id == 0) {
            return '';
        }

        // Don't show picture in spreadsheet.
        $isexport = ($format !== 'html');
        if ($isexport) {
            return fullname($extrafields);
        }

        // Process obsolete calls to this display function.
        if (isset($extrafields->userpic_picture)) {
            $picuser = new \stdClass();
            $picuser->id = $extrafields->user_id;
            $picuser->picture = $extrafields->userpic_picture;
            $picuser->imagealt = $extrafields->userpic_imagealt;
            $picuser->firstname = $extrafields->userpic_firstname;
            $picuser->firstnamephonetic = $extrafields->userpic_firstnamephonetic;
            $picuser->middlename = $extrafields->userpic_middlename;
            $picuser->lastname = $extrafields->userpic_lastname;
            $picuser->lastnamephonetic = $extrafields->userpic_lastnamephonetic;
            $picuser->alternatename = $extrafields->userpic_alternatename;
            $picuser->email = $extrafields->userpic_email;
            $extrafields = $picuser;
        }

        $url = new \moodle_url('/user/view.php', array('id' => $extrafields->id));
        return $OUTPUT->user_picture($extrafields, array('courseid' => 1)) . "&nbsp;" . \html_writer::link($url, $value);
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
