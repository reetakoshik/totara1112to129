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
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone;

defined('MOODLE_INTERNAL') || die();

final class string_manager {

    /**
     * @var \core_string_manager
     */
    private $manager;

    /**
     * @var array
     */
    private $languages;

    /**
     * Constructor
     */
    public function __construct() {
        $this->manager = get_string_manager();
        $this->languages = $this->manager->get_list_of_languages();
    }

    /**
     * @param string $lang
     * @return string
     */
    public function get_language(string $lang): string {
        if (array_key_exists($lang, $this->languages)) {
            return $this->languages[$lang];
        }
        if (\core_text::strpos($lang, '-') > 0) {
            list($langcode, $countrycode) = explode('-', $lang, 2);
            if (array_key_exists($langcode, $this->languages)) {
                $string = $this->languages[$langcode];
                $countrycode = clean_param(\core_text::strtoupper($countrycode), PARAM_STRINGID);
                if ($this->manager->string_exists($countrycode, 'core_countries')) {
                    $a = new \stdClass();
                    $a->lang = $string;
                    $a->country = get_string($countrycode, 'core_countries');
                    return get_string('langwithcode', 'contentmarketplace_goone', $a);
                }
            }
        }
        if (empty($lang)) {
            return get_string('unknownlanguage', 'contentmarketplace_goone');
        }
        return $lang;
    }

    /**
     * @param string $region
     * @return string
     */
    public function get_region($region) {
        if (empty($region)) {
            return '';
        }
        $identifier = 'region:' . clean_param($region, PARAM_STRINGID);
        if ($this->manager->string_exists($identifier, 'contentmarketplace_goone')) {
            return get_string($identifier, 'contentmarketplace_goone');
        }
        return $region;
    }

}
