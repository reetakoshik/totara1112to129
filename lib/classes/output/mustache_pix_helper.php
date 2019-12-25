<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Mustache helper render pix icons.
 *
 * @package    core
 * @category   output
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;

use Mustache_LambdaHelper;
use renderer_base;

/**
 * This class will call pix_icon with the section content.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */
class mustache_pix_helper {

    /** @var \core_renderer $renderer A reference to the renderer in use */
    private $renderer;

    /**
     * A queue of debugging messages that need to be sent.
     * @var array
     */
    private $debuggingqueue = [];

    /**
     * Save a reference to the renderer.
     * @param \core_renderer|renderer_base $renderer
     */
    public function __construct(renderer_base $renderer) {
        $this->renderer = $renderer;
    }

    /**
     * Read a pix icon name from a template and get it from pix_icon.
     *
     * API usage:
     *  - {{#pix}}identifier{{/pix}}
     *  - {{#pix}}identifier, component{{/pix}}
     *  - {{#pix}}identifier, component, alt_identifier{{/pix}}
     *  - {{#pix}}identifier, component, alt_identifier, alt_component{{/pix}}
     *
     * Legacy API usage: (Deprecated to be removed after Totara 12.
     *  - {{#pix}}t/edit,component,Anything else is alt text{{/pix}}
     *  - {{#pix}}t/edit,component,{{#str}}edit{{/str}}{{/pix}}
     *
     * The args are comma separated and only the first is required.
     *
     * @param string $text The text to parse for arguments.
     * @param Mustache_LambdaHelper $helper Used to render nested mustache variables.
     * @return string
     */
    public function pix($text, Mustache_LambdaHelper $helper) {

        $bits = explode(',', $text);

        $identifier = $this->get_and_expand($bits, $helper);
        $component = $this->get_and_expand($bits, $helper);
        if (!reset($bits)) {
            if ($component === 'flexicon') {
                // TOTARA HACK: The pix helper has received a flex icon, we can use the flex_helper.
                $flexicon = new flex_icon($identifier);
                return $this->finalised($this->renderer->render($flexicon));
            }
            return $this->finalised($this->renderer->pix_icon($identifier, '', $component));
        }

        if (empty($bits)) {
            return $this->finalised($this->renderer->pix_icon($identifier, '', $component));
        }

        $next = $this->get($bits, '');

        // Check if it looks like a string identifier. It's a guess, sorry.
        // Intentionally favour the new API.
        if ($next && $next === clean_param($next, PARAM_STRINGID)) {
            // We don't need to clean again, we know that it cleans fine because of the above if.
            $alt_identifier_cleaned = $next;

            $alt_component = $this->get($bits, '');
            $alt_component_cleaned = clean_param($alt_component, PARAM_COMPONENT);
            if ($alt_component_cleaned !== $alt_component) {
                $this->debuggingqueue[] = 'Invalid $a component for pix helper must be a string component.';
            }
            $data = [
                'alt' => get_string($alt_identifier_cleaned, $alt_component_cleaned)
            ];
        } else {
            $this->debuggingqueue[] = 'Legacy pix icon helper API in use, please use the pix icon template instead.';
            // Hmm, add it back on for ease of processing.
            array_unshift($bits, $next);
            if ($this->is_legacy_api_customdata($bits)) {
                $data = $this->process_legacy_pix_icon_customdata($bits, $helper);
            } else {
                // OK, its raw content.
                // Rejoin the bits into a single item, and turn that into an array.
                $bits = [
                    join(',', $bits)
                ];
                $data = [
                    'alt' => format_string($this->get_and_expand($bits, $helper))
                ];
            }
        }

        if ($data['alt'] !== '') {
            $data['title'] = $data['alt'];
        }

        if ($component === 'flexicon') {
            // TOTARA HACK: The pix helper has received a flex icon, we can use the flex_helper.
            // Arguments are passed as following:
            //    identifier, component, json encoded icon data.
            // This hack is facilitate by code in \core\output\flex_icon::export_for_pix()
            $flexicon = new flex_icon($identifier, $data);
            return $this->finalised($this->renderer->render($flexicon));
        }

        return $this->finalised($this->renderer->pix_icon($identifier, '', $component, $data));
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
        return trim($text);
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
     * @param array $parameters
     * @return bool
     */
    private function is_legacy_api_customdata(array $parameters): bool {
        $first = reset($parameters);
        $first = trim($first);
        if (strpos($first, '{') !== 0) {
            // It's not a variable, or json.
            return false;
        }
        $last = end($parameters);
        $last = trim($last);
        if ((strrpos($last, '}') !== (strlen($last) - 1))) {
            // It's not a variable, or json.
            return false;
        }
        if (strpos($first, '{{') === 0 && (strrpos($last, '}}') !== (strlen($last) - 1))) {
            // It's a variable.
            return false;
        }
        // OK, its JSON.
        return true;
    }

    /**
     * Process a flex icon given the legacy API json stuff.
     *
     * @deprecated since Totara 12, will be removed in Totara 13.
     * @param string $identifier
     * @param array $parameters
     * @param Mustache_LambdaHelper $helper
     * @return string
     */
    private function process_legacy_pix_icon_customdata(array $parameters, Mustache_LambdaHelper $helper): array {
        $customdata = join(',', $parameters);
        $customdata = @json_decode($customdata, true);
        if ($customdata === null) {
            throw new \coding_exception("flex_icon helper was unable to decode JSON");
        }
        foreach ($customdata as $identifier => $value) {
            $customdata[$identifier] = $this->expand($value, $helper);
        }
        return $customdata;
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

