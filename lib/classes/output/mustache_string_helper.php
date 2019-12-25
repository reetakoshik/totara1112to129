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
 * Mustache helper to load strings from string_manager.
 *
 * @package    core
 * @category   output
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;

use Mustache_LambdaHelper;

/**
 * This class will load language strings in a template.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */
class mustache_string_helper {

    /**
     * A queue of debugging messages that need to be sent.
     * @var array
     */
    private $debuggingqueue = [];

    /**
     * Read a lang string from a template and get it from get_string.
     *
     * Some examples for calling this from a template are:
     *
     * Examples of how to use this helper:
     *   - {{#str}}identifier{{/str}}
     *   - {{#str}}identifier, component{{/str}}
     *   - {{#str}}identifier, component, a_identifier{{/str}}
     *   - {{#str}}identifier, component, a_identifier, a_component{{/str}}
     *
     * Variable arguments:
     * Identifier and component can contain mustache variables, however please note that they are not rendered, they are simply fetched
     * from the available context data, validated as string identifiers and components, and then used for get_string calls.
     * They are never used directly, and are always validated to ensure they are in the correct format, this is incredibly important
     * as you can't trust them!
     *
     * Examples of the old API (deprecated):
     *   - {{#str}}actionchoice, core, {{#str}}delete{{/str}}{{/str}} (Nested)
     *   - {{#str}}addinganewto, core, {"what":"This", "to":"That"}{{/str}} (Complex $a)
     *
     * The args are comma separated and only the first is required.
     * The last is a $a argument for get string. For complex data here, use JSON.
     *
     * @param string $text The text to parse for arguments.
     * @param Mustache_LambdaHelper $helper Used to render nested mustache variables.
     * @return string
     */
    public function str($text, Mustache_LambdaHelper $helper) {
        $bits = explode(',', $text);

        $identifier = $this->get_and_expand($bits, $helper);
        $component = $this->get_and_expand($bits, $helper);

        $identifier_cleaned = clean_param($identifier, PARAM_STRINGID);
        if ($identifier_cleaned !== $identifier) {
            // Don't fail here, instead return an empty string and ensure we show sime debugging.
            $this->debuggingqueue[] = 'Invalid identifier for string helper must be a string identifier.';
            return $this->finalised('');
        }
        $component_cleaned = clean_param($component, PARAM_COMPONENT);
        if ($component_cleaned !== $component) {
            // This will resolve to core for component.
            $this->debuggingqueue[] = 'Invalid component for string helper must be a string component.';
        }
        unset($identifier);
        unset($component);

        if (empty($bits)) {
            // Just an identifier and component.
            // $a is an empty string just for backwards compatibility. What a hack!
            return $this->finalised(get_string($identifier_cleaned, $component_cleaned, ''));
        }

        // There is a $a, lets check if it is old API or new.
        if ($this->is_legacy_api_parameters($bits)) {
            // It's the legacy API.
            $this->debuggingqueue[] = 'Legacy string helper API in use, this will not be supported in the future.';
            return $this->finalised($this->process_legacy_string($identifier_cleaned, $component_cleaned, $bits, $helper));
        }

        // It's new API, yay!
        $a_identifier = $this->get($bits);
        $a_component = $this->get($bits);
        if (!empty($bits)) {
            $this->debuggingqueue[] = 'Unexpected number of arguments, '.count($bits).' too many';
        }
        unset($bits);

        $a_identifier_cleaned = clean_param($a_identifier, PARAM_STRINGID);
        if ($a_identifier_cleaned !== $a_identifier) {
            $this->debuggingqueue[] = 'Invalid $a identifier for string helper must be a string identifier.';
        }
        $a_component_cleaned = clean_param($a_component, PARAM_COMPONENT);
        if ($a_component_cleaned !== $a_component) {
            $this->debuggingqueue[] = 'Invalid $a component for string helper must be a string component.';
        }
        unset($a_identifier);
        unset($a_component);

        $a = null;
        if (!empty($a_identifier_cleaned)) {
            $a = get_string($a_identifier_cleaned, $a_component_cleaned);
        }
        return $this->finalised(get_string($identifier_cleaned, $component_cleaned, $a));
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
     * Expand the given string by passing it through Mustache for rendering.
     *
     * @param string $string
     * @param Mustache_LambdaHelper $helper
     * @return string
     */
    private function expand($string, Mustache_LambdaHelper $helper) {
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
    private function is_legacy_api_parameters(array $parameters): bool {
        $first = reset($parameters);
        $first = trim($first);
        if (strpos($first, '{') !== 0) {
            return false;
        }
        $last = end($parameters);
        $last = trim($last);
        if ((strrpos($last, '}') !== (strlen($last) - 1))) {
            return false;
        }
        // Variables within the string helper are legacy API!
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
    private function process_legacy_string(string $identifier, string $component, array $parameters, Mustache_LambdaHelper $helper): string {
        $parameters = join(',', $parameters);
        if ((strpos($parameters, '{') === 0) && (strpos($parameters, '{{') !== 0)) {
            $rawjson = $this->expand($parameters, $helper);
            $a = json_decode($rawjson);
        } else {
            $a = $this->expand($parameters, $helper);
            // TL-7924: added the ability to send through variables from mustache
            if ((strpos($a, '{') === 0) && (strpos($a, '{{') !== 0)) {
                $a = json_decode($a);
            }
        }
        return get_string($identifier, $component, $a);
    }

    /**
     * Shows debugging details for this helper at the current time, trimming out all the mustache
     * stacktrace items to make it readable.
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
