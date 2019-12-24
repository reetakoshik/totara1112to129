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
 * @module     totara_contentmarketplace/filter_radios
 * @class      FilterRadios
 * @package    totara_contentmarketplace
 */

define(['jquery', 'core/templates'], function($, templates) {

    function FilterRadios(name, showCounts, selector, onFitler) {
        this.name = '';
        this.context = null;
        this.value = null;
        this.onFitler = null;
        this.showCounts = false;
        this.init(name, showCounts, selector, onFitler);
    }

    FilterRadios.prototype.init = function(name, showCounts, selector, onFitler) {
        this.name = name;
        this.showCounts = showCounts;
        this.context = $(selector);
        this.onFitler = onFitler;
        this.context.on('change', '.tcm-filter-radios input', this, function(event) {
            event.preventDefault();
            event.data.onFitler();
        });
    };

    FilterRadios.prototype.populate = function(seed) {
        var self = this,
            deferred = $.Deferred();
        if (seed.options.length === 0) {
            this.context.hide();
            deferred.resolve();
        } else {
            templates.render('totara_contentmarketplace/filter_radios', seed).done(function (html) {
                $('.tcm-filter-input', self.context).append(html);
                deferred.resolve();
            });
        }
        return deferred.promise();
    };

    FilterRadios.prototype.getValue = function() {
        return $("input:checked", this.context).val();
    };

    FilterRadios.prototype.setCounts = function(counts) {
        if (!this.showCounts) {
            return;
        }
        $('.tcm-filter-option-count', this.context).each(function (key, value) {
            var id = $(value).data('value');
            $(value).text(counts[id] || '0');
        });
    };

    return FilterRadios;

});
