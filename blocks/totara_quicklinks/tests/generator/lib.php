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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package	block_totara_quicklinks
 */

defined('MOODLE_INTERNAL') || die();

class block_totara_quicklinks_generator extends testing_block_generator {

    /**
     * Create a quick link for a given instance
     *
     * @param stdClass block Block object
     * @param array $data Data to use to create the link (userid, title, url)
     *
     * @return stdClass link created
     */
    public function create_quick_link($blockinstance, $data = array()) {
        global $DB;

        if ($blockinstance->blockname !== 'totara_quicklinks') {
            debugging('Specified block is not an instance of totara_quicklinks');
        }

        if (!is_array($data)) {
            debugging ('create_quick_link $data param must be an array');
        }

        $quicklink = new stdClass();
        $quicklink->userid = $data['userid'];
        $quicklink->block_instance_id = $blockinstance->id;
        $quicklink->title = $data['title'] ? $data['title'] : 'Test link';
        $quicklink->url = $data['url'] ? $data['url'] : 'http://google.com';

        $params = array('block_instance_id' => $blockinstance->id);
        $quicklink->displaypos = $DB->count_records('block_quicklinks', $params) > 0 ? $DB->get_field('block_quicklinks', 'MAX(displaypos)+1', $params) : 0;

        $linkid = $DB->insert_record('block_quicklinks', $quicklink);

        $quicklink->id = $linkid;

        return $quicklink;
    }
}
