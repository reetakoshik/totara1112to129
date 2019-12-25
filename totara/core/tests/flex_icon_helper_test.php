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
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>>
 * @package   core
 */

use core\output\flex_icon_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit unit tests for \core\output\flex_icon_helper class.
 */
class totara_core_flex_icon_helper_testcase extends advanced_testcase {
    public function test_get_icons() {
        global $CFG;
        $this->resetAfterTest();

        $icons = flex_icon_helper::get_icons($CFG->theme);
        $this->assertIsArray($icons);

        purge_all_caches();
        $this->assertSame($icons, flex_icon_helper::get_icons($CFG->theme));

        $this->assertSame($icons, flex_icon_helper::get_icons(null));
        $this->assertSame($icons, flex_icon_helper::get_icons(''));
        $this->assertSame($icons, flex_icon_helper::get_icons('xzxzzxzxzx'));
    }

    public function test_get_ajax_data() {
        global $CFG;
        $this->resetAfterTest();

        $icons = flex_icon_helper::get_icons($CFG->theme);
        $ajax = flex_icon_helper::get_ajax_data($CFG->theme);

        $this->assertSame(array('templates', 'datas', 'icons'), array_keys($ajax));

        $this->assertContains('core/flex_icon', $ajax['templates']);
        $this->assertContains('core/flex_icon_stack', $ajax['templates']);

        $this->assertSame(array_keys($icons), array_keys($ajax['icons']));

        foreach ($ajax['icons'] as $identified => $desc) {
            // This is the mapping to be used in JS.
            $template = $ajax['templates'][$desc[0]];
            $data = $ajax['datas'][$desc[1]];

            $this->assertSame($icons[$identified]['template'], $template);
            $this->assertSame($icons[$identified]['data'], $data);
        }
    }

    public function test_get_template_by_identifier() {
        global $CFG;

        $this->assertSame('core/flex_icon', flex_icon_helper::get_template_by_identifier($CFG->theme, 'edit'));
        $this->assertSame('core/flex_icon_stack', flex_icon_helper::get_template_by_identifier($CFG->theme, 'unsubscribe'));

        $missingiconstemplate = flex_icon_helper::get_template_by_identifier($CFG->theme, flex_icon_helper::MISSING_ICON);
        $this->assertSame($missingiconstemplate, flex_icon_helper::get_template_by_identifier($CFG->theme, 'xxxzxxzxzxz'));
    }

    public function test_get_data_by_identifier() {
        global $CFG;

        $expected = array('classes' => 'fa-edit');
        $this->assertSame($expected, flex_icon_helper::get_data_by_identifier($CFG->theme, 'edit'));

        $expected = array('classes' => array(
            'fa-question ft-stack-main',
            'fa-exclamation ft-stack-suffix'));
        $this->assertSame($expected, flex_icon_helper::get_data_by_identifier($CFG->theme, 'unsubscribe'));

        $missingiconsdata = flex_icon_helper::get_data_by_identifier($CFG->theme, flex_icon_helper::MISSING_ICON);
        $this->assertSame($missingiconsdata, flex_icon_helper::get_data_by_identifier($CFG->theme, 'xxxzxxzxzxz'));
    }

    public function test_get_related_theme_dirs() {
        global $CFG;

        $themedefault = \theme_config::DEFAULT_THEME;

        $this->assertSame($themedefault, $CFG->theme);

        $theme = \theme_config::load($themedefault);
        $candidatedirs = $theme->get_related_theme_dirs();

        $themehierarchy = array_reverse($theme->parents);
        $themehierarchy[] = $themedefault;
        $this->assertCount(count($themehierarchy), $candidatedirs);

        for ($i = 0; $i < count($themehierarchy); $i++) {
            $this->assertSame(realpath("$CFG->dirroot/theme/{$themehierarchy[$i]}"), realpath($candidatedirs[$i]));
        }
    }

    public function test_protected_merge_flex_icons_file_overriding() {
        $reflectionclass = new \ReflectionClass('core\output\flex_icon_helper');
        $function = $reflectionclass->getMethod('merge_flex_icons_file');
        $function->setAccessible(true);

        $iconsdata = array(
            'aliases' => array(),
            'deprecated' => array(),
            'icons' => array(),
        );
        $merged1 = $function->invoke(null, __DIR__ . '/fixtures/test_flex_icons1.php', $iconsdata, true);
        $this->assertSame(array_keys($iconsdata), array_keys($merged1));
        $this->assertSame(array('add' => 'plus'), $merged1['aliases']);
        $this->assertSame(array('nav_exit' => 'caret-up'), $merged1['deprecated']);
        $this->assertSame(array(
            'icon' => array('data' => array('classes' => 'fa-edit')),
            'fancy' => array('data' => array('classes' => 'fa-circle')),
        ), $merged1['icons']);

        $merged1b = $function->invoke(null, __DIR__ . '/fixtures/test_flex_icons1.php', $merged1, true);
        $this->assertSame($merged1, $merged1b);

        $merged2 = $function->invoke(null, __DIR__ . '/fixtures/test_flex_icons2.php', $merged1, true);
        $this->assertSame($merged1, $merged2);

        $merged3 = $function->invoke(null, __DIR__ . '/fixtures/test_flex_icons3.php', $merged2, true);
        $this->assertSame(array_keys($iconsdata), array_keys($merged3));
        $this->assertSame(array('add' => 'minus', 'remove' => 'plus'), $merged3['aliases']);
        $this->assertSame(array('nav_exit' => 'caret-up', 'nav_entry' => 'caret-down'), $merged3['deprecated']);
        $this->assertSame(array(
            'icon' => array('data' => array('classes' => 'fa-edit ft-state-warning')),
            'fancy' => array('template' => 'core/flex_icon_stack', 'data' => array('classes' => 'fa-circle')),
        ), $merged3['icons']);
    }

    public function test_protected_merge_flex_icons_file_no_overriding() {
        $reflectionclass = new \ReflectionClass('core\output\flex_icon_helper');
        $function = $reflectionclass->getMethod('merge_flex_icons_file');
        $function->setAccessible(true);

        $iconsdata = array(
            'aliases' => array(),
            'deprecated' => array(),
            'icons' => array(),
        );
        $merged1 = $function->invoke(null, __DIR__ . '/fixtures/test_flex_icons1.php', $iconsdata, false);
        $this->assertSame(array_keys($iconsdata), array_keys($merged1));
        $this->assertSame(array('add' => 'plus'), $merged1['aliases']);
        $this->assertSame(array('nav_exit' => 'caret-up'), $merged1['deprecated']);
        $this->assertSame(array(
            'icon' => array('data' => array('classes' => 'fa-edit')),
            'fancy' => array('data' => array('classes' => 'fa-circle')),
        ), $merged1['icons']);

        $merged1b = $function->invoke(null, __DIR__ . '/fixtures/test_flex_icons1.php', $merged1, false);
        $this->assertSame($merged1, $merged1b);

        $merged2 = $function->invoke(null, __DIR__ . '/fixtures/test_flex_icons2.php', $merged1, false);
        $this->assertSame($merged1, $merged2);

        $merged3 = $function->invoke(null, __DIR__ . '/fixtures/test_flex_icons3.php', $merged2, false);
        $this->assertSame(array_keys($iconsdata), array_keys($merged3));
        $this->assertSame(array('add' => 'plus', 'remove' => 'plus'), $merged3['aliases']);
        $this->assertSame(array('nav_entry' => 'caret-down', 'nav_exit' => 'caret-up'), $merged3['deprecated']);
        $this->assertSame(array(
            'icon' => array('data' => array('classes' => 'fa-edit')),
            'fancy' => array('data' => array('classes' => 'fa-circle')),
        ), $merged3['icons']);
    }

    public function test_protected_resolve_aliases() {
        $reflectionclass = new \ReflectionClass('core\output\flex_icon_helper');
        $function = $reflectionclass->getMethod('resolve_aliases');
        $function->setAccessible(true);

        $iconsdata = array(
            'aliases' => array(
                'mod_xxx|start' => 'open',
                'core|i-open' => 'open',
                'core|i-edit' => 'edit',
                'open' => 'offnen', // Ignored.
            ),
            'deprecated' => array(
                'mod_xx|close' => 'close',
                'mod_xxx|startnow' => 'mod_xxx|start',
            ),
            'icons' => array(
                'close' => array(
                    'data' =>
                        array(
                            'classes' => 'fa-close',
                        ),
                ),
                'edit' => array(
                    'template' => 'core/flex_icon_stack',
                    'data' =>
                        array(
                            'classes' =>
                                array(
                                    'fa-edit ft-stack-main',
                                    'fa-exclamation ft-stack-suffix'
                                ),
                        ),
                ),

                'open' => array(
                    'data' =>
                        array(
                            'classes' => 'fa-open',
                        ),
                ),
            ),
        );

        $icons = $function->invoke(null, $iconsdata);

        $expected = array(
            'close' =>
                array(
                    'data' =>
                        array(
                            'classes' => 'fa-close',
                        ),
                    'template' => 'core/flex_icon',
                ),
            'edit' =>
                array(
                    'template' => 'core/flex_icon_stack',
                    'data' =>
                        array(
                            'classes' =>
                                array(
                                    'fa-edit ft-stack-main',
                                    'fa-exclamation ft-stack-suffix',
                                ),
                        ),
                ),
            'open' =>
                array(
                    'data' =>
                        array(
                            'classes' => 'fa-open',
                        ),
                    'template' => 'core/flex_icon',
                ),
            'mod_xxx|start' =>
                array(
                    'data' =>
                        array(
                            'classes' => 'fa-open',
                        ),
                    'template' => 'core/flex_icon',
                    'alias' => 'open',
                ),
            'core|i-open' =>
                array(
                    'data' =>
                        array(
                            'classes' => 'fa-open',
                        ),
                    'template' => 'core/flex_icon',
                    'alias' => 'open',
                ),
            'core|i-edit' =>
                array(
                    'template' => 'core/flex_icon_stack',
                    'data' =>
                        array(
                            'classes' =>
                                array(
                                    'fa-edit ft-stack-main',
                                    'fa-exclamation ft-stack-suffix',
                                ),
                        ),
                    'alias' => 'edit',
                ),
            'mod_xx|close' =>
                array(
                    'data' =>
                        array(
                            'classes' => 'fa-close',
                        ),
                    'template' => 'core/flex_icon',
                    'alias' => 'close',
                    'deprecated' => true,
                ),
            'mod_xxx|startnow' =>
                array(
                    'data' =>
                        array(
                            'classes' => 'fa-open',
                        ),
                    'template' => 'core/flex_icon',
                    'alias' => 'open',
                    'deprecated' => true,
                ),
        );
        $this->assertSame($expected, $icons);

        $iconsdata = array(
            'aliases' => array(
                'mod_xxx|missing' => 'xxzxzxzxz',
            ),
            'deprecated' => array(
            ),
            'icons' => array(
                'close' => array(
                    'data' =>
                        array(
                            'classes' => 'fa-close',
                        ),
                ),
            ),
        );
        $icons = $function->invoke(null, $iconsdata);
        $this->assertDebuggingCalled();
        $expected = array(
            'close' =>
                array(
                    'data' =>
                        array(
                            'classes' => 'fa-close',
                        ),
                    'template' => 'core/flex_icon',
                ),
        );
        $this->assertSame($expected, $icons);

        $iconsdata = array(
            'aliases' => array(
            ),
            'deprecated' => array(
                'mod_xxx|missing' => 'xxzxzxzxz',
            ),
            'icons' => array(
                'close' => array(
                    'data' =>
                        array(
                            'classes' => 'fa-close',
                        ),
                ),
            ),
        );
        $icons = $function->invoke(null, $iconsdata);
        $this->assertDebuggingCalled();
        $expected = array(
            'close' =>
                array(
                    'data' =>
                        array(
                            'classes' => 'fa-close',
                        ),
                    'template' => 'core/flex_icon',
                ),
        );
        $this->assertSame($expected, $icons);
    }

    public function test_all_flex_icons_files() {
        global $CFG;

        $locations = array('core' => $CFG->dirroot . '/pix');

        // Load all plugins in the standard order.
        $plugintypes = \core_component::get_plugin_types();
        foreach ($plugintypes as $type => $unused) {
            $plugs = \core_component::get_plugin_list($type);
            foreach ($plugs as $name => $location) {
                if (file_exists($location . '/pix')) {
                    $locations[$type . '_' . $name] = $location . '/pix';
                }
            }
        }

        foreach ($locations as $component => $location) {
            $knownfiles = $this->get_known_pix_files($location, $component);
            $knownfiles = array_flip($knownfiles);
            $pixfiles = $this->get_pix_files($location);

            $unknownfiles = array();
            foreach ($pixfiles as $file) {
                if (!isset($knownfiles[$file])) {
                    $identifier = substr($file, strlen($location . '/'));
                    $unknownfiles[] = preg_replace('/\.(gif|png|svg|)$/', '', $identifier);
                }
            }
            if ($unknownfiles) {
                $unknownfiles = array_unique($unknownfiles);
/* TODO finish flex icon conversion and fail test if any unknown pix icon file found
                echo "Location $location\n";
                echo "\$pixonlyimages = array(\n";
                foreach ($unknownfiles as $file) {
                    echo "    '$file',\n";
                }
                echo ");\n";
                echo "\n\n";
*/
            }
        }
    }

    protected function get_known_pix_files($location, $component) {
        $aliases = array();
        $icons = array();
        $deprecated = array();
        $pixonlyimages = array();

        $file = $location . '/flex_icons.php';
        if (file_exists($file)) {
            require($file);
        }

        $knownfiles = array();
        foreach ($aliases as $id => $ignored) {
            $id = preg_replace('/^' . preg_quote($component). '\|/', '', $id);
            $knownfiles[] = $location . '/'. $id . '.gif';
            $knownfiles[] = $location . '/'. $id . '.png';
            $knownfiles[] = $location . '/'. $id . '.svg';
        }
        foreach ($icons as $id => $ignored) {
            $id = preg_replace('/^' . preg_quote($component). '\|/', '', $id);
            $knownfiles[] = $location . '/'. $id . '.gif';
            $knownfiles[] = $location . '/'. $id . '.png';
            $knownfiles[] = $location . '/'. $id . '.svg';
        }
        foreach ($deprecated as $id => $ignored) {
            $id = preg_replace('/^' . preg_quote($component). '\|/', '', $id);
            $knownfiles[] = $location . '/'. $id . '.gif';
            $knownfiles[] = $location . '/'. $id . '.png';
            $knownfiles[] = $location . '/'. $id . '.svg';
        }
        foreach ($pixonlyimages as $file) {
            $knownfiles[] = $location . '/'. $file . '.gif';
            $knownfiles[] = $location . '/'. $file . '.png';
            $knownfiles[] = $location . '/'. $file . '.svg';
        }

        return $knownfiles;
    }

    protected function get_pix_files($location) {
        $pixfiles = array();
        foreach (new DirectoryIterator($location) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            $filename = $fileInfo->getFilename();
            if ($fileInfo->isDir()) {
                $pixfiles = array_merge($pixfiles, $this->get_pix_files($location . '/' . $filename));
                continue;
            }

            // Ignore anything that is not an image supported by pix_url.
            $extension = substr($filename, -4);
            if ($extension !== '.gif' and $extension !== '.png' and $extension !== '.svg') {
                continue;
            }

            // Get rid of size suffixes.
            $filename = preg_replace('/-\d\d+\.(gif|png|svg)$/', $extension, $filename);
            $pixfiles[] = $location . '/' . $filename;
        }

        $pixfiles = array_unique($pixfiles);
        return $pixfiles;
    }
}
