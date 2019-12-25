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
 * Totara navigation rules edit form.
 *
 * @author Chris Wharton <chris.wharton@catalyst-eu.net>
 * @package totara_core
 * @subpackage navigation
 */

use \totara_core\totara\menu\item;

class rules_form extends moodleform {

    public function definition() {

        $mform = & $this->_form;
        /* @var item $item */
        $item = $this->_customdata['item'];

        $mform->addElement('hidden', 'id', $item->get_id());
        $mform->setType('id', PARAM_INT);

        $mform->addElement('html', html_writer::empty_tag('hr'));

        $mform->addElement('header', "accesscontrols", get_string('menuitem:accesscontrols', 'totara_core'));
        $mform->setExpanded("accesscontrols", true, true);

        // The menu for the visibility between rulesets.
        $radiogroup = array();
        $radiogroup[] =& $mform->createElement('radio', 'item_visibility', '',
            get_string('menuitem:withrestrictionany', 'totara_core'), item::AGGREGATION_ANY);
        $radiogroup[] =& $mform->createElement('radio', 'item_visibility', '',
            get_string('menuitem:withrestrictionall', 'totara_core'), item::AGGREGATION_ALL);
        $mform->addGroup($radiogroup, 'item_visibility',
            get_string('menuitem:restrictaccess', 'totara_core'), html_writer::empty_tag('br'), false);
        $mform->setDefault('item_visibility', $item->get_setting('visibility_restriction', 'item_visibility'));
        $mform->addHelpButton('item_visibility', 'menuitem:accessmode', 'totara_core');
        $mform->setType('item_visibility', PARAM_INT);
        $mform->addRule('item_visibility', null, 'required', null, 'client');

        // Role selector element.
        $enable = $item->get_setting('role_access', 'enable');
        $activeroles = explode('|', $item->get_setting('role_access', 'active_roles'));
        $context = $item->get_setting('role_access', 'context');
        // Generate the check boxes for the access form.
        $mform->addElement('header', 'accessbyroles', get_string('menuitem:accessbyrole', 'totara_core'));

        if ($enable) {
            $mform->setExpanded('accessbyroles');
        }

        $mform->addElement('checkbox', 'role_enable', '', get_string('menuitem:accessbyrole', 'totara_core'));
        $mform->setType('role_enable', PARAM_INT);
        $mform->setDefault('role_enable', $enable);

        $aggregationoptions = array(
            item::AGGREGATION_ANY => get_string('any'),
            item::AGGREGATION_ALL => get_string('all'),
        );

        // Role aggregation.
        $mform->addElement('select', 'role_aggregation', get_string('menuitem:roleaggregation', 'totara_core'), $aggregationoptions);
        $mform->setDefault('role_aggregation', $item->get_setting('role_access', 'aggregation'));
        $mform->setType('role_aggregation', PARAM_INT);
        $mform->disabledIf('role_aggregation', 'accessenabled', 'eq', 0);
        $mform->disabledIf('role_aggregation', 'role_enable', 'notchecked');
        $mform->addHelpButton('role_aggregation', 'menuitem:roleaggregation', 'totara_core');

        $systemcontext = context_system::instance();
        $roles = role_get_names($systemcontext);
        if (!empty($roles)) {
            $contextoptions = array(
                'site' => get_string('menuitem:systemcontext', 'totara_core'),
                'any' => get_string('menuitem:anycontext', 'totara_core')
            );

            // Set context for role-based access.
            $mform->addElement('select', 'role_context', get_string('menuitem:context', 'totara_core'), $contextoptions);
            $mform->setDefault('role_context', $context);
            $mform->disabledIf('role_context', 'accessenabled', 'eq', 0);
            $mform->disabledIf('role_context', 'role_enable', 'notchecked');
            $mform->addHelpButton('role_context', 'menuitem:context', 'totara_core');

            $rolesgroup = array();
            foreach ($roles as $role) {
                $rolesgroup[] =& $mform->createElement('advcheckbox', "role_activeroles[{$role->id}]", '',
                    $role->localname, null, array(0, 1));
                if (in_array($role->id, $activeroles)) {
                    $mform->setDefault("role_activeroles[{$role->id}]", 1);
                }
            }
            $mform->addGroup($rolesgroup, 'roles', get_string('menuitem:roleswithaccess', 'totara_core'),
                html_writer::empty_tag('br'), false);
            $mform->disabledIf('roles', 'accessenabled', 'eq', 0);
            $mform->disabledIf('roles', 'role_enable', 'notchecked');
            $mform->addHelpButton('roles', 'menuitem:roleswithaccess', 'totara_core');
        } else {
            $mform->addElement('html', html_writer::tag('p', get_string('menuitem:norolesfound', 'totara_core')));
        }
        // End role selector.

        // Audience rule selector.
        $enable = $item->get_setting('audience_access', 'enable');

        // Add audiences picker for the access form.
        $mform->addElement('header', "accessbyaudiences", get_string('menuitem:accessbyaudience', 'totara_core'));
        if ($enable) {
            $mform->setExpanded('accessbyaudiences');
        }

        $mform->addElement('checkbox', 'audience_enable', '', get_string('menuitem:restrictaccessbyaudience', 'totara_core'));
        $mform->setType('audience_enable', PARAM_INT);
        $mform->setDefault('audience_enable', $enable);

        // Audience aggregation.
        $mform->addElement('select', 'audience_aggregation', get_string('menuitem:audienceaggregation', 'totara_core'), $aggregationoptions);
        $mform->setDefault('audience_aggregation', $item->get_setting('audience_access', 'aggregation'));
        $mform->setType('audience_aggregation', PARAM_INT);
        $mform->disabledIf('audience_aggregation', 'accessenabled', 'eq', 0);
        $mform->disabledIf('audience_aggregation', 'audience_enable', 'notchecked');
        $mform->addHelpButton('audience_aggregation', 'menuitem:audienceaggregation', 'totara_core');

        $visibleaudiences = $item->get_setting('audience_access', 'active_audiences');
        // The setting gets retrieved as a string which PARAM_SEQUENCE doesn't like.
        $visibleaudiences = str_replace("'", '', $visibleaudiences);
        $mform->addElement('hidden', 'cohortsvisible', $visibleaudiences);
        $mform->setType('cohortsvisible', PARAM_SEQUENCE);

        $audienceclass = new totara_cohort_visible_learning_cohorts();
        $instancetype = COHORT_ASSN_ITEMTYPE_MENU;
        $audienceclass->build_visible_learning_table($item->get_id(), $instancetype);
        $mform->addElement('html', $audienceclass->display(true, 'visible'));

        $mform->addElement('button', 'cohortsaddvisible', get_string('menuitem:addcohorts', 'totara_core'));
        $mform->disabledIf('cohortsaddvisible', 'audience_enable', 'notchecked');
        if ($visibleaudiences) {
            $mform->setExpanded('accessbyaudiences');
        }
        // End audience rule selector.

        // Preset rule selector.
        $mform->addElement('header', "accessbypreset", get_string('menuitem:accessbypreset', 'totara_core'));
        $enable = $item->get_setting('preset_access', 'enable');
        $activepresets = explode(',', $item->get_setting('preset_access', 'active_presets'));
        $availablepresets = item::get_visibility_preset_rule_choices();
        $incompatiblepresets = $item->get_incompatible_preset_rules();

        if ($enable) {
            $mform->setExpanded('accessbypreset');
        }

        $mform->addElement('checkbox', 'preset_enable', '', get_string('menuitem:accessbypreset', 'totara_core'));
        $mform->setType('preset_enable', PARAM_INT);
        $mform->setDefault('preset_enable', $enable);

        // Preset rule aggregation.
        $mform->addElement('select', 'preset_aggregation', get_string('menuitem:presetaggregation', 'totara_core'), $aggregationoptions);
        $mform->setDefault('preset_aggregation', $item->get_setting('preset_access', 'aggregation'));
        $mform->setType('preset_aggregation', PARAM_INT);
        $mform->disabledIf('preset_aggregation', 'accessenabled', 'eq', 0);
        $mform->disabledIf('preset_aggregation', 'preset_enable', 'notchecked');
        $mform->addHelpButton('preset_aggregation', 'menuitem:presetaggregation', 'totara_core');

        $presetgroup = array();
        foreach ($availablepresets as $name => $description) {
            if (in_array($name, $incompatiblepresets)) {
                continue;
            }
            $presetgroup[] =& $mform->createElement('advcheckbox', "preset_active_presets[{$name}]", '',
                $description, null, array(0, 1));
            if (in_array($name, $activepresets)) {
                $mform->setDefault("preset_active_presets[{$name}]", 1);
            }
        }
        $mform->addGroup($presetgroup, 'preset', get_string('menuitem:presetwithaccess', 'totara_core'),
            html_writer::empty_tag('br'), false);
        $mform->disabledIf('preset', 'accessenabled', 'eq', 0);
        $mform->disabledIf('preset', 'preset_enable', 'notchecked');
        $mform->addHelpButton('preset', 'menuitem:presetwithaccess', 'totara_core');
        // End preset rule selector.

        $this->add_action_buttons();
    }

    /**
     * Check for invalid rules.
     *
     * If there are errors return array of errors ("fieldname"=>"error message"),
     * otherwise true if ok.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = array();

        // Check if any of the restriction types are enabled.
        if (!isset($data['role_enable']) && !isset($data['audience_enable']) && !isset($data['preset_enable'])) {
            $errors['item_visibility'] = get_string('error:menuitemrulerequired', 'totara_core');
        }

        // Check if a role is selected.
        if (isset($data['role_enable']) && $data['role_enable'] == 1 && !in_array('1', $data['role_activeroles'], TRUE)) {
            $errors['role_enable'] = get_string('error:menuitemrulerolerequired', 'totara_core');
        }

        // Check if an audience is selected.
        if (isset($data['audience_enable']) && $data['audience_enable'] == 1 && empty($data['cohortsvisible'])) {
            $errors['audience_enable'] = get_string('error:menuitemruleaudiencerequired', 'totara_core');
        }

        // Check if a preset is selected.
        if (isset($data['preset_enable']) && $data['preset_enable'] == 1 && !in_array('1', $data['preset_active_presets'], TRUE)) {
            $errors['preset_enable'] = get_string('error:menuitemrulepresetrequired', 'totara_core');
        }

        return $errors;
    }
}
