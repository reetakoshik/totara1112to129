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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package core_event
 */

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Tag Area updated event class.
 */
class tag_area_updated extends base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'tag_area';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Creates an event from tag object
     *
     * @since Moodle 3.1
     *
     * @param \core_tag_tag|\stdClass $tag
     *
     * @return tag_created
     */
    public static function create_from_tag_area($tagarea, $newvalue) {
        $data =
        [
            'objectid' => $tagarea->id,
            'context'  => \context_system::instance(),
            'other'    => ['itemtype' => $tagarea->itemtype, 'enabled' => $newvalue,],
        ];
        $event = self::create($data);

        return $event;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventtagareaupdated', 'core_tag');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' updated the tag area with id '$this->objectid'";
    }
}
