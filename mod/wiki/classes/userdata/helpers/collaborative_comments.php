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
 * Class collaborative_comments_helper
 *
 * @package mod_wiki\userdata
 */
final class collaborative_comments extends wiki_userdata {

    /**
     * Wiki mode type
     */
    protected const TYPE = 'collaborative';

    /**
     * Perform purge
     */
    public function purge() : void {
        $this->purge_comments();
    }

    /**
     * Perform export of user-related mod_wiki content
     *
     * @return array [$data, $files]
     */
    public function export() : array {
        $export = [];

        foreach ($this->subwikis as $record) {
            $pages = $this->export_pages($record);

            if (empty($pages)) {
                continue;
            }

            $export[intval($record->id)] = [
                'id' => intval($record->id),
                'name' => $record->wiki_name,
                'intro' => $record->wiki_intro,
                'pages' => $pages,
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
            $comments = $this->export_comments($page->id);

            if (empty($comments)) {
                continue;
            }

            $export[intval($page->id)] = [
                'id' => intval($page->id),
                'title' => $page->title,
                'comments' => $comments,
            ];
        }

        return $export;
    }

    /**
     * Count wiki instances for a given user
     *
     * @return int
     */
    public function count() : int {
        $pages = $this->get_cached_page_ids(true);
        $user = intval($this->user->id);

        return $this->db->count_records_sql("
             SELECT count(id)
             FROM {comments}
             WHERE component = 'mod_wiki' AND commentarea = 'wiki_page' AND itemid IN ({$pages}) AND userid = {$user}");
    }
}