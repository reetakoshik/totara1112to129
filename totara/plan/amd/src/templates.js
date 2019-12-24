/**
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
 * @author Brian Barnes <brian.barnes@totaralms.com>
 * @package totara
 * @subpackage plan
 */

define(['jquery'], function ($) {

    var templatecontrol = {
        // Optional php params and defaults defined here, args passed to init method
        // below will override these values.
        templates: {},
        // Public handler reference for the dialog.
        totaraDialog_handler_preRequisite: null,

        /**
         * module initialisation method called by php js_call_amd()
         *
         * @param string    args supplied in JSON format
         */
        init: function(args) {
            // If defined, parse args into this module's config object.
            if (args) {
                this.templates = args;
            }

            // Attach event to drop down.
            $('select#id_templateid').change(function() {
                var select = $(this);

                // Get current value.
                var current = select.val();

                // Overwrite form data.
                $('input#id_name').val(templatecontrol.templates[current].fullname);

                var enddate = templatecontrol.templates[current].enddate;

                $('#id_enddate_day').val(enddate.mday);
                $('#id_enddate_month').val(enddate.mon);
                $('#id_enddate_year').val(enddate.year);
            });

        }
    };
    return templatecontrol;
});
