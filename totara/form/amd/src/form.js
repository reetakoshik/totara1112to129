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
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_form
 */

/**
 * Totara ajax form class.
 *
 * @module  totara_form/form
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/config', 'core/templates', 'core/notification'], function($, CFG, Templates, Notification) {

    var debug = (window.console && CFG.hasOwnProperty('developerdebug')) ? CFG.developerdebug : false;

    /**
     * Watches a form DOM elementand confirms that it has been fully initialised
     *
     * @param {String} formid HTML id of the form to watch (NOTE: thsi already needs to have been added to the DOM)
     * @returns {Promise} resolved once the form has completed it's initialisation process
     */
    function addCompletewatch(formid) {
        var p = new Promise(function(resolve) {
            var formElement = document.getElementById(formid);
            var observerCallback = function() {
                if (formElement.getAttribute('data-totara_form-initialised') === 'true') {
                    observer.disconnect();
                    resolve();
                }
            };

            var observer = new MutationObserver(observerCallback);
            observer.observe(formElement, {
                attributes: true,   // required for IE11 (at minimum)
                attributeFilter: ['data-totara_form-initialised']
            });
        });

        return p;
    }

    /**
     * Generic element class.
     *
     * @class
     * @constructor
     * @external Form.Element
     *
     * @param {(Form|Group)} parent
     * @param {string} type
     * @param {string} id
     * @param {HTMLElement} node
     */
    function Element(parent, type, id, node) {

        if (!(this instanceof Element)) {
            return new Element(parent, type, id, node);
        }

        /**
         * The Parent item, either the Form, or a Group.
         *
         * @public
         * @type {(Form|Group)}
         */
        this.parent = parent;

        /**
         * The element type.
         *
         * To access this from the outside please call {@see getType()}.
         *
         * @protected
         * @type {string|null}
         */
        this.type = type;

        /**
         * The id of the element, this may or may not be on the input.
         *
         * To access this from the outside please call {@see getId()}.
         *
         * @protected
         * @type {string|null}
         */
        this.id = id;

        /**
         * The node containing this elements markup.
         *
         * To access this from the outside please call {@see getNode()}.
         *
         * @protected
         * @type {HTMLElement}
         */
        this.node = node;
    }

    Element.prototype = {

        /**
         * Returns the string descriptor for this element.
         *
         * @returns {string}
         */
        toString: function() {
            return '[object Element]';
        },

        /**
         * Initialises this generic element. Calling done() when it is done.
         *
         * @param {Function} done Needs to be called once this initialisation is complete.
         * @returns {null}
         */
        init: function(done) {
            done();
        },

        /**
         * Returns the elements Id.
         * @returns {string}
         */
        getId: function() {
            return this.id;
        },

        /**
         * Returns the elements type
         *
         * @returns {string}
         */
        getType: function() {
            return this.type;
        },

        /**
         * Returns the elements containing node.
         *
         * @returns {HTMLElement}
         */
        getNode: function() {
            return this.node;
        },

        /**
         * Returns the current value of the Element.
         *
         * Null is returned if we do not know the value of the element.
         *
         * @returns {null}
         */
        getValue: function() {
            return null;
        },

        /**
         * Compares the value of this element using the given operator, additional args for comparison operator types.
         *
         * @param {string} operator
         * @returns {boolean}
         */
        compare: function(operator) {
            var value = this.getValue();
            var args = [value].concat(Array.prototype.slice.call(arguments));
            return Form.prototype.compare.apply(null, args);
        },

        /**
         * Returns true if the value of this element is empty.
         *
         * @returns {boolean}
         */
        isEmpty: function() {
            var value = this.getValue();
            if (value === null || value === '' || value === 0 || value === []) {
                return true;
            }
            if (value.hasOwnProperty('isEmpty')) {
                return value.isEmpty();
            }
            return false;
        },

        /**
         * Returns true if this element is hidden
         *
         * @returns {boolean}
         */
        isHidden: function() {
            return this.node.getAttribute('data-hidden') !== null;
        },

        /**
         * Sets the hidden state of this element.
         *
         * @param {boolean} hide
         */
        setHidden: function(hide) {
            if (hide) {
                MODULE.debug('#' + this.id + ' is now hidden', this, MODULE.LOGLEVEL.debug);
                this.node.setAttribute('data-hidden', '');
            } else {
                MODULE.debug('#' + this.id + ' is no longer hidden', this, MODULE.LOGLEVEL.debug);
                this.node.removeAttribute('data-hidden');
            }
        },

        /**
         * Called via an event when the value of this element has changed.
         *
         * @param {Event} event please note the event may be an empty object if it was changed programatically.
         *     Never rely on it.
         */
        changed: function(event) {
            this.getForm().valueChanged(this.getId(), this.getValue());
        },

        /**
         * Disables this element, if possible.
         *
         * @returns {boolean} Returns true if the element was successfully disabled.
         */
        disable: function() {
            if (this.input) {
                this.input.setAttribute('disabled', '');
                return true;
            }
            return false;
        },

        /**
         * Enables this element, if possible.
         *
         * @returns {boolean} Returns true if the element was successfully enabled.
         */
        enable: function() {
            if (this.input) {
                this.input.removeAttribute('disabled');
                return true;
            }
            return false;
        },

        /**
         * Returns true if this element is disabled. Returns null if we don't know.
         *
         * @returns {(boolean|null)}
         */
        isDisabled: function() {
            if (this.input) {
                return this.input.getAttribute('disabled') !== null;
            }
            return null;
        },

        /**
         * Returns true if this element is enabled. Returns null if we don't know.
         *
         * @returns {(boolean|null)}
         */
        isEnabled: function() {
            if (this.input) {
                return this.input.getAttribute('disabled') === null;
            }
            return null;
        },

        /**
         * Find the Form object at the top of the hierarchy.
         *
         * @return {Form}
         */
        getForm: function() {

            var depth = 0;
            var getParent = function(element) {
                depth++;
                if (depth > 10) {
                    throw "Coding error: element nesting is too deep!";
                }
                if (element.hasOwnProperty('parent')) {
                    return getParent(element.parent);
                }
                return element;

            };

            return getParent(this.parent);
        }

    };

    /**
     * Mixin.
     *
     * Forms and Groups may both contain collections of form elements.
     * Functionality relating to form element collections is encapsulated
     * here so that it can be mixed in as required.
     *
     * @mixin ElementCollection
     */
    function ElementCollection() {

        /**
         * An array of elements, with the elements id as the key.
         *
         * @protected
         * @type {(Object|Element[]|Group[])}
         */
        this.elements = {};

    }

    ElementCollection.prototype = {

        /**
         * Registers an item with this collection.
         *
         * @protected
         * @param {(Group|Element)} item
         */
        registerItem: function(item) {
            if (debug && this.elements[item.getId()]) {
                MODULE.debug('Item being registered already exists #' + item.getId(), ElementCollection, MODULE.LOGLEVEL.error);
            }
            this.elements[item.getId()] = item;
        },

        /**
         * Registers a group with this collection.
         *
         * @protected
         * @param {Group} group
         * @param {Deferred} deferred
         */
        registerGroup: function(group, deferred) {
            this.registerItem(group);

            // Init method may be performing async actions. Provide a
            // function which when called resolves the deferred. This
            // will allow us to switch to vanilla ES 2015 Promises when
            // support is no longer an issue.
            var failed = true;
            if (debug) {
                var waitForMilliseconds = 3000;
                window.setTimeout(function () {
                    if (failed === true) {
                        MODULE.debug('Element #' + group.getId() + ' failed to call done() in ' + waitForMilliseconds + 'ms', group,
                            MODULE.LOGLEVEL.error);
                    }
                }, waitForMilliseconds);
            }

            MODULE.debug('Registered group #' + group.getId() + ' of type ' + group.getType(), group, MODULE.LOGLEVEL.debug);
            group.init(function() {
                MODULE.debug('Completed initialisation of #' + group.getId(), group, MODULE.LOGLEVEL.debug);
                failed = false;
                deferred.resolve(group);
            });
        },

        /**
         * Registers an element with this collection.
         *
         * @protected
         * @param {Element} element
         * @param {Deferred} deferred
         */
        registerElement: function(element, deferred) {
            this.registerItem(element);

            // Init method may be performing async actions. Provide a
            // function which when called resolves the deferred. This
            // will allow us to switch to vanilla ES 2015 Promises when
            // support is no longer an issue.
            var failed = true;
            if (debug) {
                var waitForMilliseconds = 3000;
                window.setTimeout(function () {
                    if (failed === true) {
                        MODULE.debug('Element #' + element.getId() + ' failed to call done() in ' + waitForMilliseconds + 'ms',
                            element, MODULE.LOGLEVEL.error);
                    }
                }, waitForMilliseconds);
            }

            MODULE.debug('Registered element #' + element.getId() + ' of type ' + element.getType(), element,
                MODULE.LOGLEVEL.debug);
            element.init(function() {
                MODULE.debug('Completed initialisation of #' + element.getId(), element, MODULE.LOGLEVEL.debug);
                failed = false;
                deferred.resolve(element);
            });
        },

        /**
         * Return all items referenced in this.elements filtering by given function.
         *
         * Returns a flattened array of all elements found in sub-groups if the
         * flatten param is true.
         *
         * @protected
         * @param {Function} filterFunc Passed the current element as param. Return boolean.
         * @param {Boolean} flatten
         * @param {int} [depth=0] Internal only, don't ever provide.
         * @returns {Array}
         */
        filterItems: function(filterFunc, flatten, depth) {

            var elements = [],
                groupElements = [];

            if (depth === undefined) {
                depth = 0;
            }
            depth++;

            // Having 6 is totally arbitrary, if you ever hit this you should increase the number.
            // It just ensures that if for any reason recursion happens we don't kill the browser.
            if (depth > 6) {
                MODULE.debug('Filtering depth exceeds known limits, coding error!', this, MODULE.LOGLEVEL.error);
                return elements;
            } else if (depth > 4) {
                MODULE.debug('Filtering depth nearing limit, check you need this level of depth!', this, MODULE.LOGLEVEL.warn);
            }

            if (typeof filterFunc !== 'function') {
                throw new Error('You must provide a filter function for filterItems()');
            }

            for (var elementID in this.elements) {
                if (this.elements.hasOwnProperty(elementID)) {
                    var element = this.elements[elementID];

                    if (filterFunc(element) === true) {
                        elements[elementID] = element;
                    }

                    if (flatten === true && (element instanceof Group)) {
                        groupElements = element.filterItems(filterFunc, true, depth);
                        elements = $.extend(elements, groupElements);
                    }
                }
            }

            return elements;

        },

        /**
         * Returns all element, optionally flattened.
         *
         * @param {Boolean} flatten Optional. Return all elements and children of child groups.
         * @return {Array}
         */
        getElements: function(flatten) {
            return this.filterItems(function(item) {
                return !(item instanceof Group) && (item instanceof Element);
            }, flatten);
        },

        /**
         * Returns all groups optionally flattened.
         *
         * @param {Boolean} flatten Optional. Return all elements and children of child groups.
         * @return {Array}
         */
        getGroups: function(flatten) {
            return this.filterItems(function(item) {
                return item instanceof Group;
            }, flatten);
        },

        /**
         * Returns a specific element given its id.
         *
         * @param {string} id
         * @returns {Element}
         */
        getElementById: function(id) {
            var elements = this.getElements(true);
            return elements[id];
        },

        /**
         * Returns a specific group given its id.
         *
         * @param {string} id
         * @returns {Group}
         */
        getGroupById: function(id) {
            var groups = this.getGroups(true);
            return groups[id];
        },

        /**
         * Returns a specific item given its id.
         *
         * @param {string} id
         * @return {(Element|Group)}
         */
        getItemById: function(id) {
            var items = $.extend(this.getElements(true), this.getGroups(true));
            return items[id];
        },

        /**
         * Return an array containing the result of applying a function to all elements.
         *
         * @protected
         * @param {Function} fn
         * @returns {Array}
         */
        map: function(fn) {
            var elements = this.elements;
            var result = [];

            for (var elementID in elements) {
                if (elements.hasOwnProperty(elementID)) {
                    result.push(fn(elements[elementID]));
                }
            }

            return result;
        },

        /**
         * Disables all elements within this collection.
         * @returns {ElementCollection}
         */
        disable: function() {
            this.map(function(element) {
                element.disable();
            });

            return this;
        },

        /**
         * Enables all elements within this collection.
         * @returns {ElementCollection}
         */
        enable: function() {
            this.map(function(element) {
                element.enable();
            });

            return this;
        },

        /**
         * Is the Group disabled (all sub-elements are disabled).
         *
         * @returns {boolean}
         */
        isDisabled: function() {
            var result = this.map(function(element) {
                return element.isDisabled();
            });

            return result.indexOf(false) === -1;
        },

        /**
         * Is the Group enabled? (all sub-elements are enabled).
         *
         * @returns {boolean}
         */
        isEnabled: function() {
            var result = this.map(function(element) {
                return element.isEnabled();
            });

            return result.indexOf(false) === -1;
        },

        /**
         * Are all items in this collection valid?
         *
         * @returns {boolean}
         */
        isValid: function() {
            // Checks custom validation for all child elements.
            // If none is defined we must assume a pass.
            return this.getElements(true).every(function(element) {

                if (typeof element.isValid !== 'function') {
                    // If there is not an implementation of isValid
                    // we should not block submission.
                    return true;
                }

                var valid = element.isValid();
                // If the element is not valid then allow the element to display an error message if it wants.
                if (!valid && (typeof element.displayError === 'function')) {
                    element.displayError();
                }
                // Return validity.
                return valid;

            });
        },

        /**
         * Returns the string representation of this object.
         *
         * @returns {string}
         */
        toString: function() {
            return '[object ElementCollection]';
        },

        /**
         * For a given node find elements and groups to add to internal array.
         *
         * @protected
         * @param {HTMLElement} node
         * @returns {HTMLElement[]}
         */
        findItemNodes: function(node) {
            var elementExpectation = MODULE.attributeIdentifiers.CLASSIFICATION + '="' + MODULE.itemIdentifiers.ELEMENT + '"',
                elementSelector = MODULE.getDataAttributeSelector(elementExpectation),
                groupExpectation = MODULE.attributeIdentifiers.CLASSIFICATION + '="' + MODULE.itemIdentifiers.GROUP + '"',
                groupSelector = MODULE.getDataAttributeSelector(groupExpectation);

            // This is very inefficient as each container calls it. We only want items which are
            // not nested in other containers. Can't see a quick way to improve this (looks like a bit of
            // restructuring might be required).
            var nodeJQuery = $(node);
            var itemNodes = nodeJQuery.find([elementSelector, groupSelector].join(', ')).get();

            itemNodes = Array.prototype.filter.call(itemNodes, function(itemNode) {
                return $(itemNode).parentsUntil(nodeJQuery, groupSelector).length === 0;
            });

            return itemNodes;
        },

        /**
         * Return all items within the node representing this element collection.
         *
         * @returns {*|HTMLElement[]}
         */
        getItemNodes: function() {
            return this.findItemNodes(this.node);
        },

        /**
         * Initialise a group, given its node.
         *
         * @protected
         * @param {HTMLElement} groupNode
         * @returns {Promise}
         */
        initGroup: function(groupNode) {

            var self = this,
                group,
                groupData = {},
                attributes = MODULE.attributeIdentifiers,
                itemNodes,
                deferred = $.Deferred();

            [attributes.TYPE, attributes.ID, attributes.MODULE].forEach(function(attribute) {
                groupData[attribute.split('-').pop()] = groupNode.getAttribute(attribute);
            });

            if (!groupData.module) {
                group = new Group(this, groupData.id, groupNode);
                itemNodes = group.getItemNodes();

                var groupDefer = $.Deferred();
                // First register the group.
                this.registerGroup(group, groupDefer);

                // When that is done we can register the items.
                groupDefer.done(function() {
                    // Now initialise the items.
                    group.initItems(itemNodes).done(function () {
                        deferred.resolve();
                    });
                });
            } else {
                require([groupData.module], function(GroupModule) {
                    var group = new GroupModule(self, groupData.id, groupNode);
                    var itemNodes = group.getItemNodes();
                    var groupDefer = $.Deferred();
                    // First register the group.
                    self.registerGroup(group, groupDefer);

                    // When that is done we can register the items.
                    groupDefer.done(function() {
                        group.initItems(itemNodes).then(
                            function () {
                                deferred.resolve();
                            },
                            function (e) {
                                MODULE.debug('Failed to initialise the items within a group of type ' + groupData.module, self,
                                    MODULE.LOGLEVEL.error);
                                deferred.reject();
                            }
                        );
                    });
                });
            }
            return deferred.promise();
        },

        /**
         * Initialise an element given its node.
         *
         * @protected
         * @param {HTMLElement} node
         * @returns {Promise}
         */
        initElement: function(node) {

            var self = this;
            var deferred = $.Deferred();
            var moduleData = {};
            var attributes = MODULE.attributeIdentifiers;

            [attributes.TYPE, attributes.ID, attributes.MODULE].forEach(function(attribute) {
                moduleData[attribute.split('-').pop()] = node.getAttribute(attribute);
            });

            if (!moduleData.module) {
                moduleData.module = 'totara_form/element_generic';
            }

            require([moduleData.module], function(FormElement) {
                var element = new FormElement(self, moduleData.type, moduleData.id, node);
                self.registerElement(element, deferred);
            });

            return deferred.promise();

        },

        /**
         * Request initialisation of all items and return a promise resolving when all are done.
         *
         * @param {(Array|NodeList)} itemNodes
         * @returns {Promise}
         */
        initItems: function(itemNodes) {

            var promises = Array.prototype.map.call(itemNodes, function(itemNode) {

                if (itemNode.getAttribute(MODULE.attributeIdentifiers.CLASSIFICATION) === MODULE.itemIdentifiers.GROUP) {
                    return this.initGroup(itemNode);
                }

                if (itemNode.getAttribute(MODULE.attributeIdentifiers.CLASSIFICATION) === MODULE.itemIdentifiers.ELEMENT) {
                    return this.initElement(itemNode);
                }

            }, this);

            return $.when.apply($, promises);

        }

    };

    /**
     * Generic group class.
     *
     * @class
     * @constructor
     * @mixes Element
     * @mixes ElementCollection
     *
     * @param {(Form|Group)} parent
     * @param {string} id
     * @param {HTMLElement} node
     * @constructor
     */
    function Group(parent, id, node) {

        if (!(this instanceof Group)) {
            return new Group(parent, id, node);
        }

        Element.call(this, parent, 'totara_form\\form\\group', id, node);
        ElementCollection.call(this);

    }

    Group.prototype = Object.create(Element.prototype);
    $.extend(Group.prototype, ElementCollection.prototype);
    Group.prototype.constructor = Group;
    Group.prototype.toString = function() {
        return '[object Group]';
    };

    /**
     * Initialises this group.
     *
     * Please be aware at this time the items within the group have not yet been initialised.
     * They get initialised after the group itself.
     *
     * @param {Function} done
     */
    Group.prototype.init = function(done) {
        done();
    };

    /**
     * Form
     *
     * @class
     * @constructor
     * @mixes ElementCollection
     * @param {Object} config
     */
    function Form(config) {

        if (!(this instanceof Form)) {
            return new Form(config);
        }

        // Check for required properties.
        ['el'].forEach(function(requiredProp) {
            if (!config.hasOwnProperty(requiredProp)) {
                var message = 'Missing required config property "' + requiredProp + '"';
                throw new Error(message);
            }
        });

        /**
         * The HTML container for this form.
         *
         * @protected
         * @type {HTMLElement}
         */
        this.node = config.el;

        /**
         * The id of the form.
         *
         * @protected
         * @type {string}
         */
        this.id = config.el.getAttribute('id');

        /**
         * Client actions attached to this form.
         *
         * @protected
         * @type {ClientAction[]}
         */
        this.clientActions = [];

        /**
         * An array of object listeners.
         *
         * @type {Array}
         */
        this.actionListeners = [];

        /**
         * The form node, as a jQuery object.
         *
         * @protected
         * @type {jQuery}
         */
        this.form = $(this.node);

        // Set this object against the form.
        this.form.data('TotaraForm', this);

        if (this.form.attr('action') === CFG.wwwroot + '/totara/form/ajax.php') {
            MODULE.debug('Form submission for ' + this.id + ' is being directed via ajaxSubmit', this, MODULE.LOGLEVEL.info);
            this.form.on('submit.ajaxSubmit', $.proxy(function(e) {
                e.preventDefault();
                this.ajaxSubmit(this.form.find('input[type=submit]:focus'));
            }, this));
        }

        // Grab the ElementCollection mixin.
        ElementCollection.call(this);
    }

    Form.prototype = Object.create(ElementCollection.prototype);
    Form.prototype.constructor = Form;
    $.extend(Form.prototype, {

        /**
         * Describers the object with a short string.
         * @returns {string}
         */
        toString: function() {
            return '[object Form]';
        },

        /**
         * Initialises client action.
         *
         * @protected
         * @param {Object} actionsConfig
         * @returns {Promise} Returns a promise that is resolved when all client actions are initialised.
         */
        initClientActions: function(actionsConfig) {
            var self = this;
            var promises = actionsConfig.map(function(actionConfig) {
                return self.initClientAction(actionConfig).done(function(clientAction) {
                    self.clientActions.push(clientAction);
                });
            });

            // Resolve only when all promises have resolved. Note that
            // initClientAction will throw if module is not found which
            // is why we do not add a .fail() handler.
            return $.when.apply($, promises).done(function() {
                self.checkAllClientActionStates();
            });

        },

        /**
         * Initialises a single client action.
         *
         * @protected
         * @param {Object} actionConfig
         * @returns {Promise} Returns a promise that is resolved when the action has been fully initialised.
         */
        initClientAction: function(actionConfig) {

            var self = this;
            var deferred = $.Deferred();
            var module = this.convertTypeToModule(actionConfig.actionType);

            // Action config is passed from the server. If code there is
            // incorrect we need to make the developer aware. The interface
            // will not work correctly if the action is missing.
            if (!module) {
                throw new Error('Unknown action of type [' + actionConfig.actionType + ']');
            }

            require([module], function(actionModule) {
                actionModule.init(actionConfig, self, function(instance) {
                    deferred.resolve(instance);
                });
            });

            return deferred.promise();

        },

        /**
         * Converts a given item type into an expected module.
         *
         * @protected
         * @param {string} type
         * @returns {string}
         */
        convertTypeToModule: function(type) {
            var bits = type.split('\\'),
                component = bits[0],
                element = bits.slice(1).join('_'),
                module = component + '/' + element;

            MODULE.debug('Found module to be [' + module + '] from type [' + type + '] by split.', Form, MODULE.LOGLEVEL.debug);
            return module;
        },

        /**
         * Confirms if a given module exists as a AMD module known to RequireJS.
         *
         * @protected
         * @param {string} module
         * @returns {boolean}
         */
        checkModuleExists: function(module) {
            return require.specified(module);
        },

        /**
         * Called when the value of an element belonging to this form changes.
         *
         * @param {string} elementId
         */
        valueChanged: function(elementId) {
            MODULE.debug('Value of #' + elementId + ' changed', this, MODULE.LOGLEVEL.info);
            this.checkClientActionState(elementId);
        },

        /**
         * Checks the state of all any client actions watching the given element id.
         *
         * @param {string} elementId
         */
        checkClientActionState: function(elementId) {
            var working = this.setFormWorking('checkaction');
            MODULE.debug('Checking ClientActions on #' + elementId, this, MODULE.LOGLEVEL.debug);
            this.clientActions.forEach(function(clientAction) {
                if (clientAction.getWatchedIds().indexOf(elementId) !== -1) {
                    clientAction.checkState();
                }
            });
            working.resolve();
        },

        /**
         * Checks the state of all client actions.
         */
        checkAllClientActionStates: function() {
            var working = this.setFormWorking('checkallactions');
            MODULE.debug('Checking the state of all ClientActions', this, MODULE.LOGLEVEL.debug);
            for (var property in this.clientActions) {
                if (this.clientActions.hasOwnProperty(property)) {
                    this.clientActions[property].checkState();
                }
            }
            working.resolve();
        },

        /**
         * Adds an action listener.
         *
         * @param {Object} callbackObj
         */
        addActionListener: function(callbackObj) {
            this.actionListeners.push(callbackObj);
        },

        /**
         * Calls the action on all action listeners.
         *
         * @param {String} action
         * @param {Array} args
         */
        callActionListener: function(action, args) {
            for (var i in this.actionListeners) {
                if (this.actionListeners.hasOwnProperty(i) && this.actionListeners[i].hasOwnProperty(action)) {
                    this.actionListeners[i][action].apply(this, args);
                }
            }
        },

        /**
         * Submits the form via JavaScript.
         *
         * The equivalent to clicking a submit button with no name.
         *
         * Use reload if you just want to reload, and ajaxSubmit if you want to submit via AJAX and
         * handle the outcome in JavaScript.
         */
        submit: function() {
            // Trigger the form submission.
            this.form.trigger('submit');
        },

        /**
         * Reloads the form.
         *
         * @param {HTMLElement} button
         */
        reload: function(button) {
            this.ajaxSubmit(button, true);
        },

        /**
         * Submits the form via AJAX.
         *
         * @param {HTMLElement} button Optional, value sent as data if provided.
         * @param {Boolean} reloadOnly
         * @return {Deferred}
         */
        ajaxSubmit: function(button, reloadOnly) {
            reloadOnly = reloadOnly || false;
            if (reloadOnly) {
                MODULE.debug('Reloading form #' + this.id, this, MODULE.LOGLEVEL.info);
            } else {
                MODULE.debug('Submitting form #' + this.id + ' by AJAX', this, MODULE.LOGLEVEL.info);
            }

            var formdata = this.form.serializeArray(),
                deferred = $.Deferred();

            // Set the form as working and add a done callback to mark it ready once the deferred is resolved.
            var working = this.setFormWorking('ajaxsubmit');
            deferred.done(function() {
                working.resolve();
            });

            if (button !== undefined) {
                formdata.push({
                    name: button.attr('name'),
                    value : button.val()}
                );
            }
            if (reloadOnly) {
                // Mark this as a reload.
                formdata.push({
                    name: '___tf_reload',
                    value: 1
                });
                formdata.push({
                    name: '___tf_original_action',
                    value: this.form.attr('action')
                });
            }
            $.ajax({
                type: 'POST',
                url: CFG.wwwroot + '/totara/form/ajax.php',
                data: formdata,
                context: this,
                success: function(data) {
                    this.ajaxSubmitHandler(deferred, data);
                },
                error: function(jqXHR, status, error) {
                    deferred.reject(jqXHR, status, error);
                },
                dataType: 'json'
            });
            return deferred;
        },

        /**
         * Handles the response from a reload operation.
         *
         * @private
         * @param {Deferred} deferred
         * @param {Object} data Data from an AJAX response.
         */
        ajaxSubmitHandler: function(deferred, data) {

            var self = this;
            deferred.done(
                function(action, data) {
                    self.callActionListener(action, [self, data]);
                }
            );

            if (data.formstatus === 'display') {

                MODULE.debug('Displaying the form after an AJAX reload.', Form, MODULE.LOGLEVEL.info);
                Templates.render(data.templatename, data.templatedata).done(
                    $.proxy(this.replaceFormContents, this),
                    deferred.resolve(data.formstatus, data.templatedata.formid)
                ).fail(Notification.exception);

            } else if (data.formstatus === 'cancelled') {

                deferred.done(
                    function() {
                        MODULE.debug('The form has been cancelled, the calling JS needs to do something.', Form, MODULE.LOGLEVEL.success);
                    }
                );
                deferred.resolve(data.formstatus, data);

            } else if (data.formstatus === 'submitted') {

                // The form has been submit, data is the result.
                deferred.done(
                    function() {
                        MODULE.debug('The form has been successfully submit, the calling JS needs to do something.', Form, MODULE.LOGLEVEL.success);
                    }
                );

                deferred.resolve(data.formstatus, data.data);

            } else {

                // Who knows what has happened here, but it was not expected.
                deferred.done(function() {
                    MODULE.debug('Unexpected reload result.', Form, MODULE.LOGLEVEL.error);
                });
                deferred.resolve('unknown', data);

            }
        },

        /**
         * Replaces the form with the new form, rendered by Mustache.
         *
         * @private
         * @param {string} html
         * @param {string} js
         */
        replaceFormContents: function(html, js) {
            Templates.replaceNode(this.form, html, js);
            M.util.js_pending('totara_form-replaceform');
            addCompletewatch(this.form.attr('id')).then(function() {
                M.util.js_complete('totara_form-replaceform');
            });
        },

        /**
         * Returns true is the comparison is true, false if not, and null if we don't know.
         *
         * @param {(String|Array|Object)} value
         * @param {String} operator
         * @returns {Boolean|null}
         */
        compare: function(value, operator) {

            // Did you know that null is an object in JS.
            if (value !== null && (!Array.isArray(value) && (typeof value === 'function' || typeof value === 'object'))) {
                // We don't deal with arrays, functions, or objects here.
                return false;
            }

            var compare = Form.prototype.compare,
                expected,
                l, i,
                valueIsArray = Array.isArray(value) ,
                result = null; // Null means we don't know.

            switch (operator) {

                case Form.Operators.Equals:
                    // Value === Expected.
                    if (arguments.length !== 3) {
                        l = arguments.length;
                        MODULE.debug('Compare NotEquals expects 3 arguments, ' + l + ' given.', Form, MODULE.LOGLEVEL.error);
                        return result;
                    }
                    expected = arguments[2];
                    if (Array.isArray(expected)) {
                        if (!valueIsArray || value.length < expected.length) {
                            // Its not an array or their aren't enough elements in the value.
                            result = false;
                        } else {
                            result = true;
                            for (i in expected) {
                                if (expected.hasOwnProperty(i) && !$.inArray(expected[i], value)) {
                                    // At least one item is missing, the result is false.
                                    result = false;
                                    break;
                                }
                            }
                        }
                    } else {
                        if (!valueIsArray) {
                            if (value === null) {
                                value = '';
                            }
                            result = (value.toString() === expected.toString());
                        } else {
                            // By design equals means that if the expected value matches any of the current values then its true.
                            for (i in value) {
                                if (value.hasOwnProperty(i) && value[i].toString() === expected.toString()) {
                                    result = true;
                                    break;
                                }
                            }
                        }
                    }
                    break;

                case Form.Operators.Empty:
                    // Value is empty e.g. null, '', 0, array(), {}.
                    result = (value === null ||
                              value === false ||
                              value.toString() === '' ||
                              value.toString() === '0' ||
                              (valueIsArray && value.length === 0));
                    break;

                case Form.Operators.Filled:
                    // Value has been provided, e.g. Value !== null &&  Value !== ''
                    // False and 0 pass.
                    if (valueIsArray) {
                        result = (value.length > 0);
                    } else {
                        if (value === null) {
                            value = '';
                        }
                        value = value.toString().trim();
                        result = (value !== '' && value.toLowerCase() !== 'false' && !(/^[0]*$/.test(value)));
                    }
                    break;

                case Form.Operators.NotEquals:
                    if (arguments.length !== 3) {
                        l = arguments.length;
                        MODULE.debug('Compare NotEquals expects 3 arguments, ' + l + ' given.', Form, MODULE.LOGLEVEL.error);
                        return result;
                    }
                    expected = arguments[2];
                    // Value !== Expected.
                    result = !compare(value, Form.Operators.Equals, expected);
                    break;

                case Form.Operators.NotEmpty:
                    // Value is not empty.
                    result = !compare(value, Form.Operators.Empty);
                    break;

                case Form.Operators.NotFilled:
                    // Value === Null.
                    result = !compare(value, Form.Operators.Filled);
                    break;

                default:
                    // If we hit this then you have a coding error OR someone has implemented a new operator and not
                    // set up a default comparison.
                    MODULE.debug('Unknown comparison operator given: "' + operator + '"', Form, MODULE.LOGLEVEL.error);
                    break;

            }

            return result;
        },

        /**
         * Submit handler for the form.
         *
         * @param {Event} event
         */
        submitHandler: function(event) {

            var self = event.data.form;

            event.preventDefault();

            if (self.isValid()) {
                var target = $(event.target);
                target.unbind('submit', self.submitHandler);
                target.submit();
            } else {
                MODULE.debug('Failed validation, the responsible element should identify itself.', Form, MODULE.LOGLEVEL.warn);
            }

        },

        /**
         * Custom validation is to be used.
         */
        setCustomValidation: function() {

            var jqueryElement = $(this.node);
            var self = this;

            self.node.setAttribute('novalidate', '');
            jqueryElement.on('submit.working', { form: self }, self.submitHandler);

        },

        /**
         * Please call me when the form is doing things.
         *
         * Allows the form to inform anything it wants that it is currently working.
         * Anything relying on the form can then patiently wait.
         *
         * Used for behat!
         *
         * @param {String} action a string that describes the action leading to the working.
         */
        setFormWorking: function(action) {
            MODULE.debug('Form working on ' + action + ' "' + this.id + '"', Form, MODULE.LOGLEVEL.debug);
            var deferred = $.Deferred(),
                self = this;
            deferred.done(function() {
                self.setFormReady(action);
            });
            M.util.js_pending('totaraForm_' + this.id + '_' + action);
            if (this.form) {
                // The user can not submit this form while it is working.
                this.form.on('submit.working', this.preventSubmissionOnSubmit);
            }
            return deferred;
        },

        /**
         * Please call me when the form has finished doing things.
         *
         * Allows the form to inform anything waiting for it that it is now ready to be used.
         *
         * Used for behat!
         *
         * @param {String} action a string that describes the action we were waiting for.
         */
        setFormReady: function(action) {
            MODULE.debug('Form ready after ' + action + ' "' + this.id + '"', Form, MODULE.LOGLEVEL.debug);
            if (this.form) {
                // The user can not submit this form while it is working.
                this.form.off('submit.working', this.preventSubmissionOnSubmit);
            }
            M.util.js_complete('totaraForm_' + this.id + '_' + action);
        },

        /**
         * Stops the submission of the form on the submit event.
         * @param {Event} e
         */
        preventSubmissionOnSubmit: function(e) {
            e.preventDefault();
        },

        /**
         * Show the loading icon for the form
         */
        showLoading: function() {
            if ($(MODULE.LOADING, this.node).length > 0) {
                $(MODULE.LOADING, this.node).show();
            }
        },

        /**
         * Hide the loading icon for the form
         *
         * There isn't much need for this as most of the time, the form throws itself away and re-creates itself
         */
        hideLoading: function() {
            if ($(MODULE.LOADING, this.node).length > 0) {
                $(MODULE.LOADING, this.node).hide();
            }
        }

    });

    /**
     * @namespace
     * @type {{Equals: string, Empty: string, Filled: string, NotEquals: string, NotEmpty: string, NotFilled: string}}
     */
    Form.Operators = {
        Equals: 'equals',               // Value === Expected.
        Empty: 'empty',                 // Value is empty e.g. null, '', 0, array(), {}.
        Filled: 'filled',               // Value has been provided, e.g. Value !== null &&  Value !== ''.
        NotEquals: 'notequals',         // Value !== Expected.
        NotEmpty: 'notempty',           // Value is not empty.
        NotFilled: 'notfilled'          // Value === Null.
    };

    /**
     * Module namespace that will be returns as the Object for this AMD module.
     *
     * @namespace
     */
    var MODULE = {

        /**
         * Expose form operators.
         */
        Operators: Form.Operators,

        /**
         * Expose Element so that custom elements may extend it.
         */
        Element: Element,

        /**
         * Expose Group so that custom groups my extend it.
         */
        Group: Group,

        /**
         * A collection of all initialised forms.
         * @private
         */
        forms: {},

        /**
         * A queue of listeners to add to a form once it has initialised.
         * @private
         */
        listenerQueue: {},

        /**
         * Item identifiers.
         *
         * @namespace
         * @type {{FORM: string, ELEMENT: string, GROUP: string, INPUT: string, LOADING: string}}
         */
        itemIdentifiers: {
            FORM: 'data-totara-form',
            ELEMENT: 'element',
            GROUP: 'group',
            INPUT: 'data-totara-form-element-input',
            LOADING: '.tf_loading_form'
        },

        /**
         * Attribute identifiers.
         *
         * @namespace
         * @type {{ID: string, TYPE: string, MODULE: string, CLASSIFICATION: string}}
         */
        attributeIdentifiers: {
            ID: 'data-element-id',
            TYPE: 'data-element-type',
            MODULE: 'data-element-amd-module',
            CLASSIFICATION: 'data-item-classification'
        },

        /**
         * @namespace
         * @type {{none: string, debug: string, info: string, success: string, warn: string, error: string}}
         */
        LOGLEVEL: {
            none: 'none',
            debug: 'debug',
            info: 'info',
            success: 'success',
            warn: 'warn',
            error: 'error'
        },

        /**
         * Returns a data attribute selector.
         * @param {string} attributeName
         * @returns {string}
         */
        getDataAttributeSelector: function (attributeName) {
            return '[' + attributeName + ']';
        },

        /**
         * Initialise a Totara form.
         *
         * @param {Object} formData Initialization data passed from server-side for a specific form.
         */
        init: function (formData) {

            MODULE.debug('Initialising form #' + formData.id, Form, MODULE.LOGLEVEL.info);

            var formNode = $('#' + formData.id);
            if (formNode.length === 0) {
                throw new Error('Unable to find form #' + formData.id);
            }
            formNode = formNode[0];

            // This method will usually be called from PHP via $PAGE->requires->js_call_amd() and the
            // return value discarded. However return a promise so that it's possible to execute code
            // when all forms have successfully bootstrapped (as includes async operations such as
            // requiring AMD modules) when called manually from JS. This will be important for testing.

            MODULE.debug('Preventing form #' + formData.id + ' submission during bootstrapping', Form, MODULE.LOGLEVEL.debug);
            var form = new Form({el: formNode}),
                itemNodes,
                actionsConfig = formData.actionsConfig,
                deferred = $.Deferred();

            this.forms[form.id] = form;
            if (this.listenerQueue.hasOwnProperty(form.id)) {
                form.addActionListener(this.listenerQueue[form.id]);
            }

            // We are initialising the form, mark it as working. This ensures behat knows to wait.
            var working = form.setFormWorking('init');
            itemNodes = form.getItemNodes();

            if (itemNodes.length === 0) {
                throw new Error('Form [#' + this.id + '] must contain at least one element or group.');
            }

            form.initItems(itemNodes).then(
                function () {
                    form.initClientActions(actionsConfig).done(function () {
                        deferred.resolve(form);
                    });
                },
                function (e) {
                    MODULE.debug('Failed to initialise items in form #' + formData.id, Form, MODULE.LOGLEVEL.info);
                }
            );

            return deferred.promise().done(function (form) {

                var formNode = form.node;

                MODULE.debug('Re-enabling submission of form #' + formNode.getAttribute('id'), Form, MODULE.LOGLEVEL.debug);

                // If there are custom validation functions then use them and switch off HTML5 validation.
                var useCustomValidation = form.getElements(true).some(function (element) {
                    return typeof element.isValid === 'function';
                });
                if (useCustomValidation) {
                    form.setCustomValidation();
                }

                formNode.setAttribute('data-totara_form-initialised', true);
                working.resolve();

                MODULE.debug('Initialised form #' + formData.id + '.', Form, MODULE.LOGLEVEL.success);
            });

        },

        /**
         * Extends one object with the properties belonging to the second.
         *
         * @param {Object} parentObj The object to take from
         * @param {Object} Obj The objext to apply to
         * @param {Object} [objProto] A prototype object to mix in.
         * @returns {Object}
         */
        extend: function (parentObj, Obj, objProto) {

            if (!Obj || Obj === 'undefined') {
                Obj = function () {
                    // Call the parent object constructor given the exact arguments provided here.
                    parentObj.apply(this, arguments);
                };
            }
            // Inherit from parentObj base class.
            Obj.prototype = Object.create(parentObj.prototype);
            // Set the constructor object back to self.
            Obj.prototype.constructor = Obj;
            $.extend(Obj.prototype, objProto);
            return Obj;

        },

        /**
         * Loads a form from the server using AJAX.
         *
         * @param {string} formclass
         * @param {string} idsuffix
         * @param {Object} parameters
         * @param {string} target
         */
        load: function (formclass, idsuffix, parameters, target) {

            var formdata = $.extend(parameters, {
                    sesskey: CFG.sesskey,
                    ___tf_formclass: formclass,
                    ___tf_idsuffix: idsuffix
                }),
                container = $('#' + target),
                deferred = $.Deferred();

            M.util.js_pending('totara_form-loading');

            $.ajax({
                url: CFG.wwwroot + '/totara/form/ajax.php',
                data: formdata,
                context: this,
                success: function (data) {
                    if (data.formstatus === 'display') {
                        MODULE.debug('Displaying the form for the first time.', Form, MODULE.LOGLEVEL.debug);
                        container.empty();
                        Templates.render(data.templatename, data.templatedata).done(function(html, js) {
                            Templates.replaceNodeContents($('#' + target), html, js);
                            addCompletewatch(data.templatedata.formid).then(function() {
                                M.util.js_complete('totara_form-loading');
                                deferred.resolve('display', data.templatedata.formid);
                            });
                        }).fail(Notification.exception);
                    } else {
                        MODULE.debug('Unexpected form response', Form, MODULE.LOGLEVEL.warn);
                        deferred.reject();
                    }
                },
                error: function(jqXHR, status, error) {
                    deferred.reject(jqXHR, status, error);
                },
                dataType: 'json'
            });

            return deferred.promise();
        },

        /**
         * Returns a form instance given its ID.
         *
         * @param {String} id
         * @returns {Form}
         */
        getFormInstance: function(id) {
            if (this.forms.hasOwnProperty(id)) {
                return this.forms[id];
            }
            return null;
        },

        /**
         * Adds action listeners to a form.
         *
         * @param {String} id
         * @param {Object} listeners
         */
        addActionListeners: function(id, listeners) {
            var form = this.getFormInstance(id);
            if (form === null) {
                this.listenerQueue[id] = listeners;
            } else {
                form.addActionListener(listeners);
            }
        },

        /**
         * Sends a debug message to the console, if debugging is enabled.
         *
         * @param {string} message The message to show in the console.
         * @param {Object} obj The object sending the debug message.
         * @param {string} level The debug level, one of
         */
        debug: function (message, obj, level) {
            if (debug) {
                if (!window.console.log.apply) {
                    // IE9 doesn't provide apply on developer tool methods.
                    if (window.console.log) {
                        window.console.log(message);
                    }
                    return;
                }
                if (!level) {
                    level = MODULE.LOGLEVEL.none;
                }
                if (!obj) {
                    message = '%cForm: %c' + message;
                } else if (obj.prototype && obj.prototype.constructor && obj.prototype.constructor.name) {
                    message = '%cForm.' + obj.prototype.constructor.name + ': %c' + message;
                } else if (obj.constructor && obj.constructor.name) {
                    message = '%cForm.' + obj.constructor.name + ': %c' + message;
                } else {
                    message = '%cForm: %c' + message;
                }

                var light = '#cb8bbe',
                    dark = '#ae45a3';
                switch (level) {
                    case MODULE.LOGLEVEL.debug:
                        window.console.log.apply(window.console, [
                            message,
                            'color: ' + light + '; font-style: italic; padding-left:2em;',
                            'color: #454545; font-style: italic;'
                        ]);
                        break;
                    case MODULE.LOGLEVEL.error:
                        window.console.log.apply(window.console, [message, 'color: ' + dark + '; font-weight: bold;',
                            'color: #c74939;']);
                        break;
                    case MODULE.LOGLEVEL.warn:
                        window.console.log.apply(window.console, [message, 'color: ' + dark + '; font-weight: bold;',
                            'color: #c68c25;']);
                        break;
                    case MODULE.LOGLEVEL.success:
                        window.console.log.apply(window.console, [message, 'color: ' + dark + '; font-weight: bold;',
                            'color: #2da833;']);
                        break;
                    case MODULE.LOGLEVEL.info:
                        window.console.log.apply(window.console, [message, 'color: ' + dark + '; font-weight: bold;',
                            'color: #235aac;']);
                        break;
                    default:
                        window.console.log.apply(window.console, [message, '', '']);
                        break;
                }
            }
        }
    };

    return MODULE;

});
