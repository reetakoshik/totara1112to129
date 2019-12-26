<?php
/**
 * This file is part of Totara LMS
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package block_totara_featured_links
 */

class backup_totara_featured_links_block_structure_step extends backup_block_structure_step {

    /**
     * Define the structure to be processed by this backup step.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        $tiles = new backup_nested_element(
            'tiles',
            ['id'],
            [
                'type', 'sortorder', 'timecreated', 'timemodified', 'dataraw', 'visibility', 'audienceaggregation',
                'presetsraw', 'presetsaggregation', 'overallaggregation', 'tilerules', 'audienceshowing', 'presetshowing',
                'tilerulesshowing', 'parentid'
            ]
        );
        $gallery = new backup_nested_element(
            'gallery'
        );
        $subtiles = new backup_nested_element(
            'subtiles',
            ['id'],
            [
                'type', 'sortorder', 'timecreated', 'timemodified', 'dataraw', 'visibility', 'audienceaggregation',
                'presetsraw', 'presetsaggregation', 'overallaggregation', 'tilerules', 'audienceshowing', 'presetshowing',
                'tilerulesshowing', 'parentid'
            ]
        );
        $visibility  = new backup_nested_element(
            'tilesvisibility',
            ['id'],
            [
                'cohortid'
            ]
        );

        $gallery->add_child($subtiles);
        $tiles->add_child($gallery);
        $tiles->add_child($visibility);

        $tiles->set_source_sql(
            'SELECT id, type, sortorder, timecreated, timemodified, dataraw, visibility, audienceaggregation, presetsraw,
                    presetsaggregation, overallaggregation, tilerules, audienceshowing, presetshowing, tilerulesshowing,
                    parentid
               FROM {block_totara_featured_links_tiles}
              WHERE blockid  = :blockid
                AND parentid = 0',
            [
                'blockid' => backup_helper::is_sqlparam($this->task->get_blockid())
            ]
        );
        $subtiles->set_source_sql(
            'SELECT id, type, sortorder, timecreated, timemodified, dataraw, visibility, audienceaggregation, presetsraw,
                    presetsaggregation, overallaggregation, tilerules, audienceshowing, presetshowing, tilerulesshowing,
                    parentid
               FROM {block_totara_featured_links_tiles}
              WHERE blockid  = :blockid
                AND parentid = :parentid',
            [
                'blockid' => backup_helper::is_sqlparam($this->task->get_blockid()),
                'parentid'=> backup::VAR_PARENTID
            ]
        );
        $visibility->set_source_sql(
            'SELECT id, cohortid
               FROM {cohort_visibility}
              WHERE instanceid   = :instanceid
                AND instancetype = :instancetype',
            [
                'instanceid'  => backup::VAR_PARENTID,
                'instancetype'=> backup_helper::is_sqlparam(COHORT_ASSN_ITEMTYPE_FEATURED_LINKS)
            ]
        );

        $subtiles->annotate_files('block_totara_featured_links', 'tile_background',  'id');
        $subtiles->annotate_files('block_totara_featured_links', 'tile_backgrounds', 'id');

        $tiles->annotate_files('block_totara_featured_links', 'tile_background',  'id');
        $tiles->annotate_files('block_totara_featured_links', 'tile_backgrounds', 'id');

        return $this->prepare_block_structure($tiles);
    }
}

