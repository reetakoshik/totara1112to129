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
 * Native MariaDB class representing moodle database interface.
 *
 * @package    core_dml
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/moodle_database.php');
require_once(__DIR__.'/mysqli_native_moodle_database.php');
require_once(__DIR__.'/mysqli_native_moodle_recordset.php');
require_once(__DIR__.'/mysqli_native_moodle_temptables.php');

/**
 * Native MariaDB class representing moodle database interface.
 *
 * @package    core_dml
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mariadb_native_moodle_database extends mysqli_native_moodle_database {

    /**
     * Returns localised database type name
     * Note: can be used before connect()
     * @return string
     */
    public function get_name() {
        return get_string('nativemariadb', 'install');
    }

    /**
     * Returns localised database configuration help.
     * Note: can be used before connect()
     * @return string
     */
    public function get_configuration_help() {
        return get_string('nativemariadbhelp', 'install');
    }

    /**
     * Returns the database vendor.
     * Note: can be used before connect()
     * @return string The db vendor name, usually the same as db family name.
     */
    public function get_dbvendor() {
        return 'mariadb';
    }

    /**
     * Returns more specific database driver type
     * Note: can be used before connect()
     * @return string db type mysqli, pgsql, oci, mssql, sqlsrv
     */
    protected function get_dbtype() {
        return 'mariadb';
    }

    /**
     * Add configuration for specific versions of MariaDB
     */
    protected function version_specific_support() {
        // MariaDB doesn't have issues specific to Mysql (at least yet), so override it.
    }

    /**
     * Returns the driver specific syntax for the beginning of a word boundary.
     *
     * @since Totara 12.4
     * @return string or empty if not supported
     */
    public function sql_regex_word_boundary_start(): string {
        // MariaDB doesn't have regexp issue specific to MySQL, so override it.
        return '[[:<:]]';
    }

    /**
     * Returns the driver specific syntax for the end of a word boundary.
     *
     * @since Totara 12.4
     * @return string or empty if not supported
     */
    public function sql_regex_word_boundary_end(): string {
        // MariaDB doesn't have regexp issue specific to MySQL, so override it.
        return '[[:>:]]';
    }

    /**
     * Returns database server info array
     * @return array Array containing 'description' and 'version' info
     */
    public function get_server_info() {
        if (!$this->mysqli) {
            return null;
        }

        if (isset($this->serverinfo)) {
            return $this->serverinfo;
        }

        $this->serverinfo = array(
            'description' => $this->mysqli->server_info,
            'version' => $this->mysqli->server_info,
        );

        if (preg_match('/^5\.5\.5-(10\..+)-MariaDB/i', $this->serverinfo['version'], $matches)) {
            // Looks like MariaDB decided to use these weird version numbers for better BC with MySQL...
            $this->serverinfo['version'] = $matches[1];
        }

        return $this->serverinfo;
    }

    /**
     * It is time to require transactions everywhere.
     *
     * MyISAM is NOT supported!
     *
     * @return bool
     */
    protected function transactions_supported() {
        if ($this->external) {
            return parent::transactions_supported();
        }
        return true;
    }

    /**
     * Returns true as MariaDB testing showed that for the queries tested 2 queries was faster than a counted recordset.
     *
     * Testing showed that MariaDB 10.2, 10.3, and 10.4 when using counted recordsets performed quicker than two independent queries
     * on a paginated recordset.
     * For results on performance testing of paginated results see parent class.
     *
     * @since Totara 12.4
     * @return bool
     */
    public function recommends_counted_recordset(): bool {
        return true;
    }
}
