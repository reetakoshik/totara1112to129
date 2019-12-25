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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package tool_totara_sync
 */

global $CFG;

require_once($CFG->dirroot.'/admin/tool/totara_sync/sources/databaselib.php');

abstract class totara_sync_database_testcase extends advanced_testcase {

    /** @var moodle_database */
    protected $ext_dbconnection = null;

    // Database variable for connection.
    private $dbtype = '';
    private $dbhost = '';
    private $dbport = '';
    private $dbname = '';
    private $dbuser = '';
    private $dbpass = '';
    protected $dbtable = '';

    protected $elementname = '';
    protected $sourcetable = '';

    public function setUp() {
        $this->set_up_database_connection();
        parent::setup();
    }

    protected function set_up_database_connection() {
        global $CFG;

        if (defined('TEST_SYNC_DB_TYPE') ||
            defined('TEST_SYNC_DB_HOST') ||
            defined('TEST_SYNC_DB_PORT') ||
            defined('TEST_SYNC_DB_NAME') ||
            defined('TEST_SYNC_DB_USER') ||
            defined('TEST_SYNC_DB_PASS') ||
            defined('TEST_SYNC_DB_TABLE')) {
            $this->dbtype = defined('TEST_SYNC_DB_TYPE') ? TEST_SYNC_DB_TYPE : '';
            $this->dbhost = defined('TEST_SYNC_DB_HOST') ? TEST_SYNC_DB_HOST : '';
            $this->dbport = defined('TEST_SYNC_DB_PORT') ? TEST_SYNC_DB_PORT : '';
            $this->dbname = defined('TEST_SYNC_DB_NAME') ? TEST_SYNC_DB_NAME : '';
            $this->dbuser = defined('TEST_SYNC_DB_USER') ? TEST_SYNC_DB_USER : '';
            $this->dbpass = defined('TEST_SYNC_DB_PASS') ? TEST_SYNC_DB_PASS : '';
            $this->dbtable = defined('TEST_SYNC_DB_TABLE') ? TEST_SYNC_DB_TABLE : '';
        } else {
            $this->dbtype = $CFG->dbtype;
            $this->dbhost = $CFG->dbhost;
            $this->dbport = !empty($CFG->dboptions['dbport']) ? $CFG->dboptions['dbport'] : '';
            $this->dbname = $CFG->dbname;
            $this->dbuser = $CFG->dbuser;
            $this->dbpass = !empty($CFG->dbpass) ? $CFG->dbpass : '';
            $this->dbtable = $CFG->prefix . $this->sourcetable; //'totara_sync_jobassignment_source';
        }

        if (!empty($this->dbtype) &&
            !empty($this->dbhost) &&
            !empty($this->dbname) &&
            !empty($this->dbuser) &&
            !empty($this->dbtable)) {
            // All necessary config variables are set.
            $this->ext_dbconnection = setup_sync_DB($this->dbtype, $this->dbhost, $this->dbname, $this->dbuser, $this->dbpass, array('dbport' => $this->dbport));
        } else {
            $this->assertTrue(false, 'HR Import database test configuration was only partially provided');
        }

        set_config('database_dbtype', $this->dbtype, 'totara_sync_source_' . $this->elementname . '_database');
        set_config('database_dbhost', $this->dbhost, 'totara_sync_source_' . $this->elementname . '_database');
        set_config('database_dbname', $this->dbname, 'totara_sync_source_' . $this->elementname . '_database');
        set_config('database_dbuser', $this->dbuser, 'totara_sync_source_' . $this->elementname . '_database');
        set_config('database_dbpass', $this->dbpass, 'totara_sync_source_' . $this->elementname . '_database');
        set_config('database_dbport', $this->dbport, 'totara_sync_source_' . $this->elementname . '_database');
        set_config('database_dbtable', $this->dbtable, 'totara_sync_source_' . $this->elementname . '_database');
    }

    /**
     * Teardown function
     */
    public function tearDown() {
        // Unset all class variables
        $this->dbtype = null;
        $this->dbhost = null;
        $this->dbport = null;
        $this->dbname = null;
        $this->dbuser = null;
        $this->dbpass = null;

        $this->dbtable = null;
        if (isset($this->ext_dbconnection)) {
            $this->ext_dbconnection->dispose();
        }
        $this->ext_dbconnection = null;
        $this->elementname = null;
        $this->sourcetable = null;
    }

    public abstract function create_external_db_table();

    /**
     * Get the element for use when syncing
     */
    public function get_element() {
        $elements = totara_sync_get_elements(true);
        /** @var totara_sync_element_user $element */
        return $elements[$this->elementname];
    }
}


