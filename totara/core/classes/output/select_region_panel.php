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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\output;

defined('MOODLE_INTERNAL') || die();

class select_region_panel extends select_region {

    /**
     * Create a panel region template, containing the given selectors, and with (optional) active count and clear button.
     *
     * @param string $title
     * @param select[] $selectors
     * @param bool $displayactivecount false to prevent the active count from being displayed
     * @param bool $displaycleartrigger false to prevent the clear button from being displayed
     * @param bool $hideonmobile true to cause the region to appear hidden with a "show" button on mobile devices
     * @return select_region_panel
     */
    public static function create(
        string $title,
        array $selectors,
        bool $displayactivecount = true,
        bool $displaycleartrigger = true,
        bool $hideonmobile = false
    ) : select_region_panel {
        $data = parent::get_base_template_data($selectors);

        $data->title = $title;
        $data->display_active_count = $displayactivecount;
        $data->display_clear_trigger = $displaycleartrigger;
        $data->hide_on_mobile = $hideonmobile;

        return new static((array)$data);
    }
}