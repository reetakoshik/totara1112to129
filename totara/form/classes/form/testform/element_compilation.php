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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_form
 */

namespace totara_form\form\testform;

use totara_form\form\element\checkbox;
use totara_form\form\element\checkboxes;
use totara_form\form\element\datetime;
use totara_form\form\element\editor;
use totara_form\form\element\email;
use totara_form\form\element\filemanager;
use totara_form\form\element\filepicker;
use totara_form\form\element\hidden;
use totara_form\form\element\multiselect;
use totara_form\form\element\number;
use totara_form\form\element\passwordunmask;
use totara_form\form\element\radios;
use totara_form\form\element\select;
use totara_form\form\element\static_html;
use totara_form\form\element\tel;
use totara_form\form\element\text;
use totara_form\form\element\textarea;
use totara_form\form\element\url;
use totara_form\form\element\utc10date;
use totara_form\form\element\yesno;

/**
 * Element compilation test form.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class element_compilation extends form {

    /**
     * The form name.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Compilation of elements';
    }

    /**
     * Default data.
     * @return array
     */
    public static function get_current_data_for_test() {
        return [
            'hidden' => 'Invisible'
        ];
    }

    /**
     * Definition
     */
    public function definition() {

        $this->model->add(new checkbox('checkbox', 'Checkbox', 'checked', 'empty'));
        $this->model->add(new checkboxes('checkboxes', 'Checkboxes', ['1' => 'Yes', '0' => 'No', '-1', 'Maybe']));
        $this->model->add(new datetime('datetime', 'Date and time'));
        $this->model->add(new datetime('datetime_tz', 'Date and time with TZ', 'Indian/Reunion'));
        $this->model->add(new editor('editor', 'Editor'));
        $this->model->add(new email('email', 'Email'));
        $this->model->add(new filemanager('filemanager', 'File manager'));
        $this->model->add(new filepicker('filepicker', 'File picker'));
        $this->model->add(new hidden('hidden', PARAM_ALPHANUM));
        $this->model->add(new multiselect('multiselect', 'Multiselect', [
            'red' => 'Red',
            'orange' => 'Orange',
            'yellow' => 'Yellow',
            'green' => 'Green',
            'blue' => 'Blue',
        ]));
        $this->model->add(new number('number', 'Number'));
        $this->model->add(new passwordunmask('passwordunmask', 'Password unmask'));
        $this->model->add(new radios('radios', 'Radios', ['true' => 'Agree', 'false' => 'Disagree']));
        $this->model->add(new select('select', 'Select', [
            'apple' => 'Apple',
            'orange' => 'Orange',
            'pear' => 'Pear',
            'raspberry' => 'Raspberry',
            'persimmon' => 'Persimmon',
        ]));
        $this->model->add(new static_html('statichtml', 'Static HTML', '<div style="background-color:#002a80;color:#FFF;padding:6px;">Static HTML test</div>'));
        $this->model->add(new tel('tel', 'Tel'))->add_help_button('cachejs', 'core_admin'); // Just a random help string.;
        $this->model->add(new text('text', 'Text', PARAM_RAW))->add_help_button('cachejs', 'core_admin'); // Just a random help string.;
        $this->model->add(new textarea('textarea', 'Textarea', PARAM_RAW));
        $this->model->add(new url('url', 'Web URL'));
        $this->model->add(new utc10date('utc10date', 'UTC10 Date'));
        $this->model->add(new yesno('yesno', 'Yes or No'));

        $this->add_required_elements();
    }

    /**
     * Post submit pre display formatting.
     * @param \stdClass $data
     * @return \stdClass
     */
    public static function process_after_submit(\stdClass $data) {
        if (!empty($data->datetime)) {
            $data->datetime .= ' (' . date('Y/m/d H:i', $data->datetime) . ' ' . \core_date::get_user_timezone() . ')';
        }
        if (!empty($data->datetime_tz)) {
            $data->datetime_tz .= ' (' .date('Y/m/d H:i', $data->datetime_tz) . ' ' . \core_date::get_user_timezone() . ')';
        }
        if (!empty($data->utc10date)) {
            $date = new \DateTime('@' . $data->utc10date);
            $data->utc10date .= ' (' . $date->format('Y/m/d') . ')';
        }
        return parent::process_after_submit($data);
    }
}
