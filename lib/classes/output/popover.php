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
 * @copyright 2017 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Brian Barnes <brian.barnes@totaralearning.com>
 * @package   core_output
 */

namespace core\output;

defined('MOODLE_INTERNAL' || die());

/**
 * Popover class. Provides a popover UI component.
 *
 * @copyright 2017 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Brian Barnes <brian.barnes@totaralearning.com>
 * @package   core
 */
class popover implements \templatable {
    /**
     * @var string the template for inside the popover
     */
    private $template = null;

    /**
     * @var array context data for $template
     */
    private $templatecontext = null;

    /**
     * @var string Title for the popover
     */
    private $title = '';

    /**
     * @var string placement for the popover. This can be one of the following values
     *     * 'bottom' - Default location for the popover, displayed below the tirgger element
     *     * 'top' - displayed above the trigger element
     *     * 'left' - displayed to the left the trigger element
     *     * 'right' - displayed to the right the trigger element
     *     * 'smartPosition' - displayed where there is room on the page
     */
    public $arrow_placement = null;

    /**
     * @var int Expected max height of the popover - only used when arrow_placement is smartPosition.
     */
    public $max_height = null;

    /**
     * @var int Expected max width of the popover - only used when arrow_placement is smartPosition.
     */
    public $max_width = null;

    /**
     * @var string the trigger for the popover
     */
    public $trigger = '';

    /**
     * @var bool Whether to close the popover when a user clicks outside the popover
     */
    public $focus_out = true;

    /**
     * @var string Sanitised content for the popover.
     */
    private $text = '';

    private function __construct() {
    }

    /**
     * Creates a popover object from plain text. Where possible use create_from_template
     *
     * @param string $text Pre-formatted text, ready to be used. Please
     * ensure you pass this through s() or format_string/format_text
     * before creating a popover.
     * @param string $title Pre-formatted text, ready to be used. Please
     * ensure you pass this through s() or format_string/format_text
     * before creating a popover.
     * @return popover a popover object constructed from the supplied arguments
     */
    public static function create_from_text(string $text, string $title = null) {
        $pover = new popover();
        $pover->text = $text;
        $pover->title = is_null($title) ? '' : $title;
        return $pover;
    }

    /**
     * Create a popover object using a mustache template
     *
     * @param string $template
     * @param array $contextdata
     * @param string $title Pre-formatted text, ready to be used. Please
     * ensure you pass this through s() or format_string/format_text
     * before creating a popover.
     * @return popover a popover object constructed from the supplied arguments
     */
    public static function create_from_template(string $template, array $contextdata = array(), string $title = null) {
        $pover = new popover();
        $pover->template = $template;
        $pover->templatecontext = $contextdata;
        $pover->title = is_null($title) ? '' : $title;

        return $pover;
    }

    /**
     * Template for progress bar
     *
     * @return string The template name to use
     */
    public function get_template() {
        return 'core/popover';
    }

    /**
     * Exports data to be used in a mustache template
     *
     * @return array Data for the popover template
     */
    public function export_for_template(\renderer_base $output) {
        $returnval = array(
            'contenttemplate' => isset($this->template) ? $this->template : false,
            'contenttemplatecontext' => isset($this->templatecontext) ? $this->templatecontext : false,
            'title' => $this->title,
            'contentraw' => isset($this->text) ? $this->text : '',
            'arrow_placement' => $this->arrow_placement,
            'close_on_focus_out' => $this->focus_out,
            'placement_max_height' => $this->max_height,
            'max_height' => $this->max_height,
            'placement_max_width' => $this->max_width,
            'max_width' => $this->max_width,
            'trigger' => $this->trigger
        );

        return $returnval;
    }

}