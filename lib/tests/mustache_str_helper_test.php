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

use core\output\mustache_string_helper;

defined('MOODLE_INTERNAL') || die();

class mustache_string_helper_testcase extends basic_testcase {

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
     * @covers \core\output\mustache_string_helper::__construct
     * @covers \core\output\mustache_string_helper::string
     */
    public function test_string_helper() {
        $mustachehelper = new mustache_string_helper(self::$renderer);
        $string = 'viewallcourses'; // Some random string

        $expected = get_string($string);
        $actual = $mustachehelper->str($string, $this->get_lambda_helper());

        $this->assertEquals($expected, $actual);
    }

    /**
     * It should generate the same output as rendering the renderable without customdata.
     *
     * @covers \core\output\mustache_string_helper::__construct
     * @covers \core\output\mustache_string_helper::string
     */
    public function test_string_variable_helper() {
        $mustachehelper = new mustache_string_helper(self::$renderer);

        $actualidentifier = 'viewallcourses';
        $variableidentifier = '{{test_string}}';

        $expected = get_string($actualidentifier);

        $lambdahelper = $this->get_lambda_helper(['test_string' => $actualidentifier]);
        $mustachehelper = new mustache_string_helper(self::$renderer);
        $actual = $mustachehelper->str($variableidentifier, $lambdahelper);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test core, components, and standard plugins to ensure that we are aware of all potentially
     * abusable helper uses.
     */
    public function test_no_exploitable_string_helper_uses() {
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
            $CFG->dirroot . '/totara/core/templates/errorlog_link.mustache', // Deprecated in Totara 11 and will be removed in future versions.
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
                    $result = self::has_str_helper_containing_recursive_helpers($content);
                    if ($result) {
                        if ($whitelistkey !== false) {
                            // It's OK, its on the whitelist.
                            unset($whitelist[$whitelistkey]);
                            continue;
                        }
                        $recursivehelpers[] = str_replace($CFG->dirroot, '', $path).' :: '.$result;
                    }
                    $result = self::has_str_helper_containing_variables($content);
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
            $this->fail('Templates containing string helper uses which contain recursive helper calls found'."\n * ".join("\n * ", $recursivehelpers));
        }
        if (!empty($variablesinhelpers)) {
            $this->fail('Templates containing variables in string helpers.'."\n * ".join("\n * ", $variablesinhelpers));
        }
        if (!empty($whitelist)) {
            $this->fail('Items on the whitelist were not found to contain vulnerabilities.'."\n".join("\n", $whitelist));
        }
    }

    public function test_has_str_helper_containing_variables() {
        // None.
        self::assertFalse(self::has_str_helper_containing_variables(''));
        self::assertFalse(self::has_str_helper_containing_variables('test'));
        self::assertFalse(self::has_str_helper_containing_variables('{{test}}'));
        self::assertFalse(self::has_str_helper_containing_variables('{{{test}}}'));
        self::assertFalse(self::has_str_helper_containing_variables('{{#str}}test{{/str}}'));
        self::assertFalse(self::has_str_helper_containing_variables('{{#pix}}test{{/pix}}'));
        self::assertFalse(self::has_str_helper_containing_variables('{{#str}}  test  {{/str}}'));
        self::assertFalse(self::has_str_helper_containing_variables('{{#str}}{test}{{/str}}'));
        self::assertFalse(self::has_str_helper_containing_variables('{{#pix}}{test}{{/pix}}'));
        self::assertFalse(self::has_str_helper_containing_variables('{{#pix}}{{test}}{{/pix}}'));
        self::assertFalse(self::has_str_helper_containing_variables('#str{{test}}str'));
        self::assertFalse(self::has_str_helper_containing_variables('{{str}}{{test}}{{str}}'));

        // One.
        self::assertSame(1, self::has_str_helper_containing_variables('{{#str}}{{test}}{{/str}}'));
        self::assertSame(1, self::has_str_helper_containing_variables('{{# str }} {{ test }} {{/ str }}'));
        self::assertSame(1, self::has_str_helper_containing_variables('{{#  str  }} {{  test  }}  {{/  str  }}'));
        self::assertSame(1, self::has_str_helper_containing_variables('{{#str}}{{{test}}}{{/str}}'));
        self::assertSame(1, self::has_str_helper_containing_variables('{{#str}}{{{{test}}}}{{/str}}'));
        self::assertSame(1, self::has_str_helper_containing_variables('{{#str}}  {{test}}  {{/str}}'));
        self::assertSame(1, self::has_str_helper_containing_variables('{{#str}}  {{{test}}}  {{/str}}'));
        self::assertSame(1, self::has_str_helper_containing_variables('{{#str}}  {{{{test}}}}  {{/str}}'));
        self::assertSame(1, self::has_str_helper_containing_variables('{{#str}}{{test}}{{/str}}{{#pix}}test{{/pix}}'));

        // Multiple.
        self::assertSame(2, self::has_str_helper_containing_variables('{{#str}}{{test}}{{/str}}{{#str}}{{test}}{{/str}}'));
        self::assertSame(3, self::has_str_helper_containing_variables('{{#str}} {{test}}, {{test}} {{/str}} {{#str}} {{test}} {{/str}}'));
        self::assertSame(3, self::has_str_helper_containing_variables('{{# str }} {{ test }}, {{ test }} {{/ str }} {{# str }} {{ test }} {{/ str }}'));
    }

    private static function has_str_helper_containing_variables($template) {
        preg_match_all('@(\{{2}[#/][^\}]+\}{2}|\{{2,3}[^\}]+\}{2,3})@', $template, $matches);
        $helper = 'str';
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

    public function test_has_str_helper_containing_recursive_helpers() {
        // None.
        self::assertFalse(self::has_str_helper_containing_recursive_helpers(''));
        self::assertFalse(self::has_str_helper_containing_recursive_helpers('test'));
        self::assertFalse(self::has_str_helper_containing_recursive_helpers('{{test}}'));
        self::assertFalse(self::has_str_helper_containing_recursive_helpers('{{{test}}}'));
        self::assertFalse(self::has_str_helper_containing_recursive_helpers('{{#str}}{{test}}{{/str}}'));
        self::assertFalse(self::has_str_helper_containing_recursive_helpers('{{# str }} {{ test }} {{/ str }}'));
        self::assertFalse(self::has_str_helper_containing_recursive_helpers('{{#str}}{{test}}{{/str}}{{#str}}{{test}}{{/str}}'));

        // One.
        self::assertSame(1, self::has_str_helper_containing_recursive_helpers('{{#str}}{{#str}}{{test}}{{/str}}{{/str}}'));
        self::assertSame(1, self::has_str_helper_containing_recursive_helpers('{{#str}}{{#pix}}test{{/pix}}{{/str}}'));
        self::assertSame(1, self::has_str_helper_containing_recursive_helpers('{{# str }}{{# pix }} test {{/ pix }} {{/ str }}'));
        self::assertSame(2, self::has_str_helper_containing_recursive_helpers('{{#str}}{{#pix}}{{#str}}test{{/str}}{{/pix}}{{/str}}'));

        // Multiple.
        self::assertSame(2, self::has_str_helper_containing_recursive_helpers('{{#str}}{{#str}}{{#str}}{{test}}{{/str}}{{/str}}{{/str}}'));
    }

    private static function has_str_helper_containing_recursive_helpers($template) {
        preg_match_all('@\{{2}[#/][^\}]+\}{2}@', $template, $matches);
        $helper = 'str';
        $level = 0;
        $count = 0;
        $regex_open = '@\{{2}# *'.preg_quote($helper, '@').' *\}{2}@';
        $regex_close = '@\{{2}/ *'.preg_quote($helper, '@').' *\}{2}@';
        foreach ($matches[0] as $match) {
            $opening_str = preg_match($regex_open, $match);
            $closing_str = preg_match($regex_close, $match);
            $opening = $opening_str || (strpos($match, '{{#') !== false);

            if ($opening_str) {
                if ($level > 0) {
                    $count++;
                }
                $level++;
            } else if ($closing_str) {
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
