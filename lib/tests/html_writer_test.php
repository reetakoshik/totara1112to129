<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the html_writer class.
 *
 * @package   core
 * @category  phpunit
 * @copyright 2010 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/outputcomponents.php');


/**
 * Unit tests for the html_writer class.
 *
 * @copyright 2010 Tim Hunt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_html_writer_testcase extends basic_testcase {

    public function test_start_tag() {
        $this->assertSame('<div>', html_writer::start_tag('div'));
    }

    public function test_start_tag_with_attr() {
        $this->assertSame('<div class="frog">',
            html_writer::start_tag('div', array('class' => 'frog')));
    }

    public function test_start_tag_with_attrs() {
        $this->assertSame('<div class="frog" id="mydiv">',
            html_writer::start_tag('div', array('class' => 'frog', 'id' => 'mydiv')));
    }

    public function test_end_tag() {
        $this->assertSame('</div>', html_writer::end_tag('div'));
    }

    public function test_empty_tag() {
        $this->assertSame('<br />', html_writer::empty_tag('br'));
    }

    public function test_empty_tag_with_attrs() {
        $this->assertSame('<input type="submit" value="frog" />',
            html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'frog')));
    }

    public function test_nonempty_tag_with_content() {
        $this->assertSame('<div>Hello world!</div>',
            html_writer::nonempty_tag('div', 'Hello world!'));
    }

    public function test_nonempty_tag_empty() {
        $this->assertSame('',
            html_writer::nonempty_tag('div', ''));
    }

    public function test_nonempty_tag_null() {
        $this->assertSame('',
            html_writer::nonempty_tag('div', null));
    }

    public function test_nonempty_tag_zero() {
        $this->assertSame('<div class="score">0</div>',
            html_writer::nonempty_tag('div', 0, array('class' => 'score')));
    }

    public function test_nonempty_tag_zero_string() {
        $this->assertSame('<div class="score">0</div>',
            html_writer::nonempty_tag('div', '0', array('class' => 'score')));
    }

    public function test_div() {
        // All options.
        $this->assertSame('<div class="frog" id="kermit">ribbit</div>',
                html_writer::div('ribbit', 'frog', array('id' => 'kermit')));
        // Combine class from attributes and $class.
        $this->assertSame('<div class="amphibian frog">ribbit</div>',
                html_writer::div('ribbit', 'frog', array('class' => 'amphibian')));
        // Class only.
        $this->assertSame('<div class="frog">ribbit</div>',
                html_writer::div('ribbit', 'frog'));
        // Attributes only.
        $this->assertSame('<div id="kermit">ribbit</div>',
                html_writer::div('ribbit', '', array('id' => 'kermit')));
        // No options.
        $this->assertSame('<div>ribbit</div>',
                html_writer::div('ribbit'));
    }

    public function test_start_div() {
        // All options.
        $this->assertSame('<div class="frog" id="kermit">',
                html_writer::start_div('frog', array('id' => 'kermit')));
        // Combine class from attributes and $class.
        $this->assertSame('<div class="amphibian frog">',
                html_writer::start_div('frog', array('class' => 'amphibian')));
        // Class only.
        $this->assertSame('<div class="frog">',
                html_writer::start_div('frog'));
        // Attributes only.
        $this->assertSame('<div id="kermit">',
                html_writer::start_div('', array('id' => 'kermit')));
        // No options.
        $this->assertSame('<div>',
                html_writer::start_div());
    }

    public function test_end_div() {
        $this->assertSame('</div>', html_writer::end_div());
    }

    public function test_span() {
        // All options.
        $this->assertSame('<span class="frog" id="kermit">ribbit</span>',
                html_writer::span('ribbit', 'frog', array('id' => 'kermit')));
        // Combine class from attributes and $class.
        $this->assertSame('<span class="amphibian frog">ribbit</span>',
                html_writer::span('ribbit', 'frog', array('class' => 'amphibian')));
        // Class only.
        $this->assertSame('<span class="frog">ribbit</span>',
                html_writer::span('ribbit', 'frog'));
        // Attributes only.
        $this->assertSame('<span id="kermit">ribbit</span>',
                html_writer::span('ribbit', '', array('id' => 'kermit')));
        // No options.
        $this->assertSame('<span>ribbit</span>',
                html_writer::span('ribbit'));
    }

    public function test_start_span() {
        // All options.
        $this->assertSame('<span class="frog" id="kermit">',
                html_writer::start_span('frog', array('id' => 'kermit')));
        // Combine class from attributes and $class.
        $this->assertSame('<span class="amphibian frog">',
                html_writer::start_span('frog', array('class' => 'amphibian')));
        // Class only.
        $this->assertSame('<span class="frog">',
                html_writer::start_span('frog'));
        // Attributes only.
        $this->assertSame('<span id="kermit">',
                html_writer::start_span('', array('id' => 'kermit')));
        // No options.
        $this->assertSame('<span>',
                html_writer::start_span());
    }

    public function test_end_span() {
        $this->assertSame('</span>', html_writer::end_span());
    }

    public function test_table() {
        $row = new html_table_row();

        // The attribute will get overwritten by the ID.
        $row->id = 'Bob';
        $row->attributes['id'] = 'will get overwritten';

        // The data-name will be present in the output.
        $row->attributes['data-name'] = 'Fred';
        $row->class = 'this is a table row';

        $cell = new html_table_cell();

        // The attribute will get overwritten by the ID.
        $cell->id = 'Jeremy';
        $cell->attributes['id'] = 'will get overwritten';

        // The data-name will be present in the output.
        $cell->attributes['data-name'] = 'John';
        $cell->class = 'this is a table cell';

        $row->cells[] = $cell;

        $table = new html_table();
        // The attribute will get overwritten by the ID.
        $table->id = 'Jeffrey';
        $table->attributes['id'] = 'will get overwritten';

        // The data-name will be present in the output.
        $table->attributes['data-name'] = 'Colin';
        // The attribute will get overwritten by the ID above.
        $table->data[] = $row;

        // Specify a caption to be output.
        $table->caption = "A table of meaningless data.";

        $output = html_writer::table($table);

        $expected = <<<EOF
<table class="generaltable" data-name="Colin" id="Jeffrey" data-origin="html_table">
    <caption>A table of meaningless data.</caption>
    <tbody>
        <tr class="lastrow" data-name="Fred" id="Bob">
            <td class="cell c0 lastcol" data-name="John" id="Jeremy"></td>
        </tr>
    </tbody>
</table>
EOF;

        $this->assertSame($expected, $output);
    }

    public function test_complex_table() {
        // Get hold of $PAGE so we can use a renderer.
        global $PAGE;
        $renderer = $PAGE->get_renderer('core');

        $table = new html_table;
        $table->id = 'templated-table';
        $table->caption = 'Complex Table';
        $table->attributes['class'] = 'my-class';
        $table->attributes['data-from'] = 'phpunit-test';

        // Table header.
        $header = new html_table_cell('Column 1 - has id, class and scope');
        $header->id ='this-is-the-first-header-cell';
        $header->attributes['class'] = 'just-testing-the-class';
        $header->scope = 'col';
        $head[] = $header;

        $header = new html_table_cell('Span column 2 & 3 - has italic styling');
        $header->colspan = 2;
        $header->style = 'font-style:italic';
        $head[] = $header;

        $header = new html_table_cell('Column 4 - td cell can be used in header');
        $header->header = 0;
        $head[] = $header;

        $table->head = $head;

        // Row 1.
        $cell = new html_table_cell("Row 1 Cell 1 - has id and abbr");
        $cell->id = "row-1-cell-1";
        $cell->abbr = "This is the first data cell in the table.";
        $cells[] = $cell;

        $cell = new html_table_cell("Row 1 Cell 2");
        $cells[] = $cell;
        $cell = new html_table_cell("Row 1 Cell 3");
        $cells[] = $cell;
        $cell = new html_table_cell("Row 1 Cell 4 - this should have the lastcol class");
        $cells[] = $cell;

        $row = new html_table_row($cells);
        $rows[] = $row;

        // Row 2.
        $cells = array();
        $cell = new html_table_cell("Row 2 Cell 1 - header, scope should default to row");
        $cell->header = true;
        $cells[] = $cell;
        $cell = new html_table_cell("Row 2 Cell 2");
        $cells[] = $cell;
        $cell = new html_table_cell("Row 2 Cell 3");
        $cells[] = $cell;
        $cell = new html_table_cell("Row 2 Cell 4 - this should have the lastcol class and span into row 3");
        $cell->rowspan = 2;
        $cells[] = $cell;

        $row = new html_table_row($cells);
        $rows[] = $row;

        // Row 3.
        $cells = array();
        $cell = new html_table_cell("Row 3 Cell 1 - span columns 1 and 2");
        $cell->colspan = 2;
        $cells[] = $cell;
        $cell = new html_table_cell("Row 3 Cell 2 - shouldn't have a lastcol class (it used to, incorrectly)");
        $cells[] = $cell;

        $row = new html_table_row($cells);
        $rows[] = $row;

        // Row 4.
        $cells = array();
        $cell = new html_table_cell("Row 4 Cell 1 - tr should have lastrow class and yellow background applied by style");
        $cells[] = $cell;
        $cell = new html_table_cell("Row 4 Cell 2");
        $cells[] = $cell;
        $cell = new html_table_cell("Row 4 Cell 3");
        $cells[] = $cell;
        $cell = new html_table_cell("Row 4 Cell 4 - this should have the lastcol class");
        $cells[] = $cell;

        $row = new html_table_row($cells);
        $row->id = 'this-row-should-be-in-a-separate-tbody';
        $row->divider = true;
        $row->style = 'background-color:yellow !important'; // Unfortunately, it won't appear, but check the rendered HTML.
        $rows[] = $row;

        $table->data = $rows;

        $actual = $renderer->render($table);

        $expected = <<<END
<table class="my-class" data-from="phpunit-test" id="templated-table" data-origin="html_table">
    <caption>Complex Table</caption>
    <thead>
        <tr>
            <th class="header c0 just-testing-the-class" id="this-is-the-first-header-cell" scope="col">Column 1 - has id, class and scope</th>
            <th class="header c1" colspan="2" style="font-style:italic" scope="col">Span column 2 & 3 - has italic styling</th>
            <td class="header c2 lastcol">Column 4 - td cell can be used in header</td>
        </tr>
    </thead>
    <tbody>
        <tr class="">
            <td class="cell c0" id="row-1-cell-1" abbr="This is the first data cell in the table.">Row 1 Cell 1 - has id and abbr</td>
            <td class="cell c1">Row 1 Cell 2</td>
            <td class="cell c2">Row 1 Cell 3</td>
            <td class="cell c3 lastcol">Row 1 Cell 4 - this should have the lastcol class</td>
        </tr>
        <tr class="">
            <th class="cell c0" scope="col">Row 2 Cell 1 - header, scope should default to row</th>
            <td class="cell c1">Row 2 Cell 2</td>
            <td class="cell c2">Row 2 Cell 3</td>
            <td class="cell c3 lastcol" rowspan="2">Row 2 Cell 4 - this should have the lastcol class and span into row 3</td>
        </tr>
        <tr class="">
            <td class="cell c0" colspan="2">Row 3 Cell 1 - span columns 1 and 2</td>
            <td class="cell c1">Row 3 Cell 2 - shouldn't have a lastcol class (it used to, incorrectly)</td>
        </tr>
        </tbody><tbody>
        <tr class="lastrow" id="this-row-should-be-in-a-separate-tbody" style="background-color:yellow !important">
            <td class="cell c0">Row 4 Cell 1 - tr should have lastrow class and yellow background applied by style</td>
            <td class="cell c1">Row 4 Cell 2</td>
            <td class="cell c2">Row 4 Cell 3</td>
            <td class="cell c3 lastcol">Row 4 Cell 4 - this should have the lastcol class</td>
        </tr>
    </tbody>
</table>
END;

        $this->assertSame($expected, $actual);
    }

}