/**
 * This file is part of Totara Learn
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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_form
 */

/**
 * @module  totara_form/form_element_wizard
 * @class   Wizard
 * @author  Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'totara_form/form', 'core/templates'], function($, Form) {

    /**
     * Wizard element
     *
     * @class
     * @constructor
     * @augments Form.Element
     *
     * @param {(Form|Group)} parent
     * @param {string} id
     * @param {HTMLElement} node
     */
    function Wizard(parent, id, node) {

        if (!(this instanceof Wizard)) {
            return new Wizard(parent, id, node);
        }

        Form.Group.apply(this, arguments);
        this.node = node;
    }

    Wizard.prototype = Object.create(Form.Group.prototype);
    Wizard.constructor = Wizard;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    Wizard.prototype.toString = function() {
        return '[object Wizard]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    Wizard.prototype.init = function(done) {
        var form = this.getForm(),
            node = $(this.node),
            nodeid = node.attr('data-element-id'),
            name = $('#' + nodeid).attr('data-element-name'),
            selecto = '[name="' + name + '__changestage"]',
            changestateinput = $(selecto),
            that = this,
            wizardprogress = $('#' + nodeid + ' .tf_wizard_progress'),
            stagename,
            formcancelled = 'cancelled';

        // Button click listener
        $(form.node).on('click', '[name="changestage"]', function(e) {
            e.preventDefault();
            stagename = $(this).data('jump-to-stage');
            that.changeStage(form, changestateinput, stagename);
        });

        $(form.node).on('click', '[name="submitbutton"]', function(e) {
            e.preventDefault();
            form.submit();
        });

        $(form.node).on('click', '[name="cancelbutton"]', function(e) {
            e.preventDefault();
            changestateinput.val(formcancelled);
            form.submit();
        });

        // Wizard tab click listener
        wizardprogress.on('click', '.tf_wizard_progress_bar_item', function(e) {
            e.preventDefault();
            if (!$(this).hasClass('tf_wizard_progress_bar_item_jumpable')) {
                return;
            }
            stagename = $(this).data('jump-to-stage');
            that.changeStage(form, changestateinput, stagename);
        });

        done();
    };

    Wizard.prototype.changeStage = function(form, changestateinput, stagename) {
        changestateinput.val(stagename);
        form.reload();
    };

    return Wizard;
});
