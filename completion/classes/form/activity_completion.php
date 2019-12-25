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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package core_completion
 */

namespace core_completion\form;

use totara_form\form\element\checkbox;
use totara_form\form\element\hidden;
use totara_form\form;
use totara_form\form\clientaction\onchange_ajaxsubmit;
use core_completion\form_controller\activity_completion_controller;

/**
 * Self activity completion formm
 *
 * @package   core_completion
 * @copyright 2017 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Brian Barnes <brian.barnes@totaralearning.com>
 */

class activity_completion extends form {

    /**
     * Returns class responsible for form handling.
     * This is intended especially for ajax processing.
     *
     * @return activity_completion_controller
     */
    public static function get_form_controller() {
        return new activity_completion_controller;
    }

    /**
     * Form definition.
     *
     * @return void
     */
    protected function definition() {
        $completed = new checkbox('completed', get_string('ihavecompleted','core_completion'));
        $this->model->add($completed);
        $this->model->add_clientaction(new onchange_ajaxsubmit($completed));

        $this->model->add(new hidden('activity_id', PARAM_INT));
    }

    /**
     * Form validation.
     *
     * @return array Containing any errors based on the input
     */
    protected function validation(array $data, array $files) {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $cm = get_coursemodule_from_id(null, $data['activity_id'], null, true, MUST_EXIST);
        if ($cm->completion != COMPLETION_TRACKING_MANUAL) {
            return array('completed' => get_string('cannotmanualctrack', 'error'));
        }
        return array();
    }

    /**
     * Mustache template.
     *
     * @return string
     */
    public function get_template() {
        return 'core_completion/activity_completion_form';
    }
 }