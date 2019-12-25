<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_totara_report_graph
 */

/**
 * Test the util class for report graph block.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_totara_report_graph
 */
class block_totara_report_graph_block_testcase extends advanced_testcase {

    use \block_totara_report_graph\phpunit\block_testing;

    public function test_get_content() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $rid = $this->create_user_report_with_graph();
        $blockinstance = $this->create_report_graph_block_instance($rid, ['graphimage_maxwidth' => 777]);

        $content = $blockinstance->get_content();
        $this->assertInstanceOf('stdClass', $content);
        $this->assertNotEmpty($content->text);
        $this->assertNotEmpty($content->footer);
        $this->assertContains('max-width:777px;', $content->text);
        $this->assertContains('max-height:327px;', $content->text);
        $this->assertContains('width="100%"', $content->text);
        $this->assertContains('height="100%"', $content->text);
        $this->assertContains('type="image/svg+xml"', $content->text);
        $this->assertContains('blocks/totara_report_graph/ajax_graph.php?blockid='.$blockinstance->instance->id, $content->text);
    }
}