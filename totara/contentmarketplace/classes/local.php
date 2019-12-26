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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace;

defined('MOODLE_INTERNAL') || die();

/**
 * @package totara_contentmarketplace
 */
final class local {

    /**
     * Is the contentmarketplace feature enabled.
     * @return boolean 1 for true, 0 for false.
     */
    public static function is_enabled() {
        return get_config('core', 'enablecontentmarketplaces');
    }

    /**
     * Confirms that the content marketplace is enabled.
     */
    public static function require_contentmarketplace() {
        if (!self::is_enabled()) {
            throw new \moodle_exception('error:disabledmarketplaces', 'totara_contentmarketplace');
        }
    }

    /**
     * Return localised formatted string representing the given integer.
     *
     * @param int $integer
     * @param string $locale - @deprecated since Totara 12.1, do not use
     * @return \string
     */
    public static function format_integer($integer, $locale = null) {
        if (is_null($locale)) {
            $locale = get_string('locale', 'langconfig');
        }
        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        return $formatter->format($integer, \NumberFormatter::TYPE_INT64);
    }

    /**
     * Return localised formatted string representing the given value and currency.
     *
     * @param float $value
     * @param string $currency
     * @param string $locale - @deprecated since Totara 12.1, do not use
     * @return string
     */
    public static function format_money($value, $currency, $locale = null) {
        if (is_null($locale)) {
            $locale = get_string('locale', 'langconfig');
        }
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($value, $currency);
    }

    /**
     * Determine if content marketplace introduction should be displayed to the admin user.
     *
     * @return bool
     */
    public static function should_show_admin_setup_intro() {
        /** @var \totara_contentmarketplace\plugininfo\contentmarketplace[] $plugins */
        $plugins = \core_plugin_manager::instance()->get_plugins_of_type('contentmarketplace');
        foreach ($plugins as $plugin) {
            if (!$plugin->has_never_been_enabled()) {
                return false;
            }
        }
        return true;
    }
}
