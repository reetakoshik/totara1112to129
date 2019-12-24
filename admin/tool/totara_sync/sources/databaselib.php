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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage totara_sync
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');

/**
 * Sets up instance moodle_database
 *
 * @global object
 * @global object
 * @return moodle_database instance
 */
function setup_sync_DB($dbtype, $dbhost, $dbname, $dbuser, $dbpass, array $dboptions = array()) {
    global $CFG;

    if (!isset($dbuser)) {
        $dbuser = '';
    }

    if (!isset($dbpass)) {
        $dbpass = '';
    }

    if (!isset($dbname)) {
        $dbname = '';
    }

    if (!isset($dblibrary)) {
        $dblibrary = 'native';
        // Use new drivers instead of the old adodb driver names
        switch ($dbtype) {
        case 'postgres7' :
            $dbtype = 'pgsql';
            break;

        case 'mssql_n':
            $dbtype = 'mssql';
            break;

        case 'oci8po':
            $dbtype = 'oci';
            break;

        case 'mysql' :
            $dbtype = 'mysqli';
            break;
        }
    }

    if ($dbtype === 'mssql') {
        // Totara: mssql driver is dead since PHP 7
        $dbtype = 'sqlsrv';
    }

    // Note: this is likely not a Totara database, use the $external parameter,
    //       external databases do not need prefix and do not use some other nasty hacks.
    if (!$sync_db = moodle_database::get_driver_instance($dbtype, $dblibrary, true)) {
        throw new dml_exception('dbdriverproblem', "Unknown driver $dblibrary/$dbtype");
    }

    try {
        $sync_db->connect($dbhost, $dbuser, $dbpass, $dbname, '', $dboptions);
    } catch (moodle_exception $e) {
        if (empty($CFG->noemailever) and !empty($CFG->emailconnectionerrorsto)) {
            if (file_exists($CFG->dataroot.'/emailcount')){
                $fp = @fopen($CFG->dataroot.'/emailcount', 'r');
                $content = @fread($fp, 24);
                @fclose($fp);
                if ((time() - (int)$content) > 600){
                    // Email directly rather than using messaging
                    @mail($CFG->emailconnectionerrorsto,
                        'WARNING: External database connection error: '.$CFG->wwwroot,
                        'Connection error: '.$CFG->wwwroot);
                    $fp = @fopen($CFG->dataroot.'/emailcount', 'w');
                    @fwrite($fp, time());
                }
            } else {
                // Email directly rather than using messaging
                @mail($CFG->emailconnectionerrorsto,
                    'WARNING: External database connection error: '.$CFG->wwwroot,
                    'Connection error: '.$CFG->wwwroot);
                $fp = @fopen($CFG->dataroot.'/emailcount', 'w');
                @fwrite($fp, time());
            }
        }
        // Rethrow the exception
        throw $e;
    }

    return $sync_db;
}


/**
 * Returns a list of associative array of installed database drivers
 *
 * return arrray
 */
function get_installed_db_drivers() {
    $databases = array('mysqli' => moodle_database::get_driver_instance('mysqli', 'native'),
        'pgsql'  => moodle_database::get_driver_instance('pgsql',  'native'),
        'oci'    => moodle_database::get_driver_instance('oci',    'native'),
        'sqlsrv' => moodle_database::get_driver_instance('sqlsrv', 'native'), // MS SQL*Server PHP driver.
    );

    $disabled = array();
    $installed = array();
    foreach ($databases as $type => $database) {
        if ($database->driver_installed() !== true) {
            continue;
        }
        $installed[$type] = $database->get_name();
    }

    return $installed;
}
