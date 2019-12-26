<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace core;

/**
 * User Alert notifications.
 *
 * @package    core
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use stdClass;

defined('MOODLE_INTERNAL') || die();

class notification {
    /**
     * A notification of level 'success'.
     */
    const SUCCESS = 'success';

    /**
     * A notification of level 'warning'.
     */
    const WARNING = 'warning';

    /**
     * A notification of level 'info'.
     */
    const INFO = 'info';

    /**
     * A notification of level 'error'.
     */
    const ERROR = 'error';

    /**
     * Add a message to the session notification stack.
     *
     * @param string $message The message to add to the stack
     * @param string $level   The type of message to add to the stack
     */
    public static function add($message, $level = null) {
        global $PAGE, $SESSION;

        if ($PAGE && $PAGE->state === \moodle_page::STATE_IN_BODY) {
            // Currently in the page body - just render and exit immediately.
            // We insert some code to immediately insert this into the user-notifications created by the header.
            $id = uniqid();
            $renderable = (new \core\output\notification($message, $level))
                // Totara: We changed dismissal to opt-in. Notifications
                // area notifications should be dismissable
                ->set_show_closebutton(true);
            echo \html_writer::span(
                $PAGE->get_renderer('core')->render($renderable),
                '', array('id' => $id));

            // Insert this JS here using a script directly rather than waiting for the page footer to load to avoid
            // ensure that the message is added to the user-notifications section as soon as possible after it is created.
            echo \html_writer::script(
                    "(function() {" .
                        "var notificationHolder = document.getElementById('user-notifications');" .
                        "if (!notificationHolder) { return; }" .
                        "var thisNotification = document.getElementById('{$id}');" .
                        "if (!thisNotification) { return; }" .
                        "notificationHolder.appendChild(thisNotification.firstChild);" .
                        "thisNotification.remove();" .
                    "})();"
                );
            return;
        }

        // Totara: Abstract the queueing so we can reuse
        // without the logic above which forces output for
        // compatilbility with legacy Totara notifications.
        self::add_to_session_queue((object) array(
            'message'   => $message,
            'type'      => $level,
        ));
    }

    /**
     * Fetch all of the notifications in the stack and clear the stack.
     *
     * @return array All of the notifications in the stack
     */
    public static function fetch() {

        // Totara: Moved and extended fetch internals to fetch_filter()
        // to avoid duplication of code. In this instance the filter
        // always returns true as we want all notifications returned.
        return self::fetch_filter(function($item) { return true; });
    }

    /**
     * Fetch all of the notifications in the stack and clear the stack.
     *
     * @return array All of the notifications in the stack
     */
    public static function fetch_as_array(\renderer_base $renderer) {
        $notifications = [];
        foreach (self::fetch() as $notification) {
            $notifications[] = [
                'template'  => $notification->get_template_name(),
                'variables' => $notification->export_for_template($renderer),
            ];
        }
        return $notifications;
    }

    /**
     * Add a success message to the notification stack.
     *
     * @param string $message The message to add to the stack
     */
    public static function success($message) {
        return self::add($message, self::SUCCESS);
    }

    /**
     * Add a info message to the notification stack.
     *
     * @param string $message The message to add to the stack
     */
    public static function info($message) {
        return self::add($message, self::INFO);
    }

    /**
     * Add a warning message to the notification stack.
     *
     * @param string $message The message to add to the stack
     */
    public static function warning($message) {
        return self::add($message, self::WARNING);
    }

    /**
     * Add a error message to the notification stack.
     *
     * @param string $message The message to add to the stack
     */
    public static function error($message) {
        return self::add($message, self::ERROR);
    }

    /**
     * Append an item to the session queue.
     *
     * Totara: We split this functionality out of the add method
     * so that we may use it for legacy support.
     *
     * @param \stdClass $object
     */
    protected static function add_to_session_queue($object) {
        global $SESSION;

        if (!\core\session\manager::is_session_active()) {
            if (!PHPUNIT_TEST) {
                error_log('Invalid use of \core\notification detected - session is not active: ' . $object->message);
            }
            if (!isset($SESSION)) {
                // Totara: This only hides errors, the data will not be carried over to the next page!
                $SESSION = new stdClass();
            }
        }

        // Add the notification directly to the session.
        // This will either be fetched in the header, or by JS in the footer.
        if (!isset($SESSION->notifications) || !array($SESSION->notifications)) {
            $SESSION->notifications = [];
        }
        $SESSION->notifications[] = $object;

    }

    /**
     * Provide support for Totara legacy Totara notifications.
     *
     * Totara notifications must not be output immediately to
     * preserve legacy functionality either by PHP or JavaScript.
     * Existing code may be relying on this behaviour. We also set
     * a flag so that core/notifications JavaScript can identify
     * which notification data it should not process.
     *
     * @param string $message
     * @param string $level One of the notification type constants e.g.
     *                      \core\output\notification::NOTIFY_SUCCESS
     * @param array $customdata Array of key => value pairs (as per options in totara_add_notification).
     *              Provide this for backwards compatibility. We don't want to rely on this
     *              going forward. Existing customisations may be relying on this data.
     */
    public static function add_totara_legacy($message, $level = null, $customdata = array()) {
        $data = [
            'message' => $message,
            'type' => $level,
            'totara' => true,
        ];

        self::add_to_session_queue((object)array_merge($customdata, $data));
    }

    /**
     * Fetch notifications for which $filterfunction returns true and clear them from the stack.
     *
     * This implements and extends internals of fetch().
     *
     * Totara: This method is added so that we can support legacy Totara
     * notifications while we transition to core\notification. Specifically JavaScript
     * functionalty which calls this via core/notification::fetchNotifications()
     * (see implementation of totara_fetch_as_array() in this class).
     *
     * @param \Closure $filterfunction A function by which to filter fetched notifications.
     * @return array Filtered notifications from the stack.
     */
    protected static function fetch_filter($filterfunction) {
        global $SESSION;

        if (!isset($SESSION) || !isset($SESSION->notifications)) {
            return [];
        }

        // Filter results.
        $notifications = array_filter($SESSION->notifications, $filterfunction);

        // Remove filtered from the queue taking advantage of the
        // fact objects are assigned by reference to compare equality.
        $SESSION->notifications = array_udiff($SESSION->notifications, $notifications, function($a, $b) {
            if ($a === $b) {
                return 0;
            }
            if ($a !== $b) {
                return -1;
            }
            return 1;
        });

        // Ensure that array keys are reset (array_udiff() preserves them).
        $SESSION->notifications = array_values($SESSION->notifications);

        // If no values unset (so that we have same behaviour as fetch()).
        if (empty($SESSION->notifications)) {
            unset($SESSION->notifications);
        }

        $renderables = [];
        foreach ($notifications as $notification) {

            // For Totara legacy notifications compatibility.
            $classes = array();

            // If we're dealing with a legacy Totara notification
            // then attempt to determine the type based on legacy
            // classnames. The type property may actually be a
            // string of classes or could even be null.
            if (isset($notification->totara) && $notification->totara === true) {
                $classes = \core\output\notification::preserve_custom_classes($notification->type);
                $type = \core\output\notification::resolve_legacy_type($notification->type);
                $notification->type = $type;
            }

            // Support customdata for backwards-compatibility with totara_set_notification / totara_get_notification.
            $customdata = array_filter((array)$notification, function($key) {
                return !(in_array($key, ['message', 'type', 'totara']));
            }, ARRAY_FILTER_USE_KEY);

            $renderable = (new \core\output\notification($notification->message, $notification->type))
                // Totara: Legacy notifications compatibility.
                ->set_extra_classes($classes)
                // Totara: Queued notifications should be dismissable.
                ->set_show_closebutton(true)
                // Totara: Support 'options' customdata for backwards compatibility only.
                ->set_totara_customdata($customdata);

            $renderables[] = $renderable;
        }

        return $renderables;
    }

    /**
     * Fetch non Totara legacy notifications and return them as an array of arrays.
     *
     * Totara legacy code does not expect notifications to be requested
     * and flushed in the same page load if the notifications area has
     * already been renderered. This method prevents Totara notifications
     * being processed by the AJAX endpoint.
     *
     * @return array Non Totara notifications from the stack.
     */
    public static function totara_fetch_as_array(\renderer_base $renderer) {
        $notifications = [];
        $filterfunction = function($notification) {
            // We only want non-Totara legacy messages.
            if (property_exists($notification, 'totara') && $notification->totara === true) {
                return false;
            }
            return true;
        };
        foreach (self::fetch_filter($filterfunction) as $notification) {
            $notifications[] = [
                'template'  => $notification->get_template_name(),
                'variables' => $notification->export_for_template($renderer),
            ];
        }
        return $notifications;
    }

}
