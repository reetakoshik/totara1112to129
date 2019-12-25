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

namespace totara_contentmarketplace\local\contentmarketplace;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @package totara_contentmarketplace
 */
abstract class search {

    /**
     * List sorting options available for the search results.
     *
     * @return array
     */
    abstract public function sort_options();

    /**
     * Perform search query.
     *
     * @param string $keyword
     * @param string $sort
     * @param array $filter
     * @param int $page
     * @param bool $isfirstquerywithdefaultsort
     * @param string $mode
     * @param \context $context
     * @return search_results
     */
    abstract public function query(string $keyword, string $sort, array $filter, int $page, bool $isfirstquerywithdefaultsort, string $mode, \context $context): search_results;

    /**
     * @param string $query
     * @param array $filter
     * @param string $mode
     * @param \context $context
     * @return array
     */
    abstract public function select_all($query, array $filter, string $mode, \context $context);

    /**
     * @param int $id
     * @return mixed
     */
    abstract public function get_details(int $id);

    /**
     * @param array $listing
     * @param int $firstpagesize
     * @param int $otherpagesize
     * @param int $orphan
     * @return array
     */
    public static function paginate(array $listing, $firstpagesize=5, $otherpagesize=10, $orphan=2) {
        $pages = [];
        $total = count($listing);
        for ($n = 0; $n < $total; $n += $pagesize) {
            $pagesize = $n == 0 ? $firstpagesize : $otherpagesize;
            $class = $n > 0 ? 'hidden' : '';
            if (($total - ($n + $pagesize)) <= $orphan) {
                $length = $total - $n;
                $last = true;
            } else {
                $length = $pagesize;
                $last = false;
            }
            $pages[] = [
                'options' => array_slice($listing, $n, $length),
                'class' => $class,
            ];
            if ($last) {
                break;
            }
        }
        return [
            'pages' => $pages,
            'show_more' => count($pages) > 1,
        ];
    }

    /**
     * @param array $listing
     * @return array
     */
    public static function sort(array $listing) {
        $compare = function ($a, $b) {
            if ($a['checked'] != $b['checked']) {
                return $b['checked'] ? 1 : -1;
            }
            if ($a['count'] == $b['count']) {
                return strcasecmp($a['label'], $b['label']);
            }
            return ($a['count'] < $b['count']) ? 1 : -1;
        };
        usort($listing, $compare);
        return $listing;
    }

    /**
     * @param \context $context
     * @param string $mode
     * @return array
     */
    abstract public function get_filter_seeds($context, $mode);

}
