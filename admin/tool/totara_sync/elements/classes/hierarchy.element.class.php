<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage totara_sync
 */

global $CFG;
require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/classes/element.class.php');
require_once($CFG->dirroot.'/totara/customfield/fieldlib.php');

abstract class totara_sync_hierarchy extends totara_sync_element {

    private $hierarchy;

    /**
     * Get hierarchy (implement in child class)
     *
     * @return stdClass the hierarchy object
     */
    abstract function get_hierarchy();

    function __construct() {
        parent::__construct();
        $this->hierarchy = $this->get_hierarchy();
    }

    function get_name() {
        return $this->hierarchy->shortprefix;
    }

    function has_config() {
        return true;
    }

    function config_form(&$mform) {
        $mform->addElement('selectyesno', 'sourceallrecords', get_string('sourceallrecords', 'tool_totara_sync'));
        $mform->setDefault('sourceallrecords', 0);
        $mform->addElement('static', 'sourceallrecordsdesc', '', get_string('sourceallrecordsdesc', 'tool_totara_sync'));

        // Empty CSV field setting.
        $emptyfieldopt = array(
            false => get_string('emptyfieldskeepdata', 'tool_totara_sync'),
            true => get_string('emptyfieldsremovedata', 'tool_totara_sync')
        );
        $mform->addElement('select', 'csvsaveemptyfields', get_string('emptyfieldsbehaviourhierarchy', 'tool_totara_sync'), $emptyfieldopt);
        $default = !empty($this->config->csvsaveemptyfields);
        $mform->setDefault('csvsaveemptyfields', $default);
        $mform->addHelpButton('csvsaveemptyfields', 'emptyfieldsbehaviourhierarchy', 'tool_totara_sync');

        $mform->addElement('header', 'crud', get_string('allowedactions', 'tool_totara_sync'));
        $mform->addElement('checkbox', 'allow_create', get_string('create', 'tool_totara_sync'));
        $mform->setDefault('allow_create', 1);
        $mform->addElement('checkbox', 'allow_update', get_string('update', 'tool_totara_sync'));
        $mform->setDefault('allow_update', 1);
        $mform->addElement('checkbox', 'allow_delete', get_string('delete', 'tool_totara_sync'));
        $mform->setDefault('allow_delete', 1);
    }

    function config_save($data) {
        $this->set_config('sourceallrecords', $data->sourceallrecords);

        // Only set this config is we get some data. Vaiid values are 0 and 1, but if set
        // to null it can be used to determine whether CSV or database sync is being used.
        if (isset($data->csvsaveemptyfields)) {
            $this->set_config('csvsaveemptyfields', !empty($data->csvsaveemptyfields));
        } else {
            $this->set_config('csvsaveemptyfields', null);
        }

        $this->set_config('allow_create', !empty($data->allow_create));
        $this->set_config('allow_update', !empty($data->allow_update));
        $this->set_config('allow_delete', !empty($data->allow_delete));

        $sourcename = 'source_' . $this->get_name();

        if (!empty($data->$sourcename)) {
            $source = $this->get_source($data->$sourcename);
            // Build link to source config.
            $url = new moodle_url('/admin/tool/totara_sync/admin/sourcesettings.php', array('element' => $this->get_name(), 'source' => $source->get_name()));
            if ($source->has_config()) {
                // Set import_deleted and warn if necessary.
                $import_deleted_new = ($data->sourceallrecords == 0) ? '1' : '0';
                $import_deleted_old = $source->get_config('import_deleted');
                if ($import_deleted_new != $import_deleted_old) {
                    $source->set_config('import_deleted', $import_deleted_new);
                    totara_set_notification(get_string('checkconfig', 'tool_totara_sync', $url->out()), null, array('class'=>'notifynotice alert alert-warning'));
                }
            }
        }
    }

    function sync() {
        global $DB;

        $elname = $this->get_name();

        $this->addlog(get_string('syncstarted', 'tool_totara_sync'), 'info', "{$elname}sync");
        if (!$synctable = $this->get_source_sync_table()) {
            throw new totara_sync_exception($elname, "{$elname}sync", 'couldnotgetsourcetable');
        }

        // Create a clone of the temporary table
        if (!$synctable_clone = $this->get_source_sync_table_clone($synctable)) {
            throw new totara_sync_exception($elname, "{$elname}sync", 'couldnotcreateclonetable');
        }

        if (!$this->check_sanity($synctable, $synctable_clone)) {
            $this->get_source()->drop_table($synctable_clone);
            throw new totara_sync_exception($elname, "{$elname}sync", 'sanitycheckfailed');
        }

        // Make sure the required deleted column is present if necessary.
        $synctablecolumns = $DB->get_columns($synctable);
        $deletedcolumnpresent = isset($synctablecolumns['deleted']);

        if ($this->config->sourceallrecords == 0 && !$deletedcolumnpresent) {
            throw new totara_sync_exception($elname, "{$elname}sync", 'deletefieldmissingnotallrecords');
        }

        // Create/update items
        if ($this->config->sourceallrecords == 1) {
            // If source contains all records then create/update
            // all records excluding obsolete/unmodified ones.
            $sql = "SELECT s.*
                      FROM {{$synctable}} s
                     WHERE s.idnumber NOT IN
                            (
                                SELECT ii.idnumber
                                  FROM {{$elname}} ii
                       LEFT OUTER JOIN {{$synctable_clone}} ss ON ii.idnumber = ss.idnumber
                                 WHERE (ii.totarasync = 1 AND ss.idnumber IS NULL)
                                    OR ss.timemodified = ii.timemodified
                            )";
        } else if ($this->config->sourceallrecords == 0 && $deletedcolumnpresent) {
            // If the source doesn't contain all records then we don't
            // want to create/update ones marked as deleted.
            // Note that the deleted column should always be present.
            $sql = "SELECT s.*
                      FROM {{$synctable}} s
                     WHERE s.idnumber NOT IN
                           (
                                SELECT ii.idnumber
                                  FROM {{$elname}} ii
                       LEFT OUTER JOIN {{$synctable_clone}} ss ON (ii.idnumber = ss.idnumber)
                                 WHERE (ii.totarasync = 1 AND ss.idnumber IS NULL AND ss.deleted = 1)
                                    OR ss.timemodified = ii.timemodified
                          )";
        }

        if (isset($sql)) {
            $rs = $DB->get_recordset_sql($sql);

            $iscsvimport = substr(get_class($this->get_source()), -4) === '_csv';
            $saveemptyfields = !$iscsvimport || !empty($this->config->csvsaveemptyfields);

            foreach ($rs as $item) {
                $this->sync_item($item, $synctable, $saveemptyfields);
            }
            $rs->close();
        }

        /// Delete items.
        if (!empty($this->config->allow_delete)) {
            if ($this->config->sourceallrecords == 1) {
                // If the source contains all records then we delete
                // any items that don't exist in the source.
                $sql = "SELECT i.id, i.idnumber
                          FROM {{$elname}} i
               LEFT OUTER JOIN {{$synctable}} s ON i.idnumber = s.idnumber
                         WHERE i.totarasync = 1
                           AND s.idnumber IS NULL";
            } else {
                // If the deleted column is present then we delete items
                // marked as deleted.
                if ($deletedcolumnpresent) {
                    $sql = "SELECT i.id, i.idnumber
                              FROM {{$elname}} i
                   LEFT OUTER JOIN {{$synctable}} s ON i.idnumber = s.idnumber
                             WHERE i.totarasync = 1
                               AND s.deleted = 1";
                }
            }

            $rs = $DB->get_recordset_sql($sql);

            foreach ($rs as $r) {
                if (!$this->hierarchy->delete_hierarchy_item($r->id)) {
                    $this->addlog(get_string('cannotdeletex', 'tool_totara_sync',
                        "{$elname} {$r->idnumber}"), 'warn', "{$elname}sync");
                } else {
                    $this->addlog(get_string('deletedx', 'tool_totara_sync',
                        "{$elname} {$r->idnumber}"), 'info', "{$elname}sync");
                }
            }
            $rs->close();
        }

        $this->get_source()->drop_table();
        $this->addlog(get_string('syncfinished', 'tool_totara_sync'), 'info', "{$elname}sync");

        return true;
    }

    /**
     * Sync an item
     *
     * @param stdClass $newitem object with escaped values
     * @param string $synctable sync table name
     * @param bool $saveemptyfields true if empty strings should erase data, false if the field should be ignored
     * @return bool true because someone didn't like calling return without a value
     * @throws totara_sync_exception
     */
    function sync_item($newitem, $synctable, $saveemptyfields) {
        global $DB;

        if (empty($this->config->allow_create) && empty($this->config->allow_update)) {
            // not allowed to create/update, so return early
            return true;
        }

        $elname = $this->get_name();

        if (!$newitem->frameworkid = $DB->get_field("{$elname}_framework", 'id', array('idnumber' => $newitem->frameworkidnumber))) {
            throw new totara_sync_exception($elname, 'syncitem', 'frameworkxnotfound',
                $newitem->frameworkidnumber);
        }

        // Ensure newitem's parent is synced first - only non-existent or not already synced parent items.
        // The condition must use !== '' because it needs to handle 0 as a valid parentidnumber.

        if (empty($this->config->sourceallrecords)) {
            $parentselect = "idnumber = ?";
            $parentparams = array($newitem->parentidnumber);
        } else {
            $parentselect = "idnumber = ? AND timemodified = ?";
            $parentparams = array($newitem->parentidnumber, $newitem->timemodified);
        }

        if ($newitem->parentidnumber !== '' && !$parentid = $DB->get_field_select($elname, 'id', $parentselect, $parentparams)) {

            // Sync parent first (recursive)
            $sql = "SELECT *
                      FROM {{$synctable}}
                     WHERE idnumber = ? ";
            if (!$newparent = $DB->get_record_sql($sql, array($newitem->parentidnumber), IGNORE_MULTIPLE)) {
                throw new totara_sync_exception($elname, 'syncitem', 'parentxnotfound',
                    $newitem->parentidnumber);
            }
            try {
                $this->sync_item($newparent, $synctable, $saveemptyfields);
            } catch (totara_sync_exception $e) {
                throw new totara_sync_exception($elname, 'syncitem', 'cannotsyncitemparent',
                    $newitem->parentidnumber, $e->getMessage());
            }
            // Update parentid with the newly-created one
            $parentid = $DB->get_field($elname, 'id', array('idnumber' => $newitem->parentidnumber));

        // This action is only required for CSV imports. If csvsaveemptyfields is set to zero, an
        // empty string for the parentid should not update or delete the existing value. To ensure
        // this we need to retrieve the existing value and set the parentid to it.
        } else if ($newitem->parentidnumber === '' && isset($this->config->csvsaveemptyfields) && $this->config->csvsaveemptyfields == 0) {
            $parentid = $DB->get_field($elname, 'parentid', array('idnumber' => $newitem->idnumber));
        }

        $newitem->parentid = isset($parentid) && $parentid !== '' ? $parentid : 0;

        if (!isset($newitem->typeidnumber) || (($newitem->typeidnumber === "") && !$saveemptyfields)) {
            unset($newitem->typeid);
        } else if (empty($newitem->typeidnumber)) {
            $newitem->typeid = 0;
        } else {
            $newitem->typeid = $DB->get_field($elname.'_type', 'id', array('idnumber' => $newitem->typeidnumber));
        }

        // Unset the *idnumbers, since we now have the ids ;)
        unset($newitem->frameworkidnumber, $newitem->parentidnumber, $newitem->typeidnumber);

        if (!$dbitem = $DB->get_record($elname, array('idnumber' => $newitem->idnumber))) {  // TODO: make this scale
            ///
            /// Create new hierarchy item
            ///
            if (empty($this->config->allow_create)) {
                return true;
            }
            $newitem->totarasync = 1;
            $newitem->visible = 1;
            $newitem->usermodified = get_admin()->id;

            if (!$hitem = $this->hierarchy->add_hierarchy_item($newitem, $newitem->parentid, $newitem->frameworkid, true, true, false)) {
                throw new totara_sync_exception($elname, 'syncitem', 'cannotcreatex',
                    "{$elname} {$newitem->idnumber}");
            }

            $iscsvimport = substr(get_class($this->get_source()), -4) === '_csv';

            // Save custom field data
            if ($customfields = json_decode($newitem->customfields)) {
                foreach ($customfields as $name=>$value) {
                    if ($value === null) {
                        continue; // Null means "don't update the existing data", so skip this field.
                    }

                    if ($iscsvimport && $value === "" && !$saveemptyfields) {
                        continue; // CSV import and empty fields are not saved, so skip this field.
                    }

                    $hitem->{$name} = $value;
                }
                customfield_save_data($hitem, $this->hierarchy->prefix, $this->hierarchy->shortprefix.'_type', true);
            }

            $this->addlog(get_string('createdx', 'tool_totara_sync', "{$elname} {$hitem->idnumber}"), 'info', 'syncitem');

            return true;
        }

        if ((!empty($newitem->timemodified) && $newitem->timemodified == $dbitem->timemodified) || !$dbitem->totarasync) {
            // This record is not enabled for totara syncing OR
            // Modification time the same, we can skip this sync as nothing changed ;)
            return true;
        }

        $newitem->id = $dbitem->id;


        ///
        /// Update the item
        ///
        if (empty($this->config->allow_update)) {
            return true;
        }

        foreach ($newitem as $field => $value) {
            if ($value === null) {
                unset($newitem->$field); // Null means "don't update the existing data", so skip this field.
            }

            if ($value === "" && !$saveemptyfields) {
                unset($newitem->$field); // CSV import and empty fields are not saved, so skip this field.
            }
        }

        $newitem->usermodified = get_admin()->id;
        if (!$this->hierarchy->update_hierarchy_item($dbitem->id, $newitem, false, true, false)) {
            throw new totara_sync_exception($elname, 'syncitem', 'cannotupdatex',
                "{$elname} {$newitem->idnumber}");
        }

        // Sync custom fields
        if (isset($newitem->typeid) && $newitem->typeid != $dbitem->typeid) {
            // Remove old custom field data
            $this->hierarchy->delete_custom_field_data($dbitem->id);
        }
        if (isset($newitem->typeid) && !empty($newitem->typeid)) {
            // Add/update custom field data
            if ($newcustomfields = json_decode($newitem->customfields)) {
                foreach ($newcustomfields as $name=>$value) {
                    if ($value === null) {
                        continue; // Null means "don't update the existing data", so skip this field.
                    }

                    if ($value === "" && !$saveemptyfields) {
                        continue; // CSV import and empty fields are not saved, so skip this field.
                    }

                    $newitem->{$name} = $value;
                }
                customfield_save_data($newitem, $this->hierarchy->prefix, $this->hierarchy->shortprefix.'_type', true);
            }
        }

        $this->addlog(get_string('updatedx', 'tool_totara_sync', "{$elname} {$newitem->idnumber}"), 'info', 'syncitem');

        return true;
    }

    /**
     * Checks the temporary table for data integrity
     *
     * @global object $DB
     * @param string $synctable
     * @param string $synctable_clone name of the clone table
     * @return boolean
     */
    function check_sanity($synctable, $synctable_clone) {
        global $DB;

        $elname = $this->get_name();

        /// Check frameworks
        $sql = "SELECT DISTINCT frameworkidnumber
                  FROM {{$synctable}}
                 WHERE frameworkidnumber NOT IN
                    (SELECT idnumber
                       FROM {{$elname}_framework}
                    )";
        $rs = $DB->get_recordset_sql($sql);
        if ($rs->valid()) {
            foreach ($rs as $r) {
                $this->addlog(get_string('frameworkxnotexist', 'tool_totara_sync', $r->frameworkidnumber), 'error', 'checksanity');
            }
            $rs->close();
            return false;
        }

        /// Check duplicate idnumbers and warn
        $sql = "SELECT idnumber
            FROM {{$synctable}}
            GROUP BY idnumber
                HAVING COUNT(*) > 1";
        $rs = $DB->get_recordset_sql($sql);
        if ($rs->valid()) {
            foreach ($rs as $r) {
                $this->addlog(get_string('duplicateidnumberx', 'tool_totara_sync', $r->idnumber), 'warn', 'checksanity');
            }
            $rs->close();
        }

        // Check parents.
        if (empty($this->config->sourceallrecords)) {
            $parentssql = " AND (s.parentidnumber NOT IN (SELECT idnumber FROM {{$synctable_clone}}))
                AND s.parentidnumber NOT IN (SELECT idnumber FROM {{$elname}})";
        } else {
            $parentssql = "AND s.parentidnumber NOT IN (SELECT idnumber FROM {{$synctable_clone}})";
        }

        $sql = "SELECT DISTINCT s.parentidnumber
                  FROM {{$synctable}} s
       LEFT OUTER JOIN {{$elname}} i
                    ON s.parentidnumber = i.idnumber
                 WHERE s.parentidnumber IS NOT NULL AND s.parentidnumber != '' " .
            $parentssql;

        $rs = $DB->get_recordset_sql($sql);
        if ($rs->valid()) {
            foreach ($rs as $r) {
                $lngstr = empty($this->config->sourceallrecords) ? 'parentxnotexist' : 'parentxnotexistinfile';
                $this->addlog(get_string($lngstr, 'tool_totara_sync', $r->parentidnumber), 'error', 'checksanity');
            }
            $rs->close();
            return false;
        }

        /// Check types
        $sql = "SELECT DISTINCT typeidnumber
                 FROM {{$synctable}}
                WHERE typeidnumber IS NOT NULL AND typeidnumber != '' AND typeidnumber != '0'
                  AND typeidnumber NOT IN
                     (SELECT idnumber FROM {{$elname}_type})";
        $rs = $DB->get_recordset_sql($sql);
        if ($rs->valid()) {
            foreach ($rs as $r) {
                $this->addlog(get_string('typexnotexist', 'tool_totara_sync', $r->typeidnumber), 'error', 'checksanity');
            }
            $rs->close();
            return false;
        }

        /// Check circular parent references
        /// A circular reference will never have a root node (parentid == NULL)
        /// We can determine CRs by eliminating the nodes of the valid trees
        $sql = "SELECT idnumber, parentidnumber
                  FROM {{$synctable}}";
        $nodes = $DB->get_records_sql_menu($sql);

        // If source does not contain all data we need to also include current parents.
        if (empty($this->config->sourceallrecords)) {
            $items = $DB->get_records($elname, array(), '', 'id, idnumber, parentid');
            foreach ($items as $item) {
                if (!isset($nodes[$item->idnumber])) {
                    $nodes[$item->idnumber] = ($item->parentid == 0) ? 0 : $items[$item->parentid]->idnumber;
                }
            }
        }

        // Start eliminating nodes from the valid trees
        // Start at the top so get all the root nodes (no parentid)
        $top_nodes_1 = array_keys($nodes, '');
        $top_nodes_2 = array_keys($nodes, '0');

        // Merge top level nodes into one array
        $goodnodes = array_merge($top_nodes_1, $top_nodes_2);

        while (!empty($goodnodes)) {
            $newgoodnodes = array();
            foreach ($goodnodes as $nid) {
                // Unset good parentnodes
                unset($nodes[$nid]);

                // Get all good childnodes
                $newgoodnodes = array_merge($newgoodnodes, array_keys($nodes, $nid));
            }

            $goodnodes = $newgoodnodes;
        }

        // Remaining nodes mean we have circular refs!
        if (!empty($nodes)) {
            $this->addlog(get_string('circularreferror', 'tool_totara_sync',
                (object)array('naughtynodes' => implode(', ', array_keys($nodes)))), 'error', 'checksanity');
            return false;
        }

        /// Get all hierarchy records to be created/updated - exclude obsolete/unmodified items
        $sql = "SELECT s.*
                  FROM {{$synctable}} s
                 WHERE s.idnumber NOT IN
                    (SELECT ii.idnumber
                       FROM {{$elname}} ii
            LEFT OUTER JOIN {{$synctable_clone}} ss
                         ON (ii.idnumber = ss.idnumber)
                      WHERE ss.idnumber IS NULL
                         OR ss.timemodified = ii.timemodified
                    )";
        $rs = $DB->get_recordset_sql($sql);
        if ($rs->valid()) {
            foreach ($rs as $r) {
                /// Check custom fields
                if ($customfielddata = json_decode($r->customfields, true)) {
                    $customfielddata = array_map('trim', $customfielddata);
                    $customfields = array_keys($customfielddata);
                    if (empty($r->typeidnumber)) {
                        $customfielddata = array_filter($customfielddata);
                        if (empty($customfielddata)) {
                            // Type and customfield data empty, so skip ;)
                            continue;
                        }
                        $this->addlog(get_string('customfieldsnotype', 'tool_totara_sync', "({$elname}:{$r->idnumber})"), 'error', 'checksanity');
                        return false;
                    }
                    if (!$typeid = $DB->get_field($elname.'_type', 'id', array('idnumber' => $r->typeidnumber))) {
                        $this->addlog(get_string('typexnotfound', 'tool_totara_sync', $r->typeidnumber), 'error', 'checksanity');
                        return false;
                    }
                    foreach ($customfields as $c) {
                        if (empty($customfielddata[$c])) {
                            // Don't check empty fields, as this might be another type's custom field
                            continue;
                        }
                        $shortname = str_replace('customfield_', '', $c);
                        if (!$DB->record_exists($elname.'_type_info_field', array('typeid' => $typeid, 'shortname' => $shortname))) {
                            $this->addlog(get_string('customfieldnotexist', 'tool_totara_sync',
                                (object)array('shortname' => $shortname, 'typeidnumber' => $r->typeidnumber)), 'error', 'checksanity');
                            return false;
                        }
                    }
                }
            }
            $rs->close();
        }

        return true;
    }
}
