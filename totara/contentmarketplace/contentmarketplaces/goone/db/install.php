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

/**
 * Totara workflow install hook.
 */
function xmldb_contentmarketplace_goone_install() {
    // Enable GO1 course create workflow on install.
    $workflow = contentmarketplace_goone\workflow\core_course\coursecreate\contentmarketplace::instance();
    $workflow->enable();

    // Enable goone Explore marketplace workflow on install.
    $workflow = contentmarketplace_goone\workflow\totara_contentmarketplace\exploremarketplace\goone::instance();
    $workflow->enable();
}
