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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 *
 * @package block_course_navigation
 */

final class block_course_navigation extends block_base {

    /** @var int This allows for multiple navigation trees */
    public static $navcount;

    /** @var string The name of the block */
    private const BLOCKNAME = 'block_course_navigation';

    /** @var int Trim characters from the right */
    const TRIM_RIGHT = 1;
    /** @var int Trim characters from the left */
    const TRIM_LEFT = 2;
    /** @var int Trim characters from the center */
    const TRIM_CENTER = 3;

    /**
     * Set the initial properties for the block
     */
    public function init() {
        $this->title = get_string('pluginname', self::BLOCKNAME);
    }

    /**
     * Makes sure the plugin name is set initially
     */
    public function specialization() {
        if (SITEID == $this->page->course->id) {
            $this->title = get_string('frontpage', 'admin');
        } else {
            $coursecontext = context_course::instance($this->page->course->id);
            $this->title = format_string($this->page->course->fullname, true, array('context' => $coursecontext));
        }
    }

    /**
     * Allow multiple instances of this block
     * @return bool Returns false
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Set the applicable formats for this block.
     * @return array
     */
    public function applicable_formats() {
        return ['site' => true, 'course' => true, 'mod' => true];
    }

    /**
     * Allow the user to configure a block instance
     * @return bool Returns true
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * The navigation block cannot be hidden by default as it is integral to
     * the navigation of course sections.
     *
     * @return false
     */
    public function instance_can_be_hidden() {
        return false;
    }

    /**
     * Gets Javascript that may be required for navigation
     */
    public function get_required_javascript() {
        parent::get_required_javascript();
        $arguments = array(
            'instanceid' => $this->instance->id
        );
        $this->page->requires->js_call_amd('block_course_navigation/navblock', 'init', $arguments);
    }

    /**
     * Gets the content for this block by grabbing it from $this->page
     *
     * @return stdClass $this->content
     */
    public function get_content() {
        // First check if we have already generated, don't waste cycles
        if ($this->content !== null) {
            return $this->content;
        }
        // Navcount is used to allow us to have multiple trees although I dont' know why
        // you would want two trees the same
        block_course_navigation::$navcount++;

        $trimmode = self::TRIM_RIGHT;
        $trimlength = 50;

        if (!empty($this->config->trimmode)) {
            $trimmode = (int)$this->config->trimmode;
        }

        if (!empty($this->config->trimlength)) {
            $trimlength = (int)$this->config->trimlength;
        }

        // Get the navigation object or don't display the block if none provided.
        if (!$navigation = $this->get_navigation()) {
            return null;
        }

        $this->trim($navigation, $trimmode, $trimlength, ceil($trimlength/2));

        // Get the expandable items so we can pass them to JS
        $expandable = array();
        $navigation->find_expandable($expandable);

        // Grab the items to display
        $renderer = $this->page->get_renderer(self::BLOCKNAME);
        $this->content = new stdClass();
        $this->content->text = $renderer->course_navigation($navigation->children);

        return $this->content;
    }

    /**
     * Returns the navigation
     *
     * @return navigation_node The navigation object to display
     */
    protected function get_navigation() {
        // Initialise (only actually happens if it hasn't already been done yet)
        $this->page->navigation->initialise();
        return $this->page->navigation->find($this->page->course->id, navigation_node::TYPE_COURSE);
    }

    /**
     * Trims the text and shorttext properties of this node and optionally
     * all of its children.
     *
     * @param navigation_node $node
     * @param int             $mode    One of navigation_node::TRIM_*
     * @param int             $long    The length to trim text to
     * @param int             $short   The length to trim shorttext to
     * @param bool            $recurse Recurse all children
     */
    public function trim(navigation_node $node, $mode = 1, $long = 50, $short = 25, $recurse = true) {
        switch ($mode) {
            case self::TRIM_RIGHT :
                if (core_text::strlen($node->text) > ($long + 3)) {
                    // Truncate the text to $long characters
                    $node->text = $this->trim_right($node->text, $long);
                }
                if (is_string($node->shorttext) && core_text::strlen($node->shorttext) > ($short + 3)) {
                    // Truncate the shorttext
                    $node->shorttext = $this->trim_right($node->shorttext, $short);
                }
                break;
            case self::TRIM_LEFT :
                if (core_text::strlen($node->text) > ($long + 3)) {
                    // Truncate the text to $long characters
                    $node->text = $this->trim_left($node->text, $long);
                }
                if (is_string($node->shorttext) && core_text::strlen($node->shorttext) > ($short + 3)) {
                    // Truncate the shorttext
                    $node->shorttext = $this->trim_left($node->shorttext, $short);
                }
                break;
            case self::TRIM_CENTER :
                if (core_text::strlen($node->text) > ($long + 3)) {
                    // Truncate the text to $long characters
                    $node->text = $this->trim_center($node->text, $long);
                }
                if (is_string($node->shorttext) && core_text::strlen($node->shorttext) > ($short + 3)) {
                    // Truncate the shorttext
                    $node->shorttext = $this->trim_center($node->shorttext, $short);
                }
                break;
        }
        if ($recurse && $node->children->count()) {
            foreach ($node->children as &$child) {
                $this->trim($child, $mode, $long, $short, true);
            }
        }
    }

    /**
     * Truncate a string from the left
     *
     * @param string $string The string to truncate
     * @param int    $length The length to truncate to
     *
     * @return string The truncated string
     */
    protected function trim_left($string, $length) {
        return '...' . core_text::substr($string, core_text::strlen($string) - $length, $length);
    }

    /**
     * Truncate a string from the right
     *
     * @param string $string The string to truncate
     * @param int    $length The length to truncate to
     *
     * @return string The truncated string
     */
    protected function trim_right($string, $length) {
        return core_text::substr($string, 0, $length) . '...';
    }

    /**
     * Truncate a string in the center
     *
     * @param string $string The string to truncate
     * @param int    $length The length to truncate to
     *
     * @return string The truncated string
     */
    protected function trim_center($string, $length) {
        $trimlength = ceil($length / 2);
        $start = core_text::substr($string, 0, $trimlength);
        $end = core_text::substr($string, core_text::strlen($string) - $trimlength);
        $string = $start . '...' . $end;

        return $string;
    }

    /**
     * Returns the role that best describes the navigation block... 'navigation'
     *
     * @return string 'navigation'
     */
    public function get_aria_role() {
        return 'navigation';
    }
}
