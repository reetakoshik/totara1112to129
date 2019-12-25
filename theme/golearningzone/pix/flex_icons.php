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
 * @author  Petr Skoda <petr.skoda@totaralms.com>
 * @package theme_bootstrapbase
 */

/* Developer documentation is in /pix/flex_icons.php file. */

/* Pix only images are not supposed to be converted to flex icons. */
$pixonlyimages = array(
    'screenshot',
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
    'file-writer' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-file-word-o',
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
    'core|f/writer' => 'file-writer',
    'core|f/document' => 'file-writer',
    'core|f/calc' => 'file-spreadsheet',
    'core|i/report' => 'outcomes',
);