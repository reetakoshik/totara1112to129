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

defined('MOODLE_INTERNAL') || die();
use totara_core\path_whitelist; // Totara: path_whitelist

class mustache_string_helper_testcase extends advanced_testcase {

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
        return [$mustache, $loader, $renderer, $page];
    }

    public function test_valid_usage() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        // Identifier only.
        $loader->setTemplate('test', '{{#str}}viewallcourses{{/str}}');
        $this->assertEquals(
            get_string('viewallcourses'),
            $mustache->render('test')
        );

        // Identifier only: variable spacing
        $loader->setTemplate('test', '{{# str }} viewallcourses {{/ str }}');
        $this->assertEquals(
            get_string('viewallcourses'),
            $mustache->render('test')
        );

        // Identifier + component.
        $loader->setTemplate('test', '{{#str}}viewallcourses,core{{/str}}');
        $this->assertEquals(
            get_string('viewallcourses', 'core'),
            $mustache->render('test')
        );

        // Identifier + component alt.
        $loader->setTemplate('test', '{{#str}}viewallcourses,moodle{{/str}}');
        $this->assertEquals(
            get_string('viewallcourses', 'moodle'),
            $mustache->render('test')
        );

        // Identifier + component + $a identifier
        $loader->setTemplate('test', '{{#str}}added,core,delete{{/str}}');
        $this->assertEquals(
            get_string('added', 'core', get_string('delete')),
            $mustache->render('test')
        );

        // Identifier + component + $a identifier + $a component
        $loader->setTemplate('test', '{{#str}}added,core,delete,core{{/str}}');
        $this->assertEquals(
            get_string('added', 'core', get_string('delete')),
            $mustache->render('test')
        );

        // Identifier + component + $a identifier + $a component: Varied spacing 1
        $loader->setTemplate('test', '{{#str}} added , core , delete , core {{/str}}');
        $this->assertEquals(
            get_string('added', 'core', get_string('delete')),
            $mustache->render('test')
        );

        // Identifier + component + $a identifier + $a component: Varied spacing 2
        $loader->setTemplate('test', '{{# str }}  added  ,  core  ,  delete  ,  core  {{/ str }}');
        $this->assertEquals(
            get_string('added', 'core', get_string('delete')),
            $mustache->render('test')
        );

        // Identifier + component + $a identifier + $a component: Varied spacing 3
        $loader->setTemplate('test', '{{#  str  }}  added  ,  core  ,  delete  ,  core  {{/  str  }}');
        $this->assertEquals(
            get_string('added', 'core', get_string('delete')),
            $mustache->render('test')
        );

        // Variable identifier escaped.
        $loader->setTemplate('test', '{{#str}}{{identifier}}{{/str}}');
        $this->assertEquals(
            get_string('viewallcourses'),
            $mustache->render('test', ['identifier' => 'viewallcourses'])
        );

        // Variable identifier not escaped.
        $loader->setTemplate('test', '{{#str}}{{{identifier}}}{{/str}}');
        $this->assertEquals(
            get_string('viewallcourses'),
            $mustache->render('test', ['identifier' => 'viewallcourses'])
        );

        // Variable identifier + variable component
        $loader->setTemplate('test', '{{#str}}{{identifier}},{{component}}{{/str}}');
        $this->assertEquals(
            get_string('viewallcourses'),
            $mustache->render('test', ['identifier' => 'viewallcourses', 'component' => 'moodle'])
        );

        // Variable identifier + variable component escaped
        $loader->setTemplate('test', '{{#str}}{{identifier}}, {{{component}}}{{/str}}');
        $this->assertEquals(
            get_string('viewallcourses'),
            $mustache->render('test', ['identifier' => 'viewallcourses', 'component' => 'moodle'])
        );

        // Variable identifier escaped + variable component escaped
        $loader->setTemplate('test', '{{#str}}{{{identifier}}}, {{{component}}}{{/str}}');
        $this->assertEquals(
            get_string('viewallcourses'),
            $mustache->render('test', ['identifier' => 'viewallcourses', 'component' => 'moodle'])
        );

        // Variable identifier + variable component + variable alt
        $loader->setTemplate('test', '{{#str}}{{identifier}}, {{component}}, viewallcourses{{/str}}');
        $this->assertEquals(
            get_string('added', '', get_string('viewallcourses')),
            $mustache->render('test', ['identifier' => 'added', 'component' => 'moodle'])
        );

        // Variable identifier + variable component + variable alt: Variable spacing 1
        $loader->setTemplate('test', '{{#str}} {{identifier}} , {{component}} , viewallcourses {{/str}}');
        $this->assertEquals(
            get_string('added', '', get_string('viewallcourses')),
            $mustache->render('test', ['identifier' => 'added', 'component' => 'moodle'])
        );

        // Variable identifier + variable component + variable alt: Variable spacing 2
        $loader->setTemplate('test', '{{#  str  }}  {{identifier}}  ,  {{component}}  ,  viewallcourses  {{/  str  }}');
        $this->assertEquals(
            get_string('added', '', get_string('viewallcourses')),
            $mustache->render('test', ['identifier' => 'added', 'component' => 'moodle'])
        );
    }

    public function test_legacy_usage() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        // Identifier + component + variable $a
        $loader->setTemplate('test', '{{#str}}added,core,{{var}}{{/str}}');
        $this->assertEquals(
            get_string('added', 'core', get_string('delete')),
            $mustache->render('test', ['var' => get_string('delete')])
        );
        $this->assertDebuggingCalled('Legacy string helper API in use, this will not be supported in the future.');

        // Variable identifier + variable component + variable alt escaped
        $loader->setTemplate('test', '{{#str}}{{identifier}}, {{component}}, {{{alt}}}{{/str}}');
        $this->assertEquals(
            get_string('added', '', get_string('viewallcourses')),
            $mustache->render('test', ['identifier' => 'added', 'component' => 'moodle', 'alt' => get_string('viewallcourses')])
        );
        $this->assertDebuggingCalled('Legacy string helper API in use, this will not be supported in the future.');

        // Identifier + component + JSON encoded $a containing multiple properties.
        $data = ['thing' => 'pennies', 'count' => 6];
        $loader->setTemplate('test', '{{#str}}displayingfirst,core,'.json_encode($data).'{{/str}}');
        $this->assertEquals(
            get_string('displayingfirst', 'core', $data),
            $mustache->render('test')
        );
        $this->assertDebuggingCalled('Legacy string helper API in use, this will not be supported in the future.');

        // Recursive string helpers: str + str
        $loader->setTemplate('test', '{{#str}}added,core,{{#str}}delete{{/str}}{{/str}}');
        $this->assertEquals(
            get_string('added', 'core', ''),
            $mustache->render('test')
        );
        $this->assertDebuggingCalled([
            'Escaped content contains unexpected mustache processing queues. It will be lost.',
            'Legacy string helper API in use, this will not be supported in the future.'
        ]);

        // Recursive helpers: str + flex + str
        $loader->setTemplate('test', '{{#str}}added,core,{{#flex_icon}}permissions-check,delete{{/flex_icon}}{{/str}}');
        $this->assertEquals(
            get_string('added', 'core', ''),
            $mustache->render('test')
        );
        $this->assertDebuggingCalled([
            'Escaped content contains unexpected mustache processing queues. It will be lost.',
            'Legacy string helper API in use, this will not be supported in the future.'
        ]);

        // Invalid string identifier during variable substitution.
        $loader->setTemplate('test', '{{#str}}{{test}}{{/str}}');
        $this->assertEquals(
            '',
            $mustache->render('test', ['test' => 'fake string id'])
        );
        $this->assertDebuggingCalled([
            'Invalid identifier for string helper must be a string identifier.'
        ]);

        // Invalid string identifier during variable substitution.
        $loader->setTemplate('test', '{{#str}}viewallcourses,{{test}}{{/str}}');
        $this->assertEquals(
            get_string('viewallcourses', 'core'),
            $mustache->render('test', ['test' => 'fake string component'])
        );
        $this->assertDebuggingCalled([
            'Invalid component for string helper must be a string component.'
        ]);

        // Recursive helpers: str + js
        $loader->setTemplate('test', '{{#str}}added,core,{{#js}}alert(window.location);{{/js}}{{/str}}');
        $this->assertEquals(
            get_string('added', 'core', ''),
            $mustache->render('test')
        );
        $this->assertDebuggingCalled([
            'Escaped content contains unexpected mustache processing queues. It will be lost.',
            'Legacy string helper API in use, this will not be supported in the future.'
        ]);
        $this->assertNotContains('alert(window.location);', $page->requires->get_end_code());

        // Odd mix of content.
        $loader->setTemplate('test', '{{#str}} {{identifier}} , {{component}} , {test}, core {{/str}}');
        $this->assertEquals(
            get_string('added', '', null),
            $mustache->render('test', ['identifier' => 'added', 'component' => 'moodle'])
        );
        $this->assertDebuggingCalled('Invalid $a identifier for string helper must be a string identifier.');
    }

    public function test_excess_arguments() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        // One additional argument.
        $loader->setTemplate('test', '{{#str}}added,core,delete,core,mystery{{/str}}');
        $this->assertEquals(
            get_string('added', 'core', get_string('delete')),
            $mustache->render('test', ['var' => get_string('delete')])
        );
        $this->assertDebuggingCalled('Unexpected number of arguments, 1 too many');

        // Two additional arguments.
        $loader->setTemplate('test', '{{#str}}added,core,delete,core,one,two{{/str}}');
        $this->assertEquals(
            get_string('added', 'core', get_string('delete')),
            $mustache->render('test', ['var' => get_string('delete')])
        );
        $this->assertDebuggingCalled('Unexpected number of arguments, 2 too many');
    }

    public function test_non_conforming_a_string_identifier() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        $loader->setTemplate('test', '{{#str}}added,core,test case{{/str}}');
        $this->assertEquals(
            get_string('added', 'core', null),
            $mustache->render('test')
        );
        $this->assertDebuggingCalled('Invalid $a identifier for string helper must be a string identifier.');
    }

    public function test_non_conforming_a_string_component() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        $loader->setTemplate('test', '{{#str}}added,core,delete,test case{{/str}}');
        $this->assertEquals(
            get_string('added', 'core', get_string('delete')),
            $mustache->render('test')
        );
        $this->assertDebuggingCalled('Invalid $a component for string helper must be a string component.');
    }

    public function test_no_exploitable_string_helper_uses() {
        global $CFG;

        $dir_iterator = new RecursiveDirectoryIterator($CFG->dirroot);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

        // OK, so we are about to scan all mustache templates to look for abuses.
        // There should be none, but if there are valid cases that are found to be false positive then we
        // can list them here and know that they have been manually validated as safe.
        // If you are adding to this list you need approval from the security experts.
        $whitelist = new path_whitelist([
            $CFG->dirroot . '/lib/templates/test.mustache', // A mustache test file. Must not contain anything exploitable.
            $CFG->dirroot . '/totara/core/templates/progressbar.mustache', // Deprecated since Totara 12.
            $CFG->dirroot . '/totara/core/templates/errorlog_link.mustache', // Deprecated since Totara 12.
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
                $result = self::has_str_helper_containing_recursive_helpers($content);
                if ($result) {
                    if ($whitelistkey !== false) {
                        // It's OK, its on the whitelist.
                        $whitelist->remove($whitelistkey); // Totara: path_whitelist
                        continue;
                    }
                    $recursivehelpers[] = str_replace($CFG->dirroot, '', $path).' :: '.$result;
                }
                $result = self::has_str_helper_containing_variables($content);
                if ($result) {
                    if ($whitelistkey !== false) {
                        // It's OK, its on the whitelist.
                        $whitelist->remove($whitelistkey); // Totara: path_whitelist
                        continue;
                    }
                    $variablesinhelpers[] = str_replace($CFG->dirroot, '', $path).' :: '.$result;
                }
            }
        }

        if (!empty($recursivehelpers)) {
            $this->fail('Templates containing string helper uses which contain recursive helper calls found'."\n * ".join("\n * ", $recursivehelpers));
        }
        if (!empty($variablesinhelpers)) {
            $this->fail('Templates containing variables in string helpers.'."\n * ".join("\n * ", $variablesinhelpers));
        }
        if (!$whitelist->is_empty()) { // Totara: path_whitelist
            $this->fail('Items on the whitelist were not found to contain vulnerabilities.'."\n".$whitelist->join("\n"));
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

    private static function has_str_helper_containing_variables(string $template) {
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

    private static function has_str_helper_containing_recursive_helpers(string $template) {
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
