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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

namespace totara_form\test;

use totara_form\model;

/**
 * Wrapper for the test definition method.
 *
 * This allows us to inspect the model and individual elements in phpunit tests.
 */
class test_definition {
    /**
     * The form model, this can be used later to inspect the form.
     * @var \totara_form\model $model
     */
    public $model;

    /**
     * Usually a function with $testcase and $model parameters.
     * @var callable $definition
     **/
    protected $callback;

    /**
     * This test case can be used to add asserts in the definition itself.
     * @var \advanced_testcase $testcase
     */
    public $testcase;

    /**
     * Set up definition to be tested.
     *
     * @param \advanced_testcase $testcase
     * @param callable $callback
     */
    public function __construct(\advanced_testcase $testcase, $callback) {
        $this->callback = $callback;
        $this->testcase = $testcase;
    }

    /**
     * NOTE: Do not call directly.
     *
     * @param \totara_form\model $model
     * @param array $parameters
     */
    public function definition(model $model, array $parameters) {
        $this->model = $model;
        call_user_func($this->callback, $this->model, $this->testcase, $parameters);
    }

    /**
     * Fetch element with given name.
     *
     * @param $elname
     * @return \totara_form\element
     */
    public function get_element($elname) {
        return $this->model->find($elname, 'get_name', 'totara_form\element', true);
    }
}
