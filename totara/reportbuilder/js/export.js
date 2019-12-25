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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Javascript file containing JQuery bindings for export popup window
 */

M.totara_reportbuilder_export = M.totara_reportbuilder_export || {

    Y: null,

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args) {
        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_reportbuilder_export.init()-> jQuery dependency required for this module to function.');
        }

        $('body').on('click', '#id_export', M.totara_reportbuilder_export.opentab);
    },

    opentab: function(event) {

        if ($("#id_format").val() == 'fusion') {
            event.stopPropagation();
            var url = $("#rb_export_form").attr('action');
            url += '?sesskey' + M.cfg.sesskey + '&format=fusion&export=Export';
            window.open(url, '_blank');
            return false;
        }
        return true;
    }
}
