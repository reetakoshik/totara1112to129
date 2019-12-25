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

namespace totara_hierarchy\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract Event used as the base by each hierarchy,
 * triggered when a hierarchy type is changed.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - oldtypeid         The id of the type changed from
 *      - newtypeid         The id of the type changed to
 * }
 *
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_hierarchy
 */
abstract class type_changed extends \core\event\base {

    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Returns hierarchy prefix.
     * @return string
     */
    abstract public function get_prefix();

    /**
     * Create dataobject of event.
     *
     * @param array $dataobject A data object holding the following data:
     *              -> oldtypeid
     *              -> newtypeid
     * @return  type_changed
     */
    public static function create_from_dataobject(array $dataobject) {
        $data = array(
            'objectid' => $dataobject['itemid'],
            'context' => \context_system::instance(),
            'other' => array(
                'oldtypeid' => $dataobject['oldtype'],
                'newtypeid' => $dataobject['newtype'],
            ),
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $prefix = $this->get_prefix();

        return "The {$prefix} type {$this->objectid} was changed";
    }

    public function get_url() {
        $prefix = $this->get_prefix();

        $urlparams = array('prefix' => $prefix, 'id' => $this->objectid);

        return new \moodle_url('/totara/hierarchy/item/edit.php', $urlparams);
    }

    public function get_legacy_logdata() {
        $prefix = $this->get_prefix();
        $oldtypeid = $this->data['other']['oldtypeid'];
        $newtypeid = $this->data['other']['newtypeid'];

        $logdata = array();
        $logdata[] = SITEID;
        $logdata[] = $prefix;
        $logdata[] = 'change type';
        $logdata[] = $this->get_url()->out_as_local_url(false);
        $logdata[] = "{$prefix}: {$this->objectid} (type: {$oldtypeid} -> type: {$newtypeid})";

        return $logdata;
    }

    /**
     * Custom validation
     *
     * @throws \coding_exception
     * @return void
     */
    public function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_instance() instead.');
        }

        parent::validate_data();

        if (!isset($this->other['newtypeid'])) {
            throw new \coding_exception('newtypeid must be set in $other');
        }

        if (!isset($this->other['oldtypeid'])) {
            throw new \coding_exception('oldtypeid must be set in $other');
        }
    }
}
