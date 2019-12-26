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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @author Joby Harding <joby.harding@totaralearning.com>
 * @package theme_basis
 */

defined('MOODLE_INTERNAL') || die();

use theme_basis\css_processor;

/**
 * Makes our changes to the CSS
 *
 * This is only called when compiling CSS after cache clearing.
 *
 * @param string $css
 * @param theme_config $theme
 * @return string
 */
function theme_basis_process_css($css, $theme) {

    $processor   = new css_processor($theme);
    $settingscss = $processor->get_settings_css($css);

    if (empty($theme->settings->enablestyleoverrides)) {
        // Replace all instances ($settingscss is an array).
        $css = str_replace($settingscss, '', $css);
        // Always insert settings-based custom CSS.
        return $processor->replace_tokens(array('customcss' => css_processor::$DEFAULT_CUSTOMCSS), $css);
    }

    $replacements = $settingscss;

    // Based on Basis Bootswatch.
    // These defaults will also be used to generate and replace
    // variant colours (e.g. linkcolor-dark, linkcolor-darker).
    $variantdefaults = array(
        'linkcolor'        => css_processor::$DEFAULT_LINKCOLOR,
        'linkvisitedcolor' => css_processor::$DEFAULT_LINKVISITEDCOLOR,
        'headerbgc'        => css_processor::$DEFAULT_HEADERBGC,
        'buttoncolor'      => css_processor::$DEFAULT_BUTTONCOLOR,
        'primarybuttoncolor'      => css_processor::$DEFAULT_PRIMARYBUTTONCOLOR,
    );

    // These default values do not have programmatic variants.
    $nonvariantdefaults = array(
        'contentbackground' => css_processor::$DEFAULT_CONTENTBACKGROUND,
        'bodybackground'    => css_processor::$DEFAULT_BODYBACKGROUND,
        'textcolor'         => css_processor::$DEFAULT_TEXTCOLOR,
        'navtextcolor'      => css_processor::$DEFAULT_NAVTEXTCOLOR,
    );

    foreach (array_values($replacements) as $i => $replacement) {
        $replacements[$i] = $processor->replace_colours($variantdefaults, $replacement);
        $replacements[$i] = $processor->replace_tokens($nonvariantdefaults, $replacements[$i]);
        $replacements[$i] = $processor->remove_delimiters($replacements[$i]);
    }

    if (!empty($settingscss)) {
        $css = str_replace($settingscss, $replacements, $css);
    }

    // Settings based CSS is not applied conditionally.
    $css = $processor->replace_tokens(array('customcss' => css_processor::$DEFAULT_CUSTOMCSS), $css);

    return $css;
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_basis_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel == CONTEXT_SYSTEM && ($filearea === 'logo' || $filearea === 'favicon' || $filearea === 'backgroundimage')) {
        $theme = theme_config::load('basis');
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }

    send_file_not_found();
}

/**
 * Returns the URL of the favicon if available.
 *
 * @param theme_config $theme
 * @return string|null
 */
function theme_basis_resolve_favicon($theme) {
    if (!empty($theme->settings->favicon)) {
        return $theme->setting_file_url('favicon', 'favicon');
    }
    return null;
}
