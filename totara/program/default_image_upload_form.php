<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package totara_program
 */

use totara_form\form;
use totara_form\form\element\filemanager;

/**
 * Class default_image_upload_form
 * defines the form for uploading a default image for programs and certifications.
 */
class default_image_upload_form extends form {

    /**
     * Defines the file upload and the action buttons
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function definition() {
        $defaultimage = $this->model->add(
            new filemanager(
                'defaultimage',
                get_string('imagedefault', 'totara_program'),
                [
                    'accept' => ['web_image'],
                    'maxfiles' => 1,
                    'subdirs' => 0,
                    'context' => context_system::instance()
                ]
            )
        );
        if ($this->parameters['iscertif'] == 0) {
            $defaultimage->add_help_button('imagedefault', 'totara_program');
        } else {
            $defaultimage->add_help_button('imagedefault', 'totara_certification');
        }
        $this->model->add_action_buttons(false);
    }

    /**
     * Makes sure that the iscertif parameter on the url is preserved
     *
     * @return moodle_url
     * @throws coding_exception
     */
    public function get_action_url() {
        $url = parent::get_action_url();
        $url->param('iscertif', $this->parameters['iscertif']);
        return $url;
    }
}