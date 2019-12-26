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
* @author Alastair Munro <alastair.munro@totaralms.com>
* @author Aaron Barnes <aaron.barnes@totaralms.com>
* @author Francois Marier <francois@catalyst.net.nz>
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;

final class messaging {

    /**
     * Generate iCal file for a seminar event.
     * Updated function that actually works.
     *
     * @param \stdClass $f2f Seminar instance
     * @param \stdClass $session Session Instance
     * @param integer $method The method, @see {{MDL_F2F_INVITE}}
     * @param integer|\stdClass $user user id\instance
     * @param null|array|\stdClass $dates Array or a single date to create\update
     * @param array $canceldates Array or a single date to cancel
     * @param string $description Extra message to add in the iCal description
     * @return \stdClass iCal filename and template
     */
    public static function generate_ical($f2f, $session, $method, $user, $dates = null, $canceldates = [], $description = '') {
        global $DB;

        // Checking date overrides.
        if (is_null($dates)) {
            if (empty($session->sessiondates)) {
                $dates = $DB->get_records('facetoface_sessions_dates', ['sessionid' => $session->id], 'timestart');
            } else {
                $dates = $session->sessiondates;
            }
        }

        // Checking whether a single date has been passed.
        if (is_object($dates)) {
            $dates = [$dates];
        }
        if (is_object($canceldates)) {
            $canceldates = [$canceldates];
        }

        // Filter old dates, but keeping matching old\new.
        $canceldates = array_combine(array_column($canceldates, 'id'), $canceldates);
        $dates = array_filter($dates, function($date) use (&$canceldates) {
            if (isset($canceldates[$date->id])) {
                unset($canceldates[$date->id]);
            }
            return true;
        });

        // Get user object if only id is given.
        $user = is_object($user) ? $user : $DB->get_record('user', ['id' => $user]);
        $rooms = \mod_facetoface\room_list::get_event_rooms($session->id);

        // If generating event for a single date, then use REQUEST, otherwise use PUBLISH.
        // Generally publish is used not for events, but for unsolicited invitations and must not
        // contain attendees, but requests with multiple dates simply don't work with apple calendar.
        // Moreover, removing CANCEL status here altogether, as it doesn't always mark dates as cancelled via
        // different calendar apps.
        if ((count($dates) == 1 && empty($canceldates)) ||
            count($canceldates) == 1 && empty($dates)) {
            $icalmethod = 'REQUEST';
        } else {
            $icalmethod = 'PUBLISH';
        }

        // Little helper which would have been a private method only if we had a class to generate
        // a VEVENT block for a single date to avoid code duplication.
        $generate_event_for_the_date = function ($date, $cancel = false)
        use ($f2f, $session, $user, $method, $rooms, $description) {
            global $CFG;

            $method = $cancel ? MDL_F2F_CANCEL : $method;

            // Date that this representation of the calendar information was created -
            // we use the time the session was created
            // http://www.kanzaki.com/docs/ical/dtstamp.html
            $DTSTAMP = self::ical_generate_timestamp($session->timecreated);

            // Primitive, but should work SEQUENCE according to iCal specs is an integer up to 2147483647
            // This approach gives many years of available sequence numbers.
            $SEQUENCE = time() - $session->timecreated;

            // UIDs should be globally unique.
            $urlbits = parse_url($CFG->wwwroot);

            $UID =
                $DTSTAMP .
                // Unique identifier, salted with site identifier.
                '-' . substr(md5($CFG->siteidentifier . $session->id . $user->id), -8) .
                '-' . $date->id .
                '@' . $urlbits['host']; // Hostname for this moodle installation

            $DTSTART = self::ical_generate_timestamp($date->timestart);
            $DTEND   = self::ical_generate_timestamp($date->timefinish);

            $SUMMARY = str_replace("\\n", "\\n ", self::ical_escape($f2f->name, true));

            $icaldescription = get_string('icaldescription', 'facetoface', $f2f);
            $icaldescription .= !empty($description) ? "\n" . $description : '';
            $icaldescription .= !empty($f2f->intro) ? "\n" . $f2f->intro : '';
            $icaldescription .= !empty($session->details) ? "\n" . $session->details : '';
            $DESCRIPTION = self::ical_escape($icaldescription, true);

            // Get the location data from custom fields if they exist.
            $location = [];
            if (!empty($date->roomid) && $rooms->contains($date->roomid)) {
                /**
                 * @var \mod_facetoface\room $room
                 */
                $room = $rooms->get($date->roomid);
                $roomcf = $room->get_customfield_array();

                if (!empty($room->get_name())) {
                    $location[] = $room->get_name();
                }
                if (!empty($roomcf['building'])) {
                    $location[] = $roomcf['building'];
                }
                if (!empty($roomcf['location'])) {
                    $location[] = $roomcf['location'];
                }
            }
            // NOTE: Newlines are meant to be encoded with the literal sequence
            // '\n'. But evolution presents a single line text field for location,
            // and shows the newlines as [0x0A] junk. So we switch it for commas
            // here. Remember commas need to be escaped too.
            $delimiter = get_string('icallocationstringdelimiter', 'facetoface');
            $location = str_replace('\n', $delimiter, self::ical_escape(implode($delimiter."\n", $location), true));

            // Possibility of multiple commas, replaced with the single one.
            $LOCATION = preg_replace("/{$delimiter}+/", $delimiter, $location);

            $ORGANISEREMAIL = \mod_facetoface\facetoface_user::get_facetoface_user()->email;

            if ($method & MDL_F2F_CANCEL) {
                $ROLE = 'NON-PARTICIPANT';
                $CANCELSTATUS = "\nSTATUS:CANCELLED";
            } else {
                $ROLE = 'REQ-PARTICIPANT';
                $CANCELSTATUS = '';
            }

            // FIXME: if the user has input their name in another language, we need
            // to set the LANGUAGE property parameter here
            $USERNAME = fullname($user);
            $MAILTO   = $user->email;

            return implode([
                "BEGIN:VEVENT",
                "ORGANIZER;CN={$ORGANISEREMAIL}:MAILTO:{$ORGANISEREMAIL}",
                "DTSTART:{$DTSTART}",
                "DTEND:{$DTEND}",
                "LOCATION:{$LOCATION}",
                "TRANSP:OPAQUE{$CANCELSTATUS}",
                "SEQUENCE:{$SEQUENCE}",
                "UID:{$UID}",
                "DTSTAMP:{$DTSTAMP}",
                "DESCRIPTION:{$DESCRIPTION}",
                "SUMMARY:{$SUMMARY}",
                "PRIORITY:5",
                "CLASS:PUBLIC",
                "ATTENDEE;CUTYPE=INDIVIDUAL;ROLE={$ROLE};PARTSTAT=NEEDS-ACTION;",
                " RSVP=FALSE;CN={$USERNAME};LANGUAGE=en:MAILTO:{$MAILTO}",
                "END:VEVENT",
            ], "\r\n");
        };

        $VEVENTS = [];

        // Generating for updated dates.
        foreach ($dates as $date) {
            $VEVENTS[] = $generate_event_for_the_date($date);
        }

        // Generating for cancelled dates.
        foreach ($canceldates as $date) {
            $VEVENTS[] = $generate_event_for_the_date($date, true);
        }

        $VEVENTS = implode("\r\n", $VEVENTS);

        $template = implode("\r\n", [
            "BEGIN:VCALENDAR",
            "VERSION:2.0",
            "PRODID:-//Moodle//NONSGML Facetoface//EN",
            "METHOD:{$icalmethod}",
            "{$VEVENTS}",
            "END:VCALENDAR\r\n"
        ]);

        // This is stolen from file_get_unused_draft_itemid(), replace once messaging accepts real files or strings.
        $contextid = \context_user::instance($user->id)->id;
        $fs = get_file_storage();
        $draftitemid = rand(1, 999999999);
        while ($files = $fs->get_area_files($contextid, 'user', 'draft', $draftitemid)) {
            $draftitemid = rand(1, 999999999);
        }

        // Let's just fake the draft area here because it will get automatically cleanup up later in cron if necessary.
        return (object) [
            'file' => $fs->create_file_from_string([
                'contextid' => $contextid,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draftitemid,
                'filepath' => '/',
                'filename' => 'ical.ics'
            ], $template),
            'content' => $template
        ];
    }

    /**
     * Generates a timestamp for Ical
     * 
     * @param int $timestamp
     * @return timestamp
     * @return string|false a formatted date string. If a non-numeric value is used for timestamp, false is returned
     */
    public static function ical_generate_timestamp($timestamp) {

        return gmdate('Ymd', $timestamp) . 'T' . gmdate('His', $timestamp) . 'Z';
    }

    /**
     * Escapes data of the text datatype in ICAL documents.
     * See RFC2445 or http://www.kanzaki.com/docs/ical/text.html or a more readable definition
     * 
     * @param string $text
     * @param bool $converthtml
     * @return string
     */
    public static function ical_escape($text, $converthtml=false) {
        if (empty($text)) {
            return '';
        }

        if ($converthtml) {
            $text = html_to_text($text, 0);
        }

        $text = str_replace(
            array('\\',   "\n", ';',  ',', '"'),
            array('\\\\', '\n', '\;', '\,', '\"'),
            $text
        );

        // Text should be wordwrapped at 75 octets, and there should be one
        // whitespace after the newline that does the wrapping.
        // More info: http://tools.ietf.org/html/rfc5545#section-3.1
        // For spacing issues see http://php.net/wordwrap#52532
        $text = str_replace(' ', chr(26), $text);
        $text = wordwrap($text, 74, "\r\n\t", true);
        $text = str_replace(chr(26), ' ', $text);

        return $text;
    }
}
