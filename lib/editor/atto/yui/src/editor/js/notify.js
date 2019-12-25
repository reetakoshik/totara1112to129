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
    ALERT_SELECTOR = '.editor_atto_alert';

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

        // We should only ever see one global notification, do nothing if the Node already exists
        if (this.alertOverlay === null) {

            var alertContainer = Y.one('#page-content');
            if (alertContainer === null) {
                Y.log('Atto could not find a suitable page level Node to append an alert to!', 'debug', LOGNAME_NOTIFY);
                return;
            }

            this.alertOverlay = Y.Node.create('<div class="editor_atto_alert alert alert-' + type + " " +
                                      'role="alert" aria-live="assertive">' +
                                        '<span class="icon">' + this.getIcon('close', '.editor_atto_alert .icon') + '</span>' +
                                        message + '</div>');

            alertContainer.prepend(this.alertOverlay);

            // This event only needs listening to once, as the user should only ever
            // be notified once per page visit.
            this.alertOverlay.once('click', this.hideAlert, this);

            // Growl-style notification, positioned similarly to course page growls to
            // catch the eye above main navigation (defaulting to 0 depending on themed
            // position value) and fixed in place until dismissed.
            var styletop,
                styletopunit;

            styletop = this.alertOverlay.getStyle('top') || '0';
            styletopunit = styletop.replace(/^\d+/, '');
            styletop = parseInt(styletop.replace(/\D*$/, ''), 10);

            YUI().use('anim', function (Y) {

                var fadein = new Y.Anim({
                    node: ALERT_SELECTOR,
                    from: {
                        opacity: 0.0,
                        top: (styletop - 30).toString() + styletopunit
                    },

                    to: {
                        opacity: 1.0,
                        top: styletop.toString() + styletopunit
                    },
                    duration: 0.5
                });
                fadein.run();
            });
        }
     },

    /**
     * Hide the currently displayed notification alert.
     *
     * @method hideAlert
     */
    hideAlert: function () {

        var styletop,
            styletopunit;

        // Reverse the fixed top positioning values, defaulting to 0 depending
        // on themed position value (resulting in no apparent vertical movement)
        styletop = this.alertOverlay.getStyle('top') || '0';
        styletopunit = styletop.replace(/^\d+/, '');
        styletop = parseInt(styletop.replace(/\D*$/, ''), 10);

        YUI().use('anim', function (Y) {

            var fadeout = new Y.Anim({
                node: ALERT_SELECTOR,
                from: {
                    opacity: 1.0,
                    top: styletop.toString() + styletopunit
                },

                to: {
                    opacity: 0.0,
                    top: (styletop - 30).toString() + styletopunit
                },
                duration: 0.5
            });

            fadeout.run();

            fadeout.on('end', function() {
                Y.one(ALERT_SELECTOR).remove(true);
            });

        });
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

        var intTimeout,
            bodyContent,
            icon;

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

        // Populate message contents, the icon may/not be delayed in its rendering.
        bodyContent = this.getMessageContent(message, type);
        icon = this.getIcon(type, '.editor_atto_notification .icon');
        bodyContent.one('.icon').append(icon);

        // Replace current message content with new.
        this.messageOverlay.empty();
        this.messageOverlay.append(bodyContent);
        this.messageOverlay.show(true);

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

    /**
     * Creates and returns a Node containing a message an icon placeholder.
     *
     * @method getMessageContent
     * @param {String} message Message contents to render into the returned Node.
     * @param {String} messageType The type of message determining which icon to render and CSS class to apply to the returned Node.
     * @return {Y.Node} Newly created Node containing a message and icon placeholder.
     */
     getMessageContent: function (message, messageType) {
        return Y.Node.create('<div class="atto_'+ messageType +' alert alert-' + messageType + '" ' +
                                'role="alert" aria-live="assertive">' +
                                    '<span class="icon"></span> ' +
                                    Y.Escape.html(message) +
                            '</div>');
     },

    /**
     * Wrapper for getting an icon's stringified markup, required due to asynchronous
     * use of flexicons. Where an empty String is returned, a one-time listener will
     * detect a change in the icon cache and render the icon as soon as it is available.
     *
     * @method getIcon
     * @param {String} iconType Identifier to fetch & render a known flexicon variant
     * @param {Y.Node} || {String} container Node or selector to append asynchronously rendered icon into
     * @return {String} Cached icon markup or empty String
     */
     getIcon: function (iconType, container) {

        var iconCacheAttr = 'iconCache.' + iconType,
            cache = this.get(iconCacheAttr),
            cachedIcon = cache.html,
            cacheHandler;

        if (!cachedIcon) {

            cacheHandler = this.after('iconCacheChange', function (e) {
                // When our cache contains icon markup, append the icon to supplied
                // container and clean up the event listener.
                if (e.subAttrName === iconCacheAttr.toString() + '.html') {

                    var insert = Y.one(container);
                    if (insert !== null) {
                        insert.append(this.get(e.subAttrName));
                    }
                    cacheHandler.detach();
                }
            }, this);
        }

        return cachedIcon;
     }
};

Y.Base.mix(Y.M.editor_atto.Editor, [EditorNotify]);
