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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package mod_workshop
 */

namespace mod_workshop\userdata;

/**
 * Manages user preferences belonging to the workshop module
 */
class preferences extends \core_user\userdata\plugin_preferences {

    /**
     * Returns an array of user preferences as strings.
     *
     * @param int $userid The user we are getting preferences for.
     * @return string[]
     */
    protected static function get_user_preferences(int $userid): array {
        return [
            'workshop_perpage',

            // The following are collapsible regions used by the workshop.
            'assessment-form-wrapper',
            'workshop-viewlet-instructreviewers',
            'workshop-viewlet-instructauthors',
            'workshop-viewlet-intro',
            'workshop-viewlet-allexamples',
            'workshop-viewlet-examples',
            'workshop-viewlet-ownsubmission',
            'workshop-viewlet-allsubmissions',
            'workshop-viewlet-gradereport',
            'workshop-viewlet-examplesfail',
            'workshop-viewlet-assignedassessments',
            'workshop-viewlet-cleargrades',
            'workshop-viewlet-conclusion',
            'workshop-viewlet-yourgrades',
            'workshop-viewlet-publicsubmissions',
        ];
    }

}
