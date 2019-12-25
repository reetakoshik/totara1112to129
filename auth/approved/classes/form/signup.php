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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package auth_approved
 */

namespace auth_approved\form;

defined('MOODLE_INTERNAL') || die();

use \auth_approved\request;

global $CFG;
require_once("$CFG->libdir/formslib.php");

/**
 * User sign-up request form.
 */
final class signup extends \moodleform {
    /**
     * @var int the mode of the form - see request::STAGE_SIGNUP and request::STAGE_APPROVAL constants.
     */
    protected $stage;

    /** @var \auth_plugin_approved */
    protected $authplugin;

    public function definition() {
        global $CFG;
        require_once("$CFG->dirroot/user/profile/lib.php");
        require_once("$CFG->dirroot/user/editlib.php");
        require_once("$CFG->dirroot/totara/job/lib.php");

        $mform = $this->_form;

        $this->authplugin = get_auth_plugin('approved');
        $requestid = (isset($this->_customdata['requestid'])) ? $this->_customdata['requestid'] : null;
        $this->stage = $this->_customdata['stage'];
        $managerjaoptions = (isset($this->_customdata['managerjaoptions'])) ? $this->_customdata['managerjaoptions'] : [];

        $instructions = get_config('auth_approved', 'instructions');
        if ($instructions) {
            $mform->addElement('header', 'instructions', get_string('instructions', 'auth_approved'));
            $mform->addElement('html',\html_writer::tag('div', $instructions, array('class' => 'auth_approved-instructions')));
        }

        $mform->addElement('header', 'createuserandpass', get_string('createuserandpass'), '');
        $mform->setExpanded('createuserandpass', true, true);

        $mform->addElement('text', 'username', get_string('username'), 'maxlength="100" size="12"');
        $mform->setType('username', PARAM_RAW); // Rely on validation to check username format!
        $mform->addRule('username', get_string('missingusername'), 'required', null, 'client');

        if ($this->stage == request::STAGE_SIGNUP) {
            if (!empty($CFG->passwordpolicy)){
                $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
            }
            $mform->addElement('passwordunmask', 'password', get_string('password'), 'maxlength="32" size="12"');
            $mform->setType('password', PARAM_RAW);
            $mform->addRule('password', get_string('missingpassword'), 'required', null, 'client');
        }

        $mform->addElement('header', 'supplyinfo', get_string('supplyinfo'), '');
        $mform->setExpanded('supplyinfo', true, true);

        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="25"');
        $mform->setType('email', PARAM_RAW); // Rely on validation to check the email format!
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'client');

        $namefields = useredit_get_required_name_fields();
        foreach ($namefields as $field) {
            $mform->addElement('text', $field, get_string($field), 'maxlength="100" size="30"');
            $mform->setType($field, PARAM_NOTAGS);
            if ($this->stage == request::STAGE_SIGNUP or $field === 'firstname' or $field === 'lastname') {
                $stringid = 'missing' . $field;
                if (!get_string_manager()->string_exists($stringid, 'moodle')) {
                    $stringid = 'required';
                }
                $mform->addRule($field, get_string($stringid), 'required', null, 'client');
            }
        }

        $mform->addElement('text', 'city', get_string('city'), 'maxlength="120" size="20"');
        $mform->setType('city', PARAM_NOTAGS);

        $country = array('' => get_string('selectacountry')) + get_string_manager()->get_list_of_countries();
        $mform->addElement('select', 'country', get_string('country'), $country);

        if ($this->stage == request::STAGE_SIGNUP) {
            $mform->addElement('hidden', 'lang');
            $mform->setType('lang', PARAM_LANG);
        } else {
            $mform->addElement('select', 'lang', get_string('preferredlanguage'), get_string_manager()->get_list_of_translations());
        }

        if ($this->stage == request::STAGE_SIGNUP and $this->authplugin->is_captcha_enabled()) {
            $mform->addElement('recaptcha', 'recaptcha_element', get_string('security_question', 'auth'));
            $mform->addHelpButton('recaptcha_element', 'recaptcha', 'auth');
            $mform->closeHeaderBefore('recaptcha_element');
        }

        // Manage organisations in signup self-registration.
        $this->signup_organisation();

        // Manage positions in signup self-registration.
        $this->signup_position();

        // Manage managers in signup self-registration.
        $this->signup_manager($managerjaoptions);

        // Profile fields have category headers, better put them at the end.
        profile_signup_fields($mform);

        if ($this->stage == request::STAGE_SIGNUP and !empty($CFG->sitepolicy)) {
            $mform->addElement('header', 'policyagreement', get_string('policyagreement'), '');
            $mform->setExpanded('policyagreement');
            $mform->addElement('static', 'policylink', '', '<a href="'.$CFG->sitepolicy.'" onclick="this.target=\'_blank\'">'.get_String('policyagreementclick').'</a>');
            $mform->addElement('checkbox', 'policyagreed', get_string('policyaccept'));
            $mform->addRule('policyagreed', get_string('policyagree'), 'required', null, 'client');
        }

        // Let's make this look like new user record at all times.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', 0);

        // Add hidden request id field so that approvers may edit the sign up request using this form too.
        $mform->addElement('hidden', 'requestid');
        $mform->setType('requestid', PARAM_INT);
        if ($requestid) {
            $mform->setConstant('requestid', $requestid);
        }

        if ($this->stage == request::STAGE_SIGNUP) {
            $this->add_action_buttons(true, get_string('createrequest', 'auth_approved'));
        } else {
            $this->add_action_buttons(true, get_string('savechanges'));
        }
    }

    public function definition_after_data() {
        global $DB;
        $mform = $this->_form;

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }

        if ($this->stage == request::STAGE_APPROVAL) {
            // Fix job values now because we did not know them earlier.

            $positionid = $mform->getElementValue('positionid');
            if ($positionid) {
                $positiontitle = $DB->get_field('pos', 'fullname', array('id' => $positionid));
                $positiontitle = empty($positiontitle) ? get_string('errordeletedposition', 'auth_approved') : format_string($positiontitle);

                /** @var \MoodleQuickForm_static $positionselector */
                $positionselector = $mform->getElement('positionselector');
                if (has_capability('totara/hierarchy:assignuserposition', \context_system::instance())) {
                    $positionselector->setText(
                        \html_writer::tag('span', $positiontitle, array('class' => 'nonempty', 'id' => 'positiontitle')).
                        \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseposition', 'totara_job'), 'id' => 'show-position-dialog'))
                    );
                }
                else {
                    $positionselector->setText(
                        \html_writer::tag('span', $positiontitle, array('class' => 'nonempty', 'id' => 'positiontitle')).
                        \html_writer::tag('span', get_string('errornopermissiontoselectposition', 'auth_approved'), array('class' => 'error', 'id' => 'positiontitle'))
                    );
                }
            }

            $organisationid = $mform->getElementValue('organisationid');
            if ($organisationid) {
                $organisationtitle = $DB->get_field('org', 'fullname', array('id' => $organisationid));
                $organisationtitle = empty($organisationtitle) ? get_string('errordeletedorganisation', 'auth_approved') : format_string($organisationtitle);

                /** @var \MoodleQuickForm_static $organisationselector */
                $organisationselector = $mform->getElement('organisationselector');
                if (has_capability('totara/hierarchy:assignuserposition', \context_system::instance())) {
                    $organisationselector->setText(
                        \html_writer::tag('span', $organisationtitle, array('class' => 'nonempty', 'id' => 'organisationtitle')).
                        \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseorganisation', 'totara_job'), 'id' => 'show-organisation-dialog'))
                    );
                }
                else {
                    $organisationselector->setText(
                        \html_writer::tag('span', $organisationtitle, array('class' => 'nonempty', 'id' => 'positiontitle')).
                        \html_writer::tag('span', get_string('errornopermissiontoselectorganisation', 'auth_approved'), array('class' => 'error', 'id' => 'positiontitle'))
                    );
                }
            }

            $managerjaid = $mform->getElementValue('managerjaid');
            if ($managerjaid) {
                $managerja = \totara_job\job_assignment::get_with_id($managerjaid);
                // Get the fields required to display the name of a user.
                $usernamefields = get_all_user_name_fields(true);
                $manager = $DB->get_record('user', array('id' => $managerja->userid), $usernamefields);

                // Get the manager name.
                $managername = empty($manager) ? get_string('errordeletedmanager', 'auth_approved') : totara_job_display_user_job($manager, $managerja, true);

                /** @var \MoodleQuickForm_static $managerselector */
                $managerselector = $mform->getElement('managerselector');
                if (has_capability('totara/hierarchy:assignuserposition', \context_system::instance())) {
                    $managerselector->setText(
                        \html_writer::tag('span', $managername, array('class' => 'nonempty', 'id' => 'managertitle')) .
                        \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('choosemanager', 'totara_job'), 'id' => 'show-manager-dialog'))
                    );}
                else {
                    $managerselector->setText(
                        \html_writer::tag('span', $managername, array('class' => 'nonempty', 'id' => 'positiontitle')).
                        \html_writer::tag('span', get_string('errornopermissiontoselectmanager', 'auth_approved'), array('class' => 'error', 'id' => 'positiontitle'))
                    );
                }
            }
        }

        profile_definition_after_data($mform, 0);
    }

    public function validation($data, $files) {
        if ($this->stage == request::STAGE_SIGNUP and $this->authplugin->is_captcha_enabled()) {
            // Captcha is a special case, do not do anything else if it fails.
            $errors = array();
            $recaptchaelement = $this->_form->getElement('recaptcha_element');
            if (!empty($this->_form->_submitValues['g-recaptcha-response'])) {
                $response = $this->_form->_submitValues['g-recaptcha-response'];
                if (!$recaptchaelement->verify($response)) {
                    $errors['recaptcha_element'] = get_string('incorrectpleasetryagain', 'auth');
                }
            } else {
                $errors['recaptcha_element'] = get_string('missingrecaptchachallengefield');
            }

            if ($errors) {
                return $errors;
            }
        }

        $errors = parent::validation($data, $files);
        $errors += request::validate_signup_form_data($data, $this->stage);

        return $errors;
    }

    private function signup_position() {
        $mform = $this->_form;

        if ($this->stage == request::STAGE_APPROVAL) {
            // Do not restrict options at all for approver and show always.
            $mform->addElement('static', 'positionselector', get_string('positionselect', 'auth_approved'),
                \html_writer::tag('span', '', array('class' => '', 'id' => 'positiontitle')).
                \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseposition', 'totara_job'), 'id' => 'show-position-dialog'))
            );

            $mform->addElement('hidden', 'positionid');
            $mform->setType('positionid', PARAM_INT);
            $mform->addHelpButton('positionselector', 'chooseposition', 'totara_job');

            // Note: we cannot set required rule here thanks to the old forms limitations,
            //       we have to rely on server side validation only.
            if (get_config('auth_approved', 'allowpositionfreetext')) {
                $mform->addElement('text', 'positionfreetext', get_string('positionfreetext', 'auth_approved'), 'size="60"');
                $mform->setType('positionfreetext', PARAM_NOTAGS);
                $mform->hardFreeze('positionfreetext');
            }
            return;
        }

        $freeformallowed = get_config('auth_approved', 'allowpositionfreetext');
        $selectionallowed = get_config('auth_approved', 'allowposition');
        $positionrequired = get_config('auth_approved', 'requireposition');

        if ($selectionallowed) {
            $positions = $this->hierarchies_in_frameworks('positionframeworks', 'pos', 'pos_framework');
            $mform->addElement('selectgroups', 'positionid', get_string('positionselect', 'auth_approved'), $positions, null, true);

            if ($positionrequired) {
                if ($freeformallowed) {
                    $mform->addElement('static', 'reqdpos', '', get_string('positioneitherselectionorfreeformrequired', 'auth_approved'));
                }
                else {
                    $mform->addRule('positionid', get_string('errormissingpos', 'auth_approved'), 'required', null, 'client');
                }
            }

            if (!$freeformallowed && $this->stage != request::STAGE_APPROVAL) {
                $mform->addElement('static', 'cannotfindpos', '', get_string('cannotfindpos', 'auth_approved', \core_user::get_support_user()->email));
            }
        }

        if ($freeformallowed) {
            $mform->addElement('text', 'positionfreetext', get_string('positionfreetext', 'auth_approved'), 'size="60"');
            $mform->setType('positionfreetext', PARAM_NOTAGS);

            if ($positionrequired) {
                if ($selectionallowed) {
                    $mform->addElement('static', 'reqdposf', '', get_string('positioneitherselectionorfreeformrequired', 'auth_approved'));
                }
                else {
                    $mform->addRule('positionfreetext', get_string('errormissingpos', 'auth_approved'), 'required', null, 'client');
                }
            }
        }
    }

    private function signup_organisation() {
        $mform = $this->_form;

        if ($this->stage == request::STAGE_APPROVAL) {
            // Do not restrict options at all for approver and show always.

            $mform->addElement('static', 'organisationselector', get_string('organisationselect', 'auth_approved'),
                \html_writer::tag('span', '', array('class' => '', 'id' => 'organisationtitle')) .
                \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseorganisation', 'totara_job'), 'id' => 'show-organisation-dialog'))
            );

            $mform->addElement('hidden', 'organisationid');
            $mform->setType('organisationid', PARAM_INT);
            $mform->addHelpButton('organisationselector', 'chooseorganisation', 'totara_job');

            // Note: we cannot set required rule here thanks to the old forms limitations,
            //       we have to rely on server side validation only.
            if (get_config('auth_approved', 'alloworganisationfreetext')) {
                $mform->addElement('text', 'organisationfreetext', get_string('organisationfreetext', 'auth_approved'), 'size="60"');
                $mform->setType('organisationfreetext', PARAM_NOTAGS);
                $mform->hardFreeze('organisationfreetext');
            }
            return;
        }

        $freeformallowed = get_config('auth_approved', 'alloworganisationfreetext');
        $selectionallowed = get_config('auth_approved', 'alloworganisation');
        $orgrequired = get_config('auth_approved', 'requireorganisation');

        if ($selectionallowed) {
            $organisations = $this->hierarchies_in_frameworks('organisationframeworks', 'org', 'org_framework');
            $mform->addElement('selectgroups', 'organisationid', get_string('organisationselect', 'auth_approved'), $organisations, null, true);

            if ($orgrequired) {
                if ($freeformallowed) {
                    $mform->addElement('static', 'reqorg', '', get_string('organisationeitherselectionorfreeformrequired', 'auth_approved'));
                }
                else {
                    $mform->addRule('organisationid', get_string('errormissingorg', 'auth_approved'), 'required', null, 'client');
                }
            }

            if (!$freeformallowed && $this->stage != request::STAGE_APPROVAL) {
                $mform->addElement('static', 'cannotfindorg', '', get_string('cannotfindorg', 'auth_approved', \core_user::get_support_user()->email));
            }
        }

        if ($freeformallowed) {
            $mform->addElement('text', 'organisationfreetext', get_string('organisationfreetext', 'auth_approved'), 'size="60"');
            $mform->setType('organisationfreetext', PARAM_NOTAGS);

            if ($orgrequired) {
                if ($selectionallowed) {
                    $mform->addElement('static', 'reqdorgf', '', get_string('organisationeitherselectionorfreeformrequired', 'auth_approved'));
                }
                else {
                    $mform->addRule('organisationfreetext', get_string('errormissingorg', 'auth_approved'), 'required', null, 'client');
                }
            }
        }
    }

    private function hierarchies_in_frameworks($cfgkey, $table, $tablefw) {
        global $DB;

        $params = array();
        $select = sprintf('
            SELECT h.id, h.fullname, hf.fullname AS fw_fullname
              FROM {%s} h
              JOIN {%s} hf on h.frameworkid = hf.id
             WHERE h.visible = 1
        ', $table, $tablefw);

        $frameworks = get_config('auth_approved', $cfgkey);
        $frameworks = empty($frameworks) ? [] : explode(',', $frameworks);

        if (!empty($frameworks) && !in_array(-1, $frameworks)) {
            list($fws, $fwp) = $DB->get_in_or_equal($frameworks, SQL_PARAMS_NAMED, 'fw', true, '-2'); // Imposible value if no frameworks.
            $params += $fwp;
            $select .= " AND frameworkid $fws";
        }
        $hierarchies = $DB->get_records_sql($select, $params);

        return array_reduce(
            $hierarchies,

            function (array $existing, \stdClass $hierarchy) {
                $fw = $hierarchy->fw_fullname;
                $entries = array_key_exists($fw, $existing) ? $existing[$fw] : [];

                $entries[$hierarchy->id] = $hierarchy->fullname;
                $existing[$fw] = $entries;

                return $existing;
            },

            []
        );
    }

    private function signup_manager($managerjaoptions) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/job/lib.php');
        $mform = $this->_form;

        if ($this->stage == request::STAGE_APPROVAL) {
            // Do not restrict options at all for approver and show always.

            $mform->addElement('static',  'managerselector', get_string('managerselect', 'auth_approved'),
                \html_writer::tag('span', '', array('class' => '', 'id' => 'managertitle')) .
                \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('choosemanager', 'totara_job'), 'id' => 'show-manager-dialog'))
            );

            $mform->addElement('hidden', 'managerjaid');
            $mform->setType('managerjaid', PARAM_INT);
            $mform->addHelpButton('managerselector', 'choosemanager', 'totara_job');

            // Note: we cannot set required rule here thanks to the old forms limitations,
            //       we have to rely on server side validation only.
            if (get_config('auth_approved', 'allowmanagerfreetext')) {
                $mform->addElement('text', 'managerfreetext', get_string('managerfreetext', 'auth_approved'), 'size="60"');
                $mform->setType('managerfreetext', PARAM_NOTAGS);
                $mform->hardFreeze('managerfreetext');
            }
            return;
        }

        $freeformallowed = get_config('auth_approved', 'allowmanagerfreetext');
        $selectionallowed = get_config('auth_approved', 'allowmanager');
        $managerrequired = get_config('auth_approved', 'requiremanager');

        if ($selectionallowed) {
            $args = array(
                'noselectionstring' => get_string('nomanagerselected', 'auth_approved'),
                'showsuggestions' => True,
                'placeholder' => get_string('searchformanager', 'auth_approved'),
                'tags' => false,
                'ajax' => 'auth_approved/manager-selector',
            );
            $mform->addElement('autocomplete', 'managerjaid', get_string('managerselect', 'auth_approved'), $managerjaoptions, $args);

            if ($managerrequired) {
                if ($freeformallowed) {
                    $mform->addElement('static', 'reqmgr', '', get_string('managereitherselectionorfreeformrequired', 'auth_approved'));
                }
                else {
                    $mform->addRule('managerjaid', get_string('errormissingmgr', 'auth_approved'), 'required', null, 'client');
                }
            }

            if (!$freeformallowed && $this->stage != request::STAGE_APPROVAL) {
                $mform->addElement('static', 'cannotfindmgr', '', get_string('cannotfindmgr', 'auth_approved', \core_user::get_support_user()->email));
            }
        }

        if ($freeformallowed) {
            $mform->addElement('text', 'managerfreetext', get_string('managerfreetext', 'auth_approved'), 'size="60"');
            $mform->setType('managerfreetext', PARAM_NOTAGS);

            if ($managerrequired) {
                if ($selectionallowed) {
                    $mform->addElement('static', 'reqdmgrf', '', get_string('managereitherselectionorfreeformrequired', 'auth_approved'));
                }
                else {
                    $mform->addRule('managerfreetext', get_string('errormissingmgr', 'auth_approved'), 'required', null, 'client');
                }
            }
        }
    }
}
