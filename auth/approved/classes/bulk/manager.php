<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package auth_approved
 */

namespace auth_approved\bulk;

defined('MOODLE_INTERNAL') || die();

/**
 * Bulk manager change class.
 */
final class manager extends base {
    /**
     * Execute action.
     *
     * NOTE: this method is not supposed to return,
     *       so either render some form or redirect.
     *
     * @return void
     */
    public function execute() {
        global $OUTPUT, $DB;

        \auth_approved\util::init_job_assignment_fields();

        $requestids = $this->get_request_ids();

        $currentdata = array('bulkaction' => static::get_name(), 'bulktime' => $this->bulktime);
        $parameters = array('report' => $this->report, 'requestids' => $requestids, 'managerjaid' => '0');
        $customdata = array('currentdata' => $currentdata, 'parameters' => $parameters);
        $form = new \auth_approved\form\bulk_manager($this->report->get_current_url(), $customdata);

        if ($form->is_cancelled()) {
            redirect($this->get_return_url());
        }
        $data = $form->get_data();
        if (!$data) {
            echo $OUTPUT->header();
            echo $form->render();
            echo $OUTPUT->footer();
            die;
        }

        $changed = 0;
        $errors = 0;
        $requestids = $this->get_request_ids();
        foreach ($requestids as $id) {
            $request = $DB->get_record('auth_approved_request', array('id' => $id));
            if (!$request) {
                $errors++;
                continue;
            }
            $data->managerjaid = (int)$data->managerjaid;
            $request = \auth_approved\request::decode_signup_form_data($request);
            if ($request->managerjaid == $data->managerjaid) {
                $changed++;
                continue;
            }
            $request->managerjaid = $data->managerjaid;

            \auth_approved\request::update_request($request);
            $changed++;
        }

        if ($changed) {
            totara_set_notification(get_string('successmanagerbulk', 'auth_approved', $changed), null, array('class' => 'notifysuccess'));
        }
        if ($errors) {
            totara_set_notification(get_string('errormanagerbulk', 'auth_approved', $errors), null);
        }

        redirect($this->report->get_current_url());
    }

    /**
     * Is this action available for current user?
     *
     * @return bool
     */
    public static function is_available() {
        if (!parent::is_available()) {
            return false;
        }
        return has_capability('totara/hierarchy:assignuserposition', \context_system::instance());
    }
}
