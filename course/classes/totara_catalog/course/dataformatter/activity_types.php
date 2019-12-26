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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course\dataformatter;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;

class activity_types extends formatter {

    /**
     * @var string
     */
    private $sourcedelimiter;

    /**
     * @var string
     */
    private $resultdelimiter;

    /**
     * @param string $modulesfield the database field containing the array of module ids, comma separated (use $DB->group_concat)
     * @param string $sourcedelimiter
     * @param string $resultdelimiter
     */
    public function __construct(
        string $modulesfield,
        string $sourcedelimiter = ',',
        string $resultdelimiter =', '
    ) {
        $this->add_required_field('modules', $modulesfield);
        $this->sourcedelimiter = $sourcedelimiter;
        $this->resultdelimiter = $resultdelimiter;
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_TEXT,
            formatter::TYPE_FTS,
        ];
    }

    /**
     * Given a list of modules, gets the activity type names.
     *
     * @param array $data
     * @param \context $context
     * @return string
     */
    public function get_formatted_value(array $data, \context $context): string {
        if (!array_key_exists('modules', $data)) {
            throw new \coding_exception("Course activity types data formatter expects 'modules'");
        }

        $modules = explode($this->sourcedelimiter, $data['modules']);
        $modules = array_map('trim', $modules);
        $mods = array();

        foreach ($modules as $mod) {
            if (empty($mod)) {
                continue;
            }

            if (get_string_manager()->string_exists('pluginname', $mod)) {
                $mods[] = get_string('pluginname', $mod);
            } else {
                $mods[] = ucfirst($mod);
            }
        }

        // Sort module list before displaying to make cells all consistent.
        sort($mods);

        return implode($this->resultdelimiter, $mods);
    }
}
