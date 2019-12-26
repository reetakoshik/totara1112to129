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

class mustache_pix_helper_testcase extends advanced_testcase {

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
        return [$mustache, $mustache->getLoader(), $renderer, $page];
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

        // Identifier only.
        $loader->setTemplate('test', '{{#pix}}movehere{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', '')),
            $mustache->render('test')
        );

        // Identifier + Component.
        $loader->setTemplate('test', '{{#pix}}movehere,core{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', '')),
            $mustache->render('test')
        );

        // Identifier + Component + Alt
        $loader->setTemplate('test', '{{#pix}}movehere,core,delete{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', get_string('delete'))),
            $mustache->render('test')
        );

        // Identifier + Component + Alt + Component
        $loader->setTemplate('test', '{{#pix}}movehere,core,delete,core{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', get_string('delete', 'core'))),
            $mustache->render('test')
        );

        // Identifier + Component + Alt + Component: Variable spacing 1
        $loader->setTemplate('test', '{{#pix}} movehere , core , delete , core {{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', get_string('delete', 'core'))),
            $mustache->render('test')
        );

        // Identifier + Component + Alt + Component: Variable spacing 2
        $loader->setTemplate('test', '{{#pix}}  movehere  ,  core  ,  delete  ,  core  {{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', get_string('delete', 'core'))),
            $mustache->render('test')
        );

        // Variable Identifier.
        $loader->setTemplate('test', '{{#pix}}{{icon}}{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', '')),
            $mustache->render('test', ['icon' => 'movehere'])
        );

        // Variable Identifier + Component.
        $loader->setTemplate('test', '{{#pix}}{{icon}},{{component}}{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', '')),
            $mustache->render('test', ['icon' => 'movehere', 'component' => 'core'])
        );

        // Variable Identifier + Component with alt.
        $loader->setTemplate('test', '{{#pix}}{{icon}},{{component}}, delete, core{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', get_string('delete', 'core'))),
            $mustache->render('test', ['icon' => 'movehere', 'component' => 'core'])
        );

        // Flex icon conversion.
        $loader->setTemplate('test', '{{#pix}}edit,flexicon{{/pix}}');
        $this->assertEquals(
            $renderer->render(new \core\output\flex_icon('edit')),
            $mustache->render('test')
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

        // Alt which contains just some plain text.
        $loader->setTemplate('test', '{{#pix}}movehere,core,This is a test{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', 'This is a test')),
            $mustache->render('test', ['icon' => 'movehere', 'component' => 'core', 'alt' => 'This is a test'])
        );
        $this->assertDebuggingCalled('Legacy pix icon helper API in use, please use the pix icon template instead.');

        // Alt which contains a string helper (this used to work).
        $loader->setTemplate('test', '{{#pix}}movehere,core,{{#str}}delete{{/str}}{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', '')),
            $mustache->render('test', ['icon' => 'movehere', 'component' => 'core', 'alt' => ''])
        );
        $this->assertDebuggingCalled([
            'Escaped content contains unexpected mustache processing queues. It will be lost.',
            'Legacy pix icon helper API in use, please use the pix icon template instead.',
        ]);

        // Variable Identifier + Component + variable alt resolving to static
        $loader->setTemplate('test', '{{#pix}}{{icon}},{{component}}, {{alt}}{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', 'This is a test')),
            $mustache->render('test', ['icon' => 'movehere', 'component' => 'core', 'alt' => 'This is a test'])
        );
        $this->assertDebuggingCalled('Legacy pix icon helper API in use, please use the pix icon template instead.');

        // Variable Identifier + Component + variable alt (doesn't get resolved against get_string)
        $loader->setTemplate('test', '{{#pix}}{{icon}},{{component}}, {{alt}}{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', 'viewallcourses')),
            $mustache->render('test', ['icon' => 'movehere', 'component' => 'core', 'alt' => 'viewallcourses'])
        );
        $this->assertDebuggingCalled('Legacy pix icon helper API in use, please use the pix icon template instead.');

        // JSON encoded data.
        $data = ['alt' => get_string('delete'), 'class' => 'ft-state-success ft-size-700'];
        $loader->setTemplate('test', '{{#pix}}{{icon}},core,'.json_encode($data).'{{/pix}}');
        $icon = new pix_icon('movehere', get_string('delete'), '', array(
            'alt' => get_string('delete'),
            'class' => 'ft-state-success ft-size-700',
            'title' => get_string('delete'),
        ));
        $this->assertEquals(
            $renderer->render($icon),
            $mustache->render('test', ['icon' => 'movehere'])
        );
        $this->assertDebuggingCalled('Legacy pix icon helper API in use, please use the pix icon template instead.');

        // Variable alt in JSON encoded data.
        $data = ['alt' => '{{delete}}'];
        $loader->setTemplate('test', '{{#pix}}{{icon}},core,'.json_encode($data).'{{/pix}}');
        $icon = new pix_icon('movehere', get_string('delete'), '', array(
            'alt' => get_string('delete'),
            'title' => get_string('delete'),
        ));
        $this->assertEquals(
            $renderer->render($icon),
            $mustache->render('test', ['icon' => 'movehere', 'delete' => get_string('delete')])
        );
        $this->assertDebuggingCalled('Legacy pix icon helper API in use, please use the pix icon template instead.');

        // Malicious alt 1.
        $loader->setTemplate('test', '{{#pix}}movehere,core,<script>alert(window.location);</script>{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', '<script>alert(window.location);</script>')),
            $mustache->render('test')
        );
        $this->assertNotContains('window.location', $page->requires->get_end_code());
        $this->assertDebuggingCalled('Legacy pix icon helper API in use, please use the pix icon template instead.');

        // Malicious alt 2, this is currently possible.
        $loader->setTemplate('test', '{{#pix}}movehere,core,{{#js}}alert(window.location);{{/js}}{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', '')),
            $mustache->render('test')
        );
        $this->assertNotContains('window.location', $page->requires->get_end_code());
        $this->assertDebuggingCalled([
            'Escaped content contains unexpected mustache processing queues. It will be lost.',
            'Legacy pix icon helper API in use, please use the pix icon template instead.'
        ]);
    }

    public function test_no_exploitable_pix_helper_uses() {
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
                $result = self::has_pix_helper_containing_recursive_helpers($content);
                if ($result) {
                    if ($whitelistkey !== false) {
                        // It's OK, its on the whitelist.
                        $whitelist->remove($whitelistkey); // Totara: path_whitelist
                        continue;
                    }
                    $recursivehelpers[] = str_replace($CFG->dirroot, '', $path).' :: '.$result;
                }
                $result = self::has_pix_helper_containing_variables($content);
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
            $this->fail('Templates containing pix helper uses which contain recursive helper calls found'."\n * ".join("\n * ", $recursivehelpers));
        }
        if (!empty($variablesinhelpers)) {
            $this->fail('Templates containing variables in pix helpers.'."\n * ".join("\n * ", $variablesinhelpers));
        }
        if (!$whitelist->is_empty()) { // Totara: path_whitelist
            $this->fail('Items on the whitelist were not found to contain vulnerabilities.'."\n".$whitelist->join("\n"));
        }
    }

    public function test_non_conforming_a_string_identifier() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        $loader->setTemplate('test', '{{#pix}}movehere,core,test case{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', 'test case')),
            $mustache->render('test')
        );
        $this->assertDebuggingCalled('Legacy pix icon helper API in use, please use the pix icon template instead.');
    }

    public function test_non_conforming_a_string_component() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        $loader->setTemplate('test', '{{#pix}}movehere,core,delete,test case{{/pix}}');
        $this->assertEquals(
            $renderer->render(new pix_icon('movehere', get_string('delete'))),
            $mustache->render('test')
        );
        $this->assertDebuggingCalled('Invalid $a component for pix helper must be a string component.');
    }

    public function test_has_pix_helper_containing_variables() {
        // None.
        self::assertFalse(self::has_pix_helper_containing_variables(''));
        self::assertFalse(self::has_pix_helper_containing_variables('test'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{test}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{{test}}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#pix}}test{{/pix}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#flex_icon}}test{{/flex_icon}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#pix}}  test  {{/pix}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#pix}}{test}{{/pix}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#flex_icon}}{test}{{/flex_icon}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{#flex_icon}}{{test}}{{/flex_icon}}'));
        self::assertFalse(self::has_pix_helper_containing_variables('#pix{{test}}pix'));
        self::assertFalse(self::has_pix_helper_containing_variables('{{pix}}{{test}}{{pix}}'));

        // One.
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}{{test}}{{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{# pix }} {{ test }} {{/ pix }}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}{{{test}}}{{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}{{{{test}}}}{{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}  {{test}}  {{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}  {{{test}}}  {{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}  {{{{test}}}}  {{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_variables('{{#pix}}{{test}}{{/pix}}{{#flex_icon}}test{{/flex_icon}}'));

        // Multiple.
        self::assertSame(2, self::has_pix_helper_containing_variables('{{#pix}}{{test}}{{/pix}}{{#pix}}{{test}}{{/pix}}'));
        self::assertSame(3, self::has_pix_helper_containing_variables('{{#pix}} {{test}}, {{test}} {{/pix}} {{#pix}} {{test}} {{/pix}}'));
        self::assertSame(3, self::has_pix_helper_containing_variables('{{# pix }} {{ test }}, {{ test }} {{/ pix }} {{# pix }} {{ test }} {{/ pix }}'));
    }

    private static function has_pix_helper_containing_variables(string $template) {
        preg_match_all('@(\{{2}[#/][^\}]+\}{2}|\{{2,3}[^\}!]+\}{2,3})@', $template, $matches);
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
        self::assertSame(1, self::has_pix_helper_containing_recursive_helpers('{{#pix}}{{#flex_icon}}test{{/flex_icon}}{{/pix}}'));
        self::assertSame(1, self::has_pix_helper_containing_recursive_helpers('{{# pix }} {{# flex_icon }} test {{/ flex_icon }} {{/ pix }}'));
        self::assertSame(2, self::has_pix_helper_containing_recursive_helpers('{{#pix}}{{#flex_icon}}{{#pix}}test{{/pix}}{{/flex_icon}}{{/pix}}'));

        // Multiple.
        self::assertSame(2, self::has_pix_helper_containing_recursive_helpers('{{#pix}}{{#pix}}{{#pix}}{{test}}{{/pix}}{{/pix}}{{/pix}}'));
    }

    private static function has_pix_helper_containing_recursive_helpers(string $template) {
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
