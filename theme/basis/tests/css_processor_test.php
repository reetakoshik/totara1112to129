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

defined('MOODLE_INTERNAL') || die();

use theme_basis\css_processor;

class css_processor_testcase extends basic_testcase {

    protected static $theme;

    public function test_it_can_be_initialised() {
        $expected = 'theme_basis\css_processor';
        $actual = get_class(new css_processor());
        $this->assertEquals($expected, $actual);
    }

    /**
     * It should return the settings delimited CSS if it exists or an empty string if not.
     */
    public function test_it_finds_settings_css() {
        $starttoken = css_processor::TOKEN_ENABLEOVERRIDES_START;
        $endtoken = css_processor::TOKEN_ENABLEOVERRIDES_END;
        $css = <<<EOF
body {
    background: lime;
}
{$starttoken}
SETTINGS HERE 1
{$endtoken}
.foo .bar {
    display: inline-block;
    color: #123123;
}

{$starttoken}
SETTINGS HERE 2
{$endtoken}
EOF;
        $expected = array();
        $expected[] = <<<EOF
{$starttoken}
SETTINGS HERE 1
{$endtoken}
EOF;
        $expected[] = <<<EOF
{$starttoken}
SETTINGS HERE 2
{$endtoken}
EOF;
        $actual = (new css_processor())->get_settings_css($css);

        $this->assertEquals($expected, $actual);

        // No settings.
        $expected = array();
        $actual = (new css_processor())->get_settings_css('body { background: lime; }');

        $this->assertEquals($expected, $actual);
    }

    /**
     * It should replace settings placeholders with a default colour.
     */
    public function test_it_replaces_colours_with_defaults() {

        $css =<<<EOF
body {
    background-color: [[setting:linkcolor]];
    color: [[setting:linkvisitedcolor]];
    border-top-color: [[setting:headerbgc]];
    border-bottom-color: [[setting:buttoncolor]];
    border-left-color: [[setting:primarybuttoncolor]];
}
EOF;
        $substitutions = array(
            'linkcolor' => '#123123',
            'linkvisitedcolor' => '#777777',
            'headerbgc' => '#FFFFFF',
            'buttoncolor' => '#000000',
            'primarybuttoncolor' => '#646464',
        );

        $expected = <<<EOF
body {
    background-color: #123123;
    color: #777777;
    border-top-color: #FFFFFF;
    border-bottom-color: #000000;
    border-left-color: #646464;
}
EOF;
        $actual = (new css_processor())->replace_colours($substitutions, $css);

        $this->assertEquals($expected, $actual);
    }

    /**
     * It should replace settings placeholders with user defined colours.
     *
     * User
     */
    public function test_it_replaces_colours_with_settings() {

        $theme = new stdClass();
        $theme->settings = (object)array(
            'linkcolor' => '#888888',
            'linkvisitedcolor' => '#999999',
            'headerbgc' => '#333333',
            'buttoncolor' => '#555555',
            'primarybuttoncolor' => '#424242',
        );

        $css =<<<EOF
body {
    background-color: [[setting:linkcolor]];
    color: [[setting:linkvisitedcolor]];
    border-top-color: [[setting:headerbgc]];
    border-bottom-color: [[setting:buttoncolor]];
    border-left-color: [[setting:primarybuttoncolor]];
}
EOF;
        $substitutions = array(
            'linkcolor' => '#123123',
            'linkvisitedcolor' => '#777777',
            'headerbgc' => '#FFFFFF',
            'buttoncolor' => '#000000',
            'primarybuttoncolor' => '#646464',
        );

        $expected = <<<EOF
body {
    background-color: {$theme->settings->linkcolor};
    color: {$theme->settings->linkvisitedcolor};
    border-top-color: {$theme->settings->headerbgc};
    border-bottom-color: {$theme->settings->buttoncolor};
    border-left-color: {$theme->settings->primarybuttoncolor};
}
EOF;
        $actual = (new css_processor($theme))->replace_colours($substitutions, $css);

        $this->assertEquals($expected, $actual);
    }

    /**
     * It should generate and replace variants for a given substitution colour.
     */
    public function test_it_replaces_colour_variants() {

        $substitutions = array(
            'linkcolor' => '#123123',
        );
        $linkcolor = $substitutions['linkcolor'];
        $linkcolor_lighter = totara_brightness_linear($linkcolor, 15);
        $linkcolor_darker = totara_brightness_linear($linkcolor, -15);
        $linkcolor_light = totara_brightness_linear($linkcolor, 25);
        $linkcolor_dark = totara_brightness_linear($linkcolor, -25);
        $linkcolor_lighter_perc = totara_brightness($linkcolor, 15);
        $linkcolor_darker_perc = totara_brightness($linkcolor, -15);
        $linkcolor_light_perc = totara_brightness($linkcolor, 25);
        $linkcolor_dark_perc = totara_brightness($linkcolor, -25);
        $linkcolor_readable_text = totara_readable_text($linkcolor);

        $css =<<<EOF
body {
    color: [[setting:linkcolor]];
    color: [[setting:linkcolor-lighter]];
    color: [[setting:linkcolor-darker]];
    color: [[setting:linkcolor-light]];
    color: [[setting:linkcolor-dark]];
    color: [[setting:linkcolor-lighter-perc]];
    color: [[setting:linkcolor-darker-perc]];
    color: [[setting:linkcolor-light-perc]];
    color: [[setting:linkcolor-dark-perc]];
    color: [[setting:linkcolor-readable-text]];
}
EOF;

        $expected = <<<EOF
body {
    color: {$linkcolor};
    color: {$linkcolor_lighter};
    color: {$linkcolor_darker};
    color: {$linkcolor_light};
    color: {$linkcolor_dark};
    color: {$linkcolor_lighter_perc};
    color: {$linkcolor_darker_perc};
    color: {$linkcolor_light_perc};
    color: {$linkcolor_dark_perc};
    color: {$linkcolor_readable_text};
}
EOF;
        $actual = (new css_processor())->replace_colours($substitutions, $css);

        $this->assertEquals($expected, $actual);
    }

    /**
     * It should replace the settings tokens with provided default values.
     */
    public function test_it_replaces_tokens_with_defaults() {

        $css  = '[[setting:contentbackground]]';
        $css .= '[[setting:bodybackground]]';
        $css .= '[[setting:textcolor]]';

        $defaults = array(
            'contentbackground' => '[REPLACED_1]',
            'bodybackground'    => '[REPLACED_2]',
            'textcolor'         => '[REPLACED_3]',
        );

        $expected = '[REPLACED_1][REPLACED_2][REPLACED_3]';
        $actual = (new css_processor())->replace_tokens($defaults, $css);

        $this->assertEquals($expected, $actual);
    }

    /**
     * It should replace settings tokens with user-defined values.
     */
    public function test_it_replaces_tokens_with_settings() {

        $css  = '[[setting:contentbackground]]';
        $css .= '[[setting:bodybackground]]';
        $css .= '[[setting:textcolor]]';
        $css .= '[[setting:customcss]]';

        $theme = new stdClass();
        $theme->settings = (object)array(
            'contentbackground' => '[OVERRIDDEN_1]',
            'textcolor'         => '[OVERRIDDEN_2]',
            'customcss'         => '[OVERRIDDEN_3]'
        );

        $defaults = array(
            'contentbackground' => '[REPLACED_1]',
            'bodybackground'    => '[REPLACED_2]',
            'textcolor'         => '[REPLACED_3]',
            'customcss'         => '[REPLACED_4]'
        );

        $expected = '[OVERRIDDEN_1][REPLACED_2][OVERRIDDEN_2][OVERRIDDEN_3]';
        $actual = (new css_processor($theme))->replace_tokens($defaults, $css);

        $this->assertEquals($expected, $actual);
    }

    /**
     * It should remove settings CSS delimiters.
     */
    public function test_it_removes_delimiters() {
        $starttoken = css_processor::TOKEN_ENABLEOVERRIDES_START;
        $endtoken = css_processor::TOKEN_ENABLEOVERRIDES_END;

        $css = <<<EOF
{$starttoken}SETTINGSCSS{$endtoken}
CSS
{$starttoken}SETTINGSCSS{$endtoken}
EOF;

        $expected = <<<EOF
SETTINGSCSS
CSS
SETTINGSCSS
EOF;
        $actual = (new css_processor())->remove_delimiters($css);

        $this->assertEquals($expected, $actual);
    }

}