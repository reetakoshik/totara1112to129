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
 * @author David Curry <david.curry@totaralms.com>
 * @package mod_facetoface
 */
M.facetoface_managerselect = M.facetoface_managerselect || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param int       User            The current users id
     * @param int       fid             The current facetoface id
     * @param string    manager         The html to display the currently selected manager
     * @param string    sesskey         The sesskey
     */
    init: function(Y, user, fid, manager, sesskey){
        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // Parse args into this module's config object
        this.config.userid = user;
        this.config.fid = fid;
        this.config.manager = manager;
        this.config.sesskey = sesskey;

        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.facetoface_managerselect.init()-> jQuery dependency required for this module to function.');
        }

        ///
        /// Manager dialog
        ///
        (function() {
            var url = M.cfg.wwwroot+'/mod/facetoface/approver/';
            var userid = M.facetoface_managerselect.config.userid;
            var fid = M.facetoface_managerselect.config.fid;
            var sesskey = M.facetoface_managerselect.config.sesskey;

            totaraSingleSelectDialog(
                'manager',
                M.util.get_string('selectmanager', 'mod_facetoface') + M.facetoface_managerselect.config.manager,
                url+'manager.php?userid=' + userid + '&fid=' + fid + '&sesskey=' + sesskey,
                'managerid',
                'managertitle',
                undefined,
                true            // Make selection deletable
            );
        })();
    }
};
