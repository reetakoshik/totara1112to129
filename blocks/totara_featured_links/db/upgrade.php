<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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

defined('MOODLE_INTERNAL') || die();

use block_totara_featured_links\tile\base;

/**
 * @param $oldversion
 * @param $block
 * @return bool
 */
function xmldb_block_totara_featured_links_upgrade($oldversion, $block) {
    global $DB;
    require_once(__DIR__ .'/upgradelib.php');

    $dbman = $DB->get_manager();

    if ($oldversion < 2017111600) {
        $sql = "SELECT {$DB->sql_length('btfl.type')} length, type
                  FROM {block_totara_featured_links_tiles} btfl
              ORDER BY {$DB->sql_length('btfl.type')} desc";
        $longest = $DB->get_record_sql($sql, null, IGNORE_MULTIPLE);

        if ($longest && $longest->length > 100) {
            throw new upgrade_exception('block_totara_featured_links',
                2017111600,
                "The type \"{$longest->type}\" is longer than 100 characters. Please shorten the class name on the tile type to be smaller");
        }

        $table = new xmldb_table('block_totara_featured_links_tiles');
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $dbman->change_field_type($table, $field);

        upgrade_plugin_savepoint(true, 2017111600, 'block', 'totara_featured_links');
    }

    // Upgrade from T11 or less
    if ($oldversion < 2018032600) {
        // Move the existing gallery tiles into a gorup of static tiles with the same parent id

        // Define field parentid to be added to block_totara_featured_links_tiles.
        $table = new xmldb_table('block_totara_featured_links_tiles');
        $field = new xmldb_field('parentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'tilerulesshowing');

        // Conditionally launch add field parentid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key parentid (foreign) to be added to block_totara_featured_links_tiles.
        $table = new xmldb_table('block_totara_featured_links_tiles');
        $key = new xmldb_key('parentid', XMLDB_KEY_FOREIGN, array('parentid'), 'block_totara_featured_links', array('id'));

        // Add key parentid.
        $dbman->add_key($table, $key);

        // Update the existing database.
        split_gallery_tiles_into_subtiles();

        upgrade_block_savepoint(true, 2018032600, 'totara_featured_links');
    }

    // Remove orphaned cohort_visibility records product of deleting featured link blocks.
    if ($oldversion < 2018061200) {

        $sql = "SELECT cv.id
                FROM {cohort_visibility} cv
                WHERE instancetype = :instancetype
                  AND NOT EXISTS (
                      SELECT 1 
                      FROM {block_totara_featured_links_tiles}
                      WHERE id = cv.instanceid
                  )";
        $params = array('instancetype' => COHORT_ASSN_ITEMTYPE_FEATURED_LINKS);
        $orphaned = $DB->get_records_sql($sql, $params);
        if (!empty($orphaned)) {
            $DB->delete_records_list('cohort_visibility', 'id', array_keys($orphaned));
        }

        upgrade_block_savepoint(true, 2018061200, 'totara_featured_links');
    }

    if ($oldversion < 2018062000) {
        // Fix the default values not being set on gallery, program and certification tiles.
        btfl_upgrade_set_default_heading_location();

        upgrade_block_savepoint(true, 2018062000, 'totara_featured_links');
    }

    // Change parentid field to Not Null in case someone is having Null values in there (MSSQl).
    if ($oldversion < 2018091400) {

        $sql = "UPDATE {block_totara_featured_links_tiles} SET parentid = 0 WHERE parentid IS NULL";
        $DB->execute($sql);

        // Define field parentid to be added to block_totara_featured_links_tiles.
        $table = new xmldb_table('block_totara_featured_links_tiles');
        $field = new xmldb_field('parentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'tilerulesshowing');

        if ($dbman->field_exists($table, $field)) {
            // Drop key parentid.
            $key = new xmldb_key('parentid', XMLDB_KEY_FOREIGN, array('parentid'), 'block_totara_featured_links', array('id'));
            $dbman->drop_key($table, $key);

            // Change field to not null.
            $dbman->change_field_notnull($table, $field);

            // Define key parentid (foreign) to be added to block_totara_featured_links_tiles.
            $dbman->add_key($table, $key);
        }

        upgrade_block_savepoint(true, 2018091400, 'totara_featured_links');
    }

    return true;
}
