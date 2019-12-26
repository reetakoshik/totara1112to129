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

use totara_catalog\search_metadata\search_metadata_helper;
use totara_program\hook\{program_edit_form_save_changes, program_edit_form_definition_complete};

defined('MOODLE_INTERNAL') || die();

final class program_form_watcher {
    /**
     * A watcher to add the search_metadata form element into the program/certification edit form.
     *
     * @param program_edit_form_definition_complete $hook
     * @return void
     */
    public static function add_searchmetadata_to_program_form(program_edit_form_definition_complete $hook): void {
        $form = $hook->get_form()->_form;
        $action = $hook->get_action();

        if (!in_array($action, ['add', 'edit'])) {
            // If the action of the form is not either add or edit, then we should not add any element here at all.
            return;
        }

        $programid = $hook->get_programid();
        $metadata = null;

        if (0 < $programid) {
            // Totara program and certification are one, so lets keep it that way.
            $metadata = search_metadata_helper::find_searchmetadata('totara_program', $programid);
        }

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

        $identifier = 'searchterms_program_help';
        if ($hook->is_certification()) {
            $identifier = 'searchterms_certification_help';
        }

        $form->insertElementBefore(
            $form->createElement(
                'static',
                'totara_catalog_search_metadata_help_text',
                null,
                get_string($identifier, 'totara_catalog')
            ),
            'buttonar'
        );

        $form->setType('totara_catalog_searchmetadata', PARAM_TEXT);
        if (null != $metadata) {
            $form->setDefault('totara_catalog_searchmetadata', $metadata->__toString());
        }
    }

    /**
     * Program/certification are pretty much one type.
     *
     * @param program_edit_form_save_changes $hook
     * @return void
     */
    public static function process_searchmetadata_for_program(program_edit_form_save_changes $hook): void {
        $programid = $hook->get_programid();

        if (0 === $programid) {
            debugging("Cannot process the search metadata for program without id", DEBUG_DEVELOPER);
            return;
        }

        $formdata = $hook->get_form_data();
        search_metadata_helper::process_searchmetadata(
            $formdata->totara_catalog_searchmetadata,
            'totara_program',
            $programid
        );

        // Note: Do not re-indexing the catalog item for program or certification. This is because the hook for
        // saving program/certification form is being executed before the event.
    }
}