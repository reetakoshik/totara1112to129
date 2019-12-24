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
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralearning.com>
 * @package   elementlibrary
 */

global $CFG, $PAGE, $OUTPUT;

require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');

$strheading = 'Element Library: Pattern Library';
$url = new moodle_url('/elementlibrary/pattern_library.php');
admin_externalpage_setup('elementlibrary');

$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

$PAGE->requires->js_call_amd('core_elementlibrary/pattern_library', 'init');

// 'Back to top' button.
$toplink = '';
$toplink .= '<div class="row">';
$toplink .= '<div class="col-xs-12 col-lg-3">';
$toplink .= '<p><a href="#page">Back to top ' . $OUTPUT->flex_icon('arrow-up') . '</a></p>';
$toplink .= '</div>';
$toplink .= '</div>';

// 'Extends Bootstrap' label.
$extendsbootstrap = '<span class="label label-info">Extends Bootstrap ' . $OUTPUT->flex_icon('totara') . '</span>';

// Bootstrap documentation link.
$bootstrapdocstext = 'Bootstrap documentation ' . $OUTPUT->flex_icon('external-link-square');

// Component icon (box in Roots).
$componenticon = $OUTPUT->flex_icon('course');

echo $OUTPUT->header();

echo html_writer::link(new moodle_url('/elementlibrary/'), '&laquo; Back to index');
$icon = $OUTPUT->flex_icon('books');
echo "<h1>{$icon}&nbsp;Pattern Library <small> Element Library</small></h1>";
echo <<<EOF
<p>Items which extend the standard Bootstrap API are marked with the {$extendsbootstrap} label.</p>
EOF;

echo <<<EOF
<nav class="pattern-library__nav">
    <ul>
        <li><a href="#typography">Typography</a></li>
        <li><a href="#alerts">Alerts</a></li>
        <li><a href="#badges">Badges</a></li>
        <li><a href="#labels">Labels</a></li>
        <li><a href="#buttons">Buttons</a></li>
        <li><a href="#tabs">Tabs</a></li>
    </ul>
</nav>
EOF;

//
// Typography.
//
echo '<h2 id="typography">Typography</h2>';
echo '<p>Totara provides some additional variants on top of Bootstrap defaults.</p>';
echo '<p><a href="http://getbootstrap.com/components/#badges">' . $bootstrapdocstext . '</a></p>';

// Headings.
echo '<div class="row">';
echo '<div class="col-lg-6">';
echo '<h3>Headings</h3>';
$html = '<h1>h1. Header text</h1>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
$html = '<h2>h2. Header text</h2>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
$html = '<h3>h3. Header text</h3>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
$html = '<h4>h4. Header text</h4>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
$html = '<h5>h5. Header text</h5>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
$html = '<h6>h6. Header text</h6>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-lg-6

// Small heading segments.
echo '<div class="col-lg-6">';
echo '<h3>Small heading segments <small>Standard heading</small></h3>';
$html = '<h1>h1. Header <small>with a small segment</small></h1>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
$html = '<h2>h2. Header <small>with a small segment</small></h2>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
$html = '<h3>h3. Header <small>with a small segment</small></h3>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
$html = '<h4>h4. Header <small>with a small segment</small></h4>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
$html = '<h5>h5. Header <small>with a small segment</small></h5>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
$html = '<h6>h6. Header <small>with a small segment</small></h6>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-lg-6
echo '</div>'; // .row

echo '<hr />';

// Paragraphs.
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h3>Paragraphs</h3>';
$html = '<p>This is a paragraph. Paragraphs are preset with a font size, line height and spacing to match the overall vertical rhythm. To show what a paragraph looks like this needs a little more content &mdash; so, did you know that there are storms occurring on Jupiter that are larger than the Earth? Pretty cool. Wrap strong around type to <strong>make it bold</strong>! You can also use em to <em>italicize your words</em>.</p>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";

// Links.
echo '<h3>Links</h3>';
$html = '<p>Links are standard, and the <a href="#">color should be</a> based on the theme’s primary color and AA compliant.</p>';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";

// Block quotes.
echo '<h3>Block quotes</h3>';
$html = <<<EOF
<blockquote>
    <p>This is a quotation which should be given emphasis</p>
    <cite>Jody Brody</cite>
</blockquote>
EOF;
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-md-6

// Horizontal rule.
echo '<div class="col-md-6">';
echo '<h3>Horizontal rule</h3>';
$html = '<hr />';
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";

// Preformatted text.
echo '<h3>Preformatted text</h3>';
$html = <<<EOF
<pre>P R E F O R M A T T E D T E X T
! " # $ % & ' ( ) * + , - . /
0 1 2 3 4 5 6 7 8 9 : ; < = > ?
@ A B C D E F G H I J K L M N O
P Q R S T U V W X Y Z [ \ ] ^ _
` a b c d e f g h i j k l m n o
p q r s t u v w x y z { | } ~ </pre>
EOF;
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";

// Code blocks.
echo '<h3>Code blocks</h3>';
$html = <<<EOF
<pre><code>var place = 'world';
console.log('Hello ' + place);</code></pre>
EOF;
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";

echo '</div>'; // .col-md-6
echo '</div>'; // .row

echo '<hr />';

// Lists.
echo '<div class="row">';
echo '<div class="col-xs-12">';
echo '<h3>Lists</h3>';
echo '</div>'; // .col-xs-12
echo '</div>'; // .row

echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h4>Unordered list</h4>';
$html = <<<EOF
<ul>
    <li>This is a list item</li>
    <li>This is another list item</li>
    <li>Yet another list item
        <ul>
            <li>A nested list item</li>
            <li>Another nested list item</li>
            <li>List item</li>
        </ul>
    </li>
    <li>This is a list item</li>
    <li>This is a list item</li>
</ul>
EOF;
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";

echo '<h4>Ordered list</h4>';
$html =<<<EOF
<ol>
    <li>This is a list item</li>
    <li>This is another list item</li>
    <li>Yet another list item
        <ol>
            <li>A nested list item</li>
            <li>Another nested list item</li>
            <li>List item</li>
        </ol>
    </li>
    <li>This is a list item</li>
    <li>This is a list item</li>
</ol>
EOF;
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-md-6

echo '<div class="col-md-6">';
echo '<h4>Definition list</h4>';
$html = <<<EOF
<dl>
    <dt>Definition title</dt>
    <dd>Definition Cras justo odio, dapibus ac facilisis in, egestas eget quam. Nullam id dolor id nibh ultricies vehicula ut id elit.</dd>
    <dt>Definition title</dt>
    <dd>Definition Cras justo odio, dapibus ac facilisis in, egestas eget quam. Nullam id dolor id nibh ultricies vehicula ut id elit.</dd>
</dl>
EOF;
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-md-6

echo '</div>'; // .row

echo '<br />';
echo $toplink;
echo '<hr />';

//
// Alerts.
//
echo '<div class="row">';
echo '<div class="col-xs-12">';
echo '<h2 id="alerts">' . $componenticon . ' Alerts</h2>';
echo '<p>Notifications use alert markup. Note that the info type doesn\'t yet have a Totara notification equivalent.</p>';
echo '<p><a href="http://getbootstrap.com/components/#alerts">' . $bootstrapdocstext . '</a></p>';

echo '<h3>Success</h3>';
$html = $OUTPUT->notification('<strong>Hooray:</strong> Everything went splendidly have a <a href="#">link</a> to celebrate!', \core\output\notification::NOTIFY_SUCCESS);
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";

echo '<h3>Info</h3>';
$html = $OUTPUT->notification('<strong>So you know:</strong> Some additional useful <a href="#">information</a>',  \core\output\notification::NOTIFY_INFO); echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";

echo '<h3>Warning</h3>';
$html = $OUTPUT->notification('<strong>Watch out:</strong> Better check <a href="#">some stuff</a> before proceeding', \core\output\notification::NOTIFY_WARNING);
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";

echo '<h3>Danger</h3>';
$html = $OUTPUT->notification('<strong>Danger:</strong> You\'re about to do something <a href="#">drastic</a> and permanent',
\core\output\notification::NOTIFY_ERROR);
echo $html;
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '</div>';

echo '<br />';
echo $toplink;
echo '<hr />';

//
// Badges.
//
echo '<h2 id="badges">' . $componenticon . ' Badges</h2>';
echo '<p>Note Totara badges extend the Bootstrap 3 API by providing brand and state-based variants illustrated below.</p>';
echo '<p><a href="http://getbootstrap.com/components/#badges">' . $bootstrapdocstext . '</a></p>';

echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h3>Default</h3>';
$html = '<span class="badge">7</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '<div class="col-md-6">';
echo "<h3>Primary <small>{$extendsbootstrap}</small></h3>";
$html = '<span class="badge badge-primary">7</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '</div>';

echo '<div class="row">';
echo '<div class="col-md-6">';
echo "<h3>Info <small>{$extendsbootstrap}</small></h3>";
$html = '<span class="badge badge-info">7</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '<div class="col-md-6">';
echo "<h3>Success <small>{$extendsbootstrap}</small></h3>";
$html = '<span class="badge badge-success">7</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '</div>';

echo '<div class="row">';
echo '<div class="col-md-6">';
echo "<h3>Warning <small>{$extendsbootstrap}</small></h3>";
$html = '<span class="badge badge-warning">7</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '<div class="col-md-6">';
echo "<h3>Danger <small>{$extendsbootstrap}</small></h3>";
$html = '<span class="badge badge-danger">7</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '</div>';

echo '<br />';
echo $toplink;
echo '<hr />';

//
// Labels.
//
echo '<h2 id="labels">' . $componenticon . ' Labels</h2>';
echo '<p><a href="http://getbootstrap.com/components/#labels">' . $bootstrapdocstext . '</a></p>';
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h3>Default</h3>';
$html = '<span class="label label-default">Default</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '<div class="col-md-6">';
echo '<h3>Primary</h3>';
$html = '<span class="label label-primary">Primary</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '</div>';

echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h3>Info</h3>';
$html = '<span class="label label-info">Info</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '<div class="col-md-6">';
echo '<h3>Success</h3>';
$html = '<span class="label label-success">Success</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '</div>';

echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h3>Warning</h3>';
$html = '<span class="label label-warning">Warning</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '<div class="col-md-6">';
echo '<h3>Danger</h3>';
$html = '<span class="label label-danger">Danger</span>';
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>';
echo '</div>';

echo '<br />';
echo $toplink;
echo '<hr />';

//
// Buttons.
//
echo '<div class="row">';
echo '<div class="col-xs-12">';
echo '<h2 id="buttons">' . $componenticon . ' Buttons</h2>';
echo <<<EOF
<p>Buttons in Totara are based on the Bootstrap defaults with some additional types.</p>
EOF;
echo '<p><a href="http://getbootstrap.com/components/#buttons">' . $bootstrapdocstext . '</a></p>';

$html =<<<EOF
<a class="btn btn-default" href="#" role="button">Link</a>
<button class="btn btn-default" type="submit">Button</button>
<input class="btn btn-default" type="button" value="Input">
<input class="btn btn-default" type="submit" value="Submit">
EOF;
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-xs-12
echo '</div>'; // .row

// Button variants.
echo'<div class="row">';

echo'<div class="col-lg-6">';
echo '<h3>Default <small>with size variants</small></h3>';
$html =<<<EOF
<button type="button" class="btn btn-default btn-xs">Default tiny</button>
<button type="button" class="btn btn-default btn-sm">Default small</button>
<button type="button" class="btn btn-default">Default</button>
<button type="button" class="btn btn-default btn-lg">Default large</button>
EOF;
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-lg-6

echo'<div class="col-lg-6">';
echo '<h3>Primary <small>with size variants</small></h3>';
$html =<<<EOF
<button type="button" class="btn btn-primary btn-xs">Primary tiny</button>
<button type="button" class="btn btn-primary btn-sm">Primary small</button>
<button type="button" class="btn btn-primary">Primary</button>
<button type="button" class="btn btn-primary btn-lg">Primary large</button>
EOF;
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-lg-6

echo '</div>'; // .row
echo'<div class="row">';

echo'<div class="col-lg-6">';
echo "<h3>Secondary <small>with size variants {$extendsbootstrap}</small></h3>";
$html =<<<EOF
<button type="button" class="btn btn-secondary btn-xs">Secondary tiny</button>
<button type="button" class="btn btn-secondary btn-sm">Secondary small</button>
<button type="button" class="btn btn-secondary">Secondary</button>
<button type="button" class="btn btn-secondary btn-lg">Secondary large</button>
EOF;
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-lg-6

echo'<div class="col-lg-6">';
echo "<h3>Info <small>with size variants {$extendsbootstrap}</small></h3>";
$html =<<<EOF
<button type="button" class="btn btn-info btn-xs">Info tiny</button>
<button type="button" class="btn btn-info btn-sm">Info small</button>
<button type="button" class="btn btn-info">Info</button>
<button type="button" class="btn btn-info btn-lg">Info large</button>
EOF;
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-lg-6

echo'<div class="col-lg-6">';
echo '<h3>Success <small>with size variants</small></h3>';
$html =<<<EOF
<button type="button" class="btn btn-success btn-xs">Success tiny</button>
<button type="button" class="btn btn-success btn-sm">Success small</button>
<button type="button" class="btn btn-success">Success</button>
<button type="button" class="btn btn-success btn-lg">Success large</button>
EOF;
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-lg-6

echo '</div>'; // .row
echo '<div class="row">';

echo'<div class="col-lg-6">';
echo '<h3>Warning <small>with size variants</small></h3>';
$html =<<<EOF
<button type="button" class="btn btn-warning btn-xs">Warning tiny</button>
<button type="button" class="btn btn-warning btn-sm">Warning small</button>
<button type="button" class="btn btn-warning">Warning</button>
<button type="button" class="btn btn-warning btn-lg">Warning large</button>
EOF;
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-lg-6

echo'<div class="col-lg-6">';
echo '<h3>Danger <small>with size variants</small></h3>';
$html =<<<EOF
<button type="button" class="btn btn-danger btn-xs">Danger tiny</button>
<button type="button" class="btn btn-danger btn-sm">Danger small</button>
<button type="button" class="btn btn-danger">Danger</button>
<button type="button" class="btn btn-danger btn-lg">Danger large</button>
EOF;
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-lg-6

echo '</div>'; // .row

// Disabled state.
echo '<div class="row">';
echo'<div class="col-xs-12">';
echo '<h3>Disabled <small>state variant</small></h3>';

$html = array();

$html[] =<<<EOF
<!-- default -->
<button type="button" class="btn btn-default" disabled="disabled">Button</button>
<a role="button" class="btn btn-default disabled">Link</a>

<!-- primary -->
<button type="button" class="btn btn-primary" disabled="disabled">Button</button>
<a role="button" class="btn btn-primary disabled">Link</a>
EOF;

$html[] =<<<EOF
<!-- secondary -->
<button type="button" class="btn btn-secondary" disabled="disabled">Button</button>
<a role="button" class="btn btn-secondary disabled">Link</a>

<!-- success -->
<button type="button" class="btn btn-success" disabled="disabled">Button</button>
<a role="button" class="btn btn-success disabled">Link</a>
EOF;

$html[] =<<<EOF
<!-- info -->
<button type="button" class="btn btn-info" disabled="disabled">Button</button>
<a role="button" class="btn btn-info disabled">Link</a>

<!-- warning -->
<button type="button" class="btn btn-warning" disabled="disabled">Button</button>
<a role="button" class="btn btn-warning disabled">Link</a>
EOF;

$html[] =<<<EOF
<!-- danger -->
<button type="button" class="btn btn-danger" disabled="disabled">Button</button>
<a role="button" class="btn btn-danger disabled">Link</a>
EOF;

echo "<p>";
echo '<div class="row">';
echo '<div class="col-xs-12">';
foreach ($html as $examples) {
    echo $examples;
}
echo '</div>';
echo '</div>'; // .row
echo "</p>";
echo "<pre><code>";
echo htmlentities(implode(PHP_EOL . PHP_EOL, $html));
echo "</code></pre>";
echo '</div>'; // .col-xs-12
echo '</div>'; // .row

// Block variant.
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h3>Block <small>display variant</small></h3>';
$html =<<<EOF
<button type="button" class="btn btn-primary btn-block">Button</button>
EOF;
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-md-6
echo '</div>'; // .row

echo '<br />';
echo $toplink;
echo '<hr />';

//
// Tabs.
//
echo '<div class="row">';
echo '<div class="col-xs-12">';
echo '<h2 id="tabs">' . $componenticon . ' Tabs</h2>';
echo <<<EOF
<p>Tabs in Totara are based on the Bootstrap Nav defaults with some additional types.</p>
EOF;
echo '<p><a href="http://getbootstrap.com/components/#nav-tabs">' . $bootstrapdocstext . '</a></p>';
echo '<h3 id="tabs">Horizontal</h3>';
$html =<<<EOF
<ul class="nav nav-tabs">
    <li role="presentation"><a href="#">All audiences</a></li>
    <li role="presentation" class="active"><a href="#">System audiences</a></li>
    <li role="presentation"><a href="#">Add a new audience</a></li>
    <li role="presentation"><a href="#">Upload audiences</a></li>
</ul>
EOF;
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-xs-12
echo '</div>'; // .row

echo '<div class="row">';
echo '<div class="col-xs-12">';
echo '<h3 id="tabs">Disabled <small>state variant</small></h3>';
$html =<<<EOF
<ul class="nav nav-tabs">
    <li role="presentation" class="disabled"><a href="#">All audiences</a></li>
    <li role="presentation" class="disabled"><a href="#">System audiences</a></li>
    <li role="presentation" class="active"><a href="#">System audiences</a></li>
    <li role="presentation" class="disabled"><a href="#">Add a new audience</a></li>
    <li role="presentation" class="disabled"><a href="#">Upload audiences</a></li>
</ul>
EOF;
echo "<p>{$html}</p>";
echo "<pre><code>" . htmlentities($html) . "</code></pre>";
echo '</div>'; // .col-xs-12
echo '</div>'; // .row

echo '<br />';
echo $toplink;

echo $OUTPUT->footer();
