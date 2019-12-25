<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
  'tool/totara_sync:managecomp' => array(
    'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS | RISK_CONFIG,
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => array(
      'manager' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'tool/totara_sync:manage'
  ),
   'tool/totara_sync:uploadcomp' => array(
    'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS | RISK_CONFIG,
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => array(
      'manager' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'tool/totara_sync:manage'
  )
);
