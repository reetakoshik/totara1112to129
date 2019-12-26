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
 * @author Joby Harding <joby.harding@totaralms.com>
 * @package tool_templatelibrary
 */

use tool_templatelibrary\example_data_formatter;

class example_data_formatter_testcase extends advanced_testcase {

    protected $initialthemedesignermode;

    protected function tearDown() {
        $this->initialthemedesignermode = null;
        parent::tearDown();
    }

    public function setUp() {

        global $CFG;

        $this->resetAfterTest();
        $this->initialthemedesignermode = $CFG->themedesignermode;
        theme_set_designer_mod(true);

    }

    /**
     * It should strip URLs from actionable HTML attributes in mustache template context data.
     */
    public function test_to_json_removes_actionable_attributes() {

        $templatecontext = array(
            'doublequotes' => array(
                "<a href='http://example.com/path/to/page.php'>",
                "<a href=\"http://example.com/path/to/page.php\">",
            ),
            'singlequotes' => array(
                '<form action="http://example.com/path/to/handler.php">',
                '<form action=\'http://example.com/path/to/handler.php\'>',
            ),
        );

        $expected =<<<JSON
{
    "doublequotes": [
        "<a href='#'>",
        "<a href=\"#\">"
    ],
    "singlequotes": [
        "<form action=\"#\">",
        "<form action='#'>"
    ]
}
JSON;
        // Data may be passed as an array or stdClass.
        $arrayactual = example_data_formatter::to_json($templatecontext);
        $stdclassactual = example_data_formatter::to_json((object)$templatecontext);

        $this->assertEquals($expected, $arrayactual);
        $this->assertEquals($expected, $stdclassactual);

    }

    /**
     * It should replace instances of $CFG->wwwroot with https://example.com
     */
    public function test_to_json_replaces_wwwroot() {

        global $CFG;

        $templatecontext = array("<a href=\"{$CFG->wwwroot}/index.php\">{$CFG->wwwroot}/index.php</a>");

        $expected =<<<JSON
[
    "<a href=\"#\">https://example.com/index.php</a>"
]
JSON;
        $actual = example_data_formatter::to_json($templatecontext);

        $this->assertEquals($expected, $actual);

    }

    /**
     * It should replace hostnames in image src with a placeholder.
     */
    public function test_to_json_adds_placeholders() {

        global $PAGE, $OUTPUT;

        $imgsrc = $OUTPUT->image_url('logo', 'totara_core');

        // Different quote configurations.
        $templatecontext = array(
            "<img src=\"" . $imgsrc . "\" />",
            "<img src='"  . $imgsrc . "' />",
            '<img src="'  . $imgsrc . '" />',
            '<img src=\'' . $imgsrc . '\' />'
        );

        $expected =<<<JSON
[
    "<img src=\"__WWWROOT__/theme/image.php?theme=__THEME__&amp;component=totara_core&amp;image=logo&amp;svg=0\" />",
    "<img src='__WWWROOT__/theme/image.php?theme=__THEME__&amp;component=totara_core&amp;image=logo&amp;svg=0' />",
    "<img src=\"__WWWROOT__/theme/image.php?theme=__THEME__&amp;component=totara_core&amp;image=logo&amp;svg=0\" />",
    "<img src='__WWWROOT__/theme/image.php?theme=__THEME__&amp;component=totara_core&amp;image=logo&amp;svg=0' />"
]
JSON;
        $actual = example_data_formatter::to_json($templatecontext);

        $this->assertEquals($expected, $actual);

    }

    /**
     * It should throw an exception when parameter is not array or object.
     */
    public function test_to_json_throws_when_themedesignermode_is_not_enabled() {

        theme_set_designer_mod(false);

        $this->expectException('coding_exception');

        example_data_formatter::to_json(array('foo'));

    }

    /**
     * It should throw an exception when parameter is not array or object.
     */
    public function test_to_json_throws_when_wrong_type() {

        $this->expectException('coding_exception');

        example_data_formatter::to_json(new moodle_url('/'));

    }

}
