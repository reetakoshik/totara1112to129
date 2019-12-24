<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 */

defined('MOODLE_INTERNAL') || die();

class repository_opensesame_util_testcase extends advanced_testcase {
    public function test_add_package_to_bundle() {
        global $DB;

        $this->resetAfterTest();

        $pkg1 = array('zipfilename' => 'abc.zip', 'title' => 'abc',
            'expirationdate' => '',  'mobilecompatibility' => '', 'externalid' => 'xyz', 'description' => 'efg', 'duration' => '',
            'timecreated' => 1416859984, 'timemodified' => 1416859984, 'visible' => 1);
        $id = $DB->insert_record('repository_opensesame_pkgs', $pkg1);
        $pkg1 = $DB->get_record('repository_opensesame_pkgs', array('id' => $id));

        $pkg2 = array('zipfilename' => 'def.zip', 'title' => 'def',
            'expirationdate' => '',  'mobilecompatibility' => '', 'externalid' => 'uvy', 'description' => 'dssd', 'duration' => '',
            'timecreated' => 1416859984, 'timemodified' => 1416859984, 'visible' => 1);
        $id = $DB->insert_record('repository_opensesame_pkgs', $pkg2);
        $pkg2 = $DB->get_record('repository_opensesame_pkgs', array('id' => $id));

        $pkg3 = array('zipfilename' => 'xxdef.zip', 'title' => 'xxdef',
            'expirationdate' => '',  'mobilecompatibility' => '', 'externalid' => 'xxxuvy', 'description' => 'dssd', 'duration' => '',
            'timecreated' => 1416859984, 'timemodified' => 1416859984, 'visible' => 1);
        $id = $DB->insert_record('repository_opensesame_pkgs', $pkg3);
        $pkg3 = $DB->get_record('repository_opensesame_pkgs', array('id' => $id));

        $this->assertCount(0, $DB->get_records('repository_opensesame_bps'));
        $this->assertCount(0, $DB->get_records('repository_opensesame_bdls'));
        $this->assertCount(3, $DB->get_records('repository_opensesame_pkgs'));

        \repository_opensesame\local\util::add_package_to_bundle('', $pkg1->id);

        $this->assertCount(1, $DB->get_records('repository_opensesame_bps'));
        $this->assertCount(1, $DB->get_records('repository_opensesame_bdls'));
        $this->assertCount(3, $DB->get_records('repository_opensesame_pkgs'));
        $nobundle = $DB->get_record('repository_opensesame_bdls', array('name' => ''));
        $this->assertNotEmpty($nobundle);
        $this->assertTrue($DB->record_exists('repository_opensesame_bps', array('bundleid' => $nobundle->id, 'packageid' => $pkg1->id)));

        \repository_opensesame\local\util::add_package_to_bundle('pokus', $pkg2->id);

        $this->assertCount(2, $DB->get_records('repository_opensesame_bps'));
        $this->assertCount(2, $DB->get_records('repository_opensesame_bdls'));
        $this->assertCount(3, $DB->get_records('repository_opensesame_pkgs'));
        $pokusbundle = $DB->get_record('repository_opensesame_bdls', array('name' => 'pokus'));
        $this->assertNotEmpty($pokusbundle);
        $this->assertTrue($DB->record_exists('repository_opensesame_bps', array('bundleid' => $nobundle->id, 'packageid' => $pkg1->id)));
        $this->assertTrue($DB->record_exists('repository_opensesame_bps', array('bundleid' => $pokusbundle->id, 'packageid' => $pkg2->id)));

        \repository_opensesame\local\util::add_package_to_bundle('pokus', $pkg1->id);

        $this->assertCount(2, $DB->get_records('repository_opensesame_bps'));
        $this->assertCount(2, $DB->get_records('repository_opensesame_bdls'));
        $this->assertCount(3, $DB->get_records('repository_opensesame_pkgs'));
        $this->assertTrue($DB->record_exists('repository_opensesame_bps', array('bundleid' => $pokusbundle->id, 'packageid' => $pkg1->id)));
        $this->assertTrue($DB->record_exists('repository_opensesame_bps', array('bundleid' => $pokusbundle->id, 'packageid' => $pkg2->id)));

        \repository_opensesame\local\util::add_package_to_bundle('', $pkg1->id);

        $this->assertCount(2, $DB->get_records('repository_opensesame_bps'));
        $this->assertCount(2, $DB->get_records('repository_opensesame_bdls'));
        $this->assertCount(3, $DB->get_records('repository_opensesame_pkgs'));
        $this->assertTrue($DB->record_exists('repository_opensesame_bps', array('bundleid' => $pokusbundle->id, 'packageid' => $pkg1->id)));
        $this->assertTrue($DB->record_exists('repository_opensesame_bps', array('bundleid' => $pokusbundle->id, 'packageid' => $pkg2->id)));

        \repository_opensesame\local\util::add_package_to_bundle('x x', $pkg3->id);

        $this->assertCount(3, $DB->get_records('repository_opensesame_bps'));
        $this->assertCount(3, $DB->get_records('repository_opensesame_bdls'));
        $this->assertCount(3, $DB->get_records('repository_opensesame_pkgs'));
        $xxbundle = $DB->get_record('repository_opensesame_bdls', array('name' => 'x x'));
        $this->assertNotEmpty($xxbundle);
        $this->assertTrue($DB->record_exists('repository_opensesame_bps', array('bundleid' => $pokusbundle->id, 'packageid' => $pkg1->id)));
        $this->assertTrue($DB->record_exists('repository_opensesame_bps', array('bundleid' => $pokusbundle->id, 'packageid' => $pkg2->id)));
        $this->assertTrue($DB->record_exists('repository_opensesame_bps', array('bundleid' => $xxbundle->id, 'packageid' => $pkg3->id)));
    }
}
