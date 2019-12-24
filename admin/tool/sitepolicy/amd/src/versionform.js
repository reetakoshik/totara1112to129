/*
 * This file is part of Totara LMS
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

define(['jquery'], function($) {
    var versionform = {

        /**
         * module initialisation method called by php js_init_call()
         */
        init: function() {
            // Hide heading and required-notes on preview
            var ispreview = $('#tfiid_preview_tool_sitepolicy_form_versionform').val();
            if (ispreview == '1') {
                $('#tf_fid_tool_sitepolicy_form_versionform').prev('h2').hide();
                $('.totara_form-required_note').hide();
            }


            $('#tfiid_previewbutton_tool_sitepolicy_form_versionform').on('click', function(event) {
                // We need to continue with the submit on preview in order to show the latest data
                $('#tfiid_preview_tool_sitepolicy_form_versionform').val('1');
            });

            $('#tfiid_continuebutton_tool_sitepolicy_form_versionform').on('click', function(event) {
                // No need complete submit - preview mode doesn't change data
                event.preventDefault();
                $('#tfiid_preview_tool_sitepolicy_form_versionform').val('');
                $('#tfiid_preview_tool_sitepolicy_form_versionform').trigger('change');

                // Show heading and required-notes again
                $('#tf_fid_tool_sitepolicy_form_versionform').prev('h2').show();
                $('.totara_form-required_note').show();
            });
        }
    };

    return versionform;
});
