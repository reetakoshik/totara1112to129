<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage totara_question
 */

class question_text extends question_base{

    /**
     * Maximum length of value that can be saved via this question type.
     */
    const MAX_VALUE_LENGTH = 255;

    public static function get_info() {
        return array('group' => question_manager::GROUP_QUESTION,
                     'type' => get_string('questiontypetext', 'totara_question'));
    }

    /**
     * Add database fields definition that represent current question
     *
     * @see question_base::get_xmldb()
     * @return array()
     */
    public function get_xmldb() {
        $fields = array();
        // We don't use XMLDB_TYPE_CHAR here so we can have more columns when dynamically generating tables. See TL-17647.
        $fields[$this->get_prefix_form()] = new xmldb_field($this->get_prefix_db(), XMLDB_TYPE_TEXT);
        return $fields;
    }

    /**
     * Add form elements that represent current field
     *
     * @see question_base::add_field_specific_edit_elements()
     * @param MoodleQuickForm $form Form to alter
     */
    public function add_field_specific_edit_elements(MoodleQuickForm $form) {
        $form->addElement('text', $this->get_prefix_form(), $this->label);
        $form->addRule($this->get_prefix_form(), get_string('maximumchars', '', self::MAX_VALUE_LENGTH), 'maxlength', self::MAX_VALUE_LENGTH);
        if ($this->required) {
            $form->addRule($this->get_prefix_form(), get_string('required'), 'required');
        }
    }

    /**
     * If this element has any answerable form fields, or it's a view only (informational or static) element.
     *
     * @see question_base::is_answerable()
     * @return bool
     */
    public function is_answerable() {
        return true;
    }
}
