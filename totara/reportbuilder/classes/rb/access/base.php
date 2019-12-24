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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\access;

/**
 * Abstract base access class to be extended to create report builder access restrictions.
 *
 * Defines the properties and methods required by access restrictions
 *
 * This file also contains some core access restrictions
 * that can be used by any report builder source
 */
abstract class base {
    /**
     * Return legacy type used for settings.
     *
     * NOTE: each plugin must use unique class name without namespace!
     */
    public function get_type() {
        $classname = get_class($this);
        if (!preg_match('/[a-z0-9_]+$/', $classname, $matches)) {
            throw new \coding_exception('Invalid access class name!');
        }
        return $matches[0] . '_access';
    }

    /**
     * Get list of reports this user is allowed to access by this restriction class
     * @param int $userid reports for this user
     * @return array of permitted report ids
     */
    abstract public function get_accessible_reports($userid);

    /**
     * Adds form elements required for this access restriction's settings page
     *
     * @param \MoodleQuickForm $mform Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     */
    abstract public function form_template($mform, $reportid);

    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @param integer $reportid ID of the report to process
     * @param \MoodleQuickForm $fromform Moodle form data received via form submission
     *
     * @return boolean True if form was successfully processed
     */
    abstract public function form_process($reportid, $fromform);
}
