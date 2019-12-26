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
 * Login block
 *
 * @package   block_login
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_login extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_login');
    }

    function applicable_formats() {
        return array('site' => true);
    }

    function get_content () {
        global $USER, $CFG, $SESSION, $OUTPUT;
        $wwwroot = '';
        $signup = '';

        if ($this->content !== NULL) {
            return $this->content;
        }

        $wwwroot = $CFG->wwwroot;

        if (!empty($CFG->registerauth)) {
            $authplugin = get_auth_plugin($CFG->registerauth);
            if ($authplugin->can_signup()) {
                $signup = $wwwroot . '/login/signup.php';
            }
        }
        // TODO: now that we have multiauth it is hard to find out if there is a way to change password
        $forgot = $wwwroot . '/login/forgot_password.php';


        $username = get_moodle_cookie();

        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text = '';

        if (!isloggedin() or isguestuser()) {   // Show the block
            if (empty($CFG->authloginviaemail)) {
                $strusername = get_string('username');
            } else {
                $strusername = get_string('usernameemail');
            }

            $this->content->text .= "\n".'<form id="login" method="post" action="'.get_login_url().'"><div class="loginform">';

            $this->content->text .= '<div class="form-label"><label for="login_username">'.$strusername.'</label></div>';
            $this->content->text .= '<div class="form-input"><input type="text" name="username" id="login_username" value="'.s($username).'" /></div>';
            $this->content->text .= '<div class="clearer"><!-- --></div>';
            $this->content->text .= '<div class="form-label"><label for="login_password">'.get_string('password').'</label></div>';
            $this->content->text .= '<div class="form-input"><input type="password" name="password" id="login_password" value="" /></div>';
            $this->content->text .= '<div class="clearer"><!-- --></div>';

            $rememberme = false;
            $remembermelabel = null;
            $checked = '';
            if (!empty($CFG->persistentloginenable)) {
                $rememberme = true;
                $remembermelabel = get_string('persistentloginlabel', 'totara_core');
            } else if (isset($CFG->rememberusername) and $CFG->rememberusername == 2) {
                $rememberme = true;
                $remembermelabel = get_string('rememberusername', 'admin');
                if ($username) {
                    $checked = 'checked="checked"';
                }
            }

            if ($rememberme) {
                $this->content->text .= '<div class="rememberpass">';
                $this->content->text .= '<input type="checkbox" name="rememberusernamechecked" id="rememberusernamechecked"
                        class="form-check-input" value="1" '.$checked.'/> ';
                $this->content->text .= '<label for="rememberusernamechecked">';
                $this->content->text .= $remembermelabel.'</label>';
                $this->content->text .= '</div>';
                $this->content->text .= '<div class="clearer"><!-- --></div>';
            }

            $this->content->text .= '<input type="submit" class="btn btn-primary btn-block" value="'.get_string('login').'" />';
            $this->content->text .= '<input type="hidden" name="logintoken" value="' . s(sesskey()) . '" />'; // Totara: add CSRF protection.
            $this->content->text .= '</div>';

            $this->content->text .= "</form>\n";

            if (!empty($signup)) {
                $this->content->text .= '<div><a href="'.$signup.'">'.get_string('startsignup').'</a></div>';
            }
            if (!empty($forgot)) {
                $this->content->text .= '<div><a href="'.$forgot.'">'.get_string('forgotaccount').'</a></div>';
            }

            $authsequence = get_enabled_auth_plugins(true); // Get all auths, in sequence.
            $potentialidps = array();
            foreach ($authsequence as $authname) {
                $authplugin = get_auth_plugin($authname);
                $potentialidps = array_merge($potentialidps, $authplugin->loginpage_idp_list($this->page->url->out(false)));
            }

            if (!empty($potentialidps)) {
                $this->content->text .= '<div class="potentialidps">';
                $this->content->text .= '<h6>' . get_string('potentialidps', 'auth') . '</h6>';
                $this->content->text .= '<div class="potentialidplist">';
                foreach ($potentialidps as $idp) {
                    $this->content->text .= '<div class="potentialidp">';
                    $this->content->text .= '<a class="btn btn-default btn-block" ';
                    $this->content->text .= 'href="' . $idp['url']->out() . '" title="' . s($idp['name']) . '">';
                    $this->content->text .= $OUTPUT->render($idp['icon'], $idp['name']) . s($idp['name']) . '</a></div>';
                }
                $this->content->text .= '</div>';
                $this->content->text .= '</div>';
            }
        }

        return $this->content;
    }
}
