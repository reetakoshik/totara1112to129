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
 * Class with front-end (editing form) functionality.
 *
 * This is a base class of a class implemented by each component, and also has
 * static methods.
 *
 * @package core_availability
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_availability;

defined('MOODLE_INTERNAL') || die();

/**
 * Class with front-end (editing form) functionality.
 *
 * This is a base class of a class implemented by each component, and also has
 * static methods.
 *
 * @package core_availability
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class frontend {
    // Totara: Limit maximum depth to reasonably deep; see report_validation_errors() for more info
    const AVAILABILITY_JSON_MAX_DEPTH = 300;

    /**
     * Decides whether this plugin should be available in a given course. The
     * plugin can do this depending on course or system settings.
     *
     * Default returns true.
     *
     * @param \stdClass $course Course object
     * @param \cm_info $cm Course-module currently being edited (null if none)
     * @param \section_info $section Section currently being edited (null if none)
     */
    protected function allow_add($course, \cm_info $cm = null,
            \section_info $section = null) {
        return true;
    }

    /**
     * Gets a list of string identifiers (in the plugin's language file) that
     * are required in JavaScript for this plugin. The default returns nothing.
     *
     * You do not need to include the 'title' string (which is used by core) as
     * this is automatically added.
     *
     * @return array Array of required string identifiers
     */
    protected function get_javascript_strings() {
        return array();
    }

    /**
     * Gets additional parameters for the plugin's initInner function.
     *
     * Default returns no parameters.
     *
     * @param \stdClass $course Course object
     * @param \cm_info $cm Course-module currently being edited (null if none)
     * @param \section_info $section Section currently being edited (null if none)
     * @return array Array of parameters for the JavaScript function
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null,
            \section_info $section = null) {
        return array();
    }

    /**
     * Gets the Frankenstyle component name for this plugin.
     *
     * @return string The component name for this plugin
     */
    protected function get_component() {
        return preg_replace('~^(availability_.*?)\\\\frontend$~', '$1', get_class($this));
    }

    /**
     * Includes JavaScript for the main system and all plugins.
     *
     * @param \stdClass $course Course object
     * @param \cm_info $cm Course-module currently being edited (null if none)
     * @param \section_info $section Section currently being edited (null if none)
     */
    public static function include_all_javascript($course, \cm_info $cm = null,
            \section_info $section = null) {
        global $PAGE;

        // Prepare array of required YUI modules. It is bad for performance to
        // make multiple yui_module calls, so we group all the plugin modules
        // into a single call (the main init function will call init for each
        // plugin).
        $modules = array('moodle-core_availability-form', 'base', 'node',
                'panel', 'moodle-core-notification-dialogue', 'json');

        // Work out JS to include for all components.
        $pluginmanager = \core_plugin_manager::instance();
        $enabled = $pluginmanager->get_enabled_plugins('availability');
        $componentparams = new \stdClass();
        foreach ($enabled as $plugin => $info) {
            // Create plugin front-end object.
            $class = '\availability_' . $plugin . '\frontend';
            $frontend = new $class();

            // Add to array of required YUI modules.
            $component = $frontend->get_component();
            $modules[] = 'moodle-' . $component . '-form';

            // Get parameters for this plugin.
            $componentparams->{$plugin} = array($component,
                    $frontend->allow_add($course, $cm, $section),
                    $frontend->get_javascript_init_params($course, $cm, $section));

            // Include strings for this plugin.
            $identifiers = $frontend->get_javascript_strings();
            $identifiers[] = 'title';
            $identifiers[] = 'description';
            $PAGE->requires->strings_for_js($identifiers, $component);
        }

        // Include all JS (in one call). The init function runs on DOM ready.
        $PAGE->requires->yui_module($modules,
                'M.core_availability.form.init', array($componentparams), null, true);

        // Include main strings.
        $PAGE->requires->strings_for_js(array('none', 'cancel', 'delete', 'choosedots'),
                'moodle');
        $PAGE->requires->strings_for_js(array('addrestriction', 'invalid',
                'listheader_sign_before', 'listheader_sign_pos',
                'listheader_sign_neg', 'listheader_single',
                'listheader_multi_after', 'listheader_multi_before',
                'listheader_multi_or', 'listheader_multi_and',
                'unknowncondition', 'hide_verb', 'hidden_individual',
                'show_verb', 'shown_individual', 'hidden_all', 'shown_all',
                'condition_group', 'condition_group_info', 'and', 'or',
                'label_multi', 'label_sign', 'setheading', 'itemheading',
                'missingplugin'),
                'availability');
    }

    /**
     * For use within forms, reports any validation errors from the availability
     * field.
     *
     * @param array $data Form data fields
     * @param array $errors Error array
     */
    public static function report_validation_errors(array $data, array &$errors) {
        // Empty value is allowed!
        if ($data['availabilityconditionsjson'] === '') {
            return;
        }

        // Decode value.
        // Totara: Throw coding_exception if availabilityconditionsjson is not JSON or it is deeper than acceptable depth
        // In practice, the acceptable maximum depth of restriction sets is the half of AVAILABILITY_JSON_MAX_DEPTH.
        $decoded = json_decode($data['availabilityconditionsjson'], false, self::AVAILABILITY_JSON_MAX_DEPTH);
        if (!$decoded) {
            throw new \coding_exception('Invalid JSON from availabilityconditionsjson field');
        }
        if (!empty($decoded->errors)) {
            $error = '';
            foreach ($decoded->errors as $stringinfo) {
                list ($component, $stringname) = explode(':', $stringinfo);
                if ($error !== '') {
                    $error .= ' ';
                }
                $error .= get_string($stringname, $component);
            }
            $errors['availabilityconditionsjson'] = $error;
        } else {
            // Totara: Ensure that JSON can be loaded into a tree object before saving it.
            new \core_availability\tree($decoded);
        }
    }

    /**
     * Converts an associative array into an array of objects with two fields.
     *
     * This is necessary because JavaScript associative arrays/objects are not
     * ordered (at least officially according to the language specification).
     *
     * @param array $inarray Associative array key => value
     * @param string $keyname Name to use for key in resulting array objects
     * @param string $valuename Name to use for value in resulting array objects
     * @return array Non-associative (numeric) array
     */
    protected static function convert_associative_array_for_js(array $inarray,
            $keyname, $valuename) {
        $result = array();
        foreach ($inarray as $key => $value) {
            $result[] = (object)array($keyname => $key, $valuename => $value);
        }
        return $result;
    }

    /**
     * Parse JSON and recursively iterate conditions in its array
     *
     * @param string $availability_json JSON string of {course_modules}.availability
     * @param callable $callback A callback function that takes $condition parameter
     * @return void
     */
    protected static function for_each_condition_in_availability_json($availability_json, $callback) {
        $availability = json_decode($availability_json, false, self::AVAILABILITY_JSON_MAX_DEPTH * 3 / 2);
        if (is_object($availability)) {
            self::for_each_condition_in_availability($availability, $callback);
        }
    }

    /**
     * Recursively iterate conditions in its array
     *
     * @param \stdClass $availability part of {course_modules}.availability
     * @param callable $callback A callback function that takes $condition parameter
     * @return void
     */
    private static function for_each_condition_in_availability($availability, $callback) {
        foreach ($availability->c as $single_or_set) {
            if (isset($single_or_set->c)) {
                self::for_each_condition_in_availability($single_or_set, $callback);
            } else {
                $condition = $single_or_set;
                call_user_func($callback, $condition);
            }
        }
    }
}
