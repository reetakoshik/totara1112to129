<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @package    totara_coursecatalogue
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */

namespace totara_coursecatalog\totara\menu;

class programs extends \totara_core\totara\menu\item {

    protected function get_default_title() {
        return get_string('programs', 'totara_coursecatalog');
    }

    protected function get_default_url() {
        global $CFG;

        if ($CFG->catalogtype === 'enhanced') {
            return '/totara/coursecatalog/programs.php';
        } else {
            return '/totara/program/index.php';
        }
    }

    public function get_default_sortorder() {
        return 72000;
    }

    /**
     * Is this menu item completely disabled?
     *
     * @return bool
     */
    public function is_disabled() {
        return totara_feature_disabled('programs');
    }

    protected function get_default_parent() {
        return '\totara_coursecatalog\totara\menu\findlearning';
    }

    public function get_incompatible_preset_rules(): array {
        return ['can_view_programs'];
    }
}
