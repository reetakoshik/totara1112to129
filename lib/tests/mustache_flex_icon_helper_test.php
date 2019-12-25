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
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralms.com>>
 * @package   core_output
 */

use core\output\flex_icon;
use totara_core\path_whitelist; // Totara: path_whitelist

defined('MOODLE_INTERNAL') || die();

class mustache_flex_icon_helper_testcase extends advanced_testcase {

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

    /**
     * It should generate the same output as rendering the renderable without customdata.
     */
    public function test_valid_usage() {

        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        // Identifier only.
        $loader->setTemplate('test', '{{#flex_icon}}permissions-check{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('permissions-check')),
            $mustache->render('test')
        );

        // PIX Identifier only.
        $loader->setTemplate('test', '{{#flex_icon}}core|t/edit{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('core|t/edit')),
            $mustache->render('test')
        );

        // Two piece flex icon.
        $loader->setTemplate('test', '{{#flex_icon}}alarm-danger{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('alarm-danger')),
            $mustache->render('test')
        );

        // Variable identifier.
        $loader->setTemplate('test', '{{#flex_icon}}{{test_icon}}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('settings')),
            $mustache->render('test', ['test_icon' => 'settings'])
        );

        // Identifier + alt
        $loader->setTemplate('test', '{{#flex_icon}}alarm-danger,delete{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('alarm-danger', ['alt' => get_string('delete')])),
            $mustache->render('test')
        );

        // Identifier + alt: variable spacing 1
        $loader->setTemplate('test', '{{#flex_icon}} alarm-danger , delete {{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('alarm-danger', ['alt' => get_string('delete')])),
            $mustache->render('test')
        );

        // Identifier + alt: variable spacing 2
        $loader->setTemplate('test', '{{# flex_icon }}  alarm-danger  ,  delete  {{/ flex_icon }}');
        $this->assertEquals(
            $renderer->render(new flex_icon('alarm-danger', ['alt' => get_string('delete')])),
            $mustache->render('test')
        );

        // Identifier + alt: variable spacing 3
        $loader->setTemplate('test', '{{#  flex_icon  }}  alarm-danger  ,  delete  {{/  flex_icon  }}');
        $this->assertEquals(
            $renderer->render(new flex_icon('alarm-danger', ['alt' => get_string('delete')])),
            $mustache->render('test')
        );

        // Variable identifier with get_string alt.
        $loader->setTemplate('test', '{{#flex_icon}}{{identifier}}, {{alt}}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('core|i/edit', ['alt' => get_string('delete')])),
            $mustache->render('test', ['identifier' => 'core|i/edit', 'alt' => 'delete'])
        );

        // Variable identifier with get_string alt + component.
        $loader->setTemplate('test', '{{#flex_icon}}{{identifier}}, {{alt}}, {{component}}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('core|i/edit', ['alt' => get_string('delete')])),
            $mustache->render('test', ['identifier' => 'core|i/edit', 'alt' => 'delete', 'component' => 'core'])
        );

        // Variable identifier with get_string alt + component + classes.
        $loader->setTemplate('test', '{{#flex_icon}}{{identifier}}, {{alt}}, {{component}}, size-large test{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('core|i/edit', ['alt' => get_string('delete'), 'classes' => 'size-large test'])),
            $mustache->render('test', ['identifier' => 'core|i/edit', 'alt' => 'delete', 'component' => 'core'])
        );

        // Variable identifier with get_string alt + component + classes: Variable spacing
        $loader->setTemplate('test', '{{# flex_icon }} {{identifier}} , {{alt}} , {{component}} , size-large test {{/ flex_icon }}');
        $this->assertEquals(
            $renderer->render(new flex_icon('core|i/edit', ['alt' => get_string('delete'), 'classes' => 'size-large test'])),
            $mustache->render('test', ['identifier' => 'core|i/edit', 'alt' => 'delete', 'component' => 'core'])
        );
    }

    public function test_deprecated_usage() {
        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        // Static identifier + JSON encoded classes.
        $loader->setTemplate('test', '{{#flex_icon}}permissions-check, {"classes": "ft-state-success ft-size-700"}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('permissions-check', ['classes' => 'ft-state-success ft-size-700'])),
            $mustache->render('test')
        );

        // Variable identifier with JSON encoded classes.
        $loader->setTemplate('test', '{{#flex_icon}}{{test_icon}}, {"classes":"ft-state-success ft-size-700"}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('settings', ['classes' => 'ft-state-success ft-size-700'])),
            $mustache->render('test', ['test_icon' => 'settings'])
        );

        // Variable identifier with JSON encoded alt.
        $loader->setTemplate('test', '{{#flex_icon}}{{test_icon}}, {"alt":"{{alt}}"}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('settings', ['alt' => 'Test fun'])),
            $mustache->render('test', ['test_icon' => 'settings', 'alt' => 'Test fun'])
        );

        // Variable identifier with JSON encoded alt using string helper.
        $loader->setTemplate('test', '{{#flex_icon}}{{test_icon}}, {"alt":"{{#str}}delete{{/str}}"}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('settings')),
            $mustache->render('test', ['test_icon' => 'settings'])
        );
        $this->assertDebuggingCalled([
            'Legacy flex icon helper API in use, please use the flex icon template instead.',
            'Legacy flex icon helper API in use, please use the flex icon template instead.',
            'Legacy flex icon helper API in use, please use the flex icon template instead.',
            'Escaped content contains unexpected mustache processing queues. It will be lost.',
            'Legacy flex icon helper API in use, please use the flex icon template instead.'
        ]);

        // Variable identifier with JSON encoded alt using string helper.
        $loader->setTemplate('test', '{{#flex_icon}}settings, {"alt":"{{#str}}added, moodle,{{#str}}delete{{/str}}{{/str}}"}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('settings', ['alt' =>''])),
            $mustache->render('test')
        );
        $this->assertDebuggingCalled([
            'Escaped content contains unexpected mustache processing queues. It will be lost.',
            'Legacy flex icon helper API in use, please use the flex icon template instead.'
        ]);

        // Variable identifier, alt, and classes.
        $loader->setTemplate('test', '{{#flex_icon}}{{test_icon}}, {"alt": "{{alt}}", "classes": "{{classes}}"}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('core|i/edit', ['alt' => 'Test fun', 'classes' => 'test testing'])),
            $mustache->render('test', ['test_icon' => 'core|i/edit', 'alt' => 'Test fun', 'classes' => 'test testing'])
        );
        $this->assertDebuggingCalled('Legacy flex icon helper API in use, please use the flex icon template instead.');

        // Variable identifier, alt, and classes.
        $loader->setTemplate('test', '{{#flex_icon}}{{test_icon}}, {"data-test": "monkeys"}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('core|i/edit', ['data-test' => 'monkeys'])),
            $mustache->render('test', ['test_icon' => 'core|i/edit'])
        );
        $this->assertDebuggingCalled('Legacy flex icon helper API in use, please use the flex icon template instead.');
    }

    /**
     * It should throw an exception if helper JSON cannot be parsed.
     */
    public function test_invalid_usage() {

        /**
         * @var Mustache_Engine $mustache
         * @var Mustache_Loader_ArrayLoader $loader
         * @var core_renderer $renderer
         * @var moodle_page $page
         */
        list($mustache, $loader, $renderer, $page) = $this->get_mustache();

        // Variable identifier with variable alt
        $loader->setTemplate('test', '{{#flex_icon}}{{test_icon}}, {{alt}}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('settings')),
            $mustache->render('test', ['test_icon' => 'settings', 'alt' => 'Test fun'])
        );
        $this->assertDebuggingCalled('Invalid alt identifier for flex icon, it must be a string identifier.');

        // Pix identifier, with variable component (invalid, not a string identifier).
        $loader->setTemplate('test', '{{#flex_icon}}{{identifier}}, {{alt}}, {{component}}{{/flex_icon}}');
        $this->assertEquals(
            $renderer->render(new flex_icon('core|i/edit')),
            $mustache->render('test', ['identifier' => 'core|i/edit', 'alt' => 'delete', 'component' => 'Totara Core'])
        );
        $this->assertDebuggingCalled('Invalid alt component for flex icon, it must be a string component.');

        // Invalid JSON.
        $loader->setTemplate('test', '{{#flex_icon}}cog, { this # is not valid JSON : 7 \ }{{/flex_icon}}');
        try {
            $mustache->render('test', ['test_icon' => 'settings']);
            $this->fail('An exception was expected');
        } catch (Exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: flex_icon helper was unable to decode JSON', $e->getMessage());
        }
    }

    public function test_no_exploitable_flex_helper_uses() {
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
                $result = self::has_flex_icon_helper_containing_recursive_helpers($content);
                if ($result) {
                    if ($whitelistkey !== false) {
                        // It's OK, its on the whitelist.
                        $whitelist->remove($whitelistkey); // Totara: path_whitelist
                        continue;
                    }
                    $recursivehelpers[] = str_replace($CFG->dirroot, '', $path).' :: '.$result;
                }
                $result = self::has_flex_icon_helper_containing_variables($content);
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
            $this->fail('Templates containing flex helper uses which contain recursive helper calls found'."\n * ".join("\n * ", $recursivehelpers));
        }
        if (!empty($variablesinhelpers)) {
            $this->fail('Templates containing variables in flex helpers.'."\n * ".join("\n * ", $variablesinhelpers));
        }
        if (!$whitelist->is_empty()) { // Totara: path_whitelist
            $this->fail('Items on the whitelist were not found to contain vulnerabilities.'."\n".$whitelist->join("\n"));
        }
    }

    public function test_has_flex_icon_helper_containing_variables() {
        // None.
        self::assertFalse(self::has_flex_icon_helper_containing_variables(''));
        self::assertFalse(self::has_flex_icon_helper_containing_variables('test'));
        self::assertFalse(self::has_flex_icon_helper_containing_variables('{{test}}'));
        self::assertFalse(self::has_flex_icon_helper_containing_variables('{{{test}}}'));
        self::assertFalse(self::has_flex_icon_helper_containing_variables('{{#flex_icon}}test{{/flex_icon}}'));
        self::assertFalse(self::has_flex_icon_helper_containing_variables('{{#str}}test{{/str}}'));
        self::assertFalse(self::has_flex_icon_helper_containing_variables('{{#flex_icon}}  test  {{/flex_icon}}'));
        self::assertFalse(self::has_flex_icon_helper_containing_variables('{{#flex_icon}}{test}{{/flex_icon}}'));
        self::assertFalse(self::has_flex_icon_helper_containing_variables('{{#str}}{test}{{/str}}'));
        self::assertFalse(self::has_flex_icon_helper_containing_variables('{{#str}}{{test}}{{/str}}'));
        self::assertFalse(self::has_flex_icon_helper_containing_variables('#flex_icon{{test}}flex_icon'));
        self::assertFalse(self::has_flex_icon_helper_containing_variables('{{flex_icon}}{{test}}{{flex_icon}}'));

        // One.
        self::assertSame(1, self::has_flex_icon_helper_containing_variables('{{#flex_icon}}{{test}}{{/flex_icon}}'));
        self::assertSame(1, self::has_flex_icon_helper_containing_variables('{{# flex_icon }} {{ test }} {{/ flex_icon }}'));
        self::assertSame(1, self::has_flex_icon_helper_containing_variables('{{#flex_icon}}{{{test}}}{{/flex_icon}}'));
        self::assertSame(1, self::has_flex_icon_helper_containing_variables('{{#flex_icon}}{{{{test}}}}{{/flex_icon}}'));
        self::assertSame(1, self::has_flex_icon_helper_containing_variables('{{#flex_icon}}  {{test}}  {{/flex_icon}}'));
        self::assertSame(1, self::has_flex_icon_helper_containing_variables('{{#flex_icon}}  {{{test}}}  {{/flex_icon}}'));
        self::assertSame(1, self::has_flex_icon_helper_containing_variables('{{#flex_icon}}  {{{{test}}}}  {{/flex_icon}}'));
        self::assertSame(1, self::has_flex_icon_helper_containing_variables('{{#flex_icon}}{{test}}{{/flex_icon}}{{#str}}test{{/str}}'));

        // Multiple.
        self::assertSame(2, self::has_flex_icon_helper_containing_variables('{{#flex_icon}}{{test}}{{/flex_icon}}{{#flex_icon}}{{test}}{{/flex_icon}}'));
        self::assertSame(3, self::has_flex_icon_helper_containing_variables('{{#flex_icon}} {{test}}, {{test}} {{/flex_icon}} {{#flex_icon}} {{test}} {{/flex_icon}}'));
        self::assertSame(3, self::has_flex_icon_helper_containing_variables('{{# flex_icon }} {{ test }}, {{ test }} {{/ flex_icon }} {{# flex_icon }} {{ test }} {{/ flex_icon }}'));
    }

    private static function has_flex_icon_helper_containing_variables(string $template) {
        preg_match_all('@(\{{2}[#/][^\}]+\}{2}|\{{2,3}[^\}!]+\}{2,3})@', $template, $matches);
        $helper = 'flex_icon';
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

    public function test_has_flex_icon_helper_containing_recursive_helpers() {
        // None.
        self::assertFalse(self::has_flex_icon_helper_containing_recursive_helpers(''));
        self::assertFalse(self::has_flex_icon_helper_containing_recursive_helpers('test'));
        self::assertFalse(self::has_flex_icon_helper_containing_recursive_helpers('{{test}}'));
        self::assertFalse(self::has_flex_icon_helper_containing_recursive_helpers('{{{test}}}'));
        self::assertFalse(self::has_flex_icon_helper_containing_recursive_helpers('{{#flex_icon}}{{test}}{{/flex_icon}}'));
        self::assertFalse(self::has_flex_icon_helper_containing_recursive_helpers('{{# flex_icon }} {{ test }} {{/ flex_icon }}'));
        self::assertFalse(self::has_flex_icon_helper_containing_recursive_helpers('{{#flex_icon}}{{test}}{{/flex_icon}}{{#flex_icon}}{{test}}{{/flex_icon}}'));

        // One.
        self::assertSame(1, self::has_flex_icon_helper_containing_recursive_helpers('{{#flex_icon}}{{#flex_icon}}{{test}}{{/flex_icon}}{{/flex_icon}}'));
        self::assertSame(1, self::has_flex_icon_helper_containing_recursive_helpers('{{#flex_icon}}{{#str}}test{{/str}}{{/flex_icon}}'));
        self::assertSame(1, self::has_flex_icon_helper_containing_recursive_helpers('{{# flex_icon }} {{# str }} test {{/ str }} {{/ flex_icon }}'));
        self::assertSame(2, self::has_flex_icon_helper_containing_recursive_helpers('{{#flex_icon}}{{#str}}{{#flex_icon}}test{{/flex_icon}}{{/str}}{{/flex_icon}}'));

        // Multiple.
        self::assertSame(2, self::has_flex_icon_helper_containing_recursive_helpers('{{#flex_icon}}{{#flex_icon}}{{#flex_icon}}{{test}}{{/flex_icon}}{{/flex_icon}}{{/flex_icon}}'));
    }

    private static function has_flex_icon_helper_containing_recursive_helpers(string $template) {
        preg_match_all('@\{{2}[#/][^\}]+\}{2}@', $template, $matches);
        $helper = 'flex_icon';
        $level = 0;
        $count = 0;
        $regex_open = '@\{{2}# *'.preg_quote($helper, '@').' *\}{2}@';
        $regex_close = '@\{{2}/ *'.preg_quote($helper, '@').' *\}{2}@';
        foreach ($matches[0] as $match) {
            $opening_flex_icon = preg_match($regex_open, $match);
            $closing_flex_icon = preg_match($regex_close, $match);
            $opening = $opening_flex_icon || (strpos($match, '{{#') !== false);

            if ($opening_flex_icon) {
                if ($level > 0) {
                    $count++;
                }
                $level++;
            } else if ($closing_flex_icon) {
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
