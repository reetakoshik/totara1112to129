<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_cohort
 */

/**
 * Returns audiences tagged with a specified tag.
 *
 * @param core_tag_tag $tag
 * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
 *             are displayed on the page and the per-page limit may be bigger
 * @param int $fromctx context id where the link was displayed, may be used by callbacks
 *            to display items in the same context first
 * @param int $ctx context id where to search for records
 * @param bool $rec search in subcontexts as well
 * @param int $page 0-based number of page being displayed
 * @return \core_tag\output\tagindex
 */
function totara_cohort_get_tagged_cohorts($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0) {
    global $PAGE;

    if ($ctx && $ctx != context_system::instance()->id) {
        $cohortcount = 0;
    } else {
        // Audiences can only be displayed in system context.
        $cohortcount = $tag->count_tagged_items('core', 'cohort');
    }
    $perpage = $exclusivemode ? 24 : 5;
    $content = '';
    $totalpages = ceil($cohortcount / $perpage);

    if ($cohortcount) {
        $cohortlist = $tag->get_tagged_items('core', 'cohort', $page * $perpage, $perpage);

        $items = array();

        foreach ($cohortlist as $cohort) {
            $url = new moodle_url('/cohort/view.php', array('id' => $cohort->id));
            $items[] = html_writer::link($url, $cohort->name);
        }

        $content .= html_writer::alist($items);
    }

    return new core_tag\output\tagindex($tag, 'core', 'cohort', $content,
            $exclusivemode, $fromctx, $ctx, $rec, $page, $totalpages);
}
