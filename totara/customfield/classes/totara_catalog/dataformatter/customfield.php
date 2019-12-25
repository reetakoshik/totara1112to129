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
 * @package totara_customfield
 * @category totara_catalog
 */

namespace totara_customfield\totara_catalog\dataformatter;

defined('MOODLE_INTERNAL') || die();

use customfield_base;
use totara_catalog\dataformatter\formatter;

class customfield extends formatter {

    /** @var string */
    private $fieldid;

    /** @var string */
    private $tableprefix;

    /** @var string */
    private $prefix;

    /**
     * @param string $fieldid the custom field id from <tableprefix>_info_field
     * @param string $idfield the database field containing the item id
     * @param string $tableprefix the prefix to use in front of '_info_data' and '_info_field'
     * @param string $prefix the prefix to use in front of 'id' as the column containing the item id
     */
    public function __construct(
        string $fieldid,
        string $idfield,
        string $tableprefix,
        string $prefix
    ) {
        $this->fieldid = $fieldid;
        $this->tableprefix = $tableprefix;
        $this->prefix = $prefix;
        $this->add_required_field('id', $idfield);
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_TEXT,
            formatter::TYPE_PLACEHOLDER_TITLE,
            formatter::TYPE_PLACEHOLDER_RICH_TEXT,
        ];
    }

    /**
     * Turn custom field data into a string for display.
     *
     * @param array $data
     * @param \context $context
     * @return string
     */
    public function get_formatted_value(array $data, \context $context): string {
        if (!array_key_exists('id', $data)) {
            throw new \coding_exception("customfield data formatter expects 'id'");
        }

        $item = new \stdClass();
        $item->id = $data['id'];

        /** @var customfield_base $field */
        $field = customfield_get_field_instance($item, $this->fieldid, $this->tableprefix, $this->prefix);

        return $field->display_data();
    }
}
