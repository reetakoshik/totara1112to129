<?php
/**
 * This file is part of Totara LMS

 * Copyright (C) 2010 onwards Totara Learning Solutions LTD

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.

 * @author Brian Barnes <brian.barnes@totaralms.com
 * @package core_user
 */

namespace core_user\output;
use html_writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines a User when viewing participant details in a course.
 *
 * @package   core_user
 * @copyright 2015 onwards Totara LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participant_details implements \renderable {

    /** @var integer The id of the user to display */
    public $id;

    /** @var string The full name of the user to display */
    public $name;

    /** @var array An array of array's containing a 'url' and 'text' for links to display */
    public $links;

    /** @var bool Whether the current user can perform bulk actions (e.g send a message) */
    public $bulkoperations;

    /** @var string an html image tag containing the users profile image */
    public $image;

    /** @var array An array of array's containing the name of the information and the information itself */
    public $info;

    /** @var string The users e-mail address */
    public $email;

    /** @var array Contains the label for address and the users location */
    public $location;

    /** @var array The users idnumber */
    public $idnumber;

    /** @var array The users primary phone */
    public $phone;

    /** @var array The users second phone */
    public $phone2;

    /** @var array The users department */
    public $department;

    /** @var array The users institution */
    public $institution;

    /** @var string The time this user last accessed the site. */
    public $lastaccessed;

    /** @var string A label describing bulk actions. */
    public $bulklabel;



    /*
     * Loads a user object with data that is required by the mustache template
     *
     * @param $user \stdClass A user database record
     * @param $course \stdClass A course database record
     * @param $extrafields array Contains the extra fields as defined by get_extra_user_fields($context)
     * @param $bulkoperations bool Whether bulk operations are permitted for the current user in the current context
     *
     * @returns HTML as defined by the course_participants mustache template
     */
    public static function output_from_user($user, $course, $extrafields, $bulkoperations, $selectall = false) {
        global $CFG, $USER, $OUTPUT;

        \context_helper::preload_from_record($user);

        $context = \context_course::instance($course->id);
        $usercontext = \context_user::instance($user->id);

        $countries = get_string_manager()->get_list_of_countries();

        $datestring = new \stdClass();
        $datestring->year  = get_string('year');
        $datestring->years = get_string('years');
        $datestring->day   = get_string('day');
        $datestring->days  = get_string('days');
        $datestring->hour  = get_string('hour');
        $datestring->hours = get_string('hours');
        $datestring->min   = get_string('min');
        $datestring->mins  = get_string('mins');
        $datestring->sec   = get_string('sec');
        $datestring->secs  = get_string('secs');

        $userdata = new participant_details();
        $userdata->id = $user->id;
        $userdata->image = $OUTPUT->user_picture($user, array('size' => 100, 'courseid' => $course->id));
        $userdata->name = fullname($user, has_capability('moodle/site:viewfullnames', $context));
        $userdata->selectall = (bool)$selectall;

        // Get the hidden field list.
        if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
            $hiddenfields = array();
        } else {
            $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
        }

        $userdata->info = array();

        if (!empty($user->role)) {
            $userdata->info[] = array(
                'key' => get_string('role').get_string('labelsep', 'langconfig'),
                'value' => $user->role
            );
        }

        if ($user->maildisplay == 1 or ($user->maildisplay == 2 and ($course->id != SITEID) and !isguestuser()) or
                    has_capability('moodle/course:viewhiddenuserfields', $context) or
                    in_array('email', $extrafields) or ($user->id == $USER->id)) {
            $userdata->email = array(
                'label' => get_string('email') . get_string('labelsep', 'langconfig'),
                'link' => html_writer::link("mailto:$user->email", $user->email)
            );
        }

        foreach ($extrafields as $field) {
            switch ($field) {
                case 'email':
                    // Skip email because it was displayed with different logic above
                    // because this page is intended for students too.
                    break;

                case 'idnumber':
                    $userdata->idnumber = array(
                        'label' => get_user_field_name($field) . get_string('labelsep', 'langconfig'),
                        'number' => s($user->{$field})
                    );
                    break;

                case 'phone':
                    $userdata->phone = array(
                        'label' => get_user_field_name($field) . get_string('labelsep', 'langconfig'),
                        'number' => s($user->{$field})
                    );
                    break;

                case 'phone2':
                    $userdata->phone2 = array(
                        'label' => get_user_field_name($field) . get_string('labelsep', 'langconfig'),
                        'number' => s($user->{$field})
                    );
                    break;

                case 'department':
                    $userdata->department = array(
                        'label' => get_user_field_name($field) . get_string('labelsep', 'langconfig'),
                        'value' => s($user->{$field})
                    );
                    break;

                case 'institution':
                    $userdata->institution = array(
                        'label' => get_user_field_name($field) . get_string('labelsep', 'langconfig'),
                        'value' => s($user->{$field})
                    );
                    break;

                default:
                    $userdata->info[] = array(
                        'label' => get_user_field_name($field) . get_string('labelsep', 'langconfig'),
                        'value' => s($user->{$field})
                    );
                    break;
            }
        }

        if (($user->city or $user->country) and (!isset($hiddenfields['city']) or !isset($hiddenfields['country']))) {
            $location = array(
                'label' => get_string('city') . get_string('labelsep', 'langconfig'),
                'location' => ''
            );
            if ($user->city && !isset($hiddenfields['city'])) {
                $location['location'] .= $user->city;
            }
            if (!empty($countries[$user->country]) && !isset($hiddenfields['country'])) {
                if ($user->city && !isset($hiddenfields['city'])) {
                    $location['location'] .= ', ';
                }
                $location['location'] .= $countries[$user->country];
            }
            $userdata->location = $location;
        }

        if (!isset($hiddenfields['lastaccess'])) {
            $lastaccess = array(
                'label' => get_string('lastaccess') . get_string('labelsep', 'langconfig')
            );
            if ($user->lastaccess) {
                $lastaccess['time'] = userdate($user->lastaccess) . ' ('. format_time(time() - $user->lastaccess, $datestring) .')';
            } else {
                $lastaccess['time'] = get_string('never');
            }
            $userdata->lastaccessed = $lastaccess;
        }

        $links = array();

        if ($CFG->enableblogs && ($CFG->bloglevel != BLOG_USER_LEVEL || $USER->id == $user->id)) {
            $links[] = array(
                'url' => new \moodle_url('/blog/index.php?userid='.$user->id),
                'text' => get_string('blogs', 'blog')
            );
        }

        if (!empty($CFG->enablenotes) and (has_capability('moodle/notes:manage', $context) || has_capability('moodle/notes:view', $context))) {
            $links[] = array(
                'url' => new \moodle_url('/notes/index.php?course=' . $course->id. '&user='.$user->id),
                'text' => get_string('notes', 'notes')
            );
        }

        if (has_capability('moodle/site:viewreports', $context) or has_capability('moodle/user:viewuseractivitiesreport', $usercontext)) {
            $links[] = array(
                'url' => new \moodle_url('/course/user.php?id='. $course->id .'&user='. $user->id),
                'text' => get_string('activity')
            );
        }

        if ($USER->id != $user->id && !\core\session\manager::is_loggedinas() && has_capability('moodle/user:loginas', $context) && !is_siteadmin($user->id)) {
            $links[] = array(
                'url' => new \moodle_url('/course/loginas.php?id='. $course->id .'&user='. $user->id .'&sesskey='. sesskey()),
                'text' => get_string('loginas')
            );
        }

        $links[] = array(
            'url' => new \moodle_url('/user/view.php?id='. $user->id .'&course='. $course->id),
            'text' => get_string('fullprofile') . '...'
        );
        $userdata->links = $links;
        $userdata->bulkoperations = $bulkoperations;
        $userdata->bulklabel = get_string('select', 'grades', $userdata->name);

        return $OUTPUT->render_from_template('user/course_participants', $userdata);
    }
}
