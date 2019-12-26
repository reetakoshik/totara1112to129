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

/**
 * Format data so that it suitable for inclusion in the catalog FTS index.
 */
class fts extends formatter {

    /**
     * @param string $textfield the database field containing the text
     */
    public function __construct(string $textfield) {
        $this->add_required_field('text', $textfield);
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_FTS,
        ];
    }

    /**
     * Full text search data formatter.
     *
     * @param array $data
     * @param \context $context
     * @return string
     */
    public function get_formatted_value(array $data, \context $context): string {
        global $DB;

        $formattext = '';
        if (!array_key_exists('text', $data)) {
            throw new \coding_exception("Full text search data formatter expects 'text'");
        }
        if (!empty($data['text'])) {
            $formattext = $DB->unformat_fts_content($data['text'], FORMAT_HTML);
        }

        return $formattext;
    }
}
