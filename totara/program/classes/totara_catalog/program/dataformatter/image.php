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
 * @package totara_program
 * @category totara_catalog
 */

namespace totara_program\totara_catalog\program\dataformatter;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;

class image extends formatter {

    /**
     * @param string $programidfield the database field containing the program id
     * @param string $altfield the database field containing the image alt text
     */
    public function __construct(string $programidfield, string $altfield) {
        $this->add_required_field('programid', $programidfield);
        $this->add_required_field('alt', $altfield);
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_IMAGE,
        ];
    }

    /**
     * Given a program id, gets the image. Suitable for IMAGE.
     *
     * @param array $data
     * @param \context $context
     * @return \stdClass
     */
    public function get_formatted_value(array $data, \context $context): \stdClass {
        global $CFG;

        require_once($CFG->dirroot . '/totara/program/program.class.php');

        if (!array_key_exists('programid', $data)) {
            throw new \coding_exception("Program image data formatter expects 'programid'");
        }

        if (!array_key_exists('alt', $data)) {
            throw new \coding_exception("Program image data formatter expects 'alt'");
        }

        $prog = new \program($data['programid']);

        $image = new \stdClass();
        $image->url = $prog->get_image();
        $image->alt = format_string($data['alt'], true, ['context' => $context]);

        return $image;
    }
}
