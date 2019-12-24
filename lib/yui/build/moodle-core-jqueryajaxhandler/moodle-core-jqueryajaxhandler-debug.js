YUI.add('moodle-core-jqueryajaxhandler', function (Y, NAME) {

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
 * @author Brian Barnes <brian.barnes@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

M.core = M.core || {};
M.core.jqueryajaxhandler = M.core.jqueryajaxhandler || {

    init: function (Y) {

        // Don't worry about anything if jQuery isn't loaded.
        if (typeof $ === 'undefined') {
            return;
        }

        // These need to be kept in sync with those in lib/requirejs/jquery-private.js.
        $(document).on('ajaxSuccess', function (event, response, options) {
            require(['core/jqueryajaxhandler'], function (handler) {
                handler.success(response);
            });
        });

        $(document).on('ajaxError', function(event, response, options, error) {
            require(['core/jqueryajaxhandler'], function (handler) {
                handler.error(response, options);
            });
        });
    }
};

}, '@VERSION@', {"requires": ["base"]});
