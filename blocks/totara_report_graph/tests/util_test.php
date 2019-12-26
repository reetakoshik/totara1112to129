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
class block_totara_report_graph_util_testcase extends advanced_testcase {

    use \block_totara_report_graph\phpunit\block_testing;

    /**
     * Test the util normalise_size_and_user_input method.
     */
    public function test_normalise_size_and_user_input() {

        // First up test valid values:
        $this->assertSame('64px', \block_totara_report_graph\util::normalise_size_and_user_input('64'));
        $this->assertSame('64px', \block_totara_report_graph\util::normalise_size_and_user_input('64px'));
        $this->assertSame('64em', \block_totara_report_graph\util::normalise_size_and_user_input('64em'));
        $this->assertSame('64%', \block_totara_report_graph\util::normalise_size_and_user_input('64%'));
        $this->assertSame('64.32px', \block_totara_report_graph\util::normalise_size_and_user_input('64.32'));
        $this->assertSame('64.32px', \block_totara_report_graph\util::normalise_size_and_user_input('64.32px'));
        $this->assertSame('64.32em', \block_totara_report_graph\util::normalise_size_and_user_input('64.32em'));
        $this->assertSame('64.32%', \block_totara_report_graph\util::normalise_size_and_user_input('64.32%'));
        $this->assertSame('0.32px', \block_totara_report_graph\util::normalise_size_and_user_input('0.32'));
        $this->assertSame('0.32px', \block_totara_report_graph\util::normalise_size_and_user_input('0.32 px'));
        $this->assertSame('0.32px', \block_totara_report_graph\util::normalise_size_and_user_input('.32 PX'));
        $this->assertSame('64px', \block_totara_report_graph\util::normalise_size_and_user_input('64PX'));
        $this->assertSame('64px', \block_totara_report_graph\util::normalise_size_and_user_input('64pX'));
        $this->assertSame('64px', \block_totara_report_graph\util::normalise_size_and_user_input('64 PX'));
        $this->assertSame('64px', \block_totara_report_graph\util::normalise_size_and_user_input('64  px'));
        $this->assertSame('64px', \block_totara_report_graph\util::normalise_size_and_user_input(' 64PX'));
        $this->assertSame('64px', \block_totara_report_graph\util::normalise_size_and_user_input('64px '));
        $this->assertSame('64px', \block_totara_report_graph\util::normalise_size_and_user_input('  64  PX  '));
        $this->assertSame('64px', \block_totara_report_graph\util::normalise_size_and_user_input('  64  '));
        $this->assertSame('', \block_totara_report_graph\util::normalise_size_and_user_input('0'));
        $this->assertSame('', \block_totara_report_graph\util::normalise_size_and_user_input('0px'));
        $this->assertSame('', \block_totara_report_graph\util::normalise_size_and_user_input('0em'));
        $this->assertSame('', \block_totara_report_graph\util::normalise_size_and_user_input('0 px'));
        $this->assertSame('', \block_totara_report_graph\util::normalise_size_and_user_input('-0%'));
        $this->assertSame('', \block_totara_report_graph\util::normalise_size_and_user_input('0000px'));
        $this->assertSame('-75px', \block_totara_report_graph\util::normalise_size_and_user_input('-75px'));
        $this->assertSame('-75px', \block_totara_report_graph\util::normalise_size_and_user_input('-0075px'));
        $this->assertSame('75px', \block_totara_report_graph\util::normalise_size_and_user_input('0075px'));
        $this->assertSame('0.0075px', \block_totara_report_graph\util::normalise_size_and_user_input('.0075px'));
        $this->assertSame('0.75px', \block_totara_report_graph\util::normalise_size_and_user_input('00.75px'));
        $this->assertSame('', \block_totara_report_graph\util::normalise_size_and_user_input('00.00'));
        $this->assertSame('', \block_totara_report_graph\util::normalise_size_and_user_input(''));
        $this->assertSame('', \block_totara_report_graph\util::normalise_size_and_user_input('   '));

        // Now test invalid values:
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('px'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('em'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('%'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64px;'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64em;'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64%;'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('0;'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64pxpx'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64pxem'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64emem'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64%%'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64%64%'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('%%64'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64fu'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64.'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64.px'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('.64.'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('..64'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64..32'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64.32.'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64.3.2'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('.64.32'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('--64'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('64-'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('6-4'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('-'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('.'));
        $this->assertSame(null, \block_totara_report_graph\util::normalise_size_and_user_input('sixty four pixels'));
    }

    /**
     * Tests the util get_svg_data method.
     */
    public function test_get_svg_data() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $rid = $this->create_user_report_with_graph();
        $block = $this->create_report_graph_block_instance($rid);

        $svgdata = \block_totara_report_graph\util::get_svg_data($block->instance->id, $block->config);
        $this->assertIsString($svgdata);
        $this->assertNotEmpty($svgdata);
        $this->assertNotContains('789px', $svgdata); // This is the max-width, we don't expect to see it!
        $this->assertNotContains('327px', $svgdata); // This is the max-height, we don't expect to see it!
        $this->assertContains('New Zealand', $svgdata);
        $this->assertContains('United States', $svgdata);
        $this->assertContains('Australia', $svgdata);
        $this->assertContains('62.50%', $svgdata);
        $this->assertContains('25.00%', $svgdata);
        $this->assertContains('12.50%', $svgdata);

        $svgdata = str_replace("\n", '', $svgdata);
        $svgdata = preg_replace('#<script[^>]+>.*</script>#', '', $svgdata);

        $expected =  '<svg width="100%" height="100%" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 400 400" onload="init()" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg">';
        $expected .= '<rect width="100%" height="100%" fill="#fff" stroke-width="0px"/>';
        $expected .= '<path fill="#1f77b4" id="e2" d="M200,195 L380,195 A180 180 0 1,1 72.721,67.721 z"/>';
        $expected .= '<path fill="#ff7f0e" id="e4" d="M200,195 L72.721,67.721 A180 180 0 0,1 327.28,67.721 z"/>';
        $expected .= '<path fill="#2ca02c" id="e6" d="M200,195 L327.28,67.721 A180 180 0 0,1 380,195 z"/>';
        $expected .= '<g>';
        $expected .= '<g><text font-family="sans-serif" font-size="14px" fill="rgb(0,0,0)" x="148.84" y="325.12" text-anchor="middle">62.50%</text></g>';
        $expected .= '<g><text font-family="sans-serif" font-size="14px" fill="rgb(0,0,0)" x="200.5" y="65.4" text-anchor="middle">25.00%</text></g>';
        $expected .= '<g><text font-family="sans-serif" font-size="14px" fill="rgb(0,0,0)" x="325.22" y="148.74" text-anchor="middle">12.50%</text></g>';
        $expected .= '</g>';
        $expected .= '<g font-family="sans-serif" font-size="10px" fill="black" transform="translate(267,10)" id="e8">';
        $expected .= '<rect fill="#000" width="113" height="80" y="2.5" x="2.5" opacity="0.3"/>';
        $expected .= '<rect fill="white" width="113" height="80" stroke-width="1px" stroke="black"/>';
        $expected .= '<g transform="translate(5,0)">';
        $expected .= '<rect x="0" y="5" width="20" height="20" style="fill:#1f77b4;"/>';
        $expected .= '<rect x="0" y="30" width="20" height="20" style="fill:#ff7f0e;"/>';
        $expected .= '<rect x="0" y="55" width="20" height="20" style="fill:#2ca02c;"/>';
        $expected .= '</g>';
        $expected .= '<g transform="translate(30,0)" text-anchor="start">';
        $expected .= '<text x="0" y="17.5">New Zealand</text>';
        $expected .= '<text x="0" y="42.5">United States</text>';
        $expected .= '<text x="0" y="67.5">Australia</text>';
        $expected .= '</g>';
        $expected .= '</g>';
        $expected .= '</svg>';
        $this->assertSame($expected, $svgdata);
    }

}