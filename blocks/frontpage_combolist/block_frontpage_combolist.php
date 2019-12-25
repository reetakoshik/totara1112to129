<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package block_frontpage_combolist
 */

/**
 * Main block file
 *
 * @deprecated since Totara 12. See readme.txt.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Frontpage combolist block class.
 *
 * @deprecated since Totara 12. See readme.txt.
 * @property stdClass $config
 */
class block_frontpage_combolist extends block_base {

    const DISPLAY_ALL        = 'all';
    const DISPLAY_ITEMS      = 'items';
    const DISPLAY_CATEGORIES = 'categories';

    /**
     * Initialises this block instance.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_frontpage_combolist');
    }

    /**
     * Customise the block as needed.
     */
    public function specialization() {
        parent::specialization();
        $this->title = get_string('title_' . $this->get_display_mode(), 'block_frontpage_combolist');
    }

    /**
     * Returns the display mode the block is configured to use.
     *
     * @return string
     */
    private function get_display_mode(): string {
        if (isset($this->config->display) && !empty($this->config->display)) {
            return $this->config->display;
        }

        return 'items';
    }

    /**
     * Returns the maximum category depth.
     *
     * @return int
     */
    private function get_max_category_depth(): int {
        if (!empty($this->config->maxcategorydepth)) {
            return (int)$this->config->maxcategorydepth;
        }

        return 2;
    }

    /**
     * Returns the item limit for this block.
     *
     * @return int
     */
    private function get_item_limit(): int {
        if (!empty($this->config->itemlimit)) {
            return (int)$this->config->itemlimit;
        }

        return 200;
    }

    /**
     * Where can this block be used.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['site-index' => true];
    }

    /**
     * Allow multiple instance of this block.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Returns the content for the block.
     *
     * @return stdClass
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $method = 'display_all';
        switch ($this->get_display_mode()) {
            case self::DISPLAY_CATEGORIES:
                $method = 'display_categories';
                break;
            case self::DISPLAY_ITEMS:
                $method = 'display_items';
                break;
        }
        $this->content->text = call_user_func([$this, $method]);

        return $this->content;
    }

    /**
     * Generates HTML to display the block when it is configured to show all.
     *
     * This is copied from {@link core_course_renderer::frontpage_combo_list()}
     *
     * @return string
     */
    private function display_all(): string {
        $output = $this->page->get_renderer('block_frontpage_combolist');

        $chelper = new coursecat_helper();
        $chelper->set_subcat_depth($this->get_max_category_depth())
            ->set_categories_display_options(
                [
                    'limit'       => $this->get_item_limit(),
                    'viewmoreurl' => new moodle_url('/course/index.php', ['browse' => 'categories', 'page' => 1]),
                ]
            )
            ->set_courses_display_options(
                [
                    'limit'       => $this->get_item_limit(),
                    'viewmoreurl' => new moodle_url('/course/index.php', ['browse' => 'courses', 'page' => 1]),
                ]
            )
            ->set_attributes(['class' => 'frontpage-category-combo']);

        return $output->categories($chelper, coursecat::get(0));
    }

    /**
     * Generates HTML to display the block when it is configured to show just items.
     *
     * This is copied from {@link core_course_renderer::frontpage_available_courses()}
     *
     * @return string
     */
    private function display_items(): string {
        $output = $this->page->get_renderer('block_frontpage_combolist');

        $chelper = new coursecat_helper();
        $chelper->set_show_courses(core_course_renderer::COURSECAT_SHOW_COURSES_EXPANDED)
            ->set_courses_display_options(
                [
                    'recursive'    => true,
                    'limit'        => $this->get_item_limit(),
                    'viewmoreurl'  => new moodle_url('/course/index.php'),
                    'viewmoretext' => new lang_string('fulllistofcourses'),
                ]
            )
            ->set_attributes(['class' => 'frontpage-course-list-all']);
        $courses = coursecat::get(0)->get_courses($chelper->get_courses_display_options());
        $totalcount = coursecat::get(0)->get_courses_count($chelper->get_courses_display_options());

        return $output->learning_items($chelper, $courses, $totalcount);
    }

    /**
     * Generates HTML to display the block when it is configured to show just items.
     *
     * This is copied from {@link core_course_renderer::frontpage_categories_list()}
     *
     * @return string
     */
    private function display_categories(): string {
        $output = $this->page->get_renderer('block_frontpage_combolist');

        $chelper = new coursecat_helper();
        $chelper->set_subcat_depth($this->get_max_category_depth())
            ->set_show_courses(core_course_renderer::COURSECAT_SHOW_COURSES_COUNT)
            ->set_categories_display_options(
                [
                    'limit'       => $this->get_item_limit(),
                    'viewmoreurl' => new moodle_url('/course/index.php', ['browse' => 'categories', 'page' => 1]),
                ]
            )
            ->set_attributes(['class' => 'frontpage-category-names']);

        return $output->categories($chelper, coursecat::get(0));
    }

    /**
     * Can you configure this block?
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }
}
