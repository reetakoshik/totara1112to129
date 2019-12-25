<?php
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
 * @author  Joby Harding <joby.harding@totaralms.com>
 * @author  Petr Skoda <petr.skoda@totaralms.com>
 * @package core
 */

/**
 * Information located pix/flex_icons.php files is merged to obtain
 * a full map of all flex icon definitions. Themes have the highest
 * priority and can override any map or translation item.
 */

/*
 * Translations array is expected to be used in plugins pix/flex_icons.php only.
 *
 * The data format is: array('mod_xxxx|someicon' => 'mapidentifier', 'mod_xxxx|otehricon' => 'mapidentifierx')
 */
$aliases = array(
    // NOTE: do not add anything here in core, use the $icons instead!
);

/*
 * Font icon map - this definition tells us how is each icon constructed.
 *
 * The identifiers in this core map are expected to be general
 * shape descriptions not directly related to Totara.
 *
 * In plugins the map is used when plugin needs a completely new icon
 * that is not defined here in core.
 */
$icons = array(
    /* Do not use 'flex-icon-missing' directly, it indicates requested icon was not found */
    'flex-icon-missing' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-question ft-stack-main',
                            'fa-exclamation ft-stack-suffix'
                        ),
                ),
        ),
    'alarm' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-alarm',
                ),
        ),
    'alarm-danger' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'ft-alarm ft-stack-main',
                            'fa-bolt ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'alarm-warning' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'ft-alarm ft-stack-main',
                            'fa-warning ft-stack-suffix ft-state-warning',
                        ),
                ),
        ),
    'arrow-down' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-arrow-down',
                ),
        ),
    'arrow-left' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-arrow-left',
                ),
        ),
    'arrow-right' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-arrow-right',
                ),
        ),
    'arrow-up' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-arrow-up',
                ),
        ),
    'arrows' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-arrows',
                ),
        ),
    'arrows-alt' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-arrows-alt',
                ),
        ),
    'arrows-h' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-arrows-h',
                ),
        ),
    'arrows-v' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-arrows-v',
                ),
        ),
    'attachment' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-paperclip',
                ),
        ),
    'backpack' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-backpack',
                ),
        ),
    'badge' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-trophy',
                ),
        ),
    'ban' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-ban',
                ),
        ),
    'bars' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-bars',
                ),
        ),
    'blended' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-blended',
                ),
        ),
    'block-dock' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-square-o-left ft-flip-rtl',
                ),
        ),
    'block-hide' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-minus-square',
                ),
        ),
    'block-show' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-plus-square',
                ),
        ),
    'block-undock' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-square-o-right ft-flip-rtl',
                ),
        ),
    'bookmark' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-bookmark-o',
                ),
        ),
    'books' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-books',
                ),
        ),
    'cache' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-bolt',
                ),
        ),
    'calculator' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-calculator',
                ),
        ),
    'calculator-off' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-calculator ft-stack-main',
                            'ft-slash ft-stack-over ft-state-danger',
                        ),
                ),
        ),
    'calendar' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-calendar',
                ),
        ),
    'caret-down' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-down',
                ),
        ),
    'caret-left' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-left',
                ),
        ),
    'caret-left-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-left ft-state-disabled',
                ),
        ),
    'caret-left-info' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-left ft-state-info',
                ),
        ),
    'caret-right' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-right',
                ),
        ),
    'caret-right-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-right ft-state-disabled',
                ),
        ),
    'caret-right-info' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-right ft-state-info',
                ),
        ),
    'caret-up' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-up',
                ),
        ),
    'certification' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-certificate',
                ),
        ),
    'check' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check',
                ),
        ),
    'check-circle-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check-circle-o',
                ),
        ),
    'check-circle-o-success' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check-circle-o ft-state-success',
                ),
        ),
    'check-circle-success' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check-circle ft-state-success',
                ),
        ),
    'check-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check ft-state-disabled',
                ),
        ),
    'check-square-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check-square-o',
                ),
        ),
    'check-success' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check ft-state-success',
                ),
        ),
    'check-warning' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check ft-state-warning',
                ),
        ),
    'checklist' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-checklist',
                ),
        ),
    'circle-danger' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-circle ft-state-danger',
                ),
        ),
    'circle-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-circle ft-state-disabled',
                ),
        ),
    'circle-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-circle-o',
                ),
        ),
    'circle-success' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-circle ft-state-success',
                ),
        ),
    'clock' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-clock-o',
                ),
        ),
    'clock-locked' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-clock-o ft-stack-main',
                            'fa-lock ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'close' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-times'
                ),
        ),
    'code' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-code',
                ),
        ),
    'cohort' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-users',
                ),
        ),
    'collapsed' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-right ft-flip-rtl'
                ),
        ),
    'collapsed-empty' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-right ft-flip-rtl ft-state-disabled'
                ),
        ),
    'columns' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-columns',
                ),
        ),
    'column-hide' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-minus-square'
                ),
        ),
    'column-show' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-plus-square'
                ),
        ),
    'comment' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-comment-o',
                ),
        ),
    'comment-add' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-comment-o ft-stack-main',
                            'fa-plus ft-stack-suffix',
                        ),
                ),
        ),
    'commenting-info' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-commenting ft-state-info',
                ),
        ),
    'comments' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-comments-o',
                ),
        ),
    'comments-search' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-comments-o ft-stack-main',
                            'fa-search ft-stack-suffix',
                        ),
                ),
        ),
    'competency' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-graduation-cap',
                ),
        ),
    'competency-achieved' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-graduation-cap ft-stack-main',
                            'fa-check ft-stack-suffix ft-state-success',
                        ),
                ),
        ),
    'completion-auto-enabled' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-check-circle-o ft-stack-main',
                            'fa-play ft-stack-suffix',
                        ),
                ),
        ),
    'completion-auto-fail' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-times-circle-o ft-state-danger',
                ),
        ),
    'completion-auto-n' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-circle-o',
                ),
        ),
    'completion-auto-pass' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check-circle-o ft-state-success',
                ),
        ),
    'completion-auto-y' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check-circle-o',
                ),
        ),
    'completion-manual-enabled' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-check-square-o ft-stack-main',
                            'fa-play ft-stack-suffix',
                        ),
                ),
        ),
    'completion-manual-n' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-square-o',
                ),
        ),
    'completion-manual-y' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check-square-o',
                ),
        ),
    'completion-rpl-n' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-square-o',
                ),
        ),
    'completion-rpl-y' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check-square-o',
                ),
        ),
    'contact-add' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'ft-address-book ft-stack-main',
                            'fa-plus ft-stack-suffix ft-state-info',
                        ),
                ),
        ),
    'contact-remove' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'ft-address-book ft-stack-main',
                            'fa-minus ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'course' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-cube',
                ),
        ),
    'course-completed' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-cube ft-stack-main',
                            'fa-check ft-stack-suffix ft-state-success',
                        ),
                ),
        ),
    'course-started' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-cube ft-stack-main',
                            'fa-play ft-stack-suffix',
                        ),
                ),
        ),
    'database' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-database',
                ),
        ),
    'deeper' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-right ft-flip-rtl'
                ),
        ),
    /* General delete icon to be used for all delete actions */
    'delete' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-times ft-state-danger',
                ),
        ),
    // Non-standard / no state delete. For use with dark background colours.
    'delete-ns' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-times',
                ),
        ),
    'delete-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-times ft-state-disabled',
                ),
        ),
    'document-edit' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-file-o ft-stack-main',
                            'fa-pencil ft-stack-suffix',
                        ),
                ),
        ),
    'document-new' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-file-o ft-stack-main',
                            'fa-plus ft-stack-suffix',
                        ),
                ),
        ),
    'document-properties' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-file-o ft-stack-main',
                            'fa-wrench ft-stack-suffix',
                        ),
                ),
        ),
    'dollar' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-dollar',
                ),
        ),
    'download' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-download',
                ),
        ),
    'duplicate' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-copy',
                ),
        ),
    'edit' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-edit',
                ),
        ),
    'email' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-envelope-o',
                ),
        ),
    'email-filled' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-envelope',
                ),
        ),
    'email-no' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-envelope-o ft-stack-main',
                            'ft-slash ft-stack-over ft-state-danger',
                        ),
                ),
        ),
    'emoticon-frown' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-frown-o',
                ),
        ),
    'emoticon-smile' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-smile-o',
                ),
        ),
    'enrolment-suspended' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-user ft-stack-main',
                            'fa-pause ft-stack-suffix',
                        ),
                ),
        ),
    'event-course' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-cube ft-stack-main',
                            'fa-clock-o ft-stack-suffix',
                        ),
                ),
        ),
    'event-group' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-users ft-stack-main',
                            'fa-clock-o ft-stack-suffix ft-state-info',
                        ),
                ),
        ),
    'event-user' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-user ft-stack-main',
                            'fa-clock-o ft-stack-suffix ft-state-info',
                        ),
                ),
        ),
    'exclamation-circle' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-exclamation-circle',
                ),
        ),
    'expand' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-expand',
                ),
        ),
    'expandable' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-down'
                ),
        ),
    'expanded' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-down'
                ),
        ),
    'explore' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-folder-o ft-stack-main',
                            'fa-search ft-stack-suffix',
                        ),
                ),
        ),
    'export' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-share-square-o',
                ),
        ),
    'external-link' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-external-link',
                ),
        ),
    'external-link-square' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-external-link-square',
                ),
        ),
    'file-archive' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-file-archive-o',
                ),
        ),
    'file-audio' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-volume-up',
                ),
        ),
    'file-chart' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-bar-chart',
                ),
        ),
    'file-code' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-file-code-o',
                ),
        ),
    'file-database' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-database',
                ),
        ),
    'file-ebook' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-book',
                ),
        ),
    'file-general' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-file-o',
                ),
        ),
    'file-image' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-file-image-o',
                ),
        ),
    'file-pdf' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-file-pdf-o',
                ),
        ),
    'file-powerpoint' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-file-powerpoint-o',
                ),
        ),
    'file-sound' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-file-sound-o',
                ),
        ),
    'file-spreadsheet' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-file-excel-o',
                ),
        ),
    'file-text' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-file-text-o',
                ),
        ),
    'file-video' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-file-video-o',
                ),
        ),
    'filter' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-filter',
                ),
        ),
    'flag-off' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-flag-o',
                ),
        ),
    'flag-on' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-flag',
                ),
        ),
    'folder-create' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-folder-o ft-stack-main',
                            'fa-plus ft-stack-suffix',
                        ),
                ),
        ),
    'folder' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-folder-o',
                ),
        ),
    'folder-open' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-folder-open-o',
                ),
        ),
    'grades' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-grades',
                ),
        ),
    'groups-no' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-users ft-stack-main',
                            'ft-slash ft-stack-main ft-state-danger',
                        ),
                ),
        ),
    'groups-separate' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-group-separate',
                ),
        ),
    'groups-visible' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-users',
                ),
        ),
    /* For links to Totara help */
    'help' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-info-circle ft-state-info',
                ),
        ),
    /* For action links that result in hiding of something */
    'hide' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-eye',
                ),
        ),
    'image' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-image',
                ),
        ),
    'indent' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-indent',
                ),
        ),
    'info' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-info-circle ft-state-info',
                ),
        ),
    'info-circle' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-info-circle',
                ),
        ),
    'key' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-key',
                ),
        ),
    'key-no' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-key ft-stack-main',
                            'ft-slash ft-stack-over ft-state-danger',
                        ),
                ),
        ),
    'laptop' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-laptop',
                ),
        ),
    'learningplan' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-briefcase',
                ),
        ),
    'level-up' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-level-up',
                ),
        ),
    'link' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-link',
                ),
        ),
    'loading' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-spinner fa-pulse',
                ),
        ),
    'lock' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-lock',
                ),
        ),
    'log' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-log',
                ),
        ),
    'marker-on' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-lightbulb-o',
                ),
        ),
    'marker-off' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-lightbulb-o ft-state-disabled',
                ),
        ),
    'mean' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-mean',
                ),
        ),
    'message' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-comment',
                ),
        ),
    'messages' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-comments',
                ),
        ),
    'minus' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-minus',
                ),
        ),
    'minus-square' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-minus-square',
                ),
        ),
    'minus-square-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-minus-square-o',
                ),
        ),
    'mnet-host' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-mnethost',
                ),
        ),
    'mouse-pointer' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-mouse-pointer',
                ),
        ),
    'move-down' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-arrow-down'
                ),
        ),
    'move-up' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-arrow-up'
                ),
        ),
    'nav-down' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-chevron-down',
                ),
        ),
    'nav-expand' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-chevron-right ft-flip-rtl',
                ),
        ),
    'nav-expanded' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-chevron-down',
                ),
        ),
    'navitem' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-square-small',
                ),
        ),
    'new' => // Something recently added.
        array(
            'data' =>
                array(
                    'classes' => 'ft-new',
                ),
        ),
    'news' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-newspaper-o',
                ),
        ),
    'notification' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-bell',
                ),
        ),
    'objective' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-bullseye',
                ),
        ),
    'objective-achieved' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-bullseye ft-stack-main',
                            'fa-check ft-stack-suffix ft-state-success',
                        ),
                ),
        ),
    'outcomes' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-pie-chart',
                ),
        ),
    'outdent' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-outdent',
                ),
        ),
    'package' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-package',
                ),
        ),
    'pencil' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-pencil',
                ),
        ),
    'pencil-square-info' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-pencil-square ft-state-info',
                ),
        ),
    'pencil-square-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-pencil-square-o',
                ),
        ),
    'permission-lock' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-user ft-stack-main',
                            'fa-lock ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'permissions' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-user ft-stack-main',
                            'fa-key ft-stack-suffix ft-state-info',
                        ),
                ),
        ),
    'permissions-check' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-user ft-stack-main',
                            'fa-warning ft-stack-suffix ft-state-warning',
                        ),
                ),
        ),
    'plus' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-plus',
                ),
        ),
    'plus-circle-info' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-plus-circle ft-state-info',
                ),
        ),
    'plus-square' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-plus-square',
                ),
        ),
    'plus-square-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-plus-square-o',
                ),
        ),
    'portfolio' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-profile',
                ),
        ),
    'portfolio-add' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'ft-profile ft-stack-main',
                            'fa-plus ft-stack-suffix',
                        ),
                ),
        ),
    'preferences' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-sliders',
                ),
        ),
    'preview' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-eye',
                ),
        ),
    'print' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-print',
                ),
        ),
    /* Totara program */
    'program' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-cubes',
                ),
        ),
    'publish' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-globe ft-stack-main',
                            'fa-play ft-stack-suffix ft-state-info',
                        ),
                ),
        ),
    'question' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-question',
                ),
        ),
    'question-circle' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-question-circle',
                ),
        ),
    'question-circle-warning' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-question-circle ft-state-warning',
                ),
        ),
    'ranges' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-stats-bars',
                ),
        ),
    'rating-star' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-star-half-o',
                ),
        ),
    'recordoflearning' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-archive',
                ),
        ),
    'recycle' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-recycle',
                ),
        ),
    'refresh' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-refresh',
                ),
        ),
    'repeat' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-repeat',
                ),
        ),
    /* Forms element required to be filled */
    'required' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-asterisk ft-state-danger',
                ),
        ),
    'risk-config' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-cogs ft-stack-main',
                            'fa-warning ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'risk-dataloss' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-database ft-stack-main',
                            'fa-warning ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'risk-managetrust' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-shield ft-stack-main',
                            'fa-warning ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'risk-personal' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-user ft-stack-main',
                            'fa-warning ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'risk-spam' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-envelope ft-stack-main',
                            'fa-warning ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'risk-xss' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-code ft-stack-main',
                            'fa-warning ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'rows' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-bars',
                ),
        ),
    'rss' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-rss',
                ),
        ),
    'save' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-save',
                ),
        ),
    'scales' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-stats-bars',
                ),
        ),
    'search' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-search',
                ),
        ),
    /* Settings or editing of stuff that changes how Totara works */
    'settings' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-cog',
                ),
        ),
    'settings-lock' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-cog ft-stack-main',
                            'fa-lock ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'settings-menu' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-cog ft-stack-main',
                            'fa-caret-down ft-stack-suffix',
                        ),
                ),
        ),
    'share-link' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-share-alt',
                ),
        ),
    /* Use for action icons that unhide something */
    'show' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-eye-slash',
                ),
        ),
    'sigma' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-sigma',
                ),
        ),
    'sigma-plus' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'ft-sigma ft-stack-main',
                            'fa-plus ft-stack-suffix',
                        ),
                ),
        ),
    'sign-out' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-sign-out',
                ),
        ),
    'site-lock' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-sitemap ft-stack-main',
                            'fa-lock ft-stack-suffix ft-state-danger',
                        ),
                ),
        ),
    'slash' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-slash',
                ),
        ),
    'sort' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-sort',
                ),
        ),
    'sort-asc' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-sort-asc',
                ),
        ),
    'sort-desc' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-sort-desc',
                ),
        ),
    'spacer' =>
        array(
            'template' => 'core/flex_icon_spacer',
        ),
    'square-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-square-o',
                ),
        ),
    'star' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-star',
                ),
        ),
    'star-off' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-star-o',
                ),
        ),
    'statistics' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-line-chart',
                ),
        ),
    'subcategory-no' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'ft-view-tree ft-stack-main',
                            'ft-slash ft-stack-over ft-state-danger',
                        ),
                ),
        ),
    'table' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-table',
                ),
        ),
    'tags-searchable' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-check-square-o'
                ),
        ),
    'tags-unsearchable' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-square-o',
                ),
        ),
    'thumbs-down-danger' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-thumbs-down ft-state-danger',
                ),
        ),
    'thumbs-up-success' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-thumbs-up ft-state-success',
                ),
        ),
    'times-circle-danger' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-times-circle ft-state-danger',
                ),
        ),
    'times-circle-o' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-times-circle-o',
                ),
        ),
    'times-circle-o-danger' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-times-circle-o ft-state-danger',
                ),
        ),
    'times-danger' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-times ft-state-danger',
                ),
        ),
    'toggle-off' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-toggle-off',
                ),
        ),
    'toggle-on' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-toggle-on',
                ),
        ),
    'totara' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-totara',
                ),
        ),
    'trash' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-trash',
                ),
        ),
    'tree-list-collapsed' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-angle-right',
                ),
        ),
    'tree-list-expanded' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-angle-down',
                ),
        ),
    'undo' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-undo',
                ),
        ),
    'unlink' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-unlink',
                ),
        ),
    'unlock' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-unlock',
                ),
        ),
    'unlocked' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-unlock-alt',
                ),
        ),
    'upload' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-upload',
                ),
        ),
    'user' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-user',
                ),
        ),
    'user-add' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-user-plus',
                ),
        ),
    'user-delete' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-user-times',
                ),
        ),
    'user-disabled' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-user ft-state-disabled',
                ),
        ),
    'user-refresh' =>
        array(
            'template' => 'core/flex_icon_stack',
            'data' =>
                array(
                    'classes' =>
                        array(
                            'fa-user ft-stack-main',
                            'fa-refresh ft-stack-suffix ft-state-info',
                        ),
                ),
        ),
    'user-secret' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-user-secret',
                ),
        ),
    'users' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-users',
                ),
        ),
    'view-grid' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-th-large',
                ),
        ),
    'view-large' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-th-large',
                ),
        ),
    'view-list' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-th-list',
                ),
        ),
    'view-tree' =>
        array(
            'data' =>
                array(
                    'classes' => 'ft-view-tree',
                ),
        ),
    'warning' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-warning ft-state-warning',
                ),
        ),
);

/*
 * Translation of old pix icon names to flex icon identifiers.
 *
 * The old core pix icon name format is "core|originalpixpath"
 * similar to the old pix placeholders in CSS.
 *
 * This information allows the pix_icon renderer
 * to automatically return the new flex icon markup.
 *
 * All referenced identifiers must be present in the $icons.
 *
 * Note that plugins are using the same identifier format
 * for $aliases, $deprecated and $icons "plugintype_pluginname|icon".
 */
$deprecated = array(
    'core|a/add_file' => 'document-new',
    'core|a/create_folder' => 'folder-create',
    'core|a/download_all' => 'download',
    'core|a/help' => 'help',
    'core|a/logout' => 'sign-out',
    'core|a/refresh' => 'refresh',
    'core|a/search' => 'search',
    'core|a/setting' => 'settings',
    'core|a/view_icon_active' => 'view-large',
    'core|a/view_list_active' => 'view-list',
    'core|a/view_tree_active' => 'view-tree',
    'core|b/bookmark-new' => 'bookmark',
    'core|b/document-edit' => 'document-edit',
    'core|b/document-new' => 'document-new',
    'core|b/document-properties' => 'document-properties',
    'core|b/edit-copy' => 'duplicate',
    'core|b/edit-delete' => 'delete',
    'core|c/event' => 'calendar',
    'core|docs' => 'info-circle',
    'core|f/archive' => 'file-archive',
    'core|f/audio' => 'file-audio',
    'core|f/avi' => 'file-video',
    'core|f/base' => 'file-database',
    'core|f/bmp' => 'file-image',
    'core|f/chart' => 'file-chart',
    'core|f/database' => 'file-database',
    'core|f/dmg' => 'file-archive',
    'core|f/document' => 'file-text',
    'core|f/edit' => 'pencil-square-o',
    'core|f/eps' => 'file-image',
    'core|f/epub' => 'file-ebook',
    'core|f/explore' => 'explore',
    'core|f/flash' => 'file-video',
    'core|f/folder' => 'folder',
    'core|f/folder-open' => 'folder-open',
    'core|f/gif' => 'file-image',
    'core|f/help-32' => 'help',
    'core|f/html' => 'file-code',
    'core|f/image' => 'file-image',
    'core|f/jpeg' => 'file-image',
    'core|f/markup' => 'file-code',
    'core|f/mov' => 'file-video',
    'core|f/move' => 'arrows',
    'core|f/mp3' => 'file-sound',
    'core|f/mpeg' => 'file-video',
    'core|f/parent-32' => 'level-up',
    'core|f/pdf' => 'file-pdf',
    'core|f/png' => 'file-image',
    'core|f/powerpoint' => 'file-powerpoint',
    'core|f/psd' => 'file-image',
    'core|f/quicktime' => 'file-video',
    'core|f/sourcecode' => 'file-code',
    'core|f/spreadsheet' => 'file-spreadsheet',
    'core|f/text' => 'file-text',
    'core|f/tiff' => 'file-image',
    'core|f/unknown' => 'file-general',
    'core|f/video' => 'file-video',
    'core|f/wav' => 'file-sound',
    'core|f/wmv' => 'file-video',
    'core|help' => 'help',
    'core|i/admin' => 'settings',
    'core|i/agg_mean' => 'mean',
    'core|i/agg_sum' => 'sigma',
    'core|i/ajaxloader' => 'loading',
    'core|i/assignroles' => 'user-add',
    'core|i/backup' => 'upload',
    'core|i/badge' => 'badge',
    'core|i/calc' => 'calculator',
    'core|i/calendar' => 'calendar',
    'core|i/caution' => 'exclamation-circle',
    'core|i/checkpermissions' => 'permissions-check',
    'core|i/closed' => 'folder',
    'core|i/cohort' => 'cohort',
    'core|i/completion-auto-enabled' => 'completion-auto-enabled',
    'core|i/completion-auto-fail' => 'completion-auto-fail',
    'core|i/completion-auto-n' => 'completion-auto-n',
    'core|i/completion-auto-pass' => 'completion-auto-pass',
    'core|i/completion-auto-y' => 'completion-auto-y',
    'core|i/completion-manual-enabled' => 'completion-manual-enabled',
    'core|i/completion-manual-n' => 'completion-manual-n',
    'core|i/completion-manual-y' => 'completion-manual-y',
    'core|i/configlock' => 'settings-lock',
    'core|i/course' => 'course',
    'core|i/courseevent' => 'event-course',
    'core|i/db' => 'database',
    'core|i/delete' => 'delete',
    'core|i/down' => 'arrow-down',
    'core|i/dragdrop' => 'arrows',
    'core|i/dropdown' => 'caret-down',
    'core|i/edit' => 'edit',
    'core|i/email' => 'email',
    'core|i/enrolmentsuspended' => 'enrolment-suspended',
    'core|i/enrolusers' => 'user-add',
    'core|i/export' => 'upload',
    'core|i/feedback' => 'comment',
    'core|i/feedback_add' => 'comment-add',
    'core|i/files' => 'file-general',
    'core|i/filter' => 'filter',
    'core|i/flagged' => 'flag-on',
    'core|i/folder' => 'folder',
    'core|i/grade_correct' => 'check-success',
    'core|i/grade_incorrect' => 'times-danger',
    'core|i/grade_partiallycorrect' => 'check-warning',
    'core|i/grades' => 'grades',
    'core|i/group' => 'users',
    'core|i/groupevent' => 'event-group',
    'core|i/groupn' => 'groups-no',
    'core|i/groups' => 'groups-separate',
    'core|i/groupv' => 'groups-visible',
    'core|i/guest' => 'user-secret',
    'core|i/hide' => 'hide',
    'core|i/hierarchylock' => 'site-lock',
    'core|i/import' => 'download',
    'core|i/info' => 'info-circle',
    'core|i/invalid' => 'times-danger',
    'core|i/item' => 'navitem',
    'core|i/key' => 'key',
    'core|i/loading' => 'loading',
    'core|i/loading_small' => 'loading',
    'core|i/lock' => 'lock',
    'core|i/log' => 'log',
    'core|i/manual_item' => 'edit',
    'core|i/marked' => 'marker-on',
    'core|i/marker' => 'marker-off',
    'core|i/mean' => 'mean',
    'core|i/menu' => 'bars',
    'core|i/mnethost' => 'mnet-host',
    'core|i/moodle_host' => 'totara', // Intentional change of branding for repositories on other Totara servers.
    'core|i/move_2d' => 'arrows',
    'core|i/navigationitem' => 'navitem',
    'core|i/new' => 'new',
    'core|i/news' => 'news',
    'core|i/nosubcat' => 'subcategory-no',
    'core|i/open' => 'folder-open',
    'core|i/outcomes' => 'outcomes',
    'core|i/payment' => 'dollar',
    'core|i/permissionlock' => 'permission-lock',
    'core|i/permissions' => 'permissions',
    'core|i/portfolio' => 'portfolio',
    'core|i/preview' => 'preview',
    'core|i/publish' => 'publish',
    'core|i/questions' => 'question',
    'core|i/reload' => 'refresh',
    'core|i/report' => 'file-text',
    'core|i/repository' => 'database',
    'core|i/restore' => 'download',
    'core|i/return' => 'undo',
    'core|i/risk_config' => 'risk-config',
    'core|i/risk_dataloss' => 'risk-dataloss',
    'core|i/risk_managetrust' => 'risk-managetrust',
    'core|i/risk_personal' => 'risk-personal',
    'core|i/risk_spam' => 'risk-spam',
    'core|i/risk_xss' => 'risk-xss',
    'core|i/rss' => 'rss',
    'core|i/scales' => 'scales',
    'core|i/scheduled' => 'clock',
    'core|i/search' => 'search',
    'core|i/self' => 'user',
    'core|i/settings' => 'settings',
    'core|i/show' => 'show',
    'core|i/siteevent' => 'calendar',
    'core|i/star-rating' => 'rating-star',
    'core|i/stats' => 'statistics',
    'core|i/switch' => 'toggle-on',
    'core|i/switchrole' => 'user-refresh',
    'core|i/twoway' => 'arrows-h',
    'core|i/unflagged' => 'flag-off',
    'core|i/unlock' => 'unlock',
    'core|i/up' => 'arrow-up',
    'core|i/user' => 'user',
    'core|i/useradd' => 'user-add',
    'core|i/userdel' => 'user-delete',
    'core|i/userevent' => 'event-user',
    'core|i/users' => 'users',
    'core|i/valid' => 'check-success',
    'core|i/warning' => 'warning',
    'core|i/withsubcat' => 'view-tree',
    'core|m/USD' => 'dollar',
    'core|req' => 'required',
    'core|spacer' => 'spacer',
    'core|t/add' => 'plus',
    'core|t/addcontact' => 'contact-add',
    'core|t/adddir' => 'folder-create',
    'core|t/addfile' => 'document-new',
    'core|t/approve' => 'check',
    'core|t/assignroles' => 'user-add',
    'core|t/award' => 'badge',
    'core|t/backpack' => 'backpack',
    'core|t/backup' => 'upload',
    'core|t/block' => 'ban',
    'core|t/block_to_dock' => 'block-dock',
    'core|t/block_to_dock_rtl' => 'block-dock',
    'core|t/cache' => 'cache',
    'core|t/calc' => 'calculator',
    'core|t/calc_off' => 'calculator-off',
    'core|t/calendar' => 'calendar',
    'core|t/check' => 'check',
    'core|t/cohort' => 'cohort',
    'core|t/collapsed' => 'caret-right',
    'core|t/collapsed_empty' => 'caret-right-disabled',
    'core|t/collapsed_empty_rtl' => 'caret-left-disabled',
    'core|t/collapsed_rtl' => 'caret-left',
    'core|t/contextmenu' => 'bars',
    'core|t/copy' => 'duplicate',
    'core|t/delete' => 'delete',
    'core|t/delete_gray' => 'delete-disabled',
    'core|t/disable_down' => 'arrow-down',
    'core|t/disable_up' => 'arrow-up',
    'core|t/dock_to_block' => 'block-undock',
    'core|t/dock_to_block_rtl' => 'block-undock',
    'core|t/dockclose' => 'times-circle-o',
    'core|t/down' => 'arrow-down',
    'core|t/download' => 'download',
    'core|t/dropdown' => 'caret-down',
    'core|t/edit' => 'settings',
    'core|t/edit_gray' => 'edit',
    'core|t/edit_menu' => 'settings-menu',
    'core|t/editstring' => 'edit',
    'core|t/email' => 'email',
    'core|t/emailno' => 'email-no',
    'core|t/enroladd' => 'plus',
    'core|t/enrolusers' => 'user-add',
    'core|t/expanded' => 'caret-down',
    'core|t/feedback' => 'comment',
    'core|t/feedback_add' => 'comment-add',
    'core|t/go' => 'circle-success',
    'core|t/grades' => 'grades',
    'core|t/groupn' => 'groups-no',
    'core|t/groups' => 'groups-separate',
    'core|t/groupv' => 'groups-visible',
    'core|t/hide' => 'hide',
    'core|t/left' => 'arrow-left',
    'core|t/less' => 'minus',
    'core|t/lock' => 'lock',
    'core|t/locked' => 'lock',
    'core|t/locktime' => 'clock-locked',
    'core|t/log' => 'log',
    'core|t/markasread' => 'check',
    'core|t/mean' => 'mean',
    'core|t/message' => 'message',
    'core|t/messages' => 'messages',
    'core|t/more' => 'plus',
    'core|t/move' => 'arrows-v',
    'core|t/portfolioadd' => 'portfolio-add',
    'core|t/preferences' => 'preferences',
    'core|t/preview' => 'preview',
    'core|t/print' => 'print',
    'core|t/ranges' => 'ranges',
    'core|t/recycle' => 'recycle',
    'core|t/reload' => 'refresh',
    'core|t/removecontact' => 'contact-remove',
    'core|t/reset' => 'undo',
    'core|t/restore' => 'download',
    'core|t/right' => 'arrow-right',
    'core|t/scales' => 'scales',
    'core|t/show' => 'show',
    'core|t/sigma' => 'sigma',
    'core|t/sigmaplus' => 'sigma-plus',
    'core|t/sort' => 'sort',
    'core|t/sort_asc' => 'sort-asc',
    'core|t/sort_desc' => 'sort-desc',
    'core|t/stop' => 'circle-danger',
    'core|t/stop_gray' => 'circle-disabled',
    'core|t/switch' => 'plus-square',
    'core|t/switch_minus' => 'minus-square',
    'core|t/switch_plus' => 'plus-square',
    'core|t/switch_plus_rtl' => 'plus-square',
    'core|t/switch_whole' => 'external-link-square',
    'core|t/unblock' => 'check',
    'core|t/unlock' => 'unlock',
    'core|t/unlocked' => 'unlocked',
    'core|t/up' => 'arrow-up',
    'core|t/user' => 'user',
    'core|t/viewdetails' => 'preview',
    'core|y/lm' => 'caret-down',
    'core|y/loading' => 'loading',
    'core|y/lp' => 'caret-right',
    'core|y/lp_rtl' => 'caret-left',
    'core|y/tm' => 'caret-down',
    'core|y/tp' => 'caret-right',
    'core|y/tp_rtl' => 'caret-left',
);

/*
 * Pix only images are not supposed to be converted to flex icons.
 *
 * - e/xxx pix icons should be used by Atto editor that does not support flex icons
 *
 */
$pixonlyimages = array(
    'e/abbr',
    'e/absolute',
    'e/accessibility_checker',
    'e/acronym',
    'e/advance_hr',
    'e/align_center',
    'e/align_left',
    'e/align_right',
    'e/anchor',
    'e/backward',
    'e/bold',
    'e/bullet_list',
    'e/cell_props',
    'e/cite',
    'e/cleanup_messy_code',
    'e/clear_formatting',
    'e/copy',
    'e/cut',
    'e/decrease_indent',
    'e/delete',
    'e/delete_col',
    'e/delete_row',
    'e/delete_table',
    'e/document_properties',
    'e/emoticons',
    'e/find_replace',
    'e/forward',
    'e/fullpage',
    'e/fullscreen',
    'e/help',
    'e/increase_indent',
    'e/insert',
    'e/insert_col_after',
    'e/insert_col_before',
    'e/insert_date',
    'e/insert_edit_image',
    'e/insert_edit_link',
    'e/insert_edit_video',
    'e/insert_file',
    'e/insert_horizontal_ruler',
    'e/insert_nonbreaking_space',
    'e/insert_page_break',
    'e/insert_row_after',
    'e/insert_row_before',
    'e/insert_time',
    'e/italic',
    'e/justify',
    'e/layers',
    'e/layers_over',
    'e/layers_under',
    'e/left_to_right',
    'e/manage_files',
    'e/math',
    'e/merge_cells',
    'e/new_document',
    'e/numbered_list',
    'e/page_break',
    'e/paste',
    'e/paste_text',
    'e/paste_word',
    'e/prevent_autolink',
    'e/preview',
    'e/print',
    'e/question',
    'e/redo',
    'e/remove_link',
    'e/remove_page_break',
    'e/resize',
    'e/restore_draft',
    'e/restore_last_draft',
    'e/right_to_left',
    'e/row_props',
    'e/save',
    'e/screenreader_helper',
    'e/search',
    'e/select_all',
    'e/show_invisible_characters',
    'e/source_code',
    'e/special_character',
    'e/spellcheck',
    'e/split_cells',
    'e/strikethrough',
    'e/styleprops',
    'e/subscript',
    'e/superscript',
    'e/table',
    'e/table_props',
    'e/template',
    'e/text_color',
    'e/text_color_picker',
    'e/text_highlight',
    'e/text_highlight_picker',
    'e/tick',
    'e/toggle_blockquote',
    'e/underline',
    'e/undo',
    'e/visual_aid',
    'e/visual_blocks',
    /* Default user images */
    'g/f1',
    'g/f2',
    'i/mahara_host',
    'u/f1',
    'u/f2',
    'u/f3',
    'u/user35',
    'u/user100',
    // Course catalogue images.
    'course_defaultimage'
);
