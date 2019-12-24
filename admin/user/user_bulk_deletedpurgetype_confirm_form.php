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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_userdata
 */

use totara_userdata\userdata\manager;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Class user_bulk_deletedpurgetype_confirm_form
 *
 * Confirm the details before updating the deleted purge type for a set of users.
 */
class user_bulk_deletedpurgetype_confirm_form extends \totara_form\form {

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function definition() {
        global $DB, $SESSION, $PAGE;
        $currentdata = (object)$this->model->get_current_data(null);

        /** @var \totara_userdata_renderer $renderer */
        $renderer = $PAGE->get_renderer('totara_userdata');

        list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
        $rs = $DB->get_recordset_select('user', "id $in", $params, 'lastname ASC, firstname ASC', get_all_user_name_fields(true));
        $users = array();
        foreach ($rs as $user) {
            $users[] = fullname($user);
        }
        $rs->close();
        $users = implode(', ', $users);

        $options = manager::get_purge_types(target_user::STATUS_DELETED, 'deleted');
        if (empty($currentdata->deletedpurgetypeid)) {
            if ($deleteddefault = get_config('totara_userdata', 'defaultdeletedpurgetypeid')) {
                $name = get_string('purgeautodefault', 'totara_userdata', $options[$deleteddefault]);
                // If there is a default and the item 'None' was selected, we'll list items for the default.
                $purgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $deleteddefault), '*', MUST_EXIST);
            } else {
                $name = get_string('none');
            }
        } else {
            $purgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $currentdata->deletedpurgetypeid), '*', MUST_EXIST);
            $name = $options[$currentdata->deletedpurgetypeid];
        }

        $detailshtml = $renderer->heading(get_string('bulksettingdetails', 'totara_userdata'), 3);
        $detailshtml .= '<dl class="dl-horizontal">';
        $detailshtml .= '<dt>' . get_string('selectedusers', 'totara_userdata') . '</dt><dd>'. $users . '</dd>';
        $detailshtml .= '<dt>' . get_string('purgetype', 'totara_userdata') . '</dt><dd>' . $name . '</dd></dl>';

        $this->model->add(new \totara_form\form\element\static_html('details', '', $detailshtml));

        $deletedpurgetypeid = new \totara_form\form\element\hidden('deletedpurgetypeid', PARAM_INT);
        $this->model->add($deletedpurgetypeid);

        $datatopurgehtml = $renderer->heading(get_string('purgeitemselection', 'totara_userdata'), 3);
        if (empty($purgetype)) {
            // 'None' was selected and there must be no default purge type.
            $datatopurgehtml .= get_string('noadditionaldatadeleted', 'totara_userdata');
        } else {

            $datatopurgehtml .= markdown_to_html(get_string('bulkoncedeleted', 'totara_userdata'));
            $datatopurgehtml .= $renderer->purge_type_active_items($purgetype);
        }

        $datatopurge = new \totara_form\form\element\static_html('datatopurge', '', $datatopurgehtml);
        $this->model->add($datatopurge);

        $this->model->add_action_buttons(true, get_string('bulkconfirmtypesetting', 'totara_userdata'));

        $this->model->add(new \totara_form\form\element\hidden('confirmhash', PARAM_ALPHANUM));
        $this->model->add(new \totara_form\form\element\hidden('loadconfirmform', PARAM_BOOL));
    }

    /**
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of ("fieldname"=>stored_file[]) of submitted files
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK
     * @throws coding_exception
     */
    protected function validation(array $data, array $files) {
        $errors = [];

        $options = manager::get_purge_types(target_user::STATUS_DELETED, 'deleted');
        if (!empty($data['deletedpurgetypeid']) and !isset($options[$data['deletedpurgetypeid']])) {
            $errors['deletedpurgetypeid'] = get_string('purgetypenolongerapply', 'totara_userdata');
        }

        return $errors;
    }
}
