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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_customfield
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/customfield/lib.php');
require_once($CFG->dirroot.'/totara/customfield/fieldlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');

$prefix         = required_param('prefix', PARAM_ALPHA);        // hierarchy name or mod name
$typeid         = optional_param('typeid', '0', PARAM_INT);    // typeid if hierarchy
$action         = optional_param('action', 'showlist', PARAM_ALPHA);    // param for some action
$id             = optional_param('id', 0, PARAM_INT); // id of a custom field
$class          = optional_param('class', '', PARAM_ALPHA);

require_login();
$sitecontext = context_system::instance();
$PAGE->set_context($sitecontext);

// Add params to extrainfo in case the customfield need them.
$extrainfo = array('id' => $id, 'action' => $action, 'typeid' => $typeid, 'class' => $class);
/** @var \totara_customfield\prefix\type_base $customfieldtype */
$customfieldtype = get_customfield_type_instace($prefix, $sitecontext, $extrainfo);

if (!$customfieldtype) {
   print_error('nocustomfielddefinedfortheprefix');
}

// Check if the feature is disabled before managing custom fields in that area.
if ($customfieldtype->is_feature_type_disabled()) {
    print_error('nocustomfieldfordisabledfeature');
}

/** @var totara_customfield_renderer $renderer*/
$renderer = $PAGE->get_renderer('totara_customfield');

// Set redirect.
$redirectoptions = $renderer->get_redirect_options($prefix, $id, $typeid, $class);
$redirectpage = '/totara/customfield/index.php';
$redirect = new moodle_url('/totara/customfield/index.php', $redirectoptions);

$PAGE->set_url($redirect);

if ($class) {
    $adminpagename = $class . $renderer->get_admin_page($prefix);
} else {
    $adminpagename = $renderer->get_admin_page($prefix);
}

admin_externalpage_setup($adminpagename);

// Check if any actions need to be performed.
switch ($action) {
    case 'showlist':
        echo $OUTPUT->header();
        echo $renderer->customfield_tabs_link($prefix, $redirectoptions);
        echo $renderer->get_heading($prefix, $action);

        $options = customfield_list_datatypes();
        $can_manage = has_capability($customfieldtype->get_capability_managefield(), $sitecontext);
        $fields = $customfieldtype->get_defined_fields($customfieldtype->get_fields_sql_where());

        echo $renderer->totara_customfield_print_list($fields, $can_manage, $options, $redirectpage, $redirectoptions);
        break;
    case 'movefield':
        require_capability($customfieldtype->get_capability_managefield(), $sitecontext);
        $id  = required_param('id', PARAM_INT);
        $dir = required_param('dir', PARAM_ALPHA);

        if (confirm_sesskey()) {
            $customfieldtype->move($id, $dir);
            redirect($redirect);
        }
        break;
    case 'deletefield':
        require_capability($customfieldtype->get_capability_managefield(), $sitecontext);
        $id      = required_param('id', PARAM_INT);
        $confirm = optional_param('confirm', 0, PARAM_BOOL);

        if (data_submitted() and $confirm and confirm_sesskey()) {
            $customfieldtype->delete($id);
            redirect($redirect);
        }

        echo $OUTPUT->header();
        echo $renderer->customfield_tabs_link($prefix, $redirectoptions);
        echo $renderer->get_heading($prefix, $action);

        // Ask for confirmation.
        $datacount = $DB->count_records($customfieldtype->get_table_prefix().'_info_data', array('fieldid' => $id));
        $optionsyes = array ('prefix' => $prefix, 'id' => $id, 'confirm' => 1,
            'action' => 'deletefield', 'sesskey' => sesskey(), 'typeid' => $typeid, 'class' => $class);
        echo $renderer->totara_customfield_delete_confirmation($datacount, $redirectpage, $optionsyes, $redirectoptions);
        break;
    case 'editfield':
        $id       = optional_param('id', 0, PARAM_INT);
        $datatype = optional_param('datatype', '', PARAM_ALPHA);

        $capability = $customfieldtype->get_capability_managefield();
        require_capability($capability, $sitecontext);

        $tableprefix = $customfieldtype->get_table_prefix();
        $field = customfield_get_record_by_id($tableprefix, $id, $datatype);
        $datatype = $field->datatype;
        $datatypes = customfield_list_datatypes();

        $tabs = $renderer->customfield_tabs_link($prefix, $redirectoptions);
        $heading = $renderer->get_heading($prefix, $action, $datatypes[$datatype]);

        $renderer->customfield_manage_edit_form($prefix, $typeid, $tableprefix, $field, $redirect, $heading, $tabs, array(), $class, $customfieldtype);
        break;

    case 'hide':
        $id = required_param('id', PARAM_INT);
        $datatype = optional_param('datatype', '', PARAM_ALPHA);

        $capability = $customfieldtype->get_capability_managefield();
        require_capability($capability, $sitecontext);

        $tableprefix = $customfieldtype->get_table_prefix();
        totara_customfield_set_hidden_by_id($tableprefix, $id, $datatype);

        redirect($redirect);
        break;

    default:
        echo $OUTPUT->header();
        echo $renderer->customfield_tabs_link($prefix, $redirectoptions);
        print_error('actiondoesnotexist', 'totara_customfield');
        break;
}

echo $OUTPUT->footer();
