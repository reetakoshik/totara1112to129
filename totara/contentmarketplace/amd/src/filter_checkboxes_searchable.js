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
 * @module     totara_contentmarketplace/filter_checkboxes_searchable
 * @class      FilterCheckboxesSearchable
 * @package    totara_contentmarketplace
 */

define(['jquery', 'core/templates'], function($, templates) {

    function FilterCheckboxesSearchable(name, showCounts, selector, onFilter) {
        this.name = '';
        this.context = null;
        this.data = {};
        this.selection = [];
        this.onFitler = null;
        this.showCounts = false;
        this.init(name, showCounts, selector, onFilter);
    }

    FilterCheckboxesSearchable.prototype.init = function(name, showCounts, selector, onFilter) {
        this.name = name;
        this.showCounts = showCounts;
        this.context = $(selector);
        this.onFilter = onFilter;
        this.context.on('keyup', '.tcm-search-filter-term', this, function(event) {
            if (event.key !== 'Escape') {
                setTimeout(function () {
                    event.data.updateSearch();
                }, 1);
            }
        });
        this.context.on('focus', '.tcm-search-filter-term', this, function(event) {
            event.data.updateSearch();
            event.data.showMenu();
        });
        this.context.on('change', '.tcm-checkbox-filter input', this, function(event) {
            event.preventDefault();
            event.data.onChange(event.currentTarget);
        });
    };

    FilterCheckboxesSearchable.prototype.populate = function(seed) {
        this.data = seed.options;
        this.selection = [];
        if ($.isEmptyObject(seed.options)) {
            this.context.hide();
        }
    };

    FilterCheckboxesSearchable.prototype.showMenu = function() {
        $('body').on('keydown.tcm-filter-' + this.name, this, this.hideIfEscape);
        $('body').on('click.tcm-filter-' + this.name, this, this.hideIfOutside);
        $('body').on('focus.tcm-filter-' + this.name, '*', this, this.hideIfOutside);
        this.context.addClass('focused');
    };

    FilterCheckboxesSearchable.prototype.hideMenu = function() {
        $('body').off('keydown.tcm-filter-' + this.name);
        $('body').off('click.tcm-filter-' + this.name);
        $('body').off('focus.tcm-filter-' + this.name);

        this.context.removeClass('focused');
        $('.tcm-search-filter-term', this.context).val('');
        $('.tcm-search-filter-results', this.context).hide();
    };

    FilterCheckboxesSearchable.prototype.hideIfEscape = function(event) {
        var self = event.data;
        if (event.key === 'Escape') {
            $('.tcm-search-filter-term', self.context).trigger("blur");
            self.hideMenu();
        }
    };

    FilterCheckboxesSearchable.prototype.hideIfOutside = function(event) {
        var self = event.data;
        if (!$.contains($('.tcm-search-filter-widget', self.context)[0], event.target)) {
            self.hideMenu();
        }
    };

    FilterCheckboxesSearchable.prototype.updateSearch = function() {
        var self = this,
            results = [],
            data,
            term;

        term = $('.tcm-search-filter-term', this.context).val();
        term = term.trim().toLowerCase();

        $.each(this.search(term), function(index, hit) {
            if (self.selection.indexOf(hit) === -1) {
                results.push(self.data[hit]);
            }
        });
        if (results.length === 0) {
            $('.tcm-search-filter-results', self.context).hide();
        } else {
            data = {
                results: results
            };
            templates.render('totara_contentmarketplace/filter_checkboxes_searchable_results', data).done(function (html) {
                $('.tcm-search-filter-results', self.context).html(html);
                $('.tcm-search-filter-results', self.context).show();
            });
        }
    };

    FilterCheckboxesSearchable.prototype.updateSelection = function() {
        var self = this,
            results = [],
            deferred = $.Deferred(),
            data;
        this.selection.forEach(function(value) {
            var element = Object.assign({}, self.data[value]);
            element.htmlid += '-selected';
            results.push(element);
        });
        data = {
            results: results
        };
        templates.render('totara_contentmarketplace/filter_checkboxes_searchable_results', data).done(function (html) {
            deferred.resolve(html);
        });
        deferred.done(function (html) {
            $('.tcm-search-filter-selection-wrapper', self.context).html(html);
        });
        return deferred.promise();
    };

    FilterCheckboxesSearchable.prototype.search = function(term) {
        var self = this;
        var expression = new RegExp(self.escapeRegex(term), "i");
        return $.grep(Object.keys(self.data), function(key) {
            return expression.test(self.data[key].label);
        });
    };

    FilterCheckboxesSearchable.prototype.escapeRegex = function(value) {
        return value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
    };

    FilterCheckboxesSearchable.prototype.onChange = function (element) {
        var self = this,
            value = $(element).val(),
            checked = $(element).is(':checked'),
            index = this.selection.indexOf(value);
        if (checked) {
            if (index === -1) {
                this.selection.push(value);
            }
            this.data[value].checked = true;
        } else {
            if (index !== -1) {
                this.selection.splice(index, 1);
            }
            this.data[value].checked = false;
        }
        var promise = this.updateSelection();
        this.onFilter();
        if (checked) {
            promise.done(function() {
                var haveUnselectedOptions;
                // Batch DOM updates for display of user selection and updated search filter results.
                $(element).parent().hide();
                haveUnselectedOptions = $('.tcm-search-filter-results .tcm-checkbox-filter:visible', self.context).length !== 0;
                $('.tcm-search-filter-results', self.context).toggle(haveUnselectedOptions);
            });
        }
    };

    FilterCheckboxesSearchable.prototype.getValue = function() {
        return this.selection;
    };

    FilterCheckboxesSearchable.prototype.setCounts = function(counts) {
        if (!this.showCounts) {
            return;
        }
        var self = this;
        for (var value in this.data) {
            this.data[value].count = '0';
        }
        for (var value in counts) {
            if (value in this.data) {
                this.data[value].count = counts[value];
            }
        }
        $('.tcm-filter-option-count', this.context).each(function(key, value) {
            var id = $(value).data('value');
            $(value).text(self.data[id].count);
        });
    };

    return FilterCheckboxesSearchable;

});
