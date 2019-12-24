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

/**
 * Notification renderable component.
 *
 * @package    core
 * @copyright  2015 Jetha Chan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;

/**
 * Data structure representing a notification.
 *
 * @copyright 2015 Jetha Chan
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.9
 * @package core
 * @category output
 */
class notification implements \renderable, \templatable {

    /**
     * A notification of level 'success'.
     */
    const NOTIFY_SUCCESS = 'success';

    /**
     * A notification of level 'warning'.
     */
    const NOTIFY_WARNING = 'warning';

    /**
     * A notification of level 'info'.
     */
    const NOTIFY_INFO = 'info';

    /**
     * A notification of level 'error'.
     */
    const NOTIFY_ERROR = 'error';

    /**
     * @deprecated
     * A generic message.
     */
    const NOTIFY_MESSAGE = 'message';

    /**
     * @deprecated
     * A message notifying the user that a problem occurred.
     */
    const NOTIFY_PROBLEM = 'problem';

    /**
     * @deprecated
     * A notification of level 'redirect'.
     */
    const NOTIFY_REDIRECT = 'redirect';

    /**
     * @var string Message payload.
     */
    protected $message = '';

    /**
     * @var string Message type.
     */
    protected $messagetype = self::NOTIFY_WARNING;

    /**
     * @var bool $announce Whether this notification should be announced assertively to screen readers.
     */
    protected $announce = true;

    /**
     * @var bool $closebutton Whether this notification should inlcude a button to dismiss itself.
     *
     * Totara: This should be opt-in so default to false.
     */
    protected $closebutton = false;

    /**
     * @var array $extraclasses A list of any extra classes that may be required.
     */
    protected $extraclasses = array();

    /**
     * @var array $totara_customdata Legacy support for Totara notification 'options'.
     */
    protected $totara_customdata = [];

    /**
     * Totara: Map legacy types to class constants.
     *
     * @var array
     */
    protected static $legacymapping = [
        'notifyproblem'   => self::NOTIFY_ERROR,
        'notifytiny'      => self::NOTIFY_ERROR,
        'notifyerror'     => self::NOTIFY_ERROR,
        'notifysuccess'   => self::NOTIFY_SUCCESS,
        'notifymessage'   => self::NOTIFY_INFO,
        'notifyredirect'  => self::NOTIFY_INFO,
        'redirectmessage' => self::NOTIFY_INFO,
    ];

    /**
     * Notification constructor.
     *
     * @param string $message the message to print out
     * @param string $messagetype normally NOTIFY_PROBLEM or NOTIFY_SUCCESS.
     */
    public function __construct($message, $messagetype = null) {
        $this->message = $message;

        if (empty($messagetype)) {
            $messagetype = self::NOTIFY_ERROR;
        }

        $this->messagetype = $messagetype;

        switch ($messagetype) {
            case self::NOTIFY_PROBLEM:
            case self::NOTIFY_REDIRECT:
            case self::NOTIFY_MESSAGE:
                debugging('Use of ' . $messagetype . ' has been deprecated. Please switch to an alternative type.');
        }
    }

    /**
     * Set whether this notification should be announced assertively to screen readers.
     *
     * @param bool $announce
     * @return $this
     */
    public function set_announce($announce = false) {
        $this->announce = (bool) $announce;

        return $this;
    }

    /**
     * Set whether this notification should include a button to disiss itself.
     *
     * @param bool $button
     * @return $this
     */
    public function set_show_closebutton($button = false) {
        $this->closebutton = (bool) $button;

        return $this;
    }

    /**
     * Add any extra classes that this notification requires.
     *
     * @param array $classes
     * @return $this
     */
    public function set_extra_classes($classes = array()) {
        $this->extraclasses = $classes;

        return $this;
    }

    /**
     * Get the message for this notification.
     *
     * @return string message
     */
    public function get_message() {
        return $this->message;
    }

    /**
     * Get the message type for this notification.
     *
     * @return string message type
     */
    public function get_message_type() {
        return $this->messagetype;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return \stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        return array(
            'message'       => clean_text($this->message),
            'extraclasses'  => implode(' ', $this->extraclasses),
            'announce'      => $this->announce,
            'closebutton'   => $this->closebutton,
        );
    }

    public function get_template_name() {
        $templatemappings = [
            // Current types mapped to template names.
            'success'           => 'core/notification_success',
            'info'              => 'core/notification_info',
            'warning'           => 'core/notification_warning',
            'error'             => 'core/notification_error',
        ];

        if (isset($templatemappings[$this->messagetype])) {
            return $templatemappings[$this->messagetype];
        }
        return $templatemappings['error'];
    }

    /**
     * Given a string of classes attempt to map to a type class constant.
     *
     * This method supports TL-11584 / MDL-30811. Note that the core
     * $OUTPUT->notification helper does something similar but that
     * method is not used to render notifications from the queue which
     * (in Totara) may also have legacy class-string style type.
     *
     * @param string|null $typestring
     * @return mixed
     */
    public static function resolve_legacy_type($typestring) {

        // None was set.
        if ($typestring === null) {
            return null;
        }

        $classconstants = [self::NOTIFY_SUCCESS, self::NOTIFY_INFO, self::NOTIFY_WARNING, self::NOTIFY_ERROR];

        // If it's a class constant already then we don't need to do anything.
        if (in_array($typestring, $classconstants)) {
            return $typestring;
        }

        $classes = array_map('trim', explode(' ', $typestring));
        foreach($classes as $class) {
            // If there are more than one just return the first match.
            if (array_key_exists($class, self::$legacymapping)) {
                return self::$legacymapping[$class];
            }

            // Also check if a class constant has been added to the array of classes.
            if (in_array($class, $classconstants)) {
                return $class;
            }
        }

        // The default value of type to the constructor.
        return null;

    }

    /**
     * Given a typestring return custom classes which are not legacy types.
     *
     * TL-11584 / MDL-30811
     * Legacy notifications may provide a string of classes as
     * their type. This method will attempt to return an array
     * of custom classes which are NOT legacy notification type
     * classes so that they may still be applied to the instance
     * for backwards compatibility.
     *
     * @param string|null $typestring A string of classes.
     * @return array
     */
    public static function preserve_custom_classes($typestring) {

        // It's possible no type is set.
        if ($typestring === null) {
            return array();
        }

        // Everything including legacy types.
        $allclasses = array_map('trim', explode(' ', $typestring));

        // Return everything apart from the legacy types.
        return array_diff($allclasses, array_keys(self::$legacymapping));
    }


    /**
     * @param array $customdata
     */
    public function set_totara_customdata($customdata) {
        $this->totara_customdata = $customdata;

        return $this;
    }

    /**
     * @param array $customdata
     * @return array
     */
    public function get_totara_customdata() {
        return $this->totara_customdata;
    }

}
