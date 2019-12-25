<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package tool_totara_sync
 */

/**
 * TL-12312 Rename the setting which controls whether an import has previously linked on job assignment id number and
 * make sure that linkjobassignmentidnumber is enabled if it has previously linked on job assignment id number.
 */
function tool_totara_sync_upgrade_link_job_assignment_mismatch() {
    // If the new setting already exists and is not empty then use it.
    $alreadysetpreviouslylinked = get_config('totara_sync_element_user', 'previouslylinkedonjobassignmentidnumber');
    if (!empty($alreadysetpreviouslylinked)) {
        $previouslylinked = $alreadysetpreviouslylinked;
    } else {
        $previouslylinked = get_config('totara_sync', 'linkjobassignmentidnumber');
    }

    // Save it into the new variable.
    set_config('previouslylinkedonjobassignmentidnumber', $previouslylinked, 'totara_sync_element_user');

    // If it has, make sure that linkjobassignmentidnumber is enabled.
    if (!empty($previouslylinked)) {
        set_config('linkjobassignmentidnumber', true, 'totara_sync_element_user');
    }

    // Remove the old setting.
    unset_config('linkjobassignmentidnumber', 'totara_sync');
}
