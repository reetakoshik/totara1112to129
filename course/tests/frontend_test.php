<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package core_course
 */


defined('MOODLE_INTERNAL') || die();

use core_availability\frontend;


/**
 * Test frontend classes.
 */
class frontend_testcase extends advanced_testcase {

    /**
     * Data provider - [ availability, ... ]
     *
     * The following variables must be replaced by replace_tree_structure() function.
     *   %cm%       int     Course module
     *   %coh1%     string  Audience #1
     *   %coh2%     string  Audience #2
     *   %org1%     string  Organization #1
     *   %org2%     string  Organization #2
     *   %pos%      string  Position
     *
     * @return array
     */
    public function data_provider_availability() {
        $past = time() - YEARSECS * 2;
        $future = time() + YEARSECS * 3;
        $data[] = array(
            (object)array(
                'op' => '|',
                'c' => array(
                    (object)array(
                        'type' => 'date',
                        'd' => '<',
                        't' => $future,
                    ),
                ),
                'show' => true,
            ),
        );
        $data[] = array(
            (object)array(
                'op' => '!|',
                'c' => array(
                    (object)array(
                        'type' => 'date',
                        'd' => '>=',
                        't' => $past,
                    ),
                    (object)array(
                        'type' => 'grade',
                        'id' => '%cm%',
                        'max' => 0,
                    ),
                    (object)array(
                        'type' => 'language',
                        'lang' => 'en',
                    ),
                ),
                'showc' => array(true, true, true),
            ),
        );
        $data[] = array(
            (object)array(
                'op' => '&',
                'c' => array(
                    (object)array(
                        'op' => '&',
                        'c' => array(
                            (object)array(
                                'op' => '&',
                                'c' => array(
                                    (object)array(
                                        'op' => '&',
                                        'c' => array(
                                            (object)array(
                                                'type' => 'date',
                                                'd' => '>=',
                                                't' => $past
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'showc' => array(true),
            ),
        );
        $data[] = array(
            (object)array(
                'op' => '&',
                'c' => array(
                    (object)array(
                        'op' => '&',
                        'c' => array(
                            (object)array(
                                'type' => 'audience',
                                'cohort' => '%coh1%',
                            ),
                        ),
                    ),
                ),
                'showc' => array(true),
            ),
        );
        $data[] = array(
            (object)array(
                'op' => '&',
                'c' => array(
                    (object)array(
                        'op' => '|',
                        'c' => array(
                            (object)array(
                                'type' => 'audience',
                                'cohort' => '%coh1%',
                            ),
                            (object)array(
                                'type' => 'language',
                                'lang' => 'en',
                            ),
                            (object)array(
                                'type' => 'audience',
                                'cohort' => '%coh2%',
                            ),
                        ),
                    ),
                ),
                'showc' => array(true),
            ),
        );
        $data[] = array(
            (object)array(
                'op' => '&',
                'c' => array(
                    (object)array(
                        'op' => '&',
                        'c' => array(
                            (object)array(
                                'type' => 'date',
                                'd' => '>=',
                                't' => $past
                            ),
                            (object)array(
                                'type' => 'language',
                                'lang' => 'en',
                            ),
                        ),
                    ),
                ),
                'showc' => array(true),
            ),
        );
        $data[] = array(
            (object)array(
                'op' => '!|',
                'c' => array(
                    (object)array(
                        'type' => 'language',
                        'lang' => 'en',
                    ),
                    (object)array(
                        'op' => '!&',
                        'c' => array(
                            (object)array(
                                'op' => '&',
                                'c' => array(
                                    (object)array(
                                        'op' => '&',
                                        'c' => array(
                                            (object)array(
                                                'op' => '!|',
                                                'c' => array(
                                                    (object)array(
                                                        'op' => '|',
                                                        'c' => array(
                                                            (object)array(
                                                                'op' => '!&',
                                                                'c' => array(
                                                                    (object)array(
                                                                        'type' => 'completion',
                                                                        'cm' => '%cm%',
                                                                        'e' => 0,
                                                                    ),
                                                                    (object)array(
                                                                        'type' => 'grade',
                                                                        'id' => '%cm%',
                                                                        'min' => 42,
                                                                    ),
                                                                ),
                                                            ),
                                                            (object)array(
                                                                'type' => 'profile',
                                                                'sf' => 'icq',
                                                                'op' => 'startswith',
                                                                'v' => '123',
                                                            ),
                                                        ),
                                                    ),
                                                    (object)array(
                                                        'type' => 'date',
                                                        'd' => '<',
                                                        't' => $future,
                                                    ),
                                                ),
                                            ),
                                            (object)array(
                                                'type' => 'audience',
                                                'cohort' => '%coh2%',
                                            ),
                                        ),
                                    ),
                                    (object)array(
                                        'type' => 'profile',
                                        'sf' => 'country',
                                        'op' => 'isequalto',
                                        'v' => 'Aotearoa',
                                    ),
                                ),
                            ),
                            (object)array(
                                'type' => 'date',
                                'd' => '>=',
                                't' => $past,
                            ),
                        ),
                    ),
                    (object)array(
                        'op' => '|',
                        'c' => array(
                            (object)array(
                                'op' => '!|',
                                'c' => array(
                                    (object)array(
                                        'op' => '!&',
                                        'c' => array(
                                            (object)array(
                                                'op' => '|',
                                                'c' => array(
                                                    (object)array(
                                                        'type' => 'hierarchy_organisation',
                                                        'organisation' => '%org1%',
                                                    ),
                                                    (object)array(
                                                        'type' => 'hierarchy_organisation',
                                                        'organisation' => '%org2%',
                                                    ),
                                                ),
                                            ),
                                            (object)array(
                                                'type' => 'grade',
                                                'id' => '%cm%',
                                                'min' => 50,
                                                'max' => 51,
                                            ),
                                        ),
                                    ),
                                    (object)array(
                                        'type' => 'date',
                                        'd' => '<',
                                        't' => $future,
                                    ),
                                ),
                            ),
                            (object)array(
                                'op' => '!&',
                                'c' => array(
                                    (object)array(
                                        'op' => '&',
                                        'c' => array(
                                            (object)array(
                                                'type' => 'audience',
                                                'cohort' => '%coh1%',
                                            ),
                                            (object)array(
                                                'type' => 'hierarchy_position',
                                                'position' => '%pos%',
                                            ),
                                        ),
                                    ),
                                    (object)array(
                                        'type' => 'date',
                                        'd' => '>=',
                                        't' => $past,
                                    ),
                                    (object)array(
                                        'type' => 'date',
                                        'd' => '<',
                                        't' => $future,
                                    ),
                                ),
                            ),
                            (object)array(
                                'type' => 'time_since_completion',
                                'cm' => '%cm%',
                                'expectedcompletion' => 1,
                                'timeamount' => 1,
                                'timeperiod' => 5,
                            ),
                            (object)array(
                                'type' => 'completion',
                                'cm' => '%cm%',
                                'e' => 1,
                            ),
                        ),
                    ),
                ),
                'showc' => array(true, true, true)
            ),
        );
        return $data;
    }

    /**
     * Recursively replace variables in a tree structure with actual IDs
     *
     * @param mixed $source
     * @param array $replace
     * @return mixed
     */
    private static function realise_tree_structure($source, $replace) {
        if ($source instanceof \stdClass) {
            $destination = new \stdClass();
            foreach ((array)$source as $key => $value) {
                $destination->{$key} = self::realise_tree_structure($value, $replace);
            }
            return $destination;
        }
        if (is_array($source)) {
            return array_map(
                function ($e) use ($replace) {
                    return self::realise_tree_structure($e, $replace);
                },
                $source
            );
        }
        foreach ($replace as $rep => $to) {
            if ($source === $rep) {
                return $to;
            }
        }
        return $source;
    }

    /**
     * Test various protected functions of the frontend class.
     *
     * @dataProvider data_provider_availability
     */
    public function test_frontend_functions($structure) {
        global $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $CFG->enableavailability = true;

        $record = (object)[
            'audiencevisible' => COHORT_VISIBLE_NOUSERS,
            'shortname' => 'Course101',
            'fullname' => 'Course101'
        ];

        $options = [
            'createsections' => true
        ];

        $gen = $this->getDataGenerator();
        $hi = $gen->get_plugin_generator('totara_hierarchy');
        $course = $gen->create_course($record, $options);

        $replace = array(
            '%cm%'   => (int)$gen->create_module('quiz', array('course' => $course->id))->id,
            '%coh1%' => (string)$gen->create_cohort()->id,
            '%coh2%' => (string)$gen->create_cohort()->id,
            '%org1%' => (string)$hi->create_org(array('frameworkid' => $hi->create_org_frame(null)->id))->id,
            '%org2%' => (string)$hi->create_org(array('frameworkid' => $hi->create_org_frame(null)->id))->id,
            '%pos%'  => (string)$hi->create_hierarchy($hi->create_framework('position')->id, 'position')->id,
        );

        $structure = self::realise_tree_structure($structure, $replace);
        $tree = new \core_availability\tree($structure);
        $json = json_encode($tree->save());

        $mod = $gen->create_module('data', array('course' => $course->id, 'availability' => $json));
        $cm = get_coursemodule_from_instance('data', $mod->id, $course->id);
        $cminfo = cm_info::create($cm);
        $sectioninfo = get_fast_modinfo($course->id)->get_section_info(1);

        $pluginmanager = \core_plugin_manager::instance();
        $plugins = $pluginmanager->get_installed_plugins('availability');

        foreach ($plugins as $plugin => $info) {
            // Create plugin front-end object.
            $class = '\availability_' . $plugin . '\frontend';
            $frontend = new $class();

            // Call overridable protected functions and make sure:
            // - no exceptions are thrown
            // - a reasonable value is returned

            $reflect = new ReflectionClass($frontend);
            $allow_add = $reflect->getMethod('allow_add');
            $allow_add->setAccessible(true);
            $get_js_init_pams = $reflect->getMethod('get_javascript_init_params');
            $get_js_init_pams->setAccessible(true);
            $get_js_strs = $reflect->getMethod('get_javascript_strings');
            $get_js_strs->setAccessible(true);

            // allow_add() must return a boolean value
            $result = $allow_add->invoke($frontend, $course, $cminfo, $sectioninfo);
            $this->assertIsBool($result);

            // get_javascript_init_params() must return an array
            $result = $get_js_init_pams->invoke($frontend, $course, $cminfo, $sectioninfo);
            $this->assertIsArray($result);

            // get_javascript_strings() must return an array
            $result = $get_js_strs->invoke($frontend);
            $this->assertIsArray($result);
        }

        // Call a real function to make sure that no exceptions are thrown
        frontend::include_all_javascript($course, $cminfo, $sectioninfo);
    }

    /**
     * Data provider - [ depth|json, expected_coding_exception_message ]
     *
     * @return array
     */
    public function data_provider_nesting_depth() {
        $max_depth = frontend::AVAILABILITY_JSON_MAX_DEPTH;
        return [
            [ $max_depth / 4, '' ],
            [ $max_depth / 2 - 1, '' ],
            [ $max_depth / 2, 'Invalid JSON from availabilityconditionsjson field' ],
            [ $max_depth, 'Invalid JSON from availabilityconditionsjson field' ],
            [ '', '' ], // empty JSON is valid
            [ '""', 'Invalid JSON from availabilityconditionsjson field' ], // empty string is NOT valid
            [ 'false', 'Invalid JSON from availabilityconditionsjson field' ], // different exception to 'true'
            [ '0', 'Invalid JSON from availabilityconditionsjson field' ], // different exception to '1'
            [ '[]', 'Invalid JSON from availabilityconditionsjson field' ],
            [ 'foo', 'Invalid JSON from availabilityconditionsjson field' ],
            [ '{"oops}', 'Invalid JSON from availabilityconditionsjson field' ],
            [ '[1]', 'Invalid availability structure (not object)' ],
            [ 'true', 'Invalid availability structure (not object)' ], // different exception to 'false'
            [ '1', 'Invalid availability structure (not object)' ], // different exception to '0'
            [ '{"o":"h"}', 'Invalid availability structure (missing ->op)' ],
            [ '{"op":"\uD83D\uDE02"}', 'Invalid availability structure (unknown ->op)' ],
            [ '{"op":"&","c":[{"type":"\uD83D\uDE0D"}],"showc":[true]}', "Unknown condition type: \u{1F60D}" ],
        ];
    }

    /**
     * Test frontend::report_validation_errors() with various JSON input.
     *
     * @param int|string $depth_or_json
     * @param string $expected_exception
     * @dataProvider data_provider_nesting_depth
     */
    public function test_report_validation_errors($depth_or_json, string $expected_exception) {
        global $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $CFG->enableavailability = true;

        // create nested json without a recursive function.
        if (is_number($depth_or_json)) {
            $json = '';
            $depth = (int)$depth_or_json;
            if ($depth > 1) {
                for ($i = 1; $i <= $depth; $i++) {
                    $json .= '{"op":"&","c":[';
                }
                for ($i = 2; $i <= $depth; $i++) {
                    $json .= '{"type":"date","d":">=","t":'. (11 << 27) . '}';
                    if ($i !== $depth) {
                        $json .= ']},';
                    }
                }
                $json .= ']}],"showc":[true]}';
            }
        } else {
            $json = $depth_or_json;
        }

        $errors = [];
        $data = [ 'availabilityconditionsjson' => $json ];

        try {
            frontend::report_validation_errors($data, $errors);
        } catch (Exception $e) {
            if (!empty($expected_exception) && $e instanceof \coding_exception) {
                $this->assertEquals('Coding error detected, it must be fixed by a programmer: ' . $expected_exception, $e->getMessage());
                return;
            }
            $this->fail('Unexpected exception ' . get_class($e) . ' thrown');
        }
    }
}
