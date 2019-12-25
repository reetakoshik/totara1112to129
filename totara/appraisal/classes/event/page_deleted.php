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
 * Event triggered when an appraisal page is deleted.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - stageid   The id of the associated stage
 * }
 *
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_appraisal
 */
class page_deleted extends \core\event\base {

    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * The instance used to create the event.
     * @var \stdClass
     */
    protected $page;

    /**
     * Create instance of event.
     *
     * @param   \stdClass $instance An appraisal page record.
     * @return  page_deleted
     */
    public static function create_from_instance(\stdClass $instance) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context_system::instance(),
            'other' => array(
                'stageid' => $instance->appraisalstageid,
            ),
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        $event->page = $instance;
        $event->add_record_snapshot('appraisal_stage_page', $instance);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Get appraisal_page instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \stdClass
     */
    public function get_page() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_page() is intended for event observers only');
        }
        return $this->page;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'appraisal_stage_page';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventdeletedpage', 'totara_appraisal');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The appraisal page {$this->objectid} was deleted";
    }

    /**
     * Returns relevant url.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $urlparams = array('id' => $this->data['other']['stage']);
        return new \moodle_url('/totara/appraisal/stage.php', $urlparams);
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

        if (!isset($this->other['stageid'])) {
            throw new \coding_exception('stageid must be set in $other');
        }
    }
}
