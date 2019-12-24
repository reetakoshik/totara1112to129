<?php
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
 * @copyright 2017 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   elementlibrary
 */

require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');

$strheading = 'Element Library: Notifications';
$url = new moodle_url('/elementlibrary/notifications.php');

// Start setting up the page
admin_externalpage_setup('elementlibrary');
$params = array();
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

\core\notification::add('This notification was queued using <code>\core\notification::add()</code> <strong>before</strong> body content output started', \core\output\notification::NOTIFY_INFO);

echo $OUTPUT->header();

echo html_writer::link(new moodle_url('/elementlibrary/'), '&laquo; Back to index');
echo $OUTPUT->heading($strheading);
echo '<p>Notifications come in one of four different states: <code>info</code>, <code>success</code>, <code>warning</code> and <code>error</code>.</p>';

function code($content, $language='PHP') {
    global $OUTPUT;

    $icon = $OUTPUT->flex_icon('code');
    echo "<h5>{$icon} {$language}</h5>";

    return '<pre><code>' . htmlentities($content) . '</code></pre>';
}

//
// Inline notifications.
//
echo $OUTPUT->heading('Inline', 3);
echo '<p>In some situations it is desirable to output the notification element within page content and not the alert area at the top of the page. By default these alerts are not dismissable as we assume they are a key part of the page content.</p>';

echo '<br />';
echo $OUTPUT->heading('Info', 4);
$text = 'By the way you need to be aware of this';
echo $OUTPUT->notification($text, \core\output\notification::NOTIFY_INFO);
echo code("echo \$OUTPUT->notification('{$text}', \core\output\\notification::NOTIFY_INFO);");

echo $OUTPUT->heading('Success', 4);
$text = 'Hooray! It all went wonderfully';
echo $OUTPUT->notification($text, \core\output\notification::NOTIFY_SUCCESS);
echo code("echo \$OUTPUT->notification('{$text}', \core\output\\notification::NOTIFY_SUCCESS;");

echo $OUTPUT->heading('Warning', 4);
$text = 'You might need to take action on this';
echo $OUTPUT->notification($text, \core\output\notification::NOTIFY_WARNING);
echo code("echo \$OUTPUT->notification('{$text}', \core\output\\notification::NOTIFY_WARNING;");

echo $OUTPUT->heading('Error', 4);
$text = 'Oh no there was a problem at our end';
echo $OUTPUT->notification('Oh no something went wrong at our end', \core\output\notification::NOTIFY_ERROR);
echo code("echo \$OUTPUT->notification('{$text}', \core\output\\notification::NOTIFY_ERROR;");

echo '<hr />';

//
// Queued notifications.
//
echo $OUTPUT->heading('Notifications area', 3);
echo '<p>The notifications area sits at the top of the page above the main content. Notifications created with PHP can be queued for display at the next available opportunity; for example after a successful save operation to let the user know everything went as expected. Queued notifications appear above the main page content and are dismissable. Notifications rendered by JavaScript always appear in the notififcations area but are NOT added to the session queue. You can see an example of a notification output using the queue above the content on this page. The following examples serve to illustrate how the queued notifications will look.</p>';
echo '<br />';

echo $OUTPUT->heading('Info', 4);
$text = 'By the way you need to be aware of this';
echo $OUTPUT->render((new \core\output\notification($text, \core\output\notification::NOTIFY_INFO))->set_show_closebutton(true));
echo code(implode(PHP_EOL, [
    "\core\\notification::info('{$text}');",
    "// OR",
    "\core\\notification::add('{$text}', \core\output\\notification::NOTIFY_INFO);",
]));

function notification_js($type, $message, $selector) {
return <<<JS
// Note in your AMD modules use the define() function instead of require().
require(['core/notification'], function(notification) {

    // Add a click handler to the button below this code snippet.
    $('{$selector}').on('click', function(event) {

        // Render the '{$type}' notification.
        notification.addNotification({
            message: '{$message}' + ' added at ' + new Date().toString(),
            type: '{$type}'
        })

    });

});
JS;
}

$infojs = notification_js(\core\output\notification::NOTIFY_INFO, 'JavaScript info notification', '#notification-js-info');
echo code($infojs, 'JavaScript');
echo '<p><strong>Note:</strong> The notification will be added at the top of the page (scroll up to see it).</p>';
echo '<button id="notification-js-info">Add info notification</button>';


echo $OUTPUT->heading('Success', 4);
$text = 'Hooray! It all went wonderfully';
echo $OUTPUT->render((new \core\output\notification($text, \core\output\notification::NOTIFY_SUCCESS))->set_show_closebutton(true));
echo code(implode(PHP_EOL, [
    "\core\\notification::success('{$text}');",
    "// OR",
    "\core\\notification::add('{$text}', \core\output\\notification::NOTIFY_SUCCESS);",
]));

$successjs = notification_js(\core\output\notification::NOTIFY_SUCCESS, 'JavaScript info notification', '#notification-js-success');
echo code($successjs, 'JavaScript');
echo '<p><strong>Note:</strong> The notification will be added at the top of the page (scroll up to see it).</p>';
echo '<button id="notification-js-success">Add success notification</button>';

echo $OUTPUT->heading('Warning', 4);
$text = 'You might need to take action on this';
echo $OUTPUT->render((new \core\output\notification($text, \core\output\notification::NOTIFY_WARNING))->set_show_closebutton(true));
echo code(implode(PHP_EOL, [
    "\core\\notification::warning('{$text}');",
    "// OR",
    "\core\\notification::add('{$text}', \core\output\\notification::NOTIFY_WARNING);",
]));

$warningjs = notification_js(\core\output\notification::NOTIFY_WARNING, 'JavaScript warning notification', '#notification-js-warning');
echo code($warningjs, 'JavaScript');
echo '<p><strong>Note:</strong> The notification will be added at the top of the page (scroll up to see it).</p>';
echo '<button id="notification-js-warning">Add warning notification</button>';

echo $OUTPUT->heading('Error', 4);
$text = 'Oh no there was a problem at our end';
echo $OUTPUT->render((new \core\output\notification($text, \core\output\notification::NOTIFY_ERROR))->set_show_closebutton(true));
echo code(implode(PHP_EOL, [
    "\core\\notification::error('{$text}');",
    "// OR",
    "\core\\notification::add('{$text}', \core\output\\notification::NOTIFY_ERROR);",
]));

$errorjs = notification_js(\core\output\notification::NOTIFY_ERROR, 'JavaScript error notification', '#notification-js-error');
echo code($warningjs, 'JavaScript');
echo '<p><strong>Note:</strong> The notification will be added at the top of the page (scroll up to see it).</p>';
echo '<button id="notification-js-error">Add error notification</button>';

echo '<hr />';

//
// Legacy.
//

echo $OUTPUT->heading('Legacy', 3);
echo '<p>The following examples use legacy type class strings and remain here to visually test backwards compatibility.</p>';

echo $OUTPUT->notification('This is an error notification <a href="#">with a link</a>');

echo $OUTPUT->notification('This is a success notification <a href="#">with a link</a>', 'notifysuccess');

echo $OUTPUT->notification('This is a standard notification <a href="#">with a link</a>', 'notifymessage');

echo $OUTPUT->notification('This is a "notice" notification. <a href="#">with a link</a> At the moment we\'ve got a class "notifynotice" and the moodle class "notice" styled like this. We may want to remove the styles from "notice" or get rid of "notifynotice" and just use "notice" depending on how much "notice" is used and if it\'s appropriate.', 'notifynotice');

echo $OUTPUT->notification('This is a redirect message notification. It looks like it\'s supposed to be used on a blank page as it has 10% top margin', 'redirectmessage');

echo '<p>Note that these variants <strong>are not</strong> rendered using the <code>notification</code> output component and therefore may differ in markup structure potentially resulting in some visual differences depending on theme.';

echo '<br />';

echo $OUTPUT->error_text('This is an error generated using error_text(). I think this is used in form validation errors.');

echo $OUTPUT->box('This is a notice box <a href="#">with a link</a>, generated via box() with "generalbox" and "notice" classes added. Used by moodle in various places.', array('generalbox', 'notice'));

echo $OUTPUT->confirm('This is a confirmation box <a href="#">with a link</a>, generated by confirm(). It has the "box" and "generalbox" styles applied, as well as an id of "notice"', $url, $url);

echo '<br />';

echo $OUTPUT->container('In some cases you might want to prevent notifications from showing the border/background, in places where there is not enough room. You can do this with the .nobox class - any notifications inside a container with that class will not show the border/background. E.g.:');

echo '<br />';

echo $OUTPUT->container($OUTPUT->notification('This is a notification without the border/background', 'notifysuccess'), 'nobox');

echo '<h3>Box</h3>';

echo $OUTPUT->box('Generated by <code>$OUTPUT->box()</code> with <code>errorbox</code>, <code>alert</code> and <code>alert-info</code> classes', 'errorbox alert alert-info', null, array('data-rel' => 'fatalerror'));
echo $OUTPUT->box('Generated by <code>$OUTPUT->box()</code> with <code>errorbox</code>, <code>alert</code> and <code>alert-success</code> classes', 'errorbox alert alert-success', null, array('data-rel' => 'fatalerror'));
echo $OUTPUT->box('Generated by <code>$OUTPUT->box()</code> with <code>errorbox</code>, <code>alert</code> and <code>alert-warning</code> classes', 'errorbox alert alert-warning', null, array('data-rel' => 'fatalerror'));
echo $OUTPUT->box('Generated by <code>$OUTPUT->box()</code> with <code>errorbox</code>, <code>alert</code> and <code>alert-danger</code> classes', 'errorbox alert alert-danger', null, array('data-rel' => 'fatalerror'));

echo '<div class="alert alert-success">Plain old <code>&lt;div&gt;</code> containing text with <code>alert</code> and <code>alert-success</code> classes</div>';

\core\notification::add('This notification was queued using <code>\core\notification::add()</code> <strong>after</strong> body content output started', \core\output\notification::NOTIFY_INFO);

echo $OUTPUT->footer();

echo '<script type="text/javascript">' . implode(PHP_EOL, [$infojs, $successjs, $warningjs, $errorjs]) . '</script>';

