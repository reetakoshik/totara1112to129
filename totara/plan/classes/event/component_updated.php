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
 * Event triggered when a component in a plan is updated
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
class component_updated extends \core\event\base {
    /** @var \development_plan */
    protected $plan;

    /**
     * Create component event for plan.
     *
     * When creating a component_updated event for a competencyproficiency, function create should be used instead,
     * and you should ensure that proficiencyvalue is included in the 'other' data.
     *
     * @param \development_plan $plan
     * @param string $component
     * @param int $componentid
     * @param string $componentname
     * @return component_updated
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

        /** @var component_updated $event */
        $event = self::create($data);
        $event->plan = $plan;

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
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'dp_plan';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcomponentupdated', 'totara_plan');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $desc = "The user with id '{$this->userid}' updated {$this->other['component']} {$this->other['componentid']}:{$this->other['componentname']}";
        $components = array('plan', 'evidencetype', 'objectivescales', 'objectivescalevalues', 'priorityscales', 'priorityscalevalues');
        if (!in_array($this->other['component'], $components)) {
            $desc .= " in plan {$this->objectid}:{$this->other['name']}.";
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
        return array(SITEID, 'plan', "updated {$this->other['component']}", $logurl, "{$this->other['componentid']}:{$this->other['componentname']}");
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
                $logurl = new \moodle_url("/totara/plan/components/$component/view.php", array('id' => $this->objectid, 'itemid' => $this->other['componentid']));
                break;
            case 'competencyproficiency':
                $params = array('competencyid' => $this->other['componentid'], 'planid' => $this->objectid, 'prof' => $this->other['proficiencyvalue']);
                $logurl = new \moodle_url('/totara/plan/components/competency/update-competency-setting.php', $params);
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
        if ($this->other['component'] == 'competencyproficiency' && !isset($this->other['proficiencyvalue'])) {
            throw new \coding_exception('proficiencyvalue must be set in $other');
        }
    }
}
