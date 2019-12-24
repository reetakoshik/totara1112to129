<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @package modules
 * @subpackage facetoface
 */

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_renderer extends plugin_renderer_base {
    protected $context = null;

    /**
     * Outputs a table showing a list of sessions along with other information.
     *
     * Note some aspects of the API used here that may not be obvious:
     *
     * It's assumed that these sessions are all from the same facetoface activity.
     *
     * *** If a session has been booked by the user ***
     * Todo: We need a more intuitive api for this part.
     * If a session has been booked, then that one session should have a bookedsession property
     * containing that information. An appropriate object for this property would be the
     * first result from facetoface_get_user_submissions, obtained via array_shift.
     *
     * If 'multiple signups' is not allowed for the facetoface activity, then if a session has been booked, it should
     * be added to the bookedsession property of each session. It'll display the right data for each session
     * by checking for whether the id of booked session matches that of the session row being processed.
     *
     * @param array $sessions - array of session objects.
     * @param bool $viewattendees - true if the current user has this capability ('mod/facetoface:viewattendees').
     * @param bool $editevents - true if the current user has this capability ('mod/facetoface:editevents').
     * @param bool $displaytimezones - true if the timezones should be displayed.
     * @param array $reserveinfo - if managereserve if set to true for the facetoface, use facetoface_can_reserve_or_allocate
     * to fill out this array.
     * @param string $currenturl - generally this would be $PAGE->url.
     * @param bool $minimal - setting this to true will not show the customfields and will show the registration dates
     * in a tooltip when hovering over the signup link rather than in a column.
     * @param bool $returntoallsessions Returns the user to view all sessions after they signup/cancel.
     * @return string containing html for this table.
     * @throws coding_exception
     */
    public function print_session_list_table($sessions, $viewattendees, $editevents, $displaytimezones, $reserveinfo = array(),
                                             $currenturl=null, $minimal = false, $returntoallsessions = true) {
        $output = '';

        if (empty($sessions)) {
            // If there's no sessions, just return an empty string.
            return '';
        }

        $tableheader = array();

        // If we want the minimal table, no customfield columns are shown.
        if (!$minimal) {
            $customfields = customfield_get_fields_definition('facetoface_session', array('hidden' => 0));
            foreach ($customfields as $customfield) {
                if (!empty($customfield->showinsummary)) {
                    $tableheader[] = format_string($customfield->fullname);
                }
            }
        }

        $tableheader[] = get_string('date', 'facetoface');
        if (!empty($displaytimezones)) {
            $tableheader[] = get_string('timeandtimezone', 'facetoface');
        } else {
            $tableheader[] = get_string('time', 'facetoface');
        }
        $tableheader[] = get_string('room', 'facetoface');
        if ($viewattendees) {
            $tableheader[] = get_string('capacity', 'facetoface');
        } else {
            $tableheader[] = get_string('seatsavailable', 'facetoface');
        }
        $tableheader[] = get_string('status', 'facetoface');

        // If we want the minimal table, the registration dates are shown in a tooltip instead of a column.
        if (!$minimal) {
            $tableheader[] = get_string('signupperiodheader', 'facetoface');
        }

        $tableheader[] = get_string('options', 'facetoface');

        $table = new html_table();
        $table->summary = get_string('previoussessionslist', 'facetoface');
        $table->attributes['class'] = 'generaltable fullwidth';
        $table->head = $tableheader;
        $table->data = array();

        foreach ($sessions as $session) {

            $isbookedsession = (!empty($session->bookedsession) && ($session->id == $session->bookedsession->sessionid));
            $sessionstarted = facetoface_has_session_started($session, time());

            $comp = '>='; // SQL comparison operator.
            if ($session->cancelledstatus) {
                $status = MDL_F2F_STATUS_SESSION_CANCELLED;
                $comp = '=';
            } else if (!empty($session->sessiondates)) {
                $status = MDL_F2F_STATUS_BOOKED;
            } else {
                $status = MDL_F2F_STATUS_WAITLISTED;
                $comp = '=';
            }
            $signupcount = facetoface_get_num_attendees($session->id, $status, $comp);
            $sessionfull = ($signupcount >= $session->capacity);

            $rooms = facetoface_get_session_rooms($session->id);

            if (empty($session->sessiondates)) {
                // An event without session dates, is a wait-listed event
                $sessionrow = array();

                if (!$minimal) {
                    $sessionrow = array_merge($sessionrow, $this->session_customfield_table_cells($session, $customfields));
                }

                // For the date and time columns.
                $sessionrow[] = get_string('wait-listed', 'facetoface');
                $sessionrow[] = get_string('wait-listed', 'facetoface');

                // For the room column.
                $sessionrow[] = '';

                $sessionrow[] = $this->session_capacity_table_cell($session, $viewattendees, $signupcount);
                $sessionrow[] = $this->session_status_table_cell($session, $signupcount);

                if (!$minimal) {
                    $sessionrow[] = $this->session_resgistrationperiod_table_cell($session);
                }
                $reservelink = $this->session_options_reserve_link($session, $signupcount, $reserveinfo);
                $signuplink = $this->session_options_signup_link($session, $sessionstarted, $minimal, $returntoallsessions, $displaytimezones);
                $sessionrow[] = $this->session_options_table_cell($session, $viewattendees, $editevents, $reservelink, $signuplink);

                $row = new html_table_row($sessionrow);

                // Set the CSS class for the row.
                if ($sessionstarted || !empty($session->cancelledstatus)) {
                    $row->attributes = array('class' => 'dimmed_text');
                } else if ($isbookedsession) {
                    $row->attributes = array('class' => 'highlight');
                } else if ($sessionfull && $session->allowoverbook == '0') {
                    $row->attributes = array('class' => 'dimmed_text');
                }

                // Add row to table.
                $table->data[] = $row;

            } else {
                // If there are session dates, we create one row per session date, but some will be
                // given a rowspan value as they apply to the whole session rather than just the session date.
                $datescount = count($session->sessiondates);
                $firstsessiondate = true;
                foreach($session->sessiondates as $date) {
                    $sessionrow = array();
                    if ($firstsessiondate && !$minimal) {
                        $sessionrow = array_merge($sessionrow, $this->session_customfield_table_cells($session, $customfields, $datescount));
                    }

                    $sessionobj = facetoface_format_session_times($date->timestart, $date->timefinish, $date->sessiontimezone);
                    if ($sessionobj->startdate == $sessionobj->enddate) {
                        $sessionrow[] = $sessionobj->startdate;
                    } else {
                        $sessionrow[] = $sessionobj->startdate . ' - ' . $sessionobj->enddate;
                    }
                    $sessiontimezonetext = !empty($displaytimezones) ? $sessionobj->timezone : '';
                    $sessionrow[] = $sessionobj->starttime . ' - ' . $sessionobj->endtime . ' ' . $sessiontimezonetext;

                    if (!empty($date->roomid) && isset($rooms[$date->roomid])) {
                        $room = $rooms[$date->roomid];
                        $sessionrow[] = facetoface_room_html($room, $currenturl);
                    } else {
                        $sessionrow[] = '';
                    }

                    if ($firstsessiondate) {
                        $sessionrow[] = $this->session_capacity_table_cell($session, $viewattendees, $signupcount, $datescount);
                        $sessionrow[] = $this->session_status_table_cell($session, $signupcount, $datescount);
                        if (!$minimal) {
                            $sessionrow[] = $this->session_resgistrationperiod_table_cell($session, $datescount);
                        }
                        $reservelink = $this->session_options_reserve_link($session, $signupcount, $reserveinfo);
                        $signuplink = $this->session_options_signup_link($session, $sessionstarted, $minimal, $returntoallsessions, $displaytimezones);
                        $sessionrow[] = $this->session_options_table_cell($session, $viewattendees, $editevents, $reservelink, $signuplink, $datescount);
                    }

                    // $firsessiondate should only be true on the iteration of this foreach loop.
                    $firstsessiondate = false;

                    $row = new html_table_row($sessionrow);

                    // Set the CSS class for the row.
                    if ($sessionstarted || !empty($session->cancelledstatus)) {
                        $row->attributes = array('class' => 'dimmed_text');
                    } else if ($isbookedsession) {
                        $row->attributes = array('class' => 'highlight');
                    } else if ($sessionfull && $session->allowoverbook == '0') {
                        $row->attributes = array('class' => 'dimmed_text');
                    }

                    // Add row to table.
                    $table->data[] = $row;
                }
            }
        }

        if (empty($table->data)) {
            // There were sessions when we checked at the beginning, but they've been eliminated
            // for one reason or another, so just return an empty string.
            return '';
        }

        $output .= $this->render($table);

        return $output;
    }

    /**
     * Add a table cells for each customfield value associated with a session.
     *
     * @param stdClass $session
     * @param array $customfields - as returned by facetoface_get_session_customfields().
     * @param int $datescount - this determines the rowspan. Count the number of session dates to get this figure.
     * @return array of table cells to be merged with an array for the rest of the cells.
     */
    private function session_customfield_table_cells($session, $customfields, $datescount = 0) {

        $customfieldsdata = customfield_get_data($session, 'facetoface_session', 'facetofacesession', false);
        $sessionrow = array();

        foreach ($customfields as $customfield) {
            if (empty($customfield->showinsummary)) {
                continue;
            }
            if (array_key_exists($customfield->shortname, $customfieldsdata)) {
                $cell = new html_table_cell($customfieldsdata[$customfield->shortname]);
                if ($datescount > 1) {
                    $cell->rowspan = $datescount;
                }
                $sessionrow[] = $cell;
            } else {
                $cell = new html_table_cell('&nbsp;');
                if ($datescount > 1) {
                    $cell->rowspan = $datescount;
                }
                $sessionrow[] = $cell;
            }
        }

        return $sessionrow;
    }

    /**
     * Create a table cell containing a sessions capacity or seats remaining.
     *
     * If the user has viewattendees permissions, this will show capacity.
     * If not, then this will show seats remanining.
     *
     * @param stdClass $session - An event, not session date
     * @param bool $viewattendees - true if they do have permissions.
     * @param int $signupcount - number currently signed up to this session.
     * @param int $datescount - this determines the rowspan. Count the number of session dates to get this figure.
     * @return html_table_cell
     * @throws coding_exception
     */
    private function session_capacity_table_cell($session, $viewattendees, $signupcount, $datescount = 0) {
        if ($viewattendees) {
            if (!empty($session->sessiondates)) {
                $a = array('current' => $signupcount, 'maximum' => $session->capacity);
                $stats = get_string('capacitycurrentofmaximum', 'facetoface', $a);
                if ($signupcount > $session->capacity) {
                    $stats .= get_string('capacityoverbooked', 'facetoface');
                }
                $waitlisted = facetoface_get_num_attendees($session->id, MDL_F2F_STATUS_WAITLISTED) - $signupcount;
                if ($waitlisted > 0) {
                    $stats .= " (" . $waitlisted . " " . get_string('status_waitlisted', 'facetoface') . ")";
                }
            } else {

                // Since within the event that has no sesison date, and user that are in wait-list could be moved to
                // attendees, and it caused the number of wait-listed user being calculated and rendered wrong.
                // If there is any user that confirm as booked, then it should display the number of booked user within current
                $currentbookeduser = (int) facetoface_get_num_attendees($session->id, MDL_F2F_STATUS_BOOKED, "=");
                $a = array('current' => $currentbookeduser, 'maximum' => $session->capacity);
                $stats = get_string('capacitycurrentofmaximum', 'facetoface', $a);
                if ($currentbookeduser > $session->capacity) {
                    $stats .= get_string('capacityoverbooked', 'facetoface');
                }
                $stats .= " (" . $signupcount . " " . get_string('status_waitlisted', 'facetoface') . ")";
            }
        } else {
            $stats = max(0, $session->capacity - $signupcount);
        }

        $sessioncell = new html_table_cell($stats);
        if ($datescount > 1) {
            $sessioncell->rowspan = $datescount;
        }

        return $sessioncell;
    }

    /**
     * Create a table cell containing the status of a session.
     *
     * Examples would include 'In progress' or 'Booking open'.
     *
     * @param stdClass $session
     * @param int $signupcount - number currently signed up to this session.
     * @param int $datescount - this determines the rowspan. Count the number of session dates to get this figure.
     * @return html_table_cell
     * @throws coding_exception
     */
    private function session_status_table_cell($session, $signupcount, $datescount = 0) {
        global $CFG;

        $isbookedsession = (!empty($session->bookedsession) && ($session->id == $session->bookedsession->sessionid));
        $timenow = time();

        $status  = get_string('bookingopen', 'facetoface');
        if (!empty($session->cancelledstatus)) {
                $status = get_string('bookingsessioncancelled', 'facetoface');
        } else if (!empty($session->sessiondates) && facetoface_has_session_started($session, $timenow) && facetoface_is_session_in_progress($session, $timenow)) {
            $status = get_string('sessioninprogress', 'facetoface');
        } else if (!empty($session->sessiondates) && facetoface_has_session_started($session, $timenow)) {
            $status = get_string('sessionover', 'facetoface');
        } else if ($isbookedsession) {
            $signupstatus = facetoface_get_status($session->bookedsession->statuscode);
            $status = get_string('status_'.$signupstatus, 'facetoface');
        } else if ($signupcount >= $session->capacity) {
            $status = get_string('bookingfull', 'facetoface');
        } else if (!empty($session->registrationtimestart) && $session->registrationtimestart > $timenow) {
            $status = get_string('registrationnotopen', 'facetoface');
        } else if (!empty($session->registrationtimefinish) && $timenow > $session->registrationtimefinish) {
            $status = get_string('registrationclosed', 'facetoface');
        }

        if ($CFG->enableavailability) {
            $cm = get_coursemodule_from_instance('facetoface', $session->facetoface);

            if (!get_fast_modinfo($cm->course)->get_cm($cm->id)->available) {
                $status = get_string('bookingrestricted', 'facetoface');
            }
        }

        $sessioncell = new html_table_cell($status);
        if ($datescount > 1) {
            $sessioncell->rowspan = $datescount;
        }

        return $sessioncell;
    }

    /**
     * Creates a table cell containing the registration period, if any, for a session.
     *
     * @param stdClass $session
     * @param int $datescount - determines the number for the rowspan.
     * @return html_table_cell
     * @throws coding_exception
     */
    private function session_resgistrationperiod_table_cell($session, $datescount = 0) {
        // Signup Start Dates/times.
        if(!empty($session->registrationtimestart)) {
            if(!empty($session->registrationtimefinish)) {
                $sessionobj = facetoface_format_session_times($session->registrationtimestart, $session->registrationtimefinish, '');
                $registrationstring = get_string('signupstartend', 'facetoface', $sessionobj);
            } else {
                $start = new stdClass();
                $start->startdate = userdate($session->registrationtimestart, get_string('strftimedate', 'langconfig'));
                $start->starttime = userdate($session->registrationtimestart, get_string('strftimetime', 'langconfig'));
                $start->timezone = core_date::get_user_timezone();
                $registrationstring = get_string('signupstartsonly', 'facetoface', $start);
            }
        } else {
            if(!empty($session->registrationtimefinish)) {
                $finish = new stdClass();
                $finish->enddate = userdate($session->registrationtimefinish, get_string('strftimedate', 'langconfig'));
                $finish->endtime = userdate($session->registrationtimefinish, get_string('strftimetime', 'langconfig'));
                $finish->timezone = core_date::get_user_timezone();
                $registrationstring = get_string('signupendsonly', 'facetoface', $finish);
            } else {
                $registrationstring = "";
            }
        }

        $sessioncell = new html_table_cell($registrationstring);
        if ($datescount > 1) {
            $sessioncell->rowspan = $datescount;
        }

        return $sessioncell;
    }

    /**
     * Creates a table cell for the options available for a session.
     *
     * @param stdClass $session
     * @param bool $viewattendees - true if the user has this permission.
     * @param bool $editevents - true if the user has this permission.
     * @param string $reservelink - html generated with the method session_options_reserve_link().
     * @param string $signuplink - html generated with the method session_options_signup_link().
     * @param int $datescount - determines the number for the rowspan.
     * @return html_table_cell
     * @throws coding_exception
     */
    private function session_options_table_cell($session, $viewattendees, $editevents, $reservelink, $signuplink, $datescount = 0) {

        global $CFG;

        $options = '';
        $timenow = time();

        // NOTE: This is not a nice hack, we can only guess where to return because there is no argument above.
        $bas = 0;
        if ($this->page->url->compare(new moodle_url('/mod/facetoface/view.php'), URL_MATCH_BASE)) {
            $bas = 1;
        }

        // Can edit sessions.
        if ($editevents) {
            if ($session->cancelledstatus == 0) {
                $options .= $this->output->action_icon(new moodle_url('/mod/facetoface/events/edit.php', array('s' => $session->id, 'backtoallsessions' => $bas)), new pix_icon('t/edit', get_string('editsession', 'facetoface'))) . ' ';
                if (!facetoface_has_session_started($session, $timenow)) {
                    $options .= $this->output->action_icon(new moodle_url('/mod/facetoface/events/cancel.php', array('s' => $session->id, 'backtoallsessions' => $bas)), new pix_icon('t/block', get_string('cancelsession', 'facetoface'))) . ' ';
                }
            }
            $options .= $this->output->action_icon(new moodle_url('/mod/facetoface/events/edit.php', array('s' => $session->id, 'c' => 1, 'backtoallsessions' => $bas)), new pix_icon('t/copy', get_string('copysession', 'facetoface'))) . ' ';
            $options .= $this->output->action_icon(new moodle_url('/mod/facetoface/events/delete.php', array('s' => $session->id, 'backtoallsessions' => $bas)), new pix_icon('t/delete', get_string('deletesession', 'facetoface'))) . ' ';
            $options .= html_writer::empty_tag('br');
        }

        // Can view attendees.
        if ($viewattendees) {
            $options .= html_writer::link(new moodle_url('/mod/facetoface/attendees.php', array('s' => $session->id, 'backtoallsessions' => $bas)), get_string('attendees', 'facetoface'), array('title' => get_string('seeattendees', 'facetoface')));
            $options .= html_writer::empty_tag('br');
        }

        if (!empty($reservelink)) {
            $options .= $reservelink;
        }

        $showsignuplink = true;

        if (!enrol_is_enabled('totara_facetoface') || $CFG->enableavailability) {
            $cm = get_coursemodule_from_instance('facetoface', $session->facetoface);
            $modinfo = get_fast_modinfo($cm->course);
            $cm = $modinfo->get_cm($cm->id);

            // If Seminar enrolment plugin is not enabled check visibility of the activity.
            if (!enrol_is_enabled('totara_facetoface')) {
                // Check visibility of activity (includes visible flag, conditional availability, etc) before adding Sign up link.
                $showsignuplink = $cm->uservisible;
            }

            if ($CFG->enableavailability) {
                // Check whether this activity is available for the user. However if it's available, but not visible
                // for some reason we're still not displaying a link.
                $showsignuplink &= $cm->available;
            }
        }

        if (!empty($signuplink) && $showsignuplink) {
            $options .= $signuplink;
        }

        if (empty($options)) {
            $options = get_string('none', 'facetoface');
        }

        $sessioncell = new html_table_cell($options);
        if ($datescount > 1) {
            $sessioncell->rowspan = $datescount;
        }

        return $sessioncell;
    }

    /**
     * Returns the text containing registration start and end dates if there are any.
     *
     * @param stdClass $session
     * @return string to add to the tooltip and aria-label attributes of an html link.
     * @throws coding_exception
     */
    private function get_regdates_tooltip_info($session, $displaytimezones) {
        $tooltip = array();
        if (!empty($session->registrationtimestart)) {
            $start = new stdClass();
            $start->startdate = userdate($session->registrationtimestart, get_string('strftimedate', 'langconfig'));
            $start->starttime = userdate($session->registrationtimestart, get_string('strftimetime', 'langconfig'));
            if ($displaytimezones) {
                $start->timezone = core_date::get_user_timezone();
                $tooltip[] = get_string('registrationhoverhintstarttz', 'facetoface', $start);
            } else {
                $tooltip[] = get_string('registrationhoverhintstart', 'facetoface', $start);
            }
        }
        if (!empty($session->registrationtimefinish)) {
            $finish = new stdClass();
            $finish->enddate = userdate($session->registrationtimefinish, get_string('strftimedate', 'langconfig'));
            $finish->endtime = userdate($session->registrationtimefinish, get_string('strftimetime', 'langconfig'));
            if ($displaytimezones) {
                $finish->timezone = core_date::get_user_timezone();
                $tooltip[] = get_string('registrationhoverhintendtz', 'facetoface', $finish);
            } else {
                $tooltip[] = get_string('registrationhoverhintend', 'facetoface', $finish);
            }
        }

        return implode("\n", $tooltip);
    }

    /**
     * Create the html for a reserve spaces link in the session list table.
     * This needs to be inserted into a table cell. E.g. add it to the options table cell.
     *
     * @param stdClass $session
     * @param int $signupcount - number currently signed up to this session.
     * @param array $reserveinfo - if managereserve if set to true for the facetoface, use facetoface_can_reserve_or_allocate
     * to fill out this array.
     * @return string
     * @throws coding_exception
     */
    private function session_options_reserve_link($session, $signupcount, $reserveinfo = array()) {

        $reservelink = '';
        if (!empty($session->cancelledstatus)) {
            return $reservelink;
        }

        $currentime = time();
        if (isset($session->sessiondates)
            && facetoface_has_session_started($session, $currentime)
                || facetoface_is_session_over($session, $currentime)) {
            return $reservelink;
        }

        // Output links to reserve/allocate spaces.
        if (!empty($reserveinfo)) {
            $sessreserveinfo = $reserveinfo;
            if (!$session->allowoverbook) {
                $sessreserveinfo = facetoface_limit_reserveinfo_to_capacity_left($session->id, $sessreserveinfo,
                    max(0, $session->capacity - $signupcount));
            }
            $sessreserveinfo = facetoface_limit_reserveinfo_by_session_date($sessreserveinfo, $session);
            if (!empty($sessreserveinfo['allocate']) && $sessreserveinfo['maxallocate'][$session->id] > 0) {
                // Able to allocate and not used all allocations for other sessions.
                $allocateurl = new moodle_url('/mod/facetoface/reserve.php', array('action' => 'allocate', 's' => $session->id,
                    'backtoallsessions' => 1));
                $reservelink .= html_writer::link($allocateurl, get_string('allocate', 'mod_facetoface'));
                $reservelink .= ' ('.$sessreserveinfo['allocated'][$session->id].'/'.$sessreserveinfo['maxallocate'][$session->id].')';
                $reservelink .= html_writer::empty_tag('br');
            }
            if (!empty($sessreserveinfo['reserve']) && $sessreserveinfo['maxreserve'][$session->id] > 0) {
                if (empty($sessreserveinfo['reservepastdeadline'])) {
                    $reserveurl = new moodle_url('/mod/facetoface/reserve.php', array('action' => 'reserve', 's' => $session->id,
                        'backtoallsessions' => 1));
                    $reservelink .= html_writer::link($reserveurl, get_string('reserve', 'mod_facetoface'));
                    $reservelink .= ' ('.$sessreserveinfo['reserved'][$session->id].'/'.$sessreserveinfo['maxreserve'][$session->id].')';
                    $reservelink .= html_writer::empty_tag('br');
                }
            } else if (!empty($sessreserveinfo['reserveother']) && empty($sessreserveinfo['reservepastdeadline'])) {
                $reserveurl = new moodle_url('/mod/facetoface/reserve.php', array('action' => 'reserve', 's' => $session->id,
                    'backtoallsessions' => 1));
                $reservelink .= html_writer::link($reserveurl, get_string('reserveother', 'mod_facetoface'));
                $reservelink .= html_writer::empty_tag('br');
            }

            if (has_capability('mod/facetoface:managereservations', $this->context)) {
                $managereserveurl = new moodle_url('/mod/facetoface/managereservations.php',
                    array('action'=> 'manage', 's' => $session->id));

                $reservelink .= html_writer::link($managereserveurl, get_string('managereservations', 'mod_facetoface'));
                $reservelink .= html_writer::empty_tag('br');
            }
        }

        return $reservelink;
    }

    /**
     * Creates the html for the signup/cancel/'more info' links. Basically the links where
     * their set up depends on the user's signup status and abilities around signing up (such
     * as whether they can cancel).
     *
     * @param stdClass $session
     * @param bool $sessionstarted - true if the session has started.
     * @param bool $regdatestooltip - true if we want the dates in a tooltip for the signup link.
     * @param bool $returntoallsessions True if we want the user to return to view all sessions after an action.
     * @return string to be put into an options cell in the sessions table.
     * @throws coding_exception
     */
    private function session_options_signup_link($session, $sessionstarted, $regdatestooltip = false, $returntoallsessions = true, $displaytimezones = true) {
        global $USER, $DB;
        $signuplink = '';

        $timenow = time();
        // Registration status.
        if (!empty($session->registrationtimestart) && $session->registrationtimestart > $timenow) {
            $registrationopen = false;
        } else {
            $registrationopen = true;
        }

        if (!empty($session->registrationtimefinish) && $timenow > $session->registrationtimefinish) {
            $registrationclosed = true;
        } else {
            $registrationclosed = false;
        }

        // Prepare singup and cancel links.
        $urlparams = array('s' => $session->id);
        if ($returntoallsessions) {
            $urlparams['backtoallsessions'] = 1;
        }
        $signupurl = new moodle_url('/mod/facetoface/signup.php', $urlparams);
        $cancelurl = new moodle_url('/mod/facetoface/cancelsignup.php', $urlparams);

        $hasbookedsession = !empty($session->bookedsession);
        $isbookedsession = ($hasbookedsession
            && in_array($session->id, array_column(facetoface_get_user_submissions($session->facetoface, $USER->id), 'sessionid')));

        // Check if the user is allowed to cancel his booking.
        $allowcancellation = facetoface_allow_user_cancellation($session);
        if ($isbookedsession) {
            if (!$sessionstarted) {
                $signuplink .= html_writer::link($signupurl, get_string('moreinfo', 'facetoface'), array('title' => get_string('moreinfo', 'facetoface')));
            }
            if ($allowcancellation) {
                $signuplink .= html_writer::empty_tag('br');
                $canceltext = facetoface_is_user_on_waitlist($session) ? 'cancelwaitlist' : 'cancelbooking';
                $signuplink .= html_writer::link($cancelurl, get_string($canceltext, 'facetoface'), array('title' => get_string($canceltext, 'facetoface')));
            }
        } else if (!$sessionstarted) {
            if (!facetoface_session_has_capacity($session, $this->context, MDL_F2F_STATUS_WAITLISTED) && !$session->allowoverbook) {
                $signuplink .= get_string('none', 'facetoface');
            } else {
                $facetoface = $DB->get_record('facetoface', ['id' => $session->facetoface], 'multiplesessions');
                if (empty($session->cancelledstatus) && $registrationopen == true && $registrationclosed == false) {
                    if (!facetoface_has_unarchived_signups($session->facetoface, $USER->id)
                        || $facetoface->multiplesessions == "1") {
                        // Ok to register.
                        if ($regdatestooltip) {
                            $tooltip = $this->get_regdates_tooltip_info($session, $displaytimezones);
                        } else {
                            $tooltip = '';
                        }
                        $signuptext = facetoface_is_signup_by_waitlist($session) ? 'joinwaitlist' : 'signup';
                        $signuplink .= html_writer::link($signupurl, get_string($signuptext, 'facetoface'), array('title' => $tooltip, 'aria-label' => $tooltip));
                    } else {
                        $signuplink .= html_writer::span(get_string('error:alreadysignedup', 'facetoface'), '',
                            array('aria-label' => get_string('error:alreadysignedup', 'facetoface')));
                    }
                } else if ($registrationclosed == true) {
                    // Registration has closed for this session.
                    if ($regdatestooltip) {
                        $tooltip = $this->get_regdates_tooltip_info($session, $displaytimezones);
                    } else {
                        $tooltip = get_string('registrationclosed', 'facetoface');
                    }
                    $signuplink .= html_writer::span(get_string('signupunavailable', 'facetoface'), '',
                        array('title' => $tooltip, 'aria-label' => $tooltip));
                } else {
                    // Registration date not yet reached.
                    if ($regdatestooltip) {
                        $tooltip = $this->get_regdates_tooltip_info($session, $displaytimezones);
                    } else {
                        $tooltip = get_string('registrationnotopen', 'facetoface');
                    }
                    $signuplink .= html_writer::span(get_string('signupunavailable', 'facetoface'), '',
                        array('title' => $tooltip, 'aria-label' => $tooltip));
                }
            }
        }

        if (empty($signuplink)) {
            if ($sessionstarted && $allowcancellation) {
                $canceltext = facetoface_is_user_on_waitlist($session) ? 'cancelwaitlist' : 'cancelbooking';
                $signuplink = html_writer::link($cancelurl, get_string($canceltext, 'facetoface'), array('title' => get_string($canceltext, 'facetoface')));
            }
        }

        return $signuplink;
    }

    /**
     * Main calendar hook function for rendering the f2f filter controls
     *
     * @return string html
     */
    public function calendar_filter_controls() {
        global $SESSION;

        // Custom fields.
        $fieldsall = facetoface_get_customfield_filters();
        $output = '';
        foreach ($fieldsall as $type => $fields) {
            foreach ($fields as $f) {
                $currentval = '';
                if (!empty($SESSION->calendarfacetofacefilter[$type][$f->shortname])) {
                    $currentval = $SESSION->calendarfacetofacefilter[$type][$f->shortname];
                }
                $output .= $this->custom_field_chooser($type, $f, $currentval);
            }
        }
        return $output;
    }

    /**
     * Generates a custom field select for a f2f custom field
     *
     * @param string $type Custom field set ("room", "sess", etc)
     * @param int $field
     * @param string $currentvalue
     *
     * @return string html
     */
    public function custom_field_chooser($type, $field, $currentvalue) {
        // Same $fieldname  must be used in lib.php facetoface_calendar_set_filter().
        $fieldname = "field_{$type}_{$field->shortname}";
        $stringsource = '';
        switch($type) {
            case 'sess':
                $stringsource = 'customfieldsession';
            break;
            case 'room':
                $stringsource = 'customfieldroom';
            break;
            default:
                $stringsource = 'customfieldother';
        }

        $value = empty($currentvalue) ? '' : $currentvalue;
        $values = array();
        switch ($field->datatype) {
            case 'multiselect':
                $param1 = json_decode($field->param1, true);
                foreach ($param1 as $option) {
                    $values[] = $option['option'];
                }
                break;
            case 'menu':
                $values = explode("\n", $field->param1);
                break;
            case 'checkbox':
                $values = array(0 => get_string('no'), 1 => get_string('yes'));
                break;
            case 'datetime':
                $label = html_writer::empty_tag('input', array('type' => 'text', 'size' => 10, 'name' => $fieldname, 'value' => $value, 'id' => 'id_' . $fieldname));
                build_datepicker_js('#id_' . $fieldname);
                return html_writer::tag('label', get_string($stringsource, 'facetoface', $field->fullname) . ':', array('for' => 'id_' . $fieldname)) . $label;
                break;
            case 'location':
            case 'textarea':
            case 'text':
                $label = html_writer::empty_tag('input', array('type' => 'text', 'size' => 15, 'name' => $fieldname, 'value' => $value, 'id' => 'id_' . $fieldname));
                return html_writer::tag('label', get_string($stringsource, 'facetoface', $field->fullname) . ':', array('for' => 'id_' . $fieldname)) . $label;
                break;
            default:
                return false;
        }

        // Build up dropdown list of values.
        $options = array();
        if (!empty($values)) {
            foreach ($values as $value) {
                $v = clean_param(trim($value), PARAM_TEXT);
                if (!empty($v)) {
                    $options[s($v)] = format_string($v);
                }
            }
        }

        $nothing = get_string('all');
        $nothingvalue = 'all';

        $currentvalue = empty($currentvalue) ? $nothingvalue : $currentvalue;

        $dropdown = html_writer::select($options, $fieldname, $currentvalue, array($nothingvalue => $nothing));

        return html_writer::tag('label', get_string($stringsource, 'facetoface', $field->fullname) . ':', array('for' => 'id_customfields')) . $dropdown;

    }

    public function setcontext($context) {
        $this->context = $context;
    }

    /**
     * Generate the multiselect inputs + add/remove buttons to control allocating / deallocating users
     * for this session
     *
     * @param object $team containing the lists of users who can be allocated / deallocated
     * @param object $session
     * @param array $reserveinfo details of the number of allocations allowed / left
     * @return string HTML fragment to be output
     */
    function session_user_selector($team, $session, $reserveinfo) {
        $output = html_writer::start_tag('div', array('class' => 'row-fluid user-multiselect'));

        // Current allocations.
        $output .= html_writer::start_tag('div', array('class' => 'span5'));
        $info = (object)array(
            'allocated' => $reserveinfo['allocated'][$session->id],
            'max' => $reserveinfo['maxallocate'][$session->id],
        );
        $heading = get_string('currentallocations', 'mod_facetoface', $info);
        $output .= html_writer::tag('label', $heading, array('for' => 'deallocation'));
        $selected = optional_param_array('deallocation', array(), PARAM_INT);

        $opts = '';
        $opts .= html_writer::start_tag('optgroup', array('label' => get_string('thissession', 'mod_facetoface')));
        if (empty($team->current)) {
            $opts .= html_writer::tag('option', get_string('none'), array('value' => null, 'disabled' => 'disabled'));
        } else {
            foreach ($team->current as $user) {
                $name = fullname($user);
                $attr = array('value' => $user->id);
                if (in_array($user->id, $selected)) {
                    $attr['selected'] = 'selected';
                }
                if (!empty($user->cannotremove)) {
                    $attr['disabled'] = 'disabled';
                    $name .= ' ('.get_string($user->cannotremove, 'mod_facetoface').')';
                }
                $opts .= html_writer::tag('option', $name, $attr)."\n";
            }
        }
        $opts .= html_writer::end_tag('optgroup');
        if (!empty($team->othersession)) {
            $opts .= html_writer::start_tag('optgroup', array('label' => get_string('othersession', 'mod_facetoface')));
            foreach ($team->othersession as $user) {
                $name = fullname($user);
                $attr = array('value' => $user->id, 'disabled' => 'disabled');
                if (!empty($user->cannotremove)) {
                    $name .= ' ('.get_string($user->cannotremove, 'mod_facetoface').')';
                }
                $opts .= html_writer::tag('option', $name, $attr)."\n";
            }
        }
        $output .= html_writer::tag('select', $opts, array('name' => 'deallocation[]', 'multiple' => 'multiple',
                                                          'id' => 'deallocation', 'size' => 20));
        $output .= html_writer::end_tag('div');

        // Buttons.
        $output .= html_writer::start_tag('div', array('class' => 'span2 controls'));
        $addlabel = $this->output->larrow().' '.get_string('add');
        $output .= html_writer::empty_tag('input', array('name' => 'add', 'id' => 'add', 'type' => 'submit',
                                                         'value' => $addlabel, 'title' => get_string('add')));
        $removelabel = get_string('remove').' '.$this->output->rarrow();
        $output .= html_writer::empty_tag('input', array('name' => 'remove', 'id' => 'remove', 'type' => 'submit',
                                                          'value' => $removelabel, 'title' => get_string('remove')));
        $output .= html_writer::end_tag('div');

        // Potential allocations.
        $output .= html_writer::start_tag('div', array('class' => 'span5'));
        $output .= html_writer::tag('label', get_string('potentialallocations', 'mod_facetoface',
                                                           $reserveinfo['allocate'][$session->id]),
                                       array('for' => 'allocation'));

        $selected = optional_param_array('allocation', array(), PARAM_INT);
        $optspotential = array();
        foreach ($team->potential as $potential) {
            $optspotential[$potential->id] = fullname($potential);
        }
        $attr = array('multiple' => 'multiple', 'id' => 'allocation', 'size' => 20);
        if ($reserveinfo['allocate'][$session->id] == 0) {
            $attr['disabled'] = 'disabled';
        }
        $output .= html_writer::select($optspotential, 'allocation[]', $selected, null, $attr);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Output the given list of reservations/allocations that this manager has made
     * in other sessions in this facetoface.
     *
     * @param object[] $bookings
     * @param object $manager
     * @return string HTML fragment to output the list
     */
    function other_reservations($bookings, $manager) {
        global $USER;

        if (!$bookings) {
            return '';
        }

        // Gather the session data together.
        $sessions = array();
        foreach ($bookings as $booking) {
            if (!isset($sessions[$booking->sessionid])) {
                $session = facetoface_get_session($booking->sessionid);
                $sessions[$booking->sessionid] = (object)array(
                    'reservations' => 0,
                    'bookings' => array(),
                    'dates' => \mod_facetoface\event_dates::format_dates($session),
                );
            }
            if ($booking->userid) {
                $sessions[$booking->sessionid]->bookings[$booking->userid] = fullname($booking);
            } else {
                $sessions[$booking->sessionid]->reservations++;
            }
        }

        // Output the details as a table.
        if ($manager->id == $USER->id) {
            $bookingstr = get_string('yourbookings', 'facetoface');
        } else {
            $bookingstr = get_string('managerbookings', 'facetoface', fullname($manager));
        }
        $table = new html_table();
        $table->head = array(
            get_string('sessiondatetime', 'facetoface'),
            $bookingstr,
        );
        $table->attributes = array('class' => 'generaltable managerbookings');

        foreach ($sessions as $session) {
            $details = array();
            if ($session->reservations) {
                $details[] = get_string('reservations', 'mod_facetoface', $session->reservations);
            }
            $details += $session->bookings;
            $details = '<li>'.implode('</li><li>', $details).'</li>';
            $details = html_writer::tag('ul', $details);
            $row = new html_table_row(array($session->dates, $details));
            $table->data[] = $row;
        }

        $heading = $this->output->heading(get_string('existingbookings', 'mod_facetoface'), 3);

        return $heading . html_writer::table($table);
    }

    /**
     * Manage customfield tabs displayed in customfield/index.php
     *
     * @param string $currenttab
     * @return string tabs
     */
    public function customfield_management_tabs($currenttab = 'facetofacesession') {
        $tabs = array();
        $row = array();
        $activated = array();
        $inactive = array();

        $row[] = new tabobject('facetofacesession', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofacesession')),
            get_string('sessioncustomfieldtab', 'facetoface'));
        $row[] = new tabobject('facetofaceasset', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofaceasset')),
            get_string('assetcustomfieldtab', 'facetoface'));
        $row[] = new tabobject('facetofaceroom', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofaceroom')),
            get_string('roomcustomfieldtab', 'facetoface'));
        $row[] = new tabobject('facetofacesignup', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofacesignup')),
            get_string('signupcustomfieldtab', 'facetoface'));
        $row[] = new tabobject('facetofacecancellation', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofacecancellation')),
            get_string('cancellationcustomfieldtab', 'facetoface'));
        $row[] = new tabobject('facetofacesessioncancel', new moodle_url('/mod/facetoface/customfields.php', array('prefix' => 'facetofacesessioncancel')),
            get_string('sessioncancellationcustomfieldtab', 'facetoface'));

        $tabs[] = $row;
        $activated[] = $currenttab;

        return print_tabs($tabs, $currenttab, $inactive, $activated, true);
    }

    /**
     * Manage report tabs
     *
     * @param string $currenttab
     * @return string tabs
     */
    public function reports_management_tabs($currenttab = 'facetofaceeventreport') {
        $tabs = array();
        $row = array();
        $activated = array();
        $inactive = array();

        $row[] = new tabobject('facetofaceeventreport', new moodle_url('/mod/facetoface/eventreport.php'),
            get_string('eventsview', 'mod_facetoface'));

        $row[] = new tabobject('facetofacesessionreport', new moodle_url('/mod/facetoface/sessionreport.php'),
            get_string('sessionsview', 'mod_facetoface'));

        $tabs[] = $row;
        $activated[] = $currenttab;

        return print_tabs($tabs, $currenttab, $inactive, $activated, true);
    }

    /**
     * Show a list of all reservations for a session and allow them to be removed.
     *
     * @param object $reservations Data about all the reservations
     */
    public function print_reservation_management_table($reservations) {

        $out = '';

        if (count($reservations) > 0) {
            $table = new html_table();
            $table->head = array(
                get_string('managername', 'mod_facetoface'),
                get_string('spacesreserved', 'mod_facetoface'),
                get_string('actions'));

            $table->attributes = array('class' => 'generaltable managereservations fullwidth');

            $strdelete = get_string('delete');

            foreach ($reservations as $reservation) {
                $managername = fullname($reservation);

                $managerlink = html_writer::link(new moodle_url('/user/profile.php',
                    array('id' => $reservation->bookedby)), $managername);

                $deleteurl = new moodle_url('/mod/facetoface/managereservations.php', array('s' => $reservation->sessionid,
                    'action' => 'delete', 'managerid' => $reservation->bookedby, 'sesskey' => sesskey()));
                $buttons = $this->action_icon($deleteurl, new pix_icon('t/delete', $strdelete));

                $row = new html_table_row(array($managerlink, $reservation->reservedspaces, $buttons));
                $table->data[] = $row;
            }

            $out .= html_writer::table($table);

        } else {
            $out .= html_writer::tag('p', get_string('noreservationsforsession', 'mod_facetoface'));
        }

        return $out;
    }

    /**
     * Render table of users used in add attendees list
     * @param array $users
     * @param \mod_facetoface\bulk_list $list User list Needed only with job assignments
     * @param int $sessionid Needed only with job assignments
     * @param int $jaselector Job assignements selection: 0 - no, 1 - optional, 2 - required
     * @return string
     */
    public function print_userlist_table($users, \mod_facetoface\bulk_list $list = null, $sessionid = 0, $jaselector = 0) {
        global $OUTPUT;
        $out = '';
        $showcfdatawarning = false;
        if (count($users) > 0) {
            $table = new html_table();
            $table->head = array(
                get_string('name'),
                get_string('email'),
                get_string('username'),
                get_string('idnumber'));
            if ($jaselector) {
                $jacolumnheader = get_string('jobassignment', 'facetoface');
                if ($jaselector == 2) {
                    // Taken from lib/formslib.php.
                    $jacolumnheader .= $OUTPUT->flex_icon('required', array(
                        'classes' => 'form-required',
                        'alt' => get_string('requiredelement', 'form'),
                        'title' => get_string('requiredelement', 'form')
                    ));
                }
                $table->head[] = $jacolumnheader;
            }

            if (isset(current($users)->cntcfdata)) {
                $table->head[] = get_string('signupdata', 'facetoface');
            }

            $table->attributes = array('class' => 'generaltable userstoadd fullwidth');

            foreach ($users as $user) {
                $fullname = fullname($user);
                $row = array($fullname, $user->email, $user->username, $user->idnumber);

                if ($jaselector) {
                    $janame = '';
                    // Get previously stored jobassignmentid from user list. @see attendess/select_job_assignment.php.
                    $userdata = $list->get_user_data($user->id);
                    if (!empty($userdata['jobassignmentid'])) {
                        $jobassignment = \totara_job\job_assignment::get_with_id($userdata['jobassignmentid']);
                        $janame = $jobassignment->fullname;
                    }
                    $url = new moodle_url('/mod/facetoface/attendees/select_job_assignment.php',
                            array('id' => $user->id, 's' => $sessionid, 'listid' => $list->get_list_id()));
                    $icon = $OUTPUT->action_icon($url, new pix_icon('t/edit', get_string('edit')), null,
                            array('class' => 'action-icon attendee-edit-job-assignment pull-right'));
                    $jobassign = html_writer::span($janame, 'jobassign'.$user->id, array('id' => 'jobassign'.$user->id));
                    $row[] = $icon . $jobassign;
                }

                if (isset($user->cntcfdata)) {
                    if ($user->cntcfdata) {
                        $showcfdatawarning = true;
                        $row[] = html_writer::tag('strong', get_string('yes'));
                    } else {
                        $row[] = get_string('no');
                    }
                }
                $row = new html_table_row($row);
                $table->data[] = $row;
            }

            if ($showcfdatawarning) {
                $out .= $OUTPUT->notification(get_string('removecfdatawarning', 'facetoface'), 'notifymessage');
            }
            $out .= $OUTPUT->render($table);
        }
        return $out;
    }

    /**
     * Displays the dismiss action icon the the mismatched approval types notice
     *
     * @param int $f2fid    The id of the facetoface currently being viewed
     * @return string       The html for the icon
     */
    public function dismiss_selfapproval_notice($f2fid) {
        $attributes = array('class' => 'smallicon dismissicon');
        $dismissstr = get_string('dismiss', 'mod_facetoface');
        $dismissurl = new moodle_url('/mod/facetoface/approver/dismiss.php', array('fid' => $f2fid, 'sesskey' => sesskey()));
        return $this->output->action_icon($dismissurl, new pix_icon('/t/delete', $dismissstr), null, $attributes);
    }

    /**
     * Render details of the room
     * @param stdClass $room
     * @return string
     */
    public function render_room_details($room) {
        global $DB;

        $output = array();

        $output[] = html_writer::start_tag('dl', array('class' => 'f2f roomdetails dl-horizontal'));

        // Room name.
        $output[] = html_writer::tag('dt', get_string('roomname', 'facetoface'));
        $output[] = html_writer::tag('dd', $room->name);

        $options = array('prefix' => 'facetofaceroom', 'extended' => true);
        $fields = customfield_get_data($room, 'facetoface_room', 'facetofaceroom', true, $options);
        if (!empty($fields)) {

            foreach ($fields as $field => $value) {

                $output[] = html_writer::tag('dt', $field);
                $output[] = html_writer::tag('dd', $value);
            }
        }

        // Capacity.
        $output[] = html_writer::tag('dt', get_string('capacity', 'facetoface'));
        $output[] = html_writer::tag('dd', $room->capacity);

        // Allow scheduling conflicts.
        $output[] = html_writer::tag('dt', get_string('allowroomconflicts', 'facetoface'));
        $output[] = html_writer::tag('dd', $room->allowconflicts ? get_string('yes') : get_string('no'));

        // Description.
        if (!empty($room->description)) {
            $output[] = html_writer::tag('dt', get_string('roomdescription', 'facetoface'));
            $descriptionhtml = file_rewrite_pluginfile_urls(
                $room->description,
                'pluginfile.php',
                \context_system::instance()->id,
                'mod_facetoface',
                'room',
                $room->id
            );
            $descriptionhtml = format_text($descriptionhtml, FORMAT_HTML);
            $output[] = html_writer::tag('dd', $descriptionhtml);
        }

        // Created.
        $created = new stdClass();
        $created->user = get_string('unknownuser');
        if (!empty($room->usercreated)) {
            $created->user = html_writer::link(
                new moodle_url('/user/view.php', array('id' => $room->usercreated)),
                fullname($DB->get_record('user', array('id' => $room->usercreated)))
            );
        }
        $created->time = userdate($room->timecreated);
        $output[] = html_writer::tag('dt', get_string('created', 'mod_facetoface'));
        $output[] = html_writer::tag('dd', get_string('timestampbyuser', 'mod_facetoface', $created));

        // Modified.
        if (!empty($room->timemodified)) {
            $modified = new stdClass();
            $modified->user = get_string('unknownuser');
            if (!empty($room->usermodified)) {
                $modified->user = html_writer::link(
                    new moodle_url('/user/view.php', array('id' => $room->usermodified)),
                    fullname($DB->get_record('user', array('id' => $room->usermodified)))
                );
            }
            $modified->time = userdate($room->timemodified);

            $output[] = html_writer::tag('dt', get_string('modified'));
            $output[] = html_writer::tag('dd', get_string('timestampbyuser', 'mod_facetoface', $modified));
        }

        $output[] = html_writer::end_tag('dl');

        $output = implode('', $output);

        return $output;
    }

    /**
     * Render asset meta data
     *
     * @param stdClass $asset
     * @return string
     */
    public function render_asset_details($asset) {
        global $DB;

        $output = [];

        $output[] = html_writer::start_tag('dl', array('class' => 'f2f roomdetails'));

        // Asset name.
        $output[] = html_writer::tag('dt', get_string('assetname', 'facetoface'));
        $output[] = html_writer::tag('dd', $asset->name);

        $options = array('prefix' => 'facetofaceasset', 'extended' => true);
        $fields = customfield_get_data($asset, 'facetoface_asset', 'facetofaceasset', true, $options);
        if (!empty($fields)) {
            foreach ($fields as $field => $value) {

                $output[] = html_writer::tag('dt', $field);
                $output[] = html_writer::tag('dd', $value);
            }
        }

        // Allow scheduling conflicts.
        $output[] = html_writer::tag('dt', get_string('allowassetconflicts', 'facetoface'));
        $output[] = html_writer::tag('dd', $asset->allowconflicts ? get_string('yes') : get_string('no'));

        // Description.
        if (!empty($asset->description)) {
            $output[] = html_writer::tag('dt', get_string('assetdescription', 'facetoface'));
            $descriptionhtml = file_rewrite_pluginfile_urls(
                $asset->description,
                'pluginfile.php',
                \context_system::instance()->id,
                'mod_facetoface',
                'asset',
                $asset->id
            );
            $descriptionhtml = format_text($descriptionhtml, FORMAT_HTML);
            $output[] = html_writer::tag('dd', $descriptionhtml);
        }

        // Created.
        $created = new stdClass();
        $created->user = get_string('unknownuser');
        if (!empty($asset->usercreated)) {
            $created->user = html_writer::link(
                new moodle_url('/user/view.php', array('id' => $asset->usercreated)),
                fullname($DB->get_record('user', array('id' => $asset->usercreated)))
            );
        }
        $created->time = userdate($asset->timecreated);
        $output[] = html_writer::tag('dt', get_string('created', 'mod_facetoface'));
        $output[] = html_writer::tag('dd', get_string('timestampbyuser', 'mod_facetoface', $created));

        // Modified.
        if (!empty($asset->timemodified)) {
            $modified = new stdClass();
            $modified->user = get_string('unknownuser');
            if (!empty($asset->usermodified)) {
                $modified->user = html_writer::link(
                    new moodle_url('/user/view.php', array('id' => $asset->usermodified)),
                    fullname($DB->get_record('user', array('id' => $asset->usermodified)))
                );
            }
            $modified->time = userdate($asset->timemodified);

            $output[] = html_writer::tag('dt', get_string('modified'));
            $output[] = html_writer::tag('dd', get_string('timestampbyuser', 'mod_facetoface', $modified));
        }

        $output[] = html_writer::end_tag('dl');

        $output = implode('', $output);

        return $output;
    }
}
