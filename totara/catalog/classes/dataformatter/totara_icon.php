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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\dataformatter;

defined('MOODLE_INTERNAL') || die();

class totara_icon extends formatter {

    /** @var string */
    private $icontype;

    /**
     * @param string $idfield the database field containing the icon id
     * @param string $altfield the database field containing the image alt text
     * @param string $icontype the type of icon, either 'course' or 'prog'
     */
    public function __construct(
        string $idfield,
        string $altfield,
        string $icontype
    ) {
        $this->add_required_field('id', $idfield);
        $this->add_required_field('alt', $altfield);

        $this->icontype = $icontype;
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_ICON,
        ];
    }

    /**
     * Totara icon data formatter.
     *
     * Expects $data to contain keys 'id' and 'icon_type'.
     * 'icon_type' should be one of TOTARA_ICON_TYPE_COURSE or TOTARA_ICON_TYPE_PROGRAM.
     *
     * @param array $data
     * @param \context $context
     * @return \stdClass
     */
    public function get_formatted_value(array $data, \context $context) {
        global $CFG;

        require_once($CFG->dirroot . "/totara/core/utils.php");

        if (!array_key_exists('id', $data)) {
            throw new \coding_exception("Totara icon data formatter expects 'id'");
        }

        if (!array_key_exists('alt', $data)) {
            throw new \coding_exception("Totara icon data formatter expects 'alt'");
        }

        if (empty($data['id'])) {
            return null;
        }

        $icon = new \stdClass();
        $icon->url = totara_get_icon($data['id'], $this->icontype);
        $icon->alt = format_string($data['alt'], true, ['context' => $context]);

        return $icon;
    }
}
