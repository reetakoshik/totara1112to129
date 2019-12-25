<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @copyright 2015 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralms.com>>
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 * @package   core
 */

namespace core\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Flex Icon helper class.
 *
 * This class is expected to be used from the flex_icon
 * and internal stuff only, this is not part of public API!
 *
 * @copyright 2015 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralms.com>>
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 * @package   core
 */
class flex_icon_helper {
    const MISSING_ICON = 'flex-icon-missing';

    /**
     * Resolve aliases, deprecated, and icons
     * to create a list of all icons.
     *
     * @param array $iconsdata
     * @return array all flex icon definitions
     */
    protected static function resolve_aliases(array $iconsdata) {
        // Add defaults to all icons.
        foreach ($iconsdata['icons'] as $identifier => $item) {
            if (empty($item['template'])) {
                $iconsdata['icons'][$identifier]['template'] = 'core/flex_icon';
            }
            if (!isset($item['data'])) {
                $iconsdata['icons'][$identifier]['data'] = array();
            }
        }

        // Verify the aliases first.
        foreach ($iconsdata['aliases'] as $identifierfrom => $identifierto) {
            if (isset($iconsdata['icons'][$identifierfrom])) {
                // Translation cannot override map, do not show debug warning because
                // theme might force these for some reason.
                unset($iconsdata['aliases'][$identifierfrom]);
                continue;
            }
            if (!isset($iconsdata['icons'][$identifierto])) {
                debugging("Flex icon translation $identifierfrom points to non-existent $identifierto map entry", DEBUG_DEVELOPER);
                unset($iconsdata['aliases'][$identifierfrom]);
                continue;
            }
        }

        // Map the aliases and remember what we did.
        foreach ($iconsdata['aliases'] as $identifierfrom => $identifierto) {
            $iconsdata['icons'][$identifierfrom] = $iconsdata['icons'][$identifierto];
            $iconsdata['icons'][$identifierfrom]['alias'] = $identifierto;
        }

        // Add deprecated stuff.
        foreach ($iconsdata['deprecated'] as $identifierfrom => $identifierto) {
            if (isset($iconsdata['icons'][$identifierfrom])) {
                // Valid map already exists.
                continue;
            }
            if (!isset($iconsdata['icons'][$identifierto])) {
                debugging("Deprecated pix icon $identifierfrom points to non-existent $identifierto map entry", DEBUG_DEVELOPER);
                continue;
            }
            if (!empty($iconsdata['icons'][$identifierto]['alias'])) {
                // Always link the original.
                $identifierto = $iconsdata['icons'][$identifierto]['alias'];
            }
            $iconsdata['icons'][$identifierfrom] = $iconsdata['icons'][$identifierto];
            $iconsdata['icons'][$identifierfrom]['alias'] = $identifierto;
            $iconsdata['icons'][$identifierfrom]['deprecated'] = true;
        }

        return $iconsdata['icons'];
    }

    /**
     * Return data for javascript part of flex icons implementation.
     *
     * Note: this is not a public api and is intended for \core\output\external::get_flex_icons() only.
     *
     * @param string $themename
     * @return array
     */
    public static function get_ajax_data($themename) {
        $icons = self::get_icons($themename);

        $templates = array();
        $ti = 0;

        $datas = array();
        $di = 0;

        foreach ($icons as $identifier => $desc) {
            if (!empty($desc['alias'])) {
                continue;
            }
            $icon = array();

            $template = $desc['template'];
            if (!isset($templates[$template])) {
                $templates[$template] = $ti;
                $ti++;
            }
            $icon[0] = $templates[$template];

            $datas[$di] = $desc['data'];
            $icon[1] = $di;
            $di++;

            $icons[$identifier] = $icon;
        }

        foreach ($icons as $identifier => $desc) {
            if (empty($desc['alias'])) {
                continue;
            }
            $icons[$identifier] = $icons[$desc['alias']];
        }

        return array(
            'templates' => array_flip($templates),
            'datas' => $datas,
            'icons' => $icons,
        );
    }

    /**
     * Get the list of icon definitions.
     *
     * Recurse through parent theme hierarchy and core icon data
     * to resolve data and template for every icon. This method
     * should only be called when building the cache file for
     * performance reasons.
     *
     * @param string $themename
     * @return array
     */
    public static function get_icons($themename) {
        global $CFG;

        $themename = clean_param($themename, PARAM_THEME);
        if (!$themename) {
            // We do not want any failures in here, always return something valid.
            $themename = $CFG->theme;
        }

        $cache = \cache::make('totara_core', 'flex_icons');
        $cached = $cache->get($themename);
        if ($cached) {
            return $cached;
        }

        $flexiconsfile = '/pix/flex_icons.php';
        $iconsdata = array(
            'aliases' => array(),
            'deprecated' => array(),
            'icons' => array(),
        );

        // Load all plugins in the standard order, the first one wins - no overriding.
        $plugintypes = \core_component::get_plugin_types();
        foreach ($plugintypes as $type => $unused) {
            $plugs = \core_component::get_plugin_list($type);
            foreach ($plugs as $name => $location) {
                $iconsdata = self::merge_flex_icons_file($location . $flexiconsfile, $iconsdata, false);
            }
        }

        // Load core translation and map overriding any plugins trying to change the core icons.
        $iconsdata = self::merge_flex_icons_file($CFG->dirroot. $flexiconsfile, $iconsdata, true);

        // Then parent theme and at the very end load the current theme.
        $theme = \theme_config::load($themename);
        $candidatedirs = $theme->get_related_theme_dirs();
        foreach ($candidatedirs as $candidatedir) {
            $iconsdata = self::merge_flex_icons_file($candidatedir . $flexiconsfile, $iconsdata, true);
        }

        // Flatten the icons structure.
        $iconsmap = self::resolve_aliases($iconsdata);
        $cache->set($themename, $iconsmap);
        return $iconsmap;
    }

    /**
     * Merge individual flex_icon.php files.
     *
     * @param string $file
     * @param array $iconsdata
     * @param bool $allowoverride
     * @return array the new icons data
     */
    protected static function merge_flex_icons_file($file, $iconsdata, $allowoverride) {
        if (!file_exists($file)) {
            return $iconsdata;
        }

        $aliases = array();
        $deprecated = array();
        $icons = array();
        require($file);

        if ($deprecated) {
            if ($allowoverride) {
                $iconsdata['deprecated'] = array_merge($iconsdata['deprecated'], $deprecated);
            } else {
                $iconsdata['deprecated'] = array_merge($deprecated, $iconsdata['deprecated']);
            }
        }
        if ($aliases) {
            if ($allowoverride) {
                $iconsdata['aliases'] = array_merge($iconsdata['aliases'], $aliases);
            } else {
                $iconsdata['aliases'] = array_merge($aliases, $iconsdata['aliases']);
            }
        }
        if ($icons) {
            if ($allowoverride) {
                $iconsdata['icons'] = array_merge($iconsdata['icons'], $icons);
            } else {
                $iconsdata['icons'] = array_merge($icons, $iconsdata['icons']);
            }
        }
        return $iconsdata;
    }

    /**
     * Return the template name for rendering a given flex icon.
     *
     * @param string $themename Name of the theme to get icon data from.
     * @param string $identifier Resolved identifier for the icon to be rendered.
     * @return string
     */
    public static function get_template_by_identifier($themename, $identifier) {
        $iconsmap = self::get_icons($themename);
        if (!isset($iconsmap[$identifier])) {
            $identifier = self::MISSING_ICON;
        }
        return $iconsmap[$identifier]['template'];
    }

    /**
     * Retrieve data associated with given Flex Icon.
     *
     * @param string $themename
     * @param string $identifier Flex Icon identifier.
     * @return array
     */
    public static function get_data_by_identifier($themename, $identifier) {
        $iconsmap = self::get_icons($themename);
        if (!isset($iconsmap[$identifier])) {
            $identifier = self::MISSING_ICON;
        }
        return $iconsmap[$identifier]['data'];
    }
}
