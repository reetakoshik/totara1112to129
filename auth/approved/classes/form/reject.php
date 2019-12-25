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

namespace auth_approved\form;

use totara_form\form\element\hidden;
use totara_form\form\element\static_html;
use totara_form\form\element\textarea;

defined('MOODLE_INTERNAL') || die();

/**
 * Confirmation of request rejection.
 */
class reject extends \totara_form\form {
    public function definition() {

        $this->model->add(new static_html('sure', '', get_string('rejectsure', 'auth_approved')));

        $this->model->add(new hidden('requestid', PARAM_INT));
        $this->model->add(new hidden('reportid', PARAM_INT));

        $curretdata = $this->model->get_current_data(null);
        $details = \auth_approved\util::render_request_details_view($curretdata['requestid']);
        $this->model->add(new static_html('requestdetails', '', $details));

        $this->model->add(new textarea('custommessage', get_string('custommessage', 'auth_approved'), PARAM_CLEANHTML));

        $this->model->add_action_buttons(true, get_string('reject', 'auth_approved'));
    }
}
