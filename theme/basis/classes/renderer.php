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

defined('MOODLE_INTERNAL' || die());

class theme_basis_renderer extends theme_roots_renderer {

    /**
     * Implements standard 'convenience method' renderer pattern.
     */
    public function page_footer_nav($menudata) {
        $renderable = new theme_basis\output\page_footer_nav($menudata);
        return $this->render($renderable);
    }

    /**
     * Render method for page_footer_nav renderables.
     */
    protected function render_page_footer_nav($renderable) {
        $templatecontext = $renderable->export_for_template($this);
        return $this->render_from_template('theme_basis/page_footer_nav', $templatecontext);
    }

}
