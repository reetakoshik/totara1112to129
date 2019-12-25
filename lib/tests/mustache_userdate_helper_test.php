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
 * @copyright 2017 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Brian Barnes <brian.barnes@totaralearning.com>
 * @package   core_output
 */

use core\output\mustache_userdate_helper;
use totara_core\path_whitelist; // Totara: path_whitelist

defined('MOODLE_INTERNAL') || die();

class mustache_userdate_helper_testcase extends advanced_testcase {

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
     * Creates a new mustache instance, cloned from the real one, ready for testing.
     *
     * @return array
     */
    private static function get_mustache() {

        $page = new \moodle_page();
        $renderer = $page->get_renderer('core');
        $reflection = new ReflectionMethod($renderer, 'get_mustache');
        $reflection->setAccessible(true);
        /** @var Mustache_Engine $mustache */
        $mustache = $reflection->invoke($renderer);
        // Clone it, we want the real mustache loader to still have access to the templates.
        $mustache = clone($mustache);
        // Set a new loader so that we can add templates for testing.
        $loader = new Mustache_Loader_ArrayLoader([]);
        $mustache->setLoader($loader);
        return [$mustache, $loader, $renderer, $page];
    }

    /**
     * Test the get_mustache method returns what we require.
     */
    public function test_get_mustache() {
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();
        self::assertInstanceOf(Mustache_Engine::class, $mustache);
        self::assertInstanceOf(Mustache_Loader_ArrayLoader::class, $loader);
        self::assertInstanceOf(core_renderer::class, $renderer);
        self::assertInstanceOf(moodle_page::class, $page);
    }

    public function test_valid_usage() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        $loader->setTemplate('test', '{{#userdate}}1293876000,%d %B %Y{{/userdate}}');
        $this->assertEquals('1 January 2011', $mustache->render('test'));
        $this->assertDebuggingCalled('userdate mustache helper in use - this has a potential XSS issue');

        $loader->setTemplate('test', '{{# userdate }} 1293876000, %d %B %Y {{/ userdate }}');
        $this->assertEquals('1 January 2011', $mustache->render('test'));
        $this->assertDebuggingCalled('userdate mustache helper in use - this has a potential XSS issue');

        $loader->setTemplate('test', '{{#userdate}}1293876000,{{#str}} strftimedate, langconfig {{/str}}{{/userdate}}');
        $this->assertEquals('1 January 2011', $mustache->render('test'));
        $this->assertDebuggingCalled('userdate mustache helper in use - this has a potential XSS issue');

        $debugging = array(
            'userdate mustache helper in use - this has a potential XSS issue',
            'Mustache processing quotes converted to square brackets for safety.'
        );

        $loader->setTemplate('test', '{{#userdate}} {{ date }}, {{#str}} strftimedate, langconfig {{/str}} {{/userdate}}');
        $this->assertEquals('1 January 2011', $mustache->render('test', ['date' => 1293876000]));
        $this->assertDebuggingCalledCount(2, $debugging);

        $loader->setTemplate('test', '{{#userdate}} {{date}}, {{ format }} {{/userdate}}');
        $this->assertEquals('1 January 2011', $mustache->render('test', ['date' => 1293876000, 'format' => get_string('strftimedate', 'langconfig')]));
        $this->assertDebuggingCalledCount(2, $debugging);

        $loader->setTemplate('test', '{{#userdate}} {{{date}}}, {{{format}}} {{/userdate}}');
        $this->assertEquals('1 January 2011', $mustache->render('test', ['date' => 1293876000, 'format' => get_string('strftimedate', 'langconfig')]));
        $this->assertDebuggingCalledCount(2, $debugging);
    }


    public function test_no_exploitable_userdate_helper_uses() {
        global $CFG;

        $dir_iterator = new RecursiveDirectoryIterator($CFG->dirroot);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

        // OK, so we are about to scan all mustache templates to look for abuses.
        // There should be none, but if there are valid cases that are found to be false positive then we
        // can list them here and know that they have been manually validated as safe.
        // If you are adding to this list you need approval from the security experts.
        $whitelist = new path_whitelist([
            $CFG->dirroot . '/lib/templates/test.mustache', // A mustache test file. Must not contain anything exploitable.
        ]); // Totara: path_whitelist

        $recursivehelpers = [];
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile() && $file->getExtension() === 'mustache') {
                $path = $file->getPathname();
                $whitelistkey = $whitelist->search($path); // Totara: path_whitelist
                if (!is_readable($path)) {
                    $this->fail('Mustache template is not readable by unit test suite "'.$path.'"');
                }
                $content = file_get_contents($path);
                $content = str_replace("\n", '', $content);
                $result = self::has_userdate_helper($content);
                if ($result) {
                    if ($whitelistkey !== false) {
                        // It's OK, its on the whitelist.
                        $whitelist->remove($whitelistkey); // Totara: path_whitelist
                        continue;
                    }
                    $recursivehelpers[] = str_replace($CFG->dirroot, '', $path).' :: '.$result;
                }
            }
        }

        if (!empty($recursivehelpers)) {
            $this->fail('Templates containing userdate helper uses which contain recursive helper calls found'."\n * ".join("\n * ", $recursivehelpers));
        }
        if (!empty($variablesinhelpers)) {
            $this->fail('Templates containing variables in string helpers.'."\n * ".join("\n * ", $variablesinhelpers));
        }
        if (!$whitelist->is_empty()) { // Totara: path_whitelist
            $this->fail('Items on the whitelist were not found to contain vulnerabilities.'."\n".$whitelist->join("\n"));
        }
    }

    private static function has_userdate_helper(string $template) {
        $helper = 'userdate';
        $regex_open = '@\{{2}# *'.preg_quote($helper, '@').' *\}{2}@';
        $regex_close = '@\{{2}/ *'.preg_quote($helper, '@').' *\}{2}@';
        return preg_match($regex_open, $template) && preg_match($regex_close, $template);
    }


    public function test_has_userdate_helper_containing_variables() {
        // None.
        self::assertFalse(self::has_userdate_helper_containing_variables(''));
        self::assertFalse(self::has_userdate_helper_containing_variables('test'));
        self::assertFalse(self::has_userdate_helper_containing_variables('{{test}}'));
        self::assertFalse(self::has_userdate_helper_containing_variables('{{{test}}}'));
        self::assertFalse(self::has_userdate_helper_containing_variables('{{#userdate}}test{{/userdate}}'));
        self::assertFalse(self::has_userdate_helper_containing_variables('{{#pix}}test{{/pix}}'));
        self::assertFalse(self::has_userdate_helper_containing_variables('{{#userdate}}  test  {{/userdate}}'));
        self::assertFalse(self::has_userdate_helper_containing_variables('{{#userdate}}{test}{{/userdate}}'));
        self::assertFalse(self::has_userdate_helper_containing_variables('{{#pix}}{test}{{/pix}}'));
        self::assertFalse(self::has_userdate_helper_containing_variables('{{#pix}}{{test}}{{/pix}}'));
        self::assertFalse(self::has_userdate_helper_containing_variables('#userdate{{test}}userdate'));
        self::assertFalse(self::has_userdate_helper_containing_variables('{{userdate}}{{test}}{{userdate}}'));

        // One.
        self::assertSame(1, self::has_userdate_helper_containing_variables('{{#userdate}}{{test}}{{/userdate}}'));
        self::assertSame(1, self::has_userdate_helper_containing_variables('{{# userdate }} {{ test }} {{/ userdate }}'));
        self::assertSame(1, self::has_userdate_helper_containing_variables('{{#  userdate  }} {{  test  }}  {{/  userdate  }}'));
        self::assertSame(1, self::has_userdate_helper_containing_variables('{{#userdate}}{{{test}}}{{/userdate}}'));
        self::assertSame(1, self::has_userdate_helper_containing_variables('{{#userdate}}{{{{test}}}}{{/userdate}}'));
        self::assertSame(1, self::has_userdate_helper_containing_variables('{{#userdate}}  {{test}}  {{/userdate}}'));
        self::assertSame(1, self::has_userdate_helper_containing_variables('{{#userdate}}  {{{test}}}  {{/userdate}}'));
        self::assertSame(1, self::has_userdate_helper_containing_variables('{{#userdate}}  {{{{test}}}}  {{/userdate}}'));
        self::assertSame(1, self::has_userdate_helper_containing_variables('{{#userdate}}{{test}}{{/userdate}}{{#pix}}test{{/pix}}'));

        // Multiple.
        self::assertSame(2, self::has_userdate_helper_containing_variables('{{#userdate}}{{test}}{{/userdate}}{{#userdate}}{{test}}{{/userdate}}'));
        self::assertSame(3, self::has_userdate_helper_containing_variables('{{#userdate}} {{test}}, {{test}} {{/userdate}} {{#userdate}} {{test}} {{/userdate}}'));
        self::assertSame(3, self::has_userdate_helper_containing_variables('{{# userdate }} {{ test }}, {{ test }} {{/ userdate }} {{# userdate }} {{ test }} {{/ userdate }}'));
    }

    private static function has_userdate_helper_containing_variables(string $template) {
        preg_match_all('@(\{{2}[#/][^\}]+\}{2}|\{{2,3}[^\}]+\}{2,3})@', $template, $matches);
        $helper = 'userdate';
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

    public function test_has_userdate_helper_containing_recursive_helpers() {
        // None.
        self::assertFalse(self::has_userdate_helper_containing_recursive_helpers(''));
        self::assertFalse(self::has_userdate_helper_containing_recursive_helpers('test'));
        self::assertFalse(self::has_userdate_helper_containing_recursive_helpers('{{test}}'));
        self::assertFalse(self::has_userdate_helper_containing_recursive_helpers('{{{test}}}'));
        self::assertFalse(self::has_userdate_helper_containing_recursive_helpers('{{#userdate}}{{test}}{{/userdate}}'));
        self::assertFalse(self::has_userdate_helper_containing_recursive_helpers('{{# userdate }} {{ test }} {{/ userdate }}'));
        self::assertFalse(self::has_userdate_helper_containing_recursive_helpers('{{#userdate}}{{test}}{{/userdate}}{{#userdate}}{{test}}{{/userdate}}'));

        // One.
        self::assertSame(1, self::has_userdate_helper_containing_recursive_helpers('{{#userdate}}{{#userdate}}{{test}}{{/userdate}}{{/userdate}}'));
        self::assertSame(1, self::has_userdate_helper_containing_recursive_helpers('{{#userdate}}{{#pix}}test{{/pix}}{{/userdate}}'));
        self::assertSame(1, self::has_userdate_helper_containing_recursive_helpers('{{# userdate }}{{# pix }} test {{/ pix }} {{/ userdate }}'));
        self::assertSame(2, self::has_userdate_helper_containing_recursive_helpers('{{#userdate}}{{#pix}}{{#userdate}}test{{/userdate}}{{/pix}}{{/userdate}}'));

        // Multiple.
        self::assertSame(2, self::has_userdate_helper_containing_recursive_helpers('{{#userdate}}{{#userdate}}{{#userdate}}{{test}}{{/userdate}}{{/userdate}}{{/userdate}}'));
    }

    private static function has_userdate_helper_containing_recursive_helpers(string $template) {
        preg_match_all('@\{{2}[#/][^\}]+\}{2}@', $template, $matches);
        $helper = 'userdate';
        $level = 0;
        $count = 0;
        $regex_open = '@\{{2}# *'.preg_quote($helper, '@').' *\}{2}@';
        $regex_close = '@\{{2}/ *'.preg_quote($helper, '@').' *\}{2}@';
        foreach ($matches[0] as $match) {
            $opening_userdate = preg_match($regex_open, $match);
            $closing_userdate = preg_match($regex_close, $match);
            $opening = $opening_userdate || (strpos($match, '{{#') !== false);

            if ($opening_userdate) {
                if ($level > 0) {
                    $count++;
                }
                $level++;
            } else if ($closing_userdate) {
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