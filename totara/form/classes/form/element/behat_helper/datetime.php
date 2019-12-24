<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\element\behat_helper;
use Behat\Mink\Exception\ExpectationException;

/**
 * A datetime element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class datetime extends text {

    /**
     * Sets the value of the datetime input.
     *
     * @param string $value
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function set_value($value) {
        $value = static::normalise_value_pre_set($value);
        if (!$this->context->running_javascript()) {
            // If JS is not running this is practically just a plain text field.
            parent::set_value($value);
            return;
        }
        // If JS is running then we need to use JS to set the value.
        // It has to be perfectly formatted.
        $text = $this->get_text_input();
        $id = $this->node->getAttribute('data-element-id');
        $js  = 'var e, t;';
        $js .= 'e = document.getElementById(' . json_encode($id) . ');';
        $js .= 'e.value = ' . json_encode($value) . ';';
        $this->context->getSession()->executeScript($js);

        // As this value is set via Javascript, simulate the change event
        $this->context->getSession()->getDriver()->triggerSynScript(
            $text->getXPath(),
            "Syn.trigger('change', {}, {{ELEMENT}})"
        );
        // Close the date picker by simulating a mousedown event elsewhere
        $this->context->getSession()->getDriver()->triggerSynScript(
            '//body',
            "Syn.trigger('mousedown', {}, {{ELEMENT}})"
        );
    }

    /**
     * Returns the value of the input.
     *
     * @return string
     */
    protected function get_value() {
        $value = parent::get_value();
        // Remove the trailing seconds.
        if (preg_match('/\d{1,2}:\d{1,2}:00$/', $value)) {
            $value = substr($value, 0, -3);
        }
        return $value;
    }

    /**
     * Asserts the field has expected value.
     *
     * @param string $expectedvalue
     * @return void
     */
    public function assert_value($expectedvalue) {
        $value = self::normalise_value_pre_set($this->get_value());
        $expected = self::normalise_value_pre_set($expectedvalue);

        if ($expected === $value) {
            return;
        }

        throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not match expected value: {$expectedvalue}", $this->context->getSession());
    }

    /**
     * Normalises the given value prior to setting it.
     *
     * @param string $value
     * @return string
     */
    public static function normalise_value_pre_set($value) {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        // Alright in behat feature files we accept a couple of different formats.
        // 1. YYYY-MM-DD(T| )hh:mm - this is the default
        // 2. YYYY-MM-DD  - this is our own.
        $regexdate = '#^(?P<year>\d{2,4})[\-/ ](?P<month>\d{1,2})[\-/ ](?P<day>\d{1,2})([T ](?P<hour>\d{1,2}):(?P<minute>\d{1,2}))?$#';
        // The internal must start with +P or -P.
        $regexinterval = '/^([+-])(P(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?)$/';

        if (preg_match($regexinterval, $value, $matches)) {
            $date = new \DateTime();
            $interval = new \DateInterval($matches[2]);
            if ($matches[1] === '+') {
                $date->add($interval);
            } else {
                $date->sub($interval);
            }

        } else if (preg_match($regexdate, $value, $matches)) {
            $year = $matches['year'];
            $month = (int)$matches['month'];
            $day = (int)$matches['day'];
            // Why 3:45pm you ask - just so that we know what we can expect when the datetime has not been specified.
            $hour = isset($matches['hour']) ? (int)$matches['hour'] : 15;
            $minute = isset($matches['minute']) ? (int)$matches['minute'] : 45;
            if ($year < 99) {
                $year += 2000;
            }

            $date = new \DateTime();
            $date->setDate($year, $month, $day);
            $date->setTime($hour, $minute, 0);

        } else {
            throw new \coding_exception('Invalid datetime value provided, it should be YYYY-MM-DD hh:mm date or relative +/- P interval, "'.$value.'"');
        }

        return $date->format('Y-m-d\TH:i');
    }
}