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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_customfield
 */

namespace totara_customfield;

defined('MOODLE_INTERNAL') || die();

/**
 * Report builder custom field loader class
 *
 * This class helps us by ensuring that the custom fields we're looking at across all reports are consistent.
 * Fields are statically cached when loaded, and held for the lifetime of the request.
 * We don't mind this, as you shouldn't be able to add, edit or delete a field and produce a report afterwards in the same request.
 * Those actions should always lead to a refresh.
 * If you do need to reset this static cache then please call \totara_customfield\report_builder_field_loader::reset()
 */
final class report_builder_field_loader {

    /**
     * @var report_builder_field_loader|null
     */
    private static $instance;

    /**
     * An array of fields (database records), organised by prefix (as key).
     * @var array|\stdClass[]
     */
    private $fieldsbyprefix = [];

    /**
     * Returns all of the visible custom fields for the given prefix.
     *
     * @param string $prefix
     * @return array|\stdClass[] An array of database records.
     */
    public static function get_visible_fields(string $prefix): array {
        $instance = self::instance();
        $instance->ensure_visible_fields_loaded($prefix);
        return $instance->fieldsbyprefix[$prefix];
    }

    /**
     * Returns an instance of this class.
     *
     * @return report_builder_field_loader
     */
    private static function instance(): report_builder_field_loader {
        if (self::$instance === null) {
            self::$instance = new report_builder_field_loader();
        }
        return self::$instance;
    }

    /**
     * Resets this instance.
     */
    public static function reset() {
        self::$instance = null;
    }

    /**
     * Loads all of the visible fields for the given prefix, if they've not already been loaded.
     *
     * @param string $prefix
     */
    private function ensure_visible_fields_loaded(string $prefix) {
        global $DB;
        if (!isset($this->fieldsbyprefix[$prefix])) {

            if ($prefix === 'user') {
                // Users are special, they aren't Totara custom fields, they don't have the hidden flag.
                $where = [];
            } else {
                $where = ['hidden' => '0'];
            }
            $items = $DB->get_records($prefix . '_info_field', $where);
            $this->fieldsbyprefix[$prefix] = $items;
        }
    }
}