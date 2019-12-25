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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\task;

class preprocess_group_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('preprocessgrouptask', 'totara_reportbuilder');
    }


    /**
     * Preprocess report groups
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
        require_once($CFG->dirroot . '/totara/reportbuilder/groupslib.php');
        require_once($CFG->dirroot . '/totara/core/lib/scheduler.php');

        $groups = $DB->get_records('report_builder_group', null, 'id');

        foreach ($groups as $group) {

            $preproc = $group->preproc;
            $groupid = $group->id;

            // Create instance of preprocessor.
            if (!$pp = \reportbuilder::get_preproc_object($preproc, $groupid)) {
                mtrace('Warning: preprocessor "' . $preproc . '" not found.');
                continue;
            }

            // Check for items where tags have been added or removed.
            update_tag_grouping($groupid);

            // Get list of items and when they were last processed.
            $trackinfo = $pp->get_track_info();

            // Get a list of items that need processing.
            $items = $pp->get_group_items();

            mtrace("Running '$preproc' pre-processor on group '{$group->name}' (" .
                  count($items) . ' items).');

            foreach ($items as $item) {

                // Get track info about this item if it exists.
                if (array_key_exists($item, $trackinfo)) {
                    $lastchecked = $trackinfo[$item]->lastchecked;
                    $disabled = $trackinfo[$item]->disabled;
                } else {
                    $lastchecked = null;
                    $disabled = 0;
                }

                // Skip processing if item is disabled.
                if ($disabled) {
                    mtrace('Skipping disabled item '.$item);
                    continue;
                }

                $message = '';
                // Try processing the item, if it goes wrong disable
                // it to prevent future attempts to process it.
                if (!$pp->run($item, $lastchecked, $message)) {
                    $pp->disable_item($item);
                    mtrace($message);
                }
            }
        }
    }
}