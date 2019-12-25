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
 * @package core_course
 */

namespace core_course\workflow_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Workflow manager singleton class for managing coursecreate workflow instances.
 */
class coursecreate extends \totara_workflow\workflow_manager\base {

    public function get_name(): string {
        return get_string('addnewcourse', 'moodle');
    }

    protected function can_access(): bool {
        global $CFG;

        $params = $this->get_params();

        if (!empty($params['category'])) {
            $category = $params['category'];
        } else {
            $category = $CFG->defaultrequestcategory;
        }

        require_once($CFG->dirroot . '/lib/coursecatlib.php');
        try {
            $cat = \coursecat::get($category);
        } catch (\moodle_exception $e) {
            // If the category is invisible to the user, it will be 'unknown' to coursecat::get().
            if ($e->errorcode == 'unknowncategory') {
                return false;
            } else {
                throw $e;
            }
        }
        return $cat->can_create_course();
    }

    /**
     * Defines data required by the workflow manager.
     * This data is included in the workflow URL and
     * workflow form (via hidden fields below).
     *
     * @return array Workflow manager data.
     */
    public function get_workflow_manager_data(): array {
        global $CFG;
        $category = optional_param('category', $CFG->defaultrequestcategory, PARAM_INT);
        $returnto = optional_param('returnto', 0, PARAM_ALPHANUM);
        $returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
        $data = ['category' => $category];
        if (!empty($returnto)) {
            $data['returnto'] = $returnto;
        }
        if (!empty($returnurl)) {
            $data['returnurl'] = $returnurl;
        }
        return $data;
    }

    /**
     * Defines workflow form elements required by the manager to pass
     * required data through the form.
     *
     * This should be called by the workflow form.
     *
     * @param \totara_form\model $model
     */
    public function add_workflow_manager_form_elements(\totara_form\model $model): void {
        $model->add(new \totara_form\form\element\hidden('category', PARAM_INT));
        $model->add(new \totara_form\form\element\hidden('returnto', PARAM_ALPHANUM));
        $model->add(new \totara_form\form\element\hidden('returnurl', PARAM_LOCALURL));
    }
}
