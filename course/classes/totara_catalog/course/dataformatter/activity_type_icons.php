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

class activity_type_icons extends formatter {
    /**
     * @var string
     */
    private $sourcedelimiter;

    /**
     * @param string $modulesfield the database field containing the array of module ids, comma separated (use $DB->group_concat)
     * @param string $sourcedelimiter
     */
    public function __construct(
        string $modulesfield,
        string $sourcedelimiter = ','
    ) {
        $this->add_required_field('modules', $modulesfield);
        $this->sourcedelimiter = $sourcedelimiter;
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_ICONS,
        ];
    }

    /**
     * Given a list of modules, gets the activity type icons.
     *
     * @param array $data
     * @param \context $context
     * @return \stdClass[]
     */
    public function get_formatted_value(array $data, \context $context): array {
        global $CFG, $OUTPUT;

        if (!array_key_exists('modules', $data)) {
            throw new \coding_exception("Course activity type icons data formatter expects 'modules'");
        }

        $modules = explode($this->sourcedelimiter, $data['modules']);
        $modules = array_map('trim', $modules);
        $mods = array();

        foreach ($modules as $mod) {
            if (empty($mod)) {
                continue;
            }
            $module = new \stdClass();
            $module->name = $mod;
            if (get_string_manager()->string_exists('pluginname', $mod)) {
                $module->localname = get_string('pluginname', $mod);
            } else {
                $module->localname = ucfirst($mod);
            }
            $mods[] = $module;
        }

        // Sort module list before displaying to make cells all consistent.
        \core_collator::asort_objects_by_property($mods, 'localname');

        $icons = array();

        foreach ($mods as $module) {
            if (file_exists($CFG->dirroot . '/mod/' . $module->name . '/pix/icon.gif') ||
                file_exists($CFG->dirroot . '/mod/' . $module->name . '/pix/icon.png')) {
                $icon = new \stdClass();
                $icon->icon = $OUTPUT->pix_icon('icon', $module->localname, $module->name);

                $icons[] = $icon;
            }
        }

        return $icons;
    }
}
