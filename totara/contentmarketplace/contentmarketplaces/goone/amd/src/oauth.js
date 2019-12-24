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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package contentmarketplace_goone
 */

/**
 *
 * @module     contentmarketplace_goone/oauth
 * @class      oauth
 * @package    contentmarketplace_goone
 */

define(['jquery'], function($) {

    var oauth = {};

    oauth.init = function(selector) {
        var context = $(selector);
        var oauth_authorize_url = context.data('oauthauthorizeurl');
        context.on('click', function(e) {
            e.preventDefault();
            var width = 500;
            var height = 700;
            var wLeft = window.screenLeft ? window.screenLeft : window.screenX;
            var wTop = window.screenTop ? window.screenTop : window.screenY;
            var left = wLeft + (window.innerWidth / 2) - (width / 2);
            var top = wTop + (window.innerHeight / 2) - (height / 2);
            var nw = window.open(
                oauth_authorize_url,
                'setup',
                'height=' + height + ', width=' + width + ', top=' + top + ', left=' + left
            );
            if (window.focus) {
                nw.focus();
            }
        });
    };

    return oauth;

});
