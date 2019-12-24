/**
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package block_current_learning
 */

define(['jquery',
        'core/templates',
        'core/notification',
        'core/str',
        'core/log'
        ], function($, templates, notification, mdlstr, log) {

    var _blockData = null;

    // This gets set on init.
    var instanceselector;

    // This gets overridden on initialisation.
    var items_per_page = 10;

    var filter_data_for_paging = function(data, page) {
        // Get the current page.
        var currentPage = parseInt($(instanceselector+' .pagination li.active a').attr('data-page'));
        var newPage = null;

        if (page == 'next') {
            newPage = currentPage + 1;
        } else if (page == 'prev') {
            newPage = currentPage - 1;
        } else {
            newPage = page;
        }

        var numOfItems = data.learningitems.length;
        var numOfPages = Math.ceil(numOfItems / items_per_page);

        var result = {};
        var pageStart = ((newPage - 1) * items_per_page) + 1;

        // Get the 10 items assigned to the page.
        result.learningitems = data.learningitems.slice(pageStart - 1, (pageStart - 1) + items_per_page);

        // Update the paging data to reflect the page change.
        var pagingData = data.pagination;
        if (newPage > 1) {
            pagingData.previousclass = '';
        } else {
            pagingData.previousclass = 'disabled';
        }

        // If we are at the end then disable the next page button.
        if (newPage >= numOfPages) {
            pagingData.nextclass = 'disabled';
        } else {
            pagingData.nextclass = '';
        }

        // If we are at the start disable the previous page button.
        pagingData.pages.forEach(function(p) {
            if (p.page == newPage) {
                p.active = 'active';
            } else {
                p.active = '';
            }
        });

        // Update display text.
        var strData = {};
        var stringDeferred = $.Deferred();
        strData.start = ((newPage - 1) * items_per_page) + 1;
        var pageEnd = null;
        if (data.pagination.totalitems < newPage * items_per_page) {
            pageEnd = data.pagination.totalitems;
        } else {
            pageEnd = (newPage * items_per_page);
        }
        strData.end = pageEnd;
        strData.total = data.pagination.totalitems;
        mdlstr.get_string('displayingxofx', 'block_current_learning', strData)
            .done(function(paginationString) {
                pagingData.text = paginationString;
                result.pagination = pagingData;
                // If we are changing pages there must be learningitems.
                result.haslearningitems = true;
                stringDeferred.resolve(result);
            }).fail(function(err) {
                stringDeferred.reject(err);
            });

        return stringDeferred.promise();
    };

    /**
     * Initialises handlers for pagination.
     */
    var initPaginationHandlers = function() {
        $(instanceselector+' .pagination').on('click', 'a', function(e) {
            e.preventDefault();

            var anchor = $(this); // The <li>
            var parent = anchor.parent();

            // If the button clicked is disabled then return.
            if (parent.hasClass('disabled')) {
                return false;
            }

            var page = anchor.attr('data-page');

            filter_data_for_paging(_blockData, page).done(function(filteredData) {
                templates.render('block_current_learning/main_content', filteredData).done(function(rendered, js) {
                    $(instanceselector+' .current-learning-content').replaceWith(rendered).trigger("block_current_learning:content_updated");
                    templates.runTemplateJS(js);
                }).fail(function(error) {
                    notification.exception(error);
                });

                // Re-render the footer.
                templates.render('block_current_learning/paging', filteredData).done(function(rendered) {
                    $(instanceselector+' .panel-footer').replaceWith(rendered);
                    initPaginationHandlers();
                }).fail(function(error) {
                    notification.exception(error);
                });
            });
        });
    };

    /**
     * Initialises tooltips using Bootstrap JS.
     */
    var initTooltips = function() {
        var tooltipSelector = instanceselector+' [data-toggle="tooltip"]';
        $(tooltipSelector).tooltip();
        $(instanceselector+' .current-learning-content').on('block_current_learning:content_updated', function(event) {
            $(tooltipSelector).tooltip();
        });
    };

    /**
     * Keep the height of the block when changing to a page with fewer
     * items than the page page limit.
     *
     * @param {Object} blockData
     */
    var initStyles = function(blockData) {
        if (blockData.pagination.pages.length > 1) {
            var mylearningPanel = $(instanceselector+' .current-learning-content');
            mylearningPanel.css('min-height', mylearningPanel.outerHeight());
        }
    };

    /**
     * Init function for the block
     *
     * @param {Object} blockData
     */
    var init = function(blockData) {

        instanceselector = '#inst'+blockData.instanceid.toString();

        if (blockData.pagination && blockData.pagination.itemsperpage) {
            items_per_page = blockData.pagination.itemsperpage;
        }

        var bsjavascript = true;
        if (!$.fn.collapse || !$.fn.tooltip) {
            log.debug('Current learning block requires Bootstrap 3 JavaScript, please include it or find your own solution.');
            bsjavascript = false;
        }

        _blockData = blockData;

        var blockTemplates = [
            'block_current_learning/main_content',
            'block_current_learning/course_row',
            'block_current_learning/program_row',
        ];

        // Preload Templates to cache them on the client
        // preventing a delay when a user first performs an
        // action that required the templates to be re-rendered.
        blockTemplates.forEach(function(template) {
            templates.render(template, {});
        });

        if (blockData.pagination) {
            // Separate preloader for paging as it needs some dummy data.
            var dummyPagingData = {'pagination': {'onepage': false, 'previousclass': 'disabled', 'nextclass': ''}};
            templates.render('block_current_learning/paging', dummyPagingData);
            mdlstr.get_string('displayingxofx', 'block_current_learning', {'start': 1, 'end': 2, 'total': 2});

            initPaginationHandlers();
            initStyles(blockData);
        }

        if (bsjavascript) {
            initTooltips();
        }
    };

    return {
        init: init
    };
});
