<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_hierarchy
 */

namespace hierarchy_goal\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Triggered when a hierarchy assignment is created.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - fullname  The name of the assignment
 * }
 *
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_hierarchy
 */
class assignment_position_created extends \hierarchy_goal\event\assignment_created {
    /**
     * Returns type.
     * @return string
     */
    public function get_type() {
        return 'position';
    }

    /**
     * Create instance of event.
     *
     * @param   \stdClass $instance A  goal record.
     * @return  assignment_created
     */
    public static function create_from_instance(\stdClass $instance) {
        $userid = isset($instance->userid) ? $instance->userid : null;

        $data = array(
            'objectid' => $instance->id,
            'context' => \context_system::instance(),
            'relateduserid' => $userid,
            'other' => array(
                'goalid' => $instance->goalid,
                'instanceid' => $instance->posid,
            ),
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        $event->add_record_snapshot($event->objecttable, $instance);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'goal_grp_pos';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public function get_url() {
        $params = array('prefix' => $this->get_type(), 'id' => $this->data['other']['instanceid']);
        return new \moodle_url('/totara/hierarchy/item/view.php', $params);
    }
}
