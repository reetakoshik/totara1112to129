<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package auth_connect
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');

class auth_connect_form_server_delete extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $server = $this->_customdata;

        $strrequired = get_string('required');

        $mform->addElement('static', 'servername', get_string('name'));
        $mform->addElement('static', 'serveridnumber', get_string('idnumber'));
        $mform->addElement('static', 'serverurl', get_string('url'));

        $options = array(
            '' => get_string('choosedots'),
            'delete' => get_string('auth_remove_delete', 'core_auth'),
            'suspend' => get_string('auth_remove_suspend', 'core_auth'),
            'keep' => get_string('auth_remove_keep', 'core_auth'),
        );
        $mform->addElement('select', 'removeuser', get_string('serverdeleteuser', 'auth_connect'), $options);
        $mform->addRule('removeuser', $strrequired, 'required', null, 'client');

        $auths = core_component::get_plugin_list('auth');
        $enabled = get_string('pluginenabled', 'core_plugin');
        $disabled = get_string('plugindisabled', 'core_plugin');
        $authoptions = array($enabled => array(), $disabled => array());
        foreach ($auths as $auth => $unused) {
            if ($auth === 'connect') {
                continue;
            }
            if (is_enabled_auth($auth)) {
                $authoptions[$enabled][$auth] = get_string('pluginname', "auth_{$auth}");
            } else {
                $authoptions[$disabled][$auth] = get_string('pluginname', "auth_{$auth}");
            }
        }
        $mform->addElement('selectgroups', 'newauth', get_string('serverdeleteauth', 'auth_connect'), $authoptions);
        $mform->setDefault('newauth', 'manual');
        $mform->disabledIf('newauth', 'removeuser', 'eq', 'delete');
        $mform->disabledIf('newauth', 'removeuser', 'eq', '');

        $mform->addElement('text', 'confirm', get_string('confirmdelete', 'auth_connect'), 'size="70"');
        $mform->setType('confirm', PARAM_RAW);
        $mform->addRule('confirm', $strrequired, 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('serverdelete', 'auth_connect'));

        $this->set_data($server);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['confirm'] !== $this->_customdata->serveridnumber) {
            $errors['confirm'] = get_string('error');
        }
        return $errors;
    }
}
