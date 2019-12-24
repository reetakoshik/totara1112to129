<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\clientaction;

use totara_form\clientaction,
    totara_form\item;

/**
 * Onchange reload client action class.
 *
 * The onchange reload client action reloads the form when the targetted element changes.
 *
 * @since Totara 9.10, 10
 * @package totara_form
 * @copyright 2017 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 */
class onchange_reload implements clientaction {

    /**
     * @var item
     */
    protected $target;

    /**
     * @var string[]
     */
    private $ignoredvalues = [];

    /**
     * Onchange reload client action constructor.
     *
     * @throws \coding_exception if the given element does not implement \totara_form\form\clientaction\supports_onchange_clientactions
     * @param item $target
     */
    public function __construct(item $target) {
        if (!$target instanceof supports_onchange_clientactions) {
            throw new \coding_exception('Only elements implementing \totara_form\form\clientaction\supports_onchange_clientactions can use the onchange reload client action.', get_class($target));
        }
        $this->target = $target;
    }

    /**
     * Adds an ignored value to the client action.
     *
     * If the value of an element using this client action changes to an ignored value then the form will NOT be reloaded.
     *
     * @param string $value The value to ignore as a string.
     * @return self This method is chainable.
     */
    public function add_ignored_value($value) {
        $value = (string)$value;
        if (!in_array($value, $this->ignoredvalues)) {
            $this->ignoredvalues[] = $value;
        }
        return $this;
    }

    /**
     * Adds empty value to the list of values to ignore when checking if the form should be reloaded.
     *
     * If the value of an element using this client action changes to an ignored value then the form will NOT be reloaded.
     *
     * @return self This method is chainable.
     */
    public function ignore_empty_values() {
        $emptyvalues = [
            '',
            '0'
        ];
        $method = 'get_empty_values';
        $reflectionclass = new \ReflectionClass($this->target);
        if ($reflectionclass->hasMethod($method)) {
            $method = $reflectionclass->getMethod($method);
            if ($method->isPublic()) {
                $emptyvalues = $method->invoke($this->target);
            }
        }
        foreach ($emptyvalues as $value) {
            $this->add_ignored_value($value);
        }
        return $this;
    }

    /**
     * Returns the configuration object that needs to be passed to JS.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function get_js_config_obj(\renderer_base $output) {
        $data = new \stdClass;
        $data->target = $this->target->get_id();
        $data->ignoredvalues = $this->ignoredvalues;
        $data->delay = clientaction::DELAY;
        return $data;
    }
}
