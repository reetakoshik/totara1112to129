<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package totara_core
 */

namespace totara_core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when course is unlocked and data deleted.
 *
 * @since   Totara 2.7
 * @author  Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */
class module_completion_unlocked extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @param \stdClass $mod
     * @return course_completion_unlocked
     */
    public static function create_from_module(\stdClass $mod) {
        $data = array(
            'courseid' => $mod->course,
            'objectid' => $mod->coursemodule,
            'context' => \context_module::instance($mod->coursemodule),
            'other' => array(
                'module' => $mod->modulename,
                'instance' => $mod->instance,
            ),
        );
        $event = self::create($data);
        return $event;
    }

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'course_modules';
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventmodulecompletionunlocked', 'totara_core');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "Completion data for module with id '$this->objectid' in course '$this->courseid' was unlocked";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/course/modedit.php', array('id' => $this->objectid));
    }

    protected function get_legacy_logdata() {
        return array($this->courseid, $this->other['module'], 'Module completion unlocked',
            'course/modedit.php?id='.$this->objectid, "instance:{$this->other['instance']}", $this->objectid);
    }
}
