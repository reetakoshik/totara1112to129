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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

"use strict";

M.totara_f2f_dateintervalkeeper = M.totara_f2f_dateintervalkeeper || {
    previousstartvalues: 0,

    getdate: function (dateelement) {
        return new Date(
            $('.fdate_time_selector select[name="' + dateelement + '[year]"]').val(),
            $('.fdate_time_selector select[name="' + dateelement + '[month]"]').val() - 1,
            $('.fdate_time_selector select[name="' + dateelement + '[day]"]').val(),
            $('.fdate_time_selector select[name="' + dateelement + '[hour]"]').val(),
            $('.fdate_time_selector select[name="' + dateelement + '[minute]"]').val()
        ).getTime() / 1000;
    },

    setdate: function (dateelement, timestamp) {
        var date = new Date(timestamp * 1000);
        $('.fdate_time_selector select[name="' + dateelement + '[year]"]').val(date.getFullYear());
        $('.fdate_time_selector select[name="' + dateelement + '[month]"]').val(date.getMonth() + 1);
        $('.fdate_time_selector select[name="' + dateelement + '[day]"]').val(date.getDate());
        $('.fdate_time_selector select[name="' + dateelement + '[hour]"]').val(date.getHours());
        $('.fdate_time_selector select[name="' + dateelement + '[minute]"]').val(date.getMinutes());
    },

    init: function(Y, elemstart, elemfinish) {
        if (!elemstart) {
            elemstart = 'timestart';
        }
        if (!elemfinish) {
            elemfinish = 'timefinish';
        }
        M.totara_f2f_dateintervalkeeper.previousstartvalues = M.totara_f2f_dateintervalkeeper.getdate(elemstart);

        $('.fdate_time_selector select[name^="' + elemstart + '"]').change(function() {
            console.log("change", this);
            var newstartdate = M.totara_f2f_dateintervalkeeper.getdate(elemstart);
            var oldstartdate = M.totara_f2f_dateintervalkeeper.previousstartvalues;
            var currentfinishdate = M.totara_f2f_dateintervalkeeper.getdate(elemfinish);
            var newfinishdate = currentfinishdate + (newstartdate - oldstartdate);
            M.totara_f2f_dateintervalkeeper.setdate(elemfinish, newfinishdate);
            M.totara_f2f_dateintervalkeeper.previousstartvalues = M.totara_f2f_dateintervalkeeper.getdate(elemstart);
        });
    }
};