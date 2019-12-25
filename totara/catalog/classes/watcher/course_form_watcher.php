<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\watcher;

use core_course\hook\{edit_form_definition_complete, edit_form_save_changes};
use core_course\totara_catalog\course;
use totara_catalog\local\catalog_storage;
use totara_catalog\search_metadata\search_metadata_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * A hook watcher to subscribe to the course related edit form hook. This watcher will try to add the search metadata
 * section element to the edit_course form. And also saving the term when the form is submitted, and re-index
 * the catalog.
 */
final class course_form_watcher {
    /**
     * A watcher to add search metadata section into the course.
     *
     * @param edit_form_definition_complete $hook
     * @return void
     */
    public static function add_searchmetadata_to_course_form(edit_form_definition_complete $hook): void {
        $form = $hook->form->_form;

        $form->insertElementBefore(
            $form->createElement(
                'header',
                'totara_catalog_searchmetadataheader',
                get_string('searchmetadata', 'totara_catalog')
            ),
            'buttonar'
        );

        $form->insertElementBefore(
            $form->createElement(
                'textarea',
                'totara_catalog_searchmetadata',
                get_string('searchterms', 'totara_catalog')
            ),
            'buttonar'
        );

        $form->insertElementBefore(
            $form->createElement(
                'static',
                'totara_catalog_search_metadata_help_text',
                null,
                get_string('searchterms_course_help', 'totara_catalog')
            ),
            'buttonar'
        );

        $form->setType('totara_catalog_searchmetadata', PARAM_TEXT);

        if (isset($hook->customdata['course'])) {
            $course = $hook->customdata['course'];

            if (!empty($course->id)) {
                $metadata = search_metadata_helper::find_searchmetadata('core_course', $course->id);

                if (null == $metadata) {
                    return;
                }

                $form->setDefault('totara_catalog_searchmetadata', $metadata->__toString());
            }
        }
    }

    /**
     * Once the form of editing course started to be saved (updating/creating). One of its hook will be triggered
     * and this watcher will watch for that hook to run updating/creating on the search_metadata of that course.
     *
     * Note that, because the event of updating/creating course would be executed before the hook triggered. Therefore,
     * we need to re-index the catalog storage after this upgrading keyword.
     *
     * @param edit_form_save_changes $hook
     * @return void
     */
    public static function process_searchmetadata_for_course(edit_form_save_changes $hook): void {
        $formdata = $hook->data;

        if (0 == $hook->courseid) {
            // We want null to be included here too.
            debugging("Unable to process search_metadata for course without id", DEBUG_DEVELOPER);
            return;
        }

        search_metadata_helper::process_searchmetadata(
            $formdata->totara_catalog_searchmetadata,
            'core_course',
            $hook->courseid
        );

        // Re-indexing the catalog storage.
        $catalog = new \stdClass();
        $catalog->objectid = $hook->courseid;
        $catalog->contextid = $hook->context->id;
        $catalog->objecttype = course::get_object_type();

        catalog_storage::update_records([$catalog]);
    }
}