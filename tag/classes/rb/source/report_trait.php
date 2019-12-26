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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package core_tag
 */

namespace core_tag\rb\source;

defined('MOODLE_INTERNAL') || die();

trait report_trait {

    /**
     * Adds the tag tables to the $joinlist array
     *
     * @param string $component component for the tag
     * @param string $itemtype tag itemtype
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     $type table
     * @param string $field Name of course id field to join on
     * @return boolean True
     */
    protected function add_core_tag_tables($component, $itemtype, &$joinlist, $join, $field) {
        global $DB;


        $idlist = $DB->sql_group_concat($DB->sql_cast_2char('t.id'), '|');
        $joinlist[] = new \rb_join(
            'tagids',
            'LEFT',
            // subquery as table name
            "(SELECT til.id AS tilid, {$idlist} AS idlist
                FROM {{$itemtype}} til
           LEFT JOIN {tag_instance} ti ON til.id = ti.itemid AND ti.itemtype = '{$itemtype}'
           LEFT JOIN {tag} t ON ti.tagid = t.id AND t.isstandard = '1'
            GROUP BY til.id)",
            "tagids.tilid = {$join}.{$field}",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );


        $namelist = $DB->sql_group_concat($DB->sql_cast_2char('t.name'), ', ');
        $joinlist[] = new \rb_join(
            'tagnames',
            'LEFT',
            // subquery as table name
            "(SELECT tnl.id AS tnlid, {$namelist} AS namelist
                FROM {{$itemtype}} tnl
           LEFT JOIN {tag_instance} ti ON tnl.id = ti.itemid AND ti.itemtype = '{$itemtype}'
           LEFT JOIN {tag} t ON ti.tagid = t.id AND t.isstandard = '1'
            GROUP BY tnl.id)",
            "tagnames.tnlid = {$join}.{$field}",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );

        // Create a join for each tag in the collection.
        $tags = \core_tag\report_builder_tag_loader::get_tags($component, $itemtype);
        foreach ($tags as $tag) {
            $tagid = $tag->id;
            $name = "{$itemtype}_tag_$tagid";
            $joinlist[] = new \rb_join(
                $name,
                'LEFT',
                '{tag_instance}',
                "($name.itemid = $join.$field AND $name.tagid = $tagid " .
                    "AND $name.itemtype = '{$itemtype}')",
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                $join
            );
        }

        return true;
    }

    /**
     * Adds some common tag info to the $columnoptions array
     *
     * @param string $component component for the tag
     * @param string $itemtype tag itemtype
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $tagids name of the join that provides the 'tagids' table.
     * @param string $tagnames name of the join that provides the 'tagnames' table.
     *
     * @return True
     */
    protected function add_core_tag_columns($component, $itemtype, &$columnoptions, $tagids='tagids', $tagnames='tagnames') {
        $columnoptions[] = new \rb_column_option(
            'tags',
            'tagids',
            get_string('tagids', 'totara_reportbuilder'),
            "$tagids.idlist",
            array('joins' => $tagids, 'selectable' => false)
        );
        $columnoptions[] = new \rb_column_option(
            'tags',
            'tagnames',
            get_string('tags', 'totara_reportbuilder'),
            "$tagnames.namelist",
            array('joins' => $tagnames,
                  'dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );

        // Only get the tags in the collection for this item type.
        $tags = \core_tag\report_builder_tag_loader::get_tags($component, $itemtype);

        // Create a on/off field for every official tag.
        foreach ($tags as $tag) {
            $tagid = $tag->id;
            $name = $tag->name;
            $join = "{$itemtype}_tag_$tagid";
            $columnoptions[] = new \rb_column_option(
                'tags',
                $join,
                get_string('taggedx', 'totara_reportbuilder', $name),
                "CASE WHEN $join.id IS NOT NULL THEN 1 ELSE 0 END",
                array(
                    'joins' => $join,
                    'displayfunc' => 'yes_or_no',
                )
            );
        }
        return true;
    }

    /**
     * Adds some common tag filters to the $filteroptions array
     *
     * @param string $component component for the tag
     * @param string $itemtype tag itemtype
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     */
    protected function add_core_tag_filters($component, $itemtype, &$filteroptions) {
        // Only get the tags in the collection for this item type.
        $tags = \core_tag\report_builder_tag_loader::get_tags($component, $itemtype);

        // Create a yes/no filter for every official tag
        foreach ($tags as $tag) {
            $tagid = $tag->id;
            $name = $tag->name;
            $join = "{$itemtype}_tag_{$tagid}";
            $filteroptions[] = new \rb_filter_option(
                'tags',
                $join,
                get_string('taggedx', 'totara_reportbuilder', $name),
                'select',
                array(
                    'selectchoices' => array(1 => get_string('yes'), 0 => get_string('no')),
                    'simplemode' => true,
                )
            );
        }

        // Build filter list from tag list.
        $tagoptions = array();
        foreach ($tags as $tag) {
            $tagoptions[$tag->id] = $tag->name;
        }

        // create a tag list selection filter
        $filteroptions[] = new \rb_filter_option(
            'tags',         // type
            'tagids',           // value
            get_string('tags', 'totara_reportbuilder'), // label
            'multicheck',     // filtertype
            array(            // options
                'selectchoices' => $tagoptions,
                'concat' => true, // Multicheck filter needs to know that we are working with concatenated values
                'showcounts' => array(
                        'joins' => array("LEFT JOIN (SELECT ti.itemid, ti.tagid FROM {{$itemtype}} base " .
                                                      "LEFT JOIN {tag_instance} ti ON base.id = ti.itemid " .
                                                            "AND ti.itemtype = '{$itemtype}'" .
                                                      "LEFT JOIN {tag} tag ON ti.tagid = tag.id " .
                                                            "AND tag.isstandard = '1')\n {$itemtype}_tagids_filter " .
                                                "ON base.id = {$itemtype}_tagids_filter.itemid"),
                        'dataalias' => $itemtype.'_tagids_filter',
                        'datafield' => 'tagid')
            )
        );
        return true;
    }
}
