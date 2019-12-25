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
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>>
 * @package   core
 */

use core\output\flex_icon;
use core\output\flex_icon_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit unit tests for \core\output\flex_icon class.
 */
class totara_core_flex_icon_testcase extends advanced_testcase {
    public function test_exists() {
        $this->assertTrue(flex_icon::exists('edit'));
        $this->assertTrue(flex_icon::exists('mod_forum|t/unsubscribed'));
        $this->assertTrue(flex_icon::exists('core|i/edit'));
        $this->assertTrue(flex_icon::exists('mod_book|icon'));
        $this->assertTrue(flex_icon::exists('mod_book|nav_exit'));

        $this->assertFalse(flex_icon::exists('fdfdsfdsfdsdfs'));
    }

    public function test_constructor() {
        // New icon names.
        $identifier = 'edit';
        $icon = new flex_icon($identifier);
        $this->assertSame($identifier, $icon->identifier);
        $this->assertSame(array(), $icon->customdata);
        $this->assertDebuggingNotCalled();

        $identifier = 'mod_forum|t/unsubscribed';
        $icon = new flex_icon($identifier);
        $this->assertSame($identifier, $icon->identifier);
        $this->assertSame(array(), $icon->customdata);
        $this->assertDebuggingNotCalled();

        // Legacy icon name.
        $identifier = 'core|i/edit';
        $customdata = array('classes' => 'boldstuff');
        $icon = new flex_icon($identifier, $customdata);
        $this->assertSame($identifier, $icon->identifier);
        $this->assertSame($customdata, $icon->customdata);
        $this->assertDebuggingNotCalled();

        // Deprecated icon.
        $identifier = 'mod_book|nav_exit';
        $customdata = array('classes' => 'deprecatedstuff');
        $icon = new flex_icon($identifier, $customdata);
        $this->assertSame($identifier, $icon->identifier);
        $this->assertSame($customdata, $icon->customdata);
        $this->assertDebuggingNotCalled();

        // Missing icon.
        new flex_icon(flex_icon_helper::MISSING_ICON);
        $this->assertDebuggingNotCalled();

        $identifier = 'fdfdsfdsfdsdfs';
        $customdata = array('classes' => 'missingstuff');
        $icon = new flex_icon($identifier, $customdata);
        $this->assertSame($identifier, $icon->identifier);
        $this->assertSame($customdata, $icon->customdata);
        $this->assertDebuggingCalled("Flex icon '$identifier' not found");

        // Legacy data.
        $icon = new flex_icon('edit');
        $this->assertInstanceOf('pix_icon', $icon);
        $this->assertSame('flexicon', $icon->pix);
        $this->assertSame(array('alt' => '', 'class' => ''), $icon->attributes);
        $this->assertSame('core', $icon->component);

        $icon = new flex_icon('core|i/edit', array('alt' => 'Alt text', 'classes' => 'xx zz'));
        $this->assertSame('flexicon', $icon->pix);
        $this->assertSame(array('class' => 'xx zz', 'alt' => 'Alt text', 'title' => 'Alt text'), $icon->attributes);
        $this->assertSame('core', $icon->component);

        $icon = new flex_icon('mod_book|chapter', array());
        $this->assertSame('flexicon', $icon->pix);
        $this->assertSame(array('alt' => '', 'class' => ''), $icon->attributes);
        $this->assertSame('mod_book', $icon->component);
    }

    public function test_get_template() {
        // New icon names.
        $this->assertSame('core/flex_icon', (new flex_icon('edit'))->get_template());
        $this->assertSame('core/flex_icon_stack', (new flex_icon('mod_forum|t/unsubscribed'))->get_template());

        // Legacy icon name.
        $this->assertSame('core/flex_icon', (new flex_icon('core|i/edit'))->get_template());

        // Deprecated icon.
        $this->assertSame('core/flex_icon', (new flex_icon('mod_book|nav_exit'))->get_template());

        // Missing icon.
        $missingiconstemplate = (new flex_icon(flex_icon_helper::MISSING_ICON))->get_template();
        $this->assertDebuggingNotCalled();
        $this->assertSame($missingiconstemplate, (new flex_icon('fdfdsfdsfdsdfs'))->get_template());
        $this->assertDebuggingCalled();
    }

    public function test_export_for_template() {
        global $PAGE;

        /** @var core_renderer $renderer */
        $renderer = $PAGE->get_renderer('core');

        // New icon names.
        $icon = new flex_icon('edit', array('classes' => 'normalstuff'));
        $expected = array(
            'classes' => 'fa-edit',
            'identifier' => 'edit',
            'customdata' => array('classes' => 'normalstuff'),
        );
        $this->assertSame($expected, $icon->export_for_template($renderer));
        $icon = new flex_icon('mod_forum|t/unsubscribed', array('classes' => 'compositestuff'));
        $expected = array(
            'classes' => array(
                'fa-envelope-o ft-stack-main',
                'fa-times ft-stack-suffix ft-state-danger',
            ),
            'identifier' => 'mod_forum|t/unsubscribed',
            'customdata' => array('classes' => 'compositestuff'),
        );
        $this->assertSame($expected, $icon->export_for_template($renderer));

        // Legacy icon name.
        $icon = new flex_icon('core|i/edit');
        $expected = array(
            'classes' => 'fa-edit',
            'identifier' => 'core|i/edit',
            'customdata' => array(),
        );
        $this->assertSame($expected, $icon->export_for_template($renderer));

        // Deprecated icon.
        $icon = new flex_icon('mod_book|nav_exit');
        $expected = array(
            'classes' => 'fa-caret-up',
            'identifier' => 'mod_book|nav_exit',
            'customdata' => array(),
        );
        $this->assertSame($expected, $icon->export_for_template($renderer));

        // Missing icon.
        $missingicondata = (new flex_icon(flex_icon_helper::MISSING_ICON))->export_for_template($renderer);
        $this->assertDebuggingNotCalled();
        $icon = new flex_icon('fdfdsfdsfdsdfs');
        $this->assertDebuggingCalled();
        $missingicondata['identifier'] = 'fdfdsfdsfdsdfs';
        $this->assertSame($missingicondata, $icon->export_for_template($renderer));
    }

    public function test_create_from_pix_icon() {
        $pixicon = new pix_icon('i/edit', 'Alt text');
        $flexicon = flex_icon::create_from_pix_icon($pixicon);
        $this->assertInstanceOf('core\output\flex_icon', $flexicon);
        $this->assertSame('core|i/edit', $flexicon->identifier);
        $this->assertSame(array('alt' => 'Alt text'), $flexicon->customdata);

        $pixicon = new pix_icon('i/edit', 'Alt text');
        $flexicon = flex_icon::create_from_pix_icon($pixicon, 'hokus pokus');
        $this->assertInstanceOf('core\output\flex_icon', $flexicon);
        $this->assertSame('core|i/edit', $flexicon->identifier);
        $this->assertSame(array('classes' => 'hokus pokus', 'alt' => 'Alt text'), $flexicon->customdata);

        $pixicon = new pix_icon('i/edit', 'Alt text');
        $flexicon = flex_icon::create_from_pix_icon($pixicon, array('hokus', 'pokus'));
        $this->assertInstanceOf('core\output\flex_icon', $flexicon);
        $this->assertSame('core|i/edit', $flexicon->identifier);
        $this->assertSame(array('classes' => 'hokus pokus', 'alt' => 'Alt text'), $flexicon->customdata);

        $pixicon = new pix_icon('icon', '', 'book');
        $flexicon = flex_icon::create_from_pix_icon($pixicon);
        $this->assertInstanceOf('core\output\flex_icon', $flexicon);
        $this->assertSame('mod_book|icon', $flexicon->identifier);
        $this->assertSame(array('alt' => ''), $flexicon->customdata);

        $pixicon = new pix_icon('icon', '', 'mod_book');
        $flexicon = flex_icon::create_from_pix_icon($pixicon);
        $this->assertInstanceOf('core\output\flex_icon', $flexicon);
        $this->assertSame('mod_book|icon', $flexicon->identifier);
        $this->assertSame(array('alt' => ''), $flexicon->customdata);

        $pixicon = new pix_icon('icon', 'Alt text', 'forum', array('class' => 'activityicon otherclass'));
        $flexicon = flex_icon::create_from_pix_icon($pixicon);
        $this->assertInstanceOf('core\output\flex_icon', $flexicon);
        $this->assertSame('mod_forum|icon', $flexicon->identifier);
        $this->assertSame(array('classes' => 'activityicon otherclass', 'alt' => 'Alt text'), $flexicon->customdata);

        $pixicon = new pix_icon('grrrrgrgrg', 'Some Forum', 'forum');
        $flexicon = flex_icon::create_from_pix_icon($pixicon);
        $this->assertNull($flexicon);

        // Title text explicitly set when creating pix_icon instance
        // should be reflected in resulting flex_icon instance.
        $attributes = array('class' => 'activityicon otherclass', 'title' => 'Title text');
        $pixicon = new pix_icon('icon', 'Alt text', 'forum', $attributes);
        $flexicon = flex_icon::create_from_pix_icon($pixicon);
        $this->assertInstanceOf('core\output\flex_icon', $flexicon);
        $this->assertSame('mod_forum|icon', $flexicon->identifier);
        $this->assertSame(array('classes' => 'activityicon otherclass', 'alt' => 'Alt text', 'title' => 'Title text'), $flexicon->customdata);

        // Title MUST NOT be set if it simply duplicates alt text.
        // Conversion code ignores setting title if alt already set and is the same.
        $attributes = array('alt' => 'Alt text', 'title' => 'Alt text');
        $pixicon = new pix_icon('icon', 'Alt text', 'forum', $attributes);
        $flexicon = flex_icon::create_from_pix_icon($pixicon);
        $this->assertInstanceOf('core\output\flex_icon', $flexicon);
        $this->assertSame('mod_forum|icon', $flexicon->identifier);
        $this->assertSame(array('alt' => 'Alt text'), $flexicon->customdata);
    }

    public function test_create_from_pix_url() {
        global $CFG, $PAGE;
        $this->resetAfterTest();

        $url = 'http://www.example.com/moodle/theme/image.php/_s/basis/forum/1/icon';
        $flexicon = flex_icon::create_from_pix_url($url);
        $this->assertInstanceOf('core\output\flex_icon', $flexicon);
        $this->assertSame('mod_forum|icon', $flexicon->identifier);

        $CFG->slasharguments = 1;
        $pixurl = $PAGE->theme->image_url('i/edit', 'core');
        $flexicon = flex_icon::create_from_pix_url($pixurl);
        $expected = new flex_icon('core|i/edit');
        $this->assertEquals($expected, $flexicon);

        $pixurl = $PAGE->theme->image_url('xxx/zzz', 'eee');
        $flexicon = flex_icon::create_from_pix_url($pixurl);
        $this->assertNull($flexicon);

        $flexicon = flex_icon::create_from_pix_url('xxx.xx');
        $this->assertNull($flexicon);

        $CFG->slasharguments = 0;
        $pixurl = $PAGE->theme->image_url('i/edit', 'core');
        $flexicon = flex_icon::create_from_pix_url($pixurl);
        $expected = new flex_icon('core|i/edit');
        $this->assertEquals($expected, $flexicon);
    }

    /**
     * Test the convenience method outputs a pix icon string.
     *
     * This test should not strictly be in this class however as there
     * is not currently a test file for outputrenderers.php and the
     * functionality is related to flex_icons we include it.
     */
    public function test_render_flex_icon() {
        global $PAGE;

        /** @var core_renderer $renderer */
        $renderer = $PAGE->get_renderer('core');

        $icon = new flex_icon('edit');
        $expected = $renderer->render_from_template($icon->get_template(), $icon->export_for_template($renderer));
        $this->assertSame($expected, $renderer->render($icon));

        $deprecatedicon = new flex_icon('core|i/edit');
        $this->assertSame(str_replace('data-flex-icon="edit"', 'data-flex-icon="core|i/edit"', $expected), $renderer->render($deprecatedicon));

        $stackdicon = new flex_icon('mod_forum|t/unsubscribed');
        $expected = $renderer->render_from_template($stackdicon->get_template(), $stackdicon->export_for_template($renderer));
        $this->assertSame($expected, $renderer->render($stackdicon));

        // Test rendering with incorrect template, the result does not matter, but there must not be fatal errors.

        $this->assertSame('core/flex_icon', $icon->get_template());
        @$renderer->render_from_template('core/flex_icon_stack', $icon->export_for_template($renderer));

        $this->assertSame('core/flex_icon_stack', $stackdicon->get_template());
        @$renderer->render_from_template('core/flex_icon', $stackdicon->export_for_template($renderer));
    }

    public function test_render_pix_icon() {
        global $PAGE;

        /** @var core_renderer $renderer */
        $renderer = $PAGE->get_renderer('core');

        $expected = $renderer->render(new flex_icon('core|i/edit'));
        $this->assertSame($expected, $renderer->render(new pix_icon('i/edit', '')));
        $this->assertSame($expected, $renderer->render(new pix_icon('i/edit', '', 'moodle')));
        $this->assertSame($expected, $renderer->render(new pix_icon('i/edit', '', 'core')));
        $this->assertSame($expected, $renderer->render(new pix_icon('i/edit', '', '')));
        $this->assertSame($expected, $renderer->render(new pix_icon('/i/edit', '')));

        $expected = $renderer->render(new flex_icon('mod_book|icon'));
        $this->assertSame($expected, $renderer->render(new pix_icon('icon', '', 'mod_book')));
        $this->assertSame($expected, $renderer->render(new pix_icon('icon', '', 'book')));
        $this->assertSame($expected, $renderer->render(new pix_icon('/icon', '', 'book')));
    }

    public function test_render_action_icon() {
        global $PAGE;

        /** @var core_renderer $renderer */
        $renderer = $PAGE->get_renderer('core');

        $url = new moodle_url('/');
        $flexicon = new flex_icon('edit');
        $pixicon = new pix_icon('i/edit', '');

        $expected = $renderer->action_icon($url, $flexicon);
        $expected = str_replace('data-flex-icon="edit"', 'data-flex-icon="core|i/edit"', $expected);
        $result = $renderer->action_icon($url, $pixicon);

        // The action link changes, so get rid of it.
        $expected = preg_replace('/id="action_link[^"]+"/', '', $expected);
        $result = preg_replace('/id="action_link[^"]+"/', '', $result);

        $this->assertSame($expected, $result);
    }

    public function test_pix_icon_url() {
        global $PAGE, $CFG;

        $url = $PAGE->theme->image_url('i/edit', 'core');
        $this->assertInstanceOf('moodle_url', $url);
        $this->assertSame("https://www.example.com/moodle/theme/image.php/_s/{$CFG->theme}/core/1/i/edit", $url->out());
    }

    /**
     * Make sure that the externallib exploit with NULL return description allows us to pass back any data.
     */
    public function test_get_flex_icons_ws() {
        global $CFG;
        require_once("$CFG->libdir/externallib.php");
        $response = external_api::call_external_function('core_output_get_flex_icons', array('themename' => 'roots'), true);
        $this->assertFalse($response['error']);
        $this->assertArrayHasKey('templates', $response['data']);
        $this->assertArrayHasKey('datas', $response['data']);
    }

    public function test_get_icon() {
        global $PAGE;
        /** @var core_renderer $renderer */
        $renderer = $PAGE->get_renderer('core');

        $data = array(
            'alt' => 'muppet',
            'classes' => 'my test'
        );

        // Convert from pix to flex
        $actual = flex_icon::get_icon('t/delete', 'core', $data);
        $actualcontext = $actual->export_for_template($renderer);
        $expected = new flex_icon('delete', $data);
        $expectedcontext = $expected->export_for_template($renderer);

        $this->assertSame($expectedcontext['classes'], $actualcontext['classes']);
        $this->assertSame($expectedcontext['customdata']['alt'], $actualcontext['customdata']['alt']);
        $this->assertSame($expectedcontext['customdata']['classes'], $actualcontext['customdata']['classes']);
        $this->assertSame($expectedcontext['customdata']['title'], $actualcontext['customdata']['title']);

        // Straight flex
        $actual = flex_icon::get_icon('delete', 'core', $data);
        $actualcontext = $actual->export_for_template($renderer);
        $expected = new flex_icon('delete', $data);
        $expectedcontext = $expected->export_for_template($renderer);

        $this->assertSame($expected->export_for_template($renderer), $actual->export_for_template($renderer));

        $pix_data = array(
            'alt' => 'muppet',
            'class' => 'my test'
        );

        $actual = flex_icon::get_icon('e/decrease_indent', 'core', $data);
        $expected = new pix_icon('e/decrease_indent', '', 'core', $pix_data);
        $this->assertSame($expected->export_for_template($renderer), $actual->export_for_template($renderer));
    }
}
