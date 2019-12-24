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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @package modules
 * @subpackage facetoface
 */

namespace mod_facetoface\form;

global $CFG;
require_once("{$CFG->libdir}/formslib.php");
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

class event extends \moodleform {

    /** @var context_module */
    protected $session;

    /** @var context_module */
    protected $facetoface;

    /** @var context_module */
    protected $context;

    /** @var context_module */
    protected $editoroptions;

    /** @var context_module */
    protected $fromform;

    /** @var context_module */
    protected $returnurl;

    function definition() {
        global $CFG;

        $mform =& $this->_form;
        $this->session = (isset($this->_customdata['session'])) ? $this->_customdata['session'] : false;
        $this->facetoface = $this->_customdata['facetoface'];
        $this->editoroptions = $this->_customdata['editoroptions'];
        $sessiondata = $this->_customdata['sessiondata'];
        $this->context = \context_module::instance($this->_customdata['cm']->id);
        if ($this->_customdata['backtoallsessions']) {
            $this->returnurl = new \moodle_url('/mod/facetoface/view.php', array('f' => $this->facetoface->id));
        } else {
            $this->returnurl = new \moodle_url('/course/view.php', array('id' => $this->_customdata['course']->id));
        }

        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->addElement('hidden', 'f', $this->_customdata['f']);
        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->addElement('hidden', 'c', $this->_customdata['c']);
        $mform->setType('id', PARAM_INT);
        $mform->setType('f', PARAM_INT);
        $mform->setType('s', PARAM_INT);
        $mform->setType('c', PARAM_INT);
        $mform->addElement('hidden', 'backtoallsessions', $this->_customdata['backtoallsessions']);
        $mform->setType('backtoallsessions', PARAM_BOOL);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        self::add_date_render_fields($this, $this->_customdata['defaulttimezone'], $this->_customdata['s'], $sessiondata);

        $mform->addElement('date_time_selector', 'registrationtimestart', get_string('registrationtimestart', 'facetoface'), array('optional' => true, 'showtimezone' => true));
        $mform->addHelpButton('registrationtimestart', 'registrationtimestart', 'facetoface');
        $mform->addElement('date_time_selector', 'registrationtimefinish', get_string('registrationtimefinish', 'facetoface'), array('optional' => true, 'showtimezone' => true));
        $mform->addHelpButton('registrationtimefinish', 'registrationtimefinish', 'facetoface');

        $mform->addElement('text', 'capacity', get_string('maxbookings', 'facetoface'), array('size' => 5));
        $mform->addRule('capacity', null, 'required', null, 'client');
        $mform->setType('capacity', PARAM_INT);
        $mform->setDefault('capacity', 10);
        $mform->addRule('capacity', null, 'numeric', null, 'client');
        $mform->addHelpButton('capacity', 'maxbookings', 'facetoface');

        $mform->addElement('checkbox', 'allowoverbook', get_string('allowoverbook', 'facetoface'));
        $mform->addHelpButton('allowoverbook', 'allowoverbook', 'facetoface');

        if (has_capability('mod/facetoface:configurecancellation', $this->context)) {
            // User cancellation settings.
            $radioarray = array();
            $radioarray[] = $mform->createElement('radio', 'allowcancellations', '', get_string('allowcancellationanytime', 'facetoface'), 1);
            $radioarray[] = $mform->createElement('radio', 'allowcancellations', '', get_string('allowcancellationnever', 'facetoface'), 0);
            $radioarray[] = $mform->createElement('radio', 'allowcancellations', '', get_string('allowcancellationcutoff', 'facetoface'), 2);
            $mform->addGroup($radioarray, 'allowcancellations', get_string('allowbookingscancellations', 'facetoface'), array('<br/>'), false);
            $mform->setType('allowcancellations', PARAM_INT);
            $mform->addHelpButton('allowcancellations', 'allowbookingscancellations', 'facetoface');

            // Cancellation cutoff.
            $cutoffnotegroup = array();
            $cutoffnotegroup[] =& $mform->createElement('duration', 'cancellationcutoff', '', array('defaultunit' => HOURSECS, 'optional' => false));
            $cutoffnotegroup[] =& $mform->createElement('static', 'cutoffnote', null, get_string('cutoffnote', 'facetoface'));
            $mform->addGroup($cutoffnotegroup, 'cutoffgroup', '', '&nbsp;', false);
            $mform->disabledIf('cancellationcutoff[number]', 'allowcancellations', 'notchecked', 2);
            $mform->disabledIf('cancellationcutoff[timeunit]', 'allowcancellations', 'notchecked', 2);
        }

        $facetoface_allowwaitlisteveryone = get_config(null, 'facetoface_allowwaitlisteveryone');
        if ($facetoface_allowwaitlisteveryone) {
            $mform->addElement('checkbox', 'waitlisteveryone', get_string('waitlisteveryone', 'facetoface'));
            $mform->addHelpButton('waitlisteveryone', 'waitlisteveryone', 'facetoface');
        }

        // Show minimum bookings and cut-off (for when this should be reached).
        $mform->addElement('text', 'mincapacity', get_string('minbookings', 'facetoface'), array('size' => 5));
        $mform->setType('mincapacity', PARAM_INT);
        $mform->setDefault('mincapacity', get_config('facetoface', 'defaultminbookings'));
        $mform->addRule('mincapacity', null, 'numeric', null, 'client');
        $mform->addHelpButton('mincapacity', 'mincapacity', 'facetoface');

        $cutoffdurationgroup = array();
        $cutoffdurationgroup[] =& $mform->createElement('checkbox', 'sendcapacityemail', '');
        $cutoffdurationgroup[] =& $mform->createElement('duration', 'cutoff', '', array('defaultunit' => HOURSECS, 'optional' => false));
        $cutoffdurationgroup[] =& $mform->createElement('static', 'cutoffnote', null, get_string('cutoffnote', 'facetoface'));
        $mform->addGroup($cutoffdurationgroup, 'cutoffdurationgroup', get_string('enablemincapacitynotification', 'facetoface'), '&nbsp;', false);

        $mform->setDefault('sendcapacityemail', 0);
        $mform->addHelpButton('cutoffdurationgroup', 'enablemincapacitynotification', 'facetoface');

        $mform->setType('cutoff', PARAM_INT);
        $mform->disabledIf('cutoff[number]', 'sendcapacityemail');
        $mform->disabledIf('cutoff[timeunit]', 'sendcapacityemail');


        if (!get_config(NULL, 'facetoface_hidecost')) {
            $formarray  = array();
            $formarray[] = $mform->createElement('text', 'normalcost', get_string('normalcost', 'facetoface'), 'size="5"');
            $formarray[] = $mform->createElement('static', 'normalcosthint', '', \html_writer::tag('span', get_string('normalcosthinttext','facetoface'), array('class' => 'hint-text')));
            $mform->addGroup($formarray,'normalcost_group', get_string('normalcost','facetoface'), array(' '),false);
            $mform->setType('normalcost', PARAM_TEXT);
            $mform->addHelpButton('normalcost_group', 'normalcost', 'facetoface');

            if (!get_config(NULL, 'facetoface_hidediscount')) {
                $formarray  = array();
                $formarray[] = $mform->createElement('text', 'discountcost', get_string('discountcost', 'facetoface'), 'size="5"');
                $formarray[] = $mform->createElement('static', 'discountcosthint', '', \html_writer::tag('span', get_string('discountcosthinttext','facetoface'), array('class' => 'hint-text')));
                $mform->addGroup($formarray,'discountcost_group', get_string('discountcost','facetoface'), array(' '),false);
                $mform->setType('discountcost', PARAM_TEXT);
                $mform->addHelpButton('discountcost_group', 'discountcost', 'facetoface');
            }
        }

        $mform->addElement('editor', 'details_editor', get_string('details', 'facetoface'), null, $this->editoroptions);
        $mform->setType('details_editor', PARAM_RAW);
        $mform->addHelpButton('details_editor', 'details', 'facetoface');

        // Choose users for trainer roles
        $roles = facetoface_get_trainer_roles($this->context);

        if ($roles) {
            // Get current trainers
            $current_trainers = facetoface_get_trainers($this->_customdata['s']);
            // Get course context and roles
            $rolenames = role_get_names($this->context);
            // Loop through all selected roles
            $header_shown = false;
            foreach ($roles as $role) {
                $rolename = $rolenames[$role->id]->localname;

                // Attempt to load users with this role in this context.
                $usernamefields = get_all_user_name_fields(true, 'u');
                $rs = get_role_users($role->id, $this->context, true, "u.id, {$usernamefields}", 'u.id ASC');

                if (!$rs) {
                    continue;
                }

                $choices = array();
                foreach ($rs as $roleuser) {
                    $choices[$roleuser->id] = fullname($roleuser);
                }

                // Show header (if haven't already)
                if ($choices && !$header_shown) {
                    $mform->addElement('header', 'trainerroles', get_string('sessionroles', 'facetoface'));
                    $mform->addElement('static', 'roleapprovalerror');
                    $header_shown = true;
                }

                // If only a few, use checkboxes
                if (count($choices) < 4) {
                    $role_shown = false;
                    foreach ($choices as $cid => $choice) {
                        // Only display the role title for the first checkbox for each role
                        if (!$role_shown) {
                            $roledisplay = $rolename;
                            $role_shown = true;
                        } else {
                            $roledisplay = '';
                        }

                        $mform->addElement('advcheckbox', 'trainerrole['.$role->id.']['.$cid.']', $roledisplay, $choice, null, array('', $cid));
                        $mform->setType('trainerrole['.$role->id.']['.$cid.']', PARAM_INT);
                    }
                } else {
                    $mform->addElement('select', 'trainerrole['.$role->id.']', $rolename, $choices, array('multiple' => 'multiple'));
                    $mform->setType('trainerrole['.$role->id.']', PARAM_SEQUENCE);
                }

                // Select current trainers
                if ($current_trainers) {
                    foreach ($current_trainers as $roleid => $trainers) {
                        $t = array();
                        foreach ($trainers as $trainer) {
                            $t[] = $trainer->id;
                            $mform->setDefault('trainerrole['.$roleid.']['.$trainer->id.']', $trainer->id);
                        }

                        $mform->setDefault('trainerrole['.$roleid.']', implode(',', $t));
                    }
                }
            }
        }

         // Show override conflicts if global setting is enabled.
        if (!empty($CFG->facetoface_allowschedulingconflicts)) {
            $text = format_text(get_string('allowschedulingconflictsnote', 'facetoface'));
            $allowschedulingconflictsgroup = array();
            $allowschedulingconflictsgroup[] =& $mform->createElement('selectyesno', 'allowconflicts', get_string('allowschedulingconflicts', 'facetoface'));
            $allowschedulingconflictsgroup[] =& $mform->createElement('static', 'allowschedulingconflictsnote', null, $text);
            $mform->addGroup($allowschedulingconflictsgroup, 'allowschedulingconflictsgroup', get_string('allowschedulingconflicts', 'facetoface'), '<br />', false);
            $mform->setType('allowschedulingconflictsgroup', PARAM_BOOL);
            $mform->addHelpButton('allowschedulingconflictsgroup', 'allowschedulingconflicts', 'facetoface');
            $mform->setDefault('allowconflicts', 0); // Defaults to 'no'.
        }

        // Show all custom fields. Customfield support.
        if (!$this->session) {
            $this->session = new \stdClass();
        }
        if (empty($this->session->id)) {
            $this->session->id = 0;
        }
        customfield_definition($mform, $this->session, 'facetofacesession', 0, 'facetoface_session');

        $this->add_action_buttons();

        $this->set_data($sessiondata);
    }

    /**
     * Adds html hidden fields and html rendered table to display in session date form
     * @param moodleform $form Form where to add fields and set values
     * @param string $defaulttimezone
     * @param int $sessionid
     * @param stdClass $sessiondata
     */
    public static function add_date_render_fields($form, $defaulttimezone, $sessionid, $sessiondata) {
        $mform = $form->_form;

        $mform->addElement('hidden', "cntdates");
        $mform->setType("cntdates", PARAM_INT);

        $table = new \html_table();
        $table->attributes['class'] = 'generaltable fullwidth f2fmanagedates';
        $table->head = array(
            get_string('dateandtime', 'facetoface'),
            get_string('room', 'facetoface'),
            get_string('assets', 'facetoface'),
            ''
        );
        $table->data = array();

        $mform->addElement('static', 'errors');

        for ($i = 0; $i < $sessiondata->cntdates; $i++) {
            $row = self::date_render_mixin($mform, $i, $sessiondata, $defaulttimezone);
            $table->data[] = $row;
        }
        $dateshtmlcontent = \html_writer::table($table);

        // Render this content hidden. Then it will be displayed by js during init.
        $html = \html_writer::div($dateshtmlcontent, 'sessiondates hidden', array('id'=>'sessiondates_' . $sessionid));
        $mform->addElement('static', 'sessiondates', get_string('sessiondates', 'facetoface'), $html);
        $mform->addElement('submit','date_add_fields', get_string('dateadd', 'facetoface'));
        $mform->registerNoSubmitButton('date_add_fields');
    }

    /**
     * Returns fields and html code required for one date (or new date if no session data provided)
     * Used also to dynamically inject new or cloned session date (event)
     * @param $mform
     * @param int $offset
     * @param stdClass $sessiondata
     * @param string $defaulttimezone Default timezone if date not set
     * @return
     */
    public static function date_render_mixin($mform, $offset, $sessiondata, $defaulttimezone) {
        global $OUTPUT;

        $dateid   = !empty($sessiondata->{"sessiondateid[$offset]"}) ? $sessiondata->{"sessiondateid[$offset]"} : 0;
        $roomid   = !empty($sessiondata->{"roomid[$offset]"}) ? $sessiondata->{"roomid[$offset]"} : '';
        $assetids = !empty($sessiondata->{"assetids[$offset]"}) ? $sessiondata->{"assetids[$offset]"} : '';

        // Add per-date form elements.
        // Clonable fields also must be listed in session.js.
        $mform->addElement('hidden', "sessiondateid[$offset]", $dateid);
        $mform->setType("sessiondateid[$offset]", PARAM_INT);
        $mform->addElement('hidden', "roomcapacity[$offset]");
        $mform->setType("roomcapacity[$offset]", PARAM_INT);
        $mform->addElement('hidden', "roomid[$offset]", $roomid);
        $mform->setType("roomid[$offset]", PARAM_INT);
        $mform->addElement('hidden', "assetids[$offset]", $assetids);
        $mform->setType("assetids[$offset]", PARAM_SEQUENCE);
        $mform->addElement('hidden', "timestart[$offset]");
        $mform->setType("timestart[$offset]", PARAM_INT);
        $mform->addElement('hidden', "timefinish[$offset]");
        $mform->setType("timefinish[$offset]", PARAM_INT);
        $mform->addElement('hidden', "sessiontimezone[$offset]");
        $mform->setType("sessiontimezone[$offset]", PARAM_TIMEZONE);
        $mform->addElement('hidden', "datedelete[$offset]");
        $mform->setType("datedelete[$offset]", PARAM_INT);

        $row = array();
        $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones');

        // Dates.
        if (empty($sessiondata->{"timestart[$offset]"})
            || empty($sessiondata->{"timefinish[$offset]"})
            || empty($sessiondata->{"sessiontimezone[$offset]"})) {
            list($timestart, $timefinish) = \mod_facetoface\event_dates::get_default();
            $sessiontimezone = $defaulttimezone;
        } else {
            $timestart = $sessiondata->{"timestart[$offset]"};
            $timefinish = $sessiondata->{"timefinish[$offset]"};
            $sessiontimezone = $sessiondata->{"sessiontimezone[$offset]"};
        }

        $mform->setDefault("timestart[$offset]", $timestart);
        $mform->setDefault("timefinish[$offset]", $timefinish);
        $mform->setDefault("sessiontimezone[$offset]", $sessiontimezone);

        $dateshtml = \mod_facetoface\event_dates::render(
            $timestart,
            $timefinish,
            $sessiontimezone,
            $displaytimezones
        );

        $strcopy = get_string('copy');
        $strdelete = get_string('delete');
        $streditdate = get_string('editdate', 'facetoface');

        $editicon = $OUTPUT->action_icon('#', new \pix_icon('t/edit', $streditdate), null,
            array('id' => "show-selectdate{$offset}-dialog", 'class' => 'action-icon show-selectdate-dialog', 'data-offset' => $offset));
        $row[] = $editicon . \html_writer::span($dateshtml, 'timeframe-text', array('id' => 'timeframe-text' . $offset));

        // Room.
        $selectroom = \html_writer::link("#", get_string('selectroom', 'facetoface'),
            array('id' => "show-selectroom{$offset}-dialog", 'class' => 'show-selectroom-dialog', 'data-offset' => $offset));

        // Room name and capacity will be loaded by js.
        $row[] = \html_writer::div('', 'roomname', array('id' => 'roomname' . $offset)) . $selectroom;

        // Assets.
        $selectassets = \html_writer::link("#", get_string('selectassets', 'facetoface'), array(
            'id' => "show-selectassets{$offset}-dialog",
            'class' => 'show-selectassets-dialog',
            'data-offset' => $offset
        ));

        // Assets items will be loaded by js.
        $row[] =  \html_writer::tag('ul', '', array(
                'id' => 'assetlist' . $offset,
                'class' => 'assetlist nonempty',
                'data-offset' => $offset
            )) . $selectassets;

        // Options.
        $cloneicon = $OUTPUT->action_icon('#', new \pix_icon('t/copy', $strcopy), null,
            array('class' => 'action-icon dateclone', 'data-offset' => $offset));
        $deleteicon = $OUTPUT->action_icon('#', new \pix_icon('t/delete', $strdelete), null,
            array('class' => 'action-icon dateremove', 'data-offset' => $offset));
        $row[] = $cloneicon . $deleteicon;

        return $row;
    }

    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        $facetofaceid = $this->_customdata['f'];
        $dates = array();
        $dateids = isset($data['sessiondateid']) ? $data['sessiondateid'] : array();
        $datecount = count($dateids);
        $deletecount = 0;
        $errdates = array();
        for ($i=0; $i < $datecount; $i++) {
            if (!empty($data['datedelete'][$i])) {
                // Ignore dates marked for deletion.
                $deletecount++;
                continue;
            }

            $starttime = $data["timestart"][$i];
            $endtime = $data["timefinish"][$i];
            $roomid = $data["roomid"][$i];
            $assetids = $data["assetids"][$i];
            $assetlist = array();
            if (!empty($assetids)) {
                $assetlist = explode(',', $assetids);
            }
            // If event is a cloning then remove session id and behave as a new event to get rooms availability.
            $sessid = ($data['c'] ? 0 : $data['s']);
            $errdate = \mod_facetoface\event_dates::validate($starttime, $endtime, $roomid, $assetlist, $sessid, $facetofaceid);

            if (!empty($errdate['timestart'])) {
                $errdates[] = $errdate['timestart'];
            }
            if (!empty($errdate['timefinish'])) {
                $errdates[] = $errdate['timefinish'];
            }
            if (!empty($errdate['roomid'])) {
                $errdates[] = $errdate['roomid'];
            }

            if (!empty($errdate['assetids'])) {
                $errdates[] = $errdate['assetids'];
            }

            //Check this date does not overlap with any previous dates - time overlap logic from a Stack Overflow post
            if (!empty($dates)) {
                foreach ($dates as $existing) {
                    if (($endtime > $existing->timestart) && ($existing->timefinish > $starttime) ||
                        ($endtime == $existing->timefinish) || ($starttime == $existing->timestart)) {
                        // This date clashes with an existing date - either they overlap or
                        // one of them is zero minutes and they start at the same time or end at the same time.
                        $messageconflictsamedate = get_string('error:sessiondatesconflict', 'facetoface');
                        if (!in_array($messageconflictsamedate, $errdates)) {
                            $errdates[] = $messageconflictsamedate;
                            break;
                        }
                    }
                }
            }

            // Registration cannot open once session has started.
            if (!empty($data['registrationtimestart'])) {
                if ($data['registrationtimestart'] >= $starttime) {
                    $errors['registrationtimestart'] = get_string('registrationstartsession', 'facetoface');
                }
            }

            // Registration close date must be on or before session has started.
            if (!empty($data['registrationtimefinish'])) {
                if ($data['registrationtimefinish'] > $starttime) {
                    $errors['registrationtimefinish'] = get_string('registrationfinishsession', 'facetoface');
                }
            }

            // If valid date, add to array.
            $date = new \stdClass();
            $date->timestart = $starttime;
            $date->timefinish = $endtime;
            $date->roomid = $roomid;
            $dates[] = $date;
        }

        if (isset($this->_customdata['session']) && isset($this->_customdata['session']->sessiondates) && count($dates) === count($this->_customdata['session']->sessiondates)) {
            // Its an existing session with the same number of dates, we are going to need to check if the session dates have been changed.
            $dateschanged = false;
            foreach ($dates as $date) {
                // We need to find each submit date.
                // If all are found then this submit date has not changed.
                $datefound = false;
                foreach ($this->_customdata['session']->sessiondates as $originaldate) {
                    if ($date->timestart == $originaldate->timestart && $date->timefinish == $originaldate->timefinish) {
                        // We've found the date.
                        $datefound = true;
                        break;
                    }
                }
                // If we didn't find the date, then we know they have changed.
                if (!$datefound) {
                    $dateschanged = true;
                    break;
                }
            }

        } else {
            // There are no previous session dates, or the number of session dates has changed.
            // Because of this we treat the dates as having changed.
            $dateschanged = true;
        }

        if(!empty($data['registrationtimestart']) && !empty($data['registrationtimefinish'])) {
            $start = $data['registrationtimestart'];
            $finish = $data['registrationtimefinish'];
            if ($start >= $finish) {
                // Registration opening time cannot be after registration close time.
                $errors['registrationtimestart'] = get_string('registrationerrorstartfinish', 'facetoface');
                $errors['registrationtimefinish'] = get_string('registrationerrorstartfinish', 'facetoface');
            }
        }

        // Check the availabilty of trainers if scheduling not allowed
        $trainerdata = !empty($data['trainerrole']) ? $data['trainerrole'] : array();
        $allowconflicts = !empty($data['allowconflicts']);

        if ($dates && !$allowconflicts && is_array($trainerdata)) {
            $wheresql = '';
            $whereparams = array();
            if (!empty($this->_customdata['s'])) {
                $wheresql = ' AND s.id != ?';
                $whereparams[] = $this->_customdata['s'];
            }

            // Seminar approval by role is set, required at least one role selected.
            $hasconflicts = 0;
            $selectedroleids = array();
            $usernamefields = get_all_user_name_fields(true, 'u');
            // Loop through roles.
            foreach ($trainerdata as $roleid => $trainers) {
                // Attempt to load users with this role in this context.
                $trainerlist = get_role_users($roleid, $this->context, true, "u.id, {$usernamefields}", 'u.id ASC');

                // Initialize error variable.
                $trainererrors = '';
                // Loop through trainers in this role.
                foreach ($trainers as $trainer) {

                    if (!$trainer) {
                        continue;
                    } else {
                        $selectedroleids[] = $roleid;
                    }

                    // Check their availability.
                    $availability = facetoface_get_sessions_within($dates, $trainer, $wheresql, $whereparams);
                    if (!empty($availability)) {
                        // Verify if trainers come in form of checkboxes or dropdown list to properly place the errors.
                        if (isset($this->_form->_types["trainerrole[{$roleid}][{$trainer}]"])) {
                            $errors["trainerrole[{$roleid}][{$trainer}]"] = facetoface_get_session_involvement($trainerlist[$trainer], $availability);
                        } else if (isset($this->_form->_types["trainerrole[{$roleid}]"])) {
                            $trainererrors .= \html_writer::tag('div', facetoface_get_session_involvement($trainerlist[$trainer], $availability));
                        }
                        ++$hasconflicts;
                    }
                }

                if (isset($this->_form->_types["trainerrole[{$roleid}]"]) && $trainererrors != '') {
                    $errors["trainerrole[{$roleid}]"] = $trainererrors;
                }
            }
            $facetoface = $DB->get_record('facetoface', array('id' => $facetofaceid));
            // Check if default role approval is selected.
            if ($facetoface->approvaltype == APPROVAL_ROLE && !in_array($facetoface->approvalrole, $selectedroleids)) {
                $rolenames = role_get_names($this->context);
                $errors['roleapprovalerror'] = get_string('error:rolerequired', 'facetoface', $rolenames[$facetoface->approvalrole]->localname);
            }
            // If there are conflicts, add a help message to checkbox
            if ($hasconflicts) {
                if ($hasconflicts > 1) {
                    $errors['allowconflicts'] = get_string('error:therearexconflicts', 'facetoface', $hasconflicts);
                } else {
                    $errors['allowconflicts'] = get_string('error:thereisaconflict', 'facetoface');
                }
            }
        }

        //check capcity is a number
        if (empty($data['capacity'])) {
            $errors['capacity'] = get_string('error:capacityzero', 'facetoface');
        } else {
            $capacity = $data['capacity'];
            if (!(is_numeric($capacity) && (intval($capacity) == $capacity) && $capacity > 0)) {
                $errors['capacity'] = get_string('error:capacitynotnumeric', 'facetoface');
            }
        }

        // Check the minimum bookings.
        $mincapacity = $data['mincapacity'];
        if (!is_numeric($mincapacity) || (intval($mincapacity) != $mincapacity)) {
            $errors['mincapacity'] = get_string('error:mincapacitynotnumeric', 'facetoface');
        } else if ($mincapacity > $data['capacity']) {
            $errors['mincapacity'] = get_string('error:mincapacitytoolarge', 'facetoface');
        }

        // Check the cut-off is at least the day before the earliest start time.
        if (!empty($data['sendcapacityemail'])) {
            // If the cutoff or the dates have changed check the cut-off is at least the day before the earliest start time.
            // We only want to run this validation if the cutoff period has changed, or if the dates have changed.
            $cutoff = $data['cutoff'];
            if (!isset($this->_customdata['session']->cutoff) || $this->_customdata['session']->cutoff != $cutoff || $dateschanged) {
                if ($cutoff < DAYSECS) {
                    $errors['cutoffdurationgroup'] = get_string('error:cutofftooclose', 'facetoface');
                } else {
                    $now = time();
                    foreach ($dates as $dateid => $date) {
                        $cutofftimestamp = $date->timestart - $cutoff;
                        if ($cutofftimestamp < $now) {
                            $errors['cutoffdurationgroup'] = get_string('error:cutofftoolate', 'facetoface');
                            break;
                        }
                    }
                }
            }
        }

        // Check that there is not booking conflicts for current attendees.
        if ($dates && !$allowconflicts) {
            $wheresql = '';
            $whereparams = array();
            if (!empty($this->_customdata['s'])) {
                $wheresql = ' AND s.id != ?';
                $whereparams[] = $this->_customdata['s'];
            }
            $currentattendees = facetoface_get_attendees($sessid);
            $conflictsdetails = facetoface_get_booking_conflicts($dates, $currentattendees, $wheresql, $whereparams);
            $conflictscount = count($conflictsdetails);
            if ($conflictscount > 0) {
                $a = new \stdClass();
                $a->users = $conflictscount;
                $a->link = \html_writer::link('#', get_string('viewdetails', 'facetoface'), array('id' => 'viewbookingconflictdetails', 'class' => 'viewbulkresults'));
                $errors['sessiondates'] = get_string('error:sessiondatesbookingconflict', 'facetoface', $a);
            }
        }

        // Process the data for a custom field and validate it.
        $errors += customfield_validation((object)$data, 'facetofacesession', 'facetoface_session');

        // Consolidate date errors.
        if (!empty($errdates)) {
            $errors['errors'] = implode(\html_writer::empty_tag('br'), $errdates);
        }
        return $errors;
    }

    /**
     * Prepare form data
     *
     * @param \stdClass $session Facetoface session
     * @param \stdClass $facetoface Facetoface instance
     * @param \stdClass $course Course
     * @param \context $context Context
     * @param int $cntdates Dates count
     * @param bool $clone A flag whether the session is being cloned from another session
     * @return array Prepared form data
     * @throws \coding_exception
     */
    public static function prepare_data($session, $facetoface, $course, $context, $cntdates, $clone = false) {
        global $TEXTAREA_OPTIONS;

        $defaulttimezone = '99';

        $editoroptions   = array(
            'noclean'  => false,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $course->maxbytes,
            'context'  => $context,
        );

        if (!isset($session)) {
            $sessiondata = new \stdClass();
            $sessiondata->id = 0;
            $sessiondata->allowcancellations = $facetoface->allowcancellationsdefault;
            $sessiondata->cancellationcutoff = $facetoface->cancellationscutoffdefault;
            $sessiondata->cntdates = $cntdates;
            $nbdays = 1;
        } else {
            if (!empty($session->sessiondates[0]->sessiontimezone) and $session->sessiondates[0]->sessiontimezone != '99') {
                $defaulttimezone = \core_date::normalise_timezone($session->sessiondates[0]->sessiontimezone);
            }

            $editoroptions = $TEXTAREA_OPTIONS;
            $editoroptions['context'] = $context;

            // Load custom fields data for the session.
            customfield_load_data($session, 'facetofacesession', 'facetoface_session');

            // Set values for the form and unset some values that will be evaluated later.
            $sessiondata = clone($session);
            if (isset($sessiondata->sessiondates)) {
                unset($sessiondata->sessiondates);
            }
            $sessiondata->detailsformat = FORMAT_HTML;
            $sessiondata = file_prepare_standard_editor($sessiondata, 'details', $editoroptions, $editoroptions['context'],
                'mod_facetoface', 'session', $session->id);
            // Let form know how many dates to process.
            if ($cntdates > $sessiondata->cntdates) {
                $sessiondata->cntdates = $cntdates;
            }

            $nbdays = count($session->sessiondates);
            if ($session->sessiondates) {
                $i = 0;
                foreach ($session->sessiondates as $date) {
                    $idfield = "sessiondateid[$i]";
                    $timestartfield = "timestart[$i]";
                    $timefinishfield = "timefinish[$i]";
                    $timezonefield = "sessiontimezone[$i]";
                    $roomidfield = "roomid[$i]";
                    $assetsfield = "assetids[$i]";

                    if ($date->sessiontimezone === '') {
                        $date->sessiontimezone = '99';
                    } else if ($date->sessiontimezone != 99) {
                        $date->sessiontimezone = \core_date::normalise_timezone($date->sessiontimezone);
                    }

                    if(!$clone) {
                        $sessiondata->$idfield = $date->id;
                    }

                    $sessiondata->$timestartfield = $date->timestart;
                    $sessiondata->$timefinishfield = $date->timefinish;
                    $sessiondata->$timezonefield = $date->sessiontimezone;
                    $sessiondata->$roomidfield = $date->roomid;
                    $sessiondata->$assetsfield = $date->assetids;

                    // NOTE: There is no need to remove rooms and assets
                    //       because form validation will not allow saving
                    //       and likely they will just change the date.

                    $i++;
                }
            }
        }
        return array($sessiondata, $editoroptions, $defaulttimezone, $nbdays);
    }

    public function process_data() {
        global $USER;

        if (!($fromform = $this->get_data())) { // Form submitted
            return null;
        }

        $session = $this->session;
        $facetoface = $this->facetoface;

        // Make sure user cannot cancel this page request. (Back luck IIS users!)
        ignore_user_abort();

        if (empty($fromform->submitbutton)) {
            print_error('error:unknownbuttonclicked', 'facetoface', $this->returnurl);
        }

        // Pre-process fields
        if (empty($fromform->allowoverbook)) {
            $fromform->allowoverbook = 0;
        }
        if (empty($fromform->waitlisteveryone)) {
            $fromform->waitlisteveryone = 0;
        }
        if (empty($fromform->normalcost)) {
            $fromform->normalcost = 0;
        }
        if (empty($fromform->discountcost)) {
            $fromform->discountcost = 0;
        }
        if (empty($fromform->selfapproval)) {
            $fromform->selfapproval = 0;
        }
        if ($fromform->mincapacity < 0) {
            $fromform->mincapacity = 0;
        }
        if (empty($fromform->sendcapacityemail)) {
            $fromform->sendcapacityemail = 0;
        }

        $todb = new \stdClass();
        $todb->cutoff     = $fromform->cutoff;
        $todb->capacity   = $fromform->capacity;
        $todb->normalcost = $fromform->normalcost;
        $todb->facetoface = $facetoface->id;
        $todb->mincapacity = $fromform->mincapacity;
        $todb->discountcost  = $fromform->discountcost;
        $todb->usermodified  = $USER->id;
        $todb->allowoverbook = $fromform->allowoverbook;
        $todb->waitlisteveryone  = $fromform->waitlisteveryone;
        $todb->sendcapacityemail = $fromform->sendcapacityemail;
        $todb->registrationtimestart  = $fromform->registrationtimestart;
        $todb->registrationtimefinish = $fromform->registrationtimefinish;

        // Do not change cancellation here!
        unset($fromform->cancelledstatus);

        $canconfigurecancellation = has_capability('mod/facetoface:configurecancellation', $this->context);
        if ($canconfigurecancellation) {
            $todb->allowcancellations = $fromform->allowcancellations;
            $todb->cancellationcutoff = $fromform->cancellationcutoff;
        } else {
            if ($session) {
                $todb->allowcancellations = $session->allowcancellations;
                $todb->cancellationcutoff = $session->cancellationcutoff;
            } else {
                $todb->allowcancellations = $facetoface->allowcancellationsdefault;
                $todb->cancellationcutoff = $facetoface->cancellationscutoffdefault;
            }
        }

        $this->fromform = $fromform;
        return $todb;
    }

    public function save($todb) {
        global $DB;

        $session = $this->session;
        $facetoface = $this->facetoface;
        $fromform =& $this->fromform;

        //check dates and calculate total duration
        $sessiondates = array();
        for ($i = 0; $i < $fromform->cntdates; $i++) {
            if (!empty($fromform->datedelete[$i])) {
                continue; // skip this date
            }
            if (!empty($fromform->timestart[$i]) && !empty($fromform->timefinish[$i])) {
                $date = new \stdClass();

                $date->id = isset($fromform->sessiondateid[$i]) ? $fromform->sessiondateid[$i] : null;
                $date->sessiontimezone = $fromform->sessiontimezone[$i];
                $date->timestart  = $fromform->timestart[$i];
                $date->timefinish = $fromform->timefinish[$i];
                $date->roomid   = $fromform->roomid[$i];
                $date->assetids = !empty($fromform->assetids[$i]) ? explode(',', $fromform->assetids[$i]) : array();
                $sessiondates[] = $date;
            }
        }

        $transaction = $DB->start_delegated_transaction();

        $update = false;
        // Cloning the session from the existing one.
        if (!$this->_customdata['c'] && (int)$session->id != 0) {
            $update = true;
            $todb->id  = $session->id;
            $sessionid = $session->id;
            $olddates  = $DB->get_records('facetoface_sessions_dates', array('sessionid' => $session->id), 'timestart');
            if (!facetoface_update_session($todb, $sessiondates)) {
                print_error('error:couldnotupdatesession', 'facetoface', $this->returnurl);
            }
        } else {
            // Create or Duplicate the session.
            if (!$sessionid = facetoface_add_session($todb, $sessiondates)) {
                print_error('error:couldnotaddsession', 'facetoface', $this->returnurl);
            }
        }

        $fromform->id = $sessionid;
        customfield_save_data($fromform, 'facetofacesession', 'facetoface_session');

        $transaction->allow_commit();

        // Retrieve record that was just inserted/updated.
        if (!$session = facetoface_get_session($sessionid)) {
            print_error('error:couldnotfindsession', 'facetoface', $this->returnurl);
        }

        if ($update) {
            // Now that we have updated the session record fetch the rest of the data we need.
            facetoface_update_attendees($session);
        }

        // Set session dates.
         $session->sessiondates = $sessiondates;

        // Get details.
        // This should be done before sending any notification as it could be a required field in their template.
        $data = file_postupdate_standard_editor($fromform, 'details', $this->editoroptions, $this->context, 'mod_facetoface', 'session', $session->id);
        $session->details = $data->details;
        $DB->set_field('facetoface_sessions', 'details', $data->details, array('id' => $session->id));

        // Save trainer roles.
        if (isset($fromform->trainerrole)) {
            facetoface_update_trainers($facetoface, $session, $fromform->trainerrole);
        }

        // Save any calendar entries.
        facetoface_update_calendar_entries($session, $facetoface);

        if ($update) {
            // Send any necessary datetime change notifications but only if date/time is known.
            if (!empty($sessiondates) && facetoface_session_dates_check($olddates, $sessiondates)) {
                $attendees = facetoface_get_attendees($session->id);
                foreach ($attendees as $user) {
                    // Checking sign-up status here to determine whether to include iCal attachment or not.
                    $invite = $user->statuscode != MDL_F2F_STATUS_WAITLISTED;

                    facetoface_send_datetime_change_notice($facetoface, $session, $user->id, $olddates, $invite);
                }
                $sessiontrainers = facetoface_get_trainers($session->id);
                if (!empty($sessiontrainers)) {
                    foreach ($sessiontrainers as $roleid => $trainers) {
                        foreach ($trainers as $trainer) {
                            facetoface_send_datetime_change_notice($facetoface, $session, $trainer->id, $olddates);
                        }
                    }
                }
            }
            \mod_facetoface\event\session_updated::create_from_session($session, $this->context)->trigger();
        } else {
            \mod_facetoface\event\session_created::create_from_session($session, $this->context)->trigger();
        }
    }
}
