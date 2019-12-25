<?php
/*
 * This file is part of Totara Learn
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */
namespace core_course\totara_catalog\course\feature_factory;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\datasearch\all;
use totara_catalog\feature;
use totara_catalog\feature_factory;
use totara_catalog\local\config;

class type extends feature_factory {

    public static function get_features(): array {
        $datafilter = new all(
            'course_type_ftrd',
            'catalog',
            ['objecttype', 'objectid'],
            'LEFT JOIN'
        );

        $datafilter->add_source(
            'notused',
            '(SELECT courseftr.id, 1 AS featured
                      FROM {course} courseftr
                     WHERE courseftr.coursetype = :course_type_ftrd_id)',
            'course_type_ftrd',
            [
                'objectid' => 'course_type_ftrd.id',
                'objecttype' => "'course'",
            ],
            "",
            [
                'course_type_ftrd_id' => config::instance()->get_value('featured_learning_value'),
            ],
            [
                'featured' => 1,
            ]
        );

        $feature = new feature(
            'course_type_ftrd',
            new \lang_string('coursetype', 'totara_core'),
            $datafilter
        );

        $feature->add_options_loader(
            function () {
                global $TOTARA_COURSE_TYPES;

                $options = [];
                foreach ($TOTARA_COURSE_TYPES as $stringkey => $intkey) {
                    $options[$intkey] = new \lang_string($stringkey, 'totara_core');
                }

                return $options;
            }
        );

        return [$feature];
    }
}
