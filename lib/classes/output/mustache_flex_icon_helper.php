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
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralms.com>
 * @package   core
 */

namespace core\output;

use Mustache_LambdaHelper;
use renderer_base;

/**
 * Mustache flex icon helper.
 *
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralms.com>
 * @package   core
 */
class mustache_flex_icon_helper {

    /**
     * @var renderer_base|\core_renderer
     */
    protected $renderer;

    /**
     * A queue of debugging messages that need to be sent.
     * @var array
     */
    private $debuggingqueue = [];

    /**
     * Constructor
     *
     * @param renderer_base $renderer
     */
    public function __construct(renderer_base $renderer) {

        $this->renderer = $renderer;

    }

    /**
     * Read a pix icon name from a template and get it from pix_icon.
     *
     * Correct use:
     *   - {{#flex_icon}}identifier, alt_identifier, alt_component, classes{{/flex_icon}}
     *   - identifier: The flex icon identifier, e.g. move_up, t/class
     *   - alt_identifier: A lang string identifier for the alt text.
     *   - alt_component: A lang string component for the alt text.
     *   - classes: A list of classes to add to the flex icon.
     *
     * Legacy use:
     *   - {{#flex_icon}}t/class, { "classes": "size" }{{/flex_icon}}
     *   - Please note that this style is deprecated and will be removed
     *     after the release of Totara 12.
     *
     * The args are comma separated and only the first is required.
     *
     * @throws \coding_exception if the JSON cache could not be decoded.
     * @param string $string Content of flex_icon helper in template.
     * @param Mustache_LambdaHelper $helper Used to render nested mustache variables.
     * @return string
     */
    public function flex_icon($string, Mustache_LambdaHelper $helper) {

        $bits = explode(',', $string);

        // There are two patterns to the bits.
        // 1. The legacy:
        //    {{#flex_icon}}t/class, { "classes": "size" }{{/flex_icon}}
        // 2. The current:
        //    {{#flex_icon}}t/class, alt_identifier, alt_component, classes{{/flex_icon}}

        $identifier = $this->get_and_expand($bits, $helper);

        if ($this->is_legacy_api_bits($bits)) {
            // It's a legacy use.
            $this->debuggingqueue[] = 'Legacy flex icon helper API in use, please use the flex icon template instead.';
            return $this->finalised($this->process_legacy_flex_icon($identifier, $bits, $helper));
        }

        $flexicon = new flex_icon($identifier);
        if (empty($bits)) {
            return $this->finalised($this->renderer->render($flexicon));
        }
        $alt_identifier = $this->get_and_expand($bits, $helper);
        $alt_identifier_cleaned = clean_param($alt_identifier, PARAM_STRINGID);
        if ($alt_identifier_cleaned !== $alt_identifier) {
            $this->debuggingqueue[] = 'Invalid alt identifier for flex icon, it must be a string identifier.';
        }

        $alt_component = $this->get_and_expand($bits, $helper, 'core');
        $alt_component_cleaned = clean_param($alt_component, PARAM_COMPONENT);
        if ($alt_component_cleaned !== $alt_component) {
            $this->debuggingqueue[] = 'Invalid alt component for flex icon, it must be a string component.';
        }

        if ($alt_identifier_cleaned && $alt_component_cleaned) {
            $flexicon->customdata['alt'] = get_string($alt_identifier_cleaned, $alt_component_cleaned);
        }

        if (count($bits)) {
            $classes = $this->get($bits);
            // Strip invalid class characters.
            $classes = preg_replace('#[^a-zA-Z0-9_\- ]+#', '', trim($classes));
            $flexicon->customdata['classes'] = $classes;
        }

        return $this->finalised($this->renderer->render($flexicon));
    }

    /**
     * Finalises and returns the text, throwing any required debugging notices.
     *
     * @param string $text
     * @return mixed
     */
    private function finalised(string $text): string {
        if (!empty($this->debuggingqueue)) {
            $this->debugging(join(" \n", $this->debuggingqueue));
            $this->debuggingqueue = [];
        }
        return $text;
    }

    /**
     * Gets the next piece from $bits, trims it, expands it, and then returns it.
     *
     * @param array $bits
     * @param Mustache_LambdaHelper $helper
     * @param string $default
     * @return string
     */
    private function get_and_expand(array &$bits, Mustache_LambdaHelper $helper, $default = ''): string {
        $string = $this->get($bits, $default);
        $string = $this->expand($string, $helper);
        return $string;
    }

    /**
     * Gets the next piece from $bits, trims it, and then returns it.
     *
     * @param array $bits
     * @param string $default
     * @return string
     */
    private function get(array &$bits, $default = ''): string {
        $string = array_shift($bits);
        if ($string === null) {
            return $default;
        }
        $string = trim($string);
        return $string;
    }

    /**
     * Expand the given string, putting it through mustache renderering.
     *
     * This function ensures the string is escaped in order to prevent recursive rendering by other helpers.
     *
     * @param string $string
     * @param Mustache_LambdaHelper $helper
     * @return string
     */
    private function expand(string $string, Mustache_LambdaHelper $helper): string {
        if (strpos($string, '{') !== false) {
            return $helper->render("{{#esc}}{$string}{{/esc}}");
        }
        return $string;
    }

    /**
     * Checks if the parameter is set up for the legacy API.
     *
     * @deprecated since Totara 12, will be removed in Totara 13.
     * @param array $bits
     * @return bool
     */
    private function is_legacy_api_bits(array $bits): bool {
        $first = reset($bits);
        $first = trim($first);
        if (strpos($first, '{') !== 0) {
            return false;
        }
        $last = end($bits);
        $last = trim($last);
        if ((strrpos($last, '}') !== (strlen($last) - 1))) {
            return false;
        }
        if (strpos($first, '{{') === 0 && (strrpos($last, '}}') !== (strlen($last) - 1))) {
            return false;
        }
        return true;
    }

    /**
     * Process a flex icon given the legacy API json stuff.
     *
     * @deprecated since Totara 12, will be removed in Totara 13.
     * @param string $identifier
     * @param array $bits
     * @param Mustache_LambdaHelper $helper
     * @return string
     */
    private function process_legacy_flex_icon(string $identifier, array $bits, Mustache_LambdaHelper $helper): string {
        $customdata = join(',', $bits);
        $customdata = @json_decode($customdata, true);
        if ($customdata === null) {
            throw new \coding_exception("flex_icon helper was unable to decode JSON");
        }
        foreach ($customdata as $key => $value) {
            $customdata[$key] = $this->expand($value, $helper);
        }
        $flexicon = new flex_icon($identifier, $customdata);
        return $this->renderer->render($flexicon);
    }


    /**
     * Sends a debugging message, but with the Mustache backtrace trimmed out.
     *
     * @codeCoverageIgnore
     * @param string $message
     */
    private function debugging(string $message) {
        if (debugging()) {
            // We want to strip out the Mustache entries from the backtrace to make this meaningful.
            $backtrace = debug_backtrace();
            $count = 0;
            foreach ($backtrace as $caller) {
                if (isset($caller['function']) && strpos($caller['function'], 'render_from_template') !== false) {
                    // Include this line and then stop.
                    break;
                }
                $count++;
            }
            if ($count) {
                $backtrace = array_slice($backtrace, $count);
            }
            debugging($message, DEBUG_DEVELOPER, $backtrace);
        }
    }

}
