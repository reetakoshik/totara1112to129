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

/**
 * Class individual_helper
 *
 * @package mod_wiki\userdata
 */
final class collaborative_files extends wiki_userdata {

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
     * Set conditions to filter the sub-wikis to only the ones with uploaded user files.
     *
     * @return $this
     */
    protected function set_conditions() {
        $userid = intval($this->user->id);

        // Select only sub-wikis which have user files uploaded.
        $this->conditions[] = "EXISTS (SELECT files.id
                                FROM {files} files
                                WHERE files.component = 'mod_wiki' AND files.filearea = 'attachments'
                                    AND files.itemid = target.id AND files.userid = {$userid})";

        return $this;
    }

    /**
     * Perform export of user-related mod_wiki content
     *
     * @return array [$data, $files]
     */
    public function export() : array {
        $this->reset_files();
        $export = [];

        foreach ($this->subwikis as $record) {
            $export[intval($record->id)] = [
                'id' => intval($record->id),
                'name' => $record->wiki_name,
                'intro' => $record->wiki_intro,
                'files' => $this->export_files($record),
            ];
        }

        return [$export, $this->files];
    }

    /**
     * Count wiki instances for a given user
     *
     * @return int
     */
    public function count() : int {
        // Ineffective, rather do it dumb way by just counting records in the {files} table and not fetching the
        // context and consequently all the modules which is not needed here at all.
        // But since I presume we need to use file API, let's count export, as there is no api for counting files.
        // Our pre-fetched sub-wiki ids.
        $this->reset_files();
        foreach ($this->subwikis as $subwiki) {
            $this->export_files($subwiki);
        }

        return count($this->files);
    }
}