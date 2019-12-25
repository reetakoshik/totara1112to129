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

defined('MOODLE_INTERNAL') || die();

class workflow_form extends \totara_form\form {

    /**
     * Workflow form definition.
     *
     * Workflow classes should use the define_form()
     * method to add elements to the form instead
     * of overriding this method.
     */
    public final function definition() {
        if (!isset($this->parameters['workflow'])) {
            throw new \coding_exception('Workflow must be passed as a parameter to ' . get_class($this));
        }
        /** @var \totara_workflow\workflow\base $workflow */
        $workflow = $this->parameters['workflow'];
        $this->model->add(new \totara_form\form\element\hidden('component', PARAM_COMPONENT));
        $this->model->add(new \totara_form\form\element\hidden('manager', PARAM_ALPHANUMEXT));
        $this->model->add(new \totara_form\form\element\hidden('workflow', PARAM_ALPHANUMEXT));
        $workflow->add_workflow_form_elements($this->model);
        $workflow->define_form($this->model);
    }
}
