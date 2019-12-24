<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when user downloads data export file.
 */
final class export_downloaded extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @param \stdClass $export
     * @param \stored_file $file
     * @return export_downloaded
     */
    public static function create_from_download(\stdClass $export, \stored_file $file) {
        $data = array(
            'relateduserid' => $export->userid,
            'objectid' => $export->id,
            'other' => array(
                'fileid' => $file->get_id(),
                'contenthash' => $file->get_contenthash(),
            ),
        );
        /** @var export_downloaded $event */
        $event = self::create($data);
        $event->add_record_snapshot('totara_userdata_export', $export);
        return $event;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'totara_userdata_export';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->context = \context_system::instance();
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventexportdownloaded', 'totara_userdata');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Data export {$this->objectid} archive file was downloaded by user {$this->userid}";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        // There is no page with detail of one export yet, point to list of all exports for the user for now.
        return new \moodle_url('/totara/userdata/exports.php', array('userid' => $this->relateduserid));
    }
}
