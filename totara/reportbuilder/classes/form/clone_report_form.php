<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\form;

use \totara_form\form\element\hidden;

class clone_report_form extends \totara_form\form {
    protected function definition() {
        $this->model->add(new hidden('id', PARAM_INT));
        $this->model->add(new hidden('returnurl', PARAM_LOCALURL));
        $this->model->add_action_buttons(true, get_string('clone', 'totara_reportbuilder'));
    }
}
