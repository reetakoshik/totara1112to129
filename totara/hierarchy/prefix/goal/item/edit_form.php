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
 * @subpackage totara_hierarchy
 */

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/goal/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/item/edit_form.php');

class goal_edit_form extends item_edit_form {

    // Load data for the form.
    public function definition_hierarchy_specific() {
        global $DB;

        $mform =& $this->_form;
        $item = $this->_customdata['item'];

        // Get the name of the framework's scale. (Note this code expects there.
        // To be only one scale per framework, even though the DB structure.
        // Allows there to be multiple since we're using a go-between table).
        $scaledesc = $DB->get_field_sql("
            SELECT s.name
            FROM
                {{$this->hierarchy->shortprefix}_scale} s,
                {{$this->hierarchy->shortprefix}_scale_assignments} a
            WHERE
                a.frameworkid = ?
                and a.scaleid = s.id
        ", array($item->frameworkid));

        $mform->addElement('static', 'scalename', get_string('scale'), ($scaledesc) ? $scaledesc : get_string('none'));
        $mform->addHelpButton('scalename', 'goalscale', 'totara_hierarchy');

    }
}

class goal_edit_personal_form extends moodleform {

    // Define the form.
    public function definition() {
        global $DB, $TEXTAREA_OPTIONS, $USER;

        // Javascript include.
        local_js(array(
            TOTARA_JS_DIALOG,
            TOTARA_JS_UI,
            TOTARA_JS_ICON_PREVIEW
        ));
        $mform =& $this->_form;
        $userid = $this->_customdata['userid'];
        $id = $this->_customdata['id'];

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', $userid);
        $mform->setDefault('id', $id);

        // Name.
        $mform->addElement('text', 'name', get_string('name'), 'maxlength="1024" size="50"');
        $mform->addRule('name', get_string('goalmissingname', 'totara_hierarchy'), 'required', null);
        $mform->setType('name', PARAM_MULTILANG);

        // Description.
        $mform->addElement('editor', 'description_editor', get_string('description', 'totara_hierarchy'),
                null, $TEXTAREA_OPTIONS);
        $mform->addHelpButton('description_editor', 'goaldescription', 'totara_hierarchy');
        $mform->setType('description_editor', PARAM_CLEANHTML);

        // Get the list of goal types.
        $goals = $DB->get_records('goal_user_type');

        // If goal types available display the dropdown list.
        $prefix = 'goal';
        $item = $this->_customdata['item'];

        hierarchy::check_enable_hierarchy($prefix);
        $hierarchy  = hierarchy::load_hierarchy($prefix);
        $typetable  = 'goal_user_type';
        $types      = $hierarchy->get_types(array('personal' => 1));
        $type       = $hierarchy->get_type_by_id($item->typeid, $typetable);

        $typename   = ($type) ? $type->fullname : get_string('unclassified', 'totara_hierarchy');

        if ($item->id) {
            // Display current type (static).
            $mform->addElement('static', 'type', get_string('type', 'totara_hierarchy'));
            $mform->setDefault('type', $typename);
            $mform->addHelpButton('type', $prefix.'type', 'totara_hierarchy');

            // Store the actual type ID.
            $mform->addElement('hidden', 'typeid', $item->typeid);
            $mform->setType('typeid', PARAM_INT);
        } else {
            if ($types) {
                // Show type picker if there are choices.
                $select = array('0' => get_string('unclassified', 'totara_hierarchy'));
                $usercohorts = $DB->get_fieldset_select('cohort_members', 'cohortid', 'userid = ?', array($userid));
                foreach ($types as $type) {
                    if ($type->audience == 0) {
                        $select[$type->id] = $type->fullname;
                    } else {
                        $typecohorts = $DB->get_fieldset_select('goal_user_type_cohort',
                            'cohortid', 'goalid = ?', array($type->id));
                        if (count(array_intersect($typecohorts, $usercohorts)) > 0) {
                            $select[$type->id] = $type->fullname;
                        }
                    }
                }
                $mform->addElement('select', 'typeid', get_string('type', 'totara_hierarchy'),
                    $select, totara_select_width_limiter());
                $mform->addHelpButton('typeid', $prefix.'type', 'totara_hierarchy');
            } else {
                // No types exist.
                // Default to 'unclassified'.
                $mform->addElement('hidden', 'typeid', '0');
                $mform->setType('typeid', PARAM_INT);
            }
        }

        // Scale.
        $scales = $DB->get_records('goal_scale', array());
        $scaledesc = array(0 => get_string('none'));
        foreach ($scales as $scale) {
            $scaledesc[$scale->id] = format_string($scale->name);
        }
        $mform->addElement('select', 'scaleid', get_string('scale'), ($scaledesc) ? $scaledesc : get_string('none'));
        $mform->addHelpButton('scaleid', 'goalscale', 'totara_hierarchy');

        // Target date.
        $mform->addElement('date_selector', 'targetdate', get_string('goaltargetdate', 'totara_hierarchy'), array('optional' => true));
        $mform->addHelpButton('targetdate', 'goaltargetdate', 'totara_hierarchy');
        $mform->setType('targetdate', PARAM_INT);

        // Add the custom fields.
        if ($item->id) {
            customfield_definition($mform, $item, 'goal_user', $item->typeid, 'goal_user');
        }

        $this->add_action_buttons();
    }

    /**
     * Add the any custom fields to the form.
     */
    public function definition_after_data() {
        global $DB;

        $mform =& $this->_form;
        $itemid = $mform->getElementValue('id');
        $prefix = 'goal_user';

        if ($item = $DB->get_record('goal_personal', array('id' => $itemid))) {
            customfield_definition_after_data($mform, $item, $prefix, $item->typeid, $prefix);
        }
    }

    public function validation($fromform, $files) {
        global $DB;
        $errors = array();
        $fromform = (object)$fromform;

        // Check user exists.
        if (!$DB->record_exists('user', array('id' => $fromform->userid))) {
            $errors['user'] = get_string('userdoesnotexist', "totara_core");
        }

        // Check scale exists.
        if (!empty($fromform->scaleid) && !$DB->record_exists('goal_scale', array('id' => $fromform->scaleid))) {
            $errors['scale'] = get_string('invalidgoalscale', "totara_hierarchy");
        }

        // Check target date is in the future.
        if (!empty($fromform->targetdate) && $fromform->targetdate < time()) {
            $errors['targetdate'] = get_string('error:invaliddatepast', 'totara_hierarchy');
        }

        if ($fromform->id) {
            // Check custom fields.
            $errors = array_merge($errors, customfield_validation($fromform, 'goal_user', 'goal_user'));
        }

        return $errors;
    }
}
