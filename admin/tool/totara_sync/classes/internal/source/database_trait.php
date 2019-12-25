<?php
/*
 * This file is part of Totara Learn
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

namespace tool_totara_sync\internal\source;

defined('MOODLE_INTERNAL') || die();

/**
 * Trait database_trait
 *
 * Can be used by a source to add functionality common to those sources that use external database imports.
 *
 * @package tool_totara_sync\internal\source
 */
trait database_trait {

    /**
     * Adds the details fields to the database settings form. This includes a static element that shows
     * the expected structure of the database as well as fields for the connection settings.
     *
     * @param  \MoodleQuickForm $mform
     */
    protected function config_form_add_database_details(&$mform) {
        global $PAGE;

        $mform->addElement('html', \html_writer::tag('p', get_string('dbconnectiondetails', 'tool_totara_sync')));

        $db_options = get_installed_db_drivers();

        // Database details
        $mform->addElement('select', 'database_dbtype', get_string('dbtype', 'tool_totara_sync'), $db_options);
        $mform->addElement('text', 'database_dbname', get_string('dbname', 'tool_totara_sync'));
        $mform->addRule('database_dbname', get_string('err_required', 'form'), 'required');
        $mform->setType('database_dbname', PARAM_RAW); // There is no safe cleaning of connection strings.
        $mform->addElement('text', 'database_dbhost', get_string('dbhost', 'tool_totara_sync'));
        $mform->setType('database_dbhost', PARAM_HOST);
        $mform->addElement('text', 'database_dbuser', get_string('dbuser', 'tool_totara_sync'));
        $mform->addRule('database_dbuser', get_string('err_required', 'form'), 'required');
        $mform->setType('database_dbuser', PARAM_ALPHANUMEXT);
        $mform->addElement('password', 'database_dbpass', get_string('dbpass', 'tool_totara_sync'));
        $mform->setType('database_dbpass', PARAM_RAW);
        $mform->addElement('text', 'database_dbport', get_string('dbport', 'tool_totara_sync'));
        $mform->setType('database_dbport', PARAM_INT);

        // Table name
        $mform->addElement('text', 'database_dbtable', get_string('dbtable', 'tool_totara_sync'));
        $mform->addRule('database_dbtable', get_string('err_required', 'form'), 'required');
        $mform->setType('database_dbtable', PARAM_ALPHANUMEXT);

        // Date format.
        $dateformats = $this->get_dateformats();
        $mform->addElement('select', 'database_dateformat', get_string('dbdateformat', 'tool_totara_sync'), $dateformats);
        $mform->setType('database_dateformat', PARAM_TEXT);
        $mform->addHelpButton('database_dateformat', 'dbdateformat', 'tool_totara_sync');

        $mform->addElement('button', 'database_dbtest', get_string('dbtestconnection', 'tool_totara_sync'));

        //Javascript include
        local_js(array(TOTARA_JS_DIALOG));

        $PAGE->requires->strings_for_js(array('dbtestconnectsuccess', 'dbtestconnectfail'), 'tool_totara_sync');

        $jsmodule = array(
            'name' => 'totara_syncdatabaseconnect',
            'fullpath' => '/admin/tool/totara_sync/sources/sync_database.js',
            'requires' => array('json', 'totara_core'));

        $PAGE->requires->js_init_call('M.totara_syncdatabaseconnect.init', null, false, $jsmodule);
    }

    /**
     * Saves data entered into fields that were created by the config_form_add_database_details method.
     *
     * @param \stdClass $data
     */
    public function config_save_database_details($data) {
        //Check database connection when saving
        try {
            setup_sync_DB($data->{'database_dbtype'}, $data->{'database_dbhost'}, $data->{'database_dbname'},
                $data->{'database_dbuser'}, $data->{'database_dbpass'}, array('dbport' => $data->{'database_dbport'}));
        } catch (\Exception $e) {
            totara_set_notification(get_string('cannotconnectdbsettings', 'tool_totara_sync'), qualified_me());
        }

        $this->set_config('database_dbtype', $data->{'database_dbtype'});
        $this->set_config('database_dbname', $data->{'database_dbname'});
        $this->set_config('database_dbhost', $data->{'database_dbhost'});
        $this->set_config('database_dbuser', $data->{'database_dbuser'});
        $this->set_config('database_dbpass', $data->{'database_dbpass'});
        $this->set_config('database_dbport', $data->{'database_dbport'});
        $this->set_config('database_dbtable', $data->{'database_dbtable'});
        $this->set_config('database_dateformat', $data->{'database_dateformat'});
    }

    /**
     * Returns a list of possible date formats
     * Based on the list at http://en.wikipedia.org/wiki/Date_format_by_country
     *
     * @return array
     */
    protected function get_dateformats() {
        $separators = array('-', '/', '.', ' ');
        $endians = array('yyyy~mm~dd', 'yy~mm~dd', 'dd~mm~yyyy', 'dd~mm~yy', 'mm~dd~yyyy', 'mm~dd~yy');
        $formats = array();

        // Standard datetime format.
        $formats['Y-m-d H:i:s'] = 'yyyy-mm-dd hh:mm:ss';

        foreach ($endians as $endian) {
            foreach ($separators as $separator) {
                $display = str_replace( '~', $separator, $endian);
                $format = str_replace('yyyy', 'Y', $display);
                $format = str_replace('yy', 'y', $format);
                $format = str_replace('mm', 'm', $format);
                $format = str_replace('dd', 'd', $format);
                $formats[$format] = $display;
            }
        }
        return $formats;
    }
}