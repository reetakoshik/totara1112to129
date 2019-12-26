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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_workflow
 */

namespace totara_workflow\form;

abstract class workflow_form_controller extends \totara_form\form_controller {

    /**
     * @var \totara_workflow\workflow\base $workflow
     */
    private $workflow;

    /**
     * @var \totara_workflow\form\workflow_form $form
     */
    protected $form;

    public function __construct() {
        $this->workflow = $this->get_workflow();
    }

    /**
     * Controller implementation must define this
     * method to return a workflow instance.
     */
    abstract public function get_workflow(): \totara_workflow\workflow\base;

    public function get_ajax_form_instance($idsuffix) {
        require_login();
        require_sesskey();
        $params = $this->workflow->get_workflow_manager_data();
        $this->workflow->set_params($params);
        if (!$this->workflow->is_available()) {
            print_error('accessdenied', 'admin');
        }
        $formclass = $this->workflow->get_form_name();
        $currentdata = $this->workflow->get_current_data();
        $this->form =  new $formclass($currentdata, ['workflow' => $this->workflow], $idsuffix);
        return $this->form;
    }

    public function process_ajax_data() {
        $data = $this->form->get_data();
        $files = (array)$this->form->get_files();
        $this->workflow->process_form($data, $files);
    }
}
