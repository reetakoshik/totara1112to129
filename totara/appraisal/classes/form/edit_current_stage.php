<?php
/*
 * This file is part of Totara LMS
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_appraisal
 */

namespace totara_appraisal\form;

use \totara_form\form;
use \totara_form\form\element\hidden;
use \totara_form\form\element\select;
use \totara_form\form\element\static_html;

global $CFG;
require_once($CFG->dirroot.'/totara/appraisal/lib.php');

class edit_current_stage extends form {

    public static function get_current_data_and_params($appraisalid, $learnerid) {
        global $DB;

        // Appraisal name.
        $appraisal = new \appraisal($appraisalid);

        // Learner name.
        $user = $DB->get_record('user', array('id' => $learnerid));
        $learnername = fullname($user);

        // Roles that can be selected.
        $rolekeys = \appraisal::get_roles();
        $userassignment = \appraisal_user_assignment::get_user($appraisalid, $learnerid);
        $usernamefields = get_all_user_name_fields(true, 'u');
        $sql = "SELECT ara.id, ara.appraisalrole, {$usernamefields}
                  FROM {appraisal_role_assignment} ara
                  JOIN {user} u ON ara.userid = u.id
                 WHERE ara.userid > 0
                   AND ara.appraisaluserassignmentid = :userassignmentid
                 ORDER BY appraisalrole ASC";
        $params = array(
            'userassignmentid' => $userassignment->id,
        );
        $roledata = $DB->get_records_sql($sql, $params);
        $roles = array(-1 => get_string('allroles', 'totara_appraisal'));
        foreach ($roledata as $role) {
            $roles[$role->id] = get_string($rolekeys[$role->appraisalrole], 'totara_appraisal') . ' - ' . fullname($role);
        }

        // Stages that can be selected.
        $stages = \appraisal_stage::get_stages($appraisalid);
        $activestageid = $userassignment->activestageid;
        $selectablestages = [];
        foreach ($stages as $stage) {
            $selectablestages[$stage->id] = $stage->name;
            if ($stage->id == $activestageid) {
                break;
            }
        }

        return array(
            array(
                'appraisalid' => $appraisalid,
                'learnerid' => $learnerid,
            ),
            array(
                'appraisalname' => $appraisal->name,
                'learnername' => $learnername,
                'roles' => $roles,
                'stages' => $selectablestages,
            ),
        );
    }

    protected function definition() {
        // Form keys.
        $this->model->add(new hidden('appraisalid', PARAM_INT));
        $this->model->add(new hidden('learnerid', PARAM_INT));

        // Static info.
        $this->model->add(
            new static_html(
                'appraisal',
                get_string('appraisal', 'totara_appraisal'),
                $this->get_parameters()['appraisalname']
            )
        );

        $this->model->add(
            new static_html(
                'learner',
                get_string('rolelearner', 'totara_appraisal'),
                $this->get_parameters()['learnername']
            )
        );

        // Role / user.
        $this->model->add(
            new select(
                'roleassignmentid',
                get_string('role_to_change', 'totara_appraisal'),
                $this->get_parameters()['roles']
            )
        );

        // Stage to change to.
        $this->model->add(
            new select(
                'stageid',
                get_string('edit_current_stage_select', 'totara_appraisal'),
                $this->get_parameters()['stages']
            )
        );

        // Buttons.
        $this->model->add_action_buttons(true, get_string('savestagechanges', 'totara_appraisal'));
    }

    protected function validation(array $data, array $files) {
        $errors = parent::validation($data, $files);

        $roleassignment = new \appraisal_role_assignment($data['roleassignmentid']);
        $stages = \appraisal_stage::get_stages($data['appraisalid'], $roleassignment->appraisalrole, \appraisal::ACCESS_CANANSWER);

        if (empty($stages[$data['stageid']])) {
            $errors['unlock_role_stage_mismatch'] = get_string('unlock_role_stage_mismatch', 'totara_appraisal');
        }

        return $errors;
    }
}
