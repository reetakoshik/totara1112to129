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
 * Password Unmask functionality.
 *
 * @module     core_form/passwordunmask
 * @package    core_form
 * @class      passwordunmask
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 * @author     Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author     Petr Skoda <petr.skoda@totaralearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.2
 */
define(['jquery'], function($) {

    /* eslint no-extend-native:"warn", no-eq-null:"warn", no-self-compare:"warn", no-bitwise:"warn" */

    // Needed for the password unmask.
    // See: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/repeat
    if (!String.prototype.repeat) {
        String.prototype.repeat = function(count) {
            'use strict';
            if (this == null) {
                throw new TypeError('can\'t convert ' + this + ' to object');
            }
            var str = '' + this;
            count = +count;
            if (count != count) {
                count = 0;
            }
            if (count < 0) {
                throw new RangeError('repeat count must be non-negative');
            }
            if (count == Infinity) {
                throw new RangeError('repeat count must be less than infinity');
            }
            count = Math.floor(count);
            if (str.length == 0 || count == 0) {
                return '';
            }
            // Ensuring count is a 31-bit integer allows us to heavily optimize the
            // main part. But anyway, most current (August 2014) browsers can't handle
            // strings 1 << 28 chars or longer, so:
            if (str.length * count >= 1 << 28) {
                throw new RangeError('repeat count must not overflow maximum string size');
            }
            var rpt = '';
            for (;;) {
                if ((count & 1) == 1) {
                    rpt += str;
                }
                count >>>= 1;
                if (count == 0) {
                    break;
                }
                str += str;
            }
            // Could we try:
            // return Array(count + 1).join(this);
            return rpt;
        };
    }

    /* eslint-enable */

    /**
     * Constructor for PasswordUnmask.
     *
     * @param   {String}    id   The element to apply the PasswordUnmask to
     */
    var PasswordUnmask = function(id) {
        var input = $('#' + id);

        this.id = id;
        this.input = null;
        this.unmaskinput = null;
        this.input = input;
        this.unmaskinput = $('#' + id + 'unmask');
        this.wrap = this.input.parent('.wrap');

        var mask = $('<input type="text" value="" class="inputmask" />');
        if (this.input.attr('readonly')) {
            mask.attr('readonly', 'readonly');
        }
        mask.insertBefore('#' + id);

        this.mask = this.wrap.find('.inputmask');
        this.updateMask();

        // Just a safety guard, if for any reason someone focuses on the mask input
        // shift focus automatically to the input focus.
        // This is not entirely accessible, but it will do the job for the time being.
        this.mask.focus(function(e){
            input.focus();
        });

        // Watch the unmask changes.
        this.unmaskinput.change($.proxy(this.unmask, this));

        // Update the astrix with each key up.
        this.input.keyup($.proxy(this.updateMask, this));
        this.input.mouseup();
    };

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    PasswordUnmask.prototype.toString = function() {
        return '[object PasswordUnmask]';
    };

    /**
     * Updates the mask.
     */
    PasswordUnmask.prototype.updateMask = function() {
        this.mask.val('●'.repeat(this.input.val().length));
    };

    /**
     * Unmasks the password.
     */
    PasswordUnmask.prototype.unmask = function() {
        if (this.unmaskinput.is(":checked")) {
            this.input.parents('.totara_passwordunmask').addClass('unmask-password');
        } else {
            this.input.parents('.totara_passwordunmask').removeClass('unmask-password');
        }
    };

    return PasswordUnmask;
});