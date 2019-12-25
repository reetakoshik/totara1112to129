<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

namespace block_totara_featured_links\tile;

class course_tile extends learning_item_tile {
    protected $used_fields = [
        'courseid',         // int The id of the course that the tile links to.
        'background_color', // string The hex value of the background color.
        'heading_location', // string Where the heading is located 'top' or 'bottom'.
        'progressbar'       // int 1 or 0 whether to show the progress bar.
    ];
    /** @var string Class for the visibility form */
    protected $visibility_form = '\block_totara_featured_links\tile\course_form_visibility';
    /** @var string Class for the visibility form*/
    protected $content_form = '\block_totara_featured_links\tile\course_form_content';
    /** @var string The classes that get added to the content of the tile */
    protected $content_class = 'block-totara-featured-links-course';

    /**
     * @var \stdClass|false $course the database row of the course
     *
     * Call $this->get_course() to load this property.
     */
    protected $course = null;

    /**
     * returns the name of the tile that will be displayed in the edit content form
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('course_name', 'block_totara_featured_links');
    }

    /**
     * Gets the data for the learning item content form and adds the
     * course name and id.
     *
     * {@inheritdoc}
     * @return \stdClass
     */
    public function get_content_form_data(): \stdClass {
        $dataobj = parent::get_content_form_data();
        if (!empty($this->get_course())) {
            $dataobj->course_name = $this->get_course()->fullname;
        }
        if (isset($this->data_filtered->courseid)) {
            $dataobj->course_name_id = $this->data_filtered->courseid;
        }
        if (!isset($this->data->heading_location)) {
            $dataobj->heading_location = self::HEADING_TOP;
        }
        return $dataobj;
    }

    /**
     * Adds heading to the content data for a learning item tile.
     *
     * {@inheritdoc}
     * @return array
     */
    protected function get_content_template_data(): array {
        global $USER, $DB;
        if (empty($this->get_course())) {
            return [];
        }
        if (!$status = $DB->get_field('course_completions', 'status', array('userid' => $USER->id, 'course' => $this->data->courseid))) {
            $status = null;
        }
        if (isset($this->data->progressbar) && $this->data->progressbar == '1') {
            $progressbar = totara_display_course_progress_bar($USER->id, $this->data->courseid, $status);
        } else {
            $progressbar = false;
        }

        $data = parent::get_content_template_data();
        $data['heading'] = format_string($this->get_course()->fullname);
        $data['progress_bar'] = $progressbar;

        return $data;
    }

    /**
     * Gets the data for the content_wrapper template from {@learning_item}
     * and add the url to the course if the course can be retrieved.
     *
     * @param \renderer_base $renderer
     * @param array $settings
     * @return array
     */
    protected function get_content_wrapper_template_data(\renderer_base $renderer, array $settings = []): array {
        global $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        $data = parent::get_content_wrapper_template_data($renderer, $settings);
        if (!empty($this->get_course())) {
            $course = $this->get_course();
            $data['url'] = $CFG->wwwroot.'/course/view.php?id='.$course->id;
            $data['background_img'] = false;

            // Get course tile image to use it as background.
            $image = course_get_image($course);
            $data['background_img'] = $image->out();
        }

        return $data;
    }

    /**
     * Sets the course id into the data property
     *
     * @param \stdClass $data
     */
    public function save_content_tile($data): void {
        if (isset($data->course_name_id)) {
            $this->data->courseid = $data->course_name_id;
        }
        parent::save_content_tile($data);
    }

    /**
     * Returns true if the user is allowed to view the content of this tile.
     *
     * This gives custom tile types a way of removing the tile if the user does not have permission to view the content of the tile.
     * If this returns true then the standard visibility checks are made by {@link self::is_visible()}.
     * If this returns false then the user is deemed to not be allowed to see the content of the tile, and consequently
     * other visibility checks are not made, the user is simply not checked.
     *
     * @return bool
     */
    protected function user_can_view_content(): bool {
        return boolval($this->get_course());
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function get_accessibility_text(): array {
        return ['sr-only' => get_string('course_sr-only', 'block_totara_featured_links', !empty($this->course->fullname) ? $this->course->fullname : '')];
    }

    /**
     * Returns the course this tile is associated with.
     *
     * @param boolean $reload if the course is to be reloaded
     * @return \stdClass|bool The course record or false if there is no associated course.
     */
    public function get_course($reload = false) {
        global $DB;

        if (empty($this->data->courseid)) {
            return false;
        }
        if (!$DB->record_exists('course', ['id' => $this->data->courseid])) {
            return false;
        }

        if (!isset($this->course) or $reload) {
            if (totara_course_is_viewable($this->data->courseid)) {
                $this->course = $DB->get_record('course', ['id' => $this->data->courseid]);
            } else {
                $this->course = false;
            }
        }
        return $this->course;
    }

    /**
     * {@inheritdoc}
     *
     * We'll return that the course was deleted if that is the case.
     *
     * @return string of text shown if a tile is hidden but being viewed in edit mode.
     */
    protected function get_hidden_text(): string {
        if (empty($this->get_course())) {
            return get_string('course_has_been_deleted', 'block_totara_featured_links');
        } else {
            return parent::get_hidden_text();
        }
    }
}
