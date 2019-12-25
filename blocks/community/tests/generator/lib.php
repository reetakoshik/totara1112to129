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
 * @package	block_community
 */

defined('MOODLE_INTERNAL') || die();

class block_community_generator extends testing_block_generator {

    /**
     * Create a community link
     *
     * @param stdClass $blockinstance Block instance object
     * @param array $data Data to use to create the community link
     * @throws coding_exception
     * @return stdClass link created
     */
    public function create_community_link(stdClass $blockinstance, array $data = array()) :stdClass {
        global $DB, $USER;

        if ($blockinstance->blockname !== 'community') {
            throw new coding_exception('Specified block is not an instance of community');
        }

        $community = new stdClass();
        $community->userid = !empty($data['userid']) ? $data['userid'] : $USER->id;
        $community->coursename = !empty($data['coursename']) ? $data['coursename'] : 'Course';
        $community->coursedescription = !empty($data['coursedescription']) ? $data['coursedescription'] : 'Course description';
        $community->courseurl = !empty($data['courseurl']) ? $data['courseurl'] : 'http://www.example.com';
        $community->imageurl = !empty($data['imageurl']) ? $data['imageurl'] : 'http://www.example.com/image.jpg';

        $community->id = $DB->insert_record('block_community', $community);

        return $community;
    }
}
