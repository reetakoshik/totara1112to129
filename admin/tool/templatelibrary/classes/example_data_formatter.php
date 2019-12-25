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
 * @author Joby Harding <joby.harding@totaralms.com>
 * @package tool_templatelibrary
 */

namespace tool_templatelibrary;

defined('MOODLE_INTERNAL') || die();

/**
 * Class example_data_formatter
 *
 * Intended only to be used to assist in the production of mustache
 * JSON template example data. Example usage:
 *
 * tool_templatelibrary\example_data_formatter::to_json($templatecontext);
 *
 * The example output of this should still be reviewed by the developer.
 *
 * @package tool_templatelibrary
 */
class example_data_formatter {

    /**
     * Check the type of the mustache template context.
     *
     * @param $templatecontext array|\stdClass
     * @return bool
     */
    public static function templatecontext_is_correct_type($templatecontext) {

        $isarray = is_array($templatecontext);
        $isstdclass = is_object($templatecontext) && (get_class($templatecontext) === 'stdClass');

        return $isarray || $isstdclass;

    }

    /**
     * Replaces hostnames in img src with a placeholder.
     *
     * @param string $datastring
     * @return string
     */
    public static function replace_webroot_in_img_src($datastring) {

        global $CFG;

        $pattern = '/<img\s[^>]*(src=(\\\?("|\'))[^"\\\]*\2)/';

        $datastring = preg_replace_callback($pattern, function($matches) use ($CFG) {
            return str_replace($CFG->wwwroot, self::token_to_placeholder('WWWROOT'), $matches[0]);
        }, $datastring);

        return $datastring;
    }

    /**
     * Convert a given token into a placeholder.
     *
     * @param string $token
     * @return string
     */
    public static function token_to_placeholder($token) {

        return "__{$token}__";

    }

    /**
     * Remove HTML attribute values which might cause actions to be taken on live data.
     *
     * @param string $datastring
     * @return string
     */
    public static function replace_actionable_attribute_urls($datastring) {

        $pattern = '/(href|action)=(\\\?("|\'))[^"\\\]*\2/';
        $replacement = '$1=$2#$2';

        $datastring = preg_replace($pattern, $replacement, $datastring);

        return $datastring;

    }

    /**
     * Format mustache template context data for use in a mustache example.
     *
     * This method is intended primarily as a utility for developers
     * to automate the generation of standardised mustache template
     * context examples.
     *
     * @param $templatecontext array|\stdClass
     * @return string
     */
    public static function to_json($templatecontext) {

        global $CFG;

        // We must ensure paths are to uncached files (use theme mediation).
        if ((bool)$CFG->themedesignermode === false) {
            $message = 'Theme designer mode must be enabled to generate examples';
            throw new \coding_exception($message);
        }

        if (self::templatecontext_is_correct_type($templatecontext) === false) {
            $message = 'Mustache context data must be an array or stdClass';
            throw new \coding_exception($message);
        }

        $output = json_encode($templatecontext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $output = self::replace_webroot_in_img_src($output);
        // Blacklist $CFG->wwwroot.
        $output = str_replace($CFG->wwwroot, 'https://example.com', $output);

        // Whitelist some specific urls.
        $output = str_replace('https://example.com/theme/image.php', self::token_to_placeholder('WWWROOT') . '/theme/image.php', $output);
        $output = str_replace('theme=' . $CFG->theme, 'theme=' . self::token_to_placeholder('THEME'), $output);
        $output = self::replace_actionable_attribute_urls($output);

        return $output;

    }

}
