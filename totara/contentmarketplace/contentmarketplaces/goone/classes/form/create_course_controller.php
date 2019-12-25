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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone\form;

final class create_course_controller extends \totara_form\form_controller {

    /** @var create_course_form $form */
    protected $form;

    /**
     * This method is responsible for:
     *  - access control
     *  - getting of current data
     *  - getting of parameters
     *
     * and returning of the form instance.
     *
     * @param string $idsuffix string extra for identifier to allow repeated forms on one page
     * @return create_course_form
     */
    public function get_ajax_form_instance($idsuffix) {

        if (!defined('AJAX_SCRIPT') || AJAX_SCRIPT !== true) {
            throw new \coding_exception('This method can only be called by AJAX scripts');
        }

        require_login(null, false, null, false, true);
        require_sesskey();

        $selection = required_param_array('selection', PARAM_ALPHANUMEXT);
        $create = optional_param('create', create_course_form::CREATE_COURSE_MULTI_ACTIVITY, PARAM_INT);
        $category = optional_param('category', 0, PARAM_INT);
        $mode = optional_param('mode', \totara_contentmarketplace\explorer::MODE_CREATE_COURSE, PARAM_ALPHAEXT);

        if ($category === 0) {
            $context = \context_system::instance();
        } else {
            $context = \context_coursecat::instance($category);
        }
        require_capability('totara/contentmarketplace:add', $context);

        list($currentdata, $params) = self::get_current_data_and_params($selection, $create, $category, $mode);
        $this->form = new create_course_form($currentdata, $params, $idsuffix);

        return $this->form;
    }

    /**
     * Process the submitted form.
     *
     * @return array processed data
     */
    public function process_ajax_data() {
        $result = array();
        $result['data'] = (array)$this->form->get_data();
        return $result;
    }

    public static function get_current_data_and_params($selection, $create, $category, $mode) {
        global $CFG;
        require_once($CFG->libdir. '/coursecatlib.php');

        $api = new \contentmarketplace_goone\api();
        $courses = [];
        $courselist = []; // Like a select list for the list of courses.
        foreach ($selection as $id) {
            $learningobject = $api->get_learning_object($id);
            $courses[] = [
                "title" => $learningobject->title,
                "id" => $id,
            ];
            $courselist[$id] = $learningobject->title;
        }

        $currentdata = [];
        $currentdata['create'] = $create;
        $currentdata['mode'] = $mode;

        $categorylist = \coursecat::make_categories_list('totara/contentmarketplace:add');
        if (count($categorylist) == 1) {
            // Only one category so can select that.
            $category = array_keys($categorylist)[0];
        } else if (!array_key_exists($category, $categorylist)) {
            // Multiple categories. Explicitly get the user to select one.
            $category = 0;
            $categorylist = [0 => ''] + $categorylist;
        }

        if (count($selection) == 1 || $create == create_course_form::CREATE_COURSE_SINGLE_ACTIVITY) {
            foreach ($courses as $course) {
                $currentdata['fullname_' . $course['id']] = $course['title'];
                $currentdata['shortname_' . $course['id']] = $course['title'];
                $currentdata['category_' . $course['id']] = $category;
            }
            $currentdata['fullname'] = $courses[0]['title'];
            $currentdata['shortname'] = $courses[0]['title'];
            $currentdata['category'] = $category;
        }
        $currentdata['selection'] = $selection;

        $params = [
            'totalselected' => count($selection),
            'selection' => $selection,
            'create' => $create,
            'courses' => $courselist,
            'categorylist' => $categorylist,
        ];
        foreach ($courses as $course) {
            $params['section_' . $course['id']] = $course['title'];
        }

        return array($currentdata, $params);
    }

}
