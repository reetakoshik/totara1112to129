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
 * @package totara_catalog
 */

namespace totara_catalog\merge_select;

use totara_core\output\select_search_text;

defined('MOODLE_INTERNAL') || die();

class search_text extends merge_select {

    /** @var string */
    protected $showplaceholder = true;

    /**
     * Hide the placeholder text, which is displayed inside the search box before anything has been entered.
     *
     * @param bool $hideplaceholder
     */
    public function set_placeholder_hidden(bool $hideplaceholder = true) {
        $this->showplaceholder = !$hideplaceholder;
    }

    public function can_merge(merge_select $otherselector) {
        if (!parent::can_merge($otherselector)) {
            return false;
        }

        /** @var search_text $otherselector */
        if ($this->showplaceholder != $otherselector->showplaceholder) {
            return false;
        }

        return true;
    }

    public function get_template() {
        return select_search_text::create(
            $this->key,
            $this->title,
            $this->titlehidden,
            $this->get_data(),
            $this->showplaceholder
        );
    }
}