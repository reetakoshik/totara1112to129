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
 * Registered with OpenSesame.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string tenantid: Tenant identification.
 */
class tenant_registered extends \core\event\base {
    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Create instance of event.
     *
     * @param string $tenantid
     * @return tenant_registered
     */
    public static function create_from_tenantid($tenantid) {
        $data = array(
            'other' => array(
                'tenantid' => $tenantid,
            ),
        );
        self::$preventcreatecall = false;
        /** @var tenant_registered $event */
        $event = self::create($data);
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
        $this->context = \context_system::instance();
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventtenantregistered', 'repository_opensesame');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' registered server to use OpenSesame (id: {$this->other['tenantid']}).";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/repository/opensesame/register.php');
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call tenant_registered::create() directly, use tenant_registered::create_from_tenantid() instead.');
        }

        parent::validate_data();
    }
}
