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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\testform;

use totara_form\form_controller;

class clientaction_onchange_ajaxsubmit_controller_radios extends form_controller {

    private $form;

    public function get_ajax_form_instance($idsuffix) {

        require_login();
        require_sesskey();
        require_capability('moodle/site:config', \context_system::instance());

        // Get the current data from id parameter.
        $currentdata = clientaction_onchange_ajaxsubmit_radios::get_current_data_for_test();
        $currentdata['form_select'] = 'totara_form\form\testform\clientaction_onchange_ajaxsubmit_radios';

        $this->form = new clientaction_onchange_ajaxsubmit_radios($currentdata, [], $idsuffix);
        return $this->form;

    }

    public function process_ajax_data() {
        $result = array();
        $result['data'] = (array)$this->form->get_data();
        $result['files'] = array();
        return $result;
    }

}

