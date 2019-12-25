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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\form;

use totara_userdata\userdata\target_user;
use totara_userdata\local\purge_type;

defined('MOODLE_INTERNAL') || die();

/**
 * Add and update purge type form.
 */
final class purge_type_edit extends \totara_form\form {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $DB, $OUTPUT;

        $purgetype = (object)$this->model->get_current_data(null);
        $newitems = \totara_userdata\local\purge_type::get_new_items($purgetype->id);

        $fullname = new \totara_form\form\element\text('fullname', get_string('fullname', 'totara_userdata'), PARAM_TEXT);
        $fullname->set_attributes(array('required'=> 1, 'maxlength' => 1333, 'size' => 100));
        $this->model->add($fullname);

        $idnumber = new \totara_form\form\element\text('idnumber', get_string('idnumber'), PARAM_NOTAGS);
        $idnumber->set_attributes(array('required'=> 1, 'maxlength' => 255));
        $this->model->add($idnumber);

        $options = target_user::get_user_statuses();
        $userstatus = new \totara_form\form\element\static_html('staticuserstatus', get_string('purgetypeuserstatus', 'totara_userdata'), $options[$purgetype->userstatus]);
        $this->model->add($userstatus);
        $this->model->add(new \totara_form\form\element\hidden('userstatus', PARAM_INT));

        $description = new \totara_form\form\element\editor('description', get_string('description'));
        $description->set_attributes(array('rows'=> 4));
        $this->model->add($description);

        $options = array('allowmanual' => get_string('purgeoriginmanual', 'totara_userdata'));
        if ($purgetype->userstatus == target_user::STATUS_SUSPENDED) {
            $options['allowsuspended'] = get_string('purgeoriginsuspended', 'totara_userdata');
            if ($purgetype->id) {
                $usercount = $DB->count_records('totara_userdata_user', array('suspendedpurgetypeid' => $purgetype->id));
                if ($usercount) {
                    $options['allowsuspended'] .= " ($usercount)";
                }
            }
        }
        if ($purgetype->userstatus == target_user::STATUS_DELETED) {
            $options['allowdeleted'] = get_string('purgeorigindeleted', 'totara_userdata');
            if ($purgetype->id) {
                $usercount = $DB->count_records('totara_userdata_user', array('deletedpurgetypeid' => $purgetype->id));
                if ($usercount) {
                    $options['allowdeleted'] .= " ($usercount)";
                }
            }
        }
        $availablefor = new \totara_form\form\element\checkboxes('availablefor', get_string('purgetypeavailablefor', 'totara_userdata'), $options);
        $availablefor->add_help_button('purgetypeavailablefor', 'totara_userdata');
        $this->model->add($availablefor);

        $purgeitemselection = new \totara_form\form\group\section('itemselection', get_string('purgeitemselection', 'totara_userdata'));
        $purgeitemselection->set_collapsible(false);
        $this->model->add($purgeitemselection);

        $itemdescription = new \totara_form\form\element\static_html('itemselection_desc', '', get_string('purgeitemselection_desc', 'totara_userdata'));
        $this->model->add($itemdescription);

        $groupeditems = \totara_userdata\local\purge::get_purgeable_items_grouped_list((int)$purgetype->userstatus);
        $grouplabels = \totara_userdata\local\util::get_sorted_grouplabels(array_keys($groupeditems));
        foreach ($grouplabels as $maincomponent => $grouplabel) {
            $items = $groupeditems[$maincomponent];
            $options = array();
            $optionhelps = array();
            /** @var \totara_userdata\userdata\item $item this is not an instance, but it helps with autocomplete */
            foreach ($items as $item) {
                $value = $item::get_component() . '-' . $item::get_name();
                $options[$value] = $item::get_fullname();
                if (isset($newitems[$value])) {
                    $options[$value] .= ' <span class="label label-info">' . get_string('newitem', 'totara_userdata') . '</span>';
                }
                if ($item::help_available()) {
                    list($identifier, $component) = $item::get_fullname_string();
                    $optionhelps[] = array($value, $identifier, $component);
                }
            }
            $group = new \totara_form\form\element\checkboxes('grp_' . $maincomponent, $grouplabel, $options);
            foreach ($optionhelps as $info) {
                list($value, $identifier, $component) = $info;
                $group->add_option_help($value, $identifier, $component);
            }
            $this->model->add($group);
        }

        if ($purgetype->id) {
            if ($purgetype->userstatus != target_user::STATUS_ACTIVE) {
                $repurge = new \totara_form\form\element\checkbox('repurge', get_string('repurge', 'totara_userdata'));
                $repurge->add_help_button('repurge', 'totara_userdata');
                $this->model->add($repurge);

                $repurgecount = purge_type::count_repurged_users($purgetype->id);
                if ($repurgecount) {
                    $warning = $OUTPUT->notification(get_string('repurgewarning', 'totara_userdata', $repurgecount), 'warning');
                    $repurgewarning = new \totara_form\form\element\static_html('repurgewarningstatic', '', $warning);
                    $this->model->add($repurgewarning);
                    $this->model->add_clientaction(new \totara_form\form\clientaction\hidden_if($repurgewarning))->is_equal($repurge, '0');
                }
            }
            $this->model->add_action_buttons(true, get_string('update'));
        } else {
            $this->model->add_action_buttons(true, get_string('add'));
        }

        $this->model->add(new \totara_form\form\element\hidden('id', PARAM_INT));
    }

    /**
     * Validation - makes sure the idnumber is unique and type is not used anywhere before unsetting availablefor.
     *
     * @param array $data
     * @param array $files
     * @return array list of errors
     */
    public function validation(array $data, array $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $existing = $DB->get_record_select('totara_userdata_purge_type', "LOWER(idnumber) = LOWER(:idnumber)", array('idnumber' => $data['idnumber']));
        if ($existing and $existing->id != $data['id']) {
            $errors['idnumber'] = get_string('errorduplicateidnumber', 'totara_userdata');
        }

        $defaultsuspendedpurgetypeid = get_config('totara_userdata', 'defaultsuspendedpurgetypeid');
        if ($defaultsuspendedpurgetypeid and $data['id'] == $defaultsuspendedpurgetypeid) {
            if (!in_array('allowsuspended', $data['availablefor'])) {
                $errors['availablefor'] = get_string('defaultsuspendedpurgetypeerror', 'totara_userdata');
            }
        }

        $defaultdeletedpurgetypeid = get_config('totara_userdata', 'defaultdeletedpurgetypeid');
        if ($defaultdeletedpurgetypeid and $data['id'] == $defaultdeletedpurgetypeid) {
            if (!in_array('allowdeleted', $data['availablefor'])) {
                $errors['availablefor'] = get_string('defaultdeletedpurgetypeerror', 'totara_userdata');
            }
        }

        return $errors;
    }

}
