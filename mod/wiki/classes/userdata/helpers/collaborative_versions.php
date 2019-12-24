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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package mod_wiki
 */

namespace mod_wiki\userdata\helpers;


use stdClass;

/**
 * Class collaborative_versions_helper
 *
 * @package mod_wiki\userdata
 */
final class collaborative_versions extends wiki_userdata {

    /**
     * Wiki mode type
     */
    protected const TYPE = 'collaborative';

    /**
     * File objects
     *
     * @var array Files
     */
    protected $files = [];

    /**
     * Filter sub-wikis to select only the sub-wikis with pages which have revisions saved by the given user.
     *
     * @return $this
     */
    public function set_conditions()
    {
        $userid = intval($this->user->id);

        // Filter only by sub-wikis which actually have any pages with page versions created by user.
        $this->conditions[] =
            "EXISTS (SELECT pages.id FROM {wiki_pages} pages
                WHERE pages.subwikiid = target.id AND
                    EXISTS (SELECT * FROM {wiki_versions} versions
                        WHERE versions.pageid = pages.id AND versions.userid = {$userid} AND versions.version > 0))";

        return $this;
    }

    /**
     * Perform export of user-related mod_wiki content
     *
     * @return array [$data, $files]
     */
    public function export() : array {
        $export = [];

        foreach ($this->subwikis as $record) {
            $export[intval($record->id)] = [
                'id' => intval($record->id),
                'name' => $record->wiki_name,
                'intro' => $record->wiki_intro,
                'pages' => $this->export_pages($record),
            ];
        }

        return $export;
    }

    /**
     * Export sub-wiki pages
     *
     * @param stdClass $subwiki Sub-wiki record
     * @return array
     */
    protected function export_pages(stdClass $subwiki) : array {
        $pages = array_filter($this->pages, function($page) use ($subwiki) { return $page->subwikiid == $subwiki->id; });

        $export = [];

        foreach ($pages as $page) {
            $versions = $this->export_versions($page);

            if (empty($versions)) {
                continue;
            }

            $export[intval($page->id)] = [
                'id' => intval($page->id),
                'title' => $page->title,
                'versions' => $versions,
            ];
        }

        return $export;
    }

    /**
     * Count wiki page versions for a given user
     *
     * @return int
     */
    public function count() : int {
        $ids = $this->get_cached_page_ids(true);
        $userid = intval($this->user->id);

        return $this->db->count_records_sql("SELECT count(id) 
                                                  FROM {wiki_versions}
                                                  WHERE pageid IN ({$ids}) AND userid = {$userid}
                                                      AND version > 0");
    }
}