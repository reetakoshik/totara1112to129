/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Mark Webster <mark.webster@catalyst-eu.net>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package totara
 * @subpackage totara_customfield
 */

define(['jquery', 'core/str'], function ($, strlib) {

    return {
        /**
         * module initialisation method called by php js_init_call()
         *
         * @param bool      whether this is a multi select or just a single select
         * @param string    the id of the object to work with
         * @param int       The maximum number of entries to show on load
         */
        init : function(multiple, id, max) {
            var numVisible = 0;
            var $container = $('#fgroup_id' + id);

            var $allOptions = $container.find('div[id^="fgroup_id_multiselectitem_"]').slice(0, max);

            $allOptions.each(function(i){
                $(this).find("input[type='text']").each(function(j, e) {
                    if ($(e).val() !== '') {
                        numVisible = i + 1;
                        return false;
                    }
                });
            });

            if (numVisible < 3) {
                numVisible = 3;
            }

            // Hide from numVisible (default is 3) to last.
            $allOptions.slice(numVisible, max).hide();

            $allOptions.each(function(){
                var $this = $(this);
                if ($($this.find('span')).length > 0) {
                    // Make default part.

                    var requiredstrings = [];
                    requiredstrings.push({key: 'defaultmake', component: 'totara_customfield'});
                    requiredstrings.push({key: 'defaultselected', component: 'totara_customfield'});
                    requiredstrings.push({key: 'delete', component: 'moodle'});

                    strlib.get_strings(requiredstrings).done(function (strings) {
                        var $makeDefault =  $('<a href="#" class="customfield-multiselect-action customfield-multiselect-selectlink">' + strings[0] + '</a>');
                        var $unselect = $('<a href="#" class="customfield-multiselect-action customfield-multiselect-unselectlink">' + strings[1] + '</a>').hide();
                        var $delete = $('<a href="#" class="customfield-multiselect-action customfield-multiselect-deletelink">' + strings[2] + '</a>');

                        $makeDefault.on('click', function(){
                            if (multiple == 1) {
                                $allOptions.find('.customfield-multiselect-unselectlink').each(function(){
                                    $(this).click();
                                });
                            }

                            $this.find('input.makedefault').prop('checked', true);

                            $makeDefault.hide();
                            $unselect.show();
                            return false;
                        });

                        $unselect.on('click', function(){
                            $this.find('input.makedefault').prop('checked', false);
                            $makeDefault.show();
                            $unselect.hide();
                            return false;
                        });

                        // Delete part.
                        $delete.on('click', function(){
                            $this.find('input.delete').prop('checked', true);
                            $this.addClass('customfield-multiselect-deleted');
                            $this.hide();

                            return false;
                        });

                        $this.append($makeDefault);
                        $this.append($unselect);
                        $this.append($delete);

                        if ($this.find('input.makedefault').prop('checked')) {
                            $makeDefault.click();
                        }
                    });
                }

            });

            // Make visible #addoptionlink_$jsid.
            $container.find('a.addoptionlink').show();
            $container.find('a.addoptionlink').on('click', function() {
                var $group = $container.find('.felement > div:hidden').not('.customfield-multiselect-deleted').eq(0);

                if ($group.length) {
                    $group.show();
                    numVisible++;
                }

                if (numVisible == max) {
                    $(this).hide();
                }

                return false;
            });
        }
    };

});
