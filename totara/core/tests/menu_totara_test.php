<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

use totara_core\totara\menu\item;
use totara_core\totara\menu\helper;

/**
 * Tests Main menu functions in totara/core/totara.php
 */
class totara_core_menu_totara_testcase extends advanced_testcase {
    public function test_totara_menu_reset_cache() {
        global $SESSION;
        $this->resetAfterTest();

        $rev = helper::get_cache_revision();
        $SESSION->mymenu = array('test');

        totara_menu_reset_cache();
        $this->assertDebuggingCalled('totara_menu_reset_cache() was deprecated, use totara_menu_reset_all_caches() or totara_menu_reset_session_cache() instead');
        $this->assertObjectNotHasAttribute('mymenu', $SESSION);

        $this->assertSame($rev, helper::get_cache_revision());
    }

    public function test_totara_menu_reset_all_caches() {
        global $SESSION;
        $this->resetAfterTest();

        $rev = helper::get_cache_revision();
        $SESSION->mymenu = array('test');

        totara_menu_reset_all_caches();
        $this->assertGreaterThan($rev, helper::get_cache_revision());
        $this->assertSame(array('test'), $SESSION->mymenu);
    }

    public function test_totara_menu_reset_session_cache() {
        global $SESSION;
        $this->resetAfterTest();

        $rev = helper::get_cache_revision();
        $SESSION->mymenu = array('test');

        totara_menu_reset_session_cache();
        $this->assertObjectNotHasAttribute('mymenu', $SESSION);

        $this->assertSame($rev, helper::get_cache_revision());
    }

    public function test_totara_build_menu() {
        global $CFG, $USER, $SESSION, $DB, $PAGE;
        $this->resetAfterTest();

        //have to set page url so menu comparisons for selected items don't fail
        $PAGE->set_url(new moodle_url($CFG->wwwroot . '/'));

        $unusedcontainerid = helper::get_unused_container_id();

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container 1';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container1 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = '0';
        $data->title = 'Test container 1H';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $container1h = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = $container1->id;
        $data->title = 'Test sub container 2';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container2 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = $container2->id;
        $data->title = 'Test sub sub container 3';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container3 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = $unusedcontainerid;
        $data->title = 'Unused container 4';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container4 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = -10;
        $data->title = 'Broken container 5';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container5 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'container';
        $data->parentid = $container3->id;
        $data->title = 'Too deep container 6';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $container6 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container1->id;
        $data->title = 'Test item 1';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item1 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container2->id;
        $data->title = 'Test item 2';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_self';
        $item2 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container3->id;
        $data->title = 'Test item 3';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item3 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container4->id;
        $data->title = 'Test item 4';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item4 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container5->id;
        $data->title = 'Test item 5';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item5 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container6->id;
        $data->title = 'Test item 6';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_blank';
        $item6 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = 0;
        $data->title = 'Test item 0';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_self';
        $data->sortorder = 2;
        $item0 = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = 0;
        $data->title = 'Test item 0H';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_HIDE;
        $data->targetattr = '_self';
        $item0h = helper::add_custom_menu_item($data);

        $data = new stdClass();
        $data->type = 'item';
        $data->parentid = $container1h->id;
        $data->title = 'Test item 1H';
        $data->url = '/xx';
        $data->visibility = (string)item::VISIBILITY_SHOW;
        $data->targetattr = '_self';
        $item1h = helper::add_custom_menu_item($data);

        $rev = helper::get_cache_revision();
        totara_menu_reset_session_cache();
        $this->setGuestUser();

        $menu = totara_build_menu();
        $this->assertSame('Test item 0', $menu[0]->linktext);
        $this->assertSame('', $menu[0]->parent);
        $this->assertSame('', $menu[0]->target);
        $this->assertSame('Home', $menu[1]->linktext);
        $this->assertSame('', $menu[1]->parent);
        $this->assertSame('Find Learning', $menu[2]->linktext);
        $this->assertSame('', $menu[2]->parent);
        $this->assertSame('Test container 1', $menu[3]->linktext);
        $this->assertSame('', $menu[3]->parent);
        $this->assertSame('Test sub container 2', $menu[4]->linktext);
        $this->assertSame('totaramenuitem' . $container1->id, $menu[4]->parent);
        $this->assertSame('Test item 2', $menu[5]->linktext);
        $this->assertSame('totaramenuitem' . $container2->id, $menu[5]->parent);
        $this->assertSame('', $menu[5]->target);
        $this->assertSame('Test item 1', $menu[6]->linktext);
        $this->assertSame('totaramenuitem' . $container1->id, $menu[6]->parent);
        $this->assertSame('_blank', $menu[6]->target);
        $this->assertCount(7, $menu);
        $this->assertSame($rev, helper::get_cache_revision());

        $CFG->menulifetime = 60 * 10;

        $this->assertSame('Test item 0', $menu[0]->linktext);
        $this->assertSame('', $menu[0]->parent);
        $this->assertSame('', $menu[0]->target);
        $this->assertSame('Home', $menu[1]->linktext);
        $this->assertSame('', $menu[1]->parent);
        $this->assertSame('Find Learning', $menu[2]->linktext);
        $this->assertSame('', $menu[2]->parent);
        $this->assertSame('Test container 1', $menu[3]->linktext);
        $this->assertSame('', $menu[3]->parent);
        $this->assertSame('Test sub container 2', $menu[4]->linktext);
        $this->assertSame('totaramenuitem' . $container1->id, $menu[4]->parent);
        $this->assertSame('Test item 2', $menu[5]->linktext);
        $this->assertSame('totaramenuitem' . $container2->id, $menu[5]->parent);
        $this->assertSame('', $menu[5]->target);
        $this->assertSame('Test item 1', $menu[6]->linktext);
        $this->assertSame('totaramenuitem' . $container1->id, $menu[6]->parent);
        $this->assertSame('_blank', $menu[6]->target);
        $this->assertCount(7, $menu);
        $this->assertSame($rev, helper::get_cache_revision());

        // Test cache is invalidated based on lifetime.
        totara_menu_reset_session_cache();
        $this->setCurrentTimeStart();
        $menu = totara_build_menu();
        $this->assertSame('Test item 0', $menu[0]->linktext);
        $this->assertCount(7, $menu);
        $this->assertTimeCurrent($SESSION->mymenu['c']);

        $item0->title = 'xx';
        $DB->update_record('totara_navigation', $item0);
        $SESSION->mymenu['c'] = time() - $CFG->menulifetime + 5;
        $menu = totara_build_menu();
        $this->assertSame('Test item 0', $menu[0]->linktext);
        $this->assertCount(7, $menu);

        $SESSION->mymenu['c'] = time() - $CFG->menulifetime - 1;
        $menu = totara_build_menu();
        $this->assertSame('xx', $menu[0]->linktext);
        $this->assertCount(7, $menu);
        $this->assertTimeCurrent($SESSION->mymenu['c']);

        // Test cache is invalidated based on current languages.
        $item0->title = 'Test item 0';
        $DB->update_record('totara_navigation', $item0);
        totara_menu_reset_session_cache();
        $menu = totara_build_menu();
        $this->assertSame('Test item 0', $menu[0]->linktext);
        $this->assertCount(7, $menu);

        $item0->title = 'xx';
        $DB->update_record('totara_navigation', $item0);
        $menu = totara_build_menu();
        $this->assertSame('Test item 0', $menu[0]->linktext);
        $this->assertSame('en', $SESSION->mymenu['lang']);
        $SESSION->lang = 'de';
        $menu = totara_build_menu();
        $this->assertSame('xx', $menu[0]->linktext);
        $this->assertSame('de', $SESSION->mymenu['lang']);

        // Test cache is invalidated based on cache revision
        $item0->title = 'Test item 0';
        $DB->update_record('totara_navigation', $item0);
        totara_menu_reset_session_cache();
        $menu = totara_build_menu();
        $this->assertSame('Test item 0', $menu[0]->linktext);
        $this->assertCount(7, $menu);
        $rev = helper::get_cache_revision();

        $item0->title = 'xx';
        $DB->update_record('totara_navigation', $item0);
        $menu = totara_build_menu();
        $this->assertSame('Test item 0', $menu[0]->linktext);
        $this->assertSame($rev, $SESSION->mymenu['rev']);
        helper::bump_cache_revision();
        $menu = totara_build_menu();
        $this->assertSame('xx', $menu[0]->linktext);
        $this->assertSame(helper::get_cache_revision(), $SESSION->mymenu['rev']);

        // Test cache is invalidated based on current user.
        $item0->title = 'Test item 0';
        $DB->update_record('totara_navigation', $item0);
        totara_menu_reset_session_cache();
        $menu = totara_build_menu();
        $this->assertSame('Test item 0', $menu[0]->linktext);
        $this->assertCount(7, $menu);

        $item0->title = 'xx';
        $DB->update_record('totara_navigation', $item0);
        $menu = totara_build_menu();
        $this->assertSame('Test item 0', $menu[0]->linktext);
        $this->assertSame($USER->id, $SESSION->mymenu['id']); // Guest
        $USER->id = '0'; // not logged in
        $menu = totara_build_menu();
        $this->assertSame('xx', $menu[0]->linktext);
        $this->assertSame('0', $SESSION->mymenu['id']);
    }

    public function test_totara_menu_selected() {
        global $CFG, $PAGE, $FULLME;
        $this->resetAfterTest();

        $PAGE->set_url('/xx'); //set junk URL so it doesn't match anything
        $FULLME = $CFG->wwwroot . '/index.php?redirect=0';

        // Check that the page matches $FULLME correctly if nothing else matches
        $menu = totara_build_menu();
        foreach ($menu as $k => $node) {
            if ($CFG->wwwroot . $node->url === $FULLME) {
                $this->assertTrue($node->is_selected);
            } else {
                $this->assertFalse($node->is_selected);
            }
        }
        $this->resetDebugging();

        // Check that $PAGE->set_url correctly highlights the values,
        // and correctly overrides the $FULLME value above
        $url = '/totara/catalog/index.php';
        $PAGE->set_url($url);

        $menu = totara_build_menu();
        foreach ($menu as $k => $node) {
            if ($node->url === $url) {
                $this->assertTrue($node->is_selected);
            } else {
                $this->assertFalse($node->is_selected);
            }
        }

        // Test that we can specifically set the menuitem, and that it overrides
        // $PAGE->set_url above
        $menuitem = '\totara_core\totara\menu\home';
        $PAGE->set_totara_menu_selected($menuitem);

        $menu = totara_build_menu();
        foreach ($menu as $k => $node) {
            if ($node->classname === $menuitem) {
                $this->assertTrue($node->is_selected);
            } else {
                $this->assertFalse($node->is_selected);
            }
        }

        // Use REAL class name without the leading backslash.
        $PAGE->set_totara_menu_selected(totara_core\totara\menu\home::class);
        $menuitem = '\\' . totara_core\totara\menu\home::class;
        $menu = totara_build_menu();
        foreach ($menu as $k => $node) {
            if ($node->classname === $menuitem) {
                $this->assertTrue($node->is_selected);
            } else {
                $this->assertFalse($node->is_selected);
            }
        }
    }

    public function test_totara_upgrade_menu() {
        global $DB;
        $this->resetAfterTest();

        $rev = helper::get_cache_revision();

        $defaultitems = $DB->get_records_menu('totara_navigation', array(), 'classname ASC', 'classname AS c1, classname AS c2');
        $classes = \core_component::get_namespace_classes('totara\menu', 'totara_core\totara\menu\item', null, true);

        $DB->delete_records('totara_navigation', array());

        totara_upgrade_menu();

        $resultitems = $DB->get_records_menu('totara_navigation', array(), 'classname ASC', 'classname AS c1, classname AS c2');
        $this->assertSame($defaultitems, $resultitems);
        $this->assertCount(count($classes) - 2, $resultitems); // item and container class are for custom classes.

        $this->assertGreaterThan($rev, helper::get_cache_revision());
    }
}
