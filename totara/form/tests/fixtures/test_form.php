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

use totara_form\form;

require_once(__DIR__  . '/test_definition.php');

/**
 * This form can be used to test definition without creating new classes.
 *
 * Example:
 *
 *  $definition = function($model) {
 *       // define the form here
 *       $model->add(new text('sometext', 'Some text', PARAM_RAW));
 *  };
 *  $definition = new test_definition($this, $definition);
 *  \totara_form\test\test_form::phpunit_set_definition($definition);
 *  \totara_form\test\test_form::phpunit_set_post_data(array('sometext' => 'yy'));
 *  $form = new \totara_form\test\test_form(array('sometext' => 'zz'));
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class test_form extends form {
    /** @var test_definition $definition */
    protected static $definition;

    public static function phpunit_reset() {
        self::$definition = null;
        $_POST = array();
    }

    /**
     * Set form definition callback for the next form constructor call.
     *
     * @param test_definition $definition
     * @return test_definition
     */
    public static function phpunit_set_definition(test_definition $definition = null) {
        self::$definition = $definition;
        $_POST = array();
        return self::$definition;
    }

    /**
     * Set global _POST data to emulate form submission.
     *
     * The idsuffix must be set for all forms, by default if no idsuffix is given to the form then the classname of the form
     * is used to generate an idsuffix unique to the form.
     * If you leave it blank then we will assume it is an instance of this test_form.
     *
     * @param array|null $post null means no _POST
     * @param string $idsuffix
     */
    public static function phpunit_set_post_data(array $post = null, $idsuffix = '') {
        if (is_null($post)) {
            $_POST = array();
            return;
        }
        if (empty($idsuffix)) {
            /** This gets set when constructing the model at {@see \totara_form\model::__construct()} **/
            $idsuffix = 'totara_form_test_test_form';
        }

        $_POST = array();
        foreach ($post as $k => $v) {
            $_POST[$k] = $v;
        }

        $_POST['sesskey'] = sesskey();
        $_POST['___tf_formclass'] = __CLASS__;
        $_POST['___tf_idsuffix'] = $idsuffix;
    }

    /**
     * NOTE: Do not call directly!
     */
    public function definition() {
        if (self::$definition) {
            self::$definition->definition($this->model, $this->get_parameters());
        }
    }
}
