<?php
/**
 * This file is part of Totara LMS
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

use block_totara_featured_links\tile\base;

defined('MOODLE_INTERNAL') || die();

/**
 * block_totara_featured_links_upgrade_set_default_heading_location
 * Sets the default heading location on gallery tiles that do not have a heading_location
 */
function btfl_upgrade_set_default_heading_location() {
    global $DB;
    $sql = 'SELECT *
              FROM {block_totara_featured_links_tiles} btflt
             WHERE ' . $DB->sql_compare_text('type', 100) . '=\'block_totara_featured_links-gallery_tile\'
                OR ' . $DB->sql_compare_text('type', 100) . '=\'block_totara_featured_links-program_tile\'
                OR ' . $DB->sql_compare_text('type', 100) . '=\'block_totara_featured_links-certification_tile\'';
    $tiles = $DB->get_records_sql($sql);
    foreach ($tiles as $tilerow) {
        $tileinstance = base::get_tile_instance($tilerow);
        if (!isset($tileinstance->data->heading_location)) {
            $tileinstance->data->heading_location = base::HEADING_TOP;
            $tileinstance->save_content($tileinstance->data);
        }
    }
}