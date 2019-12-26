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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_core
 */

namespace totara_program\progress;

defined('MOODLE_INTERNAL') || die();


/**
 * Default aggregation implementation to be used with AGGREGATE_ALL
 */
final class progressinfo_aggregate_some implements \totara_core\progressinfo\progressinfo_aggregation {

    /**
     * Recursive depth first function to aggregate this node's score and weight
     * when ALL criteria must be completed
     *     - aggregated weight = sum (individual weights)
     *     - aggregated score = sum (individual scores)
     * @param progressinfo $progressinfo Progress information to aggregate
     * @return array $results Associative array containing the aggregated weight and score
     */
    public static function aggregate(\totara_core\progressinfo\progressinfo $progressinfo) {

        // For now weight is always 1
        $weight = 1;
        $score = 0;

        $progresscustomdata = $progressinfo->get_customdata();
        $requiredcourses = isset($progresscustomdata['requiredcourses']) ? $progresscustomdata['requiredcourses'] : 0;
        $requiredpoints = isset($progresscustomdata['requiredpoints']) ? $progresscustomdata['requiredpoints'] : 0;
        $totalcourses = 0;
        $totalpoints = 0;

        if ($requiredcourses == 0 && $requiredpoints == 0) {
            // Nothing really required
            $score = 1; // 100% complete
            return array('weight' => $weight, 'score' => $score);
        }

        /* For minimum courses :
             Progress is the average of the N most complete courses where N is the number of courses that
             must be completed.
             E.g. complete at least 3 of the following courses:
                Course1(0%), Course2(5%), Course3(50%), Course4(75%), Course5(100%)
             the progress is (0.5 + 0.75 + 1) / 3 = 75%
           For minimum score:
             First calculate the score already achieved by adding the scores of all completed courses ('already achieved')
             If not enough, calculate the weighted progress of the remaining (uncompleted) courses towards obtaining
             the outstanding score and use the N most completed that will provide the outstanding score.
             Convert the average of these N to points ('in progress')
             Combine the 'already achieved' score with 'in progress' score to obtain overall progress
             E.g. Complete some courses to reach 75 points from
                Course1 (0%, 10pts), Course2 (50%, 5pts), Course3 (75%, 20pts), Course4 (15%, 50pts), Course5(100%, 40pts),
             Already achieved = 40pts (from Course5 that is completed)
             Outstanding = 35pts
             Weighted progress towards obtaining the outstanding points:
                Course1 : 0 * 10/35 = 0
                Course2 : 0.5 * 5/35 = 0.07
                Course3: 0.75 * 20/35 = 0.42
                Course4 : 0.15 * 35/35 = 0.15   (50pts is more than the required 35 - so would fill whole requirement)
             Sorted by weighted progress = Course3, Course4, Course2, Course1
             Use the first N scores in the sorted weighted progress that will give the outstanding score if completed = 2 (Course3 gives 20 points and
             Course4 gives 50 points) which gives us ((0.42 + 0.15) / 2) * 35pts = 9.97 pts.
             Thus - total progress in points = (40 + 9.97) / 75 = 66%

           If both the minimum courses and minimum score is required, overall progress is the minimum of the two
        */

        $topcourses = array();
        $achievedpoints = 0;

        // First iteration
        //   - get array of course progress and sort it
        //   - get achieved points
        $coursecriteria = $progressinfo->get_all_criteria();
        foreach ($coursecriteria as $courseinfo) {
            // Using percentagecomplete instead of score to take course weight
            $coursescore = $courseinfo->get_score();

            // Get sorted array of course progress
            if ($requiredcourses > 0) {
                for ($i = 0; $i < count($topcourses); $i++) {
                    if ($coursescore > $topcourses[$i]) {
                        array_splice($topcourses, $i, 0, $coursescore);
                        break;
                    }
                }
                if ($i == count($topcourses)) {
                    $topcourses[] = $coursescore;
                }
            }

            // Calculate achievedscore
            if ($requiredpoints > 0 && $coursescore == 1) {
                $coursedata = $courseinfo->get_customdata();
                $achievedpoints += $coursedata['coursepoints'];
            }
        }

        // Now we can use the N most completed courses to calculate progress towards minimum number of courses
        if ($requiredcourses > 0) {
            array_splice($topcourses, $requiredcourses);
            $totalcourses = array_sum($topcourses);
        }

        // More work to be done for minimum score
        if ($requiredpoints > 0) {
            if ($achievedpoints >= $requiredpoints) {
                // All done
                $totalpoints = $requiredpoints;
            }
            else {
                // We can now determine the outstandingpoints and progress towards achieving this
                $outstandingpoints = $requiredpoints - $achievedpoints;
                $toppoints = array();

                // We need to iterate the courses again to get the weighted progress of uncompleted courses
                foreach ($progressinfo->get_all_criteria() as $courseinfo) {
                    $coursescore = $courseinfo->get_score();
                    if ($coursescore != 1) {
                        // Only using uncompleted courses here
                        $coursedata = $courseinfo->get_customdata();
                        $coursepoints = $coursedata['coursepoints'] > $outstandingpoints
                            ? $outstandingpoints
                            : $coursedata['coursepoints'];

                        // If 2 courses have the same weighted progress towards achieving the outstanding
                        // points, we prefer the course which will give the most points
                        $weightedprogress = $coursescore * ($coursepoints / $outstandingpoints);
                        for ($i = 0; $i < count($toppoints); $i++) {
                            if ($weightedprogress > $toppoints[$i]['progress'] ||
                                ($weightedprogress == $toppoints[$i]['progress'] &&
                                 $coursepoints > $toppoints[$i]['points'])) {
                                array_splice($toppoints, $i, 0,
                                    array(array('progress' => $weightedprogress, 'points' => $coursepoints)));
                                break;
                            }
                        }
                        if ($i == count($toppoints)) {
                            $toppoints[] = array('progress' => $weightedprogress, 'points' => $coursepoints);
                        }
                    }
                }

                // Now we use the top N weighted progress to calculate progress towards obtaining the outstanding points
                // N here are the number of courses that will get us to the outstanding points
                $points = $toppoints[0]['points'];
                $progress = $toppoints[0]['progress'];
                for ($N = 1; $points < $outstandingpoints && $N < count($toppoints); $N++) {
                    $points += $toppoints[$N]['points'];
                    $progress += $toppoints[$N]['progress'];
                }

                // Get the average weighted progress of the top N and convert that into 'in progress' points
                $progresspoints = ($progress / $N) * $outstandingpoints;

                // Overall progress towards achieving the required score
                $totalpoints = $achievedpoints + $progresspoints;
            }
        }

        // Update progressinfo data and return score and weight
        $progresscustomdata['totalcourses'] = $totalcourses;
        $progresscustomdata['totalpoints'] = $totalpoints;
        $progressinfo->set_customdata($progresscustomdata);

        // If user must achieve both minimum number of courses and minimum score,
        // overall score is the minimum of the two
        if ($requiredcourses > 0 && $requiredpoints > 0) {
            $score = min($totalcourses / $requiredcourses, $totalpoints / $requiredpoints);
        } else if ($requiredcourses > 0) {
            $score = ($totalcourses / $requiredcourses);
        } else if ($requiredpoints > 0) {
            $score = ($totalpoints / $requiredpoints);
        } else {
            $score = 1;
        }

        return array('weight' => $weight, 'score' => $score);
    }
}
