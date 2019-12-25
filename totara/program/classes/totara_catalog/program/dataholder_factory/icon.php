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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_program
 * @category totara_catalog
 */

namespace totara_program\totara_catalog\program\dataholder_factory;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataformatter\totara_icon as totara_icon_dataformatter;
use totara_catalog\dataformatter\totara_icons as totara_icons_dataformatter;
use totara_catalog\dataholder;
use totara_catalog\dataholder_factory;

class icon extends dataholder_factory {

    public static function get_dataholders(): array {
        global $CFG;
        require_once($CFG->dirroot.'/totara/core/utils.php');

        return [
            new dataholder(
                'icon',
                new \lang_string('programicon', 'totara_program'),
                [
                    formatter::TYPE_PLACEHOLDER_ICON => new totara_icon_dataformatter(
                        'base.id',
                        'base.fullname',
                        TOTARA_ICON_TYPE_PROGRAM
                    ),
                    formatter::TYPE_PLACEHOLDER_ICONS => new totara_icons_dataformatter(
                        'base.id',
                        'base.fullname',
                        TOTARA_ICON_TYPE_PROGRAM
                    ),
                ]
            )
        ];
    }
}
