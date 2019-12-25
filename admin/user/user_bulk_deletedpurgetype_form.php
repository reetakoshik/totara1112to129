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

use totara_userdata\userdata\manager;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

class user_bulk_deletedpurgetype_form extends \totara_form\form {
    public function definition() {
        global $DB, $SESSION;

        list($in, $params) = $DB->get_in_or_equal($SESSION->bulk_users);
        $rs = $DB->get_recordset_select('user', "id $in", $params, 'lastname ASC, firstname ASC', get_all_user_name_fields(true));
        $users = array();
        foreach ($rs as $user) {
            $users[] = fullname($user);
        }
        $rs->close();
        $users = implode(html_writer::empty_tag('br'), $users);

        $this->model->add(new \totara_form\form\element\static_html('staticusers', get_string('users'), $users));

        $options = manager::get_purge_types(target_user::STATUS_DELETED, 'deleted');
        if ($deleteddefault = get_config('totara_userdata', 'defaultdeletedpurgetypeid')) {
            $none = get_string('purgeautodefault', 'totara_userdata', $options[$deleteddefault]);
        } else {
            $none = get_string('none');
        }
        $options = array('' => $none) + $options;

        $deletedpurgetypeid = new \totara_form\form\element\select('deletedpurgetypeid', get_string('purgeorigindeletedbulkselect', 'totara_userdata'), $options);
        $this->model->add($deletedpurgetypeid);

        $this->model->add_action_buttons(true, get_string('applychanges', 'totara_userdata'));

        $this->model->add(new \totara_form\form\element\hidden('confirmhash', PARAM_ALPHANUM));
        $this->model->add(new \totara_form\form\element\hidden('loadconfirmform', PARAM_BOOL));
    }
}
