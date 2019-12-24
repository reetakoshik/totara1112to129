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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_gap
 */

namespace totara_gap\watcher;

use \core_user\hook\editadvanced_form_definition_complete;
use \core_user\hook\editadvanced_form_save_changes;
use \core_user\hook\editadvanced_form_display;

global $CFG;
require_once($CFG->dirroot.'/totara/gap/lib.php');

/**
 * Class for managing User edit form hooks.
 *
 * This class manages watchers for three hooks:
 *
 *    1. \core_user\hook\editadvanced_form_definition_complete
 *        Gets called at the end of the user_editadvanced_form definition.
 *        Through this watcher we can make any adjustments to the form definition we want, including adding
 *        Totara specific elements.
 *
 *    2. \core_user\hook\editadvanced_form_save_changes
 *        Gets called after the form has been submit and the initial saving has been done, before the user is redirected.
 *        Through this watcher we can save any custom element data we need to.
 *
 *    3. \core_user\hook\editadvanced_form_display
 *        Gets called immediately before the form is displayed and is used to initialise any required JS.
 *
 * @package totara_core\watcher
 */
class core_user_editadvanced_form {

    /**
     * Hook watcher that extends the user edit form with Totara specific elements.
     *
     * @param editadvanced_form_definition_complete $hook
     */
    public static function extend_form(editadvanced_form_definition_complete $hook) {
        global $CFG, $USER;
        if (!empty($USER->newadminuser)) {
            return;
        }
        $userid = isset($hook->customdata['user']->id) ? $hook->customdata['user']->id : null;
        if (totara_gap_can_edit_aspirational_position($userid)) {
            self::add_aspirational_position_controls_to_form($hook);
        } else {
            self::add_aspirational_position_view_to_form($hook);
        }

    }

    /**
     * User edit for hook watcher that is called immediately before the edit form is display.
     *
     * This watcher is used to load any JS required by the form modifications made in the {@see self::extend_form()} watcher.
     *
     * @param editadvanced_form_display $hook
     */
    public static function display_form(editadvanced_form_display $hook) {
        global $USER;
        if (!empty($USER->newadminuser)) {
            return;
        }
        $userid = isset($hook->customdata['user']->id) ? $hook->customdata['user']->id : null;
        if (totara_gap_can_edit_aspirational_position($userid)) {
            // Set up JS.
            local_js(array(
                TOTARA_JS_DIALOG,
                TOTARA_JS_TREEVIEW
            ));

            self::initialise_position_dialog_js($hook);
        }
    }

    /**
     * User edit form save watcher.
     *
     * This watcher is called when saving data from the form, allowing us to process any custom elements that need processing.
     *
     * @param editadvanced_form_save_changes $hook
     */
    public static function save_form(editadvanced_form_save_changes $hook) {
        global $USER;
        if (!empty($USER->newadminuser)) {
            return;
        }
        if (totara_gap_can_edit_aspirational_position($hook->userid)) {
            self::save_aspirational_position_changes($hook);
        }
    }

    /**
     * Add aspirational position selection controls to the user edit form.
     *
     * Adds the following fields to the form:
     *    - aspirationalpositionheader (header)
     *    - aspirationalpositionid (hidden)
     *    - chooseposition (button)
     *
     * JavaScript is required for this element and is loaded by (@see self::initialise_position_dialog_js()}
     *
     * @param editadvanced_form_definition_complete $hook
     */
    protected static function add_aspirational_position_controls_to_form(editadvanced_form_definition_complete $hook) {
        global $CFG, $DB;

        $mform = $hook->form->_form;
        $user = $hook->customdata['user'];

        // Add hidden inputs.
        $mform->addElement('hidden', 'aspirationalpositionid');
        $mform->setType('aspirationalpositionid', PARAM_INT);
        $mform->setDefault('aspirationalpositionid', 0);

        $posname = '';
        $posclass = '';

        // Hack taken from user/positions.php.
        $submittedaspirationalpositionid = optional_param('aspirationalpositionid', null, PARAM_INT);
        if ($submittedaspirationalpositionid) {
            $posclass = 'nonempty';
            $posname = $DB->get_field('pos', 'fullname', array('id' => $submittedaspirationalpositionid));
        } else {
            $position = totara_gap_get_aspirational_position($user->id);
            if (!empty($position)) {
                $mform->setDefault('aspirationalpositionid', $position->positionid);
                $posclass = 'nonempty';
                $posname = $position->fullname;
            }
        }
        $poshtml = \html_writer::tag('span', format_string($posname),
                array('class' => $posclass, 'id' => 'aspirationalpositiontitle'));

        $buttonhtml = \html_writer::empty_tag('input', array(
            'type' => 'button',
            'value' => get_string('chooseposition', 'totara_hierarchy'),
            'id' => 'show-aspirationalposition-dialog'
        ));

        $poselem = $mform->createElement(
            'static',
            'aspirationalposition',
            get_string('useraspirationalposition', 'totara_hierarchy'),
            $poshtml . $buttonhtml
        );

        // Reverse add elements because only insertElementBefore method is exists.
        $beforename = 'moodle_optional';
        if (isset($mform->_elementIndex[$beforename])) {
            $mform->insertElementBefore($poselem, $beforename);
        } else {
            $mform->addElement($poselem);
        }
        $mform->addHelpButton('aspirationalposition', 'useraspirationalposition', 'totara_hierarchy');

        $mform->insertElementBefore(
            $mform->createElement('header', 'aspirationalpositionheader',
                    get_string('useraspirationalposition', 'totara_hierarchy')),
            'aspirationalposition'
        );
    }

    /**
     * Add aspirational position read only text to the user edit form.
     *
     * Adds the following fields to the form:
     *    - aspirationalpositionheader (header)
     *    - aspirationalpositiontitle (static)
     *
     * @param editadvanced_form_definition_complete $hook
     */
    protected static function add_aspirational_position_view_to_form(editadvanced_form_definition_complete $hook) {
        global $CFG;

        $mform = $hook->form->_form;
        $user = $hook->customdata['user'];

        $posname = '';
        $position = totara_gap_get_aspirational_position($user->id);
        if (!empty($position)) {
            $posname = $position->fullname;
        }
        $poshtml = format_string($posname);

        $poselem = $mform->createElement(
            'static',
            'aspirationalposition',
            get_string('useraspirationalposition', 'totara_hierarchy'),
            $poshtml
        );

        // Reverse add elements because only insertElementBefore method is exists.
        $beforename = 'moodle_optional';
        if (isset($mform->_elementIndex[$beforename])) {
            $mform->insertElementBefore($poselem, $beforename);
        } else {
            $mform->addElement($poselem);
        }

        $mform->insertElementBefore(
            $mform->createElement('header', 'aspirationalpositionheader',
                get_string('useraspirationalposition', 'totara_hierarchy')),
            'aspirationalposition'
        );
    }

    /**
     * Initialise JS for the aspirational position elements.
     *
     * Elements are initialised by {@see self::add_aspirational_position_controls_to_form()}.
     * Data is saved by {@see self::save_aspirational_position_changes()}.
     *
     * @param editadvanced_form_display $hook
     */
    protected static function initialise_position_dialog_js(editadvanced_form_display $hook) {
        global $PAGE;

        $user = $hook->customdata['user'];

        $PAGE->requires->strings_for_js(array('chooseposition'), 'totara_hierarchy');

        $jsmodule = array(
            'name' => 'totara_positionuser',
            'fullpath' => '/totara/gap/js/aspirational_position.js',
            'requires' => array('json')
        );

        $selected_position = json_encode(dialog_display_currently_selected(get_string('selected', 'totara_hierarchy'), 'aspirationalposition'));
        $args = array('args'=>'{"userid":'.$user->id.','.
            '"dialog_display_position":'.$selected_position.'}');

        $PAGE->requires->js_init_call('M.aspirational_position.init', $args, false, $jsmodule);
    }

    /**
     * Saves changes to aspirational position.
     *
     * @param editadvanced_form_save_changes $hook
     */
    protected static function save_aspirational_position_changes(editadvanced_form_save_changes $hook) {
        $data = $hook->data;
        $userid = $hook->userid;
        $positionid = $data->aspirationalpositionid;
        totara_gap_assign_aspirational_position($userid, $positionid);
    }
}