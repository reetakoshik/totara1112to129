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

namespace totara_catalog\local;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataformatter\static_text;
use totara_catalog\dataholder;

class learning_type_dataholders {

    /**
     * @param string $name
     * @return dataholder[]
     */
    public static function create(string $name): array {
        return [
            new dataholder(
                'catalog_learning_type',
                new \lang_string('learning_type', 'totara_catalog'),
                [
                    formatter::TYPE_PLACEHOLDER_TEXT => new static_text(
                        $name
                    ),
                ]
            )
        ];
    }
}
