<?php
/*
 * This file is part of Totara LMS
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\watcher;

defined('MOODLE_INTERNAL') || die();

use \totara_customfield\hook\field_form_render_data;
use \totara_customfield\hook\field_form_set_data;
use \totara_customfield\hook\field_form_validation;
use \totara_customfield\hook\field_form_render_icons;

/**
 * Manage reserved Totara seminar custom fields.
 *
 * @package mod_facetoface\watcher
 */
class shortname_customfield {

    /**
     * Inject seminar custom filed form definion into totara custom filed form definiion
     * changing a shortname field behaviour if it is the reserved seminar custom field,
     * changing the shortname attrs to readonly, label and help text.
     *
     * @param \totara_customfield\hook\field_form_set_data $hook
     */
    public static function set_data(field_form_set_data $hook) {
        global $F2F_CUSTOMFIELD_RESERVED;

        if (!isset($hook->customdata['prefix'])) {
            return;
        }

        if (!isset($F2F_CUSTOMFIELD_RESERVED[$hook->customdata['prefix']])) {
            return;
        }
        $form = $hook->mform;
        $shortname = $form->_form->getElement('shortname')->getAttribute('value');
        $reserved = $F2F_CUSTOMFIELD_RESERVED[$hook->customdata['prefix']];
        foreach ($reserved as $datatype => $sname) {
            if ($shortname == $sname && $datatype == $hook->customdata['datatype'] && !$form->is_submitted()) {
                // Set Totara reserved custom field shortname a new label.
                $form->_form->getElement('shortname')->setLabel(get_string('shortnamereserved', 'totara_customfield'));
                // Set Totara reserved custom field shortname to readonly state.
                $form->_form->getElement('shortname')->updateAttributes('readonly');
                // Overwrite help message letting user know that this is reserved field.
                $form->_form->addHelpButton('shortname', 'customfieldshortnamereadonly', 'totara_customfield');
                break;
            }
        }
    }

    /**
     * Inject seminar custom filed form validation into totara custom filed form validation,
     * reserved seminar custom field should not be used with a new custom fields.
     *
     * @param \totara_customfield\hook\field_form_validation $hook
     */
    public static function validation(field_form_validation $hook) {
        global $F2F_CUSTOMFIELD_RESERVED;

        if (!isset($hook->data['prefix'])) {
            return;
        }

        if (!isset($F2F_CUSTOMFIELD_RESERVED[$hook->data['prefix']])) {
            return;
        }

        if ((int)$hook->data['id'] == 0) {
            $reserved = $F2F_CUSTOMFIELD_RESERVED[$hook->data['prefix']];
            foreach ($reserved as $datatype => $sname) {
                if ($datatype == $hook->data['datatype'] && $sname == $hook->data['shortname']) {
                    $hook->errors['shortname'] = get_string('error:shortnamecustomfield', 'facetoface');
                    break;
                }
            }
        }
   }

    /**
     * Check for reserved seminar custom field, set to true if it is.
     *
     * @param \totara_customfield\hook\field_form_render_data $hook
     */
    public static function render_data(field_form_render_data $hook) {
        global $F2F_CUSTOMFIELD_RESERVED;

        if (!isset($hook->params['prefix'])) {
            return;
        }

        if (!isset($F2F_CUSTOMFIELD_RESERVED[$hook->params['prefix']])) {
            return;
        }

        $prefix = $F2F_CUSTOMFIELD_RESERVED[$hook->params['prefix']];
        foreach ($prefix as $datatype => $sname) {
            if ($hook->field->shortname == $sname && $hook->field->datatype == $datatype) {
                $hook->reserved = true;
                break;
            }
        }
    }

    /**
     * Check for reserved seminar custom field, set can_delete to false if it is.
     *
     * @param \totara_customfield\hook\field_form_render_icons $hook
     */
    public static function render_icons(field_form_render_icons $hook) {
        global $F2F_CUSTOMFIELD_RESERVED;

        if (!isset($hook->params['prefix'])) {
            return;
        }

        if (!isset($F2F_CUSTOMFIELD_RESERVED[$hook->params['prefix']])) {
            return;
        }

        $prefix = $F2F_CUSTOMFIELD_RESERVED[$hook->params['prefix']];
        foreach ($prefix as $datatype => $sname) {
            if ($hook->field->shortname == $sname && $hook->field->datatype == $datatype) {
                $hook->can_delete = false;
                break;
            }
        }
    }
}