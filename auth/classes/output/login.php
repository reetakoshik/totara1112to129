<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Login renderable.
 *
 * @package    core_auth
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_auth\output;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_system;
use help_icon;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Login renderable class.
 *
 * @package    core_auth
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class login implements renderable, templatable {

    /** @var bool Whether to auto focus the form fields. */
    public $autofocusform;
    /** @var bool Whether we can login as guest. */
    public $canloginasguest;
    /** @var bool Whether we can login by e-mail. */
    public $canloginbyemail;
    /** @var bool Whether we can sign-up. */
    public $cansignup;
    /** @var help_icon The cookies help icon. */
    public $cookieshelpicon;
    /** @var string The error message, if any. */
    public $error;
    /** @var moodle_url Forgot password URL. */
    public $forgotpasswordurl;
    /** @var array Additional identify providers, contains the keys 'url', 'name' and 'icon'. */
    public $identityproviders;
    /** @var string Login instructions, if any. */
    public $instructions;
    /** @var moodle_url The form action login URL. */
    public $loginurl;
    /** @var bool Whether the username should be remembered. */
    public $rememberusername = false;
    /** @var bool Whether the "remember me" option was selected. */
    public $rememberusernamechecked = false;
    /** @var string label for checkbox */
    public $rememberusernamelabel;
    /** @var moodle_url The sign-up URL. */
    public $signupurl;
    /** @var string The user name to pre-fill the form with. */
    public $username;

    /**
     * Constructor.
     *
     * @param array $authsequence The enabled sequence of authentication plugins.
     * @param stdClass $frm raw data to override defaults.
     */
    public function __construct(array $authsequence, $frm=null) {
        global $CFG, $SESSION;

        if (is_string($frm)) {
            // This is a call from Moodle or a Moodle plugin.
            debugging('Please update your call to new \core_auth\output\login() to pass the data object instead of the username', DEBUG_DEVELOPER);
            $username = $frm;
            $frm = new stdClass;
            $frm->username = $username;
            unset($username);
        }

        $this->username = isset($frm->username) ? $frm->username : '';

        $this->canloginasguest = $CFG->guestloginbutton and !isguestuser();
        $this->canloginbyemail = !empty($CFG->authloginviaemail);
        $this->cansignup = $CFG->registerauth == 'email' || !empty($CFG->registerauth);
        $this->cookieshelpicon = new help_icon('cookiesenabled', 'core');

        $this->autofocusform = !empty($CFG->loginpageautofocus);

        if (!empty($CFG->persistentloginenable)) {
            $this->rememberusername = true;
            $this->rememberusernamelabel = get_string('persistentloginlabel', 'totara_core');
        } else if (isset($CFG->rememberusername) and $CFG->rememberusername == 2) {
            $this->rememberusername = true;
            $this->rememberusernamelabel = get_string('rememberusername', 'admin');
        }
        $this->rememberusernamechecked = !empty($frm->rememberusernamechecked);

        $this->forgotpasswordurl = new moodle_url('/login/forgot_password.php');
        $this->loginurl = new moodle_url('/login/index.php');
        $this->signupurl = new moodle_url('/login/signup.php');

        // Authentication instructions.
        $this->instructions = $CFG->auth_instructions;
        if (is_enabled_auth('none')) {
            $this->instructions = get_string('loginstepsnone');
        } else if ($CFG->registerauth == 'email' && empty($this->instructions)) {
            $this->instructions = get_string('loginsteps', 'core', 'signup.php');
        } else if ($CFG->registerauth && empty($this->instructions)) {
            if (get_string_manager()->string_exists('loginsteps', 'auth_' . $CFG->registerauth)) {
                $this->instructions = get_string('loginsteps', 'auth_' . $CFG->registerauth, 'signup.php');
            }
        }

        // Identity providers.
        $this->identityproviders = \auth_plugin_base::get_identity_providers($authsequence);
    }

    /**
     * Set the error message.
     *
     * @param string $error The error message.
     */
    public function set_error($error) {
        $this->error = $error;
    }

    public function export_for_template(renderer_base $output) {
        global $CFG;

        $identityproviders = \auth_plugin_base::prepare_identity_providers_for_output($this->identityproviders, $output);

        $data = new stdClass();
        $data->autofocusform = $this->autofocusform;
        $data->canloginasguest = $this->canloginasguest;
        $data->canloginbyemail = $this->canloginbyemail;
        $data->cansignup = $this->cansignup;
        $data->cookieshelpicon = $this->cookieshelpicon->export_for_template($output);
        $data->error = $this->error;
        $data->forgotpasswordurl = $this->forgotpasswordurl->out(false);
        $data->hasidentityproviders = !empty($this->identityproviders);
        $data->hasinstructions = !empty($this->instructions);
        $data->identityproviders = $identityproviders;
        $context = context_system::instance();
        $options = array('noclean' => true, 'filter' => true, 'context' => $context);
        list($data->instructions, $data->instructionsformat) = external_format_text($this->instructions, FORMAT_HTML,
            $context->id, null, null, null, $options);
        $data->loginurl = $this->loginurl->out(false);
        $data->rememberusername = $this->rememberusername;
        $data->rememberusernamechecked = $this->rememberusernamechecked;
        $data->rememberusernamelabel = $this->rememberusernamelabel;
        $data->signupurl = $this->signupurl->out(false);
        $data->username = $this->username;
        $data->skiplinktext = get_string('skipa', 'access', get_string('login', 'core'));
        // Totara: add CSRF protection
        $data->logintoken = sesskey();

        return $data;
    }
}
