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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

global $CFG;
require_once($CFG->dirroot."/mod/facetoface/lib.php");

class event_dates {
    /**
     * Get rendered start date, finish date and timestamp.
     * @param int $timestart start timestamp
     * @param int $timefinish finish timestamp
     * @param string $sesiontimezone
     * @param bool $displaytimezone should timezone be displayed
     * @return string
     */
    public static function render($timestart, $timefinish, $sesiontimezone, $displaytimezone = true) {
        $sessionobj = facetoface_format_session_times(
            $timestart,
            $timefinish,
            $sesiontimezone
        );

        if (empty($displaytimezone)) {
            $sessionobj->timezone = '';
        }

        return get_string('sessiondatecolumn_html', 'facetoface', $sessionobj);
    }

    /**
     * Return default start and end date/time of session
     * @return array($defaultstart, $defaultfinish)
     */
    public static function get_default() {
        $config = get_config('facetoface');
        $now = time();
        $defaultstart = $now;

        if (!empty($config->defaultdaystosession)) {
            if (!empty($config->defaultdaysskipweekends)) {
                $defaultstart = strtotime("+{$config->defaultdaystosession} weekdays", $defaultstart);
            } else {
                $defaultstart = strtotime("+{$config->defaultdaystosession} days", $defaultstart);
            }
        }

        $defaultfinish = $defaultstart;

        if (!empty($config->defaultdaysbetweenstartfinish)) {
            $days = (int)$config->defaultdaysbetweenstartfinish;
            if (!empty($config->defaultdaysskipweekends)) {
                $defaultfinish = strtotime("+{$days} weekdays", $defaultfinish);
            } else {
                $defaultfinish = strtotime("+{$days} days", $defaultfinish);
            }
        }

        // Adjust for start time hours.
        if (!empty($config->defaultstarttime_hours)) {
            $defaultstart = strtotime(date('Y-m-d', $defaultstart).' 00:00:00');
            $defaultstart += HOURSECS * (int)$config->defaultstarttime_hours;
        }

        // Adjust for finish time hours.
        if (!empty($config->defaultfinishtime_hours)) {
            $defaultfinish = strtotime(date('Y-m-d', $defaultfinish).' 00:00:00');
            $defaultfinish += HOURSECS * (int)$config->defaultfinishtime_hours;
        }

        // Adjust for start time minutes.
        if (!empty($config->defaultstarttime_minutes)) {
            $defaultstart += MINSECS * (int)$config->defaultstarttime_minutes;
        }

        // Adjust for finish time minutes.
        if (!empty($config->defaultfinishtime_minutes)) {
            $defaultfinish += MINSECS * (int)$config->defaultfinishtime_minutes;
        }
        return array($defaultstart, $defaultfinish);
    }

    /**
     * Validate dates and room availability
     * @param int $timestart
     * @param int $timefinish
     * @param int $roomid
     * @param array $assetids
     * @param int $sessionid ignore room conflicts within current session (as it is covered by dates and some dates can be marked as deleted)
     * @param int $facetofaceid
     * @return array errors ('timestart' => string, 'timefinish' => string, 'assetids' => string, 'roomid' => string)
     */
    public static function validate($timestart, $timefinish, $roomid, $assetids, $sessionid, $facetofaceid) {
        $errors = array();
        // Validate start time.
        if ($timestart > $timefinish) {
            $errstr = get_string('error:sessionstartafterend', 'facetoface');
            $errors['timestart'] = $errstr;
            $errors['timefinish'] = $errstr;
        }

        // Validate room.
        if (!empty($roomid)) {
            // Check if the room is available.
            $room = facetoface_get_room($roomid);
            if (!$room) {
                $errors['roomid'] = get_string('roomdeleted', 'facetoface');
            } else if (!facetoface_is_room_available($timestart, $timefinish, $room, $sessionid, $facetofaceid)) {
                $link = \html_writer::link(new \moodle_url('/mod/facetoface/room.php', array('roomid' => $roomid)), $room->name,
                    array('target' => '_blank'));
                // We should not get here because users should be able to select only available slots.
                $errors['roomid'] = get_string('error:isalreadybooked', 'facetoface', $link);
            }
        }

        // Validate assets.
        if (!empty($assetids)) {
            foreach ($assetids as $assetid) {
                $asset = facetoface_get_asset($assetid);
                if (!$asset) {
                    $errors['assetid'][] = get_string('assetdeleted', 'facetoface');
                } else if (!facetoface_is_asset_available($timestart, $timefinish, $asset, $sessionid, $facetofaceid)) {
                    $link = \html_writer::link(new \moodle_url('/mod/facetoface/asset.php', array('assetid' => $assetid)), $asset->name,
                        array('target' => '_blank'));
                    // We should not get here because users should be able to select only available slots.
                    $errors['assetid'][] = get_string('error:isalreadybooked', 'facetoface', $link);
                }
            }
            if (!empty($errors['assetid'])) {
                $errors['assetids'] = implode(\html_writer::empty_tag('br'), $errors['assetid']);
            }
        }

        // Consolidate error message.
        if (!empty($errors['roomid']) || !empty($errors['assetid'])) {
            $items = array();
            if (!empty($errors['roomid'])) {
                $items[] = \html_writer::tag('li', $errors['roomid']);
                // Don't show duplicate error.
                unset($errors['roomid']);
            }
            if (!empty($errors['assetid'])) {
                foreach ($errors['assetid'] as $asseterror) {
                    $items[] = \html_writer::tag('li', $asseterror);
                }
                // Don't show duplicate error.
                unset($errors['assetid']);
            }
            $details = \html_writer::tag('ul', implode('', $items));
            $errors['timestart'] = get_string('error:datesunavailablestuff', 'facetoface', $details);
        }
        return $errors;
    }

    /**
     * Format the dates for the given session, when listing the other bookings made by a given manager
     * in a particular face to face instance.
     *
     * @param $session
     * @return string
     */
    public static function format_dates($session) {
        if (!empty($session->sessiondates)) {
            $formatteddates = array();
            foreach ($session->sessiondates as $date) {
                $formatteddate = '';
                $sessionobj = facetoface_format_session_times($date->timestart, $date->timefinish, $date->sessiontimezone);
                if ($sessionobj->startdate == $sessionobj->enddate) {
                    $formatteddate .= $sessionobj->startdate . ', ';
                } else {
                    $formatteddate .= $sessionobj->startdate . ' - ' . $sessionobj->enddate . ', ';
                }
                $formatteddate .= $sessionobj->starttime . ' - ' . $sessionobj->endtime . ' ' . $sessionobj->timezone;
                $formatteddates[] = $formatteddate;
            }
            $formatteddates = '<li>'.implode('</li><li>', $formatteddates).'</li>';
            $ret = \html_writer::tag('ul', $formatteddates);
        } else {
            $ret = \html_writer::tag('em', get_string('wait-listed', 'facetoface'));
        }
        return $ret;
    }
}