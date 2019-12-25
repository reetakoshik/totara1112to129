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
 * @module     totara_contentmarketplace/filters
 * @class      filters
 * @package    totara_contentmarketplace
 */

define(['jquery', 'core/config'], function($, mdlcfg) {


    function Filters(selector, onFilter, marketplace, category) {
        this.context = null;
        this.filters = {};
        this.marketplace = null;
        this.category = null;
        this.populated = $.Deferred();
        this.mode = null;
        this.init(selector, onFilter, marketplace, category);
    }

    Filters.prototype.init = function(selector, onFilter, marketplace, mode, category) {
        var self = this,
            promises = [];
        this.context = $(selector + ' .tcm-search-filters-wrapper');
        this.marketplace = marketplace;
        this.mode = mode;
        this.category = category;

        $('.tcm-search-filter', this.context).each(function() {
            var name = $(this).data('filter-name');
            var module = $(this).data('filter-module');
            var showCounts = $(this).data('filter-showcounts');
            promises.push(self.initFilter(name, module, showCounts, selector + ' .tcm-search-filter-name-' + name, onFilter));
        });

        $.when.apply($, promises).done(function() {
            self.fetchFilterSeeds().done(function(seeds) {
                self.populateFilters(seeds);
            });
        });
    };

    Filters.prototype.setCounts = function(counts) {
        var self = this;
        this.populated.done(function() {
            $.each(counts, function(index, count) {
                self.filters[count.name].setCounts(count.values);
            });
        });
    };

    Filters.prototype.getValues = function() {
        var values = {};
        for (var name in this.filters) {
            values[name] = this.filters[name].getValue();
        }
        return values;
    };

    Filters.prototype.initFilter = function(name, module, showCounts, selector, onFilter) {
        var self = this;
        var deferred = $.Deferred();
        require([module], function(Filter) {
            var filter = new Filter(name, showCounts, selector, onFilter);
            self.filters[filter.name] = filter;
            deferred.resolve();
        });
        return deferred.promise();
    };

    Filters.prototype.populateFilters = function(seeds) {
        var self = this,
            promises = [];
        $.each(seeds, function(index, seed) {
            promises.push(self.filters[seed.name].populate(seed));
        });
        $.when.apply($, promises).done(function() {
            self.populated.resolve();
            $('.tcm-search-filters-loading', self.context).hide();
            $('.tcm-search-filters', self.context).show();
        });
    };

    Filters.prototype.fetchFilterSeeds = function() {
        var data = {
                sesskey: mdlcfg.sesskey,
                marketplace: this.marketplace,
                category: this.category,
                mode: this.mode
            };
        return $.post({
            url: mdlcfg.wwwroot + "/totara/contentmarketplace/ajax/filters.php",
            data: data
        });
    };

    return Filters;

});
