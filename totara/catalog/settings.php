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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

// Admin-link to totara_catalog configuration forms only when totara_catalog is activated.
$catalogtype = $CFG->catalogtype ?? 'totara';
if ($catalogtype === 'totara') {
    $ADMIN->add(
        'courses',
        new admin_externalpage(
            'configurecatalog',
            new lang_string('configurecatalog', 'totara_catalog'),
            new moodle_url('/totara/catalog/config.php'),
            'totara/catalog:configurecatalog'
        )
    );
}
