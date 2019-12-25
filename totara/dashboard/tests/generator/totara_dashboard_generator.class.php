<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_dashboard
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/dashboard/lib.php');
require_once($CFG->dirroot . '/totara/core/lib.php');

/**
 * Dashboard generator.
 */
class totara_dashboard_generator extends component_generator_base {
    protected static $ind = 0;

    /**
     * Creates dashboard.
     * All parameter keys are optional.
     *
     * @param array('name' => Name of dashboard, 'locked' => bool, 'pusblished' => bool, 'cohorts' => array('cohortid', ...))
     * @return totara_dashboard instance
     */
    public function create_dashboard(array $data = array()) {
        global $DB;
        $dashboard = new totara_dashboard();
        if (!isset($data['name'])) {
            $data['name'] = 'Test' . self::$ind++;
        }
        if (!isset($data['locked'])) {
            $data['locked'] = false;
        }
        if (!isset($data['published'])) {
            $data['published'] = true;
        }
        if (isset($data['cohorts'])) {
            $cohorts = $data['cohorts'];
            if (!is_array($data['cohorts'])) {
                $cohorts = explode(', ', $data['cohorts']);
            }
            $data['cohorts'] = array();
            foreach ($cohorts as $cohort) {
                $cohort = trim($cohort);
                if ($cohort == '') {
                    continue;
                }
                if ((string)intval($cohort) == $cohort) {
                    $data['cohorts'][] = (int)$cohort;
                } else {
                    // Convert cohort name to id.
                    $record = $DB->get_record_select('cohort', 'name = ? OR idnumber = ?', array($cohort, $cohort));
                    $data['cohorts'][] = $record->id;
                }
            }
        }
        $dashboard->set_from_form((object)$data)->save();

        return $dashboard;
    }

    /**
     * Add block to current dashboard.
     *
     * @param integer $id The dashoard Id.
     * @param string $blockname The type of block to add.
     * @param integer $weight determines the order where this block appears in the region.
     * @param string $region the block region on this page to add the block to.
     * @param boolean $showinsubcontexts whether this block appears in subcontexts, or just the current context.
     * @param string|null $pagetypepattern which page types this block should appear on. Defaults to just the current page type.
     * @param string|default $subpagepattern which subpage this block should appear on. NULL = any (the default), otherwise only the specified subpage.
     */
    public function add_block($id, $blockname, $weight, $region = '', $showinsubcontexts = false, $pagetypepattern = null, $subpagepattern = 'default') {
        global $CFG, $DB;
        require_once($CFG->libdir . '/blocklib.php');

        $page = new moodle_page();
        $page->set_context(context_system::instance());
        $page->set_pagelayout('dashboard');
        $page->set_pagetype('totara-dashboard-' . $id);
        $page->set_subpage($subpagepattern);

        if (empty($pagetypepattern)) {
            $pagetypepattern = $page->pagetype;
        }

        $blockinstance = new stdClass;
        $blockinstance->blockname = $blockname;
        $blockinstance->parentcontextid = $page->context->id;
        $blockinstance->showinsubcontexts = !empty($showinsubcontexts);
        $blockinstance->pagetypepattern = $pagetypepattern;
        $blockinstance->subpagepattern = $subpagepattern;
        $blockinstance->defaultregion = !empty($region) ? $region : $page->blocks->get_default_region();
        $blockinstance->defaultweight = $weight;
        $blockinstance->configdata = '';
        $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);

        // Ensure the block context is created.
        context_block::instance($blockinstance->id);

        // If the new instance was created, allow it to do additional setup
        if ($block = block_instance($blockname, $blockinstance)) {
            $block->instance_create();
        }

        return $block;
    }
}