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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

$functions = array(

    // Gets the catalog.
    'totara_catalog_external_get_catalog_template_data' => array(
        'classname'         => 'totara_catalog\external',
        'methodname'        => 'get_catalog_template_data',
        'classpath'         => 'totara/catalog/classes/external.php',
        'description'       => 'Gets everything needed to display the catalog, including results',
        'type'              => 'read',
        'loginrequired'     => true,
        'ajax'              => true,
        'capabilities'      => ''
    ),

    // Gets the details for a given object.
    'totara_catalog_external_get_details_template_data' => array(
        'classname'         => 'totara_catalog\external',
        'methodname'        => 'get_details_template_data',
        'classpath'         => 'totara/catalog/classes/external.php',
        'description'       => 'Gets the details for a given object',
        'type'              => 'read',
        'loginrequired'     => true,
        'ajax'              => true,
        'capabilities'      => ''
    ),

);
