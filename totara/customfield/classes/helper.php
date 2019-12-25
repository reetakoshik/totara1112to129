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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_customfield
 */

namespace totara_customfield;

/**
 * Class helper
 *
 * @package totara_customfield
 */
class helper {

    /**
     * A statically held instance of self.
     *
     * Reset by calling {@link \totara_customfield\helper::get_instance(true)} and destroyed by destroying the
     * current instance (returned by {@link \totara_customfield\helper::get_instance()})
     *
     * @var helper
     */
    protected static $instance;

    /**
     * A cache instance used to store area mappings
     * @var \cache_application
     */
    private $areacache;

    /**
     * helper constructor.
     */
    protected function __construct() {
        // Nothing to do here, we just want it defined as protected.
    }

    /**
     * Destroys this instance and clears the static instance.
     */
    public function __destruct() {
        // This object is being destroyed, clear out self::$instance.
        // Just ensures that memory is freed up and that we can clear the helper by calling unset($helper).
        // Useful during testing.
        self::$instance = null;
    }

    /**
     * Returns an instance of the helper.
     *
     * @param bool $reset If set to true then a new instance is created; always.
     * @return helper
     */
    public static function get_instance($reset = false) {
        if (self::$instance === null || $reset) {
            self::$instance = new helper();
        }
        return self::$instance;
    }

    /**
     * Returns an area cache instance.
     *
     * @return \cache_application
     */
    private function get_areacache() {
        if ($this->areacache === null) {
            $this->areacache = \cache::make('totara_customfield', 'areamap');
        }
        return $this->areacache;
    }

    /**
     * Returns the area management class.
     *
     * @param string $area
     * @return string|area An area class as a string (not initialised!)
     * @throws \coding_exception if the area does not exist. If you must check use {@link \totara_customfield\helper::area_has_management_class()}.
     */
    public function get_area_class($area) {
        $map = $this->get_areacache()->get(\totara_customfield\areamap_data_source::CLASSMAP);
        if (!isset($map[$area])) {
            throw new \coding_exception('Invalid customfield area class requested', $area);
        }

        return $map[$area];
    }

    /**
     * Returns the area management class.
     *
     * @param string $area
     * @return string The component that owns an area.
     * @throws \coding_exception if the area does not exist. If you must check use {@link \totara_customfield\helper::area_has_management_class()}.
     */
    public function get_area_component($area) {
        $map = $this->get_areacache()->get(\totara_customfield\areamap_data_source::COMPONENTMAP);
        if (isset($map[$area])) {
            return $map[$area];
        }
        throw new \coding_exception('Invalid customfield area component requested', $area);
    }

    /**
     * Returns the area management class for a given filearea.
     *
     * @param string $filearea
     * @return string|area An area class as a string (not initialised!)
     * @throws \coding_exception if the area does not exist. If you must check use {@link \totara_customfield\helper::area_has_management_class()}.
     */
    public function get_area_class_by_filearea($filearea) {
        $map = $this->get_areacache()->get(\totara_customfield\areamap_data_source::FILEAREAMAP);
        if (isset($map[$filearea])) {
            return $map[$filearea];
        }
        throw new \coding_exception('An invalid filearea was requested from totara_customfields.', $filearea);
    }

    /**
     * Returns the area management class for a given filearea.
     *
     * @param string $prefix
     * @return string|area An area class as a string (not initialised!)
     * @throws \coding_exception if the area does not exist. If you must check use {@link \totara_customfield\helper::area_has_management_class()}.
     */
    public function get_area_class_by_prefix($prefix) {
        $map = $this->get_areacache()->get(\totara_customfield\areamap_data_source::PREFIXMAP);
        if (isset($map[$prefix])) {
            return $map[$prefix];
        }
        throw new \coding_exception('An invalid prefix was requested from totara_customfields.', $prefix);
    }

    /**
     * Returns true if the area has a management class.
     *
     * All areas should have management classes, however as they were not originally required they may not be there in custom code.
     * All core areas MUST have a management class.
     *
     * @param string $area
     * @return bool
     */
    public function area_has_management_class($area) {
        $map = $this->get_areacache()->get(\totara_customfield\areamap_data_source::COMPONENTMAP);
        return (isset($map[$area]));
    }

    /**
     * Returns true if the given filearea belongs to a customfield area.
     *
     * All areas should have management classes, however as they were not originally required they may not be there in custom code.
     * All core areas MUST have a management class.
     *
     * @param string $filearea
     * @return bool
     */
    public function check_if_filearea_recognised($filearea) {
        $map = $this->get_areacache()->get(\totara_customfield\areamap_data_source::FILEAREAMAP);
        return isset($map[$filearea]);
    }

    /**
     * Returns true if the given prefix belongs to a customfield area.
     *
     * All areas should have management classes, however as they were not originally required they may not be there in custom code.
     * All core areas MUST have a management class.
     *
     * @param string $prefix
     * @return bool
     */
    public function check_if_prefix_recognised($prefix) {
        $map = $this->get_areacache()->get(\totara_customfield\areamap_data_source::PREFIXMAP);
        return isset($map[$prefix]);
    }

    /**
     * Returns an array of all area classes.
     *
     * @return string[]|area[] An array of area classes as a string (not initialised!)
     */
    public function get_area_classes() {
        return $this->get_areacache()->get(\totara_customfield\areamap_data_source::CLASSMAP);
    }

    /**
     * Returns an array of all area components.
     *
     * @return string[]
     */
    public function get_area_components() {
        return $this->get_areacache()->get(\totara_customfield\areamap_data_source::COMPONENTMAP);
    }

    /**
     * Returns an array of all fileareas mapped to customfield areas.
     *
     * @return string[]|area[] An array of area classes as a string (not initialised!)
     */
    public function get_filearea_mappings() {
        return $this->get_areacache()->get(\totara_customfield\areamap_data_source::FILEAREAMAP);
    }

    /**
     * Legacy serving of customfield pluginfiles.
     *
     * This method exists to serve a file in the legacy manner.
     * This should not ever happen, all customfield uses should implement an area management class that extends
     * the area interface.
     * This class will then be responsible for serving the files for that customfield area with the required permission checks.
     *
     * Please note that this function will be converted to throw an exception after the release of Totara 10 and removed
     * permanently in the future.
     *
     * @deprecated since introduction
     * @param \stdClass $course
     * @param \stdClass $cm
     * @param \context $context
     * @param string $filearea
     * @param array $args
     * @param bool $forcedownload
     * @param array $options
     * @return void
     */
    public function legacy_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = array()) {
        $fs = get_file_storage();
        $fullpath = "/{$context->id}/totara_customfield/$filearea/$args[0]/$args[1]";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            // We do this before the error_log so that people don't get spammed in their error logs if someone is trying
            // to abuse the system.
            send_file_not_found();
        }

        // If you are here because you are seeing this in your server logs then here is what you need to do:
        //   1. Identify the custom code you've got that makes use of Totara customfields.
        //   2. Get your developer to create a new class in that plugin/component that extends \totara_customfield\area
        //
        // The class will require you to create all the necessary methods, use it as an opportunity to write proper access control
        // for the files in the customfield area you have added.
        error_log('Totara customfield area "'.clean_param($filearea, PARAM_COMPONENT).'" needs to implement an area management class');
        send_stored_file($file, 86400, 0, true, $options); // download MUST be forced - security!
    }

}
