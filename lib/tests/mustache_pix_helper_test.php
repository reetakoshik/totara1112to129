<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @copyright 2017 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Brian Barnes <brian.barnes@totaralearning.com>
 * @package   core_output
 */

use core\output\mustache_pix_helper;

defined('MOODLE_INTERNAL') || die();

class mustache_pix_helper_testcase extends basic_testcase {

    /**
     * @var core_renderer
     */
    protected static $renderer;

    /**
     * @var \Mustache_Engine
     */
    protected static $engine;


    public static function setUpBeforeClass() {

        global $CFG;

        require_once("{$CFG->dirroot}/lib/mustache/src/Mustache/Autoloader.php");
        Mustache_Autoloader::register();

        self::$renderer = new \core_renderer(new moodle_page(), '/');
        // Get the engine from the renderer. We do this once cause its mad.
        $class = new ReflectionClass(self::$renderer);
        $method = $class->getMethod('get_mustache');
        $method->setAccessible(true);
        self::$engine = $method->invoke(self::$renderer);
    }

    /**
     * Returns a LambdaHelper populated with the given contextdata.
     *
     * @param array|stdClass $contextdata
     * @return Mustache_LambdaHelper
     */
    protected function get_lambda_helper($contextdata = []) {
        return new \Mustache_LambdaHelper(self::$engine, new \Mustache_Context($contextdata));
    }

    /**
     * It should generate the same output as rendering the renderable without customdata.
     *
     * @covers \core\output\mustache_pix_helper::__construct
     * @covers \core\output\mustache_pix_helper::pix
     */
    public function test_pix_output_without_customdata() {

        $identifier = 'movehere';
        $mustachehelper = new mustache_pix_helper(self::$renderer);

        $expected = self::$renderer->render(new pix_icon($identifier, ''));
        $actual = $mustachehelper->pix($identifier, $this->get_lambda_helper());

        $this->assertEquals($expected, $actual);

    }

    /**
     * It should generate the same output as rendering the renderable without customdata.
     *
     * @covers \core\output\mustache_pix_helper::__construct
     * @covers \core\output\mustache_pix_helper::pix
     */
    public function test_pix_output_with_plain_alt() {

        $identifier = 'movehere';
        $alt = 'sometext';
        $helperstring = $identifier . ' , core,' . $alt;

        $mustachehelper = new mustache_pix_helper(self::$renderer);

        $expected = self::$renderer->render(new pix_icon($identifier, $alt));

        $actual = $mustachehelper->pix($helperstring, $this->get_lambda_helper());

        $this->assertEquals($expected, $actual);

    }

    /**
     * It should generate the same output as rendering the renderable without customdata.
     *
     * @covers \core\output\mustache_pix_helper::__construct
     * @covers \core\output\mustache_pix_helper::pix
     */
    public function test_pix_output_with_xss_alt() {

        $identifier = 'movehere';
        $alt = '<script>alert("blah");</script>';
        $helperstring = $identifier . ' , core,' . $alt;
        $mustachehelper = new mustache_pix_helper(self::$renderer);

        $expected = self::$renderer->render(new pix_icon($identifier, $alt));

        $actual = $mustachehelper->pix($helperstring, $this->get_lambda_helper());

        $this->assertEquals($expected, $actual);

    }

    /**
     * It should generate the same output as rendering the renderable with customdata.
     *
     * @covers \core\output\mustache_pix_helper::pix
     */
    public function test_pix_output_with_customdata() {

        $identifier = 'movehere';
        $alt = 'image';
        $customdata = array(
            'class' => 'ft-state-success ft-size-700',
            'alt' => $alt
        );
        $helperstring = "{$identifier}, core, " . json_encode($customdata);

        $mustachehelper = new mustache_pix_helper(self::$renderer);

        $expected = self::$renderer->render(new pix_icon($identifier, $alt, 'core', $customdata));
        $actual = $mustachehelper->pix($helperstring, $this->get_lambda_helper());

        $this->assertEquals($expected, $actual);

    }

    /**
     * It should generate the same output as rendering the renderable with customdata.
     *
     * @covers \core\output\mustache_pix_helper::pix
     */
    public function test_pix_output_with_variable_identifier() {

        $actualidentifier = 'movehere';
        $variableidentifier = '{{test_icon}}';
        $customdata = array(
            'classes' => 'ft-state-success ft-size-700'
        );
        $helperstring = "{$variableidentifier}, core, " . json_encode($customdata);

        $expected = self::$renderer->render(new pix_icon($actualidentifier, '', 'core', $customdata));

        $lambdahelper = $this->get_lambda_helper(['test_icon' => $actualidentifier]);
        $mustachehelper = new mustache_pix_helper(self::$renderer);
        $actual = $mustachehelper->pix($helperstring, $lambdahelper);

        $this->assertEquals($expected, $actual);
    }

    /**
     * It should generate the same output as rendering the renderable with customdata.
     *
     * @covers \core\output\mustache_pix_helper::pix
     */
    public function test_pix_output_with_variable_alt() {

        $actualidentifier = 'movehere';
        $helperstring = '{{test_icon}}, core, { "alt": "{{alt}}" }';

        $expected = self::$renderer->render(new pix_icon($actualidentifier, '', 'core', ['alt' => get_string('settings')]));

        $lambdahelper = $this->get_lambda_helper(['test_icon' => $actualidentifier, 'alt' => get_string('settings')]);
        $mustachehelper = new mustache_pix_helper(self::$renderer);
        $actual = $mustachehelper->pix($helperstring, $lambdahelper);

        $this->assertEquals($expected, $actual);

    }

    /**
     * It should generate the same output as rendering the renderable with customdata.
     *
     * @covers \core\output\mustache_pix_helper::pix
     */
    public function test_pix_output_with_complex_structure() {

        $actualidentifier = 'movehere';
        $helperstring = '{{test_icon}}, core, { "alt": "{{alt}}", "classes": "{{classes}}" }';

        $expected = self::$renderer->render(new pix_icon($actualidentifier, '', 'core', ['alt' => get_string('settings'), 'classes' => 'test testing']));

        $lambdahelper = $this->get_lambda_helper(['test_icon' => $actualidentifier, 'alt' => get_string('settings'), 'classes' => 'test testing']);
        $mustachehelper = new mustache_pix_helper(self::$renderer);
        $actual = $mustachehelper->pix($helperstring, $lambdahelper);

        $this->assertEquals($expected, $actual);

    }

    /**
     * It should generate the same output as rendering the renderable with customdata.
     *
     * @covers \core\output\mustache_pix_helper::pix
     */
    public function test_pix_output_with_variable_structure() {

        $actualidentifier = 'movehere';
        // include some XSS attacks as unsanitised title is needed otherwise JSON isn't correctly sent
        $customdata = array(
            'alt' => '<script>alert("blah");</script>',
            'class' => 'some"testing'
        );
        $helperstring = '{{test_icon}}, core, {{{ title }}}';

        $expected = self::$renderer->render(new pix_icon($actualidentifier, '', 'core', $customdata));

        $lambdahelper = $this->get_lambda_helper(['test_icon' => $actualidentifier, 'title' => json_encode($customdata)]);
        $mustachehelper = new mustache_pix_helper(self::$renderer);
        $actual = $mustachehelper->pix($helperstring, $lambdahelper);

        $this->assertEquals($expected, $actual);

    }

    /**
     * Test core, components, and standard plugins to ensure that we are aware of all potentially
     * abusable helper uses.
     */
    public function test_no_exploitable_pix_helper_uses() {
        global $CFG;

        $directories = [
            $CFG->dirroot . '/lib/templates'
        ];

        $subsystems = \core_component::get_core_subsystems();
        foreach ($subsystems as $directory) {
            if (empty($directory)) {
                continue;
            }
            $directory .= '/templates';
            if (file_exists($directory) && is_dir($directory)) {
                $directories[] = $directory;
            }
        }

        $manager = \core_plugin_manager::instance();
        foreach ($manager->get_plugins() as $plugintype_class => $plugins) {
            foreach ($plugins as $plugin_class => $plugin) {
                /** @var \core\plugininfo\base $plugin */
                if (!$plugin->is_standard()) {
                    continue;
                }
                $directory = $CFG->dirroot . $plugin->get_dir() . '/templates';
                if (file_exists($directory) && is_dir($directory)) {
                    $directories[] = $directory;
                }
            }
        }

        // OK, so we are about to scan all mustache templates to look for abuses.
        // There should be none, but if there are valid cases that are found to be false positive then we
        // can list them here and know that they have been manually validated as safe.
        // If you are adding to this list you need approval from the security experts.
        $whitelist = [
            $CFG->dirroot . '/lib/templates/test.mustache', // Test cases for Mustache.
            $CFG->dirroot . '/lib/templates/loading.mustache', // No user data variables used.
            $CFG->dirroot . '/message/templates/message_area_contact.mustache', // No user data variables used.
            $CFG->dirroot . '/message/templates/message_area_messages_area.mustache', // No user data variables used.
            $CFG->dirroot . '/mod/assign/templates/popout_button.mustache', // No user data variables used.
            $CFG->dirroot . '/mod/assign/templates/grading_actions.mustache', // No user data variables used.
            $CFG->dirroot . '/mod/assign/templates/loading.mustache', // No user data variables used.
            $CFG->dirroot . '/mod/assign/templates/grading_navigation.mustache', // No user data variables used.
            $CFG->dirroot . '/mod/assign/templates/grading_app.mustache', // No user data variables used.
            $CFG->dirroot . '/mod/lti/templates/tool_card.mustache', // No user data variables used.
            $CFG->dirroot . '/mod/lti/templates/loader.mustache', // No user data variables used.
            $CFG->dirroot . '/mod/lti/templates/tool_proxy_card.mustache', // No user data variables used.
            $CFG->dirroot . '/message/output/popup/templates/message_popover.mustache', // No user data variables used.
            $CFG->dirroot . '/message/output/popup/templates/notification_popover.mustache', // No user data variables used.
            $CFG->dirroot . '/totara/form/templates/element_filemanager.mustache', // No user data variables used.
            $CFG->dirroot . '/totara/form/templates/element_checkbox.mustache', // No user data variables used.
            $CFG->dirroot . '/totara/form/templates/element_select.mustache', // No user data variables used.
            $CFG->dirroot . '/totara/cohort/templates/editing_ruleset.mustache', // No user data variables used.
        ];
        $recursivehelpers = [];
        $variablesinhelpers = [];
        foreach ($directories as $directory) {
            foreach (new DirectoryIterator($directory) as $file) {
                /** @var SplFileInfo $file */
                if ($file->isFile() && $file->getExtension() === 'mustache') {
                    $path = $file->getPathname();
                    $whitelistkey = array_search($path, $whitelist);
                    if (!is_readable($path)) {
                        $this->fail('Mustache template is not readable by unit test suite "'.$path.'"');
                    }
                    $content = file_get_contents($path);
                    $content = str_replace("\n", '', $content);
                    $result = self::has_pix_helper_containing_recursive_helpers($content);
                    if ($result) {
                        if ($whitelistkey !== false) {
                            // It's OK, its on the whitelist.
                            unset($whitelist[$whitelistkey]);
                            continue;
                        }
                        $recursivehelpers[] = str_replace($CFG->dirroot, '', $path).' :: '.$result;
                    }
                    $result = self::has_pix_helper_containing_variables($content);
                    if ($result) {
                        if ($whitelistkey !== false) {
                            // It's OK, its on the whitelist.
                            unset($whitelist[$whitelistkey]);
                            continue;
                        }
                        $variablesinhelpers[] = str_replace($CFG->dirroot, '', $path).' :: '.$result;
                    }
                }
            }
        }

        if (!empty($recursivehelpers)) {
            $this->fail('Templates containing pix helper uses which contain recursive helper calls found'."\n * ".join("\n * ", $recursivehelpers));
        }
        if (!empty($variablesinhelpers)) {
            $this->fail('Templates containing variables in pix helpers.'."\n * ".join("\n * ", $variablesinhelpers));
        }
        if (!empty($whitelist)) {
            $this->fail('Items on the whitelist were not found to contain vulnerabilities.'."\n".join("\n", $whitelist));
        }
    }

    public function test_has_pix_helper_containing_variables() {
        // None.
        self::assertFalse(self::has_pix_helper_containing_variables(''));
        self::assertFalse(self::has_pix_helper_containing_variables('test'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{test}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{{test}}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#pix}}test{{/pix}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#str}}test{{/str}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#pix}}  test  {{/pix}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#pix}}{test}{{/pix}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#str}}{test}{{/str}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#str}}{{test}}{{/str}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('#pix{{test}}pix'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{pix}}{{test}}{{pix}}'));

        // One.
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}{{test}}{{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{# pix }} {{ test }} {{/ pix }}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#  pix  }} {{  test  }}  {{/  pix  }}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}{{{test}}}{{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}{{{{test}}}}{{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}  {{test}}  {{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}  {{{test}}}  {{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}  {{{{test}}}}  {{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}{{test}}{{/pix}}{{#str}}test{{/str}}'));

        // Multiple.
        self::assertSame(2, self::has_pix_helper_containing_variables('{{#pix}}{{test}}{{/pix}}{{#pix}}{{test}}{{/pix}}'));
        self::assertSame(3, self::has_pix_helper_containing_variables('{{#pix}} {{test}}, {{test}} {{/pix}} {{#pix}} {{test}} {{/pix}}'));
        self::assertSame(3, self::has_pix_helper_containing_variables('{{# pix }} {{ test }}, {{ test }} {{/ pix }} {{# pix }} {{ test }} {{/ pix }}'));
    }

    private static function has_pix_helper_containing_variables($template) {
        preg_match_all('@(\{{2}[#/][^\}]+\}{2}|\{{2,3}[^\}]+\}{2,3})@', $template, $matches);
        $helper = 'pix';
        $helperlevel = 0;
        $count = 0;
        $regex_open = '@\{{2}# *'.preg_quote($helper, '@').' *\}{2}@';
        $regex_close = '@\{{2}/ *'.preg_quote($helper, '@').' *\}{2}@';
        foreach ($matches[0] as $match) {
            $opening = preg_match($regex_open, $match);
            $closing = preg_match($regex_close, $match);
            if ($opening) {
                $helperlevel ++;
            } else if ($closing) {
                $helperlevel --;
            }
            if ($helperlevel > 0 && !$opening && !$closing) {
                // We're withing a helper.
                $count++;
            }
        }
        return ($count === 0) ? false : $count;
    }

    public function test_has_pix_helper_containing_recursive_helpers() {
        // None.
        self::assertFalse(self::has_pix_helper_containing_recursive_helpers(''));
        self::assertFalse(self::has_pix_helper_containing_recursive_helpers('test'));
        self::assertFalse(self::has_pix_helper_containing_recursive_helpers('{{test}}'));
        self::assertFalse(self::has_pix_helper_containing_recursive_helpers('{{{test}}}'));
        self::assertFalse(self::has_pix_helper_containing_recursive_helpers('{{#pix}}{{test}}{{/pix}}'));
        self::assertFalse(self::has_pix_helper_containing_recursive_helpers('{{# pix }} {{ test }} {{/ pix }}'));
        self::assertFalse(self::has_pix_helper_containing_recursive_helpers('{{#pix}}{{test}}{{/pix}}{{#pix}}{{test}}{{/pix}}'));

        // One.
        self::assertSame(1, self::has_pix_helper_containing_recursive_helpers('{{#pix}}{{#pix}}{{test}}{{/pix}}{{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_recursive_helpers('{{#pix}}{{#str}}test{{/str}}{{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_recursive_helpers('{{# pix }}{{# str }} test {{/ str }} {{/ pix }}'));
        self::assertSame(2, self::has_pix_helper_containing_recursive_helpers('{{#pix}}{{#str}}{{#pix}}test{{/pix}}{{/str}}{{/pix}}'));

        // Multiple.
        self::assertSame(2, self::has_pix_helper_containing_recursive_helpers('{{#pix}}{{#pix}}{{#pix}}{{test}}{{/pix}}{{/pix}}{{/pix}}'));
    }

    private static function has_pix_helper_containing_recursive_helpers($template) {
        preg_match_all('@\{{2}[#/][^\}]+\}{2}@', $template, $matches);
        $helper = 'pix';
        $level = 0;
        $count = 0;
        $regex_open = '@\{{2}# *'.preg_quote($helper, '@').' *\}{2}@';
        $regex_close = '@\{{2}/ *'.preg_quote($helper, '@').' *\}{2}@';
        foreach ($matches[0] as $match) {
            $opening_pix = preg_match($regex_open, $match);
            $closing_pix = preg_match($regex_close, $match);
            $opening = $opening_pix || (strpos($match, '{{#') !== false);

            if ($opening_pix) {
                if ($level > 0) {
                    $count++;
                }
                $level++;
            } else if ($closing_pix) {
                $level--;
            } else if ($opening) {
                if ($level > 0) {
                    $count++;
                }
            }
        }
        return ($count === 0) ? false : $count;
    }
}
