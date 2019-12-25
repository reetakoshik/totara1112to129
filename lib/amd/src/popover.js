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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package core_output
 */

/**
 * Popover component JS
 *
 * @module  core_output
 * @class   Popover
 * @author  Brian Barnes <brian.barnes@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'theme_roots/bootstrap'], function($) {

    /**
     * Scans and initalises popover components that are yet to be initialised
     */
    function scan() {
        $('[data-component="/core/output/popover"][data-enhanced="false"]').each(function() {
            var popover = $(this);
            var parent = popover.parent();
            parent.attr('tabindex', 0)
                .css('cursor', 'pointer')
                .popover({
                    title: popover.data('title') ? popover.data('title') : '',
                    content: $('.popover__content', this).html(),
                    placement: 'bottom',
                    trigger: 'focus',
                    html: true
                });

            popover.attr('data-enhanced', "true");
        });
    }

    return {
        scan: scan
    };
});