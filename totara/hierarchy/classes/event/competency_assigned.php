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
 * Abstract Event used as the base by each competency_assignment,
 * triggered when a competency_assignment assignment is created.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - competencyid  The id of the related competency
 *      - instanceid    The id of the related pos/org
 * }
 *
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_hierarchy
 */
abstract class competency_assigned extends \core\event\base {

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
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $competencyid = $this->data['other']['competencyid'];
        $instanceid = $this->data['other']['instanceid'];
        $prefix = $this->get_prefix();

        return "The competency: {$competencyid} was assigned to the {$prefix}: {$instanceid}";
    }

    public function get_url() {
        $urlparams = array('id' => $this->data['other']['instanceid'], 'prefix' => $this->get_prefix());
        return new \moodle_url('/totara/hierarchy/item/view.php', $urlparams);
    }

    public function get_legacy_logdata() {
        $prefix = $this->get_prefix();

        $logdata = array();
        $logdata[] = SITEID;
        $logdata[] = $prefix;
        $logdata[] = 'create competency assignment';
        $logdata[] = $this->get_url()->out_as_local_url(false);
        $logdata[] = "{$prefix}: {$this->data['other']['instanceid']} - competency: {$this->data['other']['competencyid']}";

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

        if (empty($this->other['competencyid'])) {
            throw new \coding_exception('competencyid must be set in $other');
        }

        if (empty($this->other['instanceid'])) {
            throw new \coding_exception('instanceid must be set in $other');
        }
    }
}
