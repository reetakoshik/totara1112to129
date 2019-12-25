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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */
namespace totara_catalog\local;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\datasearch\full_text_search;
use totara_catalog\filter;
use totara_catalog\merge_select\search_text;

/**
 * Create the catalog full text search fitler.
 */
class full_text_search_filter {

    /**
     * Full text high relevance weight
     */
    const FTS_HEIGH_WEIGHT = 208;

    /**
     * Full text medium relevance weight
     */
    const FTS_MEDIUM_WEIGHT = 144;

    /**
     * Full text low relevance weight
     */
    const FTS_LOW_WEIGHT = 100;

    /**
     * Get search relevance weight
     *
     * @return array
     */
    final public static function get_search_relevance_weight(): array {
        global $CFG;

        $config = [
            'ftshigh'   => self::FTS_HEIGH_WEIGHT,
            'ftsmedium' => self::FTS_MEDIUM_WEIGHT,
            'ftslow'    => self::FTS_LOW_WEIGHT,
        ];

        if (isset($CFG->catalogrelevanceweight)) {
            if (isset($CFG->catalogrelevanceweight['high'])) {
                $config['ftshigh'] = $CFG->catalogrelevanceweight['high'];
            }
            if (isset($CFG->catalogrelevanceweight['medium'])) {
                $config['ftsmedium'] = $CFG->catalogrelevanceweight['medium'];
            }
            if (isset($CFG->catalogrelevanceweight['low'])) {
                $config['ftslow'] = $CFG->catalogrelevanceweight['low'];
            }
        }

        return $config;
    }

    /**
     * @return filter
     */
    public static function create(): filter {
        $datafilter = new full_text_search(
            'catalog_fts',
            'catalog',
            ['id']
        );
        $datafilter->set_fields_and_weights(static::get_search_relevance_weight());

        $datafilter->add_source(
            'notused', // The linking fields are specified by set_fields_and_weights.
            'notused',
            'catalogfts',
            ['id' => 'catalogfts.id']
        );

        $selector = new search_text(
            'catalog_fts',
            new \lang_string('fts_search_input', 'totara_catalog')
        );
        $selector->set_title_hidden(true);

        return new filter(
            'catalog_fts',
            filter::REGION_FTS,
            $datafilter,
            $selector
        );
    }
}
