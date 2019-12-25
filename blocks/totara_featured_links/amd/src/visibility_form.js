/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

define(['jquery'], function ($) {
    var checkboxes = [
        'audience_showing',
        'preset_showing',
        'tile_rules_showing'
    ];
    var form_id_suffix;

    var check_aggregation = function (id_suffix) {
        var num = 0;
        $.each(checkboxes, function (index, value) {
            if ($('#tfiid_' + value + '_' + id_suffix).is(":checked")) {
                num++;
            }
        });
        if (!$('#tfiid_audience_showing_' + id_suffix).length) {
            num++;
        }
        if (num >= 2) {
            $('[data-element-id="tfiid_aggregation' + id_suffix + '"]').each(function (index, value) {
                $(value).removeAttr('data-hidden');
            });
        } else {
            $('[data-element-id="tfiid_aggregation' + id_suffix + '"]').each(function (index, value) {
                $(value).attr('data-hidden', '');
            });
        }
    };

    var add_audience_table_listeners = function () {
        $('[data-type=cohort_delete]').each(function (key, value) {
            $(value).off('click');
            $(value).click(function (event) {
                $(event.target).closest('li').remove();

                var itemid = $(event.target).closest('a').attr('cohortid');
                var hidden_input = $('input[name="audiences_visible"]');
                var ids = hidden_input.val().split(',');
                if (ids.indexOf(itemid) != -1) {
                    ids.splice(ids.indexOf(itemid), 1);
                }
                hidden_input.val(ids.join());
                hidden_input.change();
            });
        });
    };

    return {
        init: function (id_suffix) {
            form_id_suffix = id_suffix;
            $.each(checkboxes, function (index, value) {
                $('#tfiid_' + value + '_' + id_suffix).on('change', function () {
                    check_aggregation(id_suffix);
                });
            });
            $('#tfiid_visibility_' + id_suffix).on('change', function () {
                if ($('input[name=visibility]:checked', '#tfiid_visibility_' + id_suffix).val() != '2') {
                    $('[data-element-id="tfiid_aggregation' + id_suffix + '"]').each(function (index, value) {
                        $(value).attr('data-hidden', '');
                    });
                } else {
                    check_aggregation(id_suffix);
                    add_audience_table_listeners();
                }
            });
            add_audience_table_listeners();
            if ($('input[name=visibility]:checked', '#tfiid_visibility_' + id_suffix).val() != '2') {
                $('[data-element-id="tfiid_aggregation' + id_suffix + '"]').each(function (index, value) {
                    $(value).attr('data-hidden', '');
                });
            } else {
                check_aggregation(id_suffix);
            }
        },
        add_audience_table_listeners: add_audience_table_listeners,
        add_to_audience_id: function (id) {
            if (id.length === 0) {
                return;
            }
            var hidden_input_audiences = $('input[name="audiences_visible"]');
            hidden_input_audiences.val(hidden_input_audiences.val() + (hidden_input_audiences.val().length === 0 ? '' : ',') + id);
            hidden_input_audiences.change();
        },
        add_to_audience_list: function (html) {
            $('#audience_visible_table').append(html);
        },
        audience_list_contains: function (id) {
            return $('#audience_visible_table').find('span[cohortid="' + id + '"]').length;
        }
    };
});