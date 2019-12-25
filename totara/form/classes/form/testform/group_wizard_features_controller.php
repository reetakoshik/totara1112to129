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

/**
 * Wizard test form ajax controller
 *
 * @author    Matthias Bonk <matthias.bonk@totaralearning.com>
 * @copyright 2017 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   totara_form
 */
class group_wizard_features_controller extends group_wizard_controller {

    protected function get_form_class() {
        return 'totara_form\form\testform\group_wizard_features';
    }
}
