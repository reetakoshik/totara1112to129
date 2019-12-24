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
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace;

/**
 * Ajax helper class.
 *
 * @package totara_contentmarketplace
 */
final class ajax_helper {

    /**
     * @throws \coding_exception if this wasn't called by an AJAX script.
     * @return array An array of filters and set values.
     */
    public static function extract_explorer_filters(): array {

        if (!defined('AJAX_SCRIPT') || !AJAX_SCRIPT) {
            throw new \coding_exception('Explorer filters can only be extracted from AJAX requests.');
        }

        // These parameters must be checked after login, sesskey and capabilities.
        // They get checked against a whitelist internally by the search instance.
        // At some point in the future that should be better abstracted, so that we can whitelist here, otherwise we can't ensure it will happen.
        $filter = array();
        $multivaluefilternames = optional_param_array('multivaluefilters', array(), PARAM_ALPHANUMEXT);
        foreach ($multivaluefilternames as $name) {
            $filter[$name] = optional_param_array('filter-' . $name, array(), PARAM_RAW_TRIMMED);
        }
        $singlevaluefilternames = optional_param_array('singlevaluefilters', array(), PARAM_ALPHANUMEXT);
        foreach ($singlevaluefilternames as $name) {
            $filter[$name] = optional_param('filter-' . $name, null, PARAM_RAW_TRIMMED);
        }

        return $filter;
    }

}