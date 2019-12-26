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

/*
 * @package    atto_align
 * @copyright  2014 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_align-button
 */

/**
 * Atto text editor align plugin.
 *
 * @namespace M.atto_align
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */

Y.namespace('M.atto_align').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    initializer: function() {
        var alignment;

        alignment = 'justifyLeft';
        this.addButton({
            icon: 'e/align_left',
            title: 'leftalign',
            buttonName: alignment,
            callback: this._changeStyle,
            callbackArgs: alignment
        });

        alignment = 'justifyCenter';
        this.addButton({
            icon: 'e/align_center',
            title: 'center',
            buttonName: alignment,
            callback: this._changeStyle,
            callbackArgs: alignment
        });

        alignment = 'justifyRight';
        this.addButton({
            icon: 'e/align_right',
            title: 'rightalign',
            buttonName: alignment,
            callback: this._changeStyle,
            callbackArgs: alignment
        });
    },


    /**
     * Change the alignment to the specified justification.
     *
     * @method _changeStyle
     * @param {EventFacade} e
     * @param {string} justification The execCommand for the new justification.
     * @private
     */
    _changeStyle: function(e, justification) {
        var host = this.get('host');

        // We temporarily re-enable CSS styling to try to have the most consistency.
        // Though, IE, as always, is stubborn and will do its own thing...
        host.enableCssStyling();

        document.execCommand(justification, false, null);

        /*
         * Text alignment in IE11/Edge unbale to be changed after setting alignment in other browers.
         * Because IE11/Edge are using align not the style="text-align".
         * For support IE11 & Edge, we have to make align as default.
        */
        if (host.editor._node) {
            const childrenNodes = host.editor._node.children;
            for (var i = 0; i < childrenNodes.length; i++) {
                childrenNodes[i].align = childrenNodes[i].style.textAlign || childrenNodes[i].align;
                childrenNodes[i].style.textAlign = '';
            }
        }

        // Re-disable the CSS styling after making the change.
        host.disableCssStyling();

        // Mark the text as having been updated.
        this.markUpdated();

        this.editor.focus();
    }
});
