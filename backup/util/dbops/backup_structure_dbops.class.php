<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    moodlecore
 * @subpackage backup-dbops
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Non instantiable helper class providing DB support to the backup_structure stuff
 *
 * This class contains various static methods available for all the DB operations
 * performed by the backup_structure stuff (mainly @backup_nested_element class)
 *
 * TODO: Finish phpdocs
 */
abstract class backup_structure_dbops extends backup_dbops {

    public static function get_iterator($element, $params, $processor) {
        global $DB;

        // Check we are going to get_iterator for one backup_nested_element
        if (! $element instanceof backup_nested_element) {
            throw new base_element_struct_exception('backup_nested_element_expected');
        }

        // If var_array, table and sql are null, and element has no final elements it is one nested element without source
        // Just return one 1 element iterator without information
        if ($element->get_source_array() === null && $element->get_source_table() === null &&
            $element->get_source_sql() === null && count($element->get_final_elements()) == 0) {
            return new backup_array_iterator(array(0 => null));

        } else if ($element->get_source_array() !== null) { // It's one array, return array_iterator
            return new backup_array_iterator($element->get_source_array());

        } else if ($element->get_source_table() !== null) { // It's one table, return recordset iterator
            return $DB->get_recordset($element->get_source_table(), self::convert_params_to_values($params, $processor), $element->get_source_table_sortby());

        } else if ($element->get_source_sql() !== null) { // It's one sql, return recordset iterator
            return $DB->get_recordset_sql($element->get_source_sql(), self::convert_params_to_values($params, $processor));

        } else { // No sources, supress completely, using null iterator
            return new backup_null_iterator();
        }
    }

    public static function convert_params_to_values($params, $processor) {
        $newparams = array();
        foreach ($params as $key => $param) {
            $newvalue = null;
            // If we have a base element, get its current value, exception if not set
            if ($param instanceof base_atom) {
                if ($param->is_set()) {
                    $newvalue = $param->get_value();
                } else {
                    throw new base_element_struct_exception('valueofparamelementnotset', $param->get_name());
                }

            } else if ($param < 0) { // Possibly one processor variable, let's process it
                // See @backup class for all the VAR_XXX variables available.
                // Note1: backup::VAR_PARENTID is handled by nested elements themselves
                // Note2: trying to use one non-existing var will throw exception
                $newvalue = $processor->get_var($param);

            // Else we have one raw param value, use it
            } else {
                $newvalue = $param;
            }

            $newparams[$key] = $newvalue;
        }
        return $newparams;
    }

    public static function insert_backup_ids_record($backupid, $itemname, $itemid) {
        global $DB;
        // We need to do some magic with scales (that are stored in negative way)
        if ($itemname == 'scale') {
            $itemid = -($itemid);
        }
        // Now, we skip any annotation with negatives/zero/nulls, ids table only stores true id (always > 0)
        if ($itemid <= 0 || is_null($itemid)) {
            return;
        }
        // TODO: Analyze if some static (and limited) cache by the 3 params could save us a bunch of record_exists() calls
        // Note: Sure it will!
        if (!$DB->record_exists('backup_ids_temp', array('backupid' => $backupid, 'itemname' => $itemname, 'itemid' => $itemid))) {
            $DB->insert_record('backup_ids_temp', array('backupid' => $backupid, 'itemname' => $itemname, 'itemid' => $itemid));
        }
    }

    /**
     * Adds backup id database record for all files in the given file area.
     *
     * @param string $backupid Backup ID
     * @param int $contextid Context id
     * @param string $component Component
     * @param string $filearea File area
     * @param int $itemid Item id
     * @param \core\progress\base $progress
     */
    public static function annotate_files($backupid, $contextid, $component, $filearea, $itemid,
            \core\progress\base $progress = null) {
        global $DB;

        $conditions = array(
            'contextid' => $contextid,
            'component' => $component
        );
        if (!is_null($filearea)) {
            // Add filearea to query and params if necessary.
            $conditions['filearea'] = $filearea;
        }
        if (!is_null($itemid)) {
            // Add itemid to query and params if necessary.
            $conditions['itemid'] = $itemid;
        }
        if ($progress) {
            $progress->start_progress('');
        }

        $from = 0;
        $limit = $DB->get_max_in_params();
        while ($ids = $DB->get_records_menu('files', $conditions, 'id DESC', 'id, 1', $from, $limit)) {
            $count = 0; // Do not use count($ids) here because PHP counts on big arrays may be very slow.
            foreach ($ids as $id => $unused) {
                $count++;
                if ($progress) {
                    $progress->progress();
                }
                self::insert_backup_ids_record($backupid, 'file', $id);
            }

            if ($count < $limit) {
                // The next get_records_menu() is not going to return anything.
                break;
            }

            $from += $limit;
        }

        if ($progress) {
            $progress->end_progress();
        }
    }

    /**
     * Moves all the existing 'item' annotations to their final 'itemfinal' ones
     * for a given backup.
     *
     * @param string $backupid Backup ID
     * @param string $itemname Item name
     * @param \core\progress\base $progress Progress tracker
     */
    public static function move_annotations_to_final($backupid, $itemname, \core\progress\base $progress = null) {
        global $DB;

        if ($progress) {
            $progress->start_progress('move_annotations_to_final');
        }

        // There is a unique key on backupid + itemname + itemid.
        // However appending "final" to the itemname can occur outside of this function, such as if
        // some smart plugin knows best.
        // In situations where there is a "final" version and a non final version we need to delete the non-final version.
        // Database capabilities really affect how we do this, for instance
        // MySQL cannot open the same temp table more than once in a query.
        // This includes both select statements and delete statements.
        // Annoying! but luckily there is a pattern to this madness.
        // As we expect this to be a rarity we are going to just read out and then delete in batches.
        // We are within a transaction here so we won't use a recordset.
        // Remember though, we don't actually expect any duplicates, we expect to read once and then be done.
        // So while this wouldn't perform well if there were lots of duplicates its going to be lightning fast
        // because there are none!

        $sql = 'SELECT b.itemid AS xid, b.itemid
                  FROM {backup_ids_temp} b
                 WHERE b.backupid = :backupid AND
                       (b.itemname = :itemname OR b.itemname = :itemnamefinal)
              GROUP BY b.itemid
                HAVING COUNT(b.id) > 1';
        $params = array(
            'backupid' => $backupid,
            'itemname' => $itemname,
            'itemnamefinal' => $itemname . 'final',
        );
        $limit = $DB->get_max_in_params();
        while ($itemids = $DB->get_records_sql_menu($sql, $params, 0, $limit)) {
            // Watch out, something else knows better how to get the final values.
            list($deletein, $deleteparams) = $DB->get_in_or_equal($itemids, SQL_PARAMS_NAMED, 'd');
            $deleteselect = 'itemname = :itemname AND backupid = :backupid AND itemid ' . $deletein;
            $deleteparams['itemname'] = $itemname;
            $deleteparams['backupid'] = $backupid;
            $DB->delete_records_select('backup_ids_temp', $deleteselect, $deleteparams);
            // Do not worry about extra DB query to get out of the while loop, we are not likely to get here.
        }

        if ($progress) {
            $progress->progress();
        }

        // Next we need to append final to all itemnames.
        $params = array('itemname' => $itemname, 'backupid' => $backupid);
        $DB->set_field('backup_ids_temp', 'itemname', $itemname . 'final', $params);

        // Step 2 is complete.
        if ($progress) {
            $progress->end_progress();
        }
    }

    /**
     * Returns true/false if there are annotations for a given item
     */
    public static function annotations_exist($backupid, $itemname) {
        global $DB;
        return (bool)$DB->count_records('backup_ids_temp', array('backupid' => $backupid, 'itemname' => $itemname));
    }
}
