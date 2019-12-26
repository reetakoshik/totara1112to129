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
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara_customfield
 */

namespace totara_customfield\rb\display;

use totara_reportbuilder\rb\display\base;

/**
 * Class customfield_url
 */
class customfield_url extends base {

    /**
     * Handles the display of the URL custom field within a report
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/url/field.class.php');

        if ($format === 'html') {
            $displaytext = \customfield_url::display_item_data($value);
        } else {
            // Just return the url.
            $urldata = json_decode($value);
            if (empty($urldata->url)) {
                return '';
            } else {
                $displaytext = s($urldata->url);
            }
        }
        return $displaytext;
    }
}
