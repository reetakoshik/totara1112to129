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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Dave Wallace <dave.wallace@kineo.co.nz>
 * @package totara_reportbuilder
 */

/* global $ */
M.totara_reportbuildercolumns = M.totara_reportbuildercolumns || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},
    loadingimg: '',
    advoptionshtml : '',
    hideicon: '',
    showicon: '',
    deleteicon: '',
    upicon: '',
    downicon: '',
    spacer: '',
    warningicon: '',

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param object    configuration data
     */
    init: function(Y, config){
        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // store config info
        this.config = config;
        this.config.user_sesskey = M.cfg.sesskey;

        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_reportbuildercolumns.init()-> jQuery dependency required for this module to function.');
        }

        // store all options for adv selector for later
        this.advoptionshtml = $('select.new_advanced_selector').html();

        var that = this;
        require(['core/templates'], function (templates) {
            var iconscache = [];
            iconscache.push(templates.renderIcon('loading', M.util.get_string('saving', 'totara_reportbuilder')));
            iconscache.push(templates.renderIcon('hide', M.util.get_string('hide', 'totara_reportbuilder')));
            iconscache.push(templates.renderIcon('show', M.util.get_string('show', 'totara_reportbuilder')));
            iconscache.push(templates.renderIcon('delete', M.util.get_string('delete', 'totara_reportbuilder')));
            iconscache.push(templates.renderIcon('arrow-up', M.util.get_string('moveup', 'totara_reportbuilder')));
            iconscache.push(templates.renderIcon('arrow-down', M.util.get_string('movedown', 'totara_reportbuilder')));
            iconscache.push(templates.renderIcon('spacer'));
            iconscache.push(templates.renderIcon('warning', M.util.get_string('deprecatedcolumn', 'totara_reportbuilder'), 'warn-deprecated'));

            $.when.apply($, iconscache).then(function (loadingicon, hideicon, showicon, deleteicon, upicon, downicon, spacer, warningicon) {
                that.loadingimg = loadingicon;
                that.hideicon = hideicon;
                that.showicon = showicon;
                that.deleteicon = deleteicon;
                that.upicon = upicon;
                that.downicon = downicon;
                that.spacer = spacer;
                that.warningicon = warningicon;
                // Do setup.
                that.rb_init_col_rows();
            });
        });
    },

    /**
     * Tweak row elements.
     * @param column_selector
     */
    rb_update_col_row: function(column_selector) {
        var module = this;

        var colName = $(column_selector).val();
        var newHeading = module.config.rb_column_headings[colName];

        var advancedSelector = $('select.advanced_selector', $(column_selector).parents('tr:first'));
        var headingElement = $('input.column_heading_text', $(column_selector).parents('tr:first'));
        var customHeadingCheckbox = $('input.column_custom_heading_checkbox', $(column_selector).parents('tr:first'));

        if (colName == '0') {
            advancedSelector.hide();
            customHeadingCheckbox.hide();
            headingElement.hide();

        } else if ($.inArray(colName, module.config.rb_grouped_columns) == -1) {
            advancedSelector.show();
            customHeadingCheckbox.show();
            headingElement.show();

            var advancedSelectorVal = advancedSelector.val();
            if (advancedSelectorVal != '') {
                if ($.inArray(advancedSelectorVal, module.config.rb_allowed_advanced[colName]) == -1) {
                    advancedSelector.val('');
                    advancedSelectorVal = '';
                } else {
                    var strName = advancedSelectorVal.replace('transform_', 'transformtype').replace('aggregate_', 'aggregatetype') + '_heading';
                    newHeading = M.util.get_string(strName, 'totara_reportbuilder', newHeading);
                }
            }
            // Alter advanced selector to show only possible options.
            advancedSelector.html(module.advoptionshtml);
            advancedSelector.val(advancedSelectorVal);
            advancedSelector.find('option').each(function () {
                var option = $(this);
                if ($.inArray(option.val(), module.config.rb_allowed_advanced[colName]) == -1) {
                    option.remove();
                }
            });
            advancedSelector.children().each(function () {
                var group = $(this);
                if (group.children().length == 0) {
                    group.remove();
                }
            });
            // One group means we have only 'None' left after removing incompatible options.
            // So, hide the entire advanced options selector.
            if (advancedSelector.children().length === 1) {
                advancedSelector.hide();
                advancedSelector.val('');
            }
        } else {
            advancedSelector.hide();
            customHeadingCheckbox.show();
            headingElement.show();

            advancedSelector.val('');
        }

        if (customHeadingCheckbox.is(':checked')) {
            headingElement.prop('disabled', false);
        } else {
            headingElement.prop('disabled', true);
            headingElement.val(newHeading);
        }

        // Add warning icon for a deprecated column if needed,
        // Or remove existing one if switching to a good column.
        var deprecated = $(column_selector).find(':selected').data('deprecated');
        if (deprecated == true) {
            if ($(column_selector).next('.warn-deprecated').length == 0) {
                $(column_selector).after(module.warningicon);
            }
        } else {
            if ($(column_selector).next('.warn-deprecated').length > 0) {
                $(column_selector).next('.warn-deprecated').remove();
            }
        }
    },

    /**
     * Add a warning if any selected columns are not compatible with selected aggregations.
     *
     * Unfortunately, we have to scan through all selected columns and options to always show
     * correct information in our warnings.
     */
    rb_check_col_compatibility: function() {
        var module = this;

        require(['core/notification'], function(notification) {
            var warnings = document.getElementsByClassName('rb-column-warning');
            while (warnings.length > 0) {
                warnings[0].remove();
            }

            // Looks through all selected columns.
            $('select.column_selector').each(function() {
                var colName = $(this).val();

                // If column is a sub-query, loop through the advanced settings of other selected columns
                // to check if aggregations are used anywhere.
                if (colName != 0 && this.options[this.selectedIndex].getAttribute('data-issubquery') === "1") {
                    $('select.advanced_selector').each(function() {
                        var advancedSelectorVal = $(this).val();
                        if (advancedSelectorVal.indexOf('aggregate_') !== -1) {
                            var advancedSelector = $('select.column_selector', $(this).parents('tr:first'));
                            var aggregatedHeading = module.config.rb_column_headings[$(advancedSelector).val()];
                            var subqueryHeading = module.config.rb_column_headings[colName];

                            var warning = M.util.get_string('warnincompatiblecolumns', 'totara_reportbuilder', {
                                subquery: subqueryHeading,
                                aggregated: aggregatedHeading
                            });

                            notification.addNotification({
                                message: warning,
                                type: 'warning',
                                extraclasses: 'rb-column-warning'
                            });
                        }
                    });
                }
            });
        });
    },

    rb_init_col_rows: function(){

        var module = this;

        // disable the new column heading field on page load
        $('#id_newheading').prop('disabled', true);
        $('#id_newcustomheading').prop('disabled', true);

        // handle changes to the column pulldowns
        $('select.column_selector').off('change');
        $('select.column_selector').on('change', function() {
            window.onbeforeunload = null;
            module.rb_update_col_row(this);
            module.rb_check_col_compatibility();
        });

        // handle changes to the advanced pulldowns
        $('select.advanced_selector').off('change');
        $('select.advanced_selector').on('change', function() {
            window.onbeforeunload = null;
            var column_selector = $('select.column_selector', $(this).parents('tr:first'));
            module.rb_update_col_row(column_selector);
            module.rb_check_col_compatibility();
        });

        // handle changes to the customise checkbox
        // use click instead of change event for IE
        $('input.column_custom_heading_checkbox').off('click');
        $('input.column_custom_heading_checkbox').on('click', function() {
            window.onbeforeunload = null;
            var column_selector = $('select.column_selector', $(this).parents('tr:first'));
            module.rb_update_col_row(column_selector);
        });

        // special case for the 'Add another column...' selector
        $('select.new_column_selector').on('change', function() {
            window.onbeforeunload = null;
            var newHeadingBox = $('input.column_heading_text', $(this).parents('tr:first'));
            var newCheckBox = $('input.column_custom_heading_checkbox', $(this).parents('tr:first'));
            var addbutton = module.rb_init_addbutton($(this));
            if ($(this).val() == 0) {
                // empty and disable the new heading box if no column chosen
                newHeadingBox.val('');
                newHeadingBox.prop('disabled', true);
                addbutton.remove();
                newCheckBox.removeAttr('checked');
                newCheckBox.prop('disabled', true);
            } else {
                // reenable it (binding above will fill the value)
                newCheckBox.prop('disabled', false);
            }
        });

        // init display of advanced column for existing fields
        $('select.column_selector').each(function() {
            module.rb_update_col_row(this);
        });

        // Check for column compatibility issues.
        module.rb_check_col_compatibility();

        // Set up delete button events
        module.rb_init_deletebuttons();

        // Set up hide button events
        module.rb_init_hidebuttons();

        // Set up show button events
        module.rb_init_showbuttons();

        // Set up 'move' button events
        module.rb_init_movedown_btns();
        module.rb_init_moveup_btns();
    },

    /**
     *
     */
    rb_init_addbutton: function(colselector){

        var module = this;
        var newAdvancedBox = $('select.advanced_selector', colselector.parents('tr:first'));
        var newHeadingCheckbox = $('input.column_custom_heading_checkbox', colselector.parents('tr:first'));
        var newHeadingBox = $('input.column_heading_text', colselector.parents('tr:first'));

        var optionsbox = $('td:last', newHeadingBox.parents('tr:first'));
        var newcolinput = colselector.closest('tr').clone();  // clone of current 'Add new col...' tr
        var addbutton = optionsbox.find('.addcolbtn');
        if (addbutton.length == 0) {
            addbutton = this.rb_get_btn_add(module.config.rb_reportid);
        } else {
            // Button already initialised
            return addbutton;
        }

        // Add save button to options
        optionsbox.prepend(addbutton);
        addbutton.off('click');
        addbutton.on('click', function(e) {
            var data = {
                action: 'add',
                sesskey: module.config.user_sesskey,
                id: module.config.rb_reportid,
                col: colselector.val(),
                heading: newHeadingBox.val(),
                advanced: newAdvancedBox.val(),
                customheading : (newHeadingCheckbox.is(':checked') ? 1 : 0)
            }
            var oldAddButtonHtml = addbutton.html();

            e.preventDefault();
            $.ajax({
                url: M.cfg.wwwroot + '/totara/reportbuilder/ajax/column.php',
                type: "POST",
                data: data,
                beforeSend: function() {
                    addbutton.html(module.loadingimg);
                },
                success: function(o) {
                    if (o.length == 0) {
                        alert(M.util.get_string('error', 'moodle'));
                        // Reload the broken page.
                        location.reload();
                    } else if (o.error) {
                        alert(o.error);
                        if (o.noreload) {
                            addbutton.html(oldAddButtonHtml);
                            return;
                        } else {
                            location.reload();
                        }
                    } else {
                        // Add action buttons to row
                        var colid = parseInt(o.result);
                        var hidebutton = module.rb_get_btn_hide(module.config.rb_reportid, colid);
                        var deletebutton = module.rb_get_btn_delete(module.config.rb_reportid, colid);

                        var upbutton = '';
                        var uppersibling = colselector.closest('tr').prev('tr');
                        if (uppersibling.find('select.column_selector').length > 0) {
                            // Create an up button for the newly added col, to be added below
                            var upbutton = module.rb_get_btn_up(module.config.rb_reportid, colid);
                        }

                        addbutton.remove();
                        optionsbox.prepend(hidebutton, deletebutton, upbutton);

                        // Set row atts
                        var columnbox = $('td:first', optionsbox.parents('tr:first'));
                        var columnSelector = $('select.column_selector', columnbox);
                        var newCustomHeading = $('input.column_custom_heading_checkbox', optionsbox.parents('tr:first'));
                        columnSelector.removeClass('new_column_selector');
                        columnSelector.attr('name', 'column'+colid);
                        columnSelector.attr('id', 'id_column'+colid);
                        columnbox.find('select optgroup[label=New]').remove();

                        newAdvancedBox.removeClass('new_advanced_selector');
                        newAdvancedBox.attr('name', 'advanced'+colid);
                        newAdvancedBox.attr('id', 'id_advanced'+colid);

                        newCustomHeading.attr('name', 'customheading'+colid);
                        newCustomHeading.removeAttr('id');

                        newHeadingBox.attr('name', 'heading'+colid);
                        newHeadingBox.attr('id', 'id_heading'+colid);
                        newHeadingBox.closest('tr').attr('data-colid', colid);

                        // Append a new col select box
                        newcolinput.find('input[name=newheading]').val('');
                        columnbox.closest('table').append(newcolinput);

                        module.rb_reload_option_btns(uppersibling);

                        var coltype = colselector.val().split('-')[0];
                        var colval = colselector.val().split('-')[1];

                        // Remove added col from the new col selector
                        $('.new_column_selector optgroup option[value='+coltype+'-'+colval+']').remove();

                        // Add added col to 'default sort column' selector
                        $('select[name=defaultsortcolumn]').append('<option value="'+coltype+'_'+colval+'">'+module.config.rb_column_headings[coltype+'-'+colval]+'</option>');

                        module.rb_init_col_rows();

                    }
                },
                error: function(h, t, e) {
                    alert(M.util.get_string('error', 'moodle'));
                    // Reload the broken page
                    location.reload();
                }
            }); // ajax
        }); // click event

        return addbutton;
    },

    /**
     *
     */
    rb_init_deletebuttons: function() {
        var module = this;
        $('.reportbuilderform table .deletecolbtn').off('click');
        $('.reportbuilderform table .deletecolbtn').on('click', function(e) {
            e.preventDefault();
            var clickedbtn = $(this);

            confirmed = confirm(M.util.get_string('confirmcoldelete', 'totara_reportbuilder'));

            if (!confirmed) {
                return;
            }

            var colrow = $(this).closest('tr');
            var colid = colrow.data('colid');
            var deprecated = $('select#id_column'+colid+'.column_selector').find(':selected').data('deprecated');
            $.ajax({
                url: M.cfg.wwwroot + '/totara/reportbuilder/ajax/column.php',
                type: "POST",
                data: ({
                    action: 'delete',
                    sesskey: module.config.user_sesskey,
                    id: module.config.rb_reportid,
                    cid: colid,
                    deprecated: deprecated
                }),
                beforeSend: function() {
                    clickedbtn.replaceWith(module.loadingimg);
                },
                success: function(o) {
                    if (o.success) {
                        var uppersibling = colrow.prev('tr');
                        var lowersibling = colrow.next('tr');

                        // Remove column row
                        colrow.remove();

                        var delcol = o.result;

                        // Fix sibling buttons
                        if (uppersibling.find('select.column_selector').length > 0) {
                            module.rb_reload_option_btns(uppersibling);
                        }
                        if (lowersibling.find('select.column_selector:not(.new_column_selector)').length > 0) {
                            module.rb_reload_option_btns(lowersibling);
                        }

                        // Add deleted col to new col selector
                        var nlabel = rb_ucwords(delcol.optgroup_label);
                        var optgroup = $(".new_column_selector optgroup[label='" + $.escapeSelector(nlabel) + "']");
                        if (optgroup.length == 0) {
                            // Create optgroup and append to select
                            optgroup = $('<optgroup label="'+nlabel+'"></optgroup>');
                            $('.new_column_selector').append(optgroup);
                        }

                        if (optgroup.find('option[value='+delcol.type+'-'+delcol.value+']').length == 0) {
                            var heading = module.config.rb_column_headings[delcol.type+'-'+delcol.value];
                            if (delcol.deprecated == true) {
                                heading = M.util.get_string('deprecated', 'totara_reportbuilder', heading);
                            }
                            optgroup.append('<option value="'+delcol.type+'-'+delcol.value+'" data-deprecated='+delcol.deprecated+'>'+heading+'</option>');
                        }

                        // Remove deleted col from 'default sort column' selector
                        $('select[name=defaultsortcolumn] option[value='+delcol.type+'_'+delcol.value+']').remove();

                        module.rb_init_col_rows();
                    } else {
                        if (typeof o.noalert === 'undefined') {
                            alert(M.util.get_string('error', 'moodle'));
                        }
                        location.reload();
                    }
                },
                error: function(h, t, e) {
                    alert(M.util.get_string('error', 'moodle'));
                    // Reload the broken page
                    location.reload();
                }
            }); // ajax

        });

        function rb_ucwords (str) {
            return (str + '').replace(/^([a-z])|\s+([a-z])/g, function($1) {
                return $1.toUpperCase();
            });
        }
    },

    /**
     *
     */
    rb_init_hidebuttons: function() {
        var module = this;
        $('.reportbuilderform table .hidecolbtn').off('click');
        $('.reportbuilderform table .hidecolbtn').on('click', function(e) {
            e.preventDefault();
            var clickedbtn = $(this);

            var colrow = $(this).closest('tr');
            $.ajax({
                url: M.cfg.wwwroot + '/totara/reportbuilder/ajax/column.php',
                type: "POST",
                data: ({action: 'hide', sesskey: module.config.user_sesskey, id: module.config.rb_reportid, cid: colrow.data('colid')}),
                beforeSend: function() {
                    clickedbtn.find('img').replaceWith(module.loadingimg);
                },
                success: function(o) {
                    if (o.success) {
                        var colid = colrow.data('colid');

                        var showbtn = module.rb_get_btn_show(module.config.rb_reportid, colid);
                        clickedbtn.replaceWith(showbtn);

                        module.rb_init_col_rows();

                    } else {
                        alert(M.util.get_string('error', 'moodle'));
                        // Reload the broken page
                        location.reload();
                    }

                },
                error: function(h, t, e) {
                    alert(M.util.get_string('error', 'moodle'));
                    // Reload the broken page
                    location.reload();
                }
            }); // ajax

        });
    },

    /**
     *
     */
    rb_init_showbuttons: function() {
        var module = this;
        $('.reportbuilderform table .showcolbtn').off('click');
        $('.reportbuilderform table .showcolbtn').on('click', function(e) {
            e.preventDefault();
            var clickedbtn = $(this);

            var colrow = $(this).closest('tr');
            $.ajax({
                url: M.cfg.wwwroot + '/totara/reportbuilder/ajax/column.php',
                type: "POST",
                data: ({action: 'show', sesskey: module.config.user_sesskey, id: module.config.rb_reportid, cid: colrow.data('colid')}),
                beforeSend: function() {
                    clickedbtn.find('img').replaceWith(module.loadingimg);
                },
                success: function(o) {
                    if (o.success) {
                        var colid = colrow.data('colid');

                        var showbtn = module.rb_get_btn_hide(module.config.rb_reportid, colid);
                        clickedbtn.replaceWith(showbtn);

                        module.rb_init_col_rows();

                    } else {
                        alert(M.util.get_string('error', 'moodle'));
                        // Reload the broken page
                        location.reload();
                    }

                },
                error: function(h, t, e) {
                    alert(M.util.get_string('error', 'moodle'));
                    // Reload the broken page
                    location.reload();
                }
            }); // ajax

        });

    },

    /**
     *
     */
    rb_init_movedown_btns: function() {
        var module = this;
        $('.reportbuilderform table .movecoldownbtn').off('click');
        $('.reportbuilderform table .movecoldownbtn').on('click', function(e) {
            e.preventDefault();
            var clickedbtn = $(this);

            var colrow = $(this).closest('tr');

            var colrowclone = colrow.clone();
            // Set the selected option, cause for some reason this don't clone so well...
            if (colrow.find('select.column_selector').val() !== '') {
                colrowclone.find('select.column_selector option[value='+colrow.find('select.column_selector').val()+']').attr('selected', 'selected');
            }
            if (colrow.find('select.advanced_selector').val() !== '') {
                colrowclone.find('select.advanced_selector option[value='+colrow.find('select.advanced_selector').val()+']').attr('selected', 'selected');
            }

            var lowersibling = colrow.next('tr');

            var lowersiblingclone = lowersibling.clone();
            // Set the selected option, cause for some reason this don't clone so well...
            if (lowersibling.find('select.column_selector').val() !== '') {
                lowersiblingclone.find('select.column_selector option[value='+lowersibling.find('select.column_selector').val()+']').attr('selected', 'selected');
            }
            if (lowersibling.find('select.advanced_selector').val() !== '') {
                lowersiblingclone.find('select.advanced_selector option[value='+lowersibling.find('select.advanced_selector').val()+']').attr('selected', 'selected');
            }

            $.ajax({
                url: M.cfg.wwwroot + '/totara/reportbuilder/ajax/column.php',
                type: "POST",
                data: ({action: 'movedown', sesskey: module.config.user_sesskey, id: module.config.rb_reportid, cid: colrow.data('colid')}),
                beforeSend: function() {
                    lowersibling.html(module.loadingimg);
                    colrow.html(module.loadingimg);
                    colrowclone.find('td *').hide();
                    lowersiblingclone.find('td *').hide();
                },
                success: function(o) {
                    if (o.success) {
                        // Switch!
                        colrow.replaceWith(lowersiblingclone);
                        lowersibling.replaceWith(colrowclone);

                        colrowclone.find('td *').fadeIn();
                        lowersiblingclone.find('td *').fadeIn();

                        // Fix option buttons
                        module.rb_reload_option_btns(colrowclone);
                        module.rb_reload_option_btns(lowersiblingclone);

                        module.rb_init_col_rows();

                    } else {
                        alert(M.util.get_string('error', 'moodle'));
                        // Reload the broken page
                        location.reload();
                    }

                },
                error: function(h, t, e) {
                    alert(M.util.get_string('error', 'moodle'));
                    // Reload the broken page
                    location.reload();
                }
            }); // ajax

        });
    },

    /**
     *
     */
    rb_init_moveup_btns: function() {
        var module = this;
        $('.reportbuilderform table .movecolupbtn').off('click');
        $('.reportbuilderform table .movecolupbtn').on('click', function(e) {
            e.preventDefault();
            var clickedbtn = $(this);

            var colrow = $(this).closest('tr');

            var colrowclone = colrow.clone();
            // Set the selected option, cause for some reason this don't clone so well...
            if (colrow.find('select.column_selector').val() !== '') {
                colrowclone.find('select.column_selector option[value='+colrow.find('select.column_selector').val()+']').attr('selected', 'selected');
            }
            if (colrow.find('select.advanced_selector').val() !== '') {
                colrowclone.find('select.advanced_selector option[value='+colrow.find('select.advanced_selector').val()+']').attr('selected', 'selected');
            }

            var uppersibling = colrow.prev('tr');

            var uppersiblingclone = uppersibling.clone();
            // Set the selected option, cause for some reason this don't clone so well...
            if (uppersibling.find('select.column_selector').val() !== '') {
                uppersiblingclone.find('select.column_selector option[value='+uppersibling.find('select.column_selector').val()+']').attr('selected', 'selected');
            }
            if (uppersibling.find('select.advanced_selector').val() !== '') {
                uppersiblingclone.find('select.advanced_selector option[value='+uppersibling.find('select.advanced_selector').val()+']').attr('selected', 'selected');
            }

            $.ajax({
                url: M.cfg.wwwroot + '/totara/reportbuilder/ajax/column.php',
                type: "POST",
                data: ({action: 'moveup', sesskey: module.config.user_sesskey, id: module.config.rb_reportid, cid: colrow.data('colid')}),
                beforeSend: function() {
                    uppersibling.html(module.loadingimg);
                    colrow.html(module.loadingimg);

                    colrowclone.find('td *').hide();
                    uppersiblingclone.find('td *').hide();
                },
                success: function(o) {
                    if (o.success) {
                        // Switch!
                        colrow.replaceWith(uppersiblingclone);
                        uppersibling.replaceWith(colrowclone);

                        colrowclone.find('td *').fadeIn();
                        uppersiblingclone.find('td *').fadeIn();

                        // Fix option buttons
                        module.rb_reload_option_btns(colrowclone);
                        module.rb_reload_option_btns(uppersiblingclone);

                        module.rb_init_col_rows();

                    } else {
                        alert(M.util.get_string('error', 'moodle'));
                        // Reload the broken page
                        location.reload();
                    }

                },
                error: function(h, t, e) {
                    alert(M.util.get_string('error', 'moodle'));
                    // Reload the broken page
                    location.reload();
                }
            }); // ajax

        });
    },

    /**
     *
     */
    rb_reload_option_btns: function(colrow) {
        var optionbox = colrow.children('td').filter(':last');
        var hideshowbtn = optionbox.find('.hidecolbtn');
        if (hideshowbtn.length == 0) {
            hideshowbtn = optionbox.find('.showcolbtn');
        }
        hideshowbtn = hideshowbtn.closest('a');

        optionbox.empty();

        // Replace with btns with updated ones
        var colid = colrow.data('colid');
        var deletebtn = this.rb_get_btn_delete(this.config.rb_reportid, colid);
        var upbtn = this.spacer;
        if (colrow.prev('tr').find('select.column_selector').length > 0) {
            upbtn = this.rb_get_btn_up(this.config.rb_reportid, colid);
        }
        var downbtn = this.spacer;
        if (colrow.next('tr').next('tr').find('select.column_selector').length > 0) {
            downbtn = this.rb_get_btn_down(this.config.rb_reportid, colid);
        }

        optionbox.append(hideshowbtn, deletebtn, upbtn, downbtn);
    },

    /**
     *
     */
    rb_get_btn_hide: function(reportid, colid) {
        return $('<a href="' + M.cfg.wwwroot + '/totara/reportbuilder/columns.php?id=' + reportid + '&cid='+colid+'&h=1" title="' + M.util.get_string('hide', 'totara_reportbuilder') + '" class="hidecolbtn action-icon">' + this.hideicon +'</a>');
    },

    rb_get_btn_show: function(reportid, colid) {
        return $('<a href="' + M.cfg.wwwroot + '/totara/reportbuilder/columns.php?id=' + reportid + '&cid='+colid+'&h=0" title="' + M.util.get_string('show', 'totara_reportbuilder') + '" class="showcolbtn action-icon">' + this.showicon + '</a>');
    },

    rb_get_btn_delete: function(reportid, colid) {
        return $('<a href="' + M.cfg.wwwroot + '/totara/reportbuilder/columns.php?id=' + reportid + '&cid='+colid+'&d=1" title="' + M.util.get_string('delete', 'totara_reportbuilder') + '" class="deletecolbtn action-icon">' + this.deleteicon + '</a>');
    },

    rb_get_btn_up: function(reportid, colid) {
        return $('<a href="' + M.cfg.wwwroot + '/totara/reportbuilder/columns.php?id=' + reportid + '&cid='+colid+'&m=up" title="' + M.util.get_string('moveup', 'totara_reportbuilder') + '" class="movecolupbtn action-icon">' + this.upicon + '</a>');
    },

    rb_get_btn_down: function(reportid, colid) {
        return $('<a href="' + M.cfg.wwwroot + '/totara/reportbuilder/columns.php?id=' + reportid + '&cid='+colid+'&m=down" title="' + M.util.get_string('movedown', 'totara_reportbuilder') + '" class="movecoldownbtn action-icon">' + this.downicon + '</a>');
    },

    rb_get_btn_add: function(reportid) {
        return $('<a href="' + M.cfg.wwwroot + '/totara/reportbuilder/columns.php?id=' + reportid + '" class="addcolbtn"><input type="button" value="' + M.util.get_string('add', 'totara_reportbuilder') + '" /></a>');
    }
};
