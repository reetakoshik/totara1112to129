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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');

/**
 * Formslib template for the element settings form
 */
class totara_sync_element_settings_form extends moodleform {

    public const USE_DEFAULT = -1;

    function definition() {
        global $CFG;
        $mform =& $this->_form;
        /* @var totara_sync_element $element */
        $element = $this->_customdata['element'];

        $mform->addElement('header', 'sourcesettingsheader', get_string('sourcesettings', 'tool_totara_sync'));

        // Source selection
        if ($sources = $element->get_sources()) {
            $sourceselection = 'source_'.$element->get_name();
            $radioarray = [];

            foreach ($sources as $source) {
                $radioarray[] = $mform->createElement(
                    'radio',
                    $sourceselection,
                    '',
                    get_string('displayname:' . $source->get_name(), 'tool_totara_sync'),
                    $source->get_name()
                );

                if (substr($source->get_name(), -3) === 'csv') {
                    $csvsource = $source->get_name();
                }
            }

            $mform->addGroup(
                $radioarray,
                'source_type_group',
                get_string('source', 'tool_totara_sync'),
                html_writer::empty_tag('br'),
                false
            );
        } else {
            $mform->addElement('static', 'nosources', '('.get_string('nosources', 'tool_totara_sync').')');
            if (!$element->has_config()) {
                return;
            }
        }

        // File access.
        if (has_capability('tool/totara_sync:setfileaccess', context_system::instance())) {
            $dir = get_string('fileaccess_directory', 'tool_totara_sync');
            $upl = get_string('fileaccess_upload', 'tool_totara_sync');

            $fileaccess_default_setting = get_config('totara_sync', 'fileaccess');
            // false means not config setting. But we should allow both 0 and '0' to mean FILE_ACCESS_DIRECTORY.
            if (($fileaccess_default_setting == FILE_ACCESS_DIRECTORY) && ($fileaccess_default_setting !== false)) {
                $filedefaultstr = get_string('fileaccess_default', 'tool_totara_sync', $dir);
            } else if ($fileaccess_default_setting == FILE_ACCESS_UPLOAD) {
                $filedefaultstr = get_string('fileaccess_default', 'tool_totara_sync', $upl);
            } else {
                // Either the setting has not been configured or is another type, perhaps a 3rd-party value.
                $filedefaultstr = get_string('fileaccess_unknowndefault', 'tool_totara_sync');
            }

            $mform->addElement(
                'select',
                'fileaccess',
                get_string('fileaccess', 'tool_totara_sync'),
                [self::USE_DEFAULT => $filedefaultstr, FILE_ACCESS_DIRECTORY => $dir, FILE_ACCESS_UPLOAD => $upl]
            );
            $mform->setType('fileaccess', PARAM_INT);
            $mform->setDefault('fileaccess', self::USE_DEFAULT);
            $mform->addHelpButton('fileaccess', 'fileaccess', 'tool_totara_sync');
            $mform->disabledIf('fileaccess', $sourceselection, 'noteq', $csvsource);

            $mform->addElement('text', 'filesdir', get_string('filesdir', 'tool_totara_sync'), array('size' => 50));
            $mform->setType('filesdir', PARAM_TEXT);
            $mform->hideIf('filesdir', 'fileaccess', 'noteq', FILE_ACCESS_DIRECTORY);
            $mform->disabledIf('filesdir', $sourceselection, 'noteq', $csvsource);

            if ($fileaccess_default_setting == FILE_ACCESS_DIRECTORY) {
                // This element is really meant to be a static element, but the hideIf method doesn't seem to work for those.
                // So we're going with a text box that gets frozen.
                $mform->addElement('text', 'filesdirdefaulttext', get_string('filesdir', 'tool_totara_sync'), array('size' => 50));
                $mform->setDefault('filesdirdefaulttext', get_config('totara_sync', 'filesdir'));
                $mform->setType('filesdirdefaulttext',PARAM_TEXT);
                $mform->hideIf('filesdirdefaulttext', 'fileaccess', 'noteq', self::USE_DEFAULT);
                $mform->hardFreeze(['filesdirdefaulttext']);
            }
        }

        // Empty CSV field setting.
        $emptyfieldopt = array(
            false => get_string('emptyfieldskeepdata', 'tool_totara_sync'),
            true => get_string('emptyfieldsremovedata', 'tool_totara_sync')
        );

        $mform->addElement('select', 'csvsaveemptyfields', get_string('emptyfieldsbehaviourhierarchy', 'tool_totara_sync'), $emptyfieldopt);
        $default = !empty($element->config->csvsaveemptyfields);
        $mform->setDefault('csvsaveemptyfields', $default);
        $mform->addHelpButton('csvsaveemptyfields', 'emptyfieldsbehaviourhierarchy', 'tool_totara_sync');

        // Disable the field when nothing is selected, and when database is selected.
        $mform->disabledIf('csvsaveemptyfields', $sourceselection, 'noteq', $csvsource);

        // Element configuration
        if ($element->has_config()) {
            $element->config_form($mform);
        }

        // Notifications.
        $mform->addElement('header', 'notificationheading', get_string('notifications', 'tool_totara_sync'));

        $mform->addElement('advcheckbox', 'notificationusedefaults',  get_string('usedefaultsettings', 'tool_totara_sync'));
        $mform->setDefault('notificationusedefaults', '1');
        $mform->addHelpButton('notificationusedefaults', 'usedefaultsettings', 'tool_totara_sync');

        // This object will be the $a for placeholders in the language strings describing notification defaults.
        $notification_defaults = new stdClass();

        // We need to get the list of default notify types and translate them.
        $notifytypes_default = get_config( 'totara_sync', 'notifytypes');

        if (!empty($notifytypes_default)) {
            $notifytypes_default_strings = [];
            foreach (explode(',', $notifytypes_default) as $logtype) {
                switch ($logtype) {
                    case 'error':
                        $notifytypes_default_strings[] = get_string('errorplural', 'tool_totara_sync');
                        break;
                    case 'warn':
                        $notifytypes_default_strings[] = get_string('warnplural', 'tool_totara_sync');
                        break;
                }
            }

            // This is not going to be a cross-language-friendly list for a bunch of reasons (e.g. hard-coded comma,
            // inserting string inside others) but the priority for now is that it at least conveys the information without
            // being confusing.
            $notification_defaults->logmessagetypes = implode(', ', $notifytypes_default_strings);
        } else {
            $notification_defaults->logmessagetypes = get_string('noneselected', 'tool_totara_sync');
        }

        $notification_defaults->recipients = get_config( 'totara_sync', 'notifymailto');

        $notication_default_text = html_writer::tag(
            'p',
            get_string('notifytypesdefault', 'tool_totara_sync', $notification_defaults)
        );
        $notication_default_text .= html_writer::tag(
            'p',
            get_string('notifymailtodefault', 'tool_totara_sync', $notification_defaults)
        );

        // hideIf doesn't work on static elements for mforms. See js in elementsettings.php which affects this element.
        $mform->addElement('static', 'notifcationdefaults', '', $notication_default_text);

        $notifytypes = array();
        $notifytypes[] = $mform->createElement('checkbox', 'notifytypes[error]', '', get_string('errorplural', 'tool_totara_sync'));
        $notifytypes[] = $mform->createElement('checkbox', 'notifytypes[warn]', '', get_string('warnplural', 'tool_totara_sync'));
        $mform->addGroup($notifytypes, 'notifytypes', get_string('notifytypes', 'tool_totara_sync'), '<br/>', false);

        $mform->hideIf('notifytypes', 'notificationusedefaults', 'checked');

        $mform->addElement('text', 'notifymailto', get_string('notifymailto', 'tool_totara_sync'));
        $mform->hideIf('notifymailto', 'notificationusedefaults', 'checked');
        $mform->setType('notifymailto', PARAM_TEXT);
        $mform->setDefault('notifymailto', $CFG->supportemail);
        $mform->addHelpButton('notifymailto', 'notifymailto', 'tool_totara_sync');
        $mform->setExpanded('notificationheading');

        $mform->addElement('header', 'scheduleheading', get_string('schedule', 'tool_totara_sync'));

        $mform->addElement('advcheckbox', 'scheduleusedefaults',  get_string('usedefaultsettings', 'tool_totara_sync'));
        $mform->setDefault('scheduleusedefaults', '1');
        $mform->addHelpButton('scheduleusedefaults', 'usedefaultsettings', 'tool_totara_sync');

        $default_task = \core\task\manager::get_scheduled_task('totara_core\task\tool_totara_sync_task');
        if ($default_task->get_disabled()) {
            $default_schedule_string = get_string('schedulingdisabled', 'tool_totara_sync');
        } else {
            list($default_complexscheduling, $default_scheduleconfig) = get_schedule_form_data($default_task);
            if ($default_complexscheduling) {
                $default_schedule_string = get_string('scheduledefault_complex', 'tool_totara_sync');
            } else {
                $scheduler = new scheduler((object)$default_scheduleconfig);
                $default_schedule_string = get_string('scheduledefault_currentsetting', 'tool_totara_sync', $scheduler->get_formatted());
            }
        }

        // hideIf doesn't work on static elements for mforms. See js in elementsettings.php which affects this element.
        $mform->addElement('static', 'scheduledefaultsetting', '', $default_schedule_string);

        $schedulearray = [];
        $schedulearray[] = $mform->createElement('radio', 'cronenable', null, get_string('scheduledisabled', 'tool_totara_sync'), 0);
        $schedulearray[] = $mform->createElement('radio', 'cronenable', null, get_string('scheduleenabled', 'tool_totara_sync'), 1);
        $mform->addGroup(
            $schedulearray,
            'cronenable_group',
            get_string('scheduledhrimporting', 'tool_totara_sync'),
            html_writer::empty_tag('br'),
            false
        );
        $mform->hideIf('cronenable', 'scheduleusedefaults', 'checked');

        if (!$this->_customdata['complexscheduling']) {
            $mform->addElement('scheduler', 'schedulegroup', get_string('scheduleserver', 'tool_totara_sync'));
            $mform->disabledIf('schedulegroup', 'cronenable', 'notchecked');
            $mform->disabledIf('schedulegroup', 'scheduleusedefaults', 'checked');
            $mform->hideIf('schedulegroup', 'scheduleusedefaults', 'checked');
        } else if (has_capability('moodle/site:config', context_system::instance())) {
            // If there is complex scheduling set then show a message and link to scheduled task edit page.
            $url = new moodle_url('/admin/tool/task/scheduledtasks.php', ['action' => 'edit', 'task' => 'tool_totara_sync\task\\' . $element->get_name()]);
            $editlink = html_writer::link($url, get_string('scheduleadvancedlink', 'totara_core'));
            $advancedschedulestr = get_string('scheduleadvanced', 'totara_core', $editlink);
            $mform->addElement('static', 'advancedschedule', get_string('scheduleserver', 'tool_totara_sync'), $advancedschedulestr);
        } else {
            $advancedschedulestr = get_string('scheduleadvancednopermission', 'totara_core');

            // hideIf doesn't work on static elements for mforms. See js in elementsettings.php which affects this element.
            $mform->addElement('static', 'advancedschedule', get_string('scheduleserver', 'tool_totara_sync'), $advancedschedulestr);
        }
        $mform->setExpanded('scheduleheading');

        $this->add_action_buttons(false);
    }

    /**
     * Validate submitted data.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        /** @var totara_sync_element $element */
        $element = $this->_customdata['element'];
        $errors = array_merge($errors, $element->validation($data, $files));

        if ($data['fileaccess'] == FILE_ACCESS_DIRECTORY && isset($data['filesdir'])) {
            $filesdir = trim($data['filesdir']);

            if (DIRECTORY_SEPARATOR == '\\') {
                $pattern = '/^[a-z0-9 \/\.\-_\\\\\\:]{1,}$/i';
            } else {
                // Character '@' is used in Jenkins workspaces, it might be used on other servers too.
                $pattern = '/^[a-z0-9@ \/\.\-_]{1,}$/i';
            }

            if (!preg_match($pattern, $filesdir)) {
                $errors['filesdir'] = get_string('pathformerror', 'tool_totara_sync');
            } else if (!is_dir($filesdir)) {
                $errors['filesdir'] = get_string('notadirerror', 'tool_totara_sync', $filesdir);
            } else if (!is_writable($filesdir)) {
                $errors['filesdir'] = get_string('readonlyerror', 'tool_totara_sync', $filesdir);
            }
        }

        if (!empty($data['notifymailto'])) {
            $emailaddresses = array_map('trim', explode(',', $data['notifymailto']));
            foreach ($emailaddresses as $mailaddress) {
                if (!validate_email($mailaddress)) {
                    $errors['notifymailto'] = get_string('invalidemailaddress', 'tool_totara_sync', format_string($mailaddress));
                    break;
                }
            }
        }

        return $errors;
    }
}


/**
 * Formslib template for the source settings form
 */
class totara_sync_source_settings_form extends moodleform {

    protected $elementname = '';

    function definition() {
        $mform =& $this->_form;
        $source = $this->_customdata['source'];
        $this->elementname = $this->_customdata['elementname'];
        $sourcename = $source->get_name();

        // Source configuration
        if ($source->config_form($mform) !== false) {
            $this->add_action_buttons(false);
        }
    }

    function set_data($data) {
        //these are set in config_form
        unset($data->import_idnumber);
        unset($data->import_timemodified);

        // All the other's delimiter characters will work as they are but tab need to be converted.
        if (isset($data->delimiter) && $data->delimiter == "\t") {
            $data->delimiter = '\t';
        }

        if ($this->elementname == 'pos' || $this->elementname == 'org') {
            unset($data->import_fullname);
            unset($data->import_frameworkidnumber);
        }
        if ($this->elementname == 'user') {
            unset($data->import_username);
            unset($data->import_deleted);
            if (get_config('totara_sync_element_user', 'allow_create')) {
                // If users can be created then the firstname and lastname settings will be determined by code.
                unset($data->import_firstname);
                unset($data->import_lastname);
                if (!get_config('totara_sync_element_user', 'allowduplicatedemails')) {
                    // If users can be created and duplicate emails are not allowed then the setting will be determined by code.
                    unset($data->import_email);
                }
            }
        }
        parent::set_data($data);
    }

    /**
     * Validate submitted data.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     */
    public function validation($data, $files) {
        /* @var totara_sync_source $source */
        $source = $this->_customdata['source'];
        return $source->validate_settings($data);
    }
}


/**
 * Form for general sync settings
 */
class totara_sync_config_form extends moodleform {
    function definition() {
        global $CFG;

        $mform = $this->_form;

        // File access.
        if (has_capability('tool/totara_sync:setfileaccess', context_system::instance())) {
            $mform->addElement('header', 'fileheading', get_string('files', 'tool_totara_sync'));

            $mform->addElement(
                'static',
                'fileusingdefaults',
                get_string('elementsusingdefault', 'tool_totara_sync'),
                $this->display_elements_using_default('fileaccess')
            );

            $dir = get_string('fileaccess_directory', 'tool_totara_sync');
            $upl = get_string('fileaccess_upload', 'tool_totara_sync');
            $mform->addElement('select', 'fileaccess', get_string('fileaccess', 'tool_totara_sync'),
                array(FILE_ACCESS_DIRECTORY => $dir, FILE_ACCESS_UPLOAD => $upl));
            $mform->setType('fileaccess', PARAM_INT);
            $mform->setDefault('fileaccess', $dir);
            $mform->addHelpButton('fileaccess', 'fileaccess', 'tool_totara_sync');
            $mform->addElement('text', 'filesdir', get_string('filesdir', 'tool_totara_sync'), array('size' => 50));
            $mform->setType('filesdir', PARAM_TEXT);
            $mform->disabledIf('filesdir', 'fileaccess', 'eq', FILE_ACCESS_UPLOAD);
        }

        // Notifications.
        $mform->addElement('header', 'notificationheading', get_string('notifications', 'tool_totara_sync'));

        $mform->addElement(
            'static',
            'notificationusingdefaults',
            get_string('elementsusingdefault', 'tool_totara_sync'),
            $this->display_elements_using_default('notification')
        );

        $notifytypes = array();
        $notifytypes[] = $mform->createElement('checkbox', 'notifytypes[error]', '', get_string('errorplural', 'tool_totara_sync'));
        $notifytypes[] = $mform->createElement('checkbox', 'notifytypes[warn]', '', get_string('warnplural', 'tool_totara_sync'));
        $mform->addGroup($notifytypes, 'notifytypes', get_string('notifytypes', 'tool_totara_sync'), '<br/>', false);

        $mform->addElement('text', 'notifymailto', get_string('notifymailto', 'tool_totara_sync'));
        $mform->setType('notifymailto', PARAM_TEXT);
        $mform->setDefault('notifymailto', $CFG->supportemail);
        $mform->addHelpButton('notifymailto', 'notifymailto', 'tool_totara_sync');
        $mform->setExpanded('notificationheading');

        $mform->addElement('header', 'scheduleheading', get_string('schedule', 'tool_totara_sync'));

        $mform->addElement(
            'static',
            'scheduleusingdefaults',
            get_string('elementsusingdefault', 'tool_totara_sync'),
            $this->display_elements_using_default('schedule')
        );

        $schedulearray = [];
        $schedulearray[] = $mform->createElement('radio', 'cronenable', null, get_string('scheduledisabled', 'tool_totara_sync'), 0);
        $schedulearray[] = $mform->createElement('radio', 'cronenable', null, get_string('scheduleenabled', 'tool_totara_sync'), 1);
        $mform->addGroup(
            $schedulearray,
            'cronenable_group',
            get_string('scheduledhrimporting', 'tool_totara_sync'),
            html_writer::empty_tag('br'),
            false
        );
        $mform->setDefault('cronenable', 1);

        $complexscheduling = $this->_customdata['complexscheduling'];
        if (!$complexscheduling) {
            $mform->addElement('scheduler', 'schedulegroup', get_string('scheduleserver', 'tool_totara_sync'));
            $mform->disabledIf('schedulegroup', 'cronenable', 1);
        } else if (has_capability('moodle/site:config', context_system::instance())) {
            // If there is complex scheduling set then show a message and link to scheduled task edit page.
            $url = new moodle_url('/admin/tool/task/scheduledtasks.php', array('action' => 'edit', 'task' => 'totara_core\task\tool_totara_sync_task'));
            $editlink = html_writer::link($url, get_string('scheduleadvancedlink', 'totara_core'));
            $advancedschedulestr = get_string('scheduleadvanced', 'totara_core', $editlink);
            $mform->addElement('static', 'advancedschedule', get_string('scheduleserver', 'tool_totara_sync'), $advancedschedulestr);
        } else {
            $advancedschedulestr = get_string('scheduleadvancednopermission', 'totara_core');
            $mform->addElement('static', 'advancedschedule', get_string('scheduleserver', 'tool_totara_sync'), $advancedschedulestr);
        }
        $mform->setExpanded('scheduleheading');

        $this->add_action_buttons(false);
    }

    /**
     * Check if path is well-formed (no validation for existence)
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (DIRECTORY_SEPARATOR == '\\') {
            $pattern = '/^[a-z0-9 \/\.\-_\\\\\\:]{1,}$/i';
        } else {
            // Character '@' is used in Jenkins workspaces, it might be used on other servers too.
            $pattern = '/^[a-z0-9@ \/\.\-_]{1,}$/i';
        }

        if ($data['fileaccess'] == FILE_ACCESS_DIRECTORY && isset($data['filesdir'])) {
            $filesdir = trim($data['filesdir']);
            if (!preg_match($pattern, $filesdir)) {
                $errors['filesdir'] = get_string('pathformerror', 'tool_totara_sync');
            } else if (!is_dir($filesdir)) {
                $errors['filesdir'] = get_string('notadirerror', 'tool_totara_sync', $filesdir);
            } else if (!is_writable($filesdir)) {
                $errors['filesdir'] = get_string('readonlyerror', 'tool_totara_sync', $filesdir);
            }
        }

        if (!empty($data['notifymailto'])) {
            $emailaddresses = array_map('trim', explode(',', $data['notifymailto']));
            foreach ($emailaddresses as $mailaddress) {
                if (!validate_email($mailaddress)) {
                    $errors['notifymailto'] = get_string('invalidemailaddress', 'tool_totara_sync', format_string($mailaddress));
                    break;
                }
            }
        }

        return $errors;
    }

    private function display_elements_using_default(string $configarea) {
        $elements = totara_sync_get_elements(true);
        $usingdefault_names = [];
        foreach ($elements as $element) {
            $usingdefaults = false;
            switch ($configarea) {
                case 'fileaccess':
                    $usingdefaults = $element->use_fileaccess_defaults();
                    break;
                case 'notification':
                    $usingdefaults = $element->use_notification_defaults();
                    break;
                case 'schedule':
                    $usingdefaults = $element->use_schedule_defaults();
                    break;
            }
            if ($usingdefaults) {
                    $usingdefault_names[] = get_string('displayname:' . $element->get_name(), 'tool_totara_sync');
            }
        }

        if (empty($usingdefault_names)) {
            return get_string('noneusedefault', 'tool_totara_sync');
        } else {
            return implode(html_writer::empty_tag('br'), $usingdefault_names);
        }
    }
}


/**
 * Form for uploading of source sync files
 */
class totara_sync_source_files_form extends moodleform {
    function definition() {
        global $CFG, $USER, $FILEPICKER_OPTIONS;
        $mform =& $this->_form;
        require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');

        $elements = totara_sync_get_elements($onlyenabled=true);
        if (!count($elements)) {
            $mform->addElement('html', html_writer::tag('p',
                get_string('noenabledelements', 'tool_totara_sync')));
            return;
        }

        foreach ($elements as $e) {
            $name = $e->get_name();
            if (!$e->can_upload_file()) {
                continue;
            }
            $mform->addElement('header', "header_{$name}",
                get_string("displayname:{$name}", 'tool_totara_sync'));
            $mform->setExpanded("header_{$name}");

            try {
                $source = $e->get_source();
            } catch (totara_sync_exception $e) {
                $link = "{$CFG->wwwroot}/admin/tool/totara_sync/admin/elementsettings.php?element={$name}";
                $mform->addElement('html', html_writer::tag('p',
                    get_string('nosourceconfigured', 'tool_totara_sync', $link)));
                continue;
            }

            if (!$source->uses_files()) {
                $mform->addElement('html', html_writer::tag('p',
                    get_string('sourcedoesnotusefiles', 'tool_totara_sync')));
                continue;
            }


            $mform->addElement('filepicker', $name,
            get_string('displayname:'.$source->get_name(), 'tool_totara_sync'), 'size="40"');

            if ($e->can_upload_file()) {
                $usercontext = context_user::instance($USER->id);
                $systemcontext = context_system::instance();
                $fs = get_file_storage();

                //check for existing draft area to prevent massive duplication
                $existing_files = $fs->get_area_files($systemcontext->id, 'totara_sync', $name);
                if (sizeof($existing_files) > 0) {
                    $file = reset($existing_files);
                    $draftid = !empty($file) ? $file->get_itemid() : 0;
                    $existing_draft = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftid);

                    //if no existing draft area, make one
                    if (sizeof($existing_draft) < 1) {
                        //create draft area to set as the value for mform->filepicker
                        file_prepare_draft_area($draftid, $systemcontext->id, 'totara_sync', $name, null, $FILEPICKER_OPTIONS);
                        $file_record = array('contextid' => $usercontext->id, 'component' => 'user', 'filearea'=> 'draft', 'itemid' => $draftid);

                        //add existing file(s) to the draft area
                        foreach ($existing_files as $file) {
                            if ($file->is_directory()) {
                                continue;
                            }
                            $fs->create_file_from_storedfile($file_record, $file);
                            $mform->addElement('static', '', '',
                                get_string('note:syncfilepending', 'tool_totara_sync'));
                        }
                    }
                    //set the filepicker value to the draft area
                    $mform->getElement($name)->setValue($draftid);
                }
            }
        }

        $this->add_action_buttons(false, get_string('upload'));
    }

    /**
     * Does this form element have a file?
     *
     * @param string $elname
     * @return boolean
     */
    function hasFile($elname) {
        global $USER;

        $elements = totara_sync_get_elements($onlyenabled=true);
        // element must exist
        if (!in_array($elname, array_keys($elements))) {
            return false;
        }

        // source must be configured
        try {
            $source = $elements[$elname]->get_source();
        } catch (totara_sync_exception $e) {
            return false;
        }

        $values = $this->_form->exportValues($elname);
        if (empty($values[$elname])) {
            return false;
        }
        $draftid = $values[$elname];
        $fs = get_file_storage();
        $context = context_user::instance($USER->id);
        if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false)) {
            return false;
        }
        return true;
    }
}
