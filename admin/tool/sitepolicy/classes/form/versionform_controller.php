<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\form;

defined('MOODLE_INTERNAL') || die();

use totara_form\form_controller;
use tool_sitepolicy\localisedpolicy;

/**
 * Controller for version form
 **/
class versionform_controller extends form_controller {

    /** @var \tool_sitepolicy\form\versionform $form */
    protected $form;

    /**
     * This method is responsible for:
     *  - access control
     *  - getting of current data
     *  - getting of parameters
     *
     * and returning of the form instance.
     *
     * @param string $idsuffix string extra for identifier to allow repeated forms on one page
     * @return \tool_sitepolicy\form\versionform
     */
    public function get_ajax_form_instance($idsuffix) {
        // Access control first.
        require_login();
        require_sesskey();
        $syscontext = \context_system::instance();
        require_capability('moodle/site:config', $syscontext);

        $localisedpolicyid = optional_param('localisedpolicy', 0, PARAM_INT);
        $versionnumber = optional_param('versionnumber', 0, PARAM_INT);
        $ret = optional_param('ret', '', PARAM_TEXT);

        if (empty($localisedpolicyid)) {
            // Adding statements to new policy that haven't yet been persisted
            $currentdata = ['localisedpolicy' => $localisedpolicy, 'versionnumber' => $versionnumber, 'ret' => $ret];
            $params = [
                'hidden' => [
                    'localisedpolicy' => PARAM_INT,
                    'policyversionid' => PARAM_INT,
                    'ret' => PARAM_TEXT],
            ];
        } else {
            // When persisted previously, we need the full set of hidden data
            $localisedpolicy = new localisedpolicy($localisedpolicyid);
            [$currentdata, $params] = versionform::prepare_current_data($localisedpolicy, false, $ret);
            $currentdata['versionnumber'] = $versionnumber;
        }

        // Create the form instance.
        $this->form = new versionform($currentdata, $params, $idsuffix);
        return $this->form;
    }

    /**
     * Process the submitted form.
     *
     * @return array processed data
     */
    public function process_ajax_data() {
        $result = array();
        $result['data'] = (array)$this->form->get_data();
        $result['files'] = array();
        return $result;
    }

}