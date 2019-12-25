<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara_plan
 */

namespace totara_plan\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a component is removed from a plan
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - componentid The id of the component added
 * - component The component type (course, competency, evidence etc)
 * - componentname The freetext fullname of the component
 * - name The freetext fullname of the plan
 * }
 *
 */
class component_deleted extends \core\event\base {
    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;
    /** @var \development_plan */
    protected $plan;

    /**
     * Create component event for plan.
     * @param \development_plan $plan
     * @param string $component
     * @param int $componentid
     * @param string $componentname
     * @return component_deleted
     */
    public static function create_from_component(\development_plan $plan, $component, $componentid, $componentname) {
        $data = array(
            'objectid' => $plan->id,
            'context' => \context_system::instance(),
            'relateduserid' => $plan->userid,
            'other' => array(
                'name' => $plan->name,
                'component' => $component,
                'componentid' => $componentid,
                'componentname' => $componentname)
        );

        self::$preventcreatecall = false;
        /** @var component_deleted $event */
        $event = self::create($data);
        $event->plan = $plan;
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Get plan instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \development_plan
     */
    public function get_plan() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_plan() is intended for event observers only');
        }
        return $this->plan;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'dp_plan';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcomponentdeleted', 'totara_plan');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $desc = "The user with id '{$this->userid}' removed {$this->other['component']}";
        $desc .= " {$this->other['componentid']}:{$this->other['componentname']}";
        if (!in_array($this->other['component'], array('evidencetype', 'objectivescales', 'objectivescalevalues', 'priorityscales', 'priorityscalevalues'))) {
            $desc .= " from plan {$this->objectid}:{$this->other['name']}.";
        }
        return $desc;
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        $logurl = $this->get_url()->out_as_local_url(false);
        return array(SITEID, 'plan', "removed {$this->other['component']}", $logurl, "{$this->other['componentid']}:{$this->other['componentname']}");
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $component = $this->other['component'];
        switch ($component) {
            case 'competency':
            case 'course':
            case 'objective':
            case 'program':
                $logurl = new \moodle_url('/totara/plan/component.php', array('id' => $this->objectid, 'c' => $component));
                break;
            default:
                $logurl = new \moodle_url('/totara/plan/view.php', array('id' => $this->objectid));
                break;
        }
        return $logurl;
    }

    /**
     * Custom validation
     *
     * @throws \coding_exception
     * @return void
     */
    public function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly');
        }
        parent::validate_data();

        if (!isset($this->other['componentid'])) {
            throw new \coding_exception('componentid must be set in $other');
        }
        if (!isset($this->other['component'])) {
            throw new \coding_exception('component must be set in $other');
        }
        if (!isset($this->other['componentname'])) {
            throw new \coding_exception('componentname must be set in $other');
        }
        if (!isset($this->other['name'])) {
            throw new \coding_exception('name must be set in $other');
        }
    }
}
