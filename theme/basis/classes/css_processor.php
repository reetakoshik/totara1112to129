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

namespace theme_basis;

defined('MOODLE_INTERNAL') || die();

class css_processor {

    const TOKEN_ENABLEOVERRIDES_START = '[[enablestyleoverrides:start]]';
    const TOKEN_ENABLEOVERRIDES_END = '[[enablestyleoverrides:end]]';

    // We use static properties rather than class constants
    // so that the class may be reused / overridden in other
    // themes. Otherwise all inheriting classes would be stuck
    // with the Basis default colours.

    /**
     * @var string
     */
    public static $DEFAULT_LINKCOLOR = '#008287';

    /**
     * @var string
     */
    public static $DEFAULT_LINKVISITEDCOLOR = '#008287';

    /**
     * @var string
     */
    public static $DEFAULT_HEADERBGC = '#3D444B';

    /**
     * @var string
     */
    public static $DEFAULT_BUTTONCOLOR = '#36A5A6';

    /**
     * @var string
     */
    public static $DEFAULT_PRIMARYBUTTONCOLOR = '#36A5A6';

    /**
     * @var string
     */
    public static $DEFAULT_CONTENTBACKGROUND = '#FFFFFF';

    /**
     * @var string
     */
    public static $DEFAULT_BODYBACKGROUND = '#FFFFFF';

    /**
     * @var string
     */
    public static $DEFAULT_TEXTCOLOR = '#333366';

    /**
     * @var string
     */
    public static $DEFAULT_NAVTEXTCOLOR = '#FFFFFF';

    /**
     * @var string
     */
    public static $DEFAULT_CUSTOMCSS = '';

    /**
     * @var \theme_config
     */
    protected $theme = null;

    /**
     * Constructor.
     *
     * @param string $css Styles to be processed.
     * @param theme_config|null $theme Theme object.
     */
    public function __construct($theme = null) {
        global $THEME;

        $this->theme = ($theme === null) ? $THEME : $theme;
    }

    /**
     * Return settings CSS delimited by start and end tokens.
     *
     * An array is always returned containing matches.
     *
     * @param string $css
     * @return array
     */
    public function get_settings_css($css) {
        $matches = array();
        $start = preg_quote(self::TOKEN_ENABLEOVERRIDES_START);
        $end = preg_quote(self::TOKEN_ENABLEOVERRIDES_END);
        if (preg_match_all("/{$start}.*?{$end}/s", $css, $matches) > 0) {
            return $matches[0];
        }
        return array();
    }

    /**
     * Replace colour placeholders including variants based on given substitutions.
     *
     * @param array $substitutions Key => Value
     * @param string $css
     * @return string
     */
    public function replace_colours($substitutions, $css) {
        return totara_theme_generate_autocolors($css, $this->theme, $substitutions);
    }

    /**
     * Replace the customcss token with given styles.
     *
     * @param array $defaults In the form token => replacement.
     * @param string $css
     * @return string
     */
    public function replace_tokens(array $defaults, $css) {
        $theme = $this->theme;
        $replacements = array();
        foreach ($defaults as $settingname => $default) {
            $replacement = isset($theme->settings->{$settingname}) ? $theme->settings->{$settingname} : $default;
            $replacements["[[setting:{$settingname}]]"] = $replacement;
        }
        return str_replace(array_keys($replacements), array_values($replacements), $css);
    }

    /**
     * Remove the settings CSS delimiter tokens.
     *
     * @param string $css
     * @return string
     */
    public function remove_delimiters($css) {
        $replacements = array(
            self::TOKEN_ENABLEOVERRIDES_START => '',
            self::TOKEN_ENABLEOVERRIDES_END => ''
        );
        return str_replace(array_keys($replacements), array_values($replacements), $css);
    }

}
