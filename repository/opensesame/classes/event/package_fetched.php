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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 */

namespace repository_opensesame\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Package fetching event.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string externalid: Package identification.
 */
class package_fetched extends \core\event\base {
    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Create instance of event.
     *
     * @param \stdClass $package
     * @return package_fetched
     */
    public static function create_from_package(\stdClass $package) {
        $data = array(
            'objectid' => $package->id,
            'other' => array('externalid' => $package->externalid),
        );
        self::$preventcreatecall = false;
        /** @var package_fetched $event */
        $event = self::create($data);
        $event->add_record_snapshot('repository_opensesame_pkgs', $package);
        self::$preventcreatecall = true;
        return $event;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'repository_opensesame_pkgs';
        $this->context = \context_system::instance();
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventpackagefetched', 'repository_opensesame');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Course package '{$this->other['externalid']}' was fetched from OpenSesame.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/repository/opensesame/index.php');
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call package_fetched::create() directly, use package_fetched::create_from_package() instead.');
        }

        parent::validate_data();
    }
}
