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
 * @package totara_appraisal
 */

namespace totara_appraisal\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when an appraisal_stage is updated.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - appraisalid   The id of the associated appraisal
 * }
 *
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_appraisal
 */
class stage_updated extends \core\event\base {

    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * The instance used to create the event.
     * @var \appraisal_stage
     */
    protected $stage;

    /**
     * Create instance of event.
     *
     * @param   \appraisal_stage $instance An appraisal_stage class.
     * @return  stage_updated
     */
    public static function create_from_instance(\appraisal_stage $instance) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context_system::instance(),
            'other' => array(
                'appraisalid' => $instance->appraisalid,
            ),
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        $event->stage = $instance;
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Get appraisal_stage instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \appraisal_stage
     */
    public function get_stage() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_stage() is intended for event observers only');
        }
        return $this->stage;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'appraisal_stage';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventupdatedstage', 'totara_appraisal');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The appraisal stage {$this->objectid} was updated";
    }

    /**
     * Returns relevant url.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $urlparams = array('appraisalid' => $this->data['other']['appraisalid'], 'action' => 'stageedit', 'id' => $this->objectid);
        return new \moodle_url('/totara/appraisal/stage.php', $urlparams);
    }

    public function get_legacy_logdata() {
        $appraisal = $this->data['other']['appraisalid'];
        $urlparams = array('appraisalid' => $appraisal, 'action' => 'stageedit', 'id' => $this->objectid);

        $logdata = array();
        $logdata[] = SITEID;
        $logdata[] = 'appraisal';
        $logdata[] = 'update stage';
        $logdata[] = new \moodle_url('/totara/appraisal/stage.php', $urlparams);
        $logdata[] = "General Settings: Appraisal ID={$appraisal}";

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

        if (!isset($this->other['appraisalid'])) {
            throw new \coding_exception('appraisalid must be set in $other');
        }
    }
}
