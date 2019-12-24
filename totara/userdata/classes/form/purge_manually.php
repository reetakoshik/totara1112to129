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

use totara_userdata\userdata\manager;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Manaul purging request.
 */
final class purge_manually extends \totara_form\form {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $DB, $PAGE;
        $currentdata = (object)$this->model->get_current_data(null);

        $user = $DB->get_record('user', array('id' => $currentdata->id));

        /** @var \totara_userdata_renderer $renderer */
        $renderer = $PAGE->get_renderer('totara_userdata');
        $this->model->add(new \totara_form\form\element\static_html('staticidcard', '', $renderer->user_id_card($user, true)));

        $targetuser = new target_user($user);
        $options = array('' => get_string('choosedots')) + manager::get_purge_types($targetuser->status, 'manual');
        $purgetypeid = new \totara_form\form\element\select('purgetypeid', get_string('purgetype', 'totara_userdata'), $options);
        $purgetypeid->set_attribute('required', 1);
        $this->model->add($purgetypeid);

        $this->model->add_action_buttons(true, get_string('purgemanually', 'totara_userdata'));

        $this->model->add(new \totara_form\form\element\hidden('id', PARAM_INT));
    }

    /**
     * Validation - makes sure the same purging is not already pending.
     *
     * @param array $data
     * @param array $files
     * @return array list of errors
     */
    public function validation(array $data, array $files) {
        $errors = parent::validation($data, $files);
        $syscontext = \context_system::instance();

        if (\totara_userdata\local\purge::is_execution_pending('manual', $data['purgetypeid'], $data['id'], $syscontext->id)) {
            $errors['purgetypeid'] = get_string('purgeispending', 'totara_userdata');
        }

        return $errors;
    }

    /**
     * Was this form submitted in any way?
     * @return bool
     */
    public function is_submitted() {
        if (!$this->model->is_finalised()) {
            throw new \coding_exception('is_submitted() cannot be used before the model is finalised');
        }

        return $this->model->is_form_submitted();
    }
}
