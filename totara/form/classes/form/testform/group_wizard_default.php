<?php
/*
 * This file is part of Totara Learn
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\testform;

use totara_form\form\group\wizard;
use totara_form\form_controller;

/**
 * Wizard test form for testing default settings.
 * Use group_wizard_features to test features that deviate from the default settings.
 *
 * @author    Matthias Bonk <matthias.bonk@totaralearning.com>
 * @copyright 2017 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   totara_form
 */
class group_wizard_default extends group_wizard {

    /**
     * @param wizard $wizard
     * @return wizard
     */
    public function set_wizard_features(wizard $wizard) {
        // Leave everything default.
        return $wizard;
    }

    /**
     * Returns the name for this test form.
     * @return string
     */
    public static function get_form_test_name() {
        return 'Basic wizard group default';
    }

    /**
     * Returns class responsible for form handling.
     * This is intended especially for ajax processing.
     *
     * @return null|form_controller
     */
    public static function get_form_controller() {
        return new group_wizard_default_controller();
    }
}