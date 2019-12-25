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
 * triggered when a competency evidence item is deleted.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *          - competencyid  The id of the linked competency
 *          - itemtype      The type of evidence
 *          - instanceid    The id of the linked itemtype (e.g. courseid)
 * }
 *
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_hierarchy
 */
class evidence_deleted extends \core\event\base {

    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Create instance of event.
     *
     * @param   \stdClass $instance A hierarchy evidence record.
     * @return  evidence_deleted
     */
    public static function create_from_instance(\stdClass $instance) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context_system::instance(),
            'other' => array(
                'competencyid' => $instance->competencyid,
                'itemtype' => $instance->itemtype,
                'instanceid' => $instance->iteminstance,
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
        $this->data['objecttable'] = 'comp_criteria';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string("eventdeletedevidence", "hierarchy_competency");
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The evidence item: {$this->objectid} was removed from competency: {$this->data['other']['competencyid']}";
    }

    public function get_url() {
        $urlparams = array('id' => $this->other['competencyid'], 'prefix' => 'competency');
        return new \moodle_url('/totara/hierarchy/item/view.php', $urlparams);
    }

    public function get_legacy_logdata() {
        $logdata = array();
        $logdata[] = SITEID;
        $logdata[] = 'competency';
        $logdata[] = 'delete evidence';
        $logdata[] = $this->get_url()->out_as_local_url(false);
        $logdata[] = "competency evidence: {$this->objectid}";

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

        if (!isset($this->other['competencyid'])) {
            throw new \coding_exception('competencyid must be set in $other');
        }

        if (!isset($this->other['itemtype'])) {
            throw new \coding_exception('itemtype must be set in $other');
        }

        if (!isset($this->other['instanceid'])) {
            throw new \coding_exception('instanceid must be set in $other');
        }
    }
}
