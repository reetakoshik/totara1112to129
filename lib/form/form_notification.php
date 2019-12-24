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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara
 * @subpackage form
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

global $CFG;
require_once("HTML/QuickForm/static.php");
require_once('templatable_form_element.php');

/**
 * Form validation notification element
 *
 * overrides {@link HTML_QuickForm_static} to display static warning
 *
 * @package   core_form
 * @category  form
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_form_notification extends HTML_QuickForm_static implements templatable {

    use templatable_form_element {
        export_for_template as export_for_template_base;
    }

    /** @var string Name of the element */
    var $elementName;

    /** @var string Validation message to display */
    var $messagetext;

    /** @var string Message text type */
    var $messagetype;

    /** @var string Validation icon type */
    var $icontype;

    /**
     * constructor
     *
     * @param string $elementName name of the field
     * @param string $messagetext Warning message to display
     * @param string $type Type of message to display. Must be one of \core\output\notification consts
     */
    public function __construct($elementName=null, $messagetext=null, $type=null) {
        parent::__construct($elementName, null, $messagetext);

        $this->elementName = $elementName;
        $this->messagetext = $messagetext;
        $this->_type = 'notification';

        switch ($type) {
            case \core\output\notification::NOTIFY_SUCCESS :
            case \core\output\notification::NOTIFY_WARNING :
            case \core\output\notification::NOTIFY_INFO :
                $this->messagetype = $type;
                $this->icontype = $type;
                break;

            case \core\output\notification::NOTIFY_ERROR :
                $this->messagetype = 'danger';
                $this->icontype = $type;
                break;

            default:
                if (!empty($type)) {
                    debugging('Unknown type ' . $type . '. Default to ' . \core\output\notification::NOTIFY_ERROR . '.');
                }

                $this->messagetype = 'danger';
                $this->icontype = \core\output\notification::NOTIFY_ERROR;
                break;
        }
    }

    /**
     * Returns HTML for this form element.
     *
     * @return string
     */
    function toHtml() {
        global $OUTPUT;
        $context = $this->export_for_template($OUTPUT);
        return $OUTPUT->render_from_template('core_form/form_notification', $context);
    }

    public function export_for_template(renderer_base $output) {
        $alertflexicon = \core\output\flex_icon::get_icon('notification-' . $this->icontype);
        $this->alerticon = [
            'template' => $alertflexicon->get_template(),
            'context' => $alertflexicon->export_for_template($output)
        ];

        $context = $this->export_for_template_base($output);
        $context['level'] = $this->messagetype;
        $context['iconlevel'] = $this->icontype;
        $context['alerticon'] = $this->alerticon;
        $context['message'] = $this->messagetext;
        $context['notifyid'] = $context['id'] . '_notify';

        return $context;
    }
}
