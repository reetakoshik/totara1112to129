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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

$capabilities = array(
    /* Alter settings and configure purge and export types */
    'totara/userdata:config' => array(
        'riskbitmask' => RISK_DATALOSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(),
    ),
    /* View user data overview information - this does not control access to actual user data stored in system */
    'totara/userdata:viewinfo' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => array(),
    ),
    /* View list of all purges and purge types */
    'totara/userdata:viewpurges' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(),
    ),
    /* Execute user data purge manually */
    'totara/userdata:purgemanual' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(),
    ),
    /* Set purge type for automatic suspended user purging */
    'totara/userdata:purgesetsuspended' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(),
    ),
    /* Set purge type for automatic deleted user purging */
    'totara/userdata:purgesetdeleted' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(),
    ),
    /* View list of all exports and export types */
    'totara/userdata:viewexports' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(),
    ),
    /* Allows user to ask for export of own data */
    'totara/userdata:exportself' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => array(),
    ),
);
