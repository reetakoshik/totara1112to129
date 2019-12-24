/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_customfield
 */

/**
 * This module facilitates the loading of Google Maps api in a AMD fashion.
 * This makes it super easy to use in an AMD script.
 */
define(function() {

    /**
     * A static counter of the instances.
     * We need this to get a unique variable in which to store the callback each time.
     * @type {number}
     */
    var instancecount = 0;

    /**
     * Increment the instance count and return the new instance count.
     * @returns {number}
     */
    function increment_instance_count() {
        instancecount += 1;
        return instancecount;
    }

    return {
        /**
         * Part of the RequireJS spec, see http://requirejs.org/docs/plugins.html#apiload
         *
         * This function relies very heavily on the callback argument passed in the URL when including Google Map API.
         * What ever is passed to callback gets executed.
         * We can use this to execute our onload function when required.
         *
         * @param {String} name Any arguments to pass through to the script
         * @param {Function} req A require function for further loading if needed. Its not.
         * @param {Function} onload A function to call once we are ready.
         * @param {Object} config A config object with info from Require.
         */
        load : function(name, req, onload, config) {
            var id,
                scripttag,
                firstscript;

            if (config.isBuild) {
                // We're in the optimiser, I don't think we can load script resources here so just call the onload function.
                // I'm not entirely sure what is going to happen, but loading the script will be worse,
                onload();
            } else {
                // Remember the onload callback so that we can call it later.
                // Store a direct reference to the function, Google map API provides a callback argument which we will use
                // to execute this function when the map API is loaded.
                id = '___location_callback' + increment_instance_count();
                window[id] = onload;

                scripttag = document.createElement('script');
                scripttag.setAttribute('type', 'text/javascript');
                scripttag.setAttribute('async', true);
                scripttag.setAttribute('src', 'https://maps.google.com/maps/api/js?' + name + '&callback='+id);

                firstscript = document.getElementsByTagName('script')[0];
                firstscript.parentNode.insertBefore(scripttag, firstscript);
            }
        }
    };
});