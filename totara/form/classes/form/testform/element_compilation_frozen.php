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
use totara_form\form\group\section;

/**
 * Element compilation test form.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class element_compilation_frozen extends form {

    /**
     * The form name.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Compilation of frozen elements';
    }

    /**
     * Default data.
     * @return array
     */
    public static function get_current_data_for_test() {
        global $USER, $CFG;

        // Needed beca
        require_once($CFG->libdir.'/filelib.php');

        $usercontext = \context_user::instance($USER->id);
        $fs = get_file_storage();
        $fs->delete_area_files($usercontext->id, 'user', 'test_filemanager');
        $fs->create_directory($usercontext->id, 'user', 'test_filemanager', 7, '/', $USER->id);
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'test_filemanager',
            'itemid' => 7,
            'filepath' => '/',
            'filename' => 'bonus.txt',
            'userid' => $USER->id
        );
        $fs->create_file_from_string($record, 'File Manager test');

        return [
            'checkbox' => 'checked',
            'checkboxes' => ['1','-1'],
            'datetime_tz' => 1457346240,
            'datetime' => 1457346240,
            'editor' => '<div><h2>Title</h2><p>Some random text, Some random text<br />Some random text, Some random text</p></div>',
            'email' => 'admin@example.com',
            'filemanager' => new \totara_form\file_area($usercontext, 'user', 'test_filemanager', 7),
            'filepicker' => new \totara_form\file_area($usercontext, 'user', 'test_filepicker', 7),
            'hidden' => 'Invisible',
            'multiselect' => ['orange','green'],
            'number' => 73.48,
            'passwordunmask' => 'Secr3t!',
            'radio' => 'true',
            'select' => 'orange',
            'statichtml' => 'This is a super bit of <strong>static</strong> html',
            'tel' => '+202-555-0174',
            'text' => 'Totara 9.0',
            'textarea' => 'Some random text, Some random text, Some random text, Some random text',
            'utc10date' => 1485338400,
            'url' => 'https://www.totaralms.com',
            'yesno' => '1',
        ];
    }

    /**
     * Definition
     */
    public function definition() {
        global $USER;

        $usercontext = \context_user::instance($USER->id);

        $this->model->add(new section('novalue', 'Frozen elements with current data'));

        $this->model->add(new checkbox('checkbox', 'Checkbox', 'checked', 'empty'))->set_frozen(true);
        $this->model->add(new checkboxes('checkboxes', 'Checkboxes', ['1' => 'Yes', '0' => 'No', '-1' => 'Maybe']))->set_frozen(true);
        $this->model->add(new datetime('datetime', 'Date and time'))->set_frozen(true);
        $this->model->add(new datetime('datetime_tz', 'Date and time with TZ', 'Indian/Reunion'))->set_frozen(true);
        $this->model->add(new editor('editor', 'Editor'))->set_frozen(true);
        $this->model->add(new email('email', 'Email'))->set_frozen(true);
        $this->model->add(new filemanager('filemanager', 'File manager'))->set_frozen(true);
        $this->model->add(new filepicker('filepicker', 'File picker', ['context' => $usercontext]))->set_frozen(true);
        $this->model->add(new hidden('hidden', PARAM_ALPHANUM))->set_frozen(true);
        $this->model->add(new multiselect('multiselect', 'Multiselect', [
            'red' => 'Red',
            'orange' => 'Orange',
            'yellow' => 'Yellow',
            'green' => 'Green',
            'blue' => 'Blue',
        ]))->set_frozen(true);;
        $this->model->add(new number('number', 'Number'))->set_frozen(true);
        $this->model->add(new passwordunmask('passwordunmask', 'Password unmask'))->set_frozen(true);
        $this->model->add(new radios('radios', 'Radios', ['true' => 'Agree', 'false' => 'Disagree']))->set_frozen(true);
        $this->model->add(new select('select', 'Select', [
            'apple' => 'Apple',
            'orange' => 'Orange',
            'pear' => 'Pear',
            'raspberry' => 'Raspberry',
            'persimmon' => 'Persimmon',
        ]))->set_frozen(true);;
        $this->model->add(new static_html('statichtml', 'Static HTML', '<div style="background-color:#002a80;color:#FFF;padding:6px;">Static HTML test</div>'))->set_frozen(true);
        $this->model->add(new tel('tel', 'Tel'))->set_frozen(true); // Just a random help string.
        $this->model->add(new text('text', 'Text', PARAM_RAW))->set_frozen(true); // Just a random help string.
        $this->model->add(new textarea('textarea', 'Textarea', PARAM_RAW))->set_frozen(true);
        $this->model->add(new url('url', 'Web URL'))->set_frozen(true);
        $this->model->add(new utc10date('utc10date', 'UTC10 Date'))->set_frozen(true);
        $this->model->add(new yesno('yesno', 'Yes or No'))->set_frozen(true);

        $this->model->add(new section('value', 'Frozen elements without values'));

        $this->model->add(new checkbox('checkbox_novalue', 'Checkbox', 'checked', 'empty'))->set_frozen(true);
        $this->model->add(new checkboxes('checkboxes_novalue', 'Checkboxes', ['1' => 'Yes', '0' => 'No', '-1', 'Maybe']))->set_frozen(true);
        $this->model->add(new datetime('datetime_tz_novalue', 'Date and time', 'Arctic/Longyearbyen'))->set_frozen(true);
        $this->model->add(new datetime('datetime_novalue', 'Date and time'))->set_frozen(true);
        $this->model->add(new editor('editor_novalue', 'Editor'))->set_frozen(true);
        $this->model->add(new email('email_novalue', 'Email'))->set_frozen(true);
        $this->model->add(new filemanager('filemanager_novalue', 'File manager'))->set_frozen(true);
        $this->model->add(new filepicker('filepicker_novalue', 'File picker'))->set_frozen(true);
        $this->model->add(new hidden('hidden_novalue', PARAM_ALPHANUM))->set_frozen(true);
        $this->model->add(new multiselect('multiselect_novalue', 'Multiselect', [
            'red' => 'Red',
            'orange' => 'Orange',
            'yellow' => 'Yellow',
            'green' => 'Green',
            'blue' => 'Blue',
        ]))->set_frozen(true);;
        $this->model->add(new number('number_novalue', 'Number'))->set_frozen(true);
        $this->model->add(new passwordunmask('passwordunmask_novalue', 'Password unmask'))->set_frozen(true);
        $this->model->add(new radios('radios_novalue', 'Radios', ['true' => 'Agree', 'false' => 'Disagree']))->set_frozen(true);
        $this->model->add(new select('select_novalue', 'Select', [
            'apple' => 'Apple',
            'orange' => 'Orange',
            'pear' => 'Pear',
            'raspberry' => 'Raspberry',
            'persimmon' => 'Persimmon',
        ]))->set_frozen(true);;
        $this->model->add(new static_html('statichtml_novalue', 'Static HTML', '<div style="background-color:#002a80;color:#FFF;padding:6px;">Static HTML test</div>'))->set_frozen(true);
        $this->model->add(new tel('tel_novalue', 'Tel'))->set_frozen(true); // Just a random help string.
        $this->model->add(new text('text_novalue', 'Text', PARAM_RAW))->set_frozen(true); // Just a random help string.
        $this->model->add(new textarea('textarea_novalue', 'Textarea', PARAM_RAW))->set_frozen(true);
        $this->model->add(new url('url_novalue', 'Web URL'))->set_frozen(true);
        $this->model->add(new utc10date('utc10date_novalue', 'UTC10 Date'))->set_frozen(true);
        $this->model->add(new yesno('yesno_novalue', 'Yes or No'))->set_frozen(true);

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
