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
 * @copyright 2015 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralms.com>>
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 * @package   elementlibrary
 */

use core\output\flex_icon;
use core\output\flex_icon_helper;

require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('elementlibrary');

$icons = flex_icon_helper::get_icons($CFG->theme);

echo $OUTPUT->header();

echo html_writer::link(new moodle_url('/elementlibrary/'), '&laquo; Back to index');
echo $OUTPUT->heading('Element Library: Flexible icons');

echo $OUTPUT->box_start();

$fontawesomelink = html_writer::link('https://fortawesome.github.io/Font-Awesome/', 'Font Awesome');

$iconsexamplecode =<<<EOF
<div class="my-page-section">
    {{! Output a 'edit' icon }}
    {{#flex_icon}}edit{{/flex_icon}}
</div>
EOF;
$iconsexamplecode = htmlentities(trim($iconsexamplecode));

echo $OUTPUT->heading('Flexible icons');
echo <<<EOF
<p>Flexible icons API is designed to provide more control over working with and delivering icons in Totara
than the existing PNG/SVG icon approach. Pix icons API should not be used any more for icons in new code,
only one-off graphical elements should use the pix_icon rendering and URLs.
Plugins that are not complatible with automatic conversion to flex icons need to be updated by developers.</p>

<p>This page diplays all flexible icons available in Totara core and illustrates how to use them.
The flexible icons are based on {$fontawesomelink} font-icons using Totara specific CSS classes -
but that doensn't mean you're limited to only using icons listed here. All flex icons can be overridden by your theme and
plugin developers may add more icons for individual plugins.</p>

<p>Flexible icons can be used from PHP or in any template via flex_icon helper.</p>
EOF;

echo $OUTPUT->heading('Simple icon example', 3);
$editicon = $OUTPUT->render(new flex_icon('edit'));
$editiconmarkup = htmlentities($editicon);
$editicondef = htmlentities("'edit' => " . var_export($icons['edit'], true));
echo <<<EOF
<p>Standard PHP code for rendering of an edit icon $editicon:<br />
<pre>
\$iconhtml = \$OUTPUT->flex_icon('edit');
</pre>It produces the following html markup:<br />
<pre>
$editiconmarkup
</pre>
Equivalent helper syntax in a template is:<br />
<pre>
{{#flex_icon}}edit{{/flex_icon}}
</pre>
The 'edit' icon is defined in core /pix/flex_icons.php file as:<br />
<pre>
$editicondef
</pre>
</p>
EOF;

echo $OUTPUT->heading('Stack icon example', 3);
$esicon = $OUTPUT->render(new flex_icon('enrolment-suspended'));
$esiconmarkup = htmlentities($esicon);
$esicondef = htmlentities("'enrolment-suspended' => " . var_export($icons['enrolment-suspended'], true));
echo <<<EOF
<p>Complex icons may be defined as stacks. PHP code for rendering of a suspended enrolment icon $esicon:<br />
<pre>
\$iconhtml = \$OUTPUT->flex_icon('enrolment-suspended');
</pre>It produces the following html markup:<br />
<pre>
$esiconmarkup
</pre>
Equivalent helper syntax in a template is:<br />
<pre>
{{#flex_icon}}enrolment-suspended{{/flex_icon}}
</pre>
The 'enrolment-suspended' icon is defined in core /pix/flex_icons.php file as:<br />
<pre>
$esicondef
</pre>
</p>
EOF;

echo $OUTPUT->heading('Icon with alt text', 3);
$editicon = $OUTPUT->render(new flex_icon('edit', array('alt' => get_string('edit'))));
$editiconmarkup = htmlentities($editicon);
echo <<<EOF
<p>In some cases icons need to have an alt attribute that is read out loud by screen readers,
PHP code for rendering of an edit icon with alternative text $editicon:<br />
<pre>
\$iconhtml = \$OUTPUT->flex_icon('edit', array('alt' => get_string('edit')));
</pre>It produces the following html markup:<br />
<pre>
$editiconmarkup
</pre>
Equivalent helper syntax in a template is:<br />
<pre>
{{#flex_icon}}edit,{"alt":"{{#str}}edit,core{{str}}"}{{/flex_icon}}
</pre>
</p>
EOF;

echo $OUTPUT->heading('Icon sizes', 3);
echo <<<EOF
<p>The following classes can be added to set the size of the font-based flexible icons.</p>
EOF;
$sizeclasses = array(
    'ft-size-100',
    'ft-size-200',
    'ft-size-300',
    'ft-size-400',
    'ft-size-500',
    'ft-size-600',
    'ft-size-700',
);
echo render_icons_table(array('alarm-warning', 'edit', 'permissions', 'loading'), $sizeclasses);
$editicon = $OUTPUT->render(new flex_icon('edit', array('classes' => 'ft-size-300')));
$editiconmarkup = htmlentities($editicon);
echo <<<EOF
<p>PHP code for rendering of a large edit icon $editicon:<br />
<pre>
\$iconhtml = \$OUTPUT->flex_icon('edit', array('classes' => 'ft-size-300'));
</pre>
Equivalent helper syntax in a template is:<br />
<pre>
{{#flex_icon}}edit,{"classes":"ft-size-500"}{{/flex_icon}}
</pre>
</p>
EOF;

echo $OUTPUT->heading('Icon states');
echo <<<EOF
<p>The following classes can be added to set the state of the font-based flexible icons.</p>
EOF;
$stateclasses = array(
    'ft-state-default',
    'ft-state-success',
    'ft-state-warning',
    'ft-state-danger',
    'ft-state-info',
    'ft-state-disabled',
);
echo render_icons_table(array('alarm-warning', 'edit', 'permissions'), $stateclasses);
$editicon = $OUTPUT->render(new flex_icon('edit', array('classes' => 'ft-state-disabled')));
$editiconmarkup = htmlentities($editicon);
echo <<<EOF
<p>PHP code for rendering of a disabled edit icon $editicon:<br />
<pre>
\$iconhtml = \$OUTPUT->flex_icon('edit', array('classes' => 'ft-state-disabled'));
</pre>
Equivalent helper syntax in a template is:<br />
<pre>
{{#flex_icon}}edit,{"classes":"ft-state-disabled"}{{/flex_icon}}
</pre>
</p>
EOF;

echo $OUTPUT->heading('pix/flex_icons.php files');
echo <<<EOF
<p>Flexible icons are defined in pix/flex_icons.php files. All non-core icon identifiers must use 'pluginname_pluginntype|' prefix.
The file format is PHP code with following variables:<br />
<dl>
<dt>\$icons</dt><dd>list of icons defined by identifier, template name (defaults to 'core/flex_icon') and template data</dd>
<dt>\$aliases</dt><dd>icon aliases referencing other \$icons identifiers</dd>
<dt>\$deprecated</dt><dd>deprecated icon aliases</dd>
<dt>\$pixonlyimages</dt><dd>array containing names of all other non-icon images stored in pix directory</dd>
</dl>
</p>
EOF;

echo $OUTPUT->heading('RTL aware icons');
echo <<<EOF
<p>Some icons need to be horizontally flipped in right-to-left languages, to do thisd evelopers may use ft-flip-rtl class in flex icon definition.
For example:<br />
<pre>
\$icons = array(
    'mod_book|nav_prev' =>
        array(
            'data' =>
                array(
                    'classes' => 'fa-caret-left ft-flip-rtl',
                ),
        ),
);
</pre>
</p>
EOF;

echo $OUTPUT->heading('Necessary code changes in pre-existing code', 3);
echo <<<EOF
<p>Flexible icons require minimal changes in existing code, in most cases new icons are
used automatically. Plugin developers only need to add a new <code>plugin/pix/flex_icons.php</code> file
that describes the mapping between legacy pix icon files and new flex icon identifiers.</p>
<p>The following three examples produce the same html markup:<br />
<pre>
\$flexiconhtml = \$OUTPUT->pix_icon('i/edit', '');   // Automatically converted to 'edit' flex icon.
\$flexiconhtml = \$OUTPUT->flex_icon('core|i/edit'); // Deprecated identifier for 'edit' flex icon.
\$flexiconhtml = \$OUTPUT->flex_icon('edit');        // Recommended new 'edit' icon usage.
</pre>
</p>
<p>Class <code>\core\output\flex_icon</code> can be used in most APIs instead of class <code>pix_icon</code>
because it is extending it internally. Legacy code that needs to use old html image markup has to get old
pix icon URL and use the html_writer instead:<br />
<pre>
\$editiconurl = \$PAGE->theme->pix_url('i/edit', 'core');
\$pixiconhtml = html_writer::img(\$editiconurl, '');
</pre>
or use the template renderer directly:<br />
<pre>
\$pixicon = new pix_icon('i/edit', '');
\$OUTPUPUT->render_from_template(\$flexicon->get_template(), \$pixicon->export_for_template(\$OUTPUT))
</pre>
</p>
<p>Please note that pix icons were never intended for general images, email messages or embedding in PDF documents.
All preexisting non-icon files in pix directories should be moved elsewhere.</p>
EOF;

$warningicon = $OUTPUT->flex_icon('warning');

echo $OUTPUT->heading('List of all standard core flex icons');
echo render_all_core_icons();
echo '<br />' . $OUTPUT->notification('<p><strong>Note:</strong> Brand icons should only be used to represent the company or product to which they refer.</p>', \core\output\notification::NOTIFY_WARNING);

echo $OUTPUT->heading('List of all plugin flex icons');
echo render_all_plugin_icons();
echo '<br />' . $OUTPUT->notification('<p><strong>Note:</strong> Brand icons should only be used to represent the company or product to which they refer.</p>', \core\output\notification::NOTIFY_WARNING);

echo $OUTPUT->box_end();

echo $OUTPUT->footer();

/**
 * Render examples of icons with given classes.
 */
function render_icons_table($identifiers, $classes) {
    global $OUTPUT;

    $table = new html_table();

    $tableheaders = array_map(function($optionalclass) {
        return new html_table_cell(html_writer::tag('code', ".{$optionalclass}"));
    }, $classes);

    array_unshift($tableheaders, 'identifier \ class');

    $table->head = $tableheaders;

    foreach ($identifiers as $identifier) {
        $cells = array_map(function($optionalclass) use ($OUTPUT, $identifier) {
            $flexicon = new flex_icon($identifier, array('classes' => $optionalclass));
            return new html_table_cell($OUTPUT->render($flexicon));
        }, $classes);

        array_unshift($cells, $identifier);

        $table->data[] = new html_table_row($cells);
    }

    return $OUTPUT->render($table);
}

/**
 * Render list of all core icons.
 */
function render_all_core_icons() {
    global $OUTPUT, $CFG;

    $icons = flex_icon_helper::get_icons($CFG->theme);
    $identifiers = array();
    foreach ($icons as $identifier => $icon) {
        if (!empty($icon['deprecated'])) {
            // No need to tell anybody about deprecated icons, they should not be used anywhere.
            continue;
        }
        if (strpos($identifier, '|') !== false) {
            // We do not want plugin icons here either
            // because they should be used strictly in individual plugins.
            continue;
        }
        $identifiers[] = $identifier;
    }
    sort($identifiers);

    $output = '<div class="row">';
    foreach ($identifiers as $identifier) {
        $output .= '<div class="col-xs-6 col-sm-4 col-md-3">' . $OUTPUT->flex_icon($identifier) . $identifier . '</div>';
    }
    $output .= '</div>'; // .row

    return $output;
}

/**
 * Render list of all core icons.
 */
function render_all_plugin_icons() {
    global $OUTPUT, $CFG;

    $icons = flex_icon_helper::get_icons($CFG->theme);
    $plugins = array();
    foreach ($icons as $identifier => $icon) {
        if (!empty($icon['deprecated'])) {
            // No need to tell anybody about deprecated icons, they should not be used anywhere.
            continue;
        }
        if (!preg_match('/([a-z0-9_]+)\|(.*)/', $identifier, $matches)) {
            continue;
        }
        $plugins[$matches[1]][] = $identifier;
    }
    ksort($plugins);

    $output = '';
    $count = 0;
    $numplugins = count($plugins);
    foreach ($plugins as $component => $identifiers) {
        $output .= $OUTPUT->heading($component, 3);
        sort($identifiers);
        $output .= '<div class="row">';
        foreach ($identifiers as $identifier) {
            $output .= '<div class="col-xs-12 col-sm-6">' . $OUTPUT->flex_icon($identifier) . $identifier . '</div>';
        }
        $output .= '</div>'; // .row;
        $count++;
        if ($count !== $numplugins) {
            $output .= '<hr />';
        }
    }

    return $output;
}
