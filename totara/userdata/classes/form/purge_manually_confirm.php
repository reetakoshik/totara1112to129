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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\form;

use totara_userdata\userdata\manager;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Confirmation of manual purging request.
 */
final class purge_manually_confirm extends \totara_form\form {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $DB, $OUTPUT, $PAGE;
        $currentdata = (object)$this->model->get_current_data(null);

        $user = $DB->get_record('user', array('id' => $currentdata->id));
        $purgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $currentdata->purgetypeid), '*', MUST_EXIST);

        /** @var \totara_userdata_renderer $renderer */
        $renderer = $PAGE->get_renderer('totara_userdata');

        $targetuser = new target_user($user);
        $options = manager::get_purge_types($targetuser->status, 'manual');

        $userdetailshtml = $renderer->heading(get_string('userdetails'), 3);
        $userdetailshtml .= '<dl class="dl-horizontal">' . $renderer->user_id_card($user, true, false);
        $userdetailshtml .= '<dt>' .get_string('purgetype', 'totara_userdata') .'</dt>';
        $userdetailshtml .= '<dd>' . $options[$currentdata->purgetypeid] . '</dd></dl>';

        $userdetails = new \totara_form\form\element\static_html('purgetypestatic', '', $userdetailshtml);
        $this->model->add($userdetails);

        $datatopurgehtml = $renderer->heading(get_string('purgeitemselection', 'totara_userdata'), 3);
        $datatopurgehtml .= markdown_to_html(get_string('purgemanuallyfollowingwillbe', 'totara_userdata'));
        $datatopurgehtml .= $renderer->purge_type_active_items($purgetype);

        $datatopurge = new \totara_form\form\element\static_html('datatopurge', '', $datatopurgehtml);
        $this->model->add($datatopurge);

        $confirmhtml = \html_writer::tag('strong', get_string('purgemanuallyareyousure', 'totara_userdata'));
        $confirmstatic = new \totara_form\form\element\static_html('confirmstatic', '', $confirmhtml);
        $this->model->add($confirmstatic);

        $this->model->add_action_buttons(true, get_string('purgemanuallyproceed', 'totara_userdata'));

        $this->model->add(new \totara_form\form\element\hidden('id', PARAM_INT));
        $this->model->add(new \totara_form\form\element\hidden('purgetypeid', PARAM_INT));
    }
}
