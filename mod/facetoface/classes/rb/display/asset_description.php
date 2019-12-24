<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <pter.skoda@totaralms.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\rb\display;

/**
 * Display asset description with images
 *
 * @package mod_facetoface
 */
class asset_description extends \totara_reportbuilder\rb\display\base {
    public static function display($description, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        $isexport = ($format !== 'html');
        $extra = self::get_extrafields_row($row, $column);
        $descriptionhtml = file_rewrite_pluginfile_urls(
            $description,
            'pluginfile.php',
            \context_system::instance()->id,
            'mod_facetoface',
            'asset',
            $extra->assetid
        );
        $descriptionhtml = format_text($descriptionhtml, FORMAT_HTML);

        if ($isexport) {
            $displaytext = html_to_text($descriptionhtml, 0, false);
            $displaytext = \core_text::entities_to_utf8($displaytext);
            return $displaytext;
        }

        return $descriptionhtml;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
