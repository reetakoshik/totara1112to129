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

namespace hierarchy_competency\event;

defined('MOODLE_INTERNAL') || die();

/**
 * triggered when a competency relation is deleted.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - compid1  The id of the first related competency
 *      - compid2  The id of the second related competency
 * }
 *
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_hierarchy
 */
class relation_deleted extends \core\event\base {

    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Create instance of event.
     *
     * @param   \stdClass $instance A hierarchy relation record.
     * @return  relation_deleted
     */
    public static function create_from_instance(\stdClass $instance) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context_system::instance(),
            'other' => array(
                'compid1' => $instance->id1,
                'compid2' => $instance->id2,
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
        $this->data['objecttable'] = 'comp_relations';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string("eventdeletedrelation", "hierarchy_competency");
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The competency relation between {$this->data['other']['compid1']} : {$this->data['other']['compid2']} was deleted";
    }

    public function get_url() {
        $urlparams = array('id' => $this->other['compid1'], 'prefix' => 'competency');
        return new \moodle_url('/totara/hierarchy/item/view.php', $urlparams);
    }

    public function get_legacy_logdata() {
        $urlparams = array('id' => $this->objectid, 'prefix' => 'competency');

        $logdata = array();
        $logdata[] = SITEID;
        $logdata[] = 'competency';
        $logdata[] = 'delete related';
        $logdata[] = $this->get_url()->out_as_local_url(false);
        $logdata[] = "competency: {$this->data['other']['compid1']} - competency: {$this->data['other']['compid2']}";

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

        if (!isset($this->other['compid1'])) {
            throw new \coding_exception('compid1 must be set in $other');
        }

        if (!isset($this->other['compid2'])) {
            throw new \coding_exception('compid2 must be set in $other');
        }
    }
}
