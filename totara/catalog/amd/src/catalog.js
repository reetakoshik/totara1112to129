/**
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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara
 * @subpackage totara_catalog
 */

define(['core/templates', 'core/ajax', 'core/notification', 'core/event'], function(templates, ajax, notification, events) {

    /**
     * Class constructor for the Catalog.
     *
     * @class
     * @constructor
     */
    function Catalog() {
        if (!(this instanceof Catalog)) {
            return new Catalog();
        }

        this.filterPanelCount = 0;
        this.overlayClass = 'tw-catalog__overlay';
        this.activePopoverClass = 'tw-catalog__activePopover';
        this.widget = '';
        this.fetchedData = '';
        this.pageChangeType = '';
        this.requestData = {
            debug: false,
            filterparams: {},
            limitfrom: 0,
            maxcount: -1,
            orderbykey: 'featured',
            request: 1,
            resultsonly: false,
            itemstyle: 'narrow'
        };
        this.requestItemData = {
            catalogid: 0,
            request: 1,
        };
        this.lastRequest = {
            catalogid: ''
        };
        this.requestEpoch = '';
        this.requestPending = false;
    }

    Catalog.prototype = {
        // Ensure instanceof knows this is a catalog
        constructor: Catalog,

        /**
        * Add event listeners for sub-widget bubbled events
        *
        */
        bubbledEventsListener: function() {
            var that = this;

            var selectRegionPanelEvents = 'totara_core/select_region_panel',
                selectRegionPrimaryEvents = 'totara_core/select_region_primary',
                grid = 'totara_core/grid',
                limitFromEvents = 'totara_catalog/pagination',
                resultsSort = 'totara_catalog/results_sort',
                itemStyleToggle = 'totara_catalog/item_style_toggle',
                toggleFilterPanel = 'totara_catalog/toggle_filter_panel';

            // Listener for filter
            this.widget.addEventListener(selectRegionPanelEvents + ':add', function(e) {
                that.setFilterAdd(e.detail);
                that.filterPanelCount++;
                that.setFilterPanelToggleCount();
            });
            this.widget.addEventListener(selectRegionPanelEvents + ':changed', function() {
                that.eventFilterChange();
            });
            this.widget.addEventListener(selectRegionPanelEvents + ':remove', function(e) {
                that.setFilterRemove(e.detail);
                that.filterPanelCount--;
                that.setFilterPanelToggleCount();
            });

            // Listener for primary filters
            this.widget.addEventListener(selectRegionPrimaryEvents + ':add', function(e) {
                // When providing a search value for the first time, change the default order by.
                if (e.detail.key === 'catalog_fts' && !that.requestData.filterparams.catalog_fts) {
                    that.setOrderBy({'val': 'score'});
                }
                that.setFilterAdd(e.detail);
            });
            this.widget.addEventListener(selectRegionPrimaryEvents + ':changed', function() {
                that.eventFilterChange();
            });
            this.widget.addEventListener(selectRegionPrimaryEvents + ':remove', function(e) {
                that.setFilterRemove(e.detail);
            });

            // Listen for sort filter
            this.widget.addEventListener(resultsSort + ':add', function(e) {
                that.setOrderBy(e.detail);
            });
            this.widget.addEventListener(resultsSort + ':changed', function() {
                that.eventFilterChange();
            });

            // Listen for item style filter
            this.widget.addEventListener(itemStyleToggle + ':add', function(e) {
                that.setItemStyle(e.detail);
            });
            this.widget.addEventListener(itemStyleToggle + ':changed', function() {
                that.eventFilterChange();
            });

            // Listener for change page request
            this.widget.addEventListener(limitFromEvents + ':changed', function(e) {
                that.setLimitFrom(e.detail.limitfrom);
                that.setMaxCount(e.detail.maxcount);
                that.setPageChangeType('resultsOnly');
                that.setResultsOnly(true);
                that.contentRequest();
            });

            // Listener for mobile toggle events
            this.widget.addEventListener(toggleFilterPanel + ':changed', function(e) {
                var target = that.widget.querySelector(e.detail.targetwidget);
                target.classList.toggle(e.detail.toggleClass);
            });

            // Listeners for grid
            this.widget.addEventListener(grid + ':add', function(e) {
                // If repeat of last request, stop
                if (e.detail.val === that.lastRequest.catalogid) {
                    return;
                }
                that.setItemAdd(e.detail);
                that.setPageChangeType('details');
                that.contentRequest();
            });
        },

        /**
         * Check if debug URL param exists
         *
         */
        checkDebugMode: function() {
            var queryString = window.location.search.slice(1);
            queryString = queryString.split('#')[0];
            var queryStringList = queryString.split('&');
            for (var s = 0; s < queryStringList.length; s++) {
                if (queryStringList[s] === 'debug=true' || queryStringList[s] === 'debug=1') {
                    this.requestData.debug = true;
                }
            }
        },

        /**
         * Handle change page request, fetching required JSON
         *
         */
        contentRequest: function() {
            var that = this,
                pageData;

            this.loadingDisplayAdd().then(function() {
                // Set unique request key
                that.setRequestEpoch(Date.now());
                // Make data request using current requestData
                pageData = that.getData();

                // Request completed
                pageData.then(function(data) {
                    // If this is not the latest request, stop
                    if (data.request != that.requestEpoch) {
                        return;
                    }
                    // Set data to new fetched data
                    that.setData(data);

                    // Check if template render currently in progress
                    var renderState = that.widget.getAttribute('data-tw-catalog-jsRenderState');
                    if (renderState === 'locked') {
                        // Render blocked, Will retrigger this task from observer observeCatalogRendering once ready
                        that.requestPending = true;
                    } else {
                        // Update page
                        that.widget.setAttribute('data-tw-catalog-jsRenderState', 'locked');
                        that.contentUpdate();
                        that.contentRequestPageURL();
                    }
                });
            });
        },

        contentRequestPageURL: function() {
            if (this.pageChangeType === 'details') {
                return;
            }

            // Update page URL but drop hidden values first
            var urlData = Object.assign({}, this.requestData);
            delete urlData.limitfrom;
            delete urlData.maxcount;
            delete urlData.request;
            delete urlData.resultsonly;
            delete urlData.debug;
            this.setPageURL(this.getRequestPathString(urlData));
        },

        /**
         * Update the specified page widgets
         *
         */
        contentUpdate: function() {
            var that = this,
                fetchedData = this.fetchedData,
                pageChangeType = this.pageChangeType,
                promiseList;

            var debugOutput = {
                    data: fetchedData,
                    renderType: 'fullHTML',
                    target: this.widget.querySelector('[data-tw-catalogDebug]'),
                    template: 'totara_catalog/debug',
                },
                limitFromBtnRequest = {
                    data: fetchedData.pagination_template_data,
                    renderType: 'innerHTML',
                    target: this.widget.querySelector('[data-tw-pagination]'),
                    template: 'totara_catalog/pagination',
                },
                resultsCount = {
                    data: fetchedData,
                    renderType: 'innerHTML',
                    target: this.widget.querySelector('[data-tw-catalogResultsCount]'),
                    template: 'totara_catalog/results_count'
                },
                resultsEmpty = {
                    data: fetchedData,
                    renderType: 'fullHTML',
                    target: this.widget.querySelector('[data-tw-catalogResultsEmpty]'),
                    template: 'totara_catalog/results_empty'
                },
                sortTree = {
                    data: fetchedData,
                    renderType: 'innerHTML',
                    target: this.widget.querySelector('[data-tw-catalogResultsSort]'),
                    template: 'totara_catalog/results_sort'
                },
                itemGridRequest = {
                    data: fetchedData.grid_template_data,
                    renderType: 'additionalItemHTML',
                    target: this.widget.querySelector('[data-tw-grid]'),
                    template: 'totara_core/grid'
                },
                detailsRequest = {
                    data: fetchedData,
                    renderType: 'fullHTML',
                    target: this.widget.querySelector('[data-tw-grid-item-id="' + fetchedData.id + '"] [data-tw-catalogDetails]'),
                    template: 'totara_catalog/details'
                };

            // Update requsted page elements
            if (pageChangeType === 'filter') {
                M.util.js_pending('totara_catalog-rendering_template');
                promiseList = Promise.all([
                    this.contentUpdateRender(itemGridRequest),
                    this.contentUpdateRender(resultsCount),
                    this.contentUpdateRender(limitFromBtnRequest),
                    this.contentUpdateRender(sortTree),
                    this.contentUpdateRender(debugOutput),
                    this.contentUpdateRender(resultsEmpty),
                ]);
            } else if (pageChangeType === 'details') {
                M.util.js_pending('totara_catalog-rendering_template');
                promiseList = Promise.all([
                    this.contentUpdateRender(detailsRequest)
                ]);
            } else if (pageChangeType === 'resultsOnly') {
                M.util.js_pending('totara_catalog-rendering_template');
                promiseList = Promise.all([
                    this.contentUpdateRender(itemGridRequest),
                    this.contentUpdateRender(resultsCount),
                    this.contentUpdateRender(limitFromBtnRequest),
                    this.contentUpdateRender(resultsEmpty),
                ]);
            } else {
                that.widget.setAttribute('data-tw-catalog-jsRenderState', 'ready');
                this.loadingDisplayRemove();
                return;
            }

            promiseList.then(function() {
                that.widget.setAttribute('data-tw-catalog-jsRenderState', 'ready');
                that.loadingDisplayRemove();
                that.setPageChangeType('');
                M.util.js_complete('totara_catalog-rendering_template');
            }).catch(function(error) {
                that.pageError();
                notification.exception({
                    fileName: 'catalog.js',
                    message: error,
                    name: 'Error rendering template',
                });
                that.widget.setAttribute('data-tw-catalog-jsRenderState', 'ready');
            });
        },

        /**
         * Render requested template
         *
         * @param {Object} renderData
         * @returns {Object} promise
         */
        contentUpdateRender: function(renderData) {
            var data = renderData.data,
                html,
                range = document.createRange(),
                renderType = renderData.renderType,
                targetContainer = renderData.target,
                template = renderData.template,
                that = this;

            return new Promise(function(resolve, reject) {
                // Give the template 45 seconds to render or fail
                var fallOver = setTimeout(function() {
                    reject('Communication error, could not render ' + template);
                }, 45000);

                // Render requested HTML & add new content
                templates.render(template, data).then(function(htmlString) {
                    // If the target container doesn't exist, page state had changed, resolve
                    if (!targetContainer) {
                        resolve(template);
                        return;
                    }

                    range.selectNode(targetContainer);
                    html = range.createContextualFragment(htmlString);

                    if (renderType === 'additionalItemHTML') {
                        var renderItems = '[data-tw-grid-item]',
                            items = html.querySelectorAll(renderItems);
                        if (!(that.pageChangeType === 'resultsOnly')) {
                            // Clear current items if not a page change
                            targetContainer.innerHTML = '';
                        }

                        for (var i = 0; i < items.length; i++) {
                            targetContainer.appendChild(items[i]);
                        }
                        that.showOverflowEllipsis();
                        // Set focus to first new item & bring into view
                        if (items && that.pageChangeType === 'resultsOnly') {
                            var firstNewItem = items[0].querySelector('[data-tw-grid-item-toggle]');
                            firstNewItem.focus();
                            firstNewItem.scrollIntoView({block: 'start'});
                        }
                    } else if (renderType === 'loadingDisplay') {
                        targetContainer.appendChild(html);
                    } else if (renderType === 'fullHTML') {
                        targetContainer.innerHTML = '';
                        targetContainer.appendChild(html);
                    } else if (renderType === 'innerHTML') {
                        var parentHTML = html.querySelector('div');
                        var childHTML = parentHTML.querySelector('div');
                        targetContainer.innerHTML = '';
                        targetContainer.appendChild(childHTML);
                    }
                    // Update
                    events.notifyFilterContentUpdated(targetContainer);
                    // Run global scan
                    templates.runTemplateJS('');

                    // Promise complete
                    clearTimeout(fallOver);
                    resolve(template);
                });
            });
        },

        /**
         * Initialise event listeners
         *
         */
        events: function() {
            var that = this;
            // If browser moving back/forward in history
            window.onpopstate = function(e) {
                // State data available
                if (e.state !== null) {
                    location.reload();
                }
            };

            // Mobile / Desktop resize changes
            window.addEventListener('resize', function() {
                var resizeTimeout;
                if (!resizeTimeout) {
                    resizeTimeout = setTimeout(function() {
                        resizeTimeout = null;

                        // Reset checked ellipsis & check again
                        var nodeList = that.widget.querySelectorAll('[data-tw-catalogItem="checked"]');
                        for (var s = 0; s < nodeList.length; s++) {
                            nodeList[s].setAttribute('data-tw-catalogItem', 'check');
                            nodeList[s].classList.remove('tw-catalogItem__showEllipsis');
                        }
                        that.showOverflowEllipsis();

                    // Execute at a rate of 15fps
                    }, 66);
                }
            });

            // Listener for closing overlays when not interacting with them
            document.addEventListener('click', function(e) {
                // Check if any overlay on catalogue
                var overlayItem = document.querySelector('.' + that.activePopoverClass);
                if (!overlayItem || !e.target) {
                    return;
                }

                // If part of overlay, don't close
                var overlayParent = e.target.closest('.' + that.activePopoverClass);
                if (overlayParent) {
                    return;
                }

                overlayItem.classList.remove(that.activePopoverClass);
            });

            /*
            // We need to keep the UI components in sync with each other
            // When a group render starts, we need to let it finish before starting the next render
            // To track this we are using the following observer
            */
            var observeCatalogRendering = new MutationObserver(function(mutations) {
                var newValue = that.widget.getAttribute('data-tw-catalog-jsRenderState');

                if (!that.requestPending || newValue === 'locked') {
                    return;
                }

                for (var i = 0; i < mutations.length; i++) {
                    var oldValue = mutations[i].oldValue;
                    if (newValue === 'ready' && oldValue === 'locked') {
                        that.contentUpdate();
                        that.contentRequestPageURL();
                    }
                }
            });
            observeCatalogRendering.observe(this.widget, {
                attributes: true,
                attributeFilter: ['data-tw-catalog-jsrenderstate'],
                attributeOldValue: true,
                subtree: false
            });
        },

        /**
         * Trigger filter change calls
         *
         */
        eventFilterChange: function() {
            this.setPageChangeType('filter');
            this.setResultsOnly(false);
            this.setLimitFrom('0');
            this.setMaxCount('-1');
            this.contentRequest();
        },

        /**
         * Make ajax request for page data
         *
         * @param {object} requestPathData data object
         * @returns {object} response data
         */
        getData: function() {
            var that = this,
                requestArgs,
                requestMethod;

            if (this.pageChangeType === 'details') {
                requestArgs = this.requestItemData;
                requestMethod = 'totara_catalog_external_get_details_template_data';
            } else {
                // Make sure details request isn't blocked after rerender
                that.setLastItemRemove();
                requestArgs = this.requestData;
                requestMethod = 'totara_catalog_external_get_catalog_template_data';
            }

            var ajaxrequests = [{
                args: requestArgs,
                methodname: requestMethod,
            }];

            M.util.js_pending('totara_catalog-requesting_data');
            return new Promise(function(resolve, reject) {
                var request = ajax.call(ajaxrequests, true, true);

                request[0].done(function(results) {
                    resolve(results);
                    M.util.js_complete('totara_catalog-requesting_data');
                }).fail(function(ex) {
                    notification.exception(ex);
                    reject(ex);
                    that.pageError();
                    M.util.js_complete('totara_catalog-requesting_data');
                });
            });
        },

        /**
         * Return URL string
         *
         * @param {object} requestPathData data object
         * @returns {string}
         */
        getRequestPathString: function(requestPathData) {
            var pathString = '';

            Object.keys(requestPathData).forEach(function(key) {
                var value = requestPathData[key];

                if (key === 'filterparams') {
                    Object.keys(requestPathData.filterparams).forEach(function(filterKey) {
                        var filterValue = requestPathData.filterparams[filterKey];

                        if (Array.isArray(filterValue)) {
                            if (filterValue.length) {
                                for (var i = 0; i < filterValue.length; i++) {
                                    pathString += (pathString === '') ? '?' : '&';
                                    pathString += filterKey + '[]=' + filterValue[i];
                                }
                            }
                        } else {
                            pathString += (pathString === '') ? '?' : '&';
                            pathString += filterKey + '=' + filterValue;
                        }
                    });

                } else {
                    pathString += (pathString === '') ? '?' : '&';
                    pathString += key + '=' + value;
                }
            });
            return (pathString);
        },

        /**
         * Add page loading display based on change type
         *
         * @returns {Object} promise
         */
        loadingDisplayAdd: function() {
            var that = this,
                parent;

            if (this.pageChangeType === 'details') {
                parent = this.widget.querySelector('[data-tw-grid-item-active] [data-tw-catalogDetails]');
            } else if (this.pageChangeType === 'filter' || this.pageChangeType === 'resultsOnly') {
                parent = this.widget.querySelector('[data-tw-grid]');
            } else {
                parent = this.widget.querySelector('[data-tw-catalogContent]');
            }

            // Add overlay to correct container
            parent.classList.add(that.overlayClass);

            var loadingRequest = {
                data: '',
                renderType: 'loadingDisplay',
                target: parent,
                template: 'totara_catalog/loading_overlay'
            };

            M.util.js_pending('totara_catalog-rendering_template');
            return new Promise(function(resolve, reject) {
                that.contentUpdateRender(loadingRequest).then(function() {
                    resolve();
                    M.util.js_complete('totara_catalog-rendering_template');
                }).catch(function() {
                    reject();
                });
            });
        },

        /**
         * Remove page loading display
         *
         */
        loadingDisplayRemove: function() {
            var loadingOverlayItems = this.widget.querySelectorAll('[data-tw-catalog-overlay]'),
                loadingOverlays = this.widget.parentNode.querySelectorAll('.' + this.overlayClass);

            for (var i = 0; i < loadingOverlayItems.length; i++) {
                loadingOverlayItems[i].remove();
            }

            for (var s = 0; s < loadingOverlays.length; s++) {
                loadingOverlays[s].classList.remove(this.overlayClass);
            }
        },

        /**
         * Remove existing results, count & loading display
         *
         */
        pageError: function() {
            this.setPageChangeType('');
            this.loadingDisplayRemove();
        },

        /**
         * Set fetched JSON for page refresh
         *
         * @param {object} data JSON data
         */
        setData: function(data) {
            this.fetchedData = data;
        },

        /**
         * Update requestData with added filter
         *
         * @param {string} filter
         */
        setFilterAdd: function(filter) {
            var filtersList = this.requestData.filterparams;
            if (filter.groupValues) {
                filtersList[filter.key] = filter.groupValues;
            } else {
                // Prevent duplicate keys
                this.setFilterRemove(filter);
                filtersList[filter.key] = filter.val;
            }
        },

        /**
         * Update requestData with removed filter
         *
         * @param {string} filter
         */
        setFilterRemove: function(filter) {
            var filtersList = this.requestData.filterparams;
            if (filter.groupValues) {
                filtersList[filter.key] = filter.groupValues;
            } else {
                delete filtersList[filter.key];
            }
        },

        /**
         * Add count to mobile filter toggle btn
         *
         */
        setFilterPanelToggleCount: function() {
            var toggleBtnContent = this.filterPanelCount >= 1 ? '( ' + this.filterPanelCount + ' )' : '',
                node = this.widget.querySelector('[data-tw-toggleFilterPanel]');
            node.setAttribute('data-tw-toggleFilterPanel-addLabelContent', toggleBtnContent);
        },

        /**
         * Update 'limit from' number
         *
         * @param {string} limitFrom
         */
        setLimitFrom: function(limitFrom) {
            this.requestData.limitfrom = limitFrom;
        },

        /**
         * Update 'max count' number
         *
         * @param {string} maxCount
         */
        setMaxCount: function(maxCount) {
            this.requestData.maxcount = maxCount;
        },

        /**
         * Update orderBy
         *
         * @param {string} type
         */
        setOrderBy: function(type) {
            this.requestData.orderbykey = type.val;
        },

        /**
         * Set page change type
         *
         * @param {string} type type of change (filter, resultsOnly, details)
         */
        setPageChangeType: function(type) {
            this.pageChangeType = type;
        },

        /**
         * Change the page URL and history
         *
         * @param {string} requestPathDataString string of request data object
         */
        setPageURL: function(requestPathDataString) {
            var urlPath = window.location.pathname + requestPathDataString;
            history.pushState({ajaxPageChange: true}, null, urlPath);
        },

        /**
         * Set widget parent
         *
         * @param {node} widgetParent
         */
        setParent: function(widgetParent) {
            this.widget = widgetParent;
        },

        /**
         * Set epoch for request locking
         *
         * @param {int} epoch
         */
        setRequestEpoch: function(epoch) {
            this.requestEpoch = epoch;
            if (this.pageChangeType === 'details') {
                this.requestItemData.request = epoch;
            } else {
                this.requestData.request = epoch;
            }
        },

        /**
         * Show overflow ellpisis
         *
         */
        showOverflowEllipsis: function() {
            var node,
                nodeHeight,
                nodeList = this.widget.querySelectorAll('[data-tw-catalogItem="check"]'),
                parentHeight;

            for (var s = 0; s < nodeList.length; s++) {
                node = nodeList[s];
                nodeHeight = node.offsetHeight;
                parentHeight = node.parentNode.offsetHeight;

                // Display ellipsis when hidden content
                if (nodeHeight > parentHeight) {
                    node.classList.add('tw-catalogItem__showEllipsis');
                }

                node.setAttribute('data-tw-catalogItem', 'checked');
            }
        },

        /**
         * Set Results Only
         *
         * @param {bool} state
         */
        setResultsOnly: function(state) {
            this.requestData.resultsonly = state;
        },

        /**
         * Add selected item ID to requestData
         *
         * @param {string} filterID
         */
        setItemAdd: function(filterID) {
            this.setItemRemove();
            this.requestItemData.catalogid = filterID.val;
            this.lastRequest.catalogid = filterID.val;
        },

        /**
         * Remove selected item ID from requestData
         *
         */
        setItemRemove: function() {
            this.requestItemData.catalogid = '';
        },

        /**
         * Update itemstyle
         *
         * @param {string} style
         */
        setItemStyle: function(style) {
            this.requestData.itemstyle = style.val;
        },

        /**
         * Remove selected item ID from requestData
         *
         */
        setLastItemRemove: function() {
            this.lastRequest.catalogid = '';
        },

    };

    /**
     * Initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            // Create an instance of catalog
            var wgt = new Catalog();
            wgt.setParent(parent);
            wgt.bubbledEventsListener();
            wgt.events();
            wgt.checkDebugMode();
            wgt.showOverflowEllipsis();
            wgt.loadingDisplayRemove();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });