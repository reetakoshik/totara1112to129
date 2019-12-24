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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_cohort\rb\display;

/**
 * Class describing column display formatting.
 */
class cohort_association_duedate extends \totara_reportbuilder\rb\display\base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        // TODO:
        //
        // This class was created in TL-16684. The original display function was
        // in rb_source_cohort_associations::rb_display_programcompletionlink()
        // and it has major problems: the link it renders does not work when the
        // report source is used in a user generated report (see TL-16787). This
        // is why this class renders due dates as strings instead. However, that
        // causes a Behat test to fail (TL-16830). So until TL-16787 is fixed,
        // this class has a split personality: for screen rendering, it defers
        // to the legacy display function, otherwise the new string rendering is
        // used.
        //
        // Once TL-16787 is finally done, the following needs to be done:
        // - get rid of the legacy display function invocation in this class.
        // - add a debugging() notice in the legacy display function
        // - change what is checked in cohort_associations_report_source.feature
        //   for the due date display
        // - remove this TODO comment

        if ($format === 'html') {
            $extrafields = self::get_extrafields_row($row, $column);
            $programid = empty($extrafields->programid) ? null : $extrafields->programid;
            return $report->src->rb_display_programcompletionlink($programid, $extrafields);
        }

        return self::display_correct($value, $format, $row, $column, $report);
    }

    private static function display_correct($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $extrafields = self::get_extrafields_row($row, $column);

        $type = empty($extrafields->type) ? 0 : $extrafields->type;
        $programtypes = [COHORT_ASSN_ITEMTYPE_PROGRAM , COHORT_ASSN_ITEMTYPE_CERTIF];
        if (!in_array($type, $programtypes)) {
            return \get_string('na', 'totara_cohort');
        }

        $programid = empty($extrafields->programid) ? null : $extrafields->programid;
        $item = [
            "completiontime" => $value,
            "completionevent" => empty($extrafields->completionevent) ? null : $extrafields->completionevent,
            "completioninstance" => empty($extrafields->completioninstance) ? null : $extrafields->completioninstance,
            "id" => empty($extrafields->cohortid) ? null : $extrafields->cohortid
        ];

        $cat = new \cohorts_category();
        $text = $cat->get_completion((object)$item, $programid, false);
        return $format === 'html' ? $text : static::to_plaintext($text);
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
