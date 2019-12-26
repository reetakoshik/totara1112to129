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
 * A notify function for the Atto editor.
 *
 * @module     moodle-editor_atto-notify
 * @submodule  notify
 * @package    editor_atto
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var LOGNAME_NOTIFY = 'moodle-editor_atto-editor-notify',
    NOTIFY_INFO = 'info',
    NOTIFY_WARNING = 'warning',
    ALERT_CSS_CLASS = 'editor_atto_alert',
    ALERT_SELECTOR = '.' + ALERT_CSS_CLASS;

function EditorNotify() {}

EditorNotify.ATTRS= {
    /**
     * Icon markup is asynchronously fetched, so we cache the markup here so it
     * can be immediately observable until the markup is available.
     *
     * Note that for backwards compat, the cache keys remain unchanged and so do
     * not accurately reflect actual Flexicon identifiers. Flexicon identifiers
     * are stored in each cache key object.
     *
     * @attribute iconCache
     * @type {Object}
     */
    iconCache: {
        value: {
            'warning': {
                'flexIcon': 'warning',
                'iconAltString': 'warning',
                'html': ''
            },
            'info': {
                'flexIcon': 'help',
                'iconAltString': 'info',
                'html': ''
            },
            'error': {
                'flexIcon': 'times-circle-danger',
                'iconAltString': 'error',
                'html': ''
            },
            'close': {
                'flexIcon': 'delete-ns',
                'iconAltString': 'closebuttontitle',
                'html': ''
            }
        }
    }
};

EditorNotify.prototype = {

    /**
     * A single Y.Node for the page containing editors with draft content. There is only ever one, and will only ever appear once.
     *
     * @property alertOverlay
     * @type {Node}
     */
    alertOverlay: null,

    /**
     * A single Y.Node for the form containing this editor. There is only ever one - it is replaced if a new message comes in.
     *
     * @property messageOverlay
     * @type {Node}
     */
    messageOverlay: null,

    /**
     * A single timer object that can be used to cancel the hiding behaviour.
     *
     * @property hideTimer
     * @type {timer}
     */
    hideTimer: null,

    /**
     * Initialize the notifications.
     *
     * @method setupNotifications
     * @chainable
     */
    setupNotifications: function() {

        var notify = this,
            cache = notify.get('iconCache');

        // Cache known message variant flexicons.
        require(['core/templates', 'core/str'], function(templates, stringlib) {
            Object.keys(cache).map(function (type) {

                var flexIconType = cache[type].flexIcon;

                stringlib.get_string(cache[type].iconAltString).then(function (promisedString) {

                    return templates.renderIcon(flexIconType, {alt: promisedString});

                }).then(function(promisedIcon) {
                    // Cache icon markup using sub-attribute dot notation
                    notify.set('iconCache.' + type + '.html', promisedIcon);
                });

            });
        });

        return this;
    },

    /**
     * Creates and shows an alert to notify the User of drafted content, intended to display
     * as though at a page level. Multiple instances of Editor may try and create an alert
     * but only one will ever be used.
     *
     * TODO: abstract /course/dndupload.js and this implementation of growl notifications
     *
     * @method showAlert
     * @param {String} message The translated message (use get_string)
     * @param {String} type Must be either "info", "warning" or "danger"
     */
     showAlert: function(message, type) {

        this.alertOverlay = Y.one(ALERT_SELECTOR);
        var self = this;

        // We should only ever see one global notification, do nothing if the Node already exists
        if (this.alertOverlay === null) {

            var alertContainer = Y.one('#page-content');
            if (alertContainer === null) {
                Y.log('Atto could not find a suitable page level Node to append an alert to!', 'debug', LOGNAME_NOTIFY);
                return;
            }

            require(['core/templates'], function(templatelib) {
                var template = 'core/notification_' + type;
                var context = {
                    closebutton: true,
                    extraclasses: ALERT_CSS_CLASS,
                    announce: true,
                    message: message
                };

                templatelib.render(template, context).then(function(html) {
                    self.alertOverlay = Y.Node.create(html);

                    alertContainer.prepend(self.alertOverlay);
                });
            });
        }
     },

    /**
     * Show a notification in a floaty overlay somewhere in the atto editor text area.
     *
     * @method showMessage
     * @param {String} message The translated message (use get_string)
     * @param {String} type Must be either "info" or "warning"
     * @param {Number} [timeout] Optional time in milliseconds to show this message for.
     * @chainable
     */
    showMessage: function(message, type, timeout) {

        var intTimeout;

        // Create a message container if there is not one already.
        if (this.messageOverlay === null) {
            this.messageOverlay = Y.Node.create('<div class="editor_atto_notification"></div>');

            this.messageOverlay.hide(true);
            this.textarea.get('parentNode').append(this.messageOverlay);

            this.messageOverlay.on('click', this.hideMessage, this);
        }

        // Tidy up previous autohide timer to avoid hide collisions.
        if (this.hideTimer !== null) {
            this.hideTimer.cancel();
        }

        // Parse the timeout value.
        intTimeout = parseInt(timeout, 10);
        if (intTimeout <= 0) {
            intTimeout = 60000;
        }

        var self = this;
        require(['core/templates'], function(templatelib) {
            templatelib.renderIcon(type).then(function(html) {
                self.messageOverlay.empty();
                self.messageOverlay.removeClass('atto_warning atto_info').addClass('atto_' + type);
                self.messageOverlay.append(html + message);
                self.messageOverlay.show(true);
            });
        });

        // reset hide timout, if applicable
        if (timeout > 0) {
            // Create a new timer for autohide.
            this.hideTimer = Y.later(intTimeout, this, function() {
                Y.log('Hide Atto notification.', 'debug', LOGNAME_NOTIFY);
                this.hideMessage();
            });
        }

        return this;
    },

    /**
     * Hide the currently displayed notification message.
     *
     * @method hideMessage
     * @chainable
     */
    hideMessage: function() {

        if (this.hideTimer !== null) {
            this.hideTimer.cancel();
            this.hideTimer = null;

            if (this.messageOverlay.inDoc()) {
                this.messageOverlay.hide(true);
            }
        }

        Y.log('Hide Atto notification.', 'debug', LOGNAME_NOTIFY);
        this.messageOverlay.hide(true);

        return this;
    },
};

Y.Base.mix(Y.M.editor_atto.Editor, [EditorNotify]);
