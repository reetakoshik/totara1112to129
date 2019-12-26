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

/**
 * This theme is designed to be the parent of all Totara themes,
 * it contains only the very basic features that are shared by all themes.
 *
 * DO NOT COPY THIS TO START NEW THEMES!
 *
 * @package   theme_base
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$THEME->name = 'base';

$THEME->parents = array();

$THEME->sheets = array('flexible-icons');
$THEME->editor_sheets = array();
$THEME->enable_dock = false;
$THEME->enable_hide = false;
$THEME->layouts = array(
    // Most backwards compatible layout without the blocks - this is the layout used by default.
    'base' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // Standard layout with blocks, this is recommended for most pages with general information.
    'standard' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // pages that need the full width of the page - no blocks shown at all
    // this is only used by totara pages
    'noblocks' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // Main course page.
    'course' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    'coursecategory' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // Part of course, typical for modules - default page layout if $cm specified in require_login().
    'incourse' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // The site home page.
    'frontpage' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // Server administration scripts.
    'admin' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // This would be better described as "user profile" but we've left it as mydashboard
    // for backward compatibilty for existing themes. This layout is NOT used by Totara
    // dashboards but is used by user related pages such as the user profile, private files
    // and badges.
    'mydashboard' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // The dashboard layout differs from the one above in that it includes a central block region.
    // It is used by Totara dashboards.
    'dashboard' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // My public page.
    'mypublic' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    'login' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),

    // Pages that appear in pop-up windows - no navigation, no blocks, no header.
    'popup' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // No blocks and minimal footer - used for legacy frame layouts only!
    'frametop' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // Embeded pages, like iframe/object embeded in moodleform - it needs as much space as possible.
    'embedded' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // Used during upgrade and install, and for the 'This site is undergoing maintenance' message.
    // This must not have any blocks, and it is good idea if it does not have links to
    // other places - for example there should not be a home link in the footer...
    'maintenance' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // Should display the content and basic headers only.
    'print' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // The pagelayout used when a redirection is occuring.
    'redirect' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // The pagelayout used for reports.
    'report' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
    // The pagelayout used for safebrowser and securewindow.
    'secure' => array(
        'file' => 'general.php',
        'regions' => array(),
    ),
);

// We don't want the base theme to be shown on the theme selection screen, by setting
// this to true it will only be shown if theme designer mode is switched on.
$THEME->hidefromselector = true;

/** List of javascript files that need to included on each page */
$THEME->javascripts = array();
$THEME->javascripts_footer = array();
