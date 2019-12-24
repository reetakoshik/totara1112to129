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
 * Utility class for browsing of system files.
 *
 * @package    core_files
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Represents the system context in the tree navigated by {@link file_browser}.
 *
 * @package    core_files
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_info_context_system extends file_info {

    /**
     * Constructor
     *
     * @param file_browser $browser file_browser instance
     * @param stdClass $context context object
     */
    public function __construct($browser, $context) {
        parent::__construct($browser, $context);
    }

    /**
     * Return information about this specific part of context level
     *
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return file_info|null file_info instance or null if not found or access not allowed
     */
    public function get_file_info($component, $filearea, $itemid, $filepath, $filename) {
        if (empty($component)) {
            return $this;
        }

        // no components supported at this level yet
        return null;
    }

    /**
     * Returns localised visible name.
     *
     * @return string
     */
    public function get_visible_name() {
        return get_string('arearoot', 'repository');
    }

    /**
     * Whether or not new files or directories can be added
     *
     * @return bool
     */
    public function is_writable() {
        return false;
    }

    /**
     * Whether or not this is a directory
     *
     * @return bool
     */
    public function is_directory() {
        return true;
    }

    /**
     * Returns list of children.
     *
     * @return array of file_info instances
     */
    public function get_children() {
        global $DB;

        $children = array();

        // Add course categories on the top level that are either visible or user is able to view hidden categories.
        $course_cats = $DB->get_records('course_categories', array('parent'=>0), 'sortorder', 'id,visible');
        foreach ($course_cats as $category) {
            $context = context_coursecat::instance($category->id);
            if (!$category->visible and !has_capability('moodle/category:viewhiddencategories', $context)) {
                continue;
            }
            if ($child = $this->browser->get_file_info($context)) {
                $children[] = $child;
            }
        }

        // Add courses where user is enrolled that are located in hidden course categories because they would not
        // be present in the above tree but user may still be able to access files in them.
        if ($hiddencontexts = $this->get_inaccessible_coursecat_contexts()) {
            $courses = enrol_get_my_courses();
            foreach ($courses as $course) {
                $context = context_course::instance($course->id);
                $parents = $context->get_parent_context_ids();
                if (array_intersect($hiddencontexts, $parents)) {
                    // This course has hidden parent category.
                    if ($child = $this->browser->get_file_info($context)) {
                        $children[] = $child;
                    }
                }
            }
        }

        return $children;
    }

    /**
     * Returns list of course categories contexts that current user can not see
     *
     * @return array array of course categories contexts ids
     */
    protected function get_inaccessible_coursecat_contexts() {
        global $DB;

        $sql = context_helper::get_preload_record_columns_sql('ctx');
        $records = $DB->get_records_sql("SELECT ctx.id, $sql
            FROM {course_categories} c
            JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = ?
            WHERE c.visible = ?", [CONTEXT_COURSECAT, 0]);
        $hiddencontexts = [];
        foreach ($records as $record) {
            context_helper::preload_from_record($record);
            $context = context::instance_by_id($record->id);
            if (!has_capability('moodle/category:viewhiddencategories', $context)) {
                $hiddencontexts[] = $record->id;
            }
        }
        return $hiddencontexts;
    }

    /**
     * Returns parent file_info instance
     *
     * @return file_info|null file_info instance or null for root
     */
    public function get_parent() {
        return null;
    }
}
