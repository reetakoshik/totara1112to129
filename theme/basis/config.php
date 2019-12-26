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
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralearning.com>
 * @package   theme_basis
 */

defined('MOODLE_INTERNAL' || die());

$THEME->doctype = 'html5';
$THEME->name = 'basis';
$THEME->parents = array('roots', 'base');
$THEME->yuicssmodules = array();
$THEME->enable_dock = true;
$THEME->enable_hide = true;
$THEME->sheets = array('totara', 'settings-noprocess');
$THEME->enable_dock = true;

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->csspostprocess = 'theme_basis_process_css';

// Use CSS preprocessing to facilitate style inheritance.
$THEME->parents_exclude_sheets = array(
    'roots' => array('totara', 'totara-rtl'),
    'base' => array('flexible-icons'),
);

// Register our favicon resolver.
$THEME->resolvefaviconcallback = 'theme_basis_resolve_favicon';
