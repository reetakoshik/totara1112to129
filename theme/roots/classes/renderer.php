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
 * @author    Joby Harding <joby.harding@totaralearning.com>
 * @package   theme_roots
 */

defined('MOODLE_INTERNAL') || die();

use \theme_roots\output\bootstrap_grid as grid;

class theme_roots_renderer extends plugin_renderer_base {

    /**
     * The grid used by this renderer.
     * @var \theme_roots\output\bootstrap_grid
     */
    private $grid;

    /**
     * Ensures that a grid has been created for this renderer.
     */
    private function ensure_grid_loaded() {
        if ($this->grid === null) {
            $this->grid = grid::initialise($this->page, $this);
        }
    }

    /**
     * Render site logo.
     *
     * @param \theme_roots\output\site_logo $sitelogo
     * @return string
     */
    public function render_site_logo(\theme_roots\output\site_logo $sitelogo) {
        global $OUTPUT;

        $context = $sitelogo->export_for_template($OUTPUT);

        return $this->render_from_template('theme_roots/site_logo', $context);
    }

    /**
     * Get the HTML for blocks in the given region.
     *
     * @param string $region The region to get HTML for.
     * @return string HTML.
     */
    public function blocks($region) {
        $this->ensure_grid_loaded();
        if (!$this->grid->show($region)) {
            return '';
        }
        $classes = $this->grid->classes($region);
        $tag = 'aside';
        $html = $this->output->blocks($region, $classes, $tag);

        if ($region === $this->grid::REGION_TOP) {
            $html = '<div id="region-top" class="row">' . $html . '</div>';
        } else if ($region === $this->grid::REGION_BOTTOM) {
            $html = '<div id="region-top" class="row">' . $html . '</div>';
        }

        return $html;
    }

    /**
     * Displays top region blocks, if they should be displayed.
     * @return string
     */
    public function blocks_top(): string {
        return $this->blocks(grid::REGION_TOP);
    }

    /**
     * Displays bottom region blocks, if they should be displayed.
     * @return string
     */
    public function blocks_bottom(): string {
        return $this->blocks(grid::REGION_BOTTOM);
    }

    /**
     * Displays side-pre region blocks, if they should be displayed.
     * @return string
     */
    public function blocks_pre(): string {
        return $this->blocks(grid::REGION_PRE);
    }

    /**
     * Displays side-post region blocks, if they should be displayed.
     * @return string
     */
    public function blocks_post(): string {
        return $this->blocks(grid::REGION_POST);
    }

    /**
     * Displays main region blocks, if they should be displayed.
     * @return string
     */
    public function blocks_main(): string {
        return $this->blocks(grid::REGION_MAIN);
    }

    /**
     * Returns CSS classes to add to the main content region.
     * @return string
     */
    public function main_content_classes(): string {
        $this->ensure_grid_loaded();

        $classes = $this->grid->classes($this->grid::CONTENT);

        return $classes;
    }
}
