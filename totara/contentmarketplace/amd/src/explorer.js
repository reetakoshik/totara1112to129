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
 * @package totara_contentmarketplace
 */

/**
 *
 * @module     totara_contentmarketplace/explorer
 * @class      explorer
 * @package    totara_contentmarketplace
 */

define(['jquery', 'core/str', 'core/config', 'core/templates', 'core/modal_factory', 'core/modal_events', 'totara_contentmarketplace/filters'], function($, Str, mdlcfg, templates, ModalFactory, ModalEvents, Filters) {

    // This must match \totara_contentmarketplace\explorer::MODE_CREATE_COURSE
    var MODE_CREATE_COURSE = 'create-course';

    var explorer = {};

    explorer.selector = null;
    explorer.haveQueriedOrSortedAtLeastOnce = false;
    explorer.selected = [];
    explorer.totalResults = 0;
    explorer.maxSelectAll = 10000;
    explorer.maxCreateCourse = 100;
    explorer.warnCreateCourse = 11;
    explorer.filters = null;
    explorer.lockId = 0;
    explorer.createpagepath = "";

    explorer.init = function (selector) {
        var self = this,
            context = $(selector);
        this.selector = selector;
        this.createpagepath = context.data('createpagepath');
        this.marketplace = context.data('marketplace');
        this.mode = context.data('mode');
        if (this.mode === MODE_CREATE_COURSE) {
            $('.tcm-collection-tool', this.selector).hide();
        }
        this.category = context.data('category');
        $('.tcm-sorting select', context).on('change', this, function(event) {event.preventDefault(); event.data.sort();});
        $('.tcm-search_query form', context).on('submit', this, function(event) {event.preventDefault(); event.data.search();});
        this.filters = new Filters(selector, this.search.bind(this), this.marketplace, this.mode, this.category);
        $('.tcm-load-more button', context).on('click', this, function(event) {event.preventDefault(); event.data.more();});
        context.on('change', '.tcm-thumbnail_selection input', this, function(event) {
            event.preventDefault();
            if ($(event.target).is(':checked')) {
                self.addToSelection($(event.target).val());
            } else {
                self.removeFromSelection($(event.target).val());
            }
        });
        context.on('click', '.tcm-thumbnail_selection label, .tcm-thumbnail_selection input', function(event) {
            // Selecting an item is not the same as showing its details.
            event.stopPropagation();
        });
        context.on('click', '.tcm-search-thumbnail', function(event) {
            event.preventDefault();
            var id = $(this).find('input').val();
            self.showDetails(id);
        });
        $('.tcm-search_selection_tools', context).on('click', '.tcm-create-course.tcm-tool-enabled', this, function(event) {
            event.preventDefault();
            event.data.createCourse();
        });
        $('.tcm-add-to-collection', context).on('click', this, function(event) {event.preventDefault(); event.data.addToCollection();});
        $('.tcm-remove-from-collection', context).on('click', this, function(event) {event.preventDefault(); event.data.removeFromCollection();});
        $('.tcm-select-all', context).on('click', this, function(event) {event.preventDefault(); event.data.selectAll();});
        $('.tcm-deselect-all', context).on('click', this, function(event) {event.preventDefault(); event.data.deselectAll();});
        this.search();
    };

    explorer.sort = function() {
        this.haveQueriedOrSortedAtLeastOnce = true;
        this.search();
    };

    explorer.search = function() {
        var context = $(this.selector),
            self = this,
            query,
            data,
            lock = this.lock();

        context.addClass('tcm-is-searching');
        $('.tcm-search_result_summary', context).html("");

        query = $('.tcm-search_query input', context).val().trim();

        data = {
            sesskey: mdlcfg.sesskey,
            page: 0,
            query: query,
            sort: $('.tcm-sorting select', context).val(),
            multivaluefilters: [],
            singlevaluefilters: [],
            marketplace: this.marketplace,
            category: this.category,
            mode: this.mode
        };

        if (!this.haveQueriedOrSortedAtLeastOnce && query !== '') {
            this.haveQueriedOrSortedAtLeastOnce = true;
            data.isfirstquerywithdefaultsort = true;
        }

        $.each(this.filters.getValues(), function(name, value) {
            if (value instanceof Array) {
                data["multivaluefilters"].push(name);
            } else {
                data["singlevaluefilters"].push(name);
            }
            data["filter-" + name] = value;
        });

        this.data = data;
        $.post({
            url: mdlcfg.wwwroot + "/totara/contentmarketplace/ajax/search.php",
            data: data
        }).done(function(data) {
            self.totalResults = data.total;
            templates.render('totara_contentmarketplace/results', data).done(function(html) {
                if (!self.checkLock(lock)) {
                    // Not the current request
                    return;
                }
                html = $(html);
                $('.tcm-thumbnail_selection input', html).each(function() {
                    if (self.selected.indexOf($(this).val()) >= 0) {
                        $(this).prop("checked", true);
                    }
                });
                $('.tcm-results').replaceWith(html);
                if (data.more) {
                    $('.tcm-load-more', context).removeClass('tcm-load-more-loading');
                    $('.tcm-load-more button', context).prop('disabled', false);
                    $('.tcm-load-more', context).show();
                } else {
                    $('.tcm-load-more', context).hide();
                }
                self.initResizeHandler();
            });
            if (self.checkLock(lock)) {
                self.filters.setCounts(data.filters);
            }
            templates.render('totara_contentmarketplace/result_summary', data).done(function(html) {
                if (!self.checkLock(lock)) {
                    // Not the current request
                    return;
                }
                context.removeClass('tcm-is-searching');
                $('.tcm-search_result_summary', context).replaceWith(html);
            });
            $('.tcm-sorting select', context).val(data.sort);
            if (self.mode !== MODE_CREATE_COURSE) {
                if (data.selectionmode === 'add') {
                    $('.tcm-add-to-collection', context).show();
                    $('.tcm-remove-from-collection', context).hide();
                } else if (data.selectionmode === 'remove') {
                    $('.tcm-add-to-collection', context).hide();
                    $('.tcm-remove-from-collection', context).show();
                }
            }
        });
    };

    explorer.more = function() {
        var context = $(this.selector),
            self = this,
            data,
            lock = this.lock();
        $('.tcm-load-more', context).addClass('tcm-load-more-loading');
        $('.tcm-load-more button', context).prop('disabled', true);
        data = this.data;
        data.page += 1;
        $.post({
            url: mdlcfg.wwwroot + "/totara/contentmarketplace/ajax/search.php",
            data: data
        }).done(function(data) {
            templates.render('totara_contentmarketplace/results', data).done(function(html) {
                if (!self.checkLock(lock)) {
                    // Not the current request
                    return;
                }
                html = $(html);
                $('.tcm-thumbnail_selection input', html).each(function() {
                    if (self.selected.indexOf($(this).val()) >= 0) {
                        $(this).prop("checked", true);
                    }
                });
                $('> div', html).appendTo('.tcm-results');
                if (data.more) {
                    $('.tcm-load-more', context).removeClass('tcm-load-more-loading');
                    $('.tcm-load-more button', context).prop('disabled', false);
                    $('.tcm-load-more', context).show();
                } else {
                    $('.tcm-load-more', context).hide();
                }
                self.initResizeHandler();
            });
        });
    };

    explorer.addToSelection = function(id) {
        var index = this.selected.indexOf(id);
        if (index < 0) {
            this.selected.push(id);
        }
        this.selection();
    };

    explorer.removeFromSelection = function(id) {
        var index = this.selected.indexOf(id);
        if (index > -1) {
            this.selected.splice(index, 1);
        }
        this.selection();
    };

    explorer.selection = function() {
        var context = $(this.selector);
        if (this.selected.length > 0) {
            var self = this;
            var length = this.selected.length;
            var strstatus = length === 1 ? 'itemselected' : 'itemselected_plural';
            var status = $('.tcm-search_selection_status', context);
            Str.get_string(strstatus, 'totara_contentmarketplace', length).done(function(selected) {
                // so we don't display the wrong string
                if (self.selected.length === length) {
                    status.text(selected);
                    $('.tcm-search_selection_tools', context).css('visibility', 'visible');
                }
            });
        } else {
            $('.tcm-search_selection_tools', context).css('visibility', 'hidden');
        }
    };

    explorer.selectAll = function() {
        var self = this;
        if (self.totalResults > self.maxSelectAll) {
            self.warningTooManySelectAll();
        } else {
            $.post({
                url: mdlcfg.wwwroot + "/totara/contentmarketplace/ajax/select.php",
                data: this.data
            }).done(function(data) {
                $('.tcm-select-all').siblings('.tcm-loading').css({"visibility": "hidden"});
                self.selected = self.selected.concat(data.selection.filter(function(id) {
                    return self.selected.indexOf(id) < 0;
                }));

                $('.tcm-thumbnail_selection input').each(function() {
                    if (self.selected.indexOf($(this).val()) >= 0) {
                        $(this).prop("checked", true);
                    }
                });
                self.selection();
            });
            $('.tcm-select-all').siblings('.tcm-loading').css({"visibility": "visible"});
        }
    };

    explorer.deselectAll = function() {
        this.selected = [];
        var context = $(this.selector);
        $(".tcm-thumbnail_selection input:checked", context).prop('checked', false);
        this.selection();
    };

    explorer.createCourse = function() {
        if (this.selected.length > this.maxCreateCourse) {
            this.disableCreateCourse();
            this.warningCreateTooManyCourses();
        } else if (this.selected.length >= this.warnCreateCourse) {
            this.disableCreateCourse(this.selected);
            this.warningCreateLotsOfCourses(this.openCreateCourseForm.bind(this, this.selected));
        } else {
            this.openCreateCourseForm(this.selected);
        }
    };

    explorer.openCreateCourseForm = function(selection) {
        var params = {
            category: this.category,
            selection: selection,
            mode: this.mode
        };
        this.disableCreateCourse();
        window.location = mdlcfg.wwwroot + this.createpagepath + "?" + $.param(params);
    };

    explorer.addToCollection = function() {
        var self = this,
            context = $(self.selector);
        context.addClass('tcm-is-searching');
        $('.tcm-search_selection_tools', context).css('visibility', 'hidden');
        $('.tcm-search_result_summary', context).html("");
        $.post({
            url: mdlcfg.wwwroot + "/totara/contentmarketplace/ajax/update_collection.php",
            data: {
                sesskey: mdlcfg.sesskey,
                selection: this.selected,
                action: "add",
                marketplace: this.marketplace
            }
        }).done(function(data) {
            self.search();
        });
        self.selected = [];
    };

    explorer.removeFromCollection = function() {
        var self = this,
            context = $(self.selector);
        context.addClass('tcm-is-searching');
        $('.tcm-search_selection_tools', context).css('visibility', 'hidden');
        $('.tcm-search_result_summary', context).html("");
        $.post({
            url: mdlcfg.wwwroot + "/totara/contentmarketplace/ajax/update_collection.php",
            data: {
                sesskey: mdlcfg.sesskey,
                selection: this.selected,
                action: "remove",
                marketplace: this.marketplace
            }
        }).done(function(data) {
            self.search();
        });
        self.selected = [];
    };

    explorer.warningTooManySelectAll = function() {
        // @todo only create once
        var requiredstrings = [
            {key: 'warningtoomanyselectall:title', component: 'totara_contentmarketplace'},
            {key: 'warningtoomanyselectall:body', component: 'totara_contentmarketplace'}
        ];
        Str.get_strings(requiredstrings).done(function(strings) {
            ModalFactory.create({
                    type: ModalFactory.types.CANCEL,
                title: strings[0],
                body: strings[1]
                }
            ).done(function(modal) {
                modal.show();
            });
        });
    };

    explorer.warningCreateTooManyCourses = function() {
        var self = this;
        // @todo only create once
        var requiredStrings = [
            {key: 'warningcreatetoomanycourses:title', component: 'totara_contentmarketplace'},
            {key: 'warningcreatetoomanycourses:body', component: 'totara_contentmarketplace'}
        ];
        Str.get_strings(requiredStrings).done(function(strings) {
            ModalFactory.create({
                type: ModalFactory.types.CANCEL,
                title: strings[0],
                body: strings[1]
                }
            ).done(function(modal) {
                var root = modal.getRoot();
                root.on(ModalEvents.hidden, self.enableCreateCourse);
                modal.show();
            });
        });
    };

    explorer.warningCreateLotsOfCourses = function(yescallback) {
        var self = this;
        // @todo only create once
        var requiredStrings = [
            {key: 'warningcreatelotsofcourses:title', component: 'totara_contentmarketplace'},
            {key: 'warningcreatelotsofcourses:body', component: 'totara_contentmarketplace', param: this.selected.length},
            {key: 'createcourses', component: 'totara_contentmarketplace', param: this.selected.length},
            {key: 'cancel'}
        ];
        Str.get_strings(requiredStrings).done(function(strings) {
            ModalFactory.create(
                {
                    type: ModalFactory.types.CONFIRM,
                    title: strings[0],
                    body: strings[1]
                },
                undefined,
                {
                    yesstr: strings[2],
                    nostr: strings[3]
            }).done(function(modal) {
                var root = modal.getRoot();
                root.on(ModalEvents.yes, yescallback);
                root.on(ModalEvents.hidden, self.enableCreateCourse);
                modal.show();
            });
        });
    };

    explorer.enableCreateCourse = function() {
        $('.tcm-create-course').addClass('tcm-tool-enabled');
    };

    explorer.disableCreateCourse = function() {
        $('.tcm-create-course').removeClass('tcm-tool-enabled');
    };

    explorer.showDetails = function(id) {
        var self = this;
        self.hideDetails();
        $('.tcm-result').removeClass('tcm-details-target');
        $('#selection-' + id).closest('.tcm-result').addClass('tcm-details-target');

        var data = this.data;
        data.id = id;

        // We have to keep a copy of the details window container,
        // because it may disappear from DOM later on.
        if (!$('#tcm-details-wrapper-actual').length) {
            $('.tcm-details-wrapper').clone().insertAfter('.tcm-results').attr('id', 'tcm-details-wrapper-actual');
        }

        var $details = $('#tcm-details-wrapper-actual');

        $('.tcm-details-content').remove();
        $details.show();
        $('.tcm-preloader').show();
        self.positionDetails();
        if (!self.isElementInViewport($details[0])) {
            var rect = $details[0].getBoundingClientRect();
            window.scrollBy({top: (rect.bottom - window.innerHeight), 'behavior': 'smooth'});
        }

        // Now do the actual work.
        $.post({
            url: mdlcfg.wwwroot + "/totara/contentmarketplace/ajax/fetch_details.php",
            data: data
        }).done(function(data) {
            templates.render('totara_contentmarketplace/details', data).done(function(html) {
                // Only display details if it still matches the selected item.
                if (id === $('.tcm-details-target').find('input').val()) {
                    $('.tcm-preloader').hide();
                    $('.tcm-details-content').remove();
                    $(html).insertAfter('.tcm-preloader');
                    $('.tcm-details-close').click(self.hideDetails);
                    $('.tcm-create-course-button').click(function() {
                        self.openCreateCourseForm([id]);
                    });
                }
            });
        });
    };

    explorer.positionDetails = function() {
        var $details = $('#tcm-details-wrapper-actual');
        var $target = $('.tcm-details-target', this.selector);
        if (!$details.length || !$target.length) {
            return;
        }

        // Place the pointer.
        var left = $target.offset().left + Math.round($target.width() / 2);
        $('.tcm-details-pointer').offset({left: left});

        // Place the details window.
        var $last_in_row = $target;
        if (!$target.hasClass('tcm-last')) {
            $last_in_row = $target.nextAll('.tcm-last:first');
        }
        $details.detach().insertAfter($last_in_row);
    };

    explorer.hideDetails = function() {
        $('#tcm-details-wrapper-actual', this.selector).remove();
        $('.tcm-result').removeClass('tcm-details-target');
    };

    explorer.initResizeHandler = function() {
        var self = this;
        // We need this one to discover the first and last thumbnail
        // in each row of the floating DIVs, so as to be able to
        // insert the course details window after the last one in a row.
        $(window).on('resize', function() {
            if (!$('.tcm-result').length) {
                return;
            }
            var $details = $('#tcm-details-wrapper-actual');
            if ($details.length) {
                $('#tcm-details-wrapper-actual').hide();
            }
            var startPosX = $('.tcm-result:first', self.selector).position().left;
            $('.tcm-result', self.selector).removeClass("tcm-last");
            $('.tcm-result').each(function() {
                if ($(this).position().left === startPosX) {
                    var $prev = $(this).prev();
                    if ($prev.hasClass('tcm-details-wrapper')) {
                        $prev = $prev.prev();
                    }
                    $prev.addClass("tcm-last");
                }
            });
            $('.tcm-result:last').addClass("tcm-last");
            if ($details.length) {
                $('#tcm-details-wrapper-actual').show();
            }
            self.positionDetails();
        });
        $(window).trigger('resize');
    };

    explorer.isElementInViewport = function(el) {
        var rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    };

    /**
     * Creates a lock so that old results don't override new results
     *
     * @return {integer}
     */
    explorer.lock = function() {
        return ++this.lockId;
    };

    /**
     * Checks to see if a lock is the correct one
     *
     * @param {integer} id the lock to check
     * @return {boolean} whether this is the current lock
     */
    explorer.checkLock = function(id) {
        return this.lockId === id;
    };

    return explorer;

});
